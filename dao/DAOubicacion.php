<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$bodega = trim((string) ($_REQUEST["bodega"] ?? ''));
$pasillo = trim((string) ($_REQUEST["pasillo"] ?? ''));
$estante = trim((string) ($_REQUEST["estante"] ?? ''));
$nivel = trim((string) ($_REQUEST["nivel"] ?? ''));
$id = $_REQUEST['id_ubicacion'] ?? '';

$opcion = $_REQUEST["opcion"] ?? '';
$operacionesGestionUbicacion = ['guardar', 'modificar', 'eliminar'];
requerir_permiso(
    in_array($opcion, $operacionesGestionUbicacion, true) ? 'ubicaciones.gestionar' : 'ubicaciones.consultar',
    true
);
if (in_array($opcion, $operacionesGestionUbicacion, true)) {
    requerir_post_api();
}

function ubicacionCombinacionExiste($conexion, $bodega, $pasillo, $estante, $nivel, $idExcluir = 0)
{
    $sql = "SELECT id_ubicacion FROM ubicacion
            WHERE bodega = ? AND pasillo = ? AND estante = ? AND nivel = ?
              AND id_ubicacion <> ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $bodega, $pasillo, $estante, $nivel, $idExcluir);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $existe;
}

if ($opcion == "guardar") {
    require_once "../conexion.php";

    try {
        if (ubicacionCombinacionExiste($conexion, $bodega, $pasillo, $estante, $nivel)) {
            echo json_encode([
                "estado" => false,
                "codigo" => "combinacion_duplicada",
                "mensaje" => "Ya existe una ubicación con esa combinación de bodega, pasillo, estante y nivel."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $sql = "INSERT INTO ubicacion (bodega, pasillo, estante, nivel) VALUES(?,?,?,?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $bodega, $pasillo, $estante, $nivel);
        $stmt->execute();
        $stmt->close();
        echo json_encode([
            "estado" => true,
            "mensaje" => "Ubicación guardada correctamente."
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $duplicada = $e instanceof mysqli_sql_exception && (int) $e->getCode() === 1062;
        echo json_encode([
            "estado" => false,
            "codigo" => $duplicada ? "combinacion_duplicada" : "error_guardar",
            "mensaje" => $duplicada
                ? "Ya existe una ubicación con esa combinación de bodega, pasillo, estante y nivel."
                : "No se pudo guardar la ubicación."
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}

if ($opcion == "modificar") {
    require_once "../conexion.php";

    $idUbicacion = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idUbicacion === false) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "La ubicación indicada no es válida."
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    try {
        $conexion->begin_transaction();

        $stmtBloquear = $conexion->prepare(
            "SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion = ? FOR UPDATE"
        );
        $stmtBloquear->bind_param("i", $idUbicacion);
        $stmtBloquear->execute();
        $ubicacionExiste = $stmtBloquear->get_result()->num_rows > 0;
        $stmtBloquear->close();

        if (!$ubicacionExiste) {
            $conexion->rollback();
            http_response_code(404);
            echo json_encode([
                "estado" => false,
                "mensaje" => "La ubicación indicada ya no existe."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmtAsignados = $conexion->prepare(
            "SELECT COALESCE(p.nombre, CONCAT('producto #', i.id_producto)) AS nombre
             FROM inventario AS i
             LEFT JOIN producto AS p ON p.id_producto = i.id_producto
             WHERE i.id_ubicacion = ?
             ORDER BY p.nombre"
        );
        $stmtAsignados->bind_param("i", $idUbicacion);
        $stmtAsignados->execute();
        $resultadoAsignados = $stmtAsignados->get_result();
        $productosAsignados = [];
        while ($producto = $resultadoAsignados->fetch_assoc()) {
            $productosAsignados[] = $producto["nombre"];
        }
        $stmtAsignados->close();

        if (count($productosAsignados) > 0) {
            $conexion->rollback();
            http_response_code(409);
            echo json_encode([
                "estado" => false,
                "codigo" => "ubicacion_asignada",
                "mensaje" => "No se puede modificar esta ubicación porque está asignada a: " . implode(", ", $productosAsignados) . "."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        if (ubicacionCombinacionExiste($conexion, $bodega, $pasillo, $estante, $nivel, $idUbicacion)) {
            $conexion->rollback();
            echo json_encode([
                "estado" => false,
                "codigo" => "combinacion_duplicada",
                "mensaje" => "Ya existe una ubicación con esa combinación de bodega, pasillo, estante y nivel."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $sql = "UPDATE ubicacion SET bodega=?, pasillo=?, estante=?, nivel=? WHERE id_ubicacion=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssssi", $bodega, $pasillo, $estante, $nivel, $idUbicacion);
        $stmt->execute();
        $stmt->close();
        $conexion->commit();
        echo json_encode([
            "estado" => true,
            "mensaje" => "Ubicación modificada correctamente."
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        $duplicada = $e instanceof mysqli_sql_exception && (int) $e->getCode() === 1062;
        echo json_encode([
            "estado" => false,
            "codigo" => $duplicada ? "combinacion_duplicada" : "error_modificar",
            "mensaje" => $duplicada
                ? "Ya existe una ubicación con esa combinación de bodega, pasillo, estante y nivel."
                : "No se pudo modificar la ubicación."
        ], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
}
if ($opcion == "eliminar") {
    require_once "../conexion.php";

    $idUbicacion = filter_var($id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
    if ($idUbicacion === false) {
        echo json_encode([
            "estado" => false,
            "mensaje" => "La ubicación indicada no es válida."
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    try {
        $conexion->begin_transaction();

        // Bloquea la ubicación hasta terminar la comprobación y el borrado.
        $stmtUbicacion = $conexion->prepare("SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion = ? FOR UPDATE");
        $stmtUbicacion->bind_param("i", $idUbicacion);
        $stmtUbicacion->execute();
        $ubicacionExiste = $stmtUbicacion->get_result()->num_rows > 0;
        $stmtUbicacion->close();

        if (!$ubicacionExiste) {
            $conexion->rollback();
            echo json_encode([
                "estado" => false,
                "mensaje" => "La ubicación ya no existe."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmtInventario = $conexion->prepare(
            "SELECT id_inventario, id_producto FROM inventario WHERE id_ubicacion = ? LIMIT 1"
        );
        $stmtInventario->bind_param("i", $idUbicacion);
        $stmtInventario->execute();
        $asociacion = $stmtInventario->get_result()->fetch_assoc();
        $stmtInventario->close();

        if ($asociacion) {
            $conexion->rollback();
            echo json_encode([
                "estado" => false,
                "mensaje" => "No se puede eliminar: la ubicación está asociada al inventario #" .
                    $asociacion["id_inventario"] . " y al producto #" . $asociacion["id_producto"] . "."
            ], JSON_UNESCAPED_UNICODE);
            $conexion->close();
            exit;
        }

        $stmtEliminar = $conexion->prepare("DELETE FROM ubicacion WHERE id_ubicacion = ?");
        $stmtEliminar->bind_param("i", $idUbicacion);
        $stmtEliminar->execute();
        $stmtEliminar->close();
        $conexion->commit();

        echo json_encode([
            "estado" => true,
            "mensaje" => "La ubicación se eliminó correctamente."
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        $conexion->rollback();
        echo json_encode([
            "estado" => false,
            "mensaje" => "No se pudo eliminar la ubicación. Verifica que no esté asociada a un producto del inventario."
        ], JSON_UNESCAPED_UNICODE);
    }

    $conexion->close();
}

// if ($opcion == "consultarDUI") {
//     require_once "../config/conexion.php";

//     $sql = "SELECT dui FROM ubicacion WHERE dui=?";
//     $stmt = $conexion->prepare($sql);
//     $stmt->bind_param("s", $dui);

//     $stmt->execute();

//     $resultado = $stmt->get_result();

//     if ($resultado->num_rows > 0) {

//         echo json_encode([
//             "nivel" => true,
//             "mensaje" => "Este Dui ya existe"
//         ]);
//     } else {
//         echo json_encode([
//             "nivel" => false,
//             "mensaje" => "a"
//         ]);
//     }

//     $stmt->close();
//     $conexion->close();
// }

if ($opcion == "consultar") {
    require_once "../conexion.php";

    try {
        $sql = "SELECT u.id_ubicacion, u.bodega, u.pasillo, u.estante, u.nivel,
                       COUNT(DISTINCT i.id_producto) AS productos_asignados,
                       GROUP_CONCAT(DISTINCT COALESCE(p.nombre, CONCAT('Producto #', i.id_producto)) ORDER BY COALESCE(p.nombre, CONCAT('Producto #', i.id_producto)) SEPARATOR ', ') AS nombres_productos
                FROM ubicacion AS u
                LEFT JOIN inventario AS i ON i.id_ubicacion = u.id_ubicacion
                LEFT JOIN producto AS p ON p.id_producto = i.id_producto
                GROUP BY u.id_ubicacion, u.bodega, u.pasillo, u.estante, u.nivel
                ORDER BY u.bodega, u.pasillo, u.estante, u.nivel";

        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar");
        }

        $ubicacion = [];

        while ($fila = $resultado->fetch_assoc()) {
            //$ubicacion[] = $fila;
            $ubicacion[] = [
                'id_ubicacion' => $fila['id_ubicacion'],
                'bodega' => $fila['bodega'],
                'pasillo' => $fila['pasillo'],
                'estante' => $fila['estante'],
                'nivel' => $fila['nivel'],
                'productos_asignados' => (int) $fila['productos_asignados'],
                'nombres_productos' => $fila['nombres_productos'] ?? ''
            ];
        }

        echo json_encode(
            $ubicacion,
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


if ($opcion == "consultarIndividual") {
    require_once "../conexion.php";

    $sql = "SELECT u.id_ubicacion, u.bodega, u.pasillo, u.estante, u.nivel,
                   COUNT(DISTINCT i.id_producto) AS productos_asignados,
                   GROUP_CONCAT(DISTINCT COALESCE(p.nombre, CONCAT('Producto #', i.id_producto)) ORDER BY COALESCE(p.nombre, CONCAT('Producto #', i.id_producto)) SEPARATOR ', ') AS nombres_productos
            FROM ubicacion AS u
            LEFT JOIN inventario AS i ON i.id_ubicacion = u.id_ubicacion
            LEFT JOIN producto AS p ON p.id_producto = i.id_producto
            WHERE u.id_ubicacion = ?
            GROUP BY u.id_ubicacion, u.bodega, u.pasillo, u.estante, u.nivel";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    $stmt->execute();

    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        echo json_encode([
            "nivel" => true,
            "datos" => [
                "id_ubicacion" => $datos["id_ubicacion"],
                "bodega" => $datos["bodega"],
                "pasillo" => $datos["pasillo"],
                "estante" => $datos["estante"],
                "nivel" => $datos["nivel"],
                "productos_asignados" => (int) $datos["productos_asignados"],
                "nombres_productos" => $datos["nombres_productos"] ?? ""
            ]
        ]);
    } else {
        echo json_encode([
            "nivel" => false,
            "datos" => "No Existe"
        ]);
    }

    $stmt->close();
    $conexion->close();
}
