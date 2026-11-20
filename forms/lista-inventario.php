<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('inventario.consultar');
$titulo_pagina = 'Existencias de inventario';
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
    .li { --li-azul: #2f4fe0; --li-radio: 1rem; }
    .li-hero {
      display: flex; align-items: center; gap: 1rem; padding: 1.25rem 1.5rem; margin-bottom: 1rem;
      color: #fff; border-radius: var(--li-radio);
      background: linear-gradient(110deg, #14257a, #2340c4 60%, #3b6bff);
    }
    .li-hero-icono {
      display: grid; place-items: center; width: 3.5rem; height: 3.5rem; flex: none;
      border: 1px solid rgba(255,255,255,.45); border-radius: 50%; background: rgba(255,255,255,.16);
      font-size: 1.6rem;
    }
    .li-hero h1 { margin: 0; font-size: 1.45rem; font-weight: 700; }
    .li-hero p { margin: .2rem 0 0; opacity: .85; }
    .li-panel {
      overflow: hidden; margin-bottom: 1.5rem; border: 1px solid var(--cui-border-color);
      border-radius: var(--li-radio); background: var(--cui-body-bg); color: var(--cui-body-color);
      box-shadow: 0 .25rem .9rem rgba(30,50,120,.07);
    }
    .li-barra, .li-pie {
      display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .75rem;
      padding: .9rem 1.1rem;
    }
    .li-barra { border-bottom: 1px solid var(--cui-border-color); }
    .li-pie { border-top: 1px solid var(--cui-border-color); background: var(--cui-tertiary-bg); }
    .li-buscar { position: relative; }
    .li-buscar i { position: absolute; top: 50%; left: .8rem; transform: translateY(-50%); color: var(--cui-secondary-color); }
    .li-buscar input {
      width: 17rem; max-width: 100%; padding: .45rem 1rem .45rem 2.25rem;
      border: 1px solid var(--cui-border-color); border-radius: 2rem;
      background: var(--cui-body-bg); color: var(--cui-body-color);
    }
    .li-tabla-wrap { overflow-x: auto; }
    .li-tabla { width: 100%; border-collapse: collapse; }
    .li-tabla th {
      padding: .8rem 1.1rem; text-align: left; white-space: nowrap; background: var(--cui-tertiary-bg);
      color: var(--cui-secondary-color); font-size: .78rem; text-transform: uppercase; letter-spacing: .04em;
    }
    .li-tabla td { padding: .85rem 1.1rem; border-top: 1px solid var(--cui-border-color); vertical-align: middle; }
    .li-tabla tbody tr:hover { background: var(--cui-tertiary-bg); }
    .li-producto { display: flex; align-items: center; gap: .75rem; }
    .li-icono {
      display: grid; place-items: center; width: 2.4rem; height: 2.4rem; flex: none;
      border-radius: .7rem; background: rgba(47,79,224,.12); color: var(--li-azul); font-size: 1.1rem;
    }
    .li-secundario { color: var(--cui-secondary-color); font-size: .8rem; }
    .li-numero { color: var(--li-azul); font-size: 1rem; font-weight: 700; text-align: center; }
    .li-vacio { padding: 2.5rem 1rem; text-align: center; }
    .li-vacio i { display: block; margin-bottom: .5rem; color: var(--li-azul); font-size: 2.5rem; }
    .li-pie select { padding: .2rem .5rem; border: 1px solid var(--cui-border-color); border-radius: 2rem; background: var(--cui-body-bg); color: var(--cui-body-color); }
    .li-pager { display: flex; gap: .3rem; }
    .li-pager button { min-width: 2rem; height: 2rem; border: 1px solid var(--cui-border-color); border-radius: .55rem; background: var(--cui-body-bg); color: var(--cui-body-color); }
    .li-pager button.activo { border-color: var(--li-azul); background: var(--li-azul); color: #fff; }
    .li-pager button:disabled { opacity: .45; }
    @media print { .sidebar, .header, .footer, .li-hero, .li-barra, .li-pie { display: none !important; } .li-panel { border: 0; box-shadow: none; } }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <main class="container-fluid li">
      <section class="li-hero">
        <span class="li-hero-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
        <div>
          <h1>Existencias de inventario</h1>
          <p>Consulta las cantidades registradas para cada producto.</p>
        </div>
      </section>

      <section class="li-panel" aria-label="Existencias registradas">
        <div class="li-barra">
          <strong><i class="bi bi-list-ul me-2 text-primary" aria-hidden="true"></i>Productos en inventario</strong>
          <label class="li-buscar">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="buscar-inventario" type="search" placeholder="Buscar producto o ubicación..." autocomplete="off" aria-label="Buscar producto o ubicación">
          </label>
        </div>

        <div class="li-tabla-wrap" id="tabla-wrap" hidden>
          <table class="li-tabla">
            <thead>
              <tr><th>Producto</th><th>Ubicación</th><th class="text-center">Existencia</th></tr>
            </thead>
            <tbody id="cuerpo-inventario"></tbody>
          </table>
        </div>
        <div class="li-vacio" id="estado-inventario">
          <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
          <strong id="estado-titulo">Cargando existencias...</strong>
          <div class="li-secundario" id="estado-detalle">Un momento, por favor.</div>
        </div>

        <div class="li-pie">
          <strong id="total-registros">Total de productos: 0</strong>
          <div class="d-flex align-items-center gap-3">
            <label class="small">Filas por página:
              <select id="tam-pagina" aria-label="Filas por página"><option>10</option><option>25</option><option>50</option></select>
            </label>
            <div class="li-pager" id="paginador"></div>
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
  <script src="<?php echo BASE_URL; ?>js/JSLista_Inventario.js?v=<?php echo filemtime(__DIR__ . '/../js/JSLista_Inventario.js'); ?>"></script>
</body>
</html>
