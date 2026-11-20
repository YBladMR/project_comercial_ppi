<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

requerir_permiso('inventario.consultar', true);

$opcion = $_GET['opcion'] ?? '';
if ($opcion !== 'consultar') {
    http_response_code(400);
    echo json_encode([
        'estado' => false,
        'mensaje' => 'La consulta solicitada no es válida.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../conexion.php';

try {
    $sql = "SELECT i.id_inventario, i.id_producto, p.nombre AS producto,
                   i.existencia,
                   CONCAT_WS(' / ', NULLIF(u.bodega, ''), NULLIF(u.pasillo, ''),
                             NULLIF(u.estante, ''), NULLIF(u.nivel, '')) AS ubicacion
            FROM inventario AS i
            INNER JOIN producto AS p ON p.id_producto = i.id_producto
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
            'producto' => $fila['producto'],
            'ubicacion' => $fila['ubicacion'] ?: 'Sin ubicación',
            'existencia' => $fila['existencia']
        ];
    }

    echo json_encode($existencias, JSON_UNESCAPED_UNICODE);
    $resultado->free();
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode([
        'estado' => false,
        'mensaje' => 'No se pudieron cargar las existencias.'
    ], JSON_UNESCAPED_UNICODE);
} finally {
    $conexion->close();
}
