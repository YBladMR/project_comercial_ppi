<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('ubicaciones.consultar'); $titulo_pagina = "Lista de Ubicaciones";
include_once(__DIR__ . '/../config.php'); ?>
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
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/lista-ubicacion.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-ubicacion.css'); ?>">
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-fluid lp">
      <section class="lp-panel" aria-label="Lista de ubicaciones">
        <div class="lp-barra">
          <strong><i class="bi bi-geo-alt me-2 text-primary" aria-hidden="true"></i>Ubicaciones registradas</strong>
          <div class="lp-herr">
            <label class="lp-buscar">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input id="table-filter" type="search" placeholder="Buscar ubicaciones..." aria-label="Buscar ubicaciones" autocomplete="off">
            </label>
            <div class="lp-asignacion-filtros" role="group" aria-label="Filtrar ubicaciones por asignación">
              <button type="button" data-asignacion="" class="activo" aria-pressed="true">Todas</button>
              <button type="button" data-asignacion="asignada" aria-pressed="false">Asignadas</button>
              <button type="button" data-asignacion="libre" aria-pressed="false">Sin asignar</button>
            </div>
            <div class="lp-filtros">
              <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
              </button>
              <div id="panelFiltros" class="lp-menu" hidden>
                <div class="d-flex flex-column gap-2">
                  <button type="button" id="limpiar-filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Limpiar filtros</button>
                  <button type="button" id="print-table" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1" aria-hidden="true"></i> Imprimir lista</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lp-tabla-wrap" id="tabla-wrap">
          <table class="lp-tabla" id="tabla-ubicaciones">
            <thead>
              <tr>
                <th data-orden="bodega">Bodega</th>
                <th data-orden="pasillo">Pasillo</th>
                <th data-orden="estante">Estante</th>
                <th data-orden="nivel">Nivel</th>
                <th>Asignación</th>
                <th class="lp-centro lp-col-acc">Acciones</th>
              </tr>
            </thead>
            <tbody id="cuerpo-tabla"></tbody>
          </table>
        </div>

        <div id="estado-vacio" class="lp-vacio" hidden>
          <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-geo-alt"></i><i class="bi bi-search"></i></div>
          <div class="lp-vacio-txt">
            <h2 id="vacio-titulo">No hay ubicaciones para mostrar</h2>
            <p id="vacio-texto">Aún no se han registrado ubicaciones. Puedes agregar una nueva ubicación o cambiar la búsqueda.</p>
            <a id="vacio-agregar" href="ubicacion.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar ubicación</a>
            <button type="button" id="vacio-limpiar" class="lp-btn" hidden><i class="bi bi-x-circle" aria-hidden="true"></i> Limpiar búsqueda</button>
          </div>
        </div>

        <div class="lp-pie">
          <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de ubicaciones: 0</strong></span>
          <div class="lp-pie-der">
            <label>Registros por página:
              <select id="tam-pagina" aria-label="Registros por página">
                <option>10</option><option>25</option><option>50</option><option>100</option>
              </select>
            </label>
            <div id="table-pagination" class="lp-pager"></div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div><!-- Cierra el wrapper de Barra_Lateral.php -->

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSLista_Ubicacion.js?v=<?php echo filemtime(__DIR__ . '/../js/JSLista_Ubicacion.js'); ?>"></script>
</body>
</html>
