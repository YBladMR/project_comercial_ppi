document.addEventListener("DOMContentLoaded", async function () {
    const formulario = document.getElementById("formUbicacion");
    if (!formulario) return;

    const campos = [
        document.getElementById("bodega"),
        document.getElementById("pasillo"),
        document.getElementById("estante"),
        document.getElementById("nivel")
    ];

    const listasSugerencias = [
        document.getElementById("sugerencias-bodega"),
        document.getElementById("sugerencias-pasillo"),
        document.getElementById("sugerencias-estante"),
        document.getElementById("sugerencias-nivel")
    ];

    const botonGuardar = document.getElementById("btnGuardar");
    const botonCancelar = document.getElementById("btnCancelar");
    const textoBotonGuardar = document.getElementById("textoGuardar");
    const tituloFormulario = document.getElementById("tituloFormulario");
    const urlDao = "../dao/DAOubicacion.php";

    let ubicaciones = [];
    let idUbicacionEditar = null;

    function normalizar(texto) {
        return String(texto || "").trim().toLowerCase();
    }

    function ocultarLista(indice) {
        listasSugerencias[indice].innerHTML = "";
        listasSugerencias[indice].hidden = true;
        campos[indice].setAttribute("aria-expanded", "false");
    }

    function ocultarOtrasListas(indiceActual) {
        for (let i = 0; i < listasSugerencias.length; i++) {
            if (i !== indiceActual) ocultarLista(i);
        }
    }

    function mostrarSugerencias(campo, indice) {
        const lista = listasSugerencias[indice];
        const textoEscrito = normalizar(campo.value);
        ocultarOtrasListas(indice);
        lista.innerHTML = "";

        if (textoEscrito === "") {
            ocultarLista(indice);
            return;
        }

        const opciones = [];

        for (let i = 0; i < ubicaciones.length; i++) {
            const valor = String(ubicaciones[i][campo.name] || "").trim();
            const valorNormalizado = normalizar(valor);
            let yaEstaEnLista = false;

            for (let j = 0; j < opciones.length; j++) {
                if (normalizar(opciones[j]) === valorNormalizado) {
                    yaEstaEnLista = true;
                    break;
                }
            }

            if (valor !== "" && valorNormalizado.includes(textoEscrito) && !yaEstaEnLista) {
                opciones.push(valor);
            }

            if (opciones.length === 2) break;
        }

        for (let i = 0; i < opciones.length; i++) {
            const botonOpcion = document.createElement("button");
            botonOpcion.type = "button";
            botonOpcion.className = "ubicacion-sugerencia";
            botonOpcion.setAttribute("role", "option");
            botonOpcion.dataset.valor = opciones[i];
            botonOpcion.textContent = opciones[i];
            lista.appendChild(botonOpcion);
        }

        lista.hidden = opciones.length === 0;
        campo.setAttribute("aria-expanded", opciones.length > 0 ? "true" : "false");
    }

    function validarCampo(campo) {
        const tieneTexto = campo.value.trim() !== "";
        campo.classList.toggle("is-valid", tieneTexto);
        campo.classList.toggle("is-invalid", !tieneTexto);
        return tieneTexto;
    }

    function hayCombinacionRepetida() {
        for (let i = 0; i < ubicaciones.length; i++) {
            const ubicacion = ubicaciones[i];
            if (String(ubicacion.id_ubicacion) === String(idUbicacionEditar)) continue;

            const mismaBodega = normalizar(ubicacion.bodega) === normalizar(campos[0].value);
            const mismoPasillo = normalizar(ubicacion.pasillo) === normalizar(campos[1].value);
            const mismoEstante = normalizar(ubicacion.estante) === normalizar(campos[2].value);
            const mismoNivel = normalizar(ubicacion.nivel) === normalizar(campos[3].value);

            if (mismaBodega && mismoPasillo && mismoEstante && mismoNivel) {
                return true;
            }
        }

        return false;
    }

    function validarCombinacion() {
        const camposCompletos = campos.every(function (campo) {
            return campo.value.trim() !== "";
        });

        if (!camposCompletos) {
            for (let i = 0; i < campos.length; i++) {
                if (campos[i].value.trim() !== "") {
                    campos[i].classList.remove("is-invalid");
                    campos[i].classList.add("is-valid");
                }
            }
            return false;
        }

        const repetida = hayCombinacionRepetida();

        for (let i = 0; i < campos.length; i++) {
            campos[i].classList.toggle("is-invalid", repetida);
            campos[i].classList.toggle("is-valid", !repetida);
        }

        return repetida;
    }

    async function cargarUbicaciones() {
        try {
            const respuesta = await fetch(urlDao + "?opcion=consultar");
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const datos = await respuesta.json();
            if (Array.isArray(datos)) {
                ubicaciones = datos;
            }
        } catch (error) {
            console.error("Error al cargar las ubicaciones:", error);
        }
    }

    function cambiarAModoEdicion() {
        tituloFormulario.textContent = "Modificar ubicación";
        textoBotonGuardar.textContent = "Modificar ubicación";
    }

    function limpiarFormulario() {
        formulario.reset();

        for (let i = 0; i < campos.length; i++) {
            campos[i].classList.remove("is-valid", "is-invalid");
            ocultarLista(i);
        }
    }

    for (let i = 0; i < campos.length; i++) {
        const campo = campos[i];
        const lista = listasSugerencias[i];

        campo.addEventListener("input", function () {
            validarCampo(campo);
            validarCombinacion();
            mostrarSugerencias(campo, i);
        });

        campo.addEventListener("blur", function () {
            validarCampo(campo);
            setTimeout(function () {
                if (!campo.parentElement.contains(document.activeElement)) {
                    ocultarLista(i);
                }
            }, 150);
        });

        campo.addEventListener("keydown", function (evento) {
            if (evento.key === "Escape") ocultarLista(i);
            if (evento.key === "ArrowDown" && !lista.hidden) {
                evento.preventDefault();
                const primeraOpcion = lista.querySelector(".ubicacion-sugerencia");
                if (primeraOpcion) primeraOpcion.focus();
            }
        });

        lista.addEventListener("mousedown", function (evento) {
            if (evento.target.closest(".ubicacion-sugerencia")) {
                evento.preventDefault();
            }
        });

        lista.addEventListener("click", function (evento) {
            const opcion = evento.target.closest(".ubicacion-sugerencia");
            if (!opcion) return;

            campo.value = opcion.dataset.valor;
            validarCampo(campo);
            validarCombinacion();
            ocultarLista(i);
            campo.focus();
        });
    }

    document.addEventListener("click", function (evento) {
        const controlSeleccionado = evento.target.closest(".ubicacion-control");

        for (let i = 0; i < campos.length; i++) {
            if (!controlSeleccionado || !controlSeleccionado.contains(campos[i])) {
                ocultarLista(i);
            }
        }
    });

    botonCancelar.addEventListener("click", function () {
        window.location.href = "lista-ubicacion.php";
    });

    botonGuardar.addEventListener("click", async function () {
        for (let i = 0; i < campos.length; i++) {
            campos[i].value = campos[i].value.trim();
            validarCampo(campos[i]);
        }

        const campoVacio = campos.find(function (campo) {
            return campo.value.trim() === "";
        });

        if (campoVacio) {
            notificar("danger", "Completa todos los campos de la ubicación.");
            campoVacio.focus();
            return;
        }

        if (validarCombinacion()) {
            notificar("danger", "Ya existe una ubicación con esa combinación de bodega, pasillo, estante y nivel.");
            campos[0].focus();
            return;
        }

        const modificando = idUbicacionEditar !== null;
        const textoOriginal = textoBotonGuardar.textContent;

        try {
            const datos = new FormData(formulario);
            datos.append("opcion", modificando ? "modificar" : "guardar");
            if (modificando) datos.append("id_ubicacion", idUbicacionEditar);

            botonGuardar.disabled = true;
            textoBotonGuardar.textContent = "Procesando...";

            const respuesta = await fetch(urlDao, {
                method: "POST",
                body: datos
            });
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();

            if (!resultado.estado) {
                notificar("danger", resultado.mensaje || "No se pudo guardar la ubicación.");
                return;
            }

            notificar("success", resultado.mensaje);
            await cargarUbicaciones();

            if (!modificando) limpiarFormulario();
        } catch (error) {
            console.error("Error al guardar la ubicación:", error);
            notificar("danger", "No se pudo completar la operación.");
        } finally {
            botonGuardar.disabled = false;
            textoBotonGuardar.textContent = textoOriginal;
        }
    });

    await cargarUbicaciones();

    const parametros = new URLSearchParams(window.location.search);
    const id = parametros.get("id_ubicacion") || parametros.get("id");

    if (id) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarIndividual");
            datos.append("id_ubicacion", id);

            const respuesta = await fetch(urlDao, {
                method: "POST",
                body: datos
            });
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();

            if (!resultado.nivel) {
                notificar("danger", resultado.datos || "La ubicación no existe.");
                return;
            }

            campos[0].value = resultado.datos.bodega || "";
            campos[1].value = resultado.datos.pasillo || "";
            campos[2].value = resultado.datos.estante || "";
            campos[3].value = resultado.datos.nivel || "";
            idUbicacionEditar = resultado.datos.id_ubicacion;

            for (let i = 0; i < campos.length; i++) {
                validarCampo(campos[i]);
            }

            validarCombinacion();
            cambiarAModoEdicion();
        } catch (error) {
            console.error("Error al cargar la ubicación:", error);
            notificar("danger", "No se pudo cargar la ubicación.");
        }
    }
});
