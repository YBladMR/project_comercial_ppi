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
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/lista-inventario.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-inventario.css'); ?>">
</head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <main class="container-fluid li">
      <section class="li-hero">
        <span class="li-hero-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
        <div>
          <h1>Existencias de inventario</h1>
          <p>Consulta existencias, costo promedio y ubicación de cada producto.</p>
        </div>
      </section>

      <section class="li-panel" aria-label="Existencias registradas">
        <div class="li-barra">
          <strong><i class="bi bi-list-ul me-2 text-primary" aria-hidden="true"></i>Productos en inventario</strong>
          <label class="li-buscar">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="buscar-inventario" type="search" placeholder="Buscar producto, categoría, ubicación o existencia..." autocomplete="off" aria-label="Buscar en el inventario">
          </label>
        </div>

        <div class="li-tabla-wrap" id="tabla-wrap" hidden>
          <table class="li-tabla">
            <thead>
              <tr><th>Producto</th><th class="text-center">Existencia</th><th class="text-end">Costo promedio</th><th class="text-center">Acciones</th></tr>
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

      <div class="modal fade" id="modalUbicacionInventario" tabindex="-1" aria-labelledby="tituloModalUbicacionInventario" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <form id="formUbicacionInventario" novalidate>
              <div class="modal-header">
                <h2 class="modal-title fs-5" id="tituloModalUbicacionInventario">Asignar ubicación</h2>
                <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
              </div>
              <div class="modal-body">
                <p class="mb-3">Producto: <strong id="nombreProductoUbicacion"></strong></p>
                <label for="selectUbicacionInventario" class="form-label">Ubicación</label>
                <select id="selectUbicacionInventario" class="form-select">
                  <option value="">Sin ubicación asignada</option>
                </select>
                <div class="form-text">Selecciona “Sin ubicación asignada” para quitar la ubicación actual.</div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnGuardarUbicacionInventario" class="btn btn-primary">
                  <i class="bi bi-geo-alt" aria-hidden="true"></i> Guardar ubicación
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
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
