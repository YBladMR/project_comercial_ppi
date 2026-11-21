<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
$operacionesGestion = ['asignarUbicacion'];
requerir_permiso(
    in_array($opcion, $operacionesGestion, true) ? 'inventario.gestionar' : 'inventario.consultar',
    true
);
if (in_array($opcion, $operacionesGestion, true)) {
    requerir_post_api();
}

if (!in_array($opcion, ['consultar', 'consultarUbicaciones', 'asignarUbicacion'], true)) {
    http_response_code(400);
    echo json_encode([
        'estado' => false,
        'mensaje' => 'La consulta solicitada no es válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../conexion.php';

$transaccionIniciada = false;
try {
    if ($opcion === 'consultar') {
        $sql = "SELECT i.id_inventario, i.id_producto, i.id_ubicacion,
                   p.nombre AS producto, c.nombre AS categoria,
                   i.existencia,
                   CONCAT_WS(' / ', NULLIF(u.bodega, ''), NULLIF(u.pasillo, ''),
                             NULLIF(u.estante, ''), NULLIF(u.nivel, '')) AS ubicacion
            FROM inventario AS i
            INNER JOIN producto AS p ON p.id_producto = i.id_producto
            LEFT JOIN categoria AS c ON c.id_categoria = p.id_categoria
            LEFT JOIN ubicacion AS u ON u.id_ubicacion = i.id_ubicacion
            ORDER BY p.nombre";

        $resultado = $conexion->query($sql);
        if (!$resultado) {
            throw new RuntimeException('No se pudo consultar la tabla de inventario.');
        }

        $existencias = [];
        while ($fila = $resultado->fetch_assoc()) {
            $existencias[] = [
                'id_inventario' => $fila['id_inventario'],
                'id_producto' => $fila['id_producto'],
                'id_ubicacion' => $fila['id_ubicacion'],
                'producto' => $fila['producto'],
                'categoria' => $fila['categoria'] ?: 'Sin categoría',
                'ubicacion' => $fila['ubicacion'] ?: 'Sin ubicación',
                'existencia' => $fila['existencia']
            ];
        }

        echo json_encode($existencias, JSON_UNESCAPED_UNICODE);
        $resultado->free();
    } elseif ($opcion === 'consultarUbicaciones') {
        $resultado = $conexion->query(
            "SELECT id_ubicacion, bodega, pasillo, estante, nivel
             FROM ubicacion ORDER BY bodega, pasillo, estante, nivel"
        );
        if (!$resultado) {
            throw new RuntimeException('No se pudieron consultar las ubicaciones.');
        }

        $ubicaciones = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ubicaciones[] = [
                'id_ubicacion' => $fila['id_ubicacion'],
                'nombre' => implode(' / ', [
                    $fila['bodega'], $fila['pasillo'], $fila['estante'], $fila['nivel']
                ])
            ];
        }

        echo json_encode($ubicaciones, JSON_UNESCAPED_UNICODE);
        $resultado->free();
    } else {
        $idProducto = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        $idUbicacion = filter_var($_POST['id_ubicacion'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($idProducto === false || $idUbicacion === false) {
            http_response_code(422);
            echo json_encode([
                'estado' => false,
                'mensaje' => 'Seleccione un producto y una ubicación válidos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $conexion->begin_transaction();
        $transaccionIniciada = true;

        $stmtInventario = $conexion->prepare(
            'SELECT id_inventario FROM inventario WHERE id_producto = ? FOR UPDATE'
        );
        $stmtInventario->bind_param('i', $idProducto);
        $stmtInventario->execute();
        $existeInventario = $stmtInventario->get_result()->num_rows > 0;
        $stmtInventario->close();

        if (!$existeInventario) {
            $conexion->rollback();
            $transaccionIniciada = false;
            http_response_code(404);
            echo json_encode([
                'estado' => false,
                'mensaje' => 'El producto todavía no tiene un registro en el inventario.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtUbicacion = $conexion->prepare(
            'SELECT id_ubicacion FROM ubicacion WHERE id_ubicacion = ? FOR UPDATE'
        );
        $stmtUbicacion->bind_param('i', $idUbicacion);
        $stmtUbicacion->execute();
        $existeUbicacion = $stmtUbicacion->get_result()->num_rows > 0;
        $stmtUbicacion->close();

        if (!$existeUbicacion) {
            $conexion->rollback();
            $transaccionIniciada = false;
            http_response_code(404);
            echo json_encode([
                'estado' => false,
                'mensaje' => 'La ubicación seleccionada ya no existe.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $stmtActualizar = $conexion->prepare(
            'UPDATE inventario SET id_ubicacion = ? WHERE id_producto = ?'
        );
        $stmtActualizar->bind_param('ii', $idUbicacion, $idProducto);
        $stmtActualizar->execute();
        $stmtActualizar->close();
        $conexion->commit();
        $transaccionIniciada = false;

        echo json_encode([
            'estado' => true,
            'mensaje' => 'La ubicación del producto se actualizó correctamente.'
        ], JSON_UNESCAPED_UNICODE);
    }
} catch (Throwable $error) {
    if ($transaccionIniciada) {
        // El rollback solo tiene efecto si había una transacción abierta.
        try { $conexion->rollback(); } catch (Throwable $ignorado) {}
    }
    http_response_code(500);
    echo json_encode([
        'estado' => false,
        'mensaje' => $opcion === 'asignarUbicacion'
            ? 'No se pudo asignar la ubicación al producto.'
            : 'No se pudieron cargar los datos del inventario.'
    ], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion->close();
}
