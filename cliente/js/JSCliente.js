document.addEventListener("DOMContentLoaded", function () {
    const formulario = document.getElementById("formCliente");

    if (!formulario) {
        return;
    }

    const idCliente = document.getElementById("id_cliente");
    const nombre = document.getElementById("nombre");
    const apellido = document.getElementById("apellido");
    const dui = document.getElementById("dui");
    const telefono = document.getElementById("telefono");
    const direccion = document.getElementById("direccion_entrega");
    const correo = document.getElementById("correo_electronico");
    const estado = document.getElementById("estado");
    const btnGuardar = document.getElementById("btnGuardar");
    const mensajeDUI = document.getElementById("mensajeDUI");

    const rutaDAO = "DAOCliente.php";

    dui.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "").slice(0, 9);

        if (valor.length > 8) {
            valor = valor.slice(0, 8) + "-" + valor.slice(8);
        }

        this.value = valor;
        mensajeDUI.textContent = "";
    });

    telefono.addEventListener("input", function () {
        let valor = this.value.replace(/\D/g, "").slice(0, 8);

        if (valor.length > 4) {
            valor = valor.slice(0, 4) + "-" + valor.slice(4);
        }

        this.value = valor;
    });

    async function consultarDUI() {
        const datos = new FormData();
        datos.append("opcion", "consultarDUI");
        datos.append("dui", dui.value);
        datos.append("id_cliente", idCliente.value || 0);

        const respuesta = await fetch(rutaDAO, {
            method: "POST",
            body: datos
        });

        return await respuesta.json();
    }

    async function cargarCliente(id) {
        const respuesta = await fetch(
            `${rutaDAO}?opcion=consultarIndividual&id=${encodeURIComponent(id)}`
        );

        const cliente = await respuesta.json();

        if (!cliente) {
            alert("No se encontró el cliente.");
            return;
        }

        nombre.value = cliente.nombre || "";
        apellido.value = cliente.apellido || "";
        dui.value = cliente.dui || "";
        telefono.value = cliente.telefono || "";
        direccion.value = cliente.direccion_entrega || "";
        correo.value = cliente.correo_electronico || "";
        estado.value = cliente.estado || "Activo";
        btnGuardar.textContent = "Modificar";
    }

    const parametros = new URLSearchParams(window.location.search);
    const idURL = parametros.get("id");

    if (idURL) {
        idCliente.value = idURL;
        cargarCliente(idURL);
    }

    formulario.addEventListener("submit", async function (evento) {
        evento.preventDefault();

        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        const duiValido = /^\d{8}-\d$/.test(dui.value);
        const telefonoValido = /^\d{4}-\d{4}$/.test(telefono.value);

        if (!duiValido) {
            mensajeDUI.textContent = "Ingrese el DUI con el formato ########-#.";
            dui.focus();
            return;
        }

        if (!telefonoValido) {
            alert("Ingrese el teléfono con el formato ####-####.");
            telefono.focus();
            return;
        }

        try {
            const validacion = await consultarDUI();

            if (validacion.existe) {
                mensajeDUI.textContent = "Ya existe un cliente registrado con ese DUI.";
                dui.focus();
                return;
            }

            const datos = new FormData(formulario);
            datos.append("opcion", idCliente.value ? "modificar" : "guardar");

            const respuesta = await fetch(rutaDAO, {
                method: "POST",
                body: datos
            });

            const resultado = await respuesta.json();

            if (resultado.resultado === "correcto") {
                alert(resultado.mensaje);
                window.location.href = "lista-clientes.php";
            } else {
                alert(resultado.mensaje || "Ocurrió un error al guardar.");
            }
        } catch (error) {
            console.error(error);
            alert("No se pudo comunicar con el servidor.");
        }
    });
});