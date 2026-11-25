<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
requerir_permiso('proveedores.gestionar');
$titulo_pagina = "Registrar Proveedor";
include_once(__DIR__ . '/../config.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titulo_pagina; ?> - Casa Aurelia</title>

    <!-- Estilos de la plantilla CoreUI -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
    <script src="<?php echo BASE_URL; ?>js/config.js"></script>
    <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>

<body>

    <?php include __DIR__ . '/../Barra_Lateral.php'; ?>

    <div class="body flex-grow-1 px-3">
        <div class="container-lg">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-3 bg-body-tertiary">
                    <span class="d-inline-flex align-items-center justify-content-center rounded bg-primary bg-opacity-10 text-primary" style="width: 38px; height: 38px;">
                        <i class="bi bi-truck fs-5" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="fw-bold" id="tituloFormulario"><?php echo $titulo_pagina; ?></div>
                        <small class="text-body-secondary">Completa la información de contacto y fiscal del proveedor.</small>
                    </div>
                </div>

                <div class="card-body">
                    <form id="formulario" name="formulario" method="POST" action="#" autocomplete="off">
                        <input type="hidden" id="idproveedor" name="idproveedor" value="" autocomplete="off">

                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label for="tipo_persona" class="form-label">Tipo de persona (*)</label>
                                <select class="form-select" id="tipo_persona" name="tipo_persona">
                                    <option value="" selected>[SELECCIONE]</option>
                                    <option value="natural">NATURAL</option>
                                    <option value="juridica">JURÍDICA</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="nombreRS" class="form-label" id="lblNombreRS">Nombre/Razón Social (*)</label>
                                <input type="text" class="form-control" id="nombreRS" name="nombreRS" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6" id="grupoDui">
                                <label for="dui" class="form-label">DUI (*)</label>
                                <input type="text" class="form-control" id="dui" name="dui" maxlength="10" pattern="\d{8}-\d" placeholder="00000000-0" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6" id="grupoNit">
                                <label for="nit" class="form-label">NIT (*)</label>
                                <input type="text" class="form-control" id="nit" name="nit" maxlength="17" pattern="\d{4}-\d{6}-\d{3}-\d" placeholder="0000-000000-000-0" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" maxlength="9" pattern="\d{4}-\d{4}" placeholder="0000-0000" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="correo" class="form-label">Correo electrónico</label>
                                <input type="email" class="form-control" id="correo" name="correo" autocomplete="off">
                            </div>

                            <div class="col-12 col-md-8">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="3"></textarea>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="estado" class="form-label">Estado (*)</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="" selected>[SELECCIONE]</option>
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" id="btnCancelar" class="btn btn-secondary">Cancelar</button>
                            <button type="button" id="btnGuardar" class="btn btn-primary">
                                <i class="bi bi-save" aria-hidden="true"></i> Guardar proveedor
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

    </div><!-- Cierra el wrapper de Barra_Lateral.php -->

    <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
    <script src="<?php echo BASE_URL; ?>js/Notificaciones.js?v=<?php echo filemtime(__DIR__ . '/../js/Notificaciones.js'); ?>"></script>
    <script src="<?php echo BASE_URL; ?>js/JSProveedor.js?v=<?php echo filemtime(__DIR__ . '/../js/JSProveedor.js'); ?>"></script>
</body>

</html>
