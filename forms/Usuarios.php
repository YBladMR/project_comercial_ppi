<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('usuarios.gestionar');
$titulo_pagina = 'Administración de usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Casa Aurelia</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
</head>
<body>
  <?php include __DIR__ . '/../Barra_Lateral.php'; ?>
  <div class="body flex-grow-1 px-3">
    <div class="container-lg">
      <div class="card mb-4">
        <div class="card-header fw-semibold">Crear cuenta de empleado</div>
        <div class="card-body">
          <p class="text-body-secondary">El administrador asigna el nombre de usuario. La contraseña temporal se genera al crear la cuenta y el empleado deberá cambiarla al ingresar.</p>
          <form id="formCrearUsuario" class="row g-3">
            <div class="col-md-3">
              <label class="form-label" for="nombreUsuario">Nombre completo</label>
              <input class="form-control" id="nombreUsuario" name="nombre" maxlength="100" autocomplete="off" required>
            </div>
            <div class="col-md-2">
              <label class="form-label" for="nombreLogin">Nombre de usuario</label>
              <input class="form-control" id="nombreLogin" name="usuario" maxlength="50" autocomplete="off" required>
              <div class="form-text">No uses el DUI como nombre de usuario.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label" for="correoUsuario">Correo electrónico (opcional)</label>
              <input class="form-control" id="correoUsuario" name="correo_electronico" type="email" maxlength="254" autocomplete="off">
            </div>
            <div class="col-md-3">
              <label class="form-label" for="rolUsuario">Rol</label>
              <select class="form-select" id="rolUsuario" name="id_rol" required><option value="">Cargando roles...</option></select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button class="btn btn-primary w-100" type="submit" title="Crear usuario" aria-label="Crear usuario"><i class="bi bi-person-plus"></i></button>
            </div>
          </form>
          <div id="credencialTemporal" class="alert alert-warning mt-4 mb-0" hidden aria-live="polite">
            <strong id="tituloCredencial">Contraseña temporal</strong>
            <p class="mb-2">Entrégala directamente al empleado. Solo se muestra ahora y no se puede recuperar desde el sistema.</p>
            <div class="d-flex flex-wrap align-items-center gap-2">
              <code id="valorCredencial" class="fs-6 user-select-all"></code>
              <button id="copiarCredencial" type="button" class="btn btn-sm btn-outline-dark"><i class="bi bi-clipboard"></i> Copiar</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold">Cuentas registradas</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead><tr><th>Nombre</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Creación</th><th>Acciones</th></tr></thead>
              <tbody id="tablaUsuarios"><tr><td colspan="7" class="text-center text-body-secondary">Cargando cuentas...</td></tr></tbody>
            </table>
          </div>
          <p class="small text-body-secondary mb-0">La cuenta administradora raíz está protegida. Las cuentas se desactivan en lugar de eliminarse para conservar el historial de operaciones.</p>
        </div>
      </div>
    </div>
  </div>
  <footer class="footer px-4"><div>Casa Aurelia &copy; 2026.</div></footer>
  </div>
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSUsuarios.js?v=<?php echo filemtime(__DIR__ . '/../js/JSUsuarios.js'); ?>"></script>
</body>
</html>
