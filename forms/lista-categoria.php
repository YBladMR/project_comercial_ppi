<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('categorias.consultar'); $titulo_pagina = "Lista de Categorías";
include_once(__DIR__ . '/../config.php'); ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lista de Categorías - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

  <!-- Diseño propio de la lista (mismo estilo que Lista de Productos) -->

  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/lista-categoria.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-categoria.css'); ?>">
  </head>

<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-fluid lp">



      <!-- filtros y tabla -->
      <section class="lp-panel">
        <div class="lp-barra">
          <div class="lp-chips" role="group" aria-label="Filtrar por estado">
            <button type="button" class="lp-chip activo" data-estado="">Todas</button>
            <button type="button" class="lp-chip" data-estado="activo">Activas</button>
            <button type="button" class="lp-chip" data-estado="inactivo">Inactivas</button>
          </div>

          <div class="lp-herr">
            <label class="lp-buscar">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input id="table-filter" type="search" placeholder="Buscar categorías..." aria-label="Buscar categorías" autocomplete="off">
            </label>

            <div class="lp-filtros">
              <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
              </button>
              <div id="panelFiltros" class="lp-menu" hidden>
                <div class="form-check mb-3">
                  <input class="form-check-input" type="checkbox" id="solo-vacias" autocomplete="off">
                  <label class="form-check-label" for="solo-vacias">Solo sin productos</label>
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
          <table class="lp-tabla" id="tabla-categorias">
            <thead>
              <tr>
                <th data-orden="nombre">Categoría</th>
                <th data-orden="nombre_padre">Pertenece a</th>
                <th data-orden="descripcion">Descripción</th>
                <th data-orden="total_productos" class="lp-centro">Productos</th>
                <th data-orden="estado" class="lp-centro">Estado</th>
                <th class="lp-centro lp-col-acc">Acciones</th>
              </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
          </table>
        </div>

        <!-- Se muestra cuando no hay filas -->
        <div id="estado-vacio" class="lp-vacio" hidden>
          <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-tags"></i><i class="bi bi-search"></i></div>
          <div class="lp-vacio-txt">
            <h2 id="vacio-titulo">No hay categorías para mostrar</h2>
            <p id="vacio-texto">Aún no se han registrado categorías en el sistema. Puedes agregar una nueva categoría o ajustar tus filtros.</p>
            <a id="vacio-accion" href="Categorias.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar Categoría</a>
          </div>
        </div>

        <div class="lp-pie">
          <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de Registros: 0</strong></span>
          <div class="lp-pie-der">
            <label>Registros por página:
              <select id="tam-pagina">
                <option>10</option>
                <option>25</option>
                <option>50</option>
                <option>100</option>
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

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>

  <script src="<?php echo BASE_URL; ?>js/JSLista_Categorias.js?v=<?php echo filemtime('../js/JSLista_Categorias.js'); ?>"></script>
</body>

</html>
