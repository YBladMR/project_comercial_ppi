<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Clientes - Comercial X</title>
  
  <!-- CSS AdminLTE 4 & Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/css/adminlte.min.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">

  <div class="app-wrapper">

    <!-- 1. BARRA DE NAVEGACIÓN SUPERIOR (NAVBAR) -->
    <nav class="app-header navbar navbar-expand bg-body">
      <div class="container-fluid">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
              <i class="bi bi-list"></i>
            </a>
          </li>
          <li class="nav-item d-none d-md-block">
            <a href="../index.html" class="nav-link">
              <i class="bi bi-grid-1x2 me-1" aria-hidden="true"></i> Live preview
            </a>
          </li>
          <li class="nav-item d-none d-md-block">
            <a href="../docs/introduction.html" class="nav-link">
              <i class="bi bi-book me-1" aria-hidden="true"></i> Documentation
            </a>
          </li>
        </ul>

        <form class="navbar-search d-none d-xl-block ms-3" role="search">
          <div class="navbar-search-field">
            <input type="search" class="form-control" placeholder="Search…" autocomplete="off" />
            <button class="navbar-search-submit" type="submit">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
              <span class="d-none d-xl-inline">Alexander Pierce</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
              <li class="user-header text-bg-primary">
                <p>Alexander Pierce - Web Developer</p>
              </li>
              <li class="user-footer">
                <a href="#" class="btn btn-outline-danger float-end">Sign out</a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </nav>

    <!-- 2. BARRA LATERAL (SIDEBAR) -->
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
      <div class="sidebar-brand">
        <a href="#" class="brand-link">
          <span class="brand-text fw-light">Comercial X</span>
        </a>
      </div>
      <div class="sidebar-wrapper">
        <nav class="mt-2">
          <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">
            <li class="nav-item">
              <a href="#" class="nav-link active">
                <i class="nav-icon bi bi-people-fill"></i>
                <p>Clientes</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-box-seam"></i>
                <p>Productos</p>
              </a>
            </li>
            <li class="nav-item">
              <a href="#" class="nav-link">
                <i class="nav-icon bi bi-cart-fill"></i>
                <p>Ventas</p>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </aside>

    <!-- 3. CONTENIDO PRINCIPAL (MAIN CONTENT) -->
    <main class="app-main">
      
      <!-- Encabezado del contenido (Page Header) -->
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <h3 class="mb-0">Control de Clientes</h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end">
                <li class="breadcrumb-item"><a href="#">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page">Clientes</li>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <!-- Cuerpo del contenido (Page Body) -->
      <div class="app-content">
        <div class="container-fluid">
          <div class="row">
            
            <!-- FORMULARIO DE REGISTRO / EDICIÓN -->
            <div class="col-lg-4">
              <div class="card card-primary card-outline mb-4">
                <div class="card-header">
                  <h5 class="card-title m-0" id="formTitle">
                    <i class="bi bi-person-plus-fill me-2"></i>Registrar Cliente
                  </h5>
                </div>
                <form id="formCliente">
                  <div class="card-body">
                    <input type="hidden" id="id_cliente">
                    
                    <div class="mb-3">
                      <label class="form-label">Nombre</label>
                      <input type="text" id="nombre" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Apellido</label>
                      <input type="text" id="apellido" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">DUI (00000000-0)</label>
                      <input type="text" id="dui" class="form-control" placeholder="00000000-0" required>
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Teléfono</label>
                      <input type="text" id="telefono" class="form-control" placeholder="0000-0000">
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Correo Electrónico</label>
                      <input type="email" id="correo_electronico" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                      <label class="form-label">Dirección de Entrega</label>
                      <textarea id="direccion_entrega" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3 d-none" id="divEstado">
                      <label class="form-label">Estado</label>
                      <select id="estado" class="form-select">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                      </select>
                    </div>
                  </div>

                  <div class="card-footer d-flex gap-2">
                    <button type="submit" id="btnGuardar" class="btn btn-primary w-100">Guardar</button>
                    <button type="button" id="btnCancelar" class="btn btn-secondary d-none" onclick="limpiarFormulario()">Cancelar</button>
                  </div>
                </form>
              </div>
            </div>

            <!-- TABLA DE DATOS -->
            <div class="col-lg-8">
              <div class="card card-outline card-secondary">
                <div class="card-header">
                  <h5 class="card-title m-0"><i class="bi bi-people-fill me-2"></i>Listado de Clientes</h5>
                </div>
                <div class="card-body">
                  
                  <div class="row mb-3">
                    <div class="col-md-6 ms-auto">
                      <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="inputBuscar" class="form-control" placeholder="Buscar por Nombre o DUI..." onkeyup="cargarClientes()">
                      </div>
                    </div>
                  </div>

                  <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle">
                      <thead class="table-dark">
                        <tr>
                          <th>ID</th>
                          <th>Cliente</th>
                          <th>DUI</th>
                          <th>Teléfono</th>
                          <th>Correo</th>
                          <th>Estado</th>
                          <th class="text-center">Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="tablaClientes">
                        <!-- Carga dinámica vía app.js -->
                      </tbody>
                    </table>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <!-- 4. PIE DE PÁGINA (FOOTER) -->
    <footer class="app-footer">
      <div class="float-end d-none d-sm-inline">Comercial X</div>
      <strong>Copyright &copy; 2026.</strong> Todos los derechos reservados.
    </footer>

  </div>

  <!-- JS CDN AdminLTE 4 & Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-beta2/dist/js/adminlte.min.js"></script>
  <script src="app.js"></script>
</body>
</html>