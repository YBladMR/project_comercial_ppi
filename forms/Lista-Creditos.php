<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('creditos.consultar');
$titulo_pagina = 'Créditos';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Créditos - Comercial X</title>
  <link rel="stylesheet" href="../vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="../css/lista-creditos.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-creditos.css'); ?>">
  <script src="../js/config.js"></script>
  <script src="../js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <div class="container-fluid lc">
      <section class="lc-hero">
        <div class="lc-hero-icon"><i class="bi bi-credit-card-2-front"></i></div>
        <div class="lc-hero-text">
          <h1>Créditos</h1>
          <p>Consulta los saldos reales, cuotas proyectadas y pagos registrados.</p>
        </div>
        <?php if (tiene_permiso('ventas.registrar')): ?>
          <a class="btn btn-light rounded-pill fw-semibold" href="Ventas.php?tipo=credito"><i class="bi bi-plus-lg me-1"></i> Registrar crédito</a>
        <?php endif; ?>
      </section>

      <section class="lc-cards" aria-label="Resumen de créditos">
        <article class="lc-card"><span>Total de créditos</span><strong id="totalCreditos">0</strong><i class="bi bi-journal-text"></i></article>
        <article class="lc-card"><span>Al día</span><strong id="creditosAlDia">0</strong><i class="bi bi-check-circle"></i></article>
        <article class="lc-card lc-card-alerta"><span>En mora</span><strong id="creditosEnMora">0</strong><i class="bi bi-exclamation-circle"></i></article>
        <article class="lc-card lc-card-saldo"><span>Saldo real pendiente</span><strong id="saldoTotal">$0.00</strong><i class="bi bi-cash-stack"></i></article>
      </section>

      <section class="lc-panel">
        <div class="lc-tools">
          <div class="lc-filtros" role="group" aria-label="Filtrar por estado">
            <button type="button" class="lc-chip activo" data-estado="">Todos</button>
            <button type="button" class="lc-chip" data-estado="al_dia">Al día</button>
            <button type="button" class="lc-chip" data-estado="en_mora">En mora</button>
            <button type="button" class="lc-chip" data-estado="pagado">Pagados</button>
          </div>
          <label class="lc-buscar"><i class="bi bi-search"></i><input id="filtroTexto" type="search" placeholder="Buscar por cliente o código" autocomplete="off"></label>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Código</th><th>Fecha</th><th>Cliente</th><th class="text-end">Capital inicial</th><th class="text-end">Capital pendiente</th><th class="text-end">Interés y mora</th><th class="text-end">Saldo actual</th><th>Estado</th><th class="text-center">Acción</th></tr></thead>
            <tbody id="cuerpoCreditos"><tr><td colspan="9" class="text-center py-4">Cargando créditos…</td></tr></tbody>
          </table>
        </div>
        <div id="mensajeVacio" class="lc-vacio" hidden>
          <i class="bi bi-credit-card"></i>
          <div><strong>No hay créditos que coincidan</strong><p>Registra una venta al crédito o cambia los filtros.</p></div>
        </div>
        <div class="lc-pie">
          <span id="totalRegistros">Total de registros: 0</span>
          <div class="lc-pie-derecha">
            <label>Registros por página
              <select id="tamPagina"><option>10</option><option>25</option><option>50</option><option value="0">Todos</option></select>
            </label>
            <div class="lc-pager"><button type="button" id="paginaAnterior" aria-label="Página anterior">‹</button><span id="paginaActual">1 / 1</span><button type="button" id="paginaSiguiente" aria-label="Página siguiente">›</button></div>
          </div>
        </div>
      </section>
    </div>
  </div>
  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="../vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="../vendors/simplebar/js/simplebar.min.js"></script>
  <script src="../js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="../js/JSLista-Creditos.js?v=<?php echo filemtime(__DIR__ . '/../js/JSLista-Creditos.js'); ?>"></script>
</body>
</html>
