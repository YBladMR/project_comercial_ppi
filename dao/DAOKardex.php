<?php
declare(strict_types=1);

require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');
requerir_permiso('inventario.consultar', true);

function responderKardex(bool $estado, string $mensaje, ?array $datos = null, int $codigo = 200): void
{
    http_response_code($codigo);
    $respuesta = ['estado' => $estado, 'mensaje' => $mensaje];
    if ($datos !== null) $respuesta['datos'] = $datos;
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (($_GET['opcion'] ?? '') !== 'consultar') {
    responderKardex(false, 'La consulta solicitada no es válida.', null, 400);
}
require_once __DIR__ . '/../conexion.php';

function textoFiltroKardex($valor, string $nombre): string
{
    if (!is_scalar($valor)) throw new InvalidArgumentException("El filtro $nombre no es válido.");
    return trim((string) $valor);
}

function idFiltroKardex($valor, string $nombre): ?int
{
    $texto = textoFiltroKardex($valor, $nombre);
    if ($texto === '') return null;
    $id = filter_var($texto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) throw new InvalidArgumentException("El filtro $nombre debe ser un entero positivo.");
    return (int) $id;
}

function fechaFiltroKardex($valor, string $nombre): ?string
{
    $fecha = textoFiltroKardex($valor, $nombre);
    if ($fecha === '') return null;
    $validada = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
    if (!$validada || $validada->format('Y-m-d') !== $fecha) {
        throw new InvalidArgumentException("La fecha $nombre no es válida.");
    }
    return $fecha;
}

function consultarFilasKardex(mysqli $conexion, string $sql, string $tipos, array $parametros): array
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) throw new RuntimeException('No se pudo preparar la consulta del kardex.');
    if ($tipos !== '') {
        $argumentos = [$tipos];
        foreach ($parametros as &$parametro) $argumentos[] = &$parametro;
        unset($parametro);
        if (!call_user_func_array([$stmt, 'bind_param'], $argumentos)) {
            $stmt->close();
            throw new RuntimeException('No se pudieron aplicar los filtros del kardex.');
        }
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('No se pudo consultar el kardex.');
    }
    $resultado = $stmt->get_result();
    if (!$resultado) {
        $stmt->close();
        throw new RuntimeException('No se pudo leer el resultado del kardex.');
    }
    $filas = [];
    while ($fila = $resultado->fetch_assoc()) $filas[] = $fila;
    $resultado->free();
    $stmt->close();
    return $filas;
}

try {
    $idProducto = idFiltroKardex($_GET['id_producto'] ?? '', 'producto');
    $idTipo = idFiltroKardex($_GET['id_tipo_movimiento'] ?? '', 'tipo de movimiento');
    $desde = fechaFiltroKardex($_GET['fecha_desde'] ?? '', 'desde');
    $hasta = fechaFiltroKardex($_GET['fecha_hasta'] ?? '', 'hasta');
    if ($desde !== null && $hasta !== null && $desde > $hasta) {
        throw new InvalidArgumentException('La fecha desde no puede ser posterior a la fecha hasta.');
    }

    $pagina = filter_var(textoFiltroKardex($_GET['pagina'] ?? '1', 'página'), FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
    if ($pagina === false) throw new InvalidArgumentException('La página solicitada no es válida.');

    $limite = filter_var(textoFiltroKardex($_GET['limite'] ?? '10', 'límite'), FILTER_VALIDATE_INT);
    if (!in_array($limite, [10, 25, 50, 100], true)) {
        throw new InvalidArgumentException('La cantidad por página debe ser 10, 25, 50 o 100.');
    }

    $condiciones = [];
    $parametros = [];
    $tipos = '';
    if ($idProducto !== null) {
        $condiciones[] = 'k.id_producto = ?';
        $parametros[] = $idProducto;
        $tipos .= 'i';
    }
    if ($idTipo !== null) {
        $condiciones[] = 'k.id_tipo_movimiento = ?';
        $parametros[] = $idTipo;
        $tipos .= 'i';
    }
    if ($desde !== null) {
        $condiciones[] = 'k.fecha_hora >= ?';
        $parametros[] = $desde . ' 00:00:00';
        $tipos .= 's';
    }
    if ($hasta !== null) {
        $condiciones[] = 'k.fecha_hora < DATE_ADD(?, INTERVAL 1 DAY)';
        $parametros[] = $hasta;
        $tipos .= 's';
    }

    $where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';
    $joins = ' FROM kardex AS k
               INNER JOIN producto AS p ON p.id_producto = k.id_producto
               INNER JOIN tipo_movimiento AS t ON t.id_tipo_movimiento = k.id_tipo_movimiento
               INNER JOIN usuario AS u ON u.id_usuario = k.id_usuario';
    $conteo = consultarFilasKardex($conexion, 'SELECT COUNT(*) AS total' . $joins . $where, $tipos, $parametros);
    $total = (int) ($conteo[0]['total'] ?? 0);

    $parametrosPagina = $parametros;
    $parametrosPagina[] = (int) $limite;
    $parametrosPagina[] = ((int) $pagina - 1) * (int) $limite;
    $movimientos = consultarFilasKardex(
        $conexion,
        "SELECT k.id_kardex, DATE_FORMAT(k.fecha_hora, '%Y-%m-%d %H:%i:%s') AS fecha_hora,
                p.id_producto, p.nombre AS producto,
                t.id_tipo_movimiento, t.nombre AS tipo_movimiento, t.naturaleza,
                k.cantidad, k.costo_unitario, k.existencias, k.costo_promedio, k.saldo_total,
                u.id_usuario, u.nombre AS usuario, k.id_compra, k.id_venta, k.id_ajuste" .
                $joins . $where .
                ' ORDER BY k.fecha_hora DESC, k.id_kardex DESC LIMIT ? OFFSET ?',
        $tipos . 'ii',
        $parametrosPagina
    );

    $productosResultado = $conexion->query('SELECT id_producto, nombre FROM producto ORDER BY nombre');
    $productos = [];
    while ($producto = $productosResultado->fetch_assoc()) $productos[] = $producto;
    $productosResultado->free();

    $tiposResultado = $conexion->query(
        'SELECT id_tipo_movimiento, nombre, naturaleza FROM tipo_movimiento ORDER BY nombre'
    );
    $tiposMovimiento = [];
    while ($tipo = $tiposResultado->fetch_assoc()) $tiposMovimiento[] = $tipo;
    $tiposResultado->free();

    responderKardex(true, 'Movimientos consultados.', [
        'movimientos' => $movimientos,
        'productos' => $productos,
        'tipos' => $tiposMovimiento,
        'total' => $total,
        'pagina' => (int) $pagina,
        'limite' => (int) $limite,
        'total_paginas' => max(1, (int) ceil($total / (int) $limite))
    ]);
} catch (InvalidArgumentException $error) {
    responderKardex(false, $error->getMessage(), null, 422);
} catch (Throwable $error) {
    error_log('DAOKardex consultar: ' . $error->getMessage());
    responderKardex(false, 'No se pudieron cargar los movimientos del kardex.', null, 500);
}