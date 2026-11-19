<?php
header('Content-Type: application/json; charset=utf-8');

$nombre = trim((string) ($_REQUEST["nombre"] ?? ''));
$descripcion = (string) ($_REQUEST["descripcion"] ?? '');
$idPadreRaw = trim((string) ($_REQUEST["id_padre"] ?? ''));
$idPadre = $idPadreRaw === '' ? null : filter_var($idPadreRaw, FILTER_VALIDATE_INT);
$estado = $_REQUEST["estado"] ?? '';
$id = $_REQUEST['id_categoria'] ?? '';

$opcion = $_REQUEST["opcion"] ?? '';

if ($opcion == "guardar") {
    require_once "../conexion.php";

    $errorValidacion = validarCategoria($conexion, $nombre, $descripcion, $idPadre);
    if ($errorValidacion !== null) {
        echo json_encode(["estado" => false, "mensaje" => $errorValidacion], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $idPadreParametro = $idPadre ?? 0;
    $sql = "INSERT INTO categoria (nombre, descripcion, id_padre, estado) VALUES(?, ?, NULLIF(?, 0), 'activo')";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssi", $nombre, $descripcion, $idPadreParametro);
    echo json_encode($stmt->execute()
        ? ["estado" => true, "mensaje" => "Datos Guardados Correctamente"]
        : ["estado" => false, "mensaje" => "Error al guardar los datos"], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conexion->close();
}

if ($opcion == "modificar") {
    require_once "../conexion.php";

    $id = (int) $id;
    $stmt = $conexion->prepare("SELECT estado FROM categoria WHERE id_categoria=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $categoriaActual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$categoriaActual || $categoriaActual['estado'] !== 'activo') {
        echo json_encode(["estado" => false, "mensaje" => "No se pueden editar categorías inactivas."], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $errorValidacion = validarCategoria($conexion, $nombre, $descripcion, $idPadre, $id);
    if ($errorValidacion !== null) {
        echo json_encode(["estado" => false, "mensaje" => $errorValidacion], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $idPadreParametro = $idPadre ?? 0;
    $sql = "UPDATE categoria SET nombre=?, descripcion=?, id_padre=NULLIF(?, 0) WHERE id_categoria=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssii", $nombre, $descripcion, $idPadreParametro, $id);
    echo json_encode($stmt->execute()
        ? ["estado" => true, "mensaje" => "Datos Modificados Correctamente"]
        : ["estado" => false, "mensaje" => "Error al modificar los datos"], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conexion->close();
}
if ($opcion == "eliminar") {
    require_once "../conexion.php";

    if (!ctype_digit((string) $id) || (int) $id <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "Categoría no válida"], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    try {
        $conexion->begin_transaction();
        $id = (int) $id;

        $stmt = $conexion->prepare("SELECT id_categoria FROM categoria WHERE id_categoria=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$existe) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "La categoría ya no existe."], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmt = $conexion->prepare("SELECT id_categoria FROM categoria WHERE id_padre=? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $tieneHijas = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($tieneHijas) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "Elimina primero las subcategorías de esta categoría."], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmt = $conexion->prepare("SELECT id_producto FROM producto WHERE id_categoria=? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $tieneProductos = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($tieneProductos) {
            $conexion->rollback();
            echo json_encode(["estado" => false, "mensaje" => "No se puede eliminar porque tiene productos asociados."], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmt = $conexion->prepare("DELETE FROM categoria WHERE id_categoria=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $conexion->commit();

        echo json_encode(["estado" => true, "mensaje" => "Categoría eliminada correctamente."], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        try {
            $conexion->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Error al revertir eliminación de categoría: " . $rollbackError->getMessage());
        }
        error_log("Error al eliminar categoría: " . $e->getMessage());
        echo json_encode(["estado" => false, "mensaje" => "No se pudo eliminar la categoría."], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

// if ($opcion == "consultarDUI") {
//     require_once "../config/conexion.php";

//     $sql = "SELECT dui FROM categoria WHERE dui=?";
//     $stmt = $conexion->prepare($sql);
//     $stmt->bind_param("s", $dui);

//     $stmt->execute();

//     $resultado = $stmt->get_result();

//     if ($resultado->num_rows > 0) {

//         echo json_encode([
//             "estado" => true,
//             "mensaje" => "Este Dui ya existe"
//         ]);
//     } else {
//         echo json_encode([
//             "estado" => false,
//             "mensaje" => "a"
//         ]);
//     }

//     $stmt->close();
//     $conexion->close();
// }

if ($opcion == "consultar") {
    require_once "../conexion.php";

    try {
        $sql = "SELECT c.id_categoria, c.id_padre, c.nombre, c.descripcion, c.estado,
                       padre.nombre AS nombre_padre,
                       COALESCE(productos.total_productos, 0) AS total_productos
                FROM categoria AS c
                LEFT JOIN categoria AS padre ON padre.id_categoria = c.id_padre
                LEFT JOIN (
                    SELECT id_categoria, COUNT(*) AS total_productos
                    FROM producto
                    GROUP BY id_categoria
                ) AS productos ON productos.id_categoria = c.id_categoria
                ORDER BY COALESCE(padre.nombre, c.nombre), c.id_padre, c.nombre";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar");
        }

        $categoria = [];

        while ($fila = $resultado->fetch_assoc()) {
            //$categoria[] = $fila;
            $categoria[] = [
                'id_categoria' => $fila['id_categoria'],
                'id_padre' => $fila['id_padre'],
                'nombre' => $fila['nombre'],
                'descripcion' => $fila['descripcion'],
                'nombre_padre' => $fila['nombre_padre'],
                'total_productos' => (int) $fila['total_productos'],
                'estado' => $fila['estado']
            ];
        }

        echo json_encode(
            $categoria,
            JSON_UNESCAPED_UNICODE
        );

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {

        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
}


if ($opcion == "consultarPadres") {
    require_once "../conexion.php";

    $sql = "SELECT id_categoria, nombre FROM categoria WHERE id_padre IS NULL ORDER BY nombre";
    $resultado = $conexion->query($sql);
    $padres = [];

    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $padres[] = [
                'id_categoria' => $fila['id_categoria'],
                'nombre' => $fila['nombre']
            ];
        }
        $resultado->free();
    }

    echo json_encode($padres, JSON_UNESCAPED_UNICODE);
    $conexion->close();
}

if ($opcion == "consultarIndividual") {
    require_once "../conexion.php";

    $sql = "SELECT id_categoria, id_padre, nombre, descripcion, estado FROM categoria WHERE id_categoria=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        if ($datos['estado'] !== 'activo') {
            echo json_encode([
                "estado" => false,
                "mensaje" => "No se pueden editar categorías inactivas."
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "estado" => true,
                "datos" => [
                    "id_categoria" => $datos["id_categoria"],
                    "id_padre" => $datos["id_padre"],
                    "nombre" => $datos["nombre"],
                    "descripcion" => $datos["descripcion"]
                ]
            ], JSON_UNESCAPED_UNICODE);
        }
    } else {
        echo json_encode([
            "estado" => false,
            "datos" => "No Existe"
        ]);
    }

    $stmt->close();
    $conexion->close();
}

if ($opcion == "cambiarEstado") {
    if (!ctype_digit((string) $id) || (int) $id <= 0 || !in_array($estado, ['activo', 'inactivo'], true)) {
        echo json_encode(["estado" => false, "mensaje" => "Datos no válidos"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once "../conexion.php";

    try {
        $conexion->begin_transaction();

        $id = (int) $id;
        $stmt = $conexion->prepare("SELECT id_padre FROM categoria WHERE id_categoria=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $categoriaActual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$categoriaActual) {
            throw new RuntimeException("La categoría no existe");
        }

        $esPrincipal = $categoriaActual['id_padre'] === null;
        $tieneSubcategorias = false;

        if ($estado === 'activo' && !$esPrincipal) {
            $stmt = $conexion->prepare("SELECT estado FROM categoria WHERE id_categoria=?");
            $stmt->bind_param("i", $categoriaActual['id_padre']);
            $stmt->execute();
            $padre = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$padre || $padre['estado'] !== 'activo') {
                $conexion->rollback();
                echo json_encode([
                    "estado" => false,
                    "mensaje" => "No se puede activar una subcategoría si su categoría padre está inactiva."
                ], JSON_UNESCAPED_UNICODE);
                $conexion->close();
                exit;
            }
        }

        $stmt = $conexion->prepare("UPDATE categoria SET estado=? WHERE id_categoria=?");
        $stmt->bind_param("si", $estado, $id);
        $stmt->execute();
        $stmt->close();

        if ($estado === 'inactivo' && $esPrincipal) {
            $stmt = $conexion->prepare("SELECT id_categoria FROM categoria WHERE id_padre=? LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $tieneSubcategorias = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($tieneSubcategorias) {
                $stmt = $conexion->prepare("UPDATE categoria SET estado='inactivo' WHERE id_padre=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
        }

        $conexion->commit();
        if ($estado === 'inactivo') {
            $mensaje = $tieneSubcategorias
                ? "Categoría y subcategorías dadas de baja correctamente"
                : "Categoría dada de baja correctamente";
        } else {
            $mensaje = "Categoría reactivada correctamente";
        }
        echo json_encode(["estado" => true, "mensaje" => $mensaje], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        try {
            $conexion->rollback();
        } catch (Throwable $rollbackError) {
            error_log("Error al revertir cambio de estado de categoría: " . $rollbackError->getMessage());
        }
        error_log("Error al cambiar estado de categoría: " . $e->getMessage());
        echo json_encode(["estado" => false, "mensaje" => "Error al cambiar el estado"], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

function validarCategoria($conexion, $nombre, $descripcion, $idPadre = null, $idExcluir = 0)
{
    if ($nombre === '') {
        return "El nombre de la categoría es obligatorio.";
    }
    if (mb_strlen($nombre, 'UTF-8') > 80) {
        return "El nombre de la categoría no puede superar 80 caracteres.";
    }
    if (preg_match('/^[\p{L} ]+$/u', $nombre) !== 1) {
        return "El nombre solo puede contener letras y espacios.";
    }
    if (mb_strlen($descripcion, 'UTF-8') > 255) {
        return "La descripción no puede superar 255 caracteres.";
    }

    if ($idPadre === false || ($idPadre !== null && (!is_int($idPadre) || $idPadre <= 0))) {
        return "La categoría padre seleccionada no es válida.";
    }

    if ($idPadre !== null) {
        if ($idPadre === $idExcluir) {
            return "Una categoría no puede ser su propia categoría padre.";
        }

        $stmt = $conexion->prepare("SELECT id_categoria FROM categoria WHERE id_categoria=? AND id_padre IS NULL LIMIT 1");
        $stmt->bind_param("i", $idPadre);
        $stmt->execute();
        $padreValido = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$padreValido) {
            return "Selecciona una categoría principal válida.";
        }

        if ($idExcluir > 0) {
            $stmt = $conexion->prepare("SELECT id_categoria FROM categoria WHERE id_padre=? LIMIT 1");
            $stmt->bind_param("i", $idExcluir);
            $stmt->execute();
            $tieneHijas = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if ($tieneHijas) {
                return "No se puede convertir en subcategoría una categoría que ya tiene subcategorías.";
            }
        }
    }

    $sql = "SELECT id_categoria FROM categoria WHERE nombre = ? AND id_categoria <> ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $nombre, $idExcluir);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $existe ? "Ya existe una categoría con ese nombre." : null;
}
