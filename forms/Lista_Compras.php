<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('compras.consultar');
$titulo_pagina = "Historial de Compras";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $titulo_pagina; ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/Lista_Compras.css?v=<?php echo filemtime(__DIR__ . '/../css/Lista_Compras.css'); ?>">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-fluid lp">
      <section class="lp-hero" aria-labelledby="titulo-compras">
        <span class="lp-hero-icono" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></span>
        <div class="lp-hero-texto">
          <h1 id="titulo-compras">Historial de Compras</h1>
          <p>Consulta, filtra y administra las compras registradas.</p>
        </div>
        <a href="Compra.php" class="lp-btn-hero"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Registrar compra</a>
        <i class="bi bi-cart-check lp-hero-deco" aria-hidden="true"></i>
      </section>

      <section class="lp-resumen" aria-label="Resumen de compras">
        <button type="button" class="lp-stat lp-stat-azul" data-atajo="" aria-pressed="true">
          <span class="lp-stat-icono"><i class="bi bi-receipt" aria-hidden="true"></i></span>
          <span><span class="lp-stat-num d-block" id="stat-total">0</span><span class="lp-stat-etq">Todas las compras</span></span>
        </button>
        <button type="button" class="lp-stat lp-stat-verde" data-atajo="registrada" aria-pressed="false">
          <span class="lp-stat-icono"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
          <span><span class="lp-stat-num d-block" id="stat-registradas">0</span><span class="lp-stat-etq">Compras registradas</span></span>
        </button>
        <button type="button" class="lp-stat lp-stat-gris" data-atajo="anulada" aria-pressed="false">
          <span class="lp-stat-icono"><i class="bi bi-x-circle" aria-hidden="true"></i></span>
          <span><span class="lp-stat-num d-block" id="stat-anuladas">0</span><span class="lp-stat-etq">Compras anuladas</span></span>
        </button>
        <div class="lp-stat lp-stat-morado">
          <span class="lp-stat-icono"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
          <span><span class="lp-stat-valor d-block" id="stat-monto">$0.00</span><span class="lp-stat-etq">Total de compras registradas</span></span>
        </div>
      </section>

      <section class="lp-panel" aria-label="Listado de compras">
        <div class="lp-barra">
          <div class="lp-chips" role="group" aria-label="Filtrar por estado">
            <button type="button" class="lp-chip activo" data-estado="" aria-pressed="true">Todas</button>
            <button type="button" class="lp-chip" data-estado="registrada" aria-pressed="false">Registradas</button>
            <button type="button" class="lp-chip" data-estado="anulada" aria-pressed="false">Anuladas</button>
          </div>

          <div class="lp-herr">
            <label class="lp-buscar">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input id="buscarCompra" type="search" placeholder="Buscar compra, proveedor o usuario..." aria-label="Buscar compras" autocomplete="off">
            </label>

            <div class="lp-filtros">
              <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
              </button>
              <div id="panelFiltros" class="lp-menu" hidden>
                <div class="lp-filtro-grid">
                  <label for="fechaDesde">Desde
                    <input type="date" id="fechaDesde">
                  </label>
                  <label for="fechaHasta">Hasta
                    <input type="date" id="fechaHasta">
                  </label>
                  <label for="totalMinimo">Total mínimo
                    <input type="number" id="totalMinimo" min="0" step="0.01" placeholder="$0.00">
                  </label>
                  <label for="totalMaximo">Total máximo
                    <input type="number" id="totalMaximo" min="0" step="0.01" placeholder="$0.00">
                  </label>
                </div>
                <div class="d-flex flex-column gap-2">
                  <button type="button" id="limpiar-filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Limpiar filtros</button>
                  <button type="button" id="print-table" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1" aria-hidden="true"></i> Imprimir lista</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lp-tabla-wrap" id="tabla-wrap">
          <table class="lp-tabla" id="tabla-compras">
            <thead>
              <tr>
                <th data-orden="id_compra">Compra <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="fecha">Fecha <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="proveedor">Proveedor <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="usuario">Registró <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="lineas" class="lp-centro">Líneas <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="subtotal" class="lp-der">Subtotal <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="iva_credito" class="lp-der">IVA <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="total" class="lp-der">Total <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th data-orden="estado" class="lp-centro">Estado <i class="bi bi-arrow-down-up" aria-hidden="true"></i></th>
                <th class="lp-centro lp-col-acc">Acciones</th>
              </tr>
            </thead>
            <tbody id="cuerpoCompras">
              <tr><td colspan="10" class="text-center py-4">Cargando compras...</td></tr>
            </tbody>
          </table>
        </div>

        <div id="estado-vacio" class="lp-vacio" hidden>
          <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i><i class="bi bi-search"></i></div>
          <div class="lp-vacio-txt">
            <h2 id="vacio-titulo">No hay compras para mostrar</h2>
            <p id="vacio-texto">Aún no se han registrado compras. Puedes registrar una nueva compra o ajustar los filtros.</p>
            <a id="vacio-accion" href="Compra.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar compra</a>
          </div>
        </div>

        <div class="lp-pie">
          <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de registros: 0</strong></span>
          <div class="lp-pie-der">
            <label>Registros por página:
              <select id="tam-pagina" aria-label="Registros por página">
                <option>10</option><option>25</option><option>50</option><option>100</option>
              </select>
            </label>
            <div id="table-pagination" class="lp-pager" aria-label="Paginación"></div>
          </div>
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
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSLista_Compras.js?v=<?php echo filemtime(__DIR__ . '/../js/JSLista_Compras.js'); ?>"></script>
</body>
</html>
