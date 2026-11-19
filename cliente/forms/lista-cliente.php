<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de clientes</title>
</head>
<body>

<?php include("../menu-cabecera.php"); ?>
<?php include("../menu-lateral.php"); ?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Listado de clientes</h1>
    </section>

    <section class="content">
        <div class="card">
            <div class="card-header">
                <a href="cliente.php" class="btn btn-primary">
                    Nuevo cliente
                </a>

                <input type="text" id="buscarCliente" class="form-control mt-3"
                       placeholder="Buscar cliente...">
            </div>

            <div class="card-body table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>DUI</th>
                            <th>Teléfono</th>
                            <th>Dirección de entrega</th>
                            <th>Correo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tablaClientes">
                        <tr>
                            <td colspan="9">Cargando clientes...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<?php include("../pie.php"); ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tabla = document.getElementById("tablaClientes");
    const buscar = document.getElementById("buscarCliente");
    let clientes = [];

    function mostrarClientes(lista) {
        tabla.innerHTML = "";

        if (lista.length === 0) {
            tabla.innerHTML = '<tr><td colspan="9">No hay clientes registrados.</td></tr>';
            return;
        }

        lista.forEach(function (cliente) {
            const fila = document.createElement("tr");

            [
                cliente.id_cliente,
                cliente.nombre,
                cliente.apellido,
                cliente.dui,
                cliente.telefono,
                cliente.direccion_entrega,
                cliente.correo_electronico,
                cliente.estado
            ].forEach(function (valor) {
                const celda = document.createElement("td");
                celda.textContent = valor ?? "";
                fila.appendChild(celda);
            });

            const acciones = document.createElement("td");

            const editar = document.createElement("a");
            editar.href = "cliente.php?id=" + encodeURIComponent(cliente.id_cliente);
            editar.className = "btn btn-warning btn-sm";
            editar.textContent = "Editar";

            const eliminar = document.createElement("button");
            eliminar.type = "button";
            eliminar.className = "btn btn-danger btn-sm ml-1";
            eliminar.textContent = "Desactivar";
            eliminar.addEventListener("click", function () {
                desactivarCliente(cliente.id_cliente);
            });

            acciones.appendChild(editar);
            acciones.appendChild(eliminar);
            fila.appendChild(acciones);
            tabla.appendChild(fila);
        });
    }

    async function cargarClientes() {
        try {
            const respuesta = await fetch("DAOCliente.php?opcion=consultar");
            clientes = await respuesta.json();
            mostrarClientes(clientes);
        } catch (error) {
            console.error(error);
            tabla.innerHTML =
                '<tr><td colspan="9">Error al cargar los clientes.</td></tr>';
        }
    }

    async function desactivarCliente(id) {
        if (!confirm("¿Desea desactivar este cliente?")) {
            return;
        }

        const datos = new FormData();
        datos.append("opcion", "eliminar");
        datos.append("id_cliente", id);

        try {
            const respuesta = await fetch("DAOCliente.php", {
                method: "POST",
                body: datos
            });

            const resultado = await respuesta.json();
            alert(resultado.mensaje || "Operación finalizada.");

            if (resultado.resultado === "correcto") {
                cargarClientes();
            }
        } catch (error) {
            console.error(error);
            alert("No se pudo desactivar el cliente.");
        }
    }

    buscar.addEventListener("input", function () {
        const texto = this.value.toLowerCase();

        const filtrados = clientes.filter(function (cliente) {
            return Object.values(cliente).some(function (valor) {
                return String(valor ?? "").toLowerCase().includes(texto);
            });
        });

        mostrarClientes(filtrados);
    });

    cargarClientes();
});
</script>
</body>
</html>