document.addEventListener("DOMContentLoaded", async function () {
    const formulario = document.getElementById("formCliente");
    if (!formulario) return;

    const idCliente = document.getElementById("id_cliente");
    const nombre = document.getElementById("nombre");
    const apellido = document.getElementById("apellido");
    const dui = document.getElementById("dui");
    const telefono = document.getElementById("telefono");
    const direccion = document.getElementById("direccion_entrega");
    const correo = document.getElementById("correo_electronico");
    const estado = document.getElementById("estado");
    const botonGuardar = document.getElementById("btnGuardar");
    const textoGuardar = document.getElementById("textoGuardar");
    const botonCancelar = document.getElementById("btnCancelar");
    const mensajeDUI = document.getElementById("mensajeDUI");
    const tituloFormulario = document.getElementById("tituloFormulario");
    const urlDao = "../dao/DAOCliente.php";
    const desdeVenta = new URLSearchParams(window.location.search).get("retorno") === "venta";
    const claveBorradorVenta = "comercial_venta_borrador";

    function notificarError(mensaje) {
        notificar("danger", mensaje);
    }

    dui.addEventListener("input", function () {
        let valor = dui.value.replace(/\D/g, "").slice(0, 9);
        if (valor.length > 8) valor = valor.slice(0, 8) + "-" + valor.slice(8);
        dui.value = valor;
        mensajeDUI.textContent = "";
        dui.classList.remove("is-invalid");
    });

    telefono.addEventListener("input", function () {
        let valor = telefono.value.replace(/\D/g, "").slice(0, 8);
        if (valor.length > 4) valor = valor.slice(0, 4) + "-" + valor.slice(4);
        telefono.value = valor;
    });

    async function consultarDUI() {
        const datos = new FormData();
        datos.append("opcion", "consultarDUI");
        datos.append("dui", dui.value);
        datos.append("id_cliente", idCliente.value || "0");

        const respuesta = await fetch(urlDao, { method: "POST", body: datos });
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
        return await respuesta.json();
    }

    async function cargarCliente(id) {
        try {
            botonGuardar.disabled = true;
            textoGuardar.textContent = "Cargando cliente...";

            const respuesta = await fetch(
                urlDao + "?opcion=consultarIndividual&id=" + encodeURIComponent(id)
            );
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const cliente = await respuesta.json();
            if (!cliente || !cliente.id_cliente) {
                notificarError("No se encontró el cliente solicitado.");
                return;
            }

            idCliente.value = cliente.id_cliente;
            nombre.value = cliente.nombre || "";
            apellido.value = cliente.apellido || "";
            dui.value = cliente.dui || "";
            telefono.value = cliente.telefono || "";
            direccion.value = cliente.direccion_entrega || "";
            correo.value = cliente.correo_electronico || "";
            estado.value = String(cliente.estado || "activo").toLowerCase();

            tituloFormulario.textContent = "Modificar cliente";
            textoGuardar.textContent = "Modificar cliente";
        } catch (error) {
            console.error("Error al cargar el cliente:", error);
            notificarError("No se pudieron cargar los datos del cliente.");
        } finally {
            botonGuardar.disabled = false;
        }
    }

    botonCancelar.addEventListener("click", function () {
        window.location.href = desdeVenta ? "Ventas.php?continuar=1" : "lista-cliente.php";
    });

    formulario.addEventListener("submit", async function (evento) {
        evento.preventDefault();
        formulario.classList.add("was-validated");

        if (!formulario.checkValidity()) {
            notificar("warning", "Revisa los campos obligatorios y los formatos indicados.");
            formulario.reportValidity();
            return;
        }

        if (!/^\d{8}-\d$/.test(dui.value)) {
            mensajeDUI.textContent = "Ingresa el DUI con el formato 00000000-0.";
            dui.classList.add("is-invalid");
            dui.focus();
            return;
        }

        if (!/^\d{4}-\d{4}$/.test(telefono.value)) {
            notificar("warning", "Ingresa el teléfono con el formato 0000-0000.");
            telefono.focus();
            return;
        }

        const textoOriginal = textoGuardar.textContent;

        try {
            const validacion = await consultarDUI();
            if (validacion.existe) {
                mensajeDUI.textContent = "Ya existe un cliente registrado con ese DUI.";
                dui.classList.add("is-invalid");
                notificar("warning", mensajeDUI.textContent);
                dui.focus();
                return;
            }

            const datos = new FormData(formulario);
            datos.append("opcion", idCliente.value ? "modificar" : "guardar");

            botonGuardar.disabled = true;
            textoGuardar.textContent = "Procesando...";

            const respuesta = await fetch(urlDao, { method: "POST", body: datos });
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();
            if (resultado.resultado !== "correcto") {
                notificarError(resultado.mensaje || "No se pudo guardar el cliente.");
                return;
            }

            notificar("success", resultado.mensaje || "Cliente guardado correctamente.");
            if (desdeVenta && !idCliente.value) {
                let borrador = {};
                try {
                    borrador = JSON.parse(sessionStorage.getItem(claveBorradorVenta) || "{}");
                    if (!borrador || typeof borrador !== "object") borrador = {};
                } catch (error) {
                    borrador = {};
                }
                borrador.cliente = {
                    id_cliente: resultado.id_cliente,
                    nombre: `${nombre.value} ${apellido.value}`.trim(),
                    dui: dui.value
                };
                sessionStorage.setItem(claveBorradorVenta, JSON.stringify(borrador));
                setTimeout(function () {
                    window.location.href = "Ventas.php?continuar=1";
                }, 700);
                return;
            }
            setTimeout(function () {
                window.location.href = "lista-cliente.php";
            }, 1200);
        } catch (error) {
            console.error("Error al guardar el cliente:", error);
            notificarError("No se pudo procesar la solicitud.");
        } finally {
            botonGuardar.disabled = false;
            textoGuardar.textContent = textoOriginal;
        }
    });

    const parametros = new URLSearchParams(window.location.search);
    const id = parametros.get("id");
    if (id) await cargarCliente(id);
});
