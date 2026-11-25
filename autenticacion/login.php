<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';

$mensajeError = (string) ($_SESSION['mensaje_login'] ?? '');
unset($_SESSION['mensaje_login']);

$mensajeConfirmacion = '';
$usuarioIngresado = '';

if (isset($_GET['salida'])) {
    $mensajeConfirmacion = 'La sesión se cerró correctamente.';
} elseif (isset($_GET['registro'])) {
    $mensajeConfirmacion = 'Las cuentas son creadas por un administrador.';
}

if (esta_autenticado()) {
    requerir_autenticacion();
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioIngresado = trim((string) ($_POST['usuario'] ?? ''));
    $contrasenaIngresada = (string) ($_POST['contrasena'] ?? '');

    if (!validar_token_csrf($_POST['token_csrf'] ?? null)) {
        $mensajeError = 'La sesión del formulario venció. Recarga la página e intenta de nuevo.';
    } elseif ($usuarioIngresado === '' || $contrasenaIngresada === '') {
        $mensajeError = 'Escribe tu usuario y contraseña.';
    } else {
        require_once __DIR__ . '/../conexion.php';

        try {
            $indiceUsuario = crear_indice_usuario($usuarioIngresado);

            $sql = "SELECT u.id_usuario, u.usuario, u.usuario_indice, u.contrasena, u.nombre,
                           u.debe_cambiar_contrasena,
                           u.estado, r.nombre AS rol
                    FROM usuario AS u
                    INNER JOIN rol AS r ON r.id_rol = u.id_rol
                    WHERE u.usuario_indice = ?
                       OR (u.usuario_indice IS NULL AND u.usuario = ?)
                    LIMIT 1";

            $stmt = $conexion->prepare($sql);

            if (!$stmt) {
                throw new RuntimeException('No se pudo preparar la consulta de inicio de sesión.');
            }

            $stmt->bind_param('ss', $indiceUsuario, $usuarioIngresado);
            $stmt->execute();

            $resultado = $stmt->get_result();
            $usuario = $resultado ? $resultado->fetch_assoc() : null;
            $stmt->close();

            if ($usuario && $usuario['estado'] === 'activo'
                && password_verify($contrasenaIngresada, $usuario['contrasena'])) {

                $usuarioLegible = $usuario['usuario_indice'] === null
                    ? (string) $usuario['usuario']
                    : descifrar_usuario((string) $usuario['usuario']);

                if ($usuario['usuario_indice'] === null) {
                    $usuarioCifrado = cifrar_usuario($usuarioLegible);
                    $nuevoIndice = crear_indice_usuario($usuarioLegible);

                    $stmtUsuario = $conexion->prepare(
                        'UPDATE usuario SET usuario = ?, usuario_indice = ?
                         WHERE id_usuario = ? AND usuario_indice IS NULL'
                    );

                    if (!$stmtUsuario) {
                        throw new RuntimeException('No se pudo proteger el nombre de usuario.');
                    }

                    $stmtUsuario->bind_param(
                        'ssi',
                        $usuarioCifrado,
                        $nuevoIndice,
                        $usuario['id_usuario']
                    );

                    $stmtUsuario->execute();
                    $stmtUsuario->close();
                }

                if (password_needs_rehash($usuario['contrasena'], PASSWORD_DEFAULT)) {
                    $hashActualizado = crear_hash_contrasena($contrasenaIngresada);

                    $stmtHash = $conexion->prepare(
                        'UPDATE usuario SET contrasena = ? WHERE id_usuario = ?'
                    );

                    if ($stmtHash) {
                        $stmtHash->bind_param(
                            'si',
                            $hashActualizado,
                            $usuario['id_usuario']
                        );

                        $stmtHash->execute();
                        $stmtHash->close();
                    }
                }

                session_regenerate_id(true);

                $_SESSION = [
                    'id_usuario' => (int) $usuario['id_usuario'],
                    'usuario' => $usuarioLegible,
                    'nombre' => $usuario['nombre'],
                    'rol' => $usuario['rol'],
                    'debe_cambiar_contrasena' => (bool) $usuario['debe_cambiar_contrasena'],
                    'ultimo_acceso' => time(),
                    'token_csrf' => bin2hex(random_bytes(32)),
                ];

                $destino = !empty($usuario['debe_cambiar_contrasena'])
                    ? 'autenticacion/cambiar_contrasena.php'
                    : 'index.php';

                header('Location: ' . BASE_URL . $destino);
                exit;
            }

            $mensajeError = 'Usuario o contraseña incorrectos, o cuenta inactiva.';

        } catch (Throwable $error) {
            error_log('Error de inicio de sesión: ' . $error->getMessage());
            $mensajeError = 'No se pudo iniciar sesión en este momento. Intenta de nuevo.';
        }
    }
}

$tokenCsrf = obtener_token_csrf();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <base href="./../">

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description" content="Inicio de sesión Casa Aurelia Muebles">

    <title>Iniciar sesión | Casa Aurelia</title>

    <!-- Estilos de CoreUI -->
    <link rel="stylesheet" href="vendors/simplebar/css/simplebar.css">
    <link rel="stylesheet" href="css/vendors/simplebar.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/examples.css" rel="stylesheet">

    <style>
        /* ==============================
           ESTILOS GENERALES
        ============================== */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 35px 15px;
            transition: background .3s ease, color .3s ease;
        }

        .login-container {
            width: 100%;
            max-width: 475px;
            margin: auto;
        }

        /* ==============================
           MODO OSCURO
        ============================== */

        [data-coreui-theme="dark"] .login-page {
            background-color: #292e3a;
            color: #f5f5f5;
        }

        [data-coreui-theme="dark"] .login-card {
            background-color: #222733;
            border: 1px solid #39404d;
            color: #f5f5f5;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .12);
        }

        [data-coreui-theme="dark"] .login-card .form-control {
            background-color: #222733;
            border-color: #39404d;
            color: #f5f5f5;
        }

        [data-coreui-theme="dark"] .login-card .form-control::placeholder {
            color: #aeb6c3;
        }

        [data-coreui-theme="dark"] .login-card .input-group-text {
            background-color: #303745;
            border-color: #39404d;
            color: #aeb6c3;
        }

        [data-coreui-theme="dark"] .login-footer {
            color: #b8c0cc;
        }

        /* ==============================
           MODO CLARO
        ============================== */

        [data-coreui-theme="light"] .login-page {
            background-color: #f3f4f7;
            color: #252a34;
        }

        [data-coreui-theme="light"] .login-card {
            background-color: #ffffff;
            border: 1px solid #e0e3e9;
            color: #252a34;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        }

        [data-coreui-theme="light"] .login-card .form-control {
            background-color: #ffffff;
            border-color: #dce0e7;
            color: #252a34;
        }

        [data-coreui-theme="light"] .login-card .form-control::placeholder {
            color: #9299a5;
        }

        [data-coreui-theme="light"] .login-card .input-group-text {
            background-color: #f0f1f5;
            border-color: #dce0e7;
            color: #687181;
        }

        [data-coreui-theme="light"] .login-footer {
            color: #687181;
        }

        /* ==============================
           LOGO
        ============================== */

        .login-logo {
            display: block;
            width: 100%;
            max-width: 100%;
            height: 10rem;
            object-fit: contain;
            margin: 0 auto;
            padding: .75rem 1.25rem;
            background-color: transparent;
        }

        /* ==============================
           TARJETA DE INICIO DE SESIÓN
        ============================== */

        .login-card {
            border-radius: 10px;
            padding: 25px 22px;
            transition: background .3s ease, border .3s ease;
        }

        .login-title {
            font-size: 21px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 28px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .login-form .form-label {
            font-weight: 500;
            margin-bottom: 7px;
        }

        .login-form .form-control {
            min-height: 43px;
            border-radius: 7px;
        }

        .login-form .input-group-text {
            border-radius: 0 7px 7px 0;
        }

        .login-form .form-control:focus {
            border-color: #6961d9;
            box-shadow: 0 0 0 .2rem rgba(105, 97, 217, .15);
        }

        /* ==============================
           BOTÓN MOSTRAR CONTRASEÑA
        ============================== */

        #alternarContrasena {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
        }

        #alternarContrasena svg {
            width: 20px;
            height: 20px;
        }

        #contrasena::-ms-reveal,
        #contrasena::-ms-clear {
            display: none;
        }

        /* ==============================
           BOTÓN INGRESAR
        ============================== */

        .login-button {
            width: 100%;
            min-height: 43px;
            border-radius: 7px;
            font-weight: 500;
            background-color: #625bd1;
            border-color: #625bd1;
            color: #ffffff;
            transition: background .2s ease;
        }

        .login-button:hover {
            background-color: #5149bd;
            border-color: #5149bd;
            color: #ffffff;
        }

        /* ==============================
           MENSAJES
        ============================== */

        .login-message {
            text-align: center;
            font-size: 13px;
            margin-top: 12px;
            margin-bottom: 0;
        }

        .login-footer {
            text-align: center;
            font-size: 14px;
            margin-top: 24px;
        }

        /* ==============================
           RESPONSIVE
        ============================== */

        @media (max-width: 480px) {

            .login-page {
                padding: 25px 15px;
            }

            .login-logo {
                max-width: 100%;
                height: 8rem;
            }

            .login-card {
                padding: 22px 17px;
            }

            .login-title {
                font-size: 19px;
            }

            .login-form .d-flex.justify-content-between {
                flex-wrap: wrap;
                gap: 5px;
            }
        }
    </style>

    <!-- Configuración del tema -->
    <script src="js/config.js"></script>
    <script src="js/color-modes.js"></script>

</head>

<body>

    <div class="login-page">

        <div class="login-container">

            <!-- LOGO -->
            <div class="mb-4">
                <img
                    class="login-logo"
                    src="assets/img/Logo1.png"
                    alt="Casa Aurelia Muebles">
            </div>

            <!-- FORMULARIO -->
            <div class="login-card">

                <div class="login-title">
                    Inicia sesión en tu cuenta
                </div>

                <form
                    class="login-form"
                    action="<?php echo BASE_URL; ?>autenticacion/login.php"
                    method="post"
                    autocomplete="off">

                    <input
                        type="hidden"
                        name="token_csrf"
                        value="<?php echo htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- USUARIO -->
                    <div>
                        <label class="form-label" for="usuario">
                            Usuario
                        </label>

                        <input
                            class="form-control"
                            id="usuario"
                            name="usuario"
                            type="text"
                            value="<?php echo htmlspecialchars($usuarioIngresado, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="Tu usuario"
                            autocomplete="off"
                            maxlength="50"
                            required>
                    </div>

                    <!-- CONTRASEÑA -->
                    <div>

                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">

                            <label class="form-label mb-0" for="contrasena">
                                Contraseña
                            </label>

                            <span class="small text-body-secondary">
                                ¿Olvidaste tu contraseña? Contacta al administrador.
                            </span>

                        </div>

                        <div class="input-group">

                            <input
                                class="form-control"
                                id="contrasena"
                                name="contrasena"
                                type="password"
                                placeholder="Tu contraseña"
                                autocomplete="current-password"
                                required>

                            <span class="input-group-text">

                                <button
                                    id="alternarContrasena"
                                    class="bg-transparent border-0 p-0 link-secondary"
                                    type="button"
                                    aria-label="Mostrar contraseña"
                                    aria-pressed="false"
                                    title="Mostrar contraseña">

                                    <svg
                                        class="icon"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 512 512">

                                        <path
                                            fill="currentColor"
                                            d="M256 144.927a103.309 103.309 0 1 0 103.309 103.309A103.426 103.426 0 0 0 256 144.927m0 174.618a71.309 71.309 0 1 1 71.309-71.309A71.39 71.39 0 0 1 256 319.545">

                                        </path>

                                        <path
                                            fill="currentColor"
                                            d="m397.222 131.1-.218-.223c-77.75-77.749-204.258-77.749-282.008 0L16 233.79v28.893l98.778 102.689.218.222a199.41 199.41 0 0 0 282.008 0l99-102.911V233.79ZM464 249.79l-89.732 93.285a167.41 167.41 0 0 1-236.536 0L48 249.79v-3.107l89.729-93.283c65.247-65.13 171.3-65.13 236.542 0L464 246.683Z">

                                        </path>

                                        <path
                                            fill="currentColor"
                                            d="M240 232h32v32h-32z">

                                        </path>

                                    </svg>

                                </button>

                            </span>

                        </div>

                    </div>

                    <!-- TIEMPO DE SESIÓN -->
                    <p class="small text-body-secondary mb-0">
                        La sesión se cerrará después de 30 minutos de inactividad.
                    </p>

                    <!-- INGRESAR -->
                    <div>

                        <button
                            class="btn btn-primary login-button"
                            type="submit">

                            Ingresar

                        </button>

                        <!-- MENSAJES -->
                        <?php if ($mensajeError !== ''): ?>

                            <p class="login-message text-danger-emphasis" role="alert">

                                <?php echo htmlspecialchars($mensajeError, ENT_QUOTES, 'UTF-8'); ?>

                            </p>

                        <?php elseif ($mensajeConfirmacion !== ''): ?>

                            <p class="login-message text-success-emphasis" role="status">

                                <?php echo htmlspecialchars($mensajeConfirmacion, ENT_QUOTES, 'UTF-8'); ?>

                            </p>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

            <!-- PIE DE PÁGINA -->
            <div class="login-footer">
                Las cuentas son creadas por tu administrador.
            </div>

        </div>

    </div>

    <!-- SCRIPTS DE COREUI -->
    <script src="vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="vendors/simplebar/js/simplebar.min.js"></script>

    <!-- MOSTRAR Y OCULTAR CONTRASEÑA -->
    <script>

        const campoContrasena = document.getElementById("contrasena");

        const botonAlternarContrasena = document.getElementById("alternarContrasena");

        if (campoContrasena && botonAlternarContrasena) {

            botonAlternarContrasena.addEventListener("click", () => {

                const mostrar = campoContrasena.type === "password";

                campoContrasena.type = mostrar ? "text" : "password";

                const etiqueta = mostrar
                    ? "Ocultar contraseña"
                    : "Mostrar contraseña";

                botonAlternarContrasena.setAttribute("aria-label", etiqueta);

                botonAlternarContrasena.setAttribute("aria-pressed", String(mostrar));

                botonAlternarContrasena.title = etiqueta;

            });

        }

    </script>

</body>
</html>