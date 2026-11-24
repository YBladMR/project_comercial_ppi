<?php
declare(strict_types=1);

require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('usuarios.gestionar', true);
requerir_post_api();
require_once __DIR__ . '/../conexion.php';
header('Content-Type: application/json; charset=utf-8');

function responder_usuarios(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function es_rol_raiz_usuario(string $nombre): bool
{
    return in_array(strtolower(trim($nombre)), ['admin', 'administrador'], true);
}

function generar_contrasena_temporal(): string
{
    // 18 bytes aleatorios representados en hexadecimal: 36 caracteres seguros.
    return bin2hex(random_bytes(18));
}

function longitud_texto_usuario(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

$opcion = trim((string) ($_POST['opcion'] ?? ''));

try {
    if ($opcion === 'consultar') {
        $sql = "SELECT u.id_usuario, u.usuario, u.usuario_indice, u.correo_electronico,
                       u.nombre, u.estado, u.fecha_creacion,
                       r.id_rol, r.nombre AS rol
                FROM usuario AS u
                INNER JOIN rol AS r ON r.id_rol = u.id_rol
                ORDER BY u.nombre, u.id_usuario";
        $resultado = $conexion->query($sql);
        $usuarios = [];
        $stmtMigrar = $conexion->prepare(
            'UPDATE usuario SET usuario = ?, usuario_indice = ?
             WHERE id_usuario = ? AND usuario_indice IS NULL'
        );
        while ($fila = $resultado->fetch_assoc()) {
            $fila['id_usuario'] = (int) $fila['id_usuario'];
            $fila['id_rol'] = (int) $fila['id_rol'];
            $fila['protegido'] = es_rol_raiz_usuario((string) $fila['rol']);
            $usuarioLegible = $fila['usuario_indice'] === null
                ? (string) $fila['usuario']
                : descifrar_usuario((string) $fila['usuario']);
            if ($fila['usuario_indice'] === null) {
                $usuarioCifrado = cifrar_usuario($usuarioLegible);
                $indice = crear_indice_usuario($usuarioLegible);
                $stmtMigrar->bind_param('ssi', $usuarioCifrado, $indice, $fila['id_usuario']);
                $stmtMigrar->execute();
            }
            $fila['usuario'] = $usuarioLegible;
            unset($fila['usuario_indice']);
            $usuarios[] = $fila;
        }
        $stmtMigrar->close();
        responder_usuarios(['estado' => true, 'usuarios' => $usuarios]);
    }

    if ($opcion === 'consultarRoles') {
        $resultado = $conexion->query(
            "SELECT id_rol, nombre FROM rol
             WHERE LOWER(TRIM(nombre)) NOT IN ('admin', 'administrador')
             ORDER BY nombre"
        );
        $roles = [];
        while ($fila = $resultado->fetch_assoc()) {
            $roles[] = ['id_rol' => (int) $fila['id_rol'], 'nombre' => $fila['nombre']];
        }
        responder_usuarios(['estado' => true, 'roles' => $roles]);
    }

    if ($opcion === 'crear') {
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $correo = trim((string) ($_POST['correo_electronico'] ?? ''));
        $correo = $correo === '' ? null : $correo;
        $idRol = filter_var($_POST['id_rol'] ?? null, FILTER_VALIDATE_INT);
        if ($nombre === '' || longitud_texto_usuario($nombre) > 100 || $usuario === '' || longitud_texto_usuario($usuario) > 50
            || preg_match('/^\d{8}-\d$/', $usuario) || ($correo !== null && (strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL)))
            || !$idRol || $idRol <= 0) {
            responder_usuarios(['estado' => false, 'mensaje' => 'Revisa el nombre, usuario, correo y rol. El usuario no puede ser un DUI.']);
        }

        $stmtRol = $conexion->prepare(
            "SELECT id_rol FROM rol
             WHERE id_rol = ? AND LOWER(TRIM(nombre)) NOT IN ('admin', 'administrador')"
        );
        $stmtRol->bind_param('i', $idRol);
        $stmtRol->execute();
        $rolValido = $stmtRol->get_result()->fetch_assoc();
        $stmtRol->close();
        if (!$rolValido) {
            responder_usuarios(['estado' => false, 'mensaje' => 'Selecciona un rol válido.']);
        }

        $contrasenaTemporal = generar_contrasena_temporal();
        $hash = crear_hash_contrasena($contrasenaTemporal);
        $usuarioCifrado = cifrar_usuario($usuario);
        $indiceUsuario = crear_indice_usuario($usuario);
        $stmt = $conexion->prepare(
            'INSERT INTO usuario (usuario, usuario_indice, correo_electronico, contrasena, nombre, id_rol, estado, debe_cambiar_contrasena)
             VALUES (?, ?, ?, ?, ?, ?, \'activo\', 1)'
        );
        $stmt->bind_param('sssssi', $usuarioCifrado, $indiceUsuario, $correo, $hash, $nombre, $idRol);
        $stmt->execute();
        $idUsuario = (int) $stmt->insert_id;
        $stmt->close();
        responder_usuarios([
            'estado' => true,
            'mensaje' => 'Cuenta creada. Entrega la contraseña temporal al empleado.',
            'usuario' => $usuario,
            'contrasena_temporal' => $contrasenaTemporal,
            'id_usuario' => $idUsuario
        ]);
    }

    if ($opcion === 'actualizar') {
        $idUsuario = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        $idRol = filter_var($_POST['id_rol'] ?? null, FILTER_VALIDATE_INT);
        $estado = trim((string) ($_POST['estado'] ?? ''));
        $correo = trim((string) ($_POST['correo_electronico'] ?? ''));
        $correo = $correo === '' ? null : $correo;
        if (!$idUsuario || $idUsuario <= 0 || !$idRol || $idRol <= 0 || !in_array($estado, ['activo', 'inactivo'], true)
            || ($correo !== null && (strlen($correo) > 254 || !filter_var($correo, FILTER_VALIDATE_EMAIL)))) {
            responder_usuarios(['estado' => false, 'mensaje' => 'Los datos enviados no son válidos. Revisa también el correo.']);
        }
        if ((int) $idUsuario === (int) $_SESSION['id_usuario']) {
            responder_usuarios(['estado' => false, 'mensaje' => 'No puedes cambiar tu propio rol o estado desde esta pantalla.']);
        }

        $stmtActual = $conexion->prepare(
            'SELECT u.id_rol, r.nombre AS rol FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol WHERE u.id_usuario = ?'
        );
        $stmtActual->bind_param('i', $idUsuario);
        $stmtActual->execute();
        $actual = $stmtActual->get_result()->fetch_assoc();
        $stmtActual->close();
        if (!$actual) {
            responder_usuarios(['estado' => false, 'mensaje' => 'No se encontró la cuenta.']);
        }
        if (es_rol_raiz_usuario((string) $actual['rol'])) {
            responder_usuarios(['estado' => false, 'mensaje' => 'La cuenta administradora raíz está protegida.']);
        }

        $stmtRol = $conexion->prepare(
            "SELECT id_rol FROM rol
             WHERE id_rol = ? AND LOWER(TRIM(nombre)) NOT IN ('admin', 'administrador')"
        );
        $stmtRol->bind_param('i', $idRol);
        $stmtRol->execute();
        $rolValido = $stmtRol->get_result()->fetch_assoc();
        $stmtRol->close();
        if (!$rolValido) {
            responder_usuarios(['estado' => false, 'mensaje' => 'Selecciona un rol válido.']);
        }

        $stmt = $conexion->prepare('UPDATE usuario SET id_rol = ?, estado = ?, correo_electronico = ? WHERE id_usuario = ?');
        $stmt->bind_param('issi', $idRol, $estado, $correo, $idUsuario);
        $stmt->execute();
        $stmt->close();
        responder_usuarios(['estado' => true, 'mensaje' => 'Cuenta actualizada.']);
    }

    if ($opcion === 'restablecerContrasena') {
        $idUsuario = filter_var($_POST['id_usuario'] ?? null, FILTER_VALIDATE_INT);
        if (!$idUsuario || $idUsuario <= 0 || (int) $idUsuario === (int) $_SESSION['id_usuario']) {
            responder_usuarios(['estado' => false, 'mensaje' => 'Selecciona otra cuenta válida. Para tu cuenta usa Cambiar contraseña.']);
        }
        $stmtActual = $conexion->prepare(
            'SELECT r.nombre AS rol FROM usuario u INNER JOIN rol r ON r.id_rol = u.id_rol WHERE u.id_usuario = ?'
        );
        $stmtActual->bind_param('i', $idUsuario);
        $stmtActual->execute();
        $actual = $stmtActual->get_result()->fetch_assoc();
        $stmtActual->close();
        if (!$actual || es_rol_raiz_usuario((string) $actual['rol'])) {
            responder_usuarios(['estado' => false, 'mensaje' => 'La cuenta no existe o está protegida.']);
        }

        $contrasenaTemporal = generar_contrasena_temporal();
        $hash = crear_hash_contrasena($contrasenaTemporal);
        $stmt = $conexion->prepare('UPDATE usuario SET contrasena = ?, debe_cambiar_contrasena = 1 WHERE id_usuario = ?');
        $stmt->bind_param('si', $hash, $idUsuario);
        $stmt->execute();
        $stmt->close();
        responder_usuarios([
            'estado' => true,
            'mensaje' => 'Contraseña temporal restablecida. Entrégala al empleado.',
            'contrasena_temporal' => $contrasenaTemporal
        ]);
    }

    responder_usuarios(['estado' => false, 'mensaje' => 'Operación desconocida.']);
} catch (mysqli_sql_exception $error) {
    error_log('DAOUsuarios: ' . $error->getMessage());
    if ((int) $error->getCode() === 1062) {
        responder_usuarios(['estado' => false, 'mensaje' => 'Ese nombre de usuario o correo ya está registrado.']);
    }
    responder_usuarios(['estado' => false, 'mensaje' => 'No se pudo procesar la operación de usuarios.']);
} catch (Throwable $error) {
    error_log('DAOUsuarios: ' . $error->getMessage());
    responder_usuarios(['estado' => false, 'mensaje' => 'No se pudo procesar la operación de usuarios.']);
}
