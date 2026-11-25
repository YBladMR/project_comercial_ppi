<?php
require_once __DIR__ . '/autenticacion/seguridad.php';
requerir_permiso('panel.ver'); 
$titulo_pagina = "Panel Principal"; 
$contrasenaActualizada = ($_GET['contrasena'] ?? '') === 'actualizada';
include_once(__DIR__ . '/config.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $titulo_pagina; ?> - Comercial X</title>
  
  <!-- Estilos CoreUI y Simplebar locales -->
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  
  <!-- Configuración y selector de temas nativos de CoreUI -->
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

  <!-- Iconos (Bootstrap Icons) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>

<body>

  <!-- Barra lateral (Incluye la etiqueta <body> y el inicio del .wrapper internamente según tu estructura) -->
  <?php include __DIR__ . '/Barra_Lateral.php'; ?>

  <!-- Contenido Principal del Dashboard -->
  <div class="body flex-grow-1 px-3">
    <div class="container-lg">
      
      <!-- Fila de bienvenida o resumen -->
      <div class="row">
        <div class="col-12">
          <?php if ($contrasenaActualizada): ?>
            <div class="alert alert-success" role="status">Tu contraseña se actualizó correctamente.</div>
          <?php endif; ?>
          <div class="card mb-4">
            <div class="card-header fw-bold">
              Bienvenido al Sistema - Comercial X
            </div>
            <div class="card-body">
              <p class="text-medium-emphasis">Selecciona una opción del menú lateral para comenzar a gestionar el inventario, ventas, compras o ubicaciones.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Pie de página -->
  <footer class="footer px-4">
    <div>Comercial X &copy; 2026.</div>
  </footer>

  </div> <!-- Cierra el wrapper abierto en Barra_Lateral.php -->

  <!-- Scripts JavaScript CoreUI -->
  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
</body>

</html>
