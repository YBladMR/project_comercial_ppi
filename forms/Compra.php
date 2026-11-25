<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('compras.registrar');
$titulo_pagina = "Registrar Compra";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $titulo_pagina; ?> - Casa Aurelia</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <style>
    .compra-tabla-wrap { overflow-x: auto; }
    .compra-tabla { min-width: 760px; }
    .compra-totales { max-width: 23rem; margin-left: auto; }
    .compra-totales .total-final { font-size: 1.2rem; }
    .compra-mensaje { min-height: 1.5rem; }
  </style>
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-lg">
      <div class="card mb-4">
        <div class="card-header fw-bold">Registrar Compra</div>
        <div class="card-body">
          <form id="formularioCompra" novalidate>
            <div id="mensajeCompra" class="alert alert-info compra-mensaje" role="status">
              El costo unitario se registra antes del IVA. El sistema calcula el crédito fiscal al 13%.
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label for="id_proveedor" class="form-label">Proveedor *</label>
                <select id="id_proveedor" class="form-select" required>
                  <option value="">Cargando proveedores...</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="usuarioActual" class="form-label">Registrado por</label>
                <input id="usuarioActual" class="form-control" type="text" value="<?php echo htmlspecialchars(obtener_nombre_usuario(), ENT_QUOTES, 'UTF-8'); ?>" readonly autocomplete="off">
                <div class="form-text">El sistema registra la compra con la cuenta que inició sesión.</div>
              </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <h5 class="mb-0">Productos recibidos</h5>
              <button type="button" id="btnAgregarLinea" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar producto
              </button>
            </div>

            <div class="compra-tabla-wrap mb-4">
              <table class="table table-bordered align-middle compra-tabla">
                <thead class="table-light">
                  <tr>
                    <th>Producto</th>
                    <th style="width: 130px">Cantidad</th>
                    <th style="width: 180px">Costo unitario ($)</th>
                    <th style="width: 160px" class="text-end">Subtotal ($)</th>
                    <th style="width: 70px" class="text-center">Quitar</th>
                  </tr>
                </thead>
                <tbody id="detalleCompra"></tbody>
              </table>
            </div>

            <div class="compra-totales mb-4">
              <div class="d-flex justify-content-between py-1"><span>Subtotal</span><strong id="subtotalCompra">$0.00</strong></div>
              <div class="d-flex justify-content-between py-1"><span>IVA crédito (13%)</span><strong id="ivaCompra">$0.00</strong></div>
              <hr class="my-2">
              <div class="d-flex justify-content-between py-1 total-final"><span>Total</span><strong id="totalCompra">$0.00</strong></div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="Lista_Compras.php" class="btn btn-secondary">Cancelar</a>
              <button type="submit" id="btnGuardarCompra" class="btn btn-primary">
                <i class="bi bi-save" aria-hidden="true"></i> Registrar compra
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer px-4"><div>Casa Aurelia &copy; 2026.</div></footer>
  </div>

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSCompra.js?v=<?php echo filemtime('../js/JSCompra.js'); ?>"></script>
</body>
</html>
