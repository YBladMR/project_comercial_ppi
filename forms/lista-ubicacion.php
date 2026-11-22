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
  <style>
    .lp { --lp-azul: #2f4fe0; --lp-azul-osc: #1b2f8f; --lp-radio: 1rem; }
    .lp-panel {
      background: var(--cui-body-bg); color: var(--cui-body-color);
      border: 1px solid var(--cui-border-color); border-radius: var(--lp-radio);
      box-shadow: 0 .25rem .9rem rgba(30, 50, 120, .07); overflow: hidden; margin-bottom: 1.5rem;
    }
    .lp-barra {
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
      gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid var(--cui-border-color);
    }
    .lp-herr { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-left: auto; }
    .lp-asignacion-filtros { display: inline-flex; flex-wrap: wrap; gap: .25rem; }
    .lp-asignacion-filtros button {
      border: 1px solid var(--cui-border-color); border-radius: 2rem; padding: .4rem .8rem;
      background: var(--cui-body-bg); color: var(--cui-body-color); font-size: .88rem;
    }
    .lp-asignacion-filtros button:hover { background: var(--cui-tertiary-bg); }
    .lp-asignacion-filtros button.activo { background: var(--lp-azul); border-color: var(--lp-azul); color: #fff; }
    .lp-buscar { position: relative; }
    .lp-buscar i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .lp-buscar input {
      width: 16rem; max-width: 100%; border-radius: 2rem; padding: .45rem 1rem .45rem 2.3rem;
      border: 1px solid var(--cui-border-color); background: var(--cui-body-bg); color: var(--cui-body-color);
    }
    .lp-buscar input:focus { outline: 2px solid rgba(47, 79, 224, .35); border-color: var(--lp-azul); }
    .lp-filtros { position: relative; }
    .lp-btn-filtros {
      background: var(--cui-body-bg); border: 1px solid var(--cui-border-color);
      border-radius: 2rem; padding: .45rem 1rem; color: var(--cui-body-color);
    }
    .lp-btn-filtros:hover { background: var(--cui-tertiary-bg); }
    .lp-menu {
      position: absolute; right: 0; top: calc(100% + .4rem); z-index: 20; min-width: 15rem;
      padding: .9rem; background: var(--cui-body-bg); border: 1px solid var(--cui-border-color);
      border-radius: .8rem; box-shadow: 0 .8rem 1.8rem rgba(0, 0, 0, .18);
    }
    .lp-menu[hidden], .lp-vacio[hidden], [hidden] { display: none !important; }
    .lp-tabla-wrap { overflow-x: auto; }
    .lp-tabla { width: 100%; border-collapse: collapse; }
    .lp-tabla th {
      text-align: left; padding: .8rem 1.1rem; font-size: .78rem; text-transform: uppercase;
      letter-spacing: .04em; color: var(--cui-secondary-color); background: var(--cui-tertiary-bg);
      white-space: nowrap; user-select: none;
    }
    .lp-tabla th[data-orden] { cursor: pointer; }
    .lp-tabla th[data-orden]:hover { color: var(--cui-body-color); }
    .lp-tabla th .bi { font-size: .7rem; margin-left: .25rem; opacity: .8; }
    .lp-tabla td { padding: .8rem 1.1rem; border-top: 1px solid var(--cui-border-color); vertical-align: middle; }
    .lp-tabla tbody tr:hover { background: var(--cui-tertiary-bg); }
    .lp-centro { text-align: center !important; }
    .lp-prod { display: flex; align-items: center; gap: .75rem; }
    .lp-prod-ico {
      width: 2.5rem; height: 2.5rem; flex: none; border-radius: .7rem; display: grid;
      place-items: center; background: rgba(138, 79, 224, .13); color: #8a4fe0; font-size: 1.15rem;
    }
    .lp-prod-nom { font-weight: 600; }
    .lp-prod-sub { color: var(--cui-secondary-color); font-size: .8rem; }
    .lp-estado-asignacion {
      display: inline-flex; align-items: center; gap: .35rem; border-radius: 2rem;
      padding: .25rem .65rem; font-size: .8rem; font-weight: 600; white-space: nowrap;
    }
    .lp-estado-asignacion.asignada { color: #176b3a; background: rgba(46, 184, 92, .14); }
    .lp-estado-asignacion.libre { color: #596579; background: var(--cui-tertiary-bg); }
    .lp-ib:disabled { opacity: .4; cursor: not-allowed; }
    .lp-ib:disabled:hover { background: var(--cui-body-bg); border-color: var(--cui-border-color); color: var(--cui-body-color); }
    .lp-acc { display: inline-flex; gap: .4rem; }
    .lp-ib {
      width: 2.1rem; height: 2.1rem; display: grid; place-items: center; border-radius: 50%;
      text-decoration: none; border: 1px solid var(--cui-border-color); background: var(--cui-body-bg);
      color: var(--cui-body-color); transition: all .15s;
    }
    .lp-ib:hover { background: var(--lp-azul); border-color: var(--lp-azul); color: #fff; }
    .lp-ib.eliminar:hover { background: #e5484d; border-color: #e5484d; color: #fff; }
    .lp-ib[aria-disabled="true"] { opacity: .4; cursor: not-allowed; }
    .lp-ib[aria-disabled="true"]:hover { background: var(--cui-body-bg); border-color: var(--cui-border-color); color: var(--cui-body-color); }
    .lp-vacio { display: flex; flex-wrap: wrap; align-items: center; justify-content: center; gap: 2.5rem; padding: 3rem 1.5rem; }
    .lp-vacio-img { position: relative; width: 11rem; height: 9rem; display: grid; place-items: center; }
    .lp-vacio-img::before {
      content: ""; position: absolute; inset: 0; border-radius: 50%;
      background: radial-gradient(circle, rgba(47, 79, 224, .18), transparent 70%);
    }
    .lp-vacio-img .bi-geo-alt { position: relative; font-size: 5.5rem; color: var(--lp-azul); }
    .lp-vacio-img .bi-search {
      position: absolute; right: .6rem; bottom: .6rem; width: 2.8rem; height: 2.8rem;
      border-radius: 50%; display: grid; place-items: center; background: var(--lp-azul);
      color: #fff; font-size: 1.2rem; box-shadow: 0 .4rem .9rem rgba(47, 79, 224, .4);
    }
    .lp-vacio-txt { max-width: 22rem; }
    .lp-vacio-txt h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: .5rem; }
    .lp-vacio-txt p { color: var(--cui-secondary-color); margin-bottom: 1rem; }
    .lp-btn {
      display: inline-flex; align-items: center; gap: .4rem; border: 0; border-radius: 2rem;
      padding: .6rem 1.4rem; background: var(--lp-azul); color: #fff; font-weight: 600;
      text-decoration: none; box-shadow: 0 .4rem 1rem rgba(47, 79, 224, .35); cursor: pointer;
    }
    .lp-btn:hover { color: #fff; filter: brightness(1.1); }
    .lp-pie {
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
      padding: .8rem 1.1rem; background: var(--cui-tertiary-bg); border-top: 1px solid var(--cui-border-color); font-size: .9rem;
    }
    .lp-pie-der { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .lp-pie select {
      border: 1px solid var(--cui-border-color); border-radius: 2rem; background: var(--cui-body-bg);
      color: var(--cui-body-color); padding: .2rem .5rem;
    }
    .lp-pager { display: flex; gap: .3rem; }
    .lp-pager button {
      min-width: 2rem; height: 2rem; border-radius: .6rem; border: 1px solid var(--cui-border-color);
      background: var(--cui-body-bg); color: var(--cui-body-color);
    }
    .lp-pager button:hover:not(:disabled) { background: var(--cui-secondary-bg); }
    .lp-pager button.activo { background: var(--lp-azul); border-color: var(--lp-azul); color: #fff; }
    .lp-pager button:disabled { opacity: .4; cursor: default; }
    @media print {
      .sidebar, .header, .footer, .lp-barra, .lp-pie, .lp-col-acc { display: none !important; }
      .lp-panel { border: 0; box-shadow: none; }
    }
  </style>
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
