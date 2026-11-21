<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
$guardarAjuste = $opcion === 'guardar';
requerir_permiso($guardarAjuste ? 'inventario.gestionar' : 'inventario.consultar', true);

if ($guardarAjuste) {
    requerir_post_api();
}

if (!in_array($opcion, ['consultar', 'guardar'], true)) {
    http_response_code(400);
    echo json_encode(['estado' => false, 'mensaje' => 'La operación de ajuste no es válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../conexion.php';

function responderAjuste($estado, $mensaje, $datos = null)
{
    $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
    if ($datos !== null) {
        $respuesta['datos'] = $datos;
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerTipoMovimientoAjuste($conexion, $nombre, $naturaleza)
{
    $stmt = $conexion->prepare(
        'INSERT INTO tipo_movimiento (nombre, naturaleza) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE id_tipo_movimiento = LAST_INSERT_ID(id_tipo_movimiento)'
    );
    $stmt->bind_param('ss', $nombre, $naturaleza);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare(
        'SELECT id_tipo_movimiento, naturaleza FROM tipo_movimiento WHERE nombre = ?'
    );
    $stmt->bind_param('s', $nombre);
    $stmt->execute();
    $tipo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$tipo || $tipo['naturaleza'] !== $naturaleza) {
        throw new DomainException('El tipo de movimiento de ajuste no está configurado correctamente.');
    }

    return (int) $tipo['id_tipo_movimiento'];
}

if ($opcion === 'consultar') {
    try {
        $productos = [];
        $resultadoProductos = $conexion->query(
            "SELECT p.id_producto, p.nombre, p.estado,
                    COALESCE(i.existencia, 0) AS existencia,
                    COALESCE(i.costo_promedio, 0) AS costo_promedio
             FROM producto AS p
             INNER JOIN inventario AS i ON i.id_producto = p.id_producto
             ORDER BY p.nombre"
        );
        while ($fila = $resultadoProductos->fetch_assoc()) {
            $productos[] = $fila;
        }
        $resultadoProductos->free();

        $ajustes = [];
        $resultadoAjustes = $conexion->query(
            "SELECT a.id_ajuste, a.id_usuario, a.fecha_hora, a.tipo, a.cantidad, a.motivo,
                    p.nombre AS producto, u.nombre AS usuario
             FROM ajuste_inventario AS a
             INNER JOIN producto AS p ON p.id_producto = a.id_producto
             INNER JOIN usuario AS u ON u.id_usuario = a.id_usuario
             ORDER BY a.fecha_hora DESC, a.id_ajuste DESC"
        );
        while ($fila = $resultadoAjustes->fetch_assoc()) {
            $ajustes[] = $fila;
        }
        $resultadoAjustes->free();

        responderAjuste(true, 'Datos de ajustes consultados.', [
            'productos' => $productos,
            'ajustes' => $ajustes
        ]);
    } catch (Throwable $error) {
        error_log('DAOAjusteInventario consultar: ' . $error->getMessage());
        responderAjuste(false, 'No se pudieron cargar los datos de los ajustes.');
    }
}

$idProducto = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);
$tipo = $_POST['tipo'] ?? '';
$cantidadTexto = (string) ($_POST['cantidad'] ?? '');
$motivo = trim((string) ($_POST['motivo'] ?? ''));
$idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
$largoMotivo = function_exists('mb_strlen') ? mb_strlen($motivo, 'UTF-8') : strlen($motivo);

if ($idProducto === false || !in_array($tipo, ['entrada', 'salida'], true)) {
    http_response_code(422);
    responderAjuste(false, 'Seleccione un producto y un tipo de ajuste válidos.');
}
if (!ctype_digit($cantidadTexto) || (int) $cantidadTexto < 1 || (float) $cantidadTexto > 4294967295) {
    http_response_code(422);
    responderAjuste(false, 'La cantidad debe ser un número entero mayor que cero.');
}
if ($motivo === '' || $largoMotivo > 255) {
    http_response_code(422);
    responderAjuste(false, 'Ingrese un motivo de hasta 255 caracteres.');
}
if ($idUsuario < 1) {
    http_response_code(401);
    responderAjuste(false, 'La sesión del usuario no es válida.');
}

$cantidad = (int) $cantidadTexto;
$transaccionIniciada = false;

try {
    $conexion->begin_transaction();
    $transaccionIniciada = true;

    $stmtProducto = $conexion->prepare(
        'SELECT p.id_producto
         FROM producto AS p
         INNER JOIN inventario AS i ON i.id_producto = p.id_producto
         WHERE p.id_producto = ? FOR UPDATE'
    );
    $stmtProducto->bind_param('i', $idProducto);
    $stmtProducto->execute();
    $productoExiste = $stmtProducto->get_result()->num_rows > 0;
    $stmtProducto->close();
    if (!$productoExiste) {
        throw new DomainException('Solo se pueden ajustar productos que ya estén registrados en el inventario.');
    }

    $stmtInventario = $conexion->prepare(
        'SELECT existencia, costo_promedio FROM inventario WHERE id_producto = ? FOR UPDATE'
    );
    $stmtInventario->bind_param('i', $idProducto);
    $stmtInventario->execute();
    $inventario = $stmtInventario->get_result()->fetch_assoc();
    $stmtInventario->close();

    if (!$inventario) {
        throw new DomainException('El producto ya no tiene un registro en el inventario.');
    }

    $existenciaAnterior = (int) $inventario['existencia'];
    $costoPromedio = round((float) $inventario['costo_promedio'], 2);

    if ($tipo === 'salida' && $cantidad > $existenciaAnterior) {
        throw new DomainException('La cantidad de salida supera las existencias disponibles.');
    }

    $existenciaNueva = $tipo === 'entrada'
        ? $existenciaAnterior + $cantidad
        : $existenciaAnterior - $cantidad;

    if ($existenciaNueva > 4294967295) {
        throw new DomainException('La existencia supera el límite permitido.');
    }

    $saldoTotal = round($existenciaNueva * $costoPromedio, 2);
    if ($saldoTotal > 9999999999.99) {
        throw new DomainException('El saldo valorizado supera el máximo permitido en kardex.');
    }

    $nombreMovimiento = $tipo === 'entrada' ? 'Ajuste de entrada' : 'Ajuste de salida';
    $idTipoMovimiento = obtenerTipoMovimientoAjuste($conexion, $nombreMovimiento, $tipo);

    $stmtAjuste = $conexion->prepare(
        'INSERT INTO ajuste_inventario (id_producto, id_usuario, tipo, cantidad, motivo)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmtAjuste->bind_param('iisis', $idProducto, $idUsuario, $tipo, $cantidad, $motivo);
    $stmtAjuste->execute();
    $idAjuste = $conexion->insert_id;
    $stmtAjuste->close();

    $stmtActualizar = $conexion->prepare(
        'UPDATE inventario SET existencia = ? WHERE id_producto = ?'
    );
    $stmtActualizar->bind_param('ii', $existenciaNueva, $idProducto);
    $stmtActualizar->execute();
    $stmtActualizar->close();

    $stmtKardex = $conexion->prepare(
        'INSERT INTO kardex
            (id_producto, id_tipo_movimiento, id_usuario, id_ajuste, cantidad,
             costo_unitario, existencias, costo_promedio, saldo_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmtKardex->bind_param(
        'iiiiididd',
        $idProducto,
        $idTipoMovimiento,
        $idUsuario,
        $idAjuste,
        $cantidad,
        $costoPromedio,
        $existenciaNueva,
        $costoPromedio,
        $saldoTotal
    );
    $stmtKardex->execute();
    $stmtKardex->close();

    $conexion->commit();
    $transaccionIniciada = false;

    responderAjuste(true, 'El ajuste se registró; se actualizó el inventario y el kardex.', [
        'id_ajuste' => $idAjuste,
        'existencia' => $existenciaNueva,
        'costo_promedio' => $costoPromedio
    ]);
} catch (Throwable $error) {
    if ($transaccionIniciada) {
        $conexion->rollback();
    }
    error_log('DAOAjusteInventario guardar: ' . $error->getMessage());
    if ($error instanceof DomainException) {
        http_response_code(422);
        responderAjuste(false, $error->getMessage());
    }
    http_response_code(500);
    responderAjuste(false, 'No se pudo registrar el ajuste; no se guardaron cambios.');
}
