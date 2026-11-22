<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('inventario.gestionar');
$titulo_pagina = 'Ajustes de inventario';
include_once __DIR__ . '/../config.php';
?>
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
    .ai { --ai-azul: #2f4fe0; --ai-radio: 1rem; }
    .ai-contenedor { max-width: 1050px; margin: 0 auto; }
    #panelFormularioAjuste[hidden] { display: none !important; }
    .ai-hero {
      display: flex; align-items: center; gap: 1rem; padding: 1.15rem 1.4rem; margin-bottom: 1rem;
      border-radius: var(--ai-radio); color: #fff; background: linear-gradient(110deg, #14257a, #2340c4 60%, #3b6bff);
    }
    .ai-hero-icono {
      display: grid; place-items: center; width: 3.3rem; height: 3.3rem; flex: none;
      border: 1px solid rgba(255,255,255,.45); border-radius: 50%; background: rgba(255,255,255,.16); font-size: 1.5rem;
    }
    .ai-hero h1 { margin: 0; font-size: 1.4rem; font-weight: 700; }
    .ai-hero p { margin: .2rem 0 0; opacity: .88; }
    .ai-panel {
      overflow: hidden; margin-bottom: 1.25rem; border: 1px solid var(--cui-border-color);
      border-radius: var(--ai-radio); background: var(--cui-body-bg); color: var(--cui-body-color);
      box-shadow: 0 .25rem .9rem rgba(30,50,120,.07);
    }
    .ai-panel-header { padding: .9rem 1.1rem; border-bottom: 1px solid var(--cui-border-color); font-weight: 650; }
    .ai-panel-body { padding: 1.1rem; }
    .ai-existencia { padding: .7rem .9rem; border-radius: .7rem; background: var(--cui-tertiary-bg); }
    .ai-existencia strong { color: var(--ai-azul); font-size: 1.1rem; }
    .ai-ayuda { color: var(--cui-secondary-color); font-size: .85rem; }
    .ai-tabla-wrap { overflow-x: auto; }
    .ai-tabla { width: 100%; border-collapse: collapse; }
    .ai-tabla th {
      padding: .75rem .9rem; text-align: left; white-space: nowrap; background: var(--cui-tertiary-bg);
      color: var(--cui-secondary-color); font-size: .76rem; text-transform: uppercase; letter-spacing: .04em;
    }
    .ai-tabla td { padding: .75rem .9rem; border-top: 1px solid var(--cui-border-color); vertical-align: middle; }
    .ai-pie {
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
      padding: .8rem 1rem; border-top: 1px solid var(--cui-border-color); background: var(--cui-tertiary-bg); font-size: .88rem;
    }
    .ai-pie-controles { display: flex; flex-wrap: wrap; align-items: center; gap: .8rem; }
    .ai-pie select { padding: .2rem .5rem; border: 1px solid var(--cui-border-color); border-radius: 2rem; background: var(--cui-body-bg); color: var(--cui-body-color); }
    .ai-pager { display: flex; gap: .3rem; }
    .ai-pager button { min-width: 2rem; height: 2rem; border: 1px solid var(--cui-border-color); border-radius: .55rem; background: var(--cui-body-bg); color: var(--cui-body-color); }
    .ai-pager button.activo { border-color: var(--ai-azul); background: var(--ai-azul); color: #fff; }
    .ai-pager button:disabled { opacity: .45; }
    .ai-vacio { padding: 1.5rem; color: var(--cui-secondary-color); text-align: center; }
    .ai-filtros { padding: .9rem 1rem; border-bottom: 1px solid var(--cui-border-color); }
    .ai-filtro-buscar { position: relative; }
    .ai-filtro-buscar i { position: absolute; z-index: 2; top: 50%; left: .8rem; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .ai-filtro-buscar input { padding-left: 2.2rem; }
    .ai-filtro-limpiar { height: 2.4rem; }
    .ai-tipo { display: inline-block; padding: .2rem .65rem; border-radius: 2rem; font-size: .78rem; font-weight: 650; }
    .ai-tipo.entrada { color: #12804b; background: rgba(31,184,112,.15); }
    .ai-tipo.salida { color: #c4363b; background: rgba(229,72,77,.13); }
    .ai-cantidad { font-weight: 700; }
    .ai-busqueda-producto { position: relative; }
    .ai-sugerencias {
      position: absolute; z-index: 30; top: calc(100% + .25rem); right: 0; left: 0; overflow: hidden;
      border: 1px solid var(--cui-border-color); border-radius: .65rem; background: var(--cui-body-bg);
      box-shadow: 0 .5rem 1.25rem rgba(0,0,0,.14);
    }
    .ai-sugerencias[hidden] { display: none; }
    .ai-sugerencia {
      display: block; width: 100%; padding: .65rem .8rem; border: 0; background: transparent;
      color: var(--cui-body-color); text-align: left;
    }
    .ai-sugerencia + .ai-sugerencia { border-top: 1px solid var(--cui-border-color); }
    .ai-sugerencia:hover, .ai-sugerencia.activa { background: var(--cui-tertiary-bg); outline: 0; }
    .ai-sugerencias-vacio { padding: .7rem .8rem; color: var(--cui-secondary-color); font-size: .9rem; }
    @media print { .sidebar, .header, .footer, .ai-hero, #formularioAjusteInventario, .ai-ayuda { display: none !important; } .ai-panel { box-shadow: none; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <main class="container-fluid ai ai-contenedor">
      <section class="ai-hero">
        <span class="ai-hero-icono"><i class="bi bi-sliders" aria-hidden="true"></i></span>
        <div>
          <h1>Ajustes de inventario</h1>
          <p>Registra entradas o salidas y conserva el movimiento en kardex.</p>
        </div>
        <button type="button" id="btnNuevoAjuste" class="btn btn-light ms-auto" disabled>
          <i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Nuevo ajuste
        </button>
      </section>

      <section class="ai-panel" id="panelFormularioAjuste" hidden>
        <div class="ai-panel-header"><i class="bi bi-pencil-square me-2 text-primary" aria-hidden="true"></i>Nuevo ajuste</div>
        <div class="ai-panel-body">
          <div id="mensajeAjuste" class="alert alert-info" role="status">
            La entrada aumenta las existencias y la salida las disminuye. El promedio de costo actual se conserva.
          </div>
          <form id="formularioAjusteInventario" novalidate>
            <div class="row g-3">
              <div class="col-md-6">
                <label for="buscarProductoAjuste" class="form-label">Producto en inventario *</label>
                <div class="ai-busqueda-producto" id="contenedorBusquedaProducto">
                  <input id="buscarProductoAjuste" class="form-control" type="text" required autocomplete="off"
                         placeholder="Escriba para buscar un producto..." role="combobox" aria-autocomplete="list"
                         aria-expanded="false" aria-controls="sugerenciasProductoAjuste">
                  <input id="productoAjuste" type="hidden">
                  <div class="ai-sugerencias" id="sugerenciasProductoAjuste" role="listbox" hidden></div>
                </div>
                <div class="form-text">Se muestran hasta 3 coincidencias. Use ↑ y ↓, luego Enter para elegir.</div>
              </div>
              <div class="col-md-3">
                <label for="tipoAjuste" class="form-label">Tipo de ajuste *</label>
                <select id="tipoAjuste" class="form-select" required>
                  <option value="entrada">Entrada — aumenta</option>
                  <option value="salida">Salida — disminuye</option>
                </select>
              </div>
              <div class="col-md-3">
                <label for="cantidadAjuste" class="form-label">Cantidad *</label>
                <input id="cantidadAjuste" class="form-control" type="number" min="1" max="4294967295" step="1" value="1" required>
              </div>
              <div class="col-md-8">
                <label for="motivoAjuste" class="form-label">Motivo *</label>
                <input id="motivoAjuste" class="form-control" type="text" maxlength="255" required autocomplete="off" placeholder="Ej. producto dañado, diferencia de conteo">
              </div>
              <div class="col-md-4">
                <div class="ai-existencia h-100 d-flex flex-column justify-content-center">
                  <span class="small text-body-secondary">Existencia actual</span>
                  <strong id="existenciaActual">—</strong>
                  <span class="small text-body-secondary">Después del ajuste: <b id="existenciaResultante">—</b></span>
                </div>
              </div>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
              <span class="ai-ayuda">El movimiento se registra con el usuario de la sesión y el costo promedio actual.</span>
              <div class="d-flex gap-2">
                <button type="button" id="btnCancelarAjuste" class="btn btn-outline-secondary">Cancelar</button>
                <button type="submit" id="btnRegistrarAjuste" class="btn btn-primary" disabled>
                  <i class="bi bi-check2-circle" aria-hidden="true"></i> Registrar ajuste
                </button>
              </div>
            </div>
          </form>
        </div>
      </section>

      <section class="ai-panel">
        <div class="ai-panel-header d-flex flex-wrap justify-content-between gap-2">
          <span><i class="bi bi-clock-history me-2 text-primary" aria-hidden="true"></i>Historial de ajustes</span>
          <span class="small text-body-secondary" id="totalAjustes">0 registros</span>
        </div>
        <div class="ai-filtros row g-2 align-items-end">
          <div class="col-12 col-lg-4">
            <label for="buscarHistorialAjustes" class="form-label small mb-1">Buscar</label>
            <div class="ai-filtro-buscar">
              <i class="bi bi-search" aria-hidden="true"></i>
              <input id="buscarHistorialAjustes" class="form-control form-control-sm" type="search"
                     placeholder="Producto, motivo o usuario..." autocomplete="off">
            </div>
          </div>
          <div class="col-6 col-lg-2">
            <label for="fechaDesdeAjustes" class="form-label small mb-1">Desde</label>
            <input id="fechaDesdeAjustes" class="form-control form-control-sm" type="date">
          </div>
          <div class="col-6 col-lg-2">
            <label for="fechaHastaAjustes" class="form-label small mb-1">Hasta</label>
            <input id="fechaHastaAjustes" class="form-control form-control-sm" type="date">
          </div>
          <div class="col-6 col-lg-1">
            <label for="tipoFiltroAjustes" class="form-label small mb-1">Tipo</label>
            <select id="tipoFiltroAjustes" class="form-select form-select-sm">
              <option value="">Todos</option>
              <option value="entrada">Entrada</option>
              <option value="salida">Salida</option>
            </select>
          </div>
          <div class="col-6 col-lg-2">
            <label for="usuarioFiltroAjustes" class="form-label small mb-1">Usuario</label>
            <select id="usuarioFiltroAjustes" class="form-select form-select-sm">
              <option value="">Todos</option>
            </select>
          </div>
          <div class="col-12 col-lg-1 d-grid">
            <button type="button" id="limpiarFiltrosAjustes" class="btn btn-sm btn-outline-secondary ai-filtro-limpiar" title="Limpiar filtros">
              <i class="bi bi-x-circle me-1" aria-hidden="true"></i><span class="d-lg-none">Limpiar</span>
            </button>
          </div>
        </div>
        <div class="ai-tabla-wrap" id="contenedorHistorial" hidden>
          <table class="ai-tabla">
            <thead>
              <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr>
            </thead>
            <tbody id="cuerpoHistorialAjustes"></tbody>
          </table>
        </div>
        <div class="ai-vacio" id="historialVacio">Cargando historial...</div>
        <div class="ai-pie">
          <span id="resumenPaginaAjustes">Mostrando 0 registros</span>
          <div class="ai-pie-controles">
            <label class="small">Registros por página:
              <select id="tamPaginaAjustes" aria-label="Registros por página">
                <option>10</option><option>25</option><option>50</option><option>100</option>
              </select>
            </label>
            <div class="ai-pager" id="paginadorAjustes"></div>
          </div>
        </div>
      </section>
    </main>
  </div>
  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSAjuste_Inventario.js?v=<?php echo filemtime(__DIR__ . '/../js/JSAjuste_Inventario.js'); ?>"></script>
</body>
</html>
