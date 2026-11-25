<?php
declare(strict_types=1);

require_once __DIR__ . '/../autenticacion/seguridad.php';

if (!tiene_permiso('creditos.consultar') && !tiene_permiso('creditos.registrar_pago')) {
    mostrar_acceso_denegado();
}

date_default_timezone_set('America/El_Salvador');
$idPago = filter_input(INPUT_GET, 'id_pago', FILTER_VALIDATE_INT);
$pago = null;
$error = '';

try {
    if (!$idPago || $idPago <= 0) {
        throw new RuntimeException('El número de pago no es válido.');
    }

    require_once __DIR__ . '/../conexion.php';
    $stmt = $conexion->prepare(
        "SELECT p.id_pago, p.fecha_pago, p.monto, p.tipo_pago,
                p.interes_corriente, p.mora, p.abono_capital,
                ec.abono_interes, ec.abono_mora,
                ec.abono_capital AS capital_aplicado, ec.saldo_final,
                cr.id_credito, v.id_venta,
                CONCAT(cl.nombre, ' ', IFNULL(cl.apellido, '')) AS cliente,
                cl.dui, cl.telefono, IFNULL(u.nombre, '') AS usuario
         FROM pago AS p
         INNER JOIN plan_pago AS pp ON pp.id_plan_pago = p.id_plan_pago
         INNER JOIN credito AS cr ON cr.id_credito = pp.id_credito
         INNER JOIN venta AS v ON v.id_venta = cr.id_venta
         INNER JOIN cliente AS cl ON cl.id_cliente = v.id_cliente
         LEFT JOIN usuario AS u ON u.id_usuario = p.id_usuario
         LEFT JOIN estado_cuenta AS ec ON ec.id_pago = p.id_pago
         WHERE p.id_pago = ?"
    );
    $stmt->bind_param('i', $idPago);
    $stmt->execute();
    $pago = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();
    $conexion->close();

    if (!$pago) {
        throw new RuntimeException('No se encontró el pago solicitado.');
    }
    $pago['cliente'] = trim((string) $pago['cliente']);
} catch (Throwable $e) {
    error_log('TicketPagoCredito: ' . $e->getMessage());
    $error = $e->getMessage();
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}

function escaparTicketCredito($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dineroTicketCredito($valor): string
{
    return '$' . number_format((float) $valor, 2, '.', ',');
}

$tiposPago = [
    'prima' => 'Prima del crédito',
    'cuota' => 'Pago o abono a cuotas',
    'abono_capital' => 'Abono anticipado a capital',
    'liquidacion' => 'Liquidación del crédito',
];
$codigoPago = $pago ? 'P-' . str_pad((string) $pago['id_pago'], 6, '0', STR_PAD_LEFT) : '';
$codigoCredito = $pago ? 'C-' . str_pad((string) $pago['id_credito'], 6, '0', STR_PAD_LEFT) : '';
$fechaPago = $pago ? date('d/m/Y H:i', strtotime((string) $pago['fecha_pago'])) : '';
$tipoPago = $pago ? ($tiposPago[$pago['tipo_pago']] ?? (string) $pago['tipo_pago']) : '';
$interesPagado = $pago ? (float) ($pago['abono_interes'] ?? $pago['interes_corriente'] ?? 0) : 0;
$moraPagada = $pago ? (float) ($pago['abono_mora'] ?? $pago['mora'] ?? 0) : 0;
$capitalPagado = $pago ? (float) ($pago['capital_aplicado'] ?? $pago['abono_capital'] ?? 0) : 0;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $pago ? 'Ticket ' . escaparTicketCredito($codigoPago) : 'Ticket de pago de crédito'; ?> - Comercial X</title>
  <style>
    @page { size: 80mm auto; margin: 4mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 20px; color: #111; background: #f1f3f5; font: 12px Arial, Helvetica, sans-serif; }
    .acciones { display: flex; justify-content: center; gap: 10px; margin: 0 auto 16px; }
    .acciones button, .acciones a { padding: 9px 14px; border: 1px solid #adb5bd; border-radius: 5px; background: #fff; color: #222; font: inherit; text-decoration: none; cursor: pointer; }
    .ticket { width: 72mm; margin: auto; padding: 5mm; background: #fff; box-shadow: 0 2px 12px #0002; }
    header { text-align: center; border-bottom: 1px dashed #555; padding-bottom: 3mm; }
    h1 { margin: 0 0 1mm; font-family: Georgia, 'Times New Roman', serif; font-size: 18px; }
    header p { margin: 1mm 0; }
    .titulo { margin-top: 3mm; font-weight: bold; text-transform: uppercase; }
    .folio { margin: 3mm 0; text-align: center; font-size: 15px; font-weight: bold; }
    .datos { display: grid; grid-template-columns: 1fr; gap: 2mm; margin: 3mm 0; }
    .datos div { display: flex; justify-content: space-between; gap: 3mm; }
    .datos span:first-child { color: #444; }
    .datos strong { text-align: right; overflow-wrap: anywhere; }
    hr { border: 0; border-top: 1px dashed #777; margin: 3mm 0; }
    .total { display: flex; justify-content: space-between; gap: 4mm; padding: 2mm 0; font-size: 15px; font-weight: bold; }
    .saldo { display: flex; justify-content: space-between; gap: 4mm; padding-top: 2mm; border-top: 1px solid #555; font-weight: bold; }
    footer { margin-top: 4mm; padding-top: 3mm; border-top: 1px dashed #555; text-align: center; font-size: 10px; line-height: 1.5; }
    .error { max-width: 620px; margin: 8vh auto; padding: 24px; background: #fff; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .error a { color: #2453c5; }
    @media print {
      body { width: auto; padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .acciones { display: none !important; }
      .ticket { width: 72mm; margin: 0 auto; padding: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <?php if ($error !== ''): ?>
    <main class="error">
      <h1>No se pudo mostrar el ticket</h1>
      <p><?php echo escaparTicketCredito($error); ?></p>
      <a href="Lista-Creditos.php">Volver a créditos</a>
    </main>
  <?php else: ?>
    <nav class="acciones" aria-label="Acciones del ticket">
      <button type="button" onclick="window.print()">Imprimir de nuevo</button>
      <a href="Detalle-Credito.php?id_credito=<?php echo (int) $pago['id_credito']; ?>">Volver al crédito</a>
    </nav>
    <main class="ticket">
      <header>
        <h1>COMERCIAL X</h1>
        <p>Comprobante de pago</p>
        <p class="titulo">Pago de crédito</p>
      </header>
      <div class="folio"><?php echo escaparTicketCredito($codigoPago); ?></div>
      <section class="datos">
        <div><span>Fecha y hora</span><strong><?php echo escaparTicketCredito($fechaPago); ?></strong></div>
        <div><span>Cliente</span><strong><?php echo escaparTicketCredito($pago['cliente']); ?></strong></div>
        <div><span>DUI</span><strong><?php echo escaparTicketCredito($pago['dui'] ?: '—'); ?></strong></div>
        <div><span>Crédito</span><strong><?php echo escaparTicketCredito($codigoCredito); ?></strong></div>
        <div><span>Venta relacionada</span><strong>V-<?php echo str_pad((string) $pago['id_venta'], 6, '0', STR_PAD_LEFT); ?></strong></div>
        <div><span>Movimiento</span><strong><?php echo escaparTicketCredito($tipoPago); ?></strong></div>
        <div><span>Atendió</span><strong><?php echo escaparTicketCredito($pago['usuario'] ?: '—'); ?></strong></div>
      </section>
      <hr>
      <section class="datos" aria-label="Aplicación del pago">
        <div><span>Abono a mora</span><strong><?php echo escaparTicketCredito(dineroTicketCredito($moraPagada)); ?></strong></div>
        <div><span>Abono a interés</span><strong><?php echo escaparTicketCredito(dineroTicketCredito($interesPagado)); ?></strong></div>
        <div><span>Abono a capital</span><strong><?php echo escaparTicketCredito(dineroTicketCredito($capitalPagado)); ?></strong></div>
      </section>
      <div class="total"><span>Total recibido</span><span><?php echo escaparTicketCredito(dineroTicketCredito($pago['monto'])); ?></span></div>
      <div class="saldo"><span>Saldo del crédito</span><span><?php echo escaparTicketCredito($pago['saldo_final'] === null ? '—' : dineroTicketCredito($pago['saldo_final'])); ?></span></div>
      <footer>
        Este comprobante corresponde al pago registrado para el crédito indicado.<br>
        Gracias por su pago.
      </footer>
    </main>
    <script>
      window.addEventListener('afterprint', () => window.location.replace('Detalle-Credito.php?id_credito=<?php echo (int) $pago['id_credito']; ?>'));
      window.addEventListener('load', () => window.setTimeout(() => window.print(), 300));
    </script>
  <?php endif; ?>
</body>
</html>
