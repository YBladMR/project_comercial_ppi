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

/**
 * Comprueba permisos por rol.
 *
 * La tabla rol actual solo guarda nombre y descripción; todavía no existe una
 * relación rol_permiso. Por eso, en esta primera etapa solo Administrador tiene
 * permisos. Los demás roles quedan denegados hasta definir la matriz de acceso.
 */
function tiene_permiso(string $permiso): bool
{
    if (!esta_autenticado()) {
        return false;
    }

    $rol = strtolower(trim((string) $_SESSION['rol']));
    $permisosPorRol = [
        'administrador' => ['*'],
        'admin' => ['*'],
        // Agrega aquí permisos por rol cuando definamos la matriz de acceso.
    ];

    $permisos = $permisosPorRol[$rol] ?? [];
    return in_array('*', $permisos, true) || in_array($permiso, $permisos, true);
}

/** Exige una sesión autenticada; las llamadas AJAX reciben JSON si no la hay. */
function requerir_autenticacion(bool $esApi = false): void
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
}

/** Exige iniciar sesión y tener permiso; las llamadas AJAX reciben JSON. */
function requerir_permiso(string $permiso, bool $esApi = false): void
{
    requerir_autenticacion($esApi);

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

        echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso denegado</title>';
        echo '<body><h1>Acceso denegado</h1><p>Tu rol no tiene permiso para ver esta página.</p>';
        echo '<a href="' . htmlspecialchars(BASE_URL . 'index.php', ENT_QUOTES, 'UTF-8') . '">Volver al inicio</a></body></html>';
        exit;
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

/**
 * Crea el valor que debe guardarse en usuario.contrasena al crear o cambiar
 * una clave. password_hash genera una sal aleatoria y la guarda dentro del
 * resultado junto con el algoritmo; no se almacena la clave original.
 */
function crear_hash_contrasena(string $contrasena): string
{
    return password_hash($contrasena, PASSWORD_DEFAULT);
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
