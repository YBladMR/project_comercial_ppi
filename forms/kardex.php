<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('inventario.consultar');
$titulo_pagina = 'Kárdex';
include_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <style>
    .kd { --kd-blue:#2f4fe0; --kd-radius:1rem; }
    .kd-hero { display:flex; flex-wrap:wrap; align-items:center; gap:1rem; padding:1.25rem 1.5rem; margin-bottom:1rem; color:#fff; border-radius:var(--kd-radius); background:linear-gradient(110deg,#14257a,#2340c4 60%,#3b6bff); }
    .kd-hero-icon { display:grid; place-items:center; width:3.5rem; height:3.5rem; flex:none; border:1px solid rgba(255,255,255,.45); border-radius:50%; background:rgba(255,255,255,.16); font-size:1.6rem; }
    .kd-hero h1 { margin:0; font-size:1.45rem; font-weight:700; }
    .kd-hero p { margin:.2rem 0 0; opacity:.85; }
    .kd-panel { overflow:hidden; margin-bottom:1.5rem; border:1px solid var(--cui-border-color); border-radius:var(--kd-radius); background:var(--cui-body-bg); color:var(--cui-body-color); box-shadow:0 .25rem .9rem rgba(30,50,120,.07); }
    .kd-head,.kd-foot { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:.75rem; padding:.9rem 1.1rem; }
    .kd-head { border-bottom:1px solid var(--cui-border-color); }
    .kd-foot { border-top:1px solid var(--cui-border-color); background:var(--cui-tertiary-bg); }
    .kd-filters { padding:1rem 1.1rem; border-bottom:1px solid var(--cui-border-color); }
    .kd-table-wrap { overflow-x:auto; }
    .kd-table { width:100%; min-width:1150px; border-collapse:collapse; }
    .kd-table th { padding:.75rem .8rem; text-align:left; white-space:nowrap; background:var(--cui-tertiary-bg); color:var(--cui-secondary-color); font-size:.75rem; text-transform:uppercase; letter-spacing:.035em; }
    .kd-table td { padding:.75rem .8rem; border-top:1px solid var(--cui-border-color); vertical-align:middle; }
    .kd-table tbody tr:hover { background:var(--cui-tertiary-bg); }
    .kd-muted { color:var(--cui-secondary-color); font-size:.8rem; }
    .kd-number { white-space:nowrap; text-align:right; font-variant-numeric:tabular-nums; }
    .kd-type { display:inline-block; padding:.2rem .65rem; border-radius:2rem; font-size:.78rem; font-weight:650; }
    .kd-type.entrada,.kd-qty.entrada { color:#12804b; background:rgba(31,184,112,.15); }
    .kd-type.salida,.kd-qty.salida { color:#c4363b; background:rgba(229,72,77,.13); }
    .kd-qty { font-weight:700; }
    .kd-state { padding:2.2rem 1rem; text-align:center; }
    .kd-state i { display:block; margin-bottom:.45rem; color:var(--kd-blue); font-size:2.3rem; }
    .kd-pages { display:flex; align-items:center; gap:.5rem; }
    .kd-pages button { min-width:2rem; height:2rem; border:1px solid var(--cui-border-color); border-radius:.55rem; background:var(--cui-body-bg); color:var(--cui-body-color); }
    .kd-pages button:disabled { opacity:.45; }
    @media print { .sidebar,.header,.footer,.kd-hero,.kd-filters,.kd-foot { display:none!important; } .kd-panel { border:0; box-shadow:none; } .kd-table { min-width:0; font-size:9pt; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <main class="container-fluid kd">
      <section class="kd-hero">
        <span class="kd-hero-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span>
        <div><h1>Kárdex de inventario</h1><p>Historial de movimientos y saldos después de cada operación.</p></div>
        <?php if (tiene_permiso('inventario.gestionar')): ?>
        <a class="btn btn-light ms-auto" href="<?php echo BASE_URL; ?>forms/ajustes-inventario.php"><i class="bi bi-sliders me-1" aria-hidden="true"></i> Registrar ajuste</a>
        <?php endif; ?>
      </section>

      <section class="kd-panel" aria-label="Movimientos del kardex">
        <div class="kd-head">
          <strong><i class="bi bi-list-ul me-2 text-primary" aria-hidden="true"></i>Movimientos registrados</strong>
          <span class="small text-body-secondary" id="totalKardex">Cargando...</span>
        </div>
        <div class="kd-filters row g-2 align-items-end">
          <div class="col-12 col-md-4"><label for="filtroProductoKardex" class="form-label small mb-1">Producto</label><select id="filtroProductoKardex" class="form-select form-select-sm"><option value="">Todos los productos</option></select></div>
          <div class="col-12 col-md-3"><label for="filtroTipoKardex" class="form-label small mb-1">Tipo de movimiento</label><select id="filtroTipoKardex" class="form-select form-select-sm"><option value="">Todos los tipos</option></select></div>
          <div class="col-6 col-md-2"><label for="filtroDesdeKardex" class="form-label small mb-1">Desde</label><input id="filtroDesdeKardex" class="form-control form-control-sm" type="date"></div>
          <div class="col-6 col-md-2"><label for="filtroHastaKardex" class="form-label small mb-1">Hasta</label><input id="filtroHastaKardex" class="form-control form-control-sm" type="date"></div>
          <div class="col-12 col-md-1 d-grid"><button type="button" id="limpiarKardex" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros"><i class="bi bi-x-circle" aria-hidden="true"></i><span class="d-md-none ms-1">Limpiar</span></button></div>
        </div>
        <div class="kd-table-wrap" id="tablaKardex" hidden>
          <table class="kd-table">
            <thead><tr><th>Fecha y hora</th><th>Producto</th><th>Tipo</th><th class="kd-number">Cantidad</th><th class="kd-number">Costo unitario</th><th class="kd-number">Existencia</th><th class="kd-number">Costo promedio</th><th class="kd-number">Saldo total</th><th>Usuario (ID)</th><th>Origen</th></tr></thead>
            <tbody id="cuerpoKardex"></tbody>
          </table>
        </div>
        <div class="kd-state" id="estadoKardex"><i class="bi bi-arrow-repeat" aria-hidden="true"></i><strong id="tituloKardex">Cargando movimientos...</strong><div class="kd-muted" id="detalleKardex">Un momento, por favor.</div></div>
        <div class="kd-foot">
          <span id="resumenKardex">Mostrando 0 movimientos</span>
          <div class="d-flex align-items-center gap-3">
            <label class="small">Filas por página: <select id="limiteKardex" class="form-select form-select-sm d-inline-block" style="width:auto"><option>10</option><option>25</option><option>50</option><option>100</option></select></label>
            <div class="kd-pages"><button type="button" id="anteriorKardex" aria-label="Página anterior" disabled><i class="bi bi-chevron-left"></i></button><span class="small" id="paginaKardex">1 / 1</span><button type="button" id="siguienteKardex" aria-label="Página siguiente" disabled><i class="bi bi-chevron-right"></i></button></div>
          </div>
        </div>
      </section>
    </main>
  </div>
  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/JSKardex.js?v=<?php echo filemtime(__DIR__ . '/../js/JSKardex.js'); ?>"></script>
</body>
</html>