<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('creditos.consultar');
$idCredito = filter_var($_GET['id_credito'] ?? 0, FILTER_VALIDATE_INT);
if (!$idCredito || $idCredito <= 0) {
    http_response_code(400);
    exit('Crédito inválido.');
}
$puedeRegistrarPago = tiene_permiso('creditos.registrar_pago');
$titulo_pagina = 'Detalle del crédito';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle del crédito - Comercial X</title>
  <link rel="stylesheet" href="../vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/detalle-credito.css?v=<?php echo filemtime(__DIR__ . '/../css/detalle-credito.css'); ?>">
  <script src="../js/config.js"></script>
  <script src="../js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>
<body data-id-credito="<?php echo (int) $idCredito; ?>" data-puede-pagar="<?php echo $puedeRegistrarPago ? '1' : '0'; ?>" data-csrf-token="<?php echo htmlspecialchars(obtener_token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <div class="container-fluid dc">
      <div class="dc-topline">
        <a href="Lista-Creditos.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Créditos</a>
        <span id="estadoCredito" class="dc-estado">Cargando…</span>
      </div>
      <section class="dc-hero">
        <div class="dc-hero-icon"><i class="bi bi-credit-card-2-front"></i></div>
        <div>
          <h1 id="tituloCredito">Crédito</h1>
          <p id="subtituloCredito">Cargando información…</p>
        </div>
      </section>

      <section class="dc-resumen" aria-label="Resumen del crédito">
        <article><span>Capital financiado</span><strong id="capitalInicial">$0.00</strong></article>
        <article><span>Capital pendiente</span><strong id="capitalPendiente">$0.00</strong></article>
        <article><span>Interés acumulado</span><strong id="interesPendiente">$0.00</strong></article>
        <article><span>Mora acumulada</span><strong id="moraPendiente">$0.00</strong></article>
        <article class="dc-total"><span>Saldo real a la fecha</span><strong id="saldoActual">$0.00</strong></article>
      </section>

      <div class="dc-grid">
        <main class="dc-main">
          <section class="dc-panel">
            <div class="dc-panel-head"><div><h2>Plan de pagos</h2><p>Proyección original; las fechas y montos se conservan.</p></div></div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Capital proyectado</th><th class="text-end">Interés proyectado</th><th class="text-end">Pago proyectado</th><th class="text-end">Capital real pendiente</th><th>Estado</th></tr></thead>
                <tbody id="cuerpoPlan"><tr><td colspan="7" class="text-center py-4">Cargando plan…</td></tr></tbody>
              </table>
            </div>
          </section>

          <section class="dc-panel">
            <div class="dc-panel-head"><div><h2>Estado de cuenta</h2><p>Movimientos reales, con la distribución de cada pago. Los pagos registrados son definitivos.</p></div></div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Fecha y hora</th><th>Movimiento</th><th>Registró</th><th class="text-end">Monto</th><th class="text-end">Interés</th><th class="text-end">Mora</th><th class="text-end">Capital</th><th class="text-end">Saldo final</th></tr></thead>
                <tbody id="cuerpoEstadoCuenta"><tr><td colspan="8" class="text-center py-4">Cargando movimientos…</td></tr></tbody>
              </table>
            </div>
            <div id="sinMovimientos" class="dc-empty" hidden>No hay pagos registrados.</div>
          </section>
        </main>

        <?php if ($puedeRegistrarPago): ?>
        <aside class="dc-panel dc-pago-panel">
          <div class="dc-panel-head"><div><h2>Registrar pago</h2><p>La fecha y hora se toman del servidor al confirmar.</p></div></div>
          <form id="formPago" class="dc-pago-form" autocomplete="off">
            <label for="tipoPago" class="form-label">Tipo de movimiento</label>
            <select id="tipoPago" class="form-select mb-3">
              <option value="cuota">Pago o abono a cuotas</option>
              <option value="abono_capital">Abono anticipado a capital</option>
              <option value="liquidacion">Liquidar todo el crédito</option>
            </select>
            <label for="montoPago" class="form-label">Monto recibido *</label>
            <div class="input-group mb-2"><span class="input-group-text">$</span><input id="montoPago" class="form-control" type="number" min="0.01" step="0.01" inputmode="decimal" autocomplete="off" required></div>
            <div id="ayudaTipoPago" class="form-text mb-3">Puedes registrar un abono menor a la cuota. El excedente, después de intereses y mora acumulados, reduce capital.</div>
            <div class="dc-regla-pago"><i class="bi bi-info-circle"></i><span>El pago se aplica primero a mora acumulada, luego a interés corriente y finalmente a capital. No se cobra interés sobre intereses.</span></div>
            <button id="btnRegistrarPago" class="btn btn-primary w-100 mt-3" type="submit"><i class="bi bi-cash-coin me-1"></i> Registrar pago</button>
            <div class="form-text mt-2">No se genera ticket. Cada movimiento queda en el estado de cuenta.</div>
          </form>
        </aside>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="../vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="../vendors/simplebar/js/simplebar.min.js"></script>
  <script src="../js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="../js/JSDetalle-Credito.js?v=<?php echo filemtime(__DIR__ . '/../js/JSDetalle-Credito.js'); ?>"></script>
</body>
</html>
