<?php
$titulo_pagina = "Lista de Clientes";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Casa Aurelia</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/lista-cliente.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-cliente.css'); ?>">
  </head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-fluid lp">
      <section class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 d-print-none" aria-labelledby="titulo-clientes">
        <div class="lp-hero-texto">
          <h1 id="titulo-clientes">Directorio de clientes</h1>
          <p>Consulta y administra la información de tus clientes.</p>
        </div>
        <a href="cliente.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Registrar cliente</a>
      </section>

      <section class="lp-panel" aria-label="Lista de clientes">
        <div class="lp-barra">
          <div class="lp-chips" role="group" aria-label="Filtrar por estado">
            <button type="button" class="lp-chip activo" data-estado="">Todos</button>
            <button type="button" class="lp-chip" data-estado="activo">Activos</button>
            <button type="button" class="lp-chip" data-estado="inactivo">Inactivos</button>
          </div>
          <div class="lp-herr">
            <label class="lp-buscar">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input type="search" id="buscarCliente" placeholder="Buscar clientes..." aria-label="Buscar clientes" autocomplete="off">
            </label>
            <div class="lp-filtros">
              <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                <i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtros<i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
              </button>
              <div id="panelFiltros" class="lp-menu" hidden>
                <div class="d-flex flex-column gap-2">
                  <button type="button" id="limpiar-filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>Limpiar filtros</button>
                  <button type="button" id="print-table" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1" aria-hidden="true"></i>Imprimir lista</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lp-tabla-wrap" id="tabla-wrap">
          <table class="lp-tabla" id="tabla-clientes">
            <thead>
              <tr>
                <th data-orden="nombre_completo">Cliente</th>
                <th data-orden="dui">DUI</th>
                <th>Contacto</th>
                <th data-orden="direccion_entrega">Dirección de entrega</th>
                <th data-orden="estado" class="lp-centro">Estado</th>
                <th class="lp-centro lp-col-acc">Acciones</th>
              </tr>
            </thead>
            <tbody id="tablaClientes"></tbody>
          </table>
        </div>

        <div id="estado-vacio" class="lp-vacio" hidden>
          <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-people"></i><i class="bi bi-search"></i></div>
          <div class="lp-vacio-txt">
            <h2 id="vacio-titulo">No hay clientes para mostrar</h2>
            <p id="vacio-texto">Aún no se han registrado clientes. Puedes agregar uno o cambiar los filtros.</p>
            <a id="vacio-agregar" href="cliente.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i>Registrar cliente</a>
            <button type="button" id="vacio-limpiar" class="lp-btn" hidden><i class="bi bi-x-circle" aria-hidden="true"></i>Limpiar filtros</button>
          </div>
        </div>

        <div class="lp-pie">
          <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de clientes: 0</strong></span>
          <div class="lp-pie-der">
            <label>Registros por página:
              <select id="tam-pagina" aria-label="Registros por página"><option>10</option><option>25</option><option>50</option><option>100</option></select>
            </label>
            <div id="table-pagination" class="lp-pager"></div>
          </div>
        </div>
      </section>
    </div>
  </div>

  <footer class="footer px-4"><div>Casa Aurelia &copy; 2026.</div></footer>
  </div><!-- Cierra el wrapper de Barra_Lateral.php -->

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSlista-cliente.js?v=<?php echo filemtime(__DIR__ . '/../js/JSlista-cliente.js'); ?>"></script>
</body>
</html>
