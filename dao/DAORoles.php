<?php
declare(strict_types=1);

require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('roles.gestionar', true);
requerir_post_api();
require_once __DIR__ . '/../conexion.php';
header('Content-Type: application/json; charset=utf-8');

function responder_roles(array $datos): void
{
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function es_rol_raiz(string $nombre): bool
{
    return in_array(strtolower(trim($nombre)), ['admin', 'administrador'], true);
}

function longitud_texto_rol(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

$opcion = trim((string) ($_POST['opcion'] ?? ''));

try {
    if ($opcion === 'consultar') {
        $rolesResultado = $conexion->query(
            "SELECT r.id_rol, r.nombre, r.descripcion,
                    COUNT(DISTINCT u.id_usuario) AS total_usuarios
             FROM rol r
             LEFT JOIN usuario u ON u.id_rol = r.id_rol
             GROUP BY r.id_rol, r.nombre, r.descripcion
             ORDER BY LOWER(r.nombre)"
        );
        $roles = [];
        while ($fila = $rolesResultado->fetch_assoc()) {
            $fila['id_rol'] = (int) $fila['id_rol'];
            $fila['total_usuarios'] = (int) $fila['total_usuarios'];
            $fila['protegido'] = es_rol_raiz((string) $fila['nombre']);
            $fila['permisos'] = [];
            if (!$fila['protegido']) {
                $stmtPermisos = $conexion->prepare(
                    'SELECT p.id_permiso, p.codigo FROM rol_permiso rp
                     INNER JOIN permiso p ON p.id_permiso = rp.id_permiso WHERE rp.id_rol = ?'
                );
                $stmtPermisos->bind_param('i', $fila['id_rol']);
                $stmtPermisos->execute();
                $resultadoPermisos = $stmtPermisos->get_result();
                while ($permiso = $resultadoPermisos->fetch_assoc()) {
                    $fila['permisos'][] = (int) $permiso['id_permiso'];
                }
                $stmtPermisos->close();
            }
            $roles[] = $fila;
        }

        $permisosResultado = $conexion->query(
            "SELECT id_permiso, codigo, descripcion FROM permiso
             WHERE codigo NOT IN ('usuarios.gestionar', 'roles.gestionar')
             ORDER BY codigo"
        );
        $permisos = [];
        while ($fila = $permisosResultado->fetch_assoc()) {
            $permisos[] = ['id_permiso' => (int) $fila['id_permiso'], 'codigo' => $fila['codigo'], 'descripcion' => $fila['descripcion']];
        }
        responder_roles(['estado' => true, 'roles' => $roles, 'permisos' => $permisos]);
    }

    if ($opcion === 'guardar') {
        $idRol = filter_var($_POST['id_rol'] ?? 0, FILTER_VALIDATE_INT);
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $permisosSolicitados = $_POST['permisos'] ?? [];
        if (!is_array($permisosSolicitados)) {
            responder_roles(['estado' => false, 'mensaje' => 'La selección de permisos no es válida.']);
        }
        if ($nombre === '' || longitud_texto_rol($nombre) > 50 || longitud_texto_rol($descripcion) > 150
            || es_rol_raiz($nombre)) {
            responder_roles(['estado' => false, 'mensaje' => 'Indica un nombre y descripción válidos. Los nombres Admin y Administrador están reservados.']);
        }

        $idsPermiso = [];
        foreach ($permisosSolicitados as $valor) {
            $idPermiso = filter_var($valor, FILTER_VALIDATE_INT);
            if (!$idPermiso || $idPermiso <= 0) {
                responder_roles(['estado' => false, 'mensaje' => 'Hay un permiso seleccionado que no es válido.']);
            }
            $idsPermiso[] = (int) $idPermiso;
        }
        $idsPermiso = array_values(array_unique($idsPermiso));

        $conexion->begin_transaction();
        try {
            if ($idRol > 0) {
                $stmtRolActual = $conexion->prepare('SELECT nombre FROM rol WHERE id_rol = ? FOR UPDATE');
                $stmtRolActual->bind_param('i', $idRol);
                $stmtRolActual->execute();
                $rolActual = $stmtRolActual->get_result()->fetch_assoc();
                $stmtRolActual->close();
                if (!$rolActual || es_rol_raiz((string) $rolActual['nombre'])) {
                    $conexion->rollback();
                    responder_roles(['estado' => false, 'mensaje' => 'Ese rol no existe o está protegido.']);
                }
                $stmt = $conexion->prepare('UPDATE rol SET nombre = ?, descripcion = ? WHERE id_rol = ?');
                $stmt->bind_param('ssi', $nombre, $descripcion, $idRol);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conexion->prepare('INSERT INTO rol (nombre, descripcion) VALUES (?, ?)');
                $stmt->bind_param('ss', $nombre, $descripcion);
                $stmt->execute();
                $idRol = (int) $stmt->insert_id;
                $stmt->close();
            }

            $stmtCatalogo = $conexion->prepare(
                "SELECT id_permiso FROM permiso
                 WHERE id_permiso = ? AND codigo NOT IN ('usuarios.gestionar', 'roles.gestionar')"
            );
            $stmtInsertar = $conexion->prepare('INSERT INTO rol_permiso (id_rol, id_permiso) VALUES (?, ?)');
            $stmtBorrar = $conexion->prepare('DELETE FROM rol_permiso WHERE id_rol = ?');
            $stmtBorrar->bind_param('i', $idRol);
            $stmtBorrar->execute();
            foreach ($idsPermiso as $idPermiso) {
                $stmtCatalogo->bind_param('i', $idPermiso);
                $stmtCatalogo->execute();
                if (!$stmtCatalogo->get_result()->fetch_assoc()) {
                    throw new RuntimeException('Se intentó asignar un permiso reservado o inexistente.');
                }
                $stmtInsertar->bind_param('ii', $idRol, $idPermiso);
                $stmtInsertar->execute();
            }
            $stmtCatalogo->close();
            $stmtInsertar->close();
            $stmtBorrar->close();
            $conexion->commit();
            responder_roles(['estado' => true, 'mensaje' => 'Rol guardado correctamente.']);
        } catch (Throwable $error) {
            $conexion->rollback();
            throw $error;
        }
    }

    if ($opcion === 'eliminar') {
        $idRol = filter_var($_POST['id_rol'] ?? null, FILTER_VALIDATE_INT);
        if (!$idRol || $idRol <= 0) {
            responder_roles(['estado' => false, 'mensaje' => 'Selecciona un rol válido.']);
        }
        $stmtRol = $conexion->prepare('SELECT nombre FROM rol WHERE id_rol = ?');
        $stmtRol->bind_param('i', $idRol);
        $stmtRol->execute();
        $rol = $stmtRol->get_result()->fetch_assoc();
        $stmtRol->close();
        if (!$rol || es_rol_raiz((string) $rol['nombre'])) {
            responder_roles(['estado' => false, 'mensaje' => 'Ese rol no existe o está protegido.']);
        }

        $stmtUsuarios = $conexion->prepare('SELECT COUNT(*) AS total FROM usuario WHERE id_rol = ?');
        $stmtUsuarios->bind_param('i', $idRol);
        $stmtUsuarios->execute();
        $total = (int) $stmtUsuarios->get_result()->fetch_assoc()['total'];
        $stmtUsuarios->close();
        if ($total > 0) {
            responder_roles(['estado' => false, 'mensaje' => 'No se puede eliminar: todavía hay usuarios asignados a este rol.']);
        }

        $stmt = $conexion->prepare('DELETE FROM rol WHERE id_rol = ?');
        $stmt->bind_param('i', $idRol);
        $stmt->execute();
        $stmt->close();
        responder_roles(['estado' => true, 'mensaje' => 'Rol eliminado.']);
    }

    responder_roles(['estado' => false, 'mensaje' => 'Operación desconocida.']);
} catch (mysqli_sql_exception $error) {
    error_log('DAORoles: ' . $error->getMessage());
    if ((int) $error->getCode() === 1062) {
        responder_roles(['estado' => false, 'mensaje' => 'Ya existe un rol con ese nombre.']);
    }
    responder_roles(['estado' => false, 'mensaje' => 'No se pudo procesar la operación de roles.']);
} catch (Throwable $error) {
    error_log('DAORoles: ' . $error->getMessage());
    responder_roles(['estado' => false, 'mensaje' => 'No se pudo procesar la operación de roles.']);
}
