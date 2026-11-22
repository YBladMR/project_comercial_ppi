<?php
include_once(__DIR__ . '/../config.php');
$id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: '';
$titulo_pagina = $id_cliente ? 'Modificar cliente' : 'Registrar cliente';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <style>
    .cliente-formulario { max-width: 900px; margin: 0 auto; }
  </style>
</head>
<body>
  <?php include '../Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <main class="container-lg cliente-formulario">
      <section class="card mb-4">
        <div class="card-header d-flex align-items-center gap-3 bg-body-tertiary">
          <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
            <i class="bi bi-person-vcard fs-5" aria-hidden="true"></i>
          </span>
          <div>
            <div class="fw-bold" id="tituloFormulario"><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?></div>
            <small class="text-body-secondary">Completa la información de contacto del cliente.</small>
          </div>
        </div>

        <div class="card-body">
          <form id="formCliente" autocomplete="off" novalidate>
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?php echo htmlspecialchars((string) $id_cliente, ENT_QUOTES, 'UTF-8'); ?>">

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label for="nombre" class="form-label">Nombre *</label>
                <input type="text" class="form-control" id="nombre" name="nombre" maxlength="50" required autocomplete="off">
              </div>
              <div class="col-12 col-md-6">
                <label for="apellido" class="form-label">Apellido *</label>
                <input type="text" class="form-control" id="apellido" name="apellido" maxlength="50" required autocomplete="off">
              </div>
              <div class="col-12 col-md-6">
                <label for="dui" class="form-label">DUI *</label>
                <input type="text" class="form-control" id="dui" name="dui" maxlength="10" pattern="\d{8}-\d" placeholder="00000000-0" required autocomplete="off" aria-describedby="mensajeDUI">
                <small id="mensajeDUI" class="text-danger" aria-live="polite"></small>
              </div>
              <div class="col-12 col-md-6">
                <label for="telefono" class="form-label">Teléfono *</label>
                <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="9" pattern="\d{4}-\d{4}" placeholder="0000-0000" required autocomplete="off">
              </div>
              <div class="col-12">
                <label for="direccion_entrega" class="form-label">Dirección de entrega *</label>
                <textarea class="form-control" id="direccion_entrega" name="direccion_entrega" rows="2" maxlength="255" required></textarea>
              </div>
              <div class="col-12 col-md-8">
                <label for="correo_electronico" class="form-label">Correo electrónico *</label>
                <input type="email" class="form-control" id="correo_electronico" name="correo_electronico" maxlength="100" required autocomplete="email">
              </div>
              <div class="col-12 col-md-4">
                <label for="estado" class="form-label">Estado *</label>
                <select class="form-select" id="estado" name="estado" required>
                  <option value="activo">Activo</option>
                  <option value="inactivo">Inactivo</option>
                </select>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
              <button type="submit" id="btnGuardar" class="btn btn-primary">
                <i class="bi bi-save" aria-hidden="true"></i> <span id="textoGuardar">Guardar cliente</span>
              </button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div><!-- Cierra el wrapper de Barra_Lateral.php -->

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSCliente.js?v=<?php echo filemtime(__DIR__ . '/../js/JSCliente.js'); ?>"></script>
</body>
</html>
