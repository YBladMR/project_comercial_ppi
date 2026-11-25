<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';

if (!tiene_permiso('ventas.consultar') && !tiene_permiso('ventas.registrar')) {
    http_response_code(403);
    exit('No tienes permiso para ver esta factura.');
}

$idVenta = filter_input(INPUT_GET, 'id_venta', FILTER_VALIDATE_INT);
$venta = null;
$detalle = [];
$error = '';

try {
    if (!$idVenta || $idVenta <= 0) {
        throw new RuntimeException('El número de venta no es válido.');
    }

    require_once __DIR__ . '/../conexion.php';

    $stmt = $conexion->prepare(
        "SELECT v.id_venta, v.fecha_hora,
                CONCAT(c.nombre, ' ', IFNULL(c.apellido, '')) AS cliente,
                c.dui, c.telefono, c.direccion_entrega,
                v.tipo_venta, v.estado, v.subtotal, v.iva_debito, v.total,
                IFNULL(u.nombre, '') AS usuario
         FROM venta AS v
         INNER JOIN cliente AS c ON c.id_cliente = v.id_cliente
         LEFT JOIN usuario AS u ON u.id_usuario = v.id_usuario
         WHERE v.id_venta = ?"
    );
    $stmt->bind_param('i', $idVenta);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$venta) {
        throw new RuntimeException('No se encontró la venta solicitada.');
    }
    $venta['cliente'] = trim($venta['cliente']);

    $stmt = $conexion->prepare(
        "SELECT p.nombre, d.cantidad, d.precio_unitario, d.subtotal
         FROM detalle_venta AS d
         INNER JOIN producto AS p ON p.id_producto = d.id_producto
         WHERE d.id_venta = ?
         ORDER BY d.id_detalle_venta"
    );
    $stmt->bind_param('i', $idVenta);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conexion->close();
} catch (Throwable $e) {
    error_log('FacturaVenta: ' . $e->getMessage());
    $error = $e->getMessage();
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}

function escaparFactura($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function dineroFactura($valor): string
{
    return '$' . number_format((float) $valor, 2, '.', ',');
}

$codigoFactura = $venta ? 'V-' . str_pad((string) $venta['id_venta'], 6, '0', STR_PAD_LEFT) : '';
$fechaFactura = $venta ? date('d/m/Y H:i', strtotime($venta['fecha_hora'])) : '';
$tipoFactura = $venta && $venta['tipo_venta'] === 'credito' ? 'Al crédito' : 'Al contado';
$estadoFactura = [
    'pagado' => 'Pagada',
    'pendiente' => 'Pendiente',
    'anulado' => 'Anulada',
][$venta['estado'] ?? ''] ?? ($venta['estado'] ?? '');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $venta ? 'Factura ' . escaparFactura($codigoFactura) : 'Factura de venta'; ?> - Comercial X</title>
  <style>
    @page { size: letter; margin: 8mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 24px; color: #111; background: #f1f3f5; font: 10px Arial, Helvetica, sans-serif; }
    .acciones { display: flex; justify-content: center; gap: 10px; margin: 0 auto 18px; }
    .acciones button, .acciones a { padding: 9px 14px; border: 1px solid #adb5bd; border-radius: 5px; background: #fff; color: #222; font: inherit; text-decoration: none; cursor: pointer; }
    .pagina { width: 216mm; min-height: 263mm; margin: auto; padding: 4mm; background: #fff; box-shadow: 0 2px 12px #0002; display: flex; flex-direction: column; justify-content: space-between; }
    .factura { min-height: 125mm; padding: 4mm 5mm; display: flex; flex-direction: column; break-inside: avoid; page-break-inside: avoid; }
    .tipo-copia { text-align: right; font-size: 9px; font-weight: bold; margin-bottom: 2mm; }
    .encabezado { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #777; padding-bottom: 3mm; }
    .encabezado h1 { margin: 0 0 1mm; font-family: Georgia, 'Times New Roman', serif; font-size: 19px; }
    .folio { display: grid; gap: 2mm; text-align: right; }
    .folio strong { font-size: 16px; }
    .datos { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2mm 5mm; padding: 3mm 0; }
    .datos div { min-width: 0; }
    .datos small { display: block; color: #555; font-size: 8px; margin-bottom: 1mm; }
    .datos strong { font-size: 9px; font-weight: normal; overflow-wrap: anywhere; }
    .datos .direccion { grid-column: span 2; }
    .detalle { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .detalle th { padding: 2mm 1mm; border-bottom: 1px solid #555; text-align: left; font-weight: bold; }
    .detalle td { padding: 1.5mm 1mm; border-bottom: 1px solid #ddd; vertical-align: top; overflow-wrap: anywhere; }
    .detalle th:first-child, .detalle td:first-child { width: 52%; }
    .detalle th:nth-child(2), .detalle td:nth-child(2) { width: 10%; }
    .detalle th:nth-child(3), .detalle td:nth-child(3) { width: 18%; }
    .detalle th:nth-child(4), .detalle td:nth-child(4) { width: 20%; }
    .centro { text-align: center !important; }
    .derecha { text-align: right !important; white-space: nowrap; }
    .pie-factura { display: flex; justify-content: space-between; gap: 8mm; margin-top: auto; padding-top: 3mm; }
    .estado { align-self: flex-end; }
    .totales { width: 55mm; }
    .totales div { display: flex; justify-content: space-between; padding: 1mm 0; }
    .totales .total { border-top: 1px solid #444; margin-top: 1mm; padding-top: 2mm; font-size: 12px; }
    .gracias { border-top: 1px solid #aaa; text-align: center; margin-top: 3mm; padding-top: 2mm; color: #444; }
    .corte { border: 0; border-top: 1px dashed #777; width: 100%; margin: 2mm 0; }
    .error { max-width: 620px; margin: 8vh auto; padding: 24px; background: #fff; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
    .error a { color: #2453c5; }
    @media print {
      body { padding: 0; background: #fff; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .acciones { display: none !important; }
      .pagina { width: auto; min-height: 263mm; margin: 0; padding: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <?php if ($error !== ''): ?>
    <main class="error">
      <h1>No se pudo mostrar la factura</h1>
      <p><?php echo escaparFactura($error); ?></p>
      <a href="Lista-ventas.php">Volver a las ventas</a>
    </main>
  <?php else: ?>
    <nav class="acciones" aria-label="Acciones de factura">
      <button type="button" onclick="window.print()">Imprimir de nuevo</button>
      <a href="Lista-ventas.php">Volver al sistema</a>
    </nav>
    <main class="pagina">
      <?php foreach (['ORIGINAL CLIENTE', 'COPIA COMERCIO'] as $tipoCopia): ?>
        <section class="factura">
          <div class="tipo-copia"><?php echo escaparFactura($tipoCopia); ?></div>
          <header class="encabezado">
            <div>
              <h1>COMERCIAL X</h1>
              <div>Factura de venta</div>
            </div>
            <div class="folio">
              <strong><?php echo escaparFactura($codigoFactura); ?></strong>
              <span><?php echo escaparFactura($fechaFactura); ?></span>
            </div>
          </header>

          <section class="datos">
            <div><small>Cliente</small><strong><?php echo escaparFactura($venta['cliente']); ?></strong></div>
            <div><small>DUI</small><strong><?php echo escaparFactura($venta['dui'] ?: '—'); ?></strong></div>
            <div><small>Teléfono</small><strong><?php echo escaparFactura($venta['telefono'] ?: '—'); ?></strong></div>
            <div><small>Tipo de venta</small><strong><?php echo escaparFactura($tipoFactura); ?></strong></div>
            <div class="direccion"><small>Dirección</small><strong><?php echo escaparFactura($venta['direccion_entrega'] ?: '—'); ?></strong></div>
            <div><small>Atendió</small><strong><?php echo escaparFactura($venta['usuario'] ?: '—'); ?></strong></div>
          </section>

          <table class="detalle">
            <thead>
              <tr><th>Producto</th><th class="centro">Cant.</th><th class="derecha">Precio</th><th class="derecha">Subtotal</th></tr>
            </thead>
            <tbody>
              <?php foreach ($detalle as $item): ?>
                <tr>
                  <td><?php echo escaparFactura($item['nombre']); ?></td>
                  <td class="centro"><?php echo escaparFactura($item['cantidad']); ?></td>
                  <td class="derecha"><?php echo escaparFactura(dineroFactura($item['precio_unitario'])); ?></td>
                  <td class="derecha"><?php echo escaparFactura(dineroFactura($item['subtotal'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>

          <div class="pie-factura">
            <div class="estado">Estado: <?php echo escaparFactura($estadoFactura); ?></div>
            <div class="totales">
              <div><span>Subtotal</span><strong><?php echo escaparFactura(dineroFactura($venta['subtotal'])); ?></strong></div>
              <div><span>IVA (13%)</span><strong><?php echo escaparFactura(dineroFactura($venta['iva_debito'])); ?></strong></div>
              <div class="total"><span>Total</span><strong><?php echo escaparFactura(dineroFactura($venta['total'])); ?></strong></div>
            </div>
          </div>
          <div class="gracias">Gracias por su compra.</div>
        </section>
        <?php if ($tipoCopia === 'ORIGINAL CLIENTE'): ?><hr class="corte"><?php endif; ?>
      <?php endforeach; ?>
    </main>
    <script>
      window.addEventListener('afterprint', () => window.location.replace('Lista-ventas.php'));
      window.addEventListener('load', () => window.setTimeout(() => window.print(), 300));
    </script>
  <?php endif; ?>
</body>
</html>
