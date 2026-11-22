/* ============================================================
 * JScategoria.js
 * Formulario de categoría: guardar y modificar.
 * Usa notificar() de Notificaciones.js (no requiere SweetAlert).
 * ============================================================ */

document.addEventListener("DOMContentLoaded", async () => {
    const formulario = document.getElementById("formCategoria");
    if (!formulario) return;

    const nombre = document.getElementById("nombre");
    const descripcion = document.getElementById("descripcion");
    const categoriaPadre = document.getElementById("id_padre");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelar");
    const textoGuardar = document.getElementById("textoGuardar");
    const titulo = document.getElementById("tituloFormulario");

    const URL_LISTA = "lista-categoria.php";   // página de la lista
    const URL_DAO = "../dao/DAOcategoria.php";

    let idEdicion = null;   // null = registrando, número = modificando

    /* ---------------------- UTILIDADES ---------------------- */

    function marcar(campo, valido) {
        campo.classList.toggle("is-invalid", !valido);
        campo.classList.toggle("is-valid", valido);
        return valido;
    }

    function validarNombre() {
        const valor = nombre.value.trim();
        const soloLetrasYEspacios = /^[\p{L} ]+$/u.test(valor);
        return marcar(nombre, valor !== "" && valor.length <= 80 && soloLetrasYEspacios);
    }

    async function cargarCategoriasPadre(idActual = null) {
        const datos = new FormData();
        datos.append("opcion", "consultarPadres");

        const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
        if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

        const resultado = await respuesta.json();
        if (!Array.isArray(resultado)) throw new Error("Respuesta no válida al cargar categorías principales");

        resultado.forEach((categoria) => {
            if (String(categoria.id_categoria) === String(idActual)) return;
            categoriaPadre.add(new Option(categoria.nombre, String(categoria.id_categoria)));
        });
    }

    function limpiarCampos() {
        formulario.reset();
        [nombre, descripcion].forEach((c) => c.classList.remove("is-invalid", "is-valid"));
    }

    function modoEdicion(activo) {
        titulo.textContent = activo ? "Modificar Categoría" : "Registrar Nueva Categoría";
        textoGuardar.textContent = activo ? "Modificar Categoría" : "Guardar Categoría";
    }

    /* ---------------------- EVENTOS ---------------------- */

    // Nombre y descripción en mayúsculas
    // [nombre, descripcion].forEach((campo) => {
    //     campo.addEventListener("input", () => {
    //         campo.value = campo.value.toUpperCase();
    //         if (campo === nombre) validarNombre();
    //     });
    // });
    nombre.addEventListener("blur", validarNombre);

    btnCancelar.addEventListener("click", () => { window.location.href = URL_LISTA; });

    btnGuardar.addEventListener("click", async () => {
        const nombreValido = validarNombre();

        if (!nombreValido) {
            const valor = nombre.value.trim();
            let mensaje = "El nombre solo puede contener letras y espacios.";
            if (valor === "") {
                mensaje = "El nombre de la categoría es obligatorio.";
            } else if (valor.length > 80) {
                mensaje = "El nombre de la categoría no puede superar 80 caracteres.";
            }
            notificar("danger", mensaje);
            nombre.focus();
            return;
        }

        const modificando = idEdicion !== null;
        const textoOriginal = textoGuardar.textContent;

        try {
            const datos = new FormData(formulario);
            datos.append("opcion", modificando ? "modificar" : "guardar");
            if (modificando) datos.append("id_categoria", idEdicion);

            btnGuardar.disabled = true;
            textoGuardar.textContent = "Procesando...";

            const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }
            const resultado = await respuesta.json();

            if (resultado.estado) {
                notificar("success", resultado.mensaje);
                if (modificando) {
                    // Vuelve a la lista cuando termina el aviso
                    setTimeout(() => { window.location.href = URL_LISTA; }, 1500);
                    return;
                }
                limpiarCampos();
            } else {
                notificar("danger", resultado.mensaje);
            }
        } catch (error) {
            console.error("Error en la petición:", error);
            notificar("danger", "No se pudo completar la operación");
        } finally {
            btnGuardar.disabled = false;
            textoGuardar.textContent = textoOriginal;
        }
    });

    /* ---------------------- CARGAR PARA MODIFICAR ---------------------- */

    // Acepta ?id=5 (como lo manda la lista) o ?id_categoria=5
    const parametros = new URLSearchParams(window.location.search);
    const id = parametros.get("id") || parametros.get("id_categoria");

    try {
        await cargarCategoriasPadre(id);
    } catch (error) {
        console.error("Error al cargar categorías principales:", error);
        notificar("danger", "No se pudieron cargar las categorías principales");
    }

    if (id) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarIndividual");
            datos.append("id_categoria", id);

            const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }
            const resultado = await respuesta.json();

            if (resultado.estado) {
                nombre.value = resultado.datos.nombre ?? "";
                descripcion.value = resultado.datos.descripcion ?? "";
                categoriaPadre.value = resultado.datos.id_padre ?? "";

                idEdicion = resultado.datos.id_categoria;
                modoEdicion(true);
            } else {
                notificar("danger", resultado.mensaje || "La categoría no existe");
            }
        } catch (error) {
            console.error("Error al consultar la categoría:", error);
            notificar("danger", "No se pudo cargar la categoría");
        }
    }
});
