<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');
$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
requerir_permiso('categorias.consultar', true);

if ($opcion == "consultar") {
    require_once __DIR__ . '/../conexion.php';
    $sql = "SELECT c.id_categoria, c.nombre, padre.nombre AS nombre_padre
            FROM categoria AS c
            INNER JOIN categoria AS padre ON padre.id_categoria = c.id_padre
            WHERE c.estado = 'activo' AND padre.estado = 'activo'
            ORDER BY padre.nombre, c.nombre";
    $resultado = $conexion->query($sql);

    $categorias = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $categorias[] = [
                'id_categoria' => $fila['id_categoria'],
                'nombre'       => $fila['nombre'],
                'nombre_padre' => $fila['nombre_padre']
            ];
        }
        $resultado->free();
    }
    echo json_encode($categorias, JSON_UNESCAPED_UNICODE);
    $conexion->close();
}
