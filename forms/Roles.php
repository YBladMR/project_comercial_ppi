<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('roles.gestionar');
$titulo_pagina = 'Administración de roles';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Comercial X</title>
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
        <div class="card-header fw-semibold" id="tituloFormularioRol">Crear rol</div>
        <div class="card-body">
          <form id="formRol">
            <input type="hidden" id="idRol" value="0" autocomplete="off">
            <div class="row g-3">
              <div class="col-md-5"><label class="form-label" for="nombreRol">Nombre</label><input class="form-control" id="nombreRol" maxlength="50" autocomplete="off" required></div>
              <div class="col-md-7"><label class="form-label" for="descripcionRol">Descripción</label><input class="form-control" id="descripcionRol" maxlength="150" autocomplete="off"></div>
            </div>
            <fieldset class="mt-4">
              <legend class="fs-6">Permisos del rol</legend>
              <p class="small text-body-secondary">Los permisos de administración de usuarios y roles pertenecen exclusivamente al administrador raíz.</p>
              <div id="listaPermisos" class="row g-2"><div class="text-body-secondary">Cargando permisos...</div></div>
            </fieldset>
            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" id="cancelarEdicionRol" class="btn btn-outline-secondary" hidden>Cancelar edición</button>
              <button type="submit" id="guardarRol" class="btn btn-primary"><i class="bi bi-shield-check"></i> Guardar rol</button>
            </div>
          </form>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header fw-semibold">Roles y accesos</div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead><tr><th>Rol</th><th>Descripción</th><th>Usuarios</th><th>Permisos</th><th>Acciones</th></tr></thead>
              <tbody id="tablaRoles"><tr><td colspan="5" class="text-center text-body-secondary">Cargando roles...</td></tr></tbody>
            </table>
          </div>
          <p class="small text-body-secondary mb-0">El rol Admin/Administrador es raíz y está protegido. Un rol asignado a usuarios no se puede eliminar.</p>
        </div>
      </div>
    </div>
  </div>
  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
  <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
  <script src="<?php echo BASE_URL; ?>js/JSRoles.js?v=<?php echo filemtime(__DIR__ . '/../js/JSRoles.js'); ?>"></script>
</body>
</html>
