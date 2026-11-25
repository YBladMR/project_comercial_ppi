<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('categorias.gestionar'); $titulo_pagina = "Registrar Categoría";
include_once(__DIR__ . '/../config.php');?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categoría - Casa Aurelia</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <!-- Iconos -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">

  <style>
    .categoria-form-container {
      max-width: 760px;
      margin: 0 auto;
    }
  </style>

</head>

<body>

  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <div class="container-lg categoria-form-container">
      <div class="card mb-4">
        <div class="card-header fw-bold" id="tituloFormulario">
          Registrar Nueva Categoría
        </div>

        <div class="card-body">
          <form id="formCategoria" autocomplete="off" novalidate>

            <!-- Nombre y categoria padre -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="80" autocomplete="off" required placeholder="Línea Blanca">
              </div>
              <div class="col-md-6">
                <label for="id_padre" class="form-label">Pertenece a</label>
                <select class="form-select" id="id_padre" name="id_padre">
                  <option value="">Ninguna (categoría principal)</option>
                </select>
                <div class="form-text">Opcional. Selecciona una categoría principal para registrar una subcategoría.</div>
              </div>
            </div>

            <!-- Fila 2: Descripción -->
            <div class="row mb-3">
              <div class="col-md-12">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="2" maxlength="255" autocomplete="off" placeholder="Descripción breve de la categoría..."></textarea>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
              <button type="button" id="btnGuardar" class="btn btn-primary">
                <i class="bi bi-save"></i> <span id="textoGuardar">Guardar Categoría</span>
              </button>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer px-4">
    <div>Casa Aurelia &copy; 2026.</div>
  </footer>

  </div> <!-- Cierra el wrapper de la Barra_Lateral.php -->

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime('../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JScategoria.js?v=<?php echo filemtime('../js/JScategoria.js'); ?>"></script>
</body>

</html> 
