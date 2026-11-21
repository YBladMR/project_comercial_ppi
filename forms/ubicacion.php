<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('ubicaciones.gestionar');
$titulo_pagina = "Registrar Ubicación";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $titulo_pagina; ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <style>
    .ubicacion-form-container {
      max-width: 760px;
      margin: 0 auto;
    }

    .ubicacion-sugerencias {
      position: absolute;
      top: calc(100% + .25rem);
      left: 0;
      right: 0;
      z-index: 1080;
      overflow: hidden;
      border: 1px solid var(--cui-border-color);
      border-radius: .6rem;
      background: var(--cui-body-bg);
      box-shadow: 0 .5rem 1.25rem rgba(0, 0, 0, .14);
    }

    .ubicacion-sugerencias[hidden] { display: none; }

    .ubicacion-sugerencia {
      display: block;
      width: 100%;
      padding: .6rem .8rem;
      border: 0;
      background: transparent;
      color: var(--cui-body-color);
      text-align: left;
    }

    .ubicacion-sugerencia + .ubicacion-sugerencia { border-top: 1px solid var(--cui-border-color); }
    .ubicacion-sugerencia:hover, .ubicacion-sugerencia:focus { background: var(--cui-tertiary-bg); outline: 0; }
  </style>
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-lg ubicacion-form-container">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center gap-3 bg-body-tertiary">
          <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
            <i class="bi bi-geo-alt fs-5" aria-hidden="true"></i>
          </span>
          <div>
            <div class="fw-bold" id="tituloFormulario"><?php echo $titulo_pagina; ?></div>
            <small class="text-body-secondary">Indica dónde se guardará el producto.</small>
          </div>
        </div>

        <div class="card-body">
          <form id="formUbicacion" autocomplete="off" novalidate>
            <div id="avisoUbicacionAsignada" class="alert alert-warning" role="alert" hidden></div>
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="bodega" class="form-label">Bodega *</label>
                <div class="position-relative ubicacion-control">
                  <input type="text" class="form-control" id="bodega" name="bodega" maxlength="30" required placeholder="Bodega principal" autocomplete="off" aria-autocomplete="list" aria-controls="sugerencias-bodega" aria-expanded="false">
                  <div class="ubicacion-sugerencias" id="sugerencias-bodega" role="listbox" hidden></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label for="pasillo" class="form-label">Pasillo *</label>
                <div class="position-relative ubicacion-control">
                  <input type="text" class="form-control" id="pasillo" name="pasillo" maxlength="20" required placeholder="Pasillo A" autocomplete="off" aria-autocomplete="list" aria-controls="sugerencias-pasillo" aria-expanded="false">
                  <div class="ubicacion-sugerencias" id="sugerencias-pasillo" role="listbox" hidden></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label for="estante" class="form-label">Estante *</label>
                <div class="position-relative ubicacion-control">
                  <input type="text" class="form-control" id="estante" name="estante" maxlength="20" required placeholder="Estante 03" autocomplete="off" aria-autocomplete="list" aria-controls="sugerencias-estante" aria-expanded="false">
                  <div class="ubicacion-sugerencias" id="sugerencias-estante" role="listbox" hidden></div>
                </div>
              </div>
              <div class="col-12 col-md-6">
                <label for="nivel" class="form-label">Nivel *</label>
                <div class="position-relative ubicacion-control">
                  <input type="text" class="form-control" id="nivel" name="nivel" maxlength="10" required placeholder="Nivel 2" autocomplete="off" aria-autocomplete="list" aria-controls="sugerencias-nivel" aria-expanded="false">
                  <div class="ubicacion-sugerencias" id="sugerencias-nivel" role="listbox" hidden></div>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
              <button type="button" id="btnGuardar" class="btn btn-primary">
                <i class="bi bi-save" aria-hidden="true"></i>
                <span id="textoGuardar">Guardar ubicación</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer px-4">
    <div>Comercial X &copy; 2026.</div>
  </footer>

  </div><!-- Cierra el wrapper de Barra_Lateral.php -->

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSubicacion.js?v=<?php echo filemtime('../js/JSubicacion.js'); ?>"></script>
</body>
</html>
