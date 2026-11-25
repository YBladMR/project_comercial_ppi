<?php
declare(strict_types=1);

require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$opcion = (string) ($_POST['opcion'] ?? $_GET['opcion'] ?? '');
$permisos = [
    'listar' => 'creditos.consultar',
    'consultar' => 'creditos.consultar',
    'registrarPago' => 'creditos.registrar_pago',
];

if (!isset($permisos[$opcion])) {
    http_response_code(400);
    responderCredito(false, 'La operación de créditos no es válida.');
}

requerir_permiso($permisos[$opcion], true);
if ($opcion === 'registrarPago') {
    requerir_post_api();
}

require_once __DIR__ . '/../conexion.php';
date_default_timezone_set('America/El_Salvador');

try {
    if ($opcion === 'listar') {
        listarCreditos($conexion);
    }
    if ($opcion === 'consultar') {
        consultarCredito($conexion, $_GET['id_credito'] ?? $_POST['id_credito'] ?? '');
    }
    if ($opcion === 'registrarPago') {
        registrarPagoCredito($conexion);
    }
} catch (mysqli_sql_exception $error) {
    if (isset($conexion) && ($conexion->errno || $conexion->in_transaction)) {
        $conexion->rollback();
    }
    error_log('DAOCreditos SQL: ' . $error->getMessage());
    http_response_code(500);
    responderCredito(false, 'No se pudo guardar o consultar el crédito.');
} catch (RuntimeException $error) {
    if ($conexion->errno || $conexion->in_transaction) {
        $conexion->rollback();
    }
    responderCredito(false, $error->getMessage());
} catch (Throwable $error) {
    if ($conexion->errno || $conexion->in_transaction) {
        $conexion->rollback();
    }
    error_log('DAOCreditos: ' . $error->getMessage());
    http_response_code(500);
    responderCredito(false, 'No se pudo procesar la operación del crédito.');
}

function responderCredito(bool $estado, string $mensaje = '', array $extra = []): void
{
    echo json_encode(
        array_merge(['estado' => $estado, 'mensaje' => $mensaje], $extra),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function idCreditoValido($valor): int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT);
    if ($id === false || $id <= 0) {
        throw new RuntimeException('Selecciona un crédito válido.');
    }
    return (int) $id;
}

function montoCreditoValido($valor): float
{
    $texto = trim((string) $valor);
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $texto)) {
        throw new RuntimeException('El monto debe ser positivo y tener hasta dos decimales.');
    }
    $monto = round((float) $texto, 2);
    if ($monto <= 0) {
        throw new RuntimeException('Ingresa un monto mayor que cero.');
    }
    return $monto;
}

function fechaCredito(string $fecha): DateTimeImmutable
{
    return new DateTimeImmutable($fecha, new DateTimeZone('America/El_Salvador'));
}

/** Interés simple por saldo diario, segmentado por año calendario. */
function interesPorDias(float $capital, float $tasaAnual, DateTimeImmutable $desde, DateTimeImmutable $hasta): float
{
    if ($capital <= 0 || $tasaAnual <= 0 || $hasta <= $desde) {
        return 0.0;
    }

    $cursor = $desde->setTime(0, 0);
    $fin = $hasta->setTime(0, 0);
    $total = 0.0;
    while ($cursor < $fin) {
        $inicioSiguienteAnio = $cursor->setDate((int) $cursor->format('Y') + 1, 1, 1);
        $finSegmento = $inicioSiguienteAnio < $fin ? $inicioSiguienteAnio : $fin;
        $dias = (int) $cursor->diff($finSegmento)->days;
        $diasAnio = $cursor->format('L') === '1' ? 366 : 365;
        $total += $capital * ($tasaAnual / 100) * $dias / $diasAnio;
        $cursor = $finSegmento;
    }
    return round($total, 2);
}

function cargarCuotasConEstado(mysqli $conexion, int $idCredito, bool $bloquear = false): array
{
    $sql = "SELECT pp.id_plan_pago, pp.numero_cuota, pp.monto_cuota,
                   pp.capital_proyectado, pp.interes_proyectado, pp.saldo_capital_proyectado,
                   pp.fecha_vencimiento, pp.estado,
                   ce.capital_pendiente, ce.interes_pendiente, ce.mora_pendiente,
                   ce.fecha_ultimo_calculo_mora
            FROM plan_pago pp
            INNER JOIN credito_cuota_estado ce ON ce.id_plan_pago = pp.id_plan_pago
            WHERE pp.id_credito = ?
            ORDER BY pp.numero_cuota";
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idCredito);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($filas as &$fila) {
        foreach (['id_plan_pago', 'numero_cuota'] as $campo) {
            $fila[$campo] = (int) $fila[$campo];
        }
        foreach (['monto_cuota', 'capital_proyectado', 'interes_proyectado', 'saldo_capital_proyectado', 'capital_pendiente', 'interes_pendiente', 'mora_pendiente'] as $campo) {
            $fila[$campo] = round((float) $fila[$campo], 2);
        }
    }
    unset($fila);
    return $filas;
}

/** Acumula interés corriente y mora en memoria, sin cargar interés sobre interés. */
function acumularIntereses(array $credito, array &$cuotas, DateTimeImmutable $hoy): array
{
    $saldoCapital = round(array_sum(array_column($cuotas, 'capital_pendiente')), 2);
    $ultimaFechaInteres = fechaCredito((string) $credito['fecha_ultimo_calculo_interes']);
    $interesAgregado = interesPorDias(
        $saldoCapital,
        (float) $credito['tasa_interes'],
        $ultimaFechaInteres,
        $hoy
    );

    if ($interesAgregado > 0) {
        foreach ($cuotas as &$cuota) {
            if ($cuota['capital_pendiente'] > 0 || $cuota['interes_pendiente'] > 0 || $cuota['mora_pendiente'] > 0) {
                $cuota['interes_pendiente'] = round($cuota['interes_pendiente'] + $interesAgregado, 2);
                break;
            }
        }
        unset($cuota);
    }

    foreach ($cuotas as &$cuota) {
        $fechaVencimiento = fechaCredito((string) $cuota['fecha_vencimiento']);
        if ($hoy <= $fechaVencimiento || $cuota['capital_pendiente'] <= 0) {
            continue;
        }

        $ultimaMora = fechaCredito((string) $cuota['fecha_ultimo_calculo_mora']);
        if ($ultimaMora < $fechaVencimiento) {
            $ultimaMora = $fechaVencimiento;
        }
        $moraAgregada = interesPorDias(
            (float) $cuota['capital_pendiente'],
            (float) $credito['tasa_interes_moratoria'],
            $ultimaMora,
            $hoy
        );
        $cuota['mora_pendiente'] = round($cuota['mora_pendiente'] + $moraAgregada, 2);
        $cuota['fecha_ultimo_calculo_mora'] = $hoy->format('Y-m-d');
    }
    unset($cuota);

    return [
        'capital' => round(array_sum(array_column($cuotas, 'capital_pendiente')), 2),
        'interes' => round(array_sum(array_column($cuotas, 'interes_pendiente')), 2),
        'mora' => round(array_sum(array_column($cuotas, 'mora_pendiente')), 2),
        'interes_agregado' => $interesAgregado,
    ];
}

function obtenerEstadoActual(array $credito, array $cuotas, DateTimeImmutable $hoy): array
{
    $saldo = acumularIntereses($credito, $cuotas, $hoy);
    $montoVencido = 0.0;
    $hayMora = false;
    foreach ($cuotas as $cuota) {
        $fechaVencimiento = fechaCredito((string) $cuota['fecha_vencimiento']);
        if ($hoy > $fechaVencimiento) {
            $saldoCuota = $cuota['capital_pendiente'] + $cuota['interes_pendiente'] + $cuota['mora_pendiente'];
            $montoVencido += $saldoCuota;
            if ($saldoCuota > 0.009) {
                $hayMora = true;
            }
        }
    }
    $saldo['vencido'] = round($montoVencido, 2);
    $saldo['total'] = round($saldo['capital'] + $saldo['interes'] + $saldo['mora'], 2);
    $saldo['estado'] = $saldo['total'] <= 0.009 ? 'pagado' : ($hayMora ? 'en_mora' : 'al_dia');
    $saldo['cuotas'] = $cuotas;
    return $saldo;
}

function cargarCredito(mysqli $conexion, int $idCredito, bool $bloquear = false): ?array
{
    $sql = "SELECT cr.*, v.estado AS estado_venta, v.id_cliente, v.total AS total_venta,
                   v.fecha_hora, CONCAT(cl.nombre, ' ', IFNULL(cl.apellido, '')) AS cliente,
                   cl.dui, IFNULL(u.nombre, '') AS usuario
            FROM credito cr
            INNER JOIN venta v ON v.id_venta = cr.id_venta
            INNER JOIN cliente cl ON cl.id_cliente = v.id_cliente
            LEFT JOIN usuario u ON u.id_usuario = v.id_usuario
            WHERE cr.id_credito = ?";
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('i', $idCredito);
    $stmt->execute();
    $credito = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    return $credito;
}

function listarCreditos(mysqli $conexion): void
{
    $resultado = $conexion->query(
        "SELECT cr.id_credito, cr.id_venta, cr.prima, cr.capital_financiado,
                cr.saldo_capital, cr.cuota, cr.numero_cuotas, cr.fecha_primer_vencimiento,
                cr.fecha_aprobacion, cr.estado, v.fecha_hora,
                CONCAT(cl.nombre, ' ', IFNULL(cl.apellido, '')) AS cliente, cl.dui
         FROM credito cr
         INNER JOIN venta v ON v.id_venta = cr.id_venta
         INNER JOIN cliente cl ON cl.id_cliente = v.id_cliente
         ORDER BY v.fecha_hora DESC, cr.id_credito DESC"
    );
    $hoy = new DateTimeImmutable('today', new DateTimeZone('America/El_Salvador'));
    $creditos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $id = (int) $fila['id_credito'];
        $credito = cargarCredito($conexion, $id);
        $cuotas = cargarCuotasConEstado($conexion, $id);
        $estado = obtenerEstadoActual($credito, $cuotas, $hoy);
        $creditos[] = [
            'id_credito' => $id,
            'id_venta' => (int) $fila['id_venta'],
            'cliente' => trim($fila['cliente']),
            'dui' => $fila['dui'],
            'fecha_hora' => $fila['fecha_hora'],
            'prima' => (float) $fila['prima'],
            'capital_financiado' => (float) $fila['capital_financiado'],
            'saldo_capital' => $estado['capital'],
            'saldo_interes' => $estado['interes'],
            'saldo_mora' => $estado['mora'],
            'saldo_actual' => $estado['total'],
            'monto_vencido' => $estado['vencido'],
            'cuota' => (float) $fila['cuota'],
            'numero_cuotas' => (int) $fila['numero_cuotas'],
            'fecha_primer_vencimiento' => $fila['fecha_primer_vencimiento'],
            'estado' => $estado['estado'],
        ];
    }
    $resultado->free();
    responderCredito(true, '', ['creditos' => $creditos]);
}

function consultarCredito(mysqli $conexion, $valorId): void
{
    $idCredito = idCreditoValido($valorId);
    $credito = cargarCredito($conexion, $idCredito);
    if (!$credito) {
        responderCredito(false, 'El crédito no existe.');
    }

    $cuotas = cargarCuotasConEstado($conexion, $idCredito);
    $hoy = new DateTimeImmutable('today', new DateTimeZone('America/El_Salvador'));
    $estado = obtenerEstadoActual($credito, $cuotas, $hoy);

    $stmtAplicaciones = $conexion->prepare(
        "SELECT ap.id_plan_pago, SUM(ap.monto) AS abonado
         FROM aplicacion_pago_credito ap
         INNER JOIN pago p ON p.id_pago = ap.id_pago
         WHERE p.id_plan_pago IN (SELECT id_plan_pago FROM plan_pago WHERE id_credito = ?)
         GROUP BY ap.id_plan_pago"
    );
    $stmtAplicaciones->bind_param('i', $idCredito);
    $stmtAplicaciones->execute();
    $abonadoPorPlan = [];
    $resultadoAplicaciones = $stmtAplicaciones->get_result();
    while ($fila = $resultadoAplicaciones->fetch_assoc()) {
        $abonadoPorPlan[(int) $fila['id_plan_pago']] = (float) $fila['abonado'];
    }
    $stmtAplicaciones->close();

    foreach ($estado['cuotas'] as &$cuota) {
        $cuota['abonado'] = round($abonadoPorPlan[$cuota['id_plan_pago']] ?? 0, 2);
        $cuota['estado_real'] = $cuota['capital_pendiente'] + $cuota['interes_pendiente'] + $cuota['mora_pendiente'] <= 0.009
            ? 'pagado'
            : ($cuota['abonado'] > 0 ? 'abono_parcial' : $cuota['estado']);
    }
    unset($cuota);

    $stmtPagos = $conexion->prepare(
        "SELECT p.id_pago, p.fecha_pago, p.monto, p.tipo_pago,
                p.interes_corriente, p.mora, p.abono_capital,
                ec.monto_cuotas, ec.abono_interes, ec.abono_mora, ec.abono_capital AS capital_aplicado,
                ec.saldo_final, IFNULL(u.nombre, '') AS usuario
         FROM pago p
         INNER JOIN plan_pago pp ON pp.id_plan_pago = p.id_plan_pago
         LEFT JOIN estado_cuenta ec ON ec.id_pago = p.id_pago
         LEFT JOIN usuario u ON u.id_usuario = p.id_usuario
         WHERE pp.id_credito = ?
         ORDER BY p.fecha_pago DESC, p.id_pago DESC"
    );
    $stmtPagos->bind_param('i', $idCredito);
    $stmtPagos->execute();
    $pagos = $stmtPagos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtPagos->close();

    foreach ($pagos as &$pago) {
        foreach (['id_pago'] as $campo) $pago[$campo] = (int) $pago[$campo];
        foreach (['monto', 'interes_corriente', 'mora', 'abono_capital', 'monto_cuotas', 'abono_interes', 'abono_mora', 'capital_aplicado', 'saldo_final'] as $campo) {
            $pago[$campo] = round((float) ($pago[$campo] ?? 0), 2);
        }
        $pago['id_pago'] = (int) $pago['id_pago'];
    }
    unset($pago);

    $creditoSalida = [
        'id_credito' => (int) $credito['id_credito'],
        'id_venta' => (int) $credito['id_venta'],
        'cliente' => trim($credito['cliente']),
        'dui' => $credito['dui'],
        'usuario' => $credito['usuario'],
        'fecha_venta' => $credito['fecha_hora'],
        'fecha_aprobacion' => $credito['fecha_aprobacion'],
        'fecha_primer_vencimiento' => $credito['fecha_primer_vencimiento'],
        'total_venta' => (float) $credito['total_venta'],
        'prima' => (float) $credito['prima'],
        'capital_financiado' => (float) $credito['capital_financiado'],
        'tasa_interes' => (float) $credito['tasa_interes'],
        'tasa_interes_moratoria' => (float) $credito['tasa_interes_moratoria'],
        'cuota' => (float) $credito['cuota'],
        'numero_cuotas' => (int) $credito['numero_cuotas'],
        'saldo_capital' => $estado['capital'],
        'saldo_interes' => $estado['interes'],
        'saldo_mora' => $estado['mora'],
        'saldo_actual' => $estado['total'],
        'monto_vencido' => $estado['vencido'],
        'estado' => $estado['estado'],
    ];

    responderCredito(true, '', [
        'credito' => $creditoSalida,
        'cuotas' => $estado['cuotas'],
        'pagos' => $pagos,
    ]);
}

function registrarPagoCredito(mysqli $conexion): void
{
    $idCredito = idCreditoValido($_POST['id_credito'] ?? 0);
    $monto = montoCreditoValido($_POST['monto'] ?? '');
    $tipoPago = (string) ($_POST['tipo_pago'] ?? 'cuota');
    if (!in_array($tipoPago, ['cuota', 'abono_capital', 'liquidacion'], true)) {
        throw new RuntimeException('El tipo de pago no es válido.');
    }

    $conexion->begin_transaction();
    $credito = cargarCredito($conexion, $idCredito, true);
    if (!$credito) {
        throw new RuntimeException('El crédito no existe.');
    }
    if ($credito['estado'] === 'pagado' || $credito['estado_venta'] === 'pagado') {
        throw new RuntimeException('Este crédito ya está pagado.');
    }

    $cuotas = cargarCuotasConEstado($conexion, $idCredito, true);
    if (!$cuotas) {
        throw new RuntimeException('El crédito no tiene cuotas generadas.');
    }
    $hoy = new DateTimeImmutable('today', new DateTimeZone('America/El_Salvador'));
    $ahora = new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador'));
    $resumenAntes = acumularIntereses($credito, $cuotas, $hoy);
    $saldoAntes = round($resumenAntes['capital'] + $resumenAntes['interes'] + $resumenAntes['mora'], 2);
    if ($saldoAntes <= 0) {
        throw new RuntimeException('El crédito ya no tiene saldo pendiente.');
    }

    if ($tipoPago === 'liquidacion' && round(abs($monto - $saldoAntes), 2) > 0) {
        throw new RuntimeException('Para liquidar, ingresa el saldo exacto mostrado en el estado de cuenta: $' . number_format($saldoAntes, 2, '.', ''));
    }
    if ($tipoPago !== 'liquidacion' && $monto > $saldoAntes) {
        throw new RuntimeException('El monto supera el saldo total. Usa la opción Liquidar crédito.');
    }
    $restante = $monto;
    $aplicaciones = [];
    $totales = ['interes_corriente' => 0.0, 'interes_moratorio' => 0.0, 'capital' => 0.0];

    aplicarPagoAConcepto($cuotas, $restante, $aplicaciones, $totales, 'mora_pendiente', 'interes_moratorio');
    aplicarPagoAConcepto($cuotas, $restante, $aplicaciones, $totales, 'interes_pendiente', 'interes_corriente');
    aplicarPagoACapital($cuotas, $restante, $aplicaciones, $totales);

    if ($restante > 0.009) {
        throw new RuntimeException('El monto no pudo aplicarse al saldo real del crédito.');
    }

    $idPlanAncla = (int) ($cuotas[0]['id_plan_pago'] ?? 0);
    if ($idPlanAncla <= 0) {
        throw new RuntimeException('No hay una cuota disponible para asociar el pago.');
    }
    $fechaPago = $ahora->format('Y-m-d H:i:s');
    $moraPagada = round($totales['interes_moratorio'], 2);
    $interesPagado = round($totales['interes_corriente'], 2);
    $capitalPagado = round($totales['capital'], 2);
    $numeroComprobante = null;

    $stmtPago = $conexion->prepare(
        "INSERT INTO pago
            (id_plan_pago, id_usuario, fecha_pago, monto, tipo_pago, interes_corriente,
             abono_capital, numero_comprobante, mora)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $idUsuario = (int) $_SESSION['id_usuario'];
    $stmtPago->bind_param(
        'iisdsddsd',
        $idPlanAncla,
        $idUsuario,
        $fechaPago,
        $monto,
        $tipoPago,
        $interesPagado,
        $capitalPagado,
        $numeroComprobante,
        $moraPagada
    );
    $stmtPago->execute();
    $idPago = (int) $stmtPago->insert_id;
    $stmtPago->close();

    $stmtAplicacion = $conexion->prepare(
        'INSERT INTO aplicacion_pago_credito (id_pago, id_plan_pago, concepto, monto) VALUES (?, ?, ?, ?)'
    );
    foreach ($aplicaciones as $aplicacion) {
        $idPlanAplicacion = $aplicacion['id_plan_pago'];
        $concepto = $aplicacion['concepto'];
        $montoAplicado = $aplicacion['monto'];
        $stmtAplicacion->bind_param('iisd', $idPago, $idPlanAplicacion, $concepto, $montoAplicado);
        $stmtAplicacion->execute();
    }
    $stmtAplicacion->close();

    $stmtActualizarEstado = $conexion->prepare(
        "UPDATE credito_cuota_estado
         SET capital_pendiente = ?, interes_pendiente = ?, mora_pendiente = ?, fecha_ultimo_calculo_mora = ?
         WHERE id_plan_pago = ?"
    );
    $stmtActualizarPlan = $conexion->prepare(
        "UPDATE plan_pago SET estado = 'pagado' WHERE id_plan_pago = ? AND estado = 'pendiente'"
    );
    $capitalRestante = 0.0;
    $interesRestante = 0.0;
    $moraRestante = 0.0;
    foreach ($cuotas as $cuota) {
        $capitalCuota = round((float) $cuota['capital_pendiente'], 2);
        $interesCuota = round((float) $cuota['interes_pendiente'], 2);
        $moraCuota = round((float) $cuota['mora_pendiente'], 2);
        $fechaUltimaMora = (string) $cuota['fecha_ultimo_calculo_mora'];
        $fechaVencimiento = fechaCredito((string) $cuota['fecha_vencimiento']);
        if ($hoy > $fechaVencimiento) {
            $fechaUltimaMora = $hoy->format('Y-m-d');
        }
        $idPlan = (int) $cuota['id_plan_pago'];
        $stmtActualizarEstado->bind_param('dddsi', $capitalCuota, $interesCuota, $moraCuota, $fechaUltimaMora, $idPlan);
        $stmtActualizarEstado->execute();
        if ($capitalCuota <= 0.009 && $interesCuota <= 0.009 && $moraCuota <= 0.009) {
            $stmtActualizarPlan->bind_param('i', $idPlan);
            $stmtActualizarPlan->execute();
        }
        $capitalRestante += $capitalCuota;
        $interesRestante += $interesCuota;
        $moraRestante += $moraCuota;
    }
    $stmtActualizarEstado->close();
    $stmtActualizarPlan->close();

    $capitalRestante = round($capitalRestante, 2);
    $interesRestante = round($interesRestante, 2);
    $moraRestante = round($moraRestante, 2);
    $saldoFinal = round($capitalRestante + $interesRestante + $moraRestante, 2);
    $fechaActualizacion = $hoy->format('Y-m-d');
    $stmtCredito = $conexion->prepare(
        "UPDATE credito
         SET saldo_capital = ?, fecha_ultimo_calculo_interes = ?, estado = ?
         WHERE id_credito = ?"
    );
    $estadoCredito = $saldoFinal <= 0.009 ? 'pagado' : 'pendiente';
    $stmtCredito->bind_param('dssi', $capitalRestante, $fechaActualizacion, $estadoCredito, $idCredito);
    $stmtCredito->execute();
    $stmtCredito->close();

    if ($estadoCredito === 'pagado') {
        $stmtVenta = $conexion->prepare("UPDATE venta SET estado = 'pagado' WHERE id_venta = ? AND tipo_venta = 'credito'");
        $idVenta = (int) $credito['id_venta'];
        $stmtVenta->bind_param('i', $idVenta);
        $stmtVenta->execute();
        $stmtVenta->close();
    }

    $montoCuotasEstado = $monto;
    $stmtEstado = $conexion->prepare(
        'INSERT INTO estado_cuenta (id_pago, monto_cuotas, abono_interes, abono_mora, abono_capital, saldo_final)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmtEstado->bind_param(
        'iddddd',
        $idPago,
        $montoCuotasEstado,
        $interesPagado,
        $moraPagada,
        $capitalPagado,
        $saldoFinal
    );
    $stmtEstado->execute();
    $stmtEstado->close();

    $conexion->commit();
    responderCredito(true, 'Pago registrado correctamente.', [
        'id_pago' => $idPago,
        'fecha_pago' => $fechaPago,
        'monto' => $monto,
        'interes_corriente' => $interesPagado,
        'mora' => $moraPagada,
        'abono_capital' => $capitalPagado,
        'saldo_final' => $saldoFinal,
        'estado_credito' => $estadoCredito,
    ]);
}

function aplicarPagoAConcepto(array &$cuotas, float &$restante, array &$aplicaciones, array &$totales, string $campo, string $concepto): void
{
    foreach ($cuotas as &$cuota) {
        if ($restante <= 0.009) break;
        $disponible = round((float) $cuota[$campo], 2);
        if ($disponible <= 0) continue;
        $aplicado = min($restante, $disponible);
        $aplicado = round($aplicado, 2);
        $cuota[$campo] = round($disponible - $aplicado, 2);
        $restante = round($restante - $aplicado, 2);
        $totales[$concepto] = round($totales[$concepto] + $aplicado, 2);
        $aplicaciones[] = [
            'id_plan_pago' => (int) $cuota['id_plan_pago'],
            'concepto' => $concepto,
            'monto' => $aplicado,
        ];
    }
    unset($cuota);
}

function aplicarPagoACapital(array &$cuotas, float &$restante, array &$aplicaciones, array &$totales): void
{
    foreach ($cuotas as &$cuota) {
        if ($restante <= 0.009) break;
        $disponible = round((float) $cuota['capital_pendiente'], 2);
        if ($disponible <= 0) continue;
        $aplicado = min($restante, $disponible);
        $aplicado = round($aplicado, 2);
        $cuota['capital_pendiente'] = round($disponible - $aplicado, 2);
        $restante = round($restante - $aplicado, 2);
        $totales['capital'] = round($totales['capital'] + $aplicado, 2);
        $aplicaciones[] = [
            'id_plan_pago' => (int) $cuota['id_plan_pago'],
            'concepto' => 'capital',
            'monto' => $aplicado,
        ];
    }
    unset($cuota);
}
