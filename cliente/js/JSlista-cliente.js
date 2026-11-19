document.addEventListener("DOMContentLoaded", function () {
    const tabla = document.getElementById("tablaClientes");
    const buscar = document.getElementById("buscarCliente");
    let clientes = [];

    async function cargarClientes() {
        try {
            const respuesta = await fetch("cliente.php?opcion=consultar");

            if (!respuesta.ok) {
                throw new Error("Error al consultar los clientes");
            }

            clientes = await respuesta.json();
            mostrarClientes(clientes);
        } catch (error) {
            console.error(error);
            tabla.innerHTML =
                '<tr><td colspan="9">No se pudieron cargar los clientes.</td></tr>';
        }
    }

    function mostrarClientes(lista) {
        tabla.innerHTML = "";

        if (lista.length === 0) {
            tabla.innerHTML =
                '<tr><td colspan="9">No hay clientes registrados.</td></tr>';
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
            ].forEach(function (dato) {
                const celda = document.createElement("td");
                celda.textContent = dato ?? "";
                fila.appendChild(celda);
            });

            const acciones = document.createElement("td");
            const editar = document.createElement("a");

            editar.href = "cliente.php?id=" + encodeURIComponent(cliente.id_cliente);
            editar.className = "btn btn-warning btn-sm";
            editar.textContent = "Editar";

            acciones.appendChild(editar);
            fila.appendChild(acciones);
            tabla.appendChild(fila);
        });
    }

    buscar.addEventListener("input", function () {
        const texto = this.value.toLowerCase();

        const filtrados = clientes.filter(function (cliente) {
            return Object.values(cliente).some(function (dato) {
                return String(dato ?? "").toLowerCase().includes(texto);
            });
        });

        mostrarClientes(filtrados);
    });

    cargarClientes();
});