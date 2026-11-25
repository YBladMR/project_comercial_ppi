<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('proveedores.consultar');
$titulo_pagina = "Lista de Proveedores";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Comercial X</title>

    <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <script src="<?php echo BASE_URL; ?>js/config.js"></script>
    <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">


  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/lista-proveedor.css?v=<?php echo filemtime(__DIR__ . '/../css/lista-proveedor.css'); ?>">
  </head>

<body>
    <?php include __DIR__ . '/../Barra_Lateral.php'; ?>

    <div class="body flex-grow-1 px-3">
        <div class="container-fluid lp">
            <section class="lp-hero" aria-labelledby="titulo-proveedores">
                <span class="lp-hero-icono" aria-hidden="true"><i class="bi bi-truck"></i></span>
                <div class="lp-hero-texto">
                    <h1 id="titulo-proveedores">Directorio de Proveedores</h1>
                    <p>Consulta, organiza y administra tus contactos comerciales.</p>
                </div>
                <a href="proveedor.php" class="lp-btn-hero"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i> Registrar proveedor</a>
                <i class="bi bi-buildings lp-hero-deco" aria-hidden="true"></i>
            </section>

            <section class="lp-resumen" aria-label="Resumen de proveedores">
                <button type="button" class="lp-stat lp-stat-azul" data-atajo="todos">
                    <span class="lp-stat-icono"><i class="bi bi-people-fill" aria-hidden="true"></i></span>
                    <span><span class="lp-stat-num d-block" id="stat-total">0</span><span class="lp-stat-etq">Todos los proveedores</span></span>
                </button>
                <button type="button" class="lp-stat lp-stat-verde" data-atajo="activo">
                    <span class="lp-stat-icono"><i class="bi bi-person-check-fill" aria-hidden="true"></i></span>
                    <span><span class="lp-stat-num d-block" id="stat-activas">0</span><span class="lp-stat-etq">Proveedores activos</span></span>
                </button>
                <button type="button" class="lp-stat lp-stat-gris" data-atajo="inactivo">
                    <span class="lp-stat-icono"><i class="bi bi-person-dash-fill" aria-hidden="true"></i></span>
                    <span><span class="lp-stat-num d-block" id="stat-inactivas">0</span><span class="lp-stat-etq">Proveedores inactivos</span></span>
                </button>
            </section>

            <section class="lp-panel" aria-label="Listado de proveedores">
                <div class="lp-barra">
                    <div class="lp-chips" role="group" aria-label="Filtrar por estado">
                        <button type="button" class="lp-chip activo" data-estado="">Todos</button>
                        <button type="button" class="lp-chip" data-estado="activo">Activos</button>
                        <button type="button" class="lp-chip" data-estado="inactivo">Inactivos</button>
                    </div>

                    <div class="lp-herr">
                        <label class="lp-buscar">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input id="table-filter" type="search" placeholder="Buscar proveedor, DUI, NIT..." aria-label="Buscar proveedores" autocomplete="off">
                        </label>

                        <div class="lp-filtros">
                            <button type="button" id="btnFiltros" class="lp-btn-filtros" aria-expanded="false" aria-controls="panelFiltros">
                                <i class="bi bi-funnel me-1" aria-hidden="true"></i> Filtros <i class="bi bi-chevron-down ms-1" aria-hidden="true"></i>
                            </button>
                            <div id="panelFiltros" class="lp-menu" hidden>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="solo-juridicos" autocomplete="off">
                                    <label class="form-check-label" for="solo-juridicos">Solo personas jurídicas</label>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <button type="button" id="limpiar-filtros" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-circle me-1" aria-hidden="true"></i> Limpiar filtros</button>
                                    <button type="button" id="print-table" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1" aria-hidden="true"></i> Imprimir lista</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lp-tabla-wrap" id="tabla-wrap">
                    <table class="lp-tabla" id="tabla-proveedores">
                        <thead>
                            <tr>
                                <th data-orden="nombre_razon_social">Proveedor</th>
                                <th data-orden="tipo_persona">Tipo</th>
                                <th data-orden="fecha_registro">Fecha de registro</th>
                                <th data-orden="estado" class="lp-centro">Estado</th>
                                <th class="lp-centro lp-col-acc">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpo-tabla"></tbody>
                    </table>
                </div>

                <div id="estado-vacio" class="lp-vacio" hidden>
                    <div class="lp-vacio-img" aria-hidden="true"><i class="bi bi-truck"></i><i class="bi bi-search"></i></div>
                    <div class="lp-vacio-txt">
                        <h2 id="vacio-titulo">No hay proveedores para mostrar</h2>
                        <p id="vacio-texto">Aún no se han registrado proveedores. Puedes agregar uno nuevo o cambiar los filtros.</p>
                        <a id="vacio-accion" href="proveedor.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar proveedor</a>
                    </div>
                </div>

                <div class="lp-pie">
                    <span><i class="bi bi-list-ul me-2" aria-hidden="true"></i><strong id="total-registros">Total de Proveedores: 0</strong></span>
                    <div class="lp-pie-der">
                        <label>Registros por página:
                            <select id="tam-pagina" aria-label="Registros por página">
                                <option>10</option><option>25</option><option>50</option><option>100</option>
                            </select>
                        </label>
                        <div id="table-pagination" class="lp-pager"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modalDetalleProveedor" tabindex="-1" aria-labelledby="tituloDetalleProveedor" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="tituloDetalleProveedor">Detalles del proveedor</h5>
                        <small class="text-body-secondary">Información registrada</small>
                    </div>
                    <button type="button" class="btn-close" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="contenidoDetalleProveedor"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-coreui-dismiss="modal">Cerrar</button>
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
    <script src="<?php echo BASE_URL; ?>js/Notificaciones.js"></script>
    <script src="<?php echo BASE_URL; ?>js/JSlista-proveedor.js?v=<?php echo filemtime(__DIR__ . '/../js/JSlista-proveedor.js'); ?>"></script>
</body>

</html>
