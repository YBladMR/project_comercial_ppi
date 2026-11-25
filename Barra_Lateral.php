<?php
require_once __DIR__ . '/autenticacion/seguridad.php';
requerir_autenticacion();
?>
<script>
(() => {
  const tokenCsrf = <?php echo json_encode(obtener_token_csrf(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const fetchOriginal = window.fetch.bind(window);

  window.fetch = (entrada, opciones = {}) => {
    const metodoEntrada = typeof Request !== "undefined" && entrada instanceof Request ? entrada.method : "GET";
    const metodo = String(opciones.method || metodoEntrada).toUpperCase();

    if (["POST", "PUT", "PATCH", "DELETE"].includes(metodo)) {
      const encabezados = new Headers(opciones.headers || (typeof Request !== "undefined" && entrada instanceof Request ? entrada.headers : undefined));
      encabezados.set("X-CSRF-Token", tokenCsrf);
      opciones = { ...opciones, headers: encabezados };
    }

    return fetchOriginal(entrada, opciones);
  };
})();
</script>
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">
  <style>
  #sidebar {
    --cui-sidebar-bg: #0e274c;
    --cui-sidebar-brand-bg: #0e274c;
  }

  /* Logo Casa Aurelia */
  #sidebar .sidebar-logo-link {
    display: block;
    background: #fff;
    border-radius: .6rem;
    padding: .25rem .5rem;
    margin: .25rem 0;
  }

  #sidebar .sidebar-logo {
    display: block;
    width: 100%;
    max-width: 190px;
    height: auto;
    margin-inline: auto;
  }

  #sidebar .inventario-group.show {
    margin-inline: .35rem;
    padding-block: .25rem;
    border: 1px solid rgba(255, 255, 255, .12);
    border-radius: .75rem;
    background: rgba(255, 255, 255, .045);
  }

  #sidebar .inventario-submenu {
    margin: .25rem .5rem .35rem;
    padding-inline-start: .4rem;
    border-inline-start: 2px solid rgba(255, 255, 255, .2);
  }

  #sidebar .inventario-submenu > .nav-item > .nav-link,
  #sidebar .inventario-submenu > .nav-group > .nav-link {
    border-radius: .5rem;
    color: rgba(255, 255, 255, .88);
  }

  #sidebar .inventario-submenu > .nav-group > .nav-group-items {
    margin-inline-start: .55rem;
  }

  #sidebar .ubicaciones-submenu {
    margin-inline-start: .75rem;
    border-inline-start: 1px solid rgba(255, 255, 255, .16);
  }

  #sidebar .ubicaciones-submenu .nav-link {
    padding-block: .4rem;
    font-size: .8rem;
    color: rgba(255, 255, 255, .78);
  }

  #sidebar .ubicaciones-submenu .nav-link:hover,
  #sidebar .ubicaciones-submenu .nav-link.active {
    color: #fff;
  }

  #sidebar .ubicaciones-submenu .nav-icon { font-size: .8rem; }
</style>
  <div class="sidebar-header border-bottom">
    <div class="sidebar-brand me-auto">
      <a href="<?php echo BASE_URL; ?>index.php" class="sidebar-logo-link">
        <img src="<?php echo BASE_URL; ?>assets/img/logo.jpeg"
             alt="Casa Aurelia Muebles"
             class="sidebar-brand-full sidebar-logo">
      </a>
      <?php /* Opcional: icono pequeño para cuando el sidebar está colapsado 
      <img src="<?php echo BASE_URL; ?>assets/img/logo-icono.png"
           alt="Casa Aurelia"
           class="sidebar-brand-narrow" width="32" height="32">*/
      ?>
    </div>
    <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>
    <li class="nav-item">
      <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M425.706 142.294A240 240 0 0 0 16 312v88h144v-32H48v-56c0-114.691 93.309-208 208-208s208 93.309 208 208v56H352v32h144v-88a238.43 238.43 0 0 0-70.294-169.706" />
          <path fill="var(--ci-primary-color, currentcolor)" d="M80 264h32v32H80zm160-136h32v32h-32zm-104 40h32v32h-32zm264 96h32v32h-32zm-102.778 71.1 69.2-144.173-28.85-13.848-69.183 144.135a64.141 64.141 0 1 0 28.833 13.886M256 416a32 32 0 1 1 32-32 32.036 32.036 0 0 1-32 32" />
        </svg>
        Inicio
      </a>
    </li>
    <li class="nav-title">General</li>

    <!-- Desplegables -->

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M256 32L32 144v224l224 112 224-112V144L256 32zm0 38.6L418.3 144 256 217.4 93.7 144 256 70.6zM64 179.4l176 79.2v184.8L64 357.6V179.4zm224 264V258.6l176-79.2v178.2L288 443.4z" />
        </svg>
        Productos
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Productos.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Lista_Productos.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar</a></li>
        <li class="nav-group">
          <a class="nav-link nav-group-toggle" href="#">
            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M256 32L32 144v224l224 112 224-112V144L256 32zm0 38.6L418.3 144 256 217.4 93.7 144 256 70.6zM64 179.4l176 79.2v184.8L64 357.6V179.4zm224 264V258.6l176-79.2v178.2L288 443.4z" />
            </svg>
            Categorías
          </a>
          <ul class="nav-group-items compact">
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/categoria.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/lista-categoria.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar</a></li>
            </ul>
        </li>
      </ul>
    </li>

    <li class="nav-group inventario-group">
      <a class="nav-link nav-group-toggle" href="#">
        <i class="nav-icon bi bi-box-seam" aria-hidden="true"></i>
        Inventario
      </a>
      <ul class="nav-group-items compact inventario-submenu">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/lista-inventario.php"><i class="nav-icon bi bi-boxes" aria-hidden="true"></i> Existencias</a></li>
        <li class="nav-group">
          <a class="nav-link nav-group-toggle" href="#">
            <i class="nav-icon bi bi-geo-alt" aria-hidden="true"></i>
            Ubicaciones
          </a>
          <ul class="nav-group-items compact ubicaciones-submenu">
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/ubicacion.php"><i class="nav-icon bi bi-plus-circle" aria-hidden="true"></i> Agregar</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/lista-ubicacion.php"><i class="nav-icon bi bi-list-ul" aria-hidden="true"></i> Consultar</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/ajustes-inventario.php"><i class="nav-icon bi bi-arrow-down-up" aria-hidden="true"></i> Ajustar Inventario</a></li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M256 64C150 64 64 150 64 256s86 192 192 192 192-86 192-192S362 64 256 64zm0 352c-88.2 0-160-71.8-160-160S167.8 96 256 96s160 71.8 160 160-71.8 160-160 160zm16-160V160h-32v112h96v-32h-64z" />
        </svg>
        Kárdex
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/kardex.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Movimientos</a></li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M480 288h-32v-96l-64-64H288V96H32v288h48a64 64 0 0 0 128 0h96a64 64 0 0 0 128 0h48v-96zM144 416a32 32 0 1 1 32-32 32 32 0 0 1-32 32zm224 0a32 32 0 1 1 32-32 32 32 0 0 1-32 32zm80-160h-64v-64h42.7l21.3 21.3z" />
        </svg>
        Proveedores
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/proveedor.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/lista-proveedor.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar</a></li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M160 416a32 32 0 1 1-32-32 32 32 0 0 1 32 32zm224 0a32 32 0 1 1-32-32 32 32 0 0 1 32 32zm48-288H128l-16-64H32v32h48l48 192h256l48-160zM176 320l-24-96h240l-28.8 96z" />
        </svg>
        Compras
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Compra.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Lista_Compras.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar/Anular</a></li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M496 160L352 16a32 32 0 0 0-22.6-9.4H192a32 32 0 0 0-32 32v128H32a32 32 0 0 0-32 32v288a32 32 0 0 0 32 32h288a32 32 0 0 0 32-32V352h136a32 32 0 0 0 32-32V182.6A32 32 0 0 0 496 160zM288 480H32V192h256zm192-160H320V192l112-112h48z" />
        </svg>
        Ventas
      </a>
      <ul class="nav-group-items compact">
        <?php if (tiene_permiso('ventas.registrar')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Ventas.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
        <?php endif; ?>
        <?php if (tiene_permiso('ventas.consultar')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Lista-ventas.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> <?php echo tiene_permiso('ventas.anular') ? 'Consultar/Anular' : 'Consultar'; ?></a></li>
        <?php endif; ?>
        <li class="nav-group">
          <a class="nav-link nav-group-toggle" href="#">
            <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M256 256a96 96 0 1 0 0-192 96 96 0 0 0 0 192zm-128 32a80 80 0 1 0 0-160 80 80 0 0 0 0 160zm256 0a80 80 0 1 0 0-160 80 80 0 0 0 0 160zM256 288c-88 0-160 72-160 160v32h320v-32c0-88-72-160-160-160zM32 448v32h64v-32c0-42 12-81 33-113-54 8-97 55-97 113zm448 0c0-58-43-105-97-113 21 32 33 71 33 113v32h64v-32z"/>
            </svg>
            Clientes
          </a>
          <ul class="nav-group-items compact ubicaciones-submenu">
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Cliente.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/lista-cliente.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar</a></li>
          </ul>
        </li>
      </ul>
    </li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M448 96H64a32 32 0 0 0-32 32v256a32 32 0 0 0 32 32h384a32 32 0 0 0 32-32V128a32 32 0 0 0-32-32zM64 128h384v48H64zm384 256H64V240h384z" />
        </svg>
        Créditos
      </a>
      <ul class="nav-group-items compact">
        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar</a></li>
        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Consultar</a></li>
        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Plan de Pagos</a></li>
        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Registrar Pago</a></li>
        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Estado de Cuenta</a></li>
      </ul>
    </li>

    <?php if (tiene_permiso('usuarios.gestionar') || tiene_permiso('roles.gestionar')): ?>
    <li class="nav-divider"></li>
    <li class="nav-title">Configuración</li>

    <li class="nav-group">
      <a class="nav-link nav-group-toggle" href="#">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M487.4 315.7l-42.8-24.7a188.7 188.7 0 0 0 0-70l42.8-24.7a16 16 0 0 0 5.9-21.8l-48-83.1a16 16 0 0 0-21.8-5.9l-42.8 24.7a189 189 0 0 0-60.6-35v-49.4a16 16 0 0 0-16-16h-96a16 16 0 0 0-16 16v49.4a189 189 0 0 0-60.6 35l-42.8-24.7a16 16 0 0 0-21.8 5.9l-48 83.1a16 16 0 0 0 5.9 21.8l42.8 24.7a188.7 188.7 0 0 0 0 70l-42.8 24.7a16 16 0 0 0-5.9 21.8l48 83.1a16 16 0 0 0 21.8 5.9l42.8-24.7a189 189 0 0 0 60.6 35v49.4a16 16 0 0 0 16 16h96a16 16 0 0 0 16-16v-49.4a189 189 0 0 0 60.6-35l42.8 24.7a16 16 0 0 0 21.8-5.9l48-83.1a16 16 0 0 0-5.9-21.8zM256 336a80 80 0 1 1 80-80 80.1 80.1 0 0 1-80 80z" />
        </svg>
        Administración
      </a>
      <ul class="nav-group-items compact">
        <?php if (tiene_permiso('usuarios.gestionar')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Usuarios.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Usuarios</a></li>
        <?php endif; ?>
        <?php if (tiene_permiso('roles.gestionar')): ?>
        <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>forms/Roles.php"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Roles y permisos</a></li>
        <?php endif; ?>
      </ul>
    </li>
    <?php endif; ?>
  </ul>

  <div class="sidebar-footer border-top d-none d-md-flex">
    <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
  </div>
</div>

<div class="wrapper d-flex flex-column min-vh-100 bg-body">
  <header class="header header-sticky p-0 mb-4">
    <div class="container-fluid border-bottom px-4">
      <button class="header-toggler" type="button" onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()" style="margin-inline-start: -14px">
        <svg class="icon icon-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color, currentcolor)" d="M80 96h352v32H80zm0 144h352v32H80zm0 144h352v32H80z" class="ci-primary" />
        </svg>
      </button>
      <ul class="header-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#">
            <svg class="icon icon-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="m450.27 348.569-43.67-80.624V184c0-83.813-68.187-152-152-152s-152 68.187-152 152v83.945l-43.672 80.623A24 24 0 0 0 80.031 384h86.935a89 89 0 0 0-.367 8 88 88 0 0 0 176 0c0-2.7-.129-5.364-.367-8h86.935a24 24 0 0 0 21.1-35.431ZM310.6 392a56 56 0 1 1-111.419-8h110.837a56 56 0 0 1 .582 8M93.462 352l41.138-75.945V184a120 120 0 0 1 240 0v92.055L415.736 352Z" class="ci-primary" />
            </svg>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <svg class="icon icon-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M136 24H16v120h120Zm-32 88H48V56h56Zm32 88H16v120h120Zm-32 88H48v-56h56Zm32 88H16v120h120Zm-32 88H48v-56h56Zm72-440.002h320v32H176zm0 88h256v32H176zm0 88h320v32H176zm0 88h256v32H176zm0 176h256v32H176zm0-88h320v32H176z" class="ci-primary" />
            </svg>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">
            <svg class="icon icon-lg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M274.6 25.623a32.01 32.01 0 0 0-37.2 0L16 183.766V496h480V183.766ZM464 402.693 339.97 322.96 464 226.492ZM256 51.662 454.429 193.4 311.434 304.615 256 268.979l-55.434 35.636L57.571 193.4ZM48 226.492l124.03 96.468L48 402.693ZM464 464H48v-23.265l208-133.714 208 133.714Z" class="ci-primary" />
            </svg>
          </a>
        </li>
      </ul>
      <ul class="header-nav">
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <li class="nav-item dropdown">
          <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" type="button" aria-expanded="false" data-coreui-toggle="dropdown">
            <svg class="icon icon-lg theme-icon-active" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
              <path fill="var(--ci-primary-color, currentcolor)" d="M256 16C123.452 16 16 123.452 16 256s107.452 240 240 240 240-107.452 240-240S388.548 16 256 16m-22 446.849a208.35 208.35 0 0 1-169.667-125.9c-.364-.859-.706-1.724-1.057-2.587L234 429.939Zm0-69.582L50.889 290.76A210 210 0 0 1 48 256q0-9.912.922-19.67L234 339.939Zm0-90L54.819 202.96a206 206 0 0 1 9.514-27.913Q67.1 168.5 70.3 162.191L234 253.934Zm0-86.015L86.914 134.819a209.4 209.4 0 0 1 22.008-25.9q3.72-3.72 7.6-7.228L234 166.027Zm0-87.708-89.648-49.093A206.95 206.95 0 0 1 234 49.151ZM464 256a207.775 207.775 0 0 1-198 207.761V48.239A207.79 207.79 0 0 1 464 256" class="ci-primary" />
            </svg>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" style="--cui-dropdown-min-width: 8rem">
            <li><button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="light">Light</button></li>
            <li><button class="dropdown-item d-flex align-items-center" type="button" data-coreui-theme-value="dark">Dark</button></li>
            <li><button class="dropdown-item d-flex align-items-center active" type="button" data-coreui-theme-value="auto">Auto</button></li>
          </ul>
        </li>
        <li class="nav-item py-1">
          <div class="vr h-100 mx-2 text-body text-opacity-75"></div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link py-0 pe-0" data-coreui-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
            <div class="avatar avatar-md">
              <img class="avatar-img" src="<?php echo BASE_URL; ?>assets/img/avatars/8.jpg" alt="<?php echo htmlspecialchars(obtener_nombre_usuario(), ENT_QUOTES, 'UTF-8'); ?>">
            </div>
          </a>
          <div class="dropdown-menu dropdown-menu-end pt-0">
            <div class="dropdown-header bg-body-tertiary text-body-secondary fw-semibold rounded-top mb-2">
              <?php echo htmlspecialchars(obtener_nombre_usuario(), ENT_QUOTES, 'UTF-8'); ?><br>
              <small><?php echo htmlspecialchars(obtener_nombre_rol(), ENT_QUOTES, 'UTF-8'); ?></small>
            </div>
            <a class="dropdown-item" href="<?php echo BASE_URL; ?>autenticacion/cambiar_contrasena.php">Cambiar contraseña</a>
            <form method="post" action="<?php echo BASE_URL; ?>autenticacion/Cerrar_Sesion.php">
              <input type="hidden" name="token_csrf" value="<?php echo htmlspecialchars(obtener_token_csrf(), ENT_QUOTES, 'UTF-8'); ?>">
              <button class="dropdown-item" type="submit">Cerrar sesión</button>
            </form>
          </div>
        </li>
      </ul>
    </div>
    <div class="container-fluid px-4">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb my-0">
          <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>index.php">Home</a></li>
          <li class="breadcrumb-item active"><span><?php echo $titulo_pagina ?? 'Dashboard'; ?></span></li>
        </ol>
      </nav>
    </div>


  </header>