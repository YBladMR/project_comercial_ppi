<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('ventas.consultar');
$titulo_pagina = "Lista de Ventas";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lista de Ventas - Comercial X</title>
  <link rel="stylesheet" href="../vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/config.js"></script>
  <script src="../js/color-modes.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

  <!-- Diseño propio de la lista (mismo estilo que Lista de Categorías y Lista de Productos) -->
  <style>
    .lp { --lp-azul: #2f4fe0; --lp-azul-osc: #1b2f8f; --lp-radio: 1rem; }

    .lp-hero {
      position: relative; overflow: hidden; color: #fff;
      border-radius: var(--lp-radio); padding: 1.5rem 1.75rem; margin-bottom: 1.25rem;
      display: flex; flex-wrap: wrap; align-items: center; gap: 1.25rem;
      background: linear-gradient(110deg, #14257a 0%, #2340c4 55%, #3b6bff 100%);
    }
    .lp-hero::before, .lp-hero::after {
      content: ""; position: absolute; border-radius: 50%; pointer-events: none;
      background: rgba(255, 255, 255, .07);
    }
    .lp-hero::before { width: 18rem; height: 18rem; right: -4rem; top: -9rem; }
    .lp-hero::after  { width: 11rem; height: 11rem; right: 12rem; bottom: -7rem; }
    .lp-hero-icono {
      width: 4rem; height: 4rem; border-radius: 50%; flex: none; z-index: 1;
      display: grid; place-items: center; font-size: 1.9rem;
      background: rgba(255, 255, 255, .16); border: 2px solid rgba(255, 255, 255, .45);
    }
    .lp-hero-texto { flex: 1 1 14rem; z-index: 1; }
    .lp-hero-texto h1 { font-size: 1.6rem; font-weight: 700; margin: 0; }
    .lp-hero-texto p { margin: .2rem 0 0; opacity: .85; }
    .lp-hero-deco { position: absolute; right: 1.5rem; bottom: -1.2rem; font-size: 8rem; opacity: .13; transform: rotate(-8deg); }
    .lp-btn-hero {
      z-index: 1; background: #fff; color: var(--lp-azul-osc); font-weight: 600;
      border: 0; border-radius: 2rem; padding: .55rem 1.25rem; text-decoration: none;
      box-shadow: 0 .4rem 1rem rgba(0, 0, 0, .18); transition: transform .15s;
    }
    .lp-btn-hero:hover { transform: translateY(-2px); color: var(--lp-azul-osc); }

    
    /* ---------- Panel principal ---------- */
    .lp-panel {
      background: var(--cui-body-bg); color: var(--cui-body-color);
      border: 1px solid var(--cui-border-color); border-radius: var(--lp-radio);
      box-shadow: 0 .25rem .9rem rgba(30, 50, 120, .07); overflow: hidden; margin-bottom: 1.5rem;
    }
    .lp-barra { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--cui-border-color); }
    .lp-chips, .lp-herr { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; }
    .lp-chip {
      border: 1px solid transparent; background: var(--cui-tertiary-bg); color: var(--cui-body-color);
      border-radius: 2rem; padding: .4rem .95rem; font-size: .9rem; font-weight: 500; cursor: pointer;
      transition: background .15s, color .15s;
    }
    .lp-chip:hover { background: var(--cui-secondary-bg); }
    .lp-chip.activo { background: var(--lp-azul); color: #fff; }
    .lp-buscar { position: relative; }
    .lp-buscar i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .lp-buscar input {
      width: 16rem; max-width: 100%; border-radius: 2rem; padding: .45rem 1rem .45rem 2.3rem;
      border: 1px solid var(--cui-border-color); background: var(--cui-body-bg); color: var(--cui-body-color);
    }
    .lp-buscar input:focus { outline: 2px solid rgba(47, 79, 224, .35); border-color: var(--lp-azul); }
    .lp-filtros { position: relative; }
    .lp-btn-filtros { background: var(--cui-body-bg); border: 1px solid var(--cui-border-color); border-radius: 2rem; padding: .45rem 1rem; color: var(--cui-body-color); }
    .lp-btn-filtros:hover { background: var(--cui-tertiary-bg); }
    .lp-menu {
      position: absolute; right: 0; top: calc(100% + .4rem); z-index: 20; min-width: 15rem; padding: .9rem;
      background: var(--cui-body-bg); border: 1px solid var(--cui-border-color); border-radius: .8rem;
      box-shadow: 0 .8rem 1.8rem rgba(0, 0, 0, .18);
    }
    .lp-menu[hidden] { display: none; }

    /* ---------- Tabla ---------- */
    .lp-tabla-wrap { overflow-x: auto; }
    .lp-tabla { width: 100%; border-collapse: collapse; }
    .lp-tabla th {
      text-align: left; padding: .8rem 1.1rem; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em;
      color: var(--cui-secondary-color); background: var(--cui-tertiary-bg); white-space: nowrap; user-select: none;
    }
    .lp-tabla th[data-orden] { cursor: pointer; }
    .lp-tabla th[data-orden]:hover { color: var(--cui-body-color); }
    .lp-tabla th .bi { font-size: .7rem; margin-left: .25rem; opacity: .8; }
    .lp-tabla td { padding: .8rem 1.1rem; border-top: 1px solid var(--cui-border-color); vertical-align: middle; }
    .lp-tabla tbody tr:hover { background: var(--cui-tertiary-bg); }
    .lp-tabla tr.lp-baja td:not(:last-child) { opacity: .55; }
    .lp-der { text-align: right !important; }
    .lp-centro { text-align: center !important; }
    .lp-prod { display: flex; align-items: center; gap: .75rem; }
    .lp-prod-ico { width: 2.5rem; height: 2.5rem; flex: none; border-radius: .7rem; display: grid; place-items: center; background: rgba(138, 79, 224, .13); color: #8a4fe0; font-size: 1.15rem; }
    .lp-prod-nom { font-weight: 600; }
    .lp-prod-sub { font-size: .8rem; color: var(--cui-secondary-color); }
    .lp-desc { color: var(--cui-secondary-color); max-width: 28rem; }
    .lp-cat { display: inline-block; padding: .2rem .7rem; border-radius: 2rem; font-size: .8rem; background: rgba(47, 79, 224, .12); color: var(--lp-azul); font-weight: 600; }
    .lp-cat.cero { background: rgba(120, 130, 150, .18); color: var(--cui-secondary-color); }
    .lp-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .75rem; border-radius: 2rem; font-size: .8rem; font-weight: 600; }
    .lp-pill::before { content: ""; width: .5rem; height: .5rem; border-radius: 50%; background: currentColor; }
    .lp-pill.activo { background: rgba(31, 184, 112, .15); color: #14a05f; }
    .lp-pill.inactivo { background: rgba(120, 130, 150, .18); color: var(--cui-secondary-color); }
    .lp-acc { display: inline-flex; gap: .4rem; }
    .lp-ib {
      width: 2.1rem; height: 2.1rem; display: grid; place-items: center; border-radius: 50%; text-decoration: none;
      border: 1px solid var(--cui-border-color); background: var(--cui-body-bg); color: var(--cui-body-color); transition: all .15s; cursor: pointer;
    }
    .lp-ib:hover { background: var(--lp-azul); border-color: var(--lp-azul); color: #fff; }
    .lp-ib.baja:hover { background: #e5484d; border-color: #e5484d; }
    .lp-ib.alta:hover { background: #1fb870; border-color: #1fb870; }

    /* ---------- Estado vacío ---------- */
    .lp-vacio { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 2.5rem; padding: 3rem 1.5rem; }
    .lp-vacio[hidden] { display: none; }
    .lp-vacio-img { position: relative; width: 11rem; height: 9rem; display: grid; place-items: center; }
    .lp-vacio-img::before { content: ""; position: absolute; inset: 0; border-radius: 50%; background: radial-gradient(circle, rgba(47, 79, 224, .18), transparent 70%); }
    .lp-vacio-img .bi-tags { position: relative; font-size: 5.5rem; color: var(--lp-azul); }
    .lp-vacio-img .bi-search { position: absolute; right: .6rem; bottom: .6rem; width: 2.8rem; height: 2.8rem; border-radius: 50%; display: grid; place-items: center; background: var(--lp-azul); color: #fff; font-size: 1.2rem; box-shadow: 0 .4rem .9rem rgba(47, 79, 224, .4); }
    .lp-vacio-txt { max-width: 22rem; }
    .lp-vacio-txt h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: .5rem; }
    .lp-vacio-txt p { color: var(--cui-secondary-color); margin-bottom: 1rem; }
    .lp-btn { display: inline-flex; align-items: center; gap: .4rem; border: 0; border-radius: 2rem; padding: .6rem 1.4rem; background: var(--lp-azul); color: #fff; font-weight: 600; text-decoration: none; box-shadow: 0 .4rem 1rem rgba(47, 79, 224, .35); }
    .lp-btn:hover { color: #fff; filter: brightness(1.1); }

    /* ---------- Pie de la tabla ---------- */
    .lp-pie { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem; padding: .8rem 1.1rem; background: var(--cui-tertiary-bg); border-top: 1px solid var(--cui-border-color); font-size: .9rem; }
    .lp-pie-der { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .lp-pie select { border: 1px solid var(--cui-border-color); border-radius: 2rem; background: var(--cui-body-bg); color: var(--cui-body-color); padding: .2rem .5rem; }
    .lp-pager { display: flex; gap: .3rem; }
    .lp-pager button { min-width: 2rem; height: 2rem; border-radius: .6rem; border: 1px solid var(--cui-border-color); background: var(--cui-body-bg); color: var(--cui-body-color); }
    .lp-pager button:hover:not(:disabled) { background: var(--cui-secondary-bg); }
    .lp-pager button.activo { background: var(--lp-azul); border-color: var(--lp-azul); color: #fff; }
    .lp-pager button:disabled { opacity: .4; cursor: default; }

    /* ---------- Impresión: solo la tabla ---------- */
    @media print {
      .sidebar, .header, .footer, .lp-hero, .lp-resumen, .lp-barra, .lp-pie, .lp-col-acc { display: none !important; }
      .lp-panel { border: 0; box-shadow: none; }
    }

    /* ---------- Ajustes para la lista de ventas ---------- */
    .lp-vacio-img .bi-receipt { position: relative; font-size: 5.5rem; color: var(--lp-azul); }
    .lp-pill.pendiente { background: rgba(245, 158, 11, .18); color: #b7791f; }
    .lp-num { text-align: right !important; white-space: nowrap; }
    .lp-fechas { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .lp-menu label.lp-et { font-size: .8rem; color: var(--cui-secondary-color); margin-bottom: .15rem; display: block; }
    .lp-menu select, .lp-menu input[type=date] {
      width: 100%; border: 1px solid var(--cui-border-color); border-radius: .6rem; padding: .3rem .5rem;
      background: var(--cui-body-bg); color: var(--cui-body-color);
    }
    .vd-cab { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: .75rem 1.25rem; margin-bottom: 1.25rem; }
    .vd-cab small { display: block; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: var(--cui-secondary-color); }
    .vd-cab strong { font-weight: 600; }
  </style>
</head>
<body
  data-puede-anular="<?php echo tiene_permiso('ventas.anular') ? '1' : '0'; ?>"
  data-puede-registrar="<?php echo tiene_permiso('ventas.registrar') ? '1' : '0'; ?>">
  <?php include '../Barra_Lateral.php'; ?>

    <div class="body flex-grow-1 px-3">
      <div class="container-fluid lp">

        <!-- filtros y tabla -->
        <section class="lp-panel">
          <div class="lp-barra">
            <div class="lp-chips" role="group" aria-label="Filtrar por estado">
              <button type="button" class="lp-chip activo" data-estado="">Todas</button>
              <button type="button" class="lp-chip" data-estado="pagado">Pagadas</button>
              <button type="button" class="lp-chip" data-estado="pendiente">Pendientes</button>
              <button type="button" class="lp-chip" data-estado="anulado">Anuladas</button>
            </div>

            <div class="lp-herr">
              <label class="lp-buscar">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="table-filter" type="search" placeholder="Buscar por código o cliente..." aria-label="Buscar ventas por código o cliente" autocomplete="off">
              </label>

              <?php if (tiene_permiso('ventas.registrar')): ?>
                <a href="Ventas.php" class="lp-btn"><i class="bi bi-cart-plus" aria-hidden="true"></i> Nueva venta</a>
              <?php endif; ?>

              <div class="lp-filtros">
                <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                  <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
                </button>
                <div id="panelFiltros" class="lp-menu" hidden>
                  <div class="lp-fechas mb-3">
                    <div>
                      <label class="lp-et" for="fecha-desde">Desde</label>
                      <input type="date" id="fecha-desde">
                    </div>
                    <div>
                      <label class="lp-et" for="fecha-hasta">Hasta</label>
                      <input type="date" id="fecha-hasta">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="lp-et" for="filtro-tipo">Tipo de venta</label>
                    <select id="filtro-tipo">
                      <option value="">Todas</option>
                      <option value="contado">Al contado</option>
                      <option value="credito">Al crédito</option>
                    </select>
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
            <table class="lp-tabla" id="tabla-ventas">
              <thead>
                <tr>
                  <th data-orden="id_venta">Código</th>
                  <th data-orden="fecha_hora">Fecha</th>
                  <th data-orden="cliente">Cliente</th>
                  <th data-orden="tipo_venta" class="lp-centro">Tipo</th>
                  <th data-orden="subtotal" class="lp-num">Subtotal</th>
                  <th data-orden="iva_debito" class="lp-num">IVA 13%</th>
                  <th data-orden="total" class="lp-num">Total</th>
                  <th data-orden="estado" class="lp-centro">Estado</th>
                  <th class="lp-centro lp-col-acc">Acciones</th>
                </tr>
              </thead>
              <tbody id="cuerpo-tabla"></tbody>
            </table>
          </div>

          <!-- Se muestra cuando no hay filas -->
          <div id="estado-vacio" class="lp-vacio" hidden>
            <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-receipt"></i><i class="bi bi-search"></i></div>
            <div class="lp-vacio-txt">
              <h2 id="vacio-titulo">No hay ventas para mostrar</h2>
              <p id="vacio-texto">Aún no se han registrado ventas en el sistema. Puedes registrar una nueva venta o ajustar tus filtros.</p>
              <?php if (tiene_permiso('ventas.registrar')): ?>
                <a id="vacio-accion" href="Ventas.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Venta</a>
              <?php else: ?>
                <button type="button" id="vacio-accion" class="lp-btn" hidden><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Venta</button>
              <?php endif; ?>
            </div>
          </div>

          <div class="lp-pie">
            <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de Registros: 0</strong></span>
            <div class="lp-pie-der">
              <label>Registros por página:
                <select id="tam-pagina">
                  <option>10</option><option>25</option><option>50</option><option>100</option>
                </select>
              </label>
              <div id="table-pagination" class="lp-pager"></div>
            </div>
          </div>
        </section>

      </div>
    </div>

    <footer class="footer px-4">
      <div>Comercial X &copy; 2026.</div>
    </footer>

    </div> <!-- cierra <div class="wrapper"> que se abrio en Barra_Lateral.php -->

    <!-- Detalle de la venta -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-labelledby="detalleTitulo" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="detalleTitulo">Detalle de la venta</h5>
            <button type="button" class="btn-close" data-cerrar aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <div class="vd-cab" id="detalle-cabecera"></div>
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Producto</th>
                    <th class="text-end">Precio unit.</th>
                    <th class="text-end">Cantidad</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                </thead>
                <tbody id="detalle-cuerpo"></tbody>
                <tfoot>
                  <tr><td colspan="3" class="text-end fw-semibold">Subtotal</td><td class="text-end" id="detalle-subtotal"></td></tr>
                  <tr><td colspan="3" class="text-end fw-semibold">IVA Débito Fiscal (13%)</td><td class="text-end" id="detalle-iva"></td></tr>
                  <tr><td colspan="3" class="text-end fw-bold fs-5">Total</td><td class="text-end fw-bold fs-5" id="detalle-total"></td></tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-primary" id="btnImprimirFactura" disabled>
              <i class="bi bi-printer me-1" aria-hidden="true"></i> Imprimir factura
            </button>
            <button type="button" class="btn btn-secondary" data-cerrar>Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="../vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="../vendors/simplebar/js/simplebar.min.js"></script>
    <script src="../js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>

  <script src="../js/JSLista-ventas.js?v=<?php echo filemtime(__DIR__ . '/../js/JSLista-ventas.js'); ?>"></script>
</body>
</html>
