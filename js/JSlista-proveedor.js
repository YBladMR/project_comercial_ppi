/*
 * Lista de proveedores con el mismo patrón visual de la lista de categorías:
 * resumen, filtros, ordenamiento, paginación e impresión.
 */
document.addEventListener("DOMContentLoaded", async () => {
    const cuerpo = document.getElementById("cuerpo-tabla");
    if (!cuerpo) return;

    const tablaWrap = document.getElementById("tabla-wrap");
    const encabezados = document.querySelectorAll("#tabla-proveedores th[data-orden]");
    const buscador = document.getElementById("table-filter");
    const chipsEstado = document.querySelectorAll(".lp-chip[data-estado]");
    const chkJuridicos = document.getElementById("solo-juridicos");
    const btnFiltros = document.getElementById("btnFiltros");
    const panelFiltros = document.getElementById("panelFiltros");
    const btnLimpiar = document.getElementById("limpiar-filtros");
    const btnImprimir = document.getElementById("print-table");
    const selTam = document.getElementById("tam-pagina");
    const pager = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const vacio = document.getElementById("estado-vacio");
    const modalDetalleEl = document.getElementById("modalDetalleProveedor");
    const contenidoDetalle = document.getElementById("contenidoDetalleProveedor");
    const modalDetalle = typeof coreui !== "undefined" && coreui.Modal
        ? coreui.Modal.getOrCreateInstance(modalDetalleEl)
        : null;

    let proveedores = [];
    let cargando = true;
    let errorCarga = false;
    let estado = "";
    let soloJuridicos = false;
    let texto = "";
    let orden = { campo: "nombre_razon_social", dir: 1 };
    let pagina = 1;
    let tam = Number(selTam.value) || 10;

    function esc(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (c) => (
            { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
        ));
    }

    function normalizar(proveedor) {
        return {
            ...proveedor,
            id_proveedor: String(proveedor.id_proveedor ?? ""),
            nombre_razon_social: String(proveedor.nombre_razon_social ?? ""),
            tipo_persona: String(proveedor.tipo_persona ?? "").toLowerCase(),
            dui: String(proveedor.dui ?? ""),
            nit: String(proveedor.nit ?? ""),
            telefono: String(proveedor.telefono ?? ""),
            correo_electronico: String(proveedor.correo_electronico ?? ""),
            direccion: String(proveedor.direccion ?? ""),
            fecha_registro: String(proveedor.fecha_registro ?? ""),
            estado: String(proveedor.estado ?? "").toLowerCase()
        };
    }

    function fechaOrden(valor) {
        const partes = String(valor ?? "").split("-");
        if (partes.length === 3 && partes[2].length === 4) {
            const fecha = new Date(Number(partes[2]), Number(partes[1]) - 1, Number(partes[0]));
            return fecha.getTime() || 0;
        }
        const fecha = new Date(valor).getTime();
        return Number.isNaN(fecha) ? 0 : fecha;
    }

    function filtrar() {
        const q = texto.trim().toLocaleLowerCase("es");
        const lista = proveedores.filter((p) => {
            if (estado && p.estado !== estado) return false;
            if (soloJuridicos && p.tipo_persona !== "juridica") return false;
            if (!q) return true;
            return [p.nombre_razon_social, p.tipo_persona, p.dui, p.nit, p.telefono,
                p.correo_electronico, p.direccion, p.estado, p.fecha_registro]
                .some((valor) => String(valor ?? "").toLocaleLowerCase("es").includes(q));
        });

        lista.sort((a, b) => {
            const x = orden.campo === "fecha_registro"
                ? fechaOrden(a[orden.campo])
                : String(a[orden.campo] ?? "").toLocaleLowerCase("es");
            const y = orden.campo === "fecha_registro"
                ? fechaOrden(b[orden.campo])
                : String(b[orden.campo] ?? "").toLocaleLowerCase("es");
            return (x < y ? -1 : x > y ? 1 : 0) * orden.dir;
        });
        return lista;
    }

    function hayFiltros() {
        return estado !== "" || soloJuridicos || texto.trim() !== "";
    }

    function dibujarResumen() {
        const activos = proveedores.filter((p) => p.estado === "activo").length;
        const poner = (id, valor) => {
            const elemento = document.getElementById(id);
            if (elemento) elemento.textContent = valor;
        };
        poner("stat-total", proveedores.length);
        poner("stat-activas", activos);
        poner("stat-inactivas", proveedores.length - activos);
    }

    function dibujarChips() {
        chipsEstado.forEach((chip) => chip.classList.toggle("activo", chip.dataset.estado === estado));
        chkJuridicos.checked = soloJuridicos;
    }

    function dibujarEncabezados() {
        encabezados.forEach((th) => {
            th.querySelector(".bi")?.remove();
            if (th.dataset.orden === orden.campo) {
                th.insertAdjacentHTML("beforeend",
                    `<i class="bi ${orden.dir === 1 ? "bi-caret-up-fill" : "bi-caret-down-fill"}" aria-hidden="true"></i>`);
            }
        });
    }

    function identidadHTML(p) {
        const datos = [];
        if (p.dui) datos.push(`<div><strong>DUI</strong> ${esc(p.dui)}</div>`);
        if (p.nit) datos.push(`<div><strong>NIT</strong> ${esc(p.nit)}</div>`);
        return datos.length ? datos.join("") : '<span class="lp-sub">Sin identificación</span>';
    }

    function contactoHTML(p) {
        const datos = [];
        if (p.telefono) datos.push(`<div><i class="bi bi-telephone me-1" aria-hidden="true"></i>${esc(p.telefono)}</div>`);
        if (p.correo_electronico) datos.push(`<div><i class="bi bi-envelope me-1" aria-hidden="true"></i>${esc(p.correo_electronico)}</div>`);
        return datos.length ? datos.join("") : '<span class="lp-sub">Sin datos de contacto</span>';
    }

    function verDetalles(proveedor) {
        const detalles = [
            ["Tipo de persona", proveedor.tipo_persona === "juridica" ? "Persona jurídica" : "Persona natural"],
            ["DUI", proveedor.dui],
            ["NIT", proveedor.nit],
            ["Teléfono", proveedor.telefono],
            ["Correo electrónico", proveedor.correo_electronico],
            ["Dirección", proveedor.direccion],
            ["Fecha de registro", proveedor.fecha_registro],
            ["Estado", proveedor.estado === "activo" ? "Activo" : "Inactivo"]
        ];

        document.getElementById("tituloDetalleProveedor").textContent = proveedor.nombre_razon_social;
        contenidoDetalle.innerHTML = `<div class="row g-3">${detalles.map(([etiqueta, valor]) => `
            <div class="col-sm-6">
                <div class="rounded border p-3 h-100">
                    <div class="small text-body-secondary mb-1">${esc(etiqueta)}</div>
                    <div class="fw-semibold text-break">${esc(valor || "No registrado")}</div>
                </div>
            </div>`).join("")}</div>`;
        modalDetalle?.show();
    }

    function filaHTML(p) {
        const activo = p.estado === "activo";
        const tipo = p.tipo_persona === "juridica" ? "Persona jurídica" : "Persona natural";

        return `
        <tr class="${activo ? "" : "lp-baja"}" data-id="${esc(p.id_proveedor)}">
            <td>
                <div class="lp-proveedor">
                    <span class="lp-proveedor-ico"><i class="bi ${p.tipo_persona === "juridica" ? "bi-buildings" : "bi-person-vcard"}" aria-hidden="true"></i></span>
                    <span>
                        <span class="lp-proveedor-nom d-block">${esc(p.nombre_razon_social)}</span>
                    </span>
                </div>
            </td>
            <td><span class="lp-tipo">${esc(tipo)}</span></td>
            <td>${esc(p.fecha_registro || "—")}</td>
            <td class="lp-centro"><span class="lp-pill ${activo ? "activo" : "inactivo"}">${activo ? "Activo" : "Inactivo"}</span></td>
            <td class="lp-centro lp-col-acc">
                <span class="lp-acc">
                    <button type="button" class="lp-ib btn-detalles" title="Ver detalles" aria-label="Ver detalles de ${esc(p.nombre_razon_social)}">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    <button type="button" class="lp-ib btn-editar" title="Modificar proveedor" aria-label="Modificar ${esc(p.nombre_razon_social)}">
                        <i class="bi bi-pencil" aria-hidden="true"></i>
                    </button>
                    ${activo ? `<button type="button" class="lp-ib baja btn-estado" title="Dar de baja" aria-label="Dar de baja a ${esc(p.nombre_razon_social)}"><i class="bi bi-slash-circle" aria-hidden="true"></i></button>` : ""}
                </span>
            </td>
        </tr>`;
    }

    function dibujarVacio(hayResultadosConFiltros) {
        const titulo = document.getElementById("vacio-titulo");
        const detalle = document.getElementById("vacio-texto");
        const accion = document.getElementById("vacio-accion");

        if (cargando) {
            titulo.textContent = "Cargando proveedores...";
            detalle.textContent = "Un momento por favor.";
            accion.hidden = true;
            return;
        }

        if (errorCarga) {
            titulo.textContent = "No se pudo cargar la lista";
            detalle.textContent = "Intenta recargar la página dentro de un momento.";
            accion.hidden = true;
            return;
        }

        accion.hidden = false;
        if (hayResultadosConFiltros) {
            titulo.textContent = "Sin resultados";
            detalle.textContent = "Ningún proveedor coincide con los filtros aplicados.";
            if (!(accion instanceof HTMLButtonElement)) {
                accion.outerHTML = '<button type="button" id="vacio-accion" class="lp-btn"><i class="bi bi-x-circle" aria-hidden="true"></i> Limpiar filtros</button>';
            }
        } else {
            titulo.textContent = "Aún no hay proveedores";
            detalle.textContent = "Registra tus contactos comerciales para tenerlos organizados en un solo lugar.";
            if (!(accion instanceof HTMLAnchorElement)) {
                accion.outerHTML = '<a id="vacio-accion" href="proveedor.php" class="lp-btn"><i class="bi bi-plus-lg" aria-hidden="true"></i> Registrar proveedor</a>';
            }
        }
    }

    function dibujarPaginador(totalPaginas) {
        const boton = (textoBoton, ir, extra = "", deshabilitado = false) =>
            `<button type="button" data-ir="${ir}" class="${extra}" ${deshabilitado ? "disabled" : ""}>${textoBoton}</button>`;

        let html = boton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', pagina - 1, "", pagina <= 1);
        const visibles = [];
        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || Math.abs(i - pagina) <= 1) visibles.push(i);
        }
        let anterior = 0;
        visibles.forEach((i) => {
            if (i - anterior > 1) html += '<button type="button" disabled aria-label="Páginas omitidas">…</button>';
            html += boton(i, i, i === pagina ? "activo" : "");
            anterior = i;
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
        if (sinFilas) dibujarVacio(proveedores.length > 0 && hayFiltros());

        contador.textContent = `Total de Proveedores: ${lista.length}`;
        dibujarEncabezados();
        dibujarChips();
        dibujarPaginador(totalPaginas);
    }

    function cerrarFiltros() {
        panelFiltros.hidden = true;
        btnFiltros.setAttribute("aria-expanded", "false");
    }

    function limpiarFiltros() {
        estado = "";
        soloJuridicos = false;
        texto = "";
        buscador.value = "";
        pagina = 1;
        dibujar();
        cerrarFiltros();
    }

    chipsEstado.forEach((chip) => chip.addEventListener("click", () => {
        estado = chip.dataset.estado;
        pagina = 1;
        dibujar();
    }));

    document.querySelectorAll(".lp-stat[data-atajo]").forEach((tarjeta) => {
        tarjeta.addEventListener("click", () => {
            estado = tarjeta.dataset.atajo === "todos" ? "" : tarjeta.dataset.atajo;
            soloJuridicos = false;
            texto = "";
            buscador.value = "";
            pagina = 1;
            dibujar();
        });
    });

    chkJuridicos.addEventListener("change", () => {
        soloJuridicos = chkJuridicos.checked;
        pagina = 1;
        dibujar();
    });
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
    btnLimpiar.addEventListener("click", limpiarFiltros);
    vacio.addEventListener("click", (evento) => {
        if (evento.target.closest("button#vacio-accion")) limpiarFiltros();
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

    btnFiltros.addEventListener("click", (evento) => {
        evento.stopPropagation();
        panelFiltros.hidden = !panelFiltros.hidden;
        btnFiltros.setAttribute("aria-expanded", String(!panelFiltros.hidden));
    });
    panelFiltros.addEventListener("click", (evento) => evento.stopPropagation());
    document.addEventListener("click", cerrarFiltros);
    document.addEventListener("keydown", (evento) => {
        if (evento.key === "Escape") cerrarFiltros();
    });

    cuerpo.addEventListener("click", async (evento) => {
        const fila = evento.target.closest("tr[data-id]");
        if (!fila) return;
        const proveedor = proveedores.find((p) => p.id_proveedor === fila.dataset.id);
        if (!proveedor) return;

        if (evento.target.closest(".btn-detalles")) {
            verDetalles(proveedor);
        } else if (evento.target.closest(".btn-editar")) {
            location.href = "proveedor.php?id=" + encodeURIComponent(proveedor.id_proveedor);
        } else if (evento.target.closest(".btn-estado")) {
            await darDeBaja(proveedor);
        }
    });

    async function darDeBaja(proveedor) {
        const confirmado = await confirmarAccion(
            `¿Confirmas dar de baja a "${proveedor.nombre_razon_social}"?`,
            "Sí, dar de baja",
            "danger"
        );
        if (!confirmado) return;

        try {
            const datos = new FormData();
            datos.append("idproveedor", proveedor.id_proveedor);
            datos.append("opcion", "baja");

            const respuesta = await fetch("../dao/DAOProveedor.php", { method: "POST", body: datos });
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);
            const resultado = await respuesta.json();
            if (!resultado.estado) {
                notificar("danger", resultado.mensaje || "No se pudo dar de baja al proveedor");
                return;
            }

            notificar("success", resultado.mensaje || "Proveedor dado de baja");
            await cargarDatos();
        } catch (error) {
            console.error("Error al dar de baja al proveedor:", error);
            notificar("danger", "No se pudo procesar la solicitud");
        }
    }

    btnImprimir.addEventListener("click", () => {
        cerrarFiltros();
        const tamOriginal = tam;
        const paginaOriginal = pagina;
        tam = Math.max(1, filtrar().length);
        pagina = 1;
        dibujar();
        window.addEventListener("afterprint", () => {
            tam = tamOriginal;
            pagina = paginaOriginal;
            selTam.value = String(tamOriginal);
            dibujar();
        }, { once: true });
        window.print();
    });

    async function cargarDatos() {
        cargando = true;
        errorCarga = false;
        dibujar();
        try {
            const datos = new FormData();
            datos.append("opcion", "consultar");
            const respuesta = await fetch("../dao/DAOProveedor.php", { method: "POST", body: datos });
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);
            const resultado = await respuesta.json();
            if (!Array.isArray(resultado)) {
                throw new Error(resultado.mensaje || "La respuesta recibida no es válida");
            }

            proveedores = resultado.map(normalizar);
            cargando = false;
            dibujarResumen();
            dibujar();
        } catch (error) {
            console.error("Error al cargar los proveedores:", error);
            proveedores = [];
            cargando = false;
            errorCarga = true;
            dibujarResumen();
            dibujar();
            notificar("danger", "No se pudieron cargar los proveedores");
        }
    }

    dibujar();
    await cargarDatos();
});
