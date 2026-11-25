<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

const SEGUNDOS_INACTIVIDAD_SESION = 1800;

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('COMERCIAL_PPI_SESSION');

    $conexionSegura = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $conexionSegura = $conexionSegura || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $conexionSegura,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

header('Cache-Control: no-store, private');
header('Pragma: no-cache');

if (isset($_SESSION['ultimo_acceso']) && time() - (int) $_SESSION['ultimo_acceso'] > SEGUNDOS_INACTIVIDAD_SESION) {
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['mensaje_login'] = 'Tu sesión expiró por inactividad. Inicia sesión de nuevo.';
}

if (isset($_SESSION['id_usuario'])) {
    $_SESSION['ultimo_acceso'] = time();
}

if (empty($_SESSION['token_csrf'])) {
    $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
}

/** Devuelve true si hay una identidad autenticada en la sesión actual. */
function esta_autenticado(): bool
{
    return isset($_SESSION['id_usuario'], $_SESSION['usuario'], $_SESSION['rol'])
        && (int) $_SESSION['id_usuario'] > 0;
}

/** Devuelve la conexión compartida sin duplicarla en cada controlador. */
function obtener_conexion_seguridad(): mysqli
{
    global $conexion;

    if (!isset($conexion) || !($conexion instanceof mysqli)) {
        require_once dirname(__DIR__) . '/conexion.php';
    }

    if (!isset($conexion) || !($conexion instanceof mysqli)) {
        throw new RuntimeException('No se pudo cargar la conexión de seguridad.');
    }

    return $conexion;
}

/** Carga el estado y los permisos actuales desde la base, una vez por petición. */
function obtener_contexto_autorizacion(): array
{
    static $contextos = [];
    $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
    if ($idUsuario <= 0) {
        return ['activo' => false, 'id_rol' => 0, 'rol' => '', 'debe_cambiar_contrasena' => false, 'permisos' => []];
    }
    if (array_key_exists($idUsuario, $contextos)) {
        return $contextos[$idUsuario];
    }

    $contexto = ['activo' => false, 'id_rol' => 0, 'rol' => '', 'debe_cambiar_contrasena' => false, 'permisos' => []];
    try {
        $conexionSeguridad = obtener_conexion_seguridad();
        $stmt = $conexionSeguridad->prepare(
            'SELECT u.estado, u.debe_cambiar_contrasena, r.id_rol, r.nombre AS rol
             FROM usuario AS u
             INNER JOIN rol AS r ON r.id_rol = u.id_rol
             WHERE u.id_usuario = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario || $usuario['estado'] !== 'activo') {
            $contextos[$idUsuario] = $contexto;
            return $contexto;
        }

        $contexto['activo'] = true;
        $contexto['id_rol'] = (int) $usuario['id_rol'];
        $contexto['rol'] = (string) $usuario['rol'];
        $contexto['debe_cambiar_contrasena'] = (bool) $usuario['debe_cambiar_contrasena'];

        // El rol administrador es un rol raíz reservado para evitar que una
        // edición de permisos deje al sistema sin una cuenta de recuperación.
        if (in_array(strtolower(trim($contexto['rol'])), ['admin', 'administrador'], true)) {
            $contexto['permisos'] = ['*'];
        } else {
            $stmtPermisos = $conexionSeguridad->prepare(
                'SELECT p.codigo
                 FROM rol_permiso AS rp
                 INNER JOIN permiso AS p ON p.id_permiso = rp.id_permiso
                 WHERE rp.id_rol = ?'
            );
            $stmtPermisos->bind_param('i', $contexto['id_rol']);
            $stmtPermisos->execute();
            $resultadoPermisos = $stmtPermisos->get_result();
            while ($fila = $resultadoPermisos->fetch_assoc()) {
                $contexto['permisos'][] = (string) $fila['codigo'];
            }
            $stmtPermisos->close();
        }
    } catch (Throwable $error) {
        error_log('Error al cargar permisos de usuario: ' . $error->getMessage());
        $contexto = ['activo' => false, 'id_rol' => 0, 'rol' => '', 'debe_cambiar_contrasena' => false, 'permisos' => []];
    }

    $contextos[$idUsuario] = $contexto;
    return $contexto;
}

/** Comprueba el permiso actual del usuario, no solo el enlace del menú. */
function tiene_permiso(string $permiso): bool
{
    if (!esta_autenticado()) {
        return false;
    }

    $contexto = obtener_contexto_autorizacion();
    if (!$contexto['activo']) {
        return false;
    }

    if ($permiso === 'perfil.contrasena.cambiar') {
        return true;
    }

    return in_array('*', $contexto['permisos'], true)
        || in_array($permiso, $contexto['permisos'], true);
}

/** Exige una sesión autenticada; las llamadas AJAX reciben JSON si no la hay. */
function requerir_autenticacion(bool $esApi = false, bool $permitirCambioObligatorio = false): void
{
    if (!esta_autenticado()) {
        if ($esApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['estado' => false, 'mensaje' => 'Debes iniciar sesión.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . BASE_URL . 'autenticacion/login.php');
        exit;
    }

    $contexto = obtener_contexto_autorizacion();
    if (!$contexto['activo']) {
        $_SESSION = [];
        session_regenerate_id(true);
        if ($esApi) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['estado' => false, 'mensaje' => 'La cuenta está inactiva o ya no existe.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . BASE_URL . 'autenticacion/login.php');
        exit;
    }

    // Refresca el rol en sesión y fuerza el cambio de las claves temporales.
    unset($_SESSION['id_rol']); // dato heredado; el rol se consulta desde BD y este valor no se consume en la sesión.
    $_SESSION['rol'] = $contexto['rol'];
    $_SESSION['debe_cambiar_contrasena'] = $contexto['debe_cambiar_contrasena'];
    if ($contexto['debe_cambiar_contrasena'] && !$permitirCambioObligatorio) {
        if ($esApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'estado' => false,
                'codigo' => 'cambio_contrasena_requerido',
                'mensaje' => 'Debes cambiar tu contraseña temporal antes de continuar.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header('Location: ' . BASE_URL . 'autenticacion/cambiar_contrasena.php');
        exit;
    }
}

/** Muestra el rechazo dentro de la plantilla y conserva la URL solicitada. */
function mostrar_acceso_denegado(): void
{
    http_response_code(403);
    $baseUrl = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Acceso denegado - Casa Aurelia</title>
      <link rel="stylesheet" href="<?= $baseUrl ?>vendors/simplebar/css/simplebar.css">
      <link rel="stylesheet" href="<?= $baseUrl ?>css/vendors/simplebar.css">
      <link rel="stylesheet" href="<?= $baseUrl ?>css/style.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
      <script src="<?= $baseUrl ?>js/config.js"></script>
      <script src="<?= $baseUrl ?>js/color-modes.js"></script>
    </head>
    <body>
      <?php include dirname(__DIR__) . '/Barra_Lateral.php'; ?>
      <main class="body flex-grow-1 px-3">
        <div class="container-lg py-4">
          <div class="alert alert-danger d-flex align-items-start gap-3" role="alert" aria-live="assertive">
            <i class="bi bi-shield-lock-fill fs-4" aria-hidden="true"></i>
            <div>
              <h1 class="h5 mb-1">Acceso denegado</h1>
              <p class="mb-0">Tu usuario no tiene permiso para acceder a esta sección.</p>
            </div>
          </div>
        </div>
      </main>
      <footer class="footer px-4"><div>Casa Aurelia &copy; 2026.</div></footer>
      </div>
      <script src="<?= $baseUrl ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
      <script src="<?= $baseUrl ?>vendors/simplebar/js/simplebar.min.js"></script>
      <script src="<?= $baseUrl ?>js/Notificaciones.js"></script>
      <script>
        if (typeof notificar === 'function') {
          notificar('danger', 'Tu usuario no tiene permiso para acceder a esta sección.');
        }
      </script>
    </body>
    </html>
    <?php
    exit;
}

/** Exige sesión y permiso; las llamadas AJAX reciben JSON. */
function requerir_permiso(string $permiso, bool $esApi = false, bool $permitirCambioObligatorio = false): void
{
    requerir_autenticacion($esApi, $permitirCambioObligatorio);

    if ($esApi && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $tokenApi = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!validar_origen_solicitud() || !validar_token_csrf($tokenApi)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['estado' => false, 'mensaje' => 'La solicitud no pasó la validación de seguridad. Recarga la página e intenta de nuevo.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if (!tiene_permiso($permiso)) {
        http_response_code(403);
        if ($esApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['estado' => false, 'mensaje' => 'No tienes permiso para realizar esta acción.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        mostrar_acceso_denegado();
    }
}

/** Rechaza solicitudes que vienen de otro origen cuando el navegador informa Origin. */
function validar_origen_solicitud(): bool
{
    $origen = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origen === '') {
        return strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')) !== 'cross-site';
    }

    $partesOrigen = parse_url($origen);
    $hostPeticion = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $partesPeticion = parse_url('//' . $hostPeticion);
    if (!is_array($partesOrigen) || !is_array($partesPeticion)) {
        return false;
    }

    $usaHttps = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    $esquemaEsperado = $usaHttps ? 'https' : 'http';
    $puertoOrigen = (int) ($partesOrigen['port'] ?? ($esquemaEsperado === 'https' ? 443 : 80));
    $puertoPeticion = (int) ($partesPeticion['port'] ?? ($esquemaEsperado === 'https' ? 443 : 80));

    return strtolower((string) ($partesOrigen['scheme'] ?? '')) === $esquemaEsperado
        && strtolower((string) ($partesOrigen['host'] ?? '')) === strtolower((string) ($partesPeticion['host'] ?? ''))
        && $puertoOrigen === $puertoPeticion;
}

/** Las operaciones que escriben datos solo se aceptan mediante POST. */
function requerir_post_api(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        header('Allow: POST');
        echo json_encode(['estado' => false, 'mensaje' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/** Token anti-CSRF usado en formularios que cambian el estado de la sesión. */
function obtener_token_csrf(): string
{
    return (string) $_SESSION['token_csrf'];
}

function validar_token_csrf($token): bool
{
    return is_string($token)
        && isset($_SESSION['token_csrf'])
        && hash_equals((string) $_SESSION['token_csrf'], $token);
}

/** Devuelve un mensaje si una contraseña nueva incumple la política común. */
function validar_politica_contrasena(string $contrasena): ?string
{
    $longitud = function_exists('mb_strlen')
        ? mb_strlen($contrasena, 'UTF-8')
        : strlen($contrasena);

    if ($longitud < 12) {
        return 'La contraseña debe tener al menos 12 caracteres.';
    }
    if (strlen($contrasena) > 72) {
        return 'La contraseña no puede superar 72 bytes.';
    }
    if (preg_match('/\p{L}/u', $contrasena) !== 1
        || preg_match('/\p{N}/u', $contrasena) !== 1
        || preg_match('/[\p{P}\p{S}]/u', $contrasena) !== 1) {
        return 'La contraseña debe incluir al menos una letra, un número y un símbolo.';
    }

    return null;
}

/**
 * Crea el valor que debe guardarse en usuario.contrasena al crear o cambiar
 * una clave. password_hash genera una sal aleatoria y la guarda dentro del
 * resultado junto con el algoritmo; no se almacena la clave original.
 */
function crear_hash_contrasena(string $contrasena): string
{
    return password_hash($contrasena, PASSWORD_DEFAULT);
}

/**
 * Carga la clave maestra de cifrado desde una configuración local ignorada por Git
 * o desde el entorno. Debe ser Base64 de exactamente 32 bytes y mantenerse igual
 * en todos los servidores que comparten esta base de datos.
 */
function obtener_clave_maestra_usuario(): string
{
    static $clave = null;
    if (is_string($clave)) {
        return $clave;
    }

    $claveConfigurada = getenv('COMERCIAL_PPI_USER_ENCRYPTION_KEY');
    $archivoLocal = __DIR__ . '/configuracion_claves.local.php';
    if ((!is_string($claveConfigurada) || trim($claveConfigurada) === '') && is_file($archivoLocal)) {
        $configuracion = require $archivoLocal;
        if (is_array($configuracion)) {
            $claveConfigurada = $configuracion['clave_usuario'] ?? '';
        }
    }

    $claveDecodificada = is_string($claveConfigurada)
        ? base64_decode(trim($claveConfigurada), true)
        : false;
    if (!is_string($claveDecodificada) || strlen($claveDecodificada) !== 32) {
        throw new RuntimeException(
            'Falta configurar COMERCIAL_PPI_USER_ENCRYPTION_KEY con una clave Base64 de 32 bytes.'
        );
    }
    if (!extension_loaded('openssl')) {
        throw new RuntimeException('La extensión OpenSSL de PHP es necesaria para cifrar nombres de usuario.');
    }

    $clave = $claveDecodificada;
    return $clave;
}

/** Normaliza solo para comparar nombres de usuario sin distinguir mayúsculas. */
function normalizar_usuario_para_indice(string $usuario): string
{
    $usuario = trim($usuario);
    return function_exists('mb_strtolower')
        ? mb_strtolower($usuario, 'UTF-8')
        : strtolower($usuario);
}

/** HMAC determinista para buscar el usuario sin guardar su texto original. */
function crear_indice_usuario(string $usuario): string
{
    $claveIndice = hash_hmac('sha256', 'comercial-ppi:indice-usuario:v1', obtener_clave_maestra_usuario(), true);
    return hash_hmac('sha256', normalizar_usuario_para_indice($usuario), $claveIndice);
}

/** Cifra el usuario con AES-256-GCM, nonce aleatorio y etiqueta de autenticación. */
function cifrar_usuario(string $usuario): string
{
    $nonce = random_bytes(12);
    $etiqueta = '';
    $claveCifrado = hash_hmac('sha256', 'comercial-ppi:cifrado-usuario:v1', obtener_clave_maestra_usuario(), true);
    $cifrado = openssl_encrypt(
        trim($usuario),
        'aes-256-gcm',
        $claveCifrado,
        OPENSSL_RAW_DATA,
        $nonce,
        $etiqueta,
        'comercial-ppi:usuario:v1',
        16
    );
    if (!is_string($cifrado)) {
        throw new RuntimeException('No se pudo cifrar el nombre de usuario.');
    }

    return 'v1:' . base64_encode($nonce . $etiqueta . $cifrado);
}

/** Descifra usuarios nuevos y permite leer temporalmente filas heredadas sin migrar. */
function descifrar_usuario(string $valorAlmacenado): string
{
    if (strncmp($valorAlmacenado, 'v1:', 3) !== 0) {
        throw new RuntimeException('El nombre de usuario no tiene el formato cifrado esperado.');
    }

    $datos = base64_decode(substr($valorAlmacenado, 3), true);
    if (!is_string($datos) || strlen($datos) < 28) {
        throw new RuntimeException('El nombre de usuario cifrado tiene un formato inválido.');
    }

    $nonce = substr($datos, 0, 12);
    $etiqueta = substr($datos, 12, 16);
    $cifrado = substr($datos, 28);
    $claveCifrado = hash_hmac('sha256', 'comercial-ppi:cifrado-usuario:v1', obtener_clave_maestra_usuario(), true);
    $usuario = openssl_decrypt(
        $cifrado,
        'aes-256-gcm',
        $claveCifrado,
        OPENSSL_RAW_DATA,
        $nonce,
        $etiqueta,
        'comercial-ppi:usuario:v1'
    );
    if (!is_string($usuario)) {
        throw new RuntimeException('No se pudo descifrar el nombre de usuario; verifica la clave de aplicación.');
    }

    return $usuario;
}

function obtener_nombre_usuario(): string
{
    return (string) ($_SESSION['nombre'] ?? '');
}

function obtener_nombre_rol(): string
{
    return (string) ($_SESSION['rol'] ?? '');
}

function cerrar_sesion(): void
{
    $_SESSION = [];
    $parametros = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $parametros['path'] ?: '/',
        'domain' => $parametros['domain'],
        'secure' => $parametros['secure'],
        'httponly' => $parametros['httponly'],
        'samesite' => $parametros['samesite'] ?? 'Lax',
    ]);
    session_destroy();
}
