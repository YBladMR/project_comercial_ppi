<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('ventas.registrar');
$titulo_pagina = "Registrar Venta";
?>
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

<body data-csrf-token="<?php echo htmlspecialchars(obtener_token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">

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
                <label for="buscar_cliente" class="form-label">Cliente *</label>
                <input type="hidden" id="id_cliente" name="id_cliente" value="" autocomplete="off">
                <input type="search" class="form-control" id="buscar_cliente" autocomplete="off"
                       placeholder="Escribe nombre, apellido o DUI" aria-describedby="ayuda_buscar_cliente">
                <div class="form-text" id="ayuda_buscar_cliente">Empieza a escribir para buscar un cliente.</div>
                <div class="list-group mt-2 d-none" id="resultados_clientes" role="listbox" aria-label="Resultados de clientes"></div>
                <div class="border rounded p-2 mt-2 d-none" id="cliente_seleccionado" role="status">
                  <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                      <div class="fw-semibold" id="texto_cliente_seleccionado"></div>
                      <small class="text-body-secondary">DUI: <span id="dui_cliente_seleccionado"></span></small>
                    </div>
                    <button type="button" class="btn btn-sm btn-link p-0" id="btn_cambiar_cliente">Cambiar</button>
                  </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="btn_registrar_cliente">
                  <i class="bi bi-person-plus me-1" aria-hidden="true"></i>Registrar nuevo cliente
                </button>
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
                <input type="text" class="form-control" id="fecha_hora" readonly autocomplete="off">
              </div>
            </div>
            <div class="form-text mt-2">Al contado la venta queda <strong>pagada</strong>; al crédito se registran la prima y el plan mensual, y permanece pendiente hasta liquidarse. La fecha y hora se registran automáticamente.</div>
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
                <input type="number" class="form-control" id="cantidad" min="1" step="1" value="1" autocomplete="off">
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

            <section id="panelCredito" class="card border-primary mt-4" hidden aria-labelledby="tituloCredito">
              <div class="card-header fw-bold" id="tituloCredito"><i class="bi bi-credit-card me-1"></i> Parámetros del crédito</div>
              <div class="card-body">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label for="prima" class="form-label">Prima obligatoria *</label>
                    <div class="input-group"><span class="input-group-text">$</span>
                      <input type="number" class="form-control" id="prima" min="0.01" step="0.01" value="1.00" inputmode="decimal" autocomplete="off">
                    </div>
                    <div class="form-text">Debe recibirse al registrar la venta y se descuenta del total.</div>
                  </div>
                  <div class="col-md-4">
                    <label for="numero_cuotas" class="form-label">Número de cuotas mensuales *</label>
                    <input type="number" class="form-control" id="numero_cuotas" min="1" max="120" step="1" value="6" autocomplete="off">
                  </div>
                  <div class="col-md-4">
                    <label for="fecha_primer_vencimiento" class="form-label">Primer vencimiento *</label>
                    <input type="date" class="form-control" id="fecha_primer_vencimiento" autocomplete="off">
                    <div class="form-text">Las siguientes cuotas vencen el mismo día de cada mes. Si el mes es más corto, se usa su último día.</div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="tasa_interes_credito">Interés ordinario anual</label>
                    <div class="input-group"><input type="text" class="form-control" id="tasa_interes_credito" value="20.00" readonly autocomplete="off"><span class="input-group-text">%</span></div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="tasa_moratoria_credito">Interés moratorio anual</label>
                    <div class="input-group"><input type="text" class="form-control" id="tasa_moratoria_credito" value="5.00" readonly autocomplete="off"><span class="input-group-text">%</span></div>
                    <div class="form-text">Se aplica solo al capital vencido pendiente.</div>
                  </div>
                </div>

                <div class="alert alert-light border mt-3 mb-3" id="resumenCredito" aria-live="polite">
                  Completa la prima y las cuotas para ver la proyección.
                </div>
                <div class="table-responsive" id="envolturaPlanPreview" hidden>
                  <table class="table table-sm table-striped align-middle mb-0">
                    <thead><tr><th>Cuota</th><th>Vencimiento</th><th class="text-end">Capital</th><th class="text-end">Interés proyectado</th><th class="text-end">Pago esperado</th></tr></thead>
                    <tbody id="cuerpoPlanPreview"></tbody>
                  </table>
                </div>
                <div class="form-text mt-2">El plan es la proyección original. Los pagos reales, intereses acumulados y saldos se reflejarán en el estado de cuenta. No se genera ticket.</div>
              </div>
            </section>

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
