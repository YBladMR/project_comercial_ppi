<?php if (!defined('BASE_URL')) define('BASE_URL', '/project_comercial_ppi/'); ?>
<?php ini_set('display_errors', 1); error_reporting(E_ALL); ?>
<?php $titulo_pagina = "Registrar Venta"; ?>
<?php $titulo_pagina = "Registrar Venta"; ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Venta - Comercial X</title>
  <link rel="stylesheet" href="../vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="../css/vendors/simplebar.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/config.js"></script>
  <script src="../js/color-modes.js"></script>

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>

<body>

  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-lg">

      <form id="formVenta" novalidate autocomplete="off">

        <!-- Datos de la venta -->
        <div class="card mb-4">
          <div class="card-header fw-bold">Registrar Nueva Venta</div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3 mb-md-0">
                <label for="id_cliente" class="form-label">Cliente *</label>
                <select class="form-select" id="id_cliente" name="id_cliente" required>
                  <option value="">[Seleccione un cliente]</option>
                </select>
              </div>

              <div class="col-md-3 mb-3 mb-md-0">
                <label for="tipo_venta" class="form-label">Tipo de venta *</label>
                <select class="form-select" id="tipo_venta" name="tipo_venta" required>
                  <option value="contado" selected>Al contado</option>
                  <option value="credito">Al crédito</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label">Fecha y hora</label>
                <input type="text" class="form-control" id="fecha_hora" readonly>
              </div>
            </div>
            <div class="form-text mt-2">Al contado la venta queda <strong>pagada</strong>; al crédito queda <strong>pendiente</strong>. La fecha y hora se registran automáticamente.</div>
          </div>
        </div>

        <!-- Detalle de la venta -->
        <div class="card mb-4">
          <div class="card-header fw-bold">Detalle de la venta</div>
          <div class="card-body">

            <div class="row align-items-end mb-2">
              <div class="col-md-6 mb-3 mb-md-0">
                <label for="producto" class="form-label">Producto</label>
                <select class="form-select" id="producto">
                  <option value="">[Seleccione un producto]</option>
                </select>
              </div>
              <div class="col-md-3 mb-3 mb-md-0">
                <label for="cantidad" class="form-label">Cantidad</label>
                <input type="number" class="form-control" id="cantidad" min="1" step="1" value="1">
              </div>
              <div class="col-md-3 d-grid">
                <button type="button" class="btn btn-outline-primary" id="btnAgregar">
                  <i class="bi bi-plus-lg"></i> Agregar
                </button>
              </div>
            </div>
            <div class="form-text mb-3" id="infoProducto">Selecciona un producto para ver su precio y existencia.</div>

            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0" id="tabla-detalle">
                <thead class="table-light">
                  <tr>
                    <th>Producto</th>
                    <th class="text-end">Precio unit.</th>
                    <th style="width: 9rem;">Cantidad</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-center" style="width: 4rem;"></th>
                  </tr>
                </thead>
                <tbody id="cuerpo-detalle"></tbody>
                <tfoot>
                  <tr>
                    <td colspan="3" class="text-end fw-semibold">Subtotal</td>
                    <td class="text-end" id="txtSubtotal">$0.00</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="3" class="text-end fw-semibold">IVA Débito Fiscal (13%)</td>
                    <td class="text-end" id="txtIva">$0.00</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td colspan="3" class="text-end fw-bold fs-5">Total</td>
                    <td class="text-end fw-bold fs-5" id="txtTotal">$0.00</td>
                    <td></td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
              <button type="button" id="btnGuardar" class="btn btn-primary">
                <i class="bi bi-save"></i> <span id="textoGuardar">Guardar Venta</span>
              </button>
            </div>

          </div>
        </div>

      </form>
    </div>
  </div>

  <footer class="footer px-4">
    <div>Comercial X &copy; 2026.</div>
  </footer>

  </div> <!-- Cierra el wrapper de la Barra_Lateral.php -->

  <script src="../vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="../vendors/simplebar/js/simplebar.min.js"></script>
  <script src="../js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>
  <script src="../js/JSVentas.js?v=<?php echo filemtime('../js/JSVentas.js'); ?>"></script>
</body>

</html>