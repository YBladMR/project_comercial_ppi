document.addEventListener("DOMContentLoaded", async () => {
    const cuerpo = document.getElementById("cuerpo-tabla");
    if (!cuerpo) return;

    const tablaWrap = document.getElementById("tabla-wrap");
    const encabezados = document.querySelectorAll("#tabla-ubicaciones th[data-orden]");
    const buscador = document.getElementById("table-filter");
    const btnFiltros = document.getElementById("btnFiltros");
    const panelFiltros = document.getElementById("panelFiltros");
    const btnLimpiar = document.getElementById("limpiar-filtros");
    const btnImprimir = document.getElementById("print-table");
    const selTam = document.getElementById("tam-pagina");
    const pager = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const vacio = document.getElementById("estado-vacio");
    const accionAgregar = document.getElementById("vacio-agregar");
    const accionLimpiar = document.getElementById("vacio-limpiar");

    let ubicaciones = [];
    let cargando = true;
    let errorCarga = false;
    let texto = "";
    let orden = { campo: "bodega", dir: 1 };
    let pagina = 1;
    let tam = Number(selTam.value) || 10;

    function esc(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (caracter) => ({
            "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
        })[caracter]);
    }

    function filtrar() {
        const consulta = texto.trim().toLocaleLowerCase("es");
        const lista = ubicaciones.filter((ubicacion) => {
            if (!consulta) return true;
            return [ubicacion.bodega, ubicacion.pasillo, ubicacion.estante, ubicacion.nivel]
                .some((valor) => String(valor ?? "").toLocaleLowerCase("es").includes(consulta));
        });

        lista.sort((a, b) => String(a[orden.campo] ?? "").localeCompare(
            String(b[orden.campo] ?? ""), "es", { numeric: true, sensitivity: "base" }
        ) * orden.dir);
        return lista;
    }

    function filaHTML(ubicacion) {
        return `
        <tr data-id="${esc(ubicacion.id_ubicacion)}">
            <td>
                <div class="lp-prod">
                    <span class="lp-prod-ico"><i class="bi bi-building" aria-hidden="true"></i></span>
                    <span>
                        <span class="lp-prod-nom d-block">${esc(ubicacion.bodega)}</span>
                        <span class="lp-prod-sub"></span>
                    </span>
                </div>
            </td>
            <td>${esc(ubicacion.pasillo)}</td>
            <td>${esc(ubicacion.estante)}</td>
            <td>${esc(ubicacion.nivel)}</td>
            <td class="lp-centro lp-col-acc">
                <span class="lp-acc">
                    <a class="lp-ib" href="ubicacion.php?id_ubicacion=${encodeURIComponent(ubicacion.id_ubicacion)}" title="Modificar ubicación" aria-label="Modificar ubicación ${esc(ubicacion.bodega)}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="lp-ib btn-eliminar eliminar" title="Eliminar ubicación" aria-label="Eliminar ubicación ${esc(ubicacion.bodega)}">
                        <i class="bi bi-trash" aria-hidden="true"></i>
                    </button>
                </span>
            </td>
        </tr>`;
    }

    function dibujarVacio(hayResultados) {
        const titulo = document.getElementById("vacio-titulo");
        const detalle = document.getElementById("vacio-texto");

        accionAgregar.hidden = true;
        accionLimpiar.hidden = true;

        if (cargando) {
            titulo.textContent = "Cargando ubicaciones...";
            detalle.textContent = "Un momento, por favor.";
        } else if (errorCarga) {
            titulo.textContent = "No se pudieron cargar las ubicaciones";
            detalle.textContent = "Intenta recargar la página.";
        } else if (hayResultados) {
            titulo.textContent = "Sin resultados";
            detalle.textContent = "Ninguna ubicación coincide con la búsqueda.";
            accionLimpiar.hidden = false;
        } else {
            titulo.textContent = "No hay ubicaciones para mostrar";
            detalle.textContent = "Aún no se han registrado ubicaciones. Puedes agregar una nueva ubicación.";
            accionAgregar.hidden = false;
        }
    }

    function dibujarEncabezados() {
        encabezados.forEach((th) => {
            th.querySelector(".bi")?.remove();
            if (th.dataset.orden === orden.campo) {
                th.insertAdjacentHTML("beforeend", `<i class="bi ${orden.dir === 1 ? "bi-caret-up-fill" : "bi-caret-down-fill"}" aria-hidden="true"></i>`);
            }
        });
    }

    function dibujarPaginador(totalPaginas) {
        const boton = (textoBoton, ir, clase = "", deshabilitado = false) =>
            `<button type="button" data-ir="${ir}" class="${clase}" ${deshabilitado ? "disabled" : ""}>${textoBoton}</button>`;
        let html = boton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', pagina - 1, "", pagina <= 1);
        const paginasVisibles = [];

        for (let indice = 1; indice <= totalPaginas; indice++) {
            if (indice === 1 || indice === totalPaginas || Math.abs(indice - pagina) <= 1) paginasVisibles.push(indice);
        }

        let anterior = 0;
        paginasVisibles.forEach((indice) => {
            if (indice - anterior > 1) html += '<button type="button" disabled aria-label="Páginas omitidas">…</button>';
            html += boton(indice, indice, indice === pagina ? "activo" : "");
            anterior = indice;
        });

        html += boton('<i class="bi bi-chevron-right" aria-hidden="true"></i>', pagina + 1, "", pagina >= totalPaginas);
        pager.innerHTML = html;
    }

    function dibujar() {
        const lista = filtrar();
        const totalPaginas = Math.max(1, Math.ceil(lista.length / tam));
        if (pagina > totalPaginas) pagina = totalPaginas;
        const visibles = lista.slice((pagina - 1) * tam, pagina * tam);

        cuerpo.innerHTML = visibles.map(filaHTML).join("");
        const sinFilas = visibles.length === 0;
        tablaWrap.hidden = sinFilas;
        vacio.hidden = !sinFilas;
        if (sinFilas) dibujarVacio(ubicaciones.length > 0 && texto.trim() !== "");

        contador.textContent = `Total de ubicaciones: ${lista.length}`;
        dibujarEncabezados();
        dibujarPaginador(totalPaginas);
    }

    function limpiarFiltros() {
        texto = "";
        buscador.value = "";
        pagina = 1;
        dibujar();
    }

    function cerrarFiltros() {
        panelFiltros.hidden = true;
        btnFiltros.setAttribute("aria-expanded", "false");
    }

    buscador.addEventListener("input", () => {
        texto = buscador.value;
        pagina = 1;
        dibujar();
    });
    selTam.addEventListener("change", () => {
        tam = Number(selTam.value) || 10;
        pagina = 1;
        dibujar();
    });
    encabezados.forEach((th) => th.addEventListener("click", () => {
        const campo = th.dataset.orden;
        orden = { campo, dir: orden.campo === campo ? -orden.dir : 1 };
        pagina = 1;
        dibujar();
    }));
    pager.addEventListener("click", (evento) => {
        const boton = evento.target.closest("button[data-ir]");
        if (!boton || boton.disabled) return;
        pagina = Number(boton.dataset.ir);
        dibujar();
    });
    btnLimpiar.addEventListener("click", () => { limpiarFiltros(); cerrarFiltros(); });
    accionLimpiar.addEventListener("click", limpiarFiltros);
    btnFiltros.addEventListener("click", (evento) => {
        evento.stopPropagation();
        panelFiltros.hidden = !panelFiltros.hidden;
        btnFiltros.setAttribute("aria-expanded", String(!panelFiltros.hidden));
    });
    panelFiltros.addEventListener("click", (evento) => evento.stopPropagation());
    document.addEventListener("click", cerrarFiltros);
    document.addEventListener("keydown", (evento) => { if (evento.key === "Escape") cerrarFiltros(); });
    btnImprimir.addEventListener("click", () => { cerrarFiltros(); window.print(); });

    cuerpo.addEventListener("click", async (evento) => {
        const botonEliminar = evento.target.closest(".btn-eliminar");
        if (!botonEliminar) return;

        const fila = botonEliminar.closest("tr[data-id]");
        const ubicacion = ubicaciones.find((item) => String(item.id_ubicacion) === fila?.dataset.id);
        if (!ubicacion) return;

        const confirmar = await confirmarAccion(
            `¿Confirma eliminar la ubicación de bodega "${ubicacion.bodega}"? Si tiene productos asociados en el inventario no se podrá eliminar.`,
            "Sí, eliminar",
            "danger"
        );
        if (!confirmar) return;

        botonEliminar.disabled = true;
        try {
            const datos = new FormData();
            datos.append("opcion", "eliminar");
            datos.append("id_ubicacion", ubicacion.id_ubicacion);

            const respuesta = await fetch("../dao/DAOubicacion.php", { method: "POST", body: datos });
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);
            const resultado = await respuesta.json();
            if (!resultado.estado) {
                notificar("danger", resultado.mensaje || "No se pudo eliminar la ubicación.");
                return;
            }

            notificar("success", resultado.mensaje || "La ubicación se eliminó correctamente.");
            await cargarDatos();
        } catch (error) {
            console.error("Error al eliminar la ubicación:", error);
            notificar("danger", "No se pudo eliminar la ubicación.");
        } finally {
            botonEliminar.disabled = false;
        }
    });

    async function cargarDatos() {
        cargando = true;
        errorCarga = false;
        dibujar();

        try {
            const respuesta = await fetch("../dao/DAOubicacion.php?opcion=consultar", {
                headers: { Accept: "application/json" }
            });
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);
            const resultado = await respuesta.json();
            if (!Array.isArray(resultado)) throw new Error(resultado.mensaje || "Respuesta no válida");
            ubicaciones = resultado;
            cargando = false;
            dibujar();
        } catch (error) {
            console.error("Error al cargar ubicaciones:", error);
            ubicaciones = [];
            cargando = false;
            errorCarga = true;
            dibujar();
            notificar("danger", "No se pudieron cargar las ubicaciones.");
        }
    }

    await cargarDatos();
});
