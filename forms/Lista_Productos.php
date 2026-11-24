<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('productos.consultar'); $titulo_pagina = "Lista de Productos";
include_once(__DIR__ . '/../config.php');?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lista de Productos - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

  <!-- Diseño propio de la lista (usa los colores de CoreUI, claro y oscuro) -->

  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/Lista_Productos.css?v=<?php echo filemtime(__DIR__ . '/../css/Lista_Productos.css'); ?>">
  </head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

    <div class="body flex-grow-1 px-3">
      <div class="container-fluid lp">

        <!-- Banner -->
        <section class="lp-hero">
          <div class="lp-hero-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
          <div class="lp-hero-texto">
            <h1>Lista de Productos</h1>
            <p>Consulta y gestiona el inventario de productos de tu negocio</p>
          </div>
          <a href="Productos.php" class="lp-btn-hero"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Nuevo producto</a>
          <i class="bi bi-boxes lp-hero-deco" aria-hidden="true"></i>
        </section>

        <!-- Resumen -->
        <section class="lp-resumen" aria-label="Resumen">
          <button type="button" class="lp-stat lp-stat-azul" data-atajo="todos">
            <span class="lp-stat-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
            <span><span class="lp-stat-num" id="stat-total">0</span><br><span class="lp-stat-etq">Total de Productos</span></span>
          </button>
          <button type="button" class="lp-stat lp-stat-verde" data-atajo="activo">
            <span class="lp-stat-icono"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
            <span><span class="lp-stat-num" id="stat-activos">0</span><br><span class="lp-stat-etq">Productos Activos</span></span>
          </button>
          <button type="button" class="lp-stat lp-stat-naranja" data-atajo="bajo">
            <span class="lp-stat-icono"><i class="bi bi-exclamation-lg" aria-hidden="true"></i></span>
            <span><span class="lp-stat-num" id="stat-bajo">0</span><br><span class="lp-stat-etq">Productos en Stock Bajo</span></span>
          </button>
          <div class="lp-stat lp-stat-morado">
            <span class="lp-stat-icono"><i class="bi bi-bar-chart-fill" aria-hidden="true"></i></span>
            <span><span class="lp-stat-num" id="stat-categorias">0</span><br><span class="lp-stat-etq">Categorías</span></span>
          </div>
        </section>

        <!-- filtros y tabla -->
        <section class="lp-panel">
          <div class="lp-barra">
            <div class="lp-chips" role="group" aria-label="Filtrar por estado">
              <button type="button" class="lp-chip activo" data-estado="">Todos</button>
              <button type="button" class="lp-chip" data-estado="activo">Activos</button>
              <button type="button" class="lp-chip" data-estado="inactivo">Inactivos</button>
              <select id="filtro-categoria" class="lp-chip" aria-label="Filtrar por categoría">
                <option value="">Por categoría</option>
              </select>
            </div>

            <div class="lp-herr">
              <label class="lp-buscar">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input id="table-filter" type="search" placeholder="Buscar productos..." aria-label="Buscar productos" autocomplete="off">
              </label>

              <div class="lp-filtros">
                <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                  <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
                </button>
                <div id="panelFiltros" class="lp-menu" hidden>
                  <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="solo-bajo">
                    <label class="form-check-label" for="solo-bajo">Solo con stock bajo</label>
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
            <table class="lp-tabla" id="tabla-productos">
              <thead>
                <tr>
                  <th data-orden="nombre">Producto</th>
                  <th data-orden="categoria">Categoría</th>
                  <th>Dimensiones (cm)</th>
                  <th data-orden="existencia" class="lp-der">Existencia</th>
                  <th data-orden="precio_venta" class="lp-der">Precio</th>
                  <th data-orden="estado" class="lp-centro">Estado</th>
                  <th class="lp-centro lp-col-acc">Acciones</th>
                </tr>
              </thead>
              <tbody id="cuerpo-tabla"></tbody>
            </table>
          </div>

          <!-- Se muestra cuando no hay filas -->
          <div id="estado-vacio" class="lp-vacio" hidden>
            <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-box-seam"></i><i class="bi bi-search"></i></div>
            <div class="lp-vacio-txt">
              <h2 id="vacio-titulo">No hay productos para mostrar</h2>
              <p id="vacio-texto">Aún no se han registrado productos en el sistema. Puedes agregar un nuevo producto o ajustar tus filtros.</p>
              <a id="vacio-accion" href="Productos.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Producto</a>
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

    <div class="modal fade" id="modalMargenProducto" tabindex="-1" aria-labelledby="tituloModalMargenProducto" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
          <form id="formularioMargenProducto" novalidate>
            <div class="modal-header">
              <h5 class="modal-title" id="tituloModalMargenProducto">Asignar margen de ganancia</h5>
              <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <p id="detalleMargenProducto" class="small text-body-secondary"></p>
              <label for="margenGananciaProducto" class="form-label">Margen de ganancia (%)</label>
              <input type="number" class="form-control" id="margenGananciaProducto" min="0.01" max="100" step="0.01" required autocomplete="off">
              <div class="form-text">Si hay costo promedio registrado, el precio se recalculará usando ese costo.</div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancelar</button>
              <button type="submit" id="btnGuardarMargen" class="btn btn-primary">Guardar margen</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <footer class="footer px-4">
      <div>Comercial X &copy; 2026.</div>
    </footer>

    </div> <!-- cierra <div class="wrapper"> que se abrio en Barra_Lateral.php -->

    <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
    <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>

  <script src="<?php echo BASE_URL; ?>js/JSLista_Productos.js?v=<?php echo filemtime('../js/JSLista_Productos.js'); ?>"></script>
</body>
</html>
