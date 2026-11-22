<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('compras.consultar');
$titulo_pagina = "Consultar Compras";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $titulo_pagina; ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <style>
    .compras-hero { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1rem; }
    .compras-hero h1 { font-size:1.45rem; margin:0; }
    .compras-tabla-wrap { overflow-x:auto; }
    .compras-tabla { min-width:900px; }
    .compras-tabla th { white-space:nowrap; }
    .compras-acciones { display:flex; justify-content:center; gap:.4rem; }
    .compras-mensaje { min-height:1.5rem; }
  </style>
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-fluid">
      <section class="compras-hero">
        <div>
          <h1>Compras</h1>
          <p class="text-body-secondary mb-0">Consulta las compras registradas y su efecto en inventario.</p>
        </div>
        <a href="Compra.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Registrar compra</a>
      </section>


      <section class="card mb-4">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
          <strong>Historial de compras</strong>
          <div class="d-flex align-items-center gap-2">
            <label for="filtroEstadoCompra" class="form-label mb-0">Estado</label>
            <select id="filtroEstadoCompra" class="form-select form-select-sm" style="width:auto">
              <option value="">Todas</option>
              <option value="registrada">Registradas</option>
              <option value="anulada">Anuladas</option>
            </select>
          </div>
        </div>
        <div class="card-body p-0 compras-tabla-wrap">
          <table class="table table-hover align-middle mb-0 compras-tabla">
            <thead class="table-light">
              <tr>
                <th>Compra</th><th>Fecha</th><th>Proveedor</th><th>Registró</th>
                <th class="text-center">Líneas</th><th class="text-end">Subtotal</th>
                <th class="text-end">IVA</th><th class="text-end">Total</th>
                <th class="text-center">Estado</th><th class="text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="cuerpoCompras">
              <tr><td colspan="10" class="text-center py-4">Cargando compras...</td></tr>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <span id="totalCompras">Total de registros: 0</span>
          <span class="small text-body-secondary">Las anulaciones quedan registradas con la cuenta autenticada.</span>
        </div>
      </section>
    </div>
  </div>

  <div class="modal fade" id="modalDetalleCompra" tabindex="-1" aria-labelledby="tituloDetalleCompra" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="tituloDetalleCompra">Detalle de compra</h5>
          <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body" id="contenidoDetalleCompra"></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cerrar</button></div>
      </div>
    </div>
  </div>

  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSLista_Compras.js?v=<?php echo filemtime('../js/JSLista_Compras.js'); ?>"></script>
</body>
</html>
