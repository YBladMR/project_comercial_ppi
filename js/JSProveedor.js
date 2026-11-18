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

const grupoDui = dui ? dui.closest(".form-floating") : null;
const grupoNit = nit ? nit.closest(".form-floating") : null;

const campos = [tipo_persona, nombreRS, dui, nit, telefono, correo, direccion, estado];

let modoModificar = false;

function enviar(datos) {
    return fetch("../dao/DAOProveedor.php", { method: "POST", body: datos });
}

function mensaje(icono, texto) {
    return Swal.fire({
        position: "top-end",
        icon: icono,
        title: "Poseidón",
        text: texto,
        showConfirmButton: false,
        timer: 1500
    });
}

// Muestra DUI o NIT según el tipo de persona
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

if (formulario) {
    //////////////// TIPO DE PERSONA
    tipo_persona.addEventListener("change", function () {
        marcar(tipo_persona, tipo_persona.value !== "");
        actualizarTipo();
    });

    //////////////// NOMBRE EN MAYUSCULAS
    nombreRS.addEventListener("input", function () {
        nombreRS.value = nombreRS.value.toUpperCase();
        validarCampo(nombreRS);
    });

    //////////////// FORMATO DUI (00000000-0)
    dui.addEventListener("input", function () {
        let valor = dui.value.replace(/\D/g, "").substring(0, 9);
        if (valor.length > 8) {
            valor = valor.substring(0, 8) + "-" + valor.substring(8);
        }
        dui.value = valor;
    });

    //////////////// FORMATO NIT (0000-000000-000-0)
    nit.addEventListener("input", function () {
        let valor = nit.value.replace(/\D/g, "").substring(0, 14);
        let partes = [];
        if (valor.length > 0) partes.push(valor.substring(0, 4));
        if (valor.length > 4) partes.push(valor.substring(4, 10));
        if (valor.length > 10) partes.push(valor.substring(10, 13));
        if (valor.length > 13) partes.push(valor.substring(13, 14));
        nit.value = partes.join("-");
    });

    //////////////// FORMATO TELEFONO (0000-0000)
    telefono.addEventListener("input", function () {
        let valor = telefono.value.replace(/\D/g, "").substring(0, 8);
        if (valor.length > 4) {
            valor = valor.substring(0, 4) + "-" + valor.substring(4);
        }
        telefono.value = valor;
    });

    //////////////// BUSCAR DUI / NIT REPETIDOS (solo al registrar)
    dui.addEventListener("keyup", async function () {
        if (modoModificar || dui.value.length !== 10) return;
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarDUI");
            datos.append("dui", dui.value);
            const resultado = await (await enviar(datos)).json();
            if (resultado.estado) {
                mensaje("warning", resultado.mensaje);
                dui.classList.add("is-invalid");
            }
        } catch (error) { }
    });

    nit.addEventListener("keyup", async function () {
        if (modoModificar || nit.value.length !== 17) return;
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarNIT");
            datos.append("nit", nit.value);
            const resultado = await (await enviar(datos)).json();
            if (resultado.estado) {
                mensaje("warning", resultado.mensaje);
                nit.classList.add("is-invalid");
            }
        } catch (error) { }
    });

    //////////////// VALIDACION AL SALIR DE CADA CAMPO
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
        window.location.href = "../forms/proveedor.php";
    });

    //////////////// GUARDAR / MODIFICAR
    btnGuardar.addEventListener("click", async function () {
        const tipo = tipo_persona.value.toLowerCase();

        const tipoValido = marcar(tipo_persona, tipo !== "");
        const estadoValido = marcar(estado, estado.value !== "");
        let nombreValido = true;
        let duiValido = true;
        let nitValido = true;

        // Opcionales: solo se validan si el usuario escribió algo
        let telefonoValido = telefono.value.trim() === "" || telefono.value.length === 9;
        let correoValido = correo.value.trim() === "" || correo.checkValidity();

        if (!modoModificar) {
            nombreValido = validarCampo(nombreRS);

            if (tipo === "natural") {
                duiValido = marcar(dui, dui.value.length === 10);
            }
            if (tipo === "juridica") {
                nitValido = marcar(nit, nit.value.length === 17);
            }
        }

        if (telefono.value.trim() !== "") marcar(telefono, telefonoValido);
        if (correo.value.trim() !== "") marcar(correo, correoValido);

        if (!(tipoValido && estadoValido && nombreValido && duiValido && nitValido && telefonoValido && correoValido)) {
            return;
        }

        const textoOriginal = modoModificar ? "Modificar" : "Guardar";

        try {
            const datos = new FormData(formulario);
            datos.set("opcion", modoModificar ? "modificar" : "guardar");
            datos.set("tipo_persona", tipo);

            btnGuardar.disabled = true;
            btnGuardar.textContent = "Procesando...";

            const respuesta = await enviar(datos);
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }

            const resultado = await respuesta.json();

            if (resultado.estado) {
                await mensaje("success", resultado.mensaje);
                if (modoModificar) {
                    window.location.href = "../forms/proveedor.php";
                } else {
                    limpiarCampos();
                }
            } else {
                mensaje("error", resultado.mensaje);
            }
        } catch (error) {
            mensaje("error", "No se pudo procesar la solicitud");
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = textoOriginal;
        }
    });

    actualizarTipo();
}

/////////////////// LIMPIAR TODOS LOS CAMPOS
function limpiarCampos() {
    campos.forEach(function (campo) {
        campo.value = "";
        campo.classList.remove("is-invalid");
        campo.classList.remove("is-valid");
    });
    actualizarTipo();
}

////////// AL CARGAR LA PAGINA: si viene ?id=, se carga el proveedor para modificar
document.addEventListener("DOMContentLoaded", async () => {
    const parametros = new URLSearchParams(window.location.search);
    const id = parametros.get("id");

    if (!id) return;

    try {
        const datos = new FormData();
        datos.append("opcion", "consultarIndividual");
        datos.append("idproveedor", id);

        Swal.fire({
            title: "Consultando...",
            text: "Espere",
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        const respuesta = await enviar(datos);
        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }

        const resultado = await respuesta.json();
        Swal.close();

        if (resultado.estado) {
            const d = resultado.datos;

            idproveedor.value = d.id_proveedor;
            tipo_persona.value = d.tipo_persona;
            nombreRS.value = d.nombre_razon_social;
            dui.value = d.dui;
            nit.value = d.nit;
            telefono.value = d.telefono;
            correo.value = d.correo_electronico;
            direccion.value = d.direccion;
            estado.value = d.estado;

            actualizarTipo();

            // Según el requerimiento, solo se modifican teléfono, dirección, correo y estado
            tipo_persona.disabled = true;
            nombreRS.disabled = true;
            dui.disabled = true;
            nit.disabled = true;

            modoModificar = true;
            btnGuardar.textContent = "Modificar";
        }
    } catch (error) {
        Swal.close();
    }
});