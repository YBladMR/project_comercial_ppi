<?php
$id_cliente = $_GET["id"] ?? "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de clientes</title>
</head>
<body>

<?php include("../menu-cabecera.php"); ?>
<?php include("../menu-lateral.php"); ?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Registro de clientes</h1>
    </section>

    <section class="content">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Datos del cliente</h3>
            </div>

            <form id="formCliente">
                <div class="card-body">
                    <input type="hidden" id="id_cliente" name="id_cliente"
                           value="<?php echo htmlspecialchars($id_cliente); ?>">

                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre"
                               name="nombre" required>
                    </div>

                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" class="form-control" id="apellido"
                               name="apellido" required>
                    </div>

                    <div class="form-group">
                        <label for="dui">DUI</label>
                        <input type="text" class="form-control" id="dui"
                               name="dui" placeholder="########-#" maxlength="10" required>
                        <small id="mensajeDUI" class="text-danger"></small>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" class="form-control" id="telefono"
                               name="telefono" placeholder="####-####" maxlength="9" required>
                    </div>

                    <div class="form-group">
                        <label for="direccion_entrega">Dirección de entrega</label>
                        <input type="text" class="form-control" id="direccion_entrega"
                               name="direccion_entrega" required>
                    </div>

                    <div class="form-group">
                        <label for="correo_electronico">Correo electrónico</label>
                        <input type="email" class="form-control" id="correo_electronico"
                               name="correo_electronico" required>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="Activo">Activo</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        Guardar
                    </button>
                    <a href="lista-clientes.php" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include("../pie.php"); ?>

<script src="../js/JSCliente.js?v=<?php echo filemtime('../js/JSCliente.js'); ?>"></script>
</body>
</html> 