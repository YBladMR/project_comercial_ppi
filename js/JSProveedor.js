function validarCampo(campo) {
    const vacio = campo.value.trim() === "";
    campo.classList.toggle("is-invalid", vacio);
    campo.classList.toggle("is-valid", !vacio);
    return !vacio;
}

function marcar(campo, valido) {
    campo.classList.toggle("is-invalid", !valido);
    campo.classList.toggle("is-valid", valido);
    return valido;
}

const formulario = document.getElementById("formulario");
const btnGuardar = document.getElementById("btnGuardar");
const btnCancelar = document.getElementById("btnCancelar");
const idproveedor = document.getElementById("idproveedor");
const tipo_persona = document.getElementById("tipo_persona");
const nombreRS = document.getElementById("nombreRS");
const lblNombreRS = document.getElementById("lblNombreRS");
const dui = document.getElementById("dui");
const nit = document.getElementById("nit");
const telefono = document.getElementById("telefono");
const correo = document.getElementById("correo");
const direccion = document.getElementById("direccion");
const estado = document.getElementById("estado");
const grupoDui = document.getElementById("grupoDui");
const grupoNit = document.getElementById("grupoNit");

const campos = [tipo_persona, nombreRS, dui, nit, telefono, correo, direccion, estado];
let modoModificar = false;

function enviar(datos) {
    return fetch("../dao/DAOProveedor.php", {
        method: "POST",
        body: datos
    });
}

function mensaje(tipo, texto) {
    if (tipo === "error") tipo = "danger";
    notificar(tipo, texto);

    // Da tiempo para leer la notificación antes de cambiar de página.
    return new Promise(function (resolver) {
        setTimeout(resolver, 1500);
    });
}

function actualizarTipo() {
    const tipo = tipo_persona.value.toLowerCase();

    if (tipo === "natural") {
        lblNombreRS.textContent = "Nombre completo (*)";
        grupoDui.style.display = "";
        grupoNit.style.display = "none";
        nit.value = "";
        nit.classList.remove("is-invalid", "is-valid");
    } else if (tipo === "juridica") {
        lblNombreRS.textContent = "Razón Social (*)";
        grupoDui.style.display = "none";
        grupoNit.style.display = "";
        dui.value = "";
        dui.classList.remove("is-invalid", "is-valid");
    } else {
        lblNombreRS.textContent = "Nombre/Razón Social (*)";
        grupoDui.style.display = "";
        grupoNit.style.display = "";
    }
}

function limpiarCampos() {
    formulario.reset();

    for (let i = 0; i < campos.length; i++) {
        campos[i].classList.remove("is-invalid", "is-valid");
    }

    actualizarTipo();
}

async function revisarDocumento(opcion, documento, campo) {
    if (modoModificar || documento.value.length === 0) return;

    if (opcion === "consultarDUI" && documento.value.length !== 10) return;
    if (opcion === "consultarNIT" && documento.value.length !== 17) return;

    try {
        const datos = new FormData();
        datos.append("opcion", opcion);
        datos.append(opcion === "consultarDUI" ? "dui" : "nit", documento.value);

        const respuesta = await enviar(datos);
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

        const resultado = await respuesta.json();
        if (resultado.estado) {
            mensaje("warning", resultado.mensaje);
            campo.classList.add("is-invalid");
            campo.classList.remove("is-valid");
        }
    } catch (error) {
        console.error("Error al revisar el documento:", error);
        mensaje("error", "No se pudo verificar el DUI o NIT.");
    }
}

function aplicarFormatoDui() {
    let valor = dui.value.replace(/\D/g, "").substring(0, 9);
    if (valor.length > 8) {
        valor = valor.substring(0, 8) + "-" + valor.substring(8);
    }
    dui.value = valor;
}

function aplicarFormatoNit() {
    const valor = nit.value.replace(/\D/g, "").substring(0, 14);
    const partes = [];

    if (valor.length > 0) partes.push(valor.substring(0, 4));
    if (valor.length > 4) partes.push(valor.substring(4, 10));
    if (valor.length > 10) partes.push(valor.substring(10, 13));
    if (valor.length > 13) partes.push(valor.substring(13, 14));

    nit.value = partes.join("-");
}

function aplicarFormatoTelefono() {
    let valor = telefono.value.replace(/\D/g, "").substring(0, 8);
    if (valor.length > 4) {
        valor = valor.substring(0, 4) + "-" + valor.substring(4);
    }
    telefono.value = valor;
}

function validarFormulario() {
    const tipoValido = marcar(tipo_persona, tipo_persona.value !== "");
    const estadoValido = marcar(estado, estado.value !== "");
    let nombreValido = true;
    let duiValido = true;
    let nitValido = true;

    if (!modoModificar) {
        nombreValido = validarCampo(nombreRS);

        if (tipo_persona.value === "natural") {
            duiValido = marcar(dui, dui.value.length === 10);
        } else if (tipo_persona.value === "juridica") {
            nitValido = marcar(nit, nit.value.length === 17);
        }
    }

    let telefonoValido = true;
    if (telefono.value.trim() !== "") {
        telefonoValido = marcar(telefono, telefono.value.length === 9);
    } else {
        telefono.classList.remove("is-invalid", "is-valid");
    }

    let correoValido = true;
    if (correo.value.trim() !== "") {
        correoValido = marcar(correo, correo.checkValidity());
    } else {
        correo.classList.remove("is-invalid", "is-valid");
    }

    return tipoValido && estadoValido && nombreValido && duiValido && nitValido && telefonoValido && correoValido;
}

if (formulario) {
    tipo_persona.addEventListener("change", function () {
        marcar(tipo_persona, tipo_persona.value !== "");
        actualizarTipo();
    });

    nombreRS.addEventListener("input", function () {
        nombreRS.value = nombreRS.value.toUpperCase();
        validarCampo(nombreRS);
    });

    dui.addEventListener("input", aplicarFormatoDui);
    nit.addEventListener("input", aplicarFormatoNit);
    telefono.addEventListener("input", aplicarFormatoTelefono);

    dui.addEventListener("keyup", function () {
        revisarDocumento("consultarDUI", dui, dui);
    });
    nit.addEventListener("keyup", function () {
        revisarDocumento("consultarNIT", nit, nit);
    });

    estado.addEventListener("change", function () {
        marcar(estado, estado.value !== "");
    });

    correo.addEventListener("blur", function () {
        if (correo.value.trim() === "") {
            correo.classList.remove("is-invalid", "is-valid");
        } else {
            marcar(correo, correo.checkValidity());
        }
    });

    telefono.addEventListener("blur", function () {
        if (telefono.value.trim() === "") {
            telefono.classList.remove("is-invalid", "is-valid");
        } else {
            marcar(telefono, telefono.value.length === 9);
        }
    });

    btnCancelar.addEventListener("click", function () {
        window.location.href = "lista-proveedor.php";
    });

    btnGuardar.addEventListener("click", async function () {
        if (!validarFormulario()) {
            mensaje("warning", "Revisa los campos obligatorios y los formatos indicados.");
            return;
        }

        const contenidoOriginal = btnGuardar.innerHTML;

        try {
            const datos = new FormData(formulario);
            datos.set("opcion", modoModificar ? "modificar" : "guardar");
            datos.set("tipo_persona", tipo_persona.value.toLowerCase());

            btnGuardar.disabled = true;
            btnGuardar.textContent = "Procesando...";

            const respuesta = await enviar(datos);
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();

            if (!resultado.estado) {
                mensaje("error", resultado.mensaje || "No se pudo guardar el proveedor.");
                return;
            }

            await mensaje("success", resultado.mensaje);

            if (modoModificar) {
                window.location.href = "lista-proveedor.php";
            } else {
                limpiarCampos();
            }
        } catch (error) {
            console.error("Error al guardar el proveedor:", error);
            mensaje("error", "No se pudo procesar la solicitud.");
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = contenidoOriginal;
        }
    });

    actualizarTipo();
}

document.addEventListener("DOMContentLoaded", async function () {
    const parametros = new URLSearchParams(window.location.search);
    const id = parametros.get("id");
    if (!id) return;

    const contenidoOriginal = btnGuardar.innerHTML;
    btnGuardar.disabled = true;
    btnGuardar.textContent = "Consultando proveedor...";

    try {
        const datos = new FormData();
        datos.append("opcion", "consultarIndividual");
        datos.append("idproveedor", id);

        const respuesta = await enviar(datos);
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

        const resultado = await respuesta.json();
        if (!resultado.estado) {
            mensaje("error", resultado.datos || "No se encontró el proveedor.");
            return;
        }

        const proveedor = resultado.datos;
        idproveedor.value = proveedor.id_proveedor;
        tipo_persona.value = proveedor.tipo_persona;
        nombreRS.value = proveedor.nombre_razon_social || "";
        dui.value = proveedor.dui || "";
        nit.value = proveedor.nit || "";
        telefono.value = proveedor.telefono || "";
        correo.value = proveedor.correo_electronico || "";
        direccion.value = proveedor.direccion || "";
        estado.value = proveedor.estado;

        actualizarTipo();

        tipo_persona.disabled = true;
        nombreRS.disabled = true;
        dui.disabled = true;
        nit.disabled = true;

        modoModificar = true;
        btnGuardar.innerHTML = '<i class="bi bi-save" aria-hidden="true"></i> Modificar proveedor';
    } catch (error) {
        console.error("Error al consultar el proveedor:", error);
        mensaje("error", "No se pudieron cargar los datos del proveedor.");
    } finally {
        btnGuardar.disabled = false;
        if (!modoModificar) btnGuardar.innerHTML = contenidoOriginal;
    }
});
