/* ============================================================
 * JSLista_Ventas.js
 * Lista de ventas con diseño propio (sin librerías de tablas):
 * filtros por código, cliente, fecha, tipo y estado, orden,
 * paginación, ver detalle y anular venta.
 * ============================================================ */

document.addEventListener("DOMContentLoaded", async () => {
    const cuerpo = document.getElementById("cuerpo-tabla");
    if (!cuerpo) return;

    const URL_DAO = "../dao/DAOVentas.php";

    const tablaWrap = document.getElementById("tabla-wrap");
    const encabezados = document.querySelectorAll("#tabla-ventas th[data-orden]");
    const buscador = document.getElementById("table-filter");
    const chipsEstado = document.querySelectorAll(".lp-chip[data-estado]");
    const inpDesde = document.getElementById("fecha-desde");
    const inpHasta = document.getElementById("fecha-hasta");
    const selTipo = document.getElementById("filtro-tipo");
    const btnFiltros = document.getElementById("btnFiltros");
    const panelFiltros = document.getElementById("panelFiltros");
    const btnLimpiar = document.getElementById("limpiar-filtros");
    const btnImprimir = document.getElementById("print-table");
    const selTam = document.getElementById("tam-pagina");
    const pager = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const vacio = document.getElementById("estado-vacio");
    const puedeAnular = document.body.dataset.puedeAnular === "1";
    const puedeRegistrar = document.body.dataset.puedeRegistrar === "1";

    const modalEl = document.getElementById("modalDetalle");
    const modalDetalle = coreui.Modal.getOrCreateInstance(modalEl);

    // Estado de la pantalla
    let ventas = [];
    let cargando = true;
    let estado = "";            // "", "pagado", "pendiente", "anulado"
    let texto = "";
    let desde = "";             // YYYY-MM-DD
    let hasta = "";
    let tipo = "";              // "", "contado", "credito"
    let orden = { campo: "fecha_hora", dir: -1 };   // la más reciente primero
    let pagina = 1;
    let tam = Number(selTam.value);

    const CAMPOS_NUMERICOS = ["id_venta", "subtotal", "iva_debito", "total"];

    /* ---------------------- UTILIDADES ---------------------- */

    function esc(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (c) => (
            { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
        ));
    }

    const dinero = (n) => "$" + Number(n).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const codigo = (id) => "V-" + String(id).padStart(6, "0");

    // "2026-11-18 14:35:00" -> "18/11/2026 14:35"
    function fechaBonita(valor) {
        const m = String(valor ?? "").match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/);
        return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : esc(valor);
    }

    const tipoTexto = (t) => (t === "credito" ? "Crédito" : "Contado");
    const estadoTexto = { pagado: "Pagada", pendiente: "Pendiente", anulado: "Anulada" };

    /* ---------------------- FILTRADO Y ORDEN ---------------------- */

    function filtrar() {
        const q = texto.trim().toLowerCase();

        const lista = ventas.filter((v) => {
            if (estado && v.estado !== estado) return false;
            if (tipo && v.tipo_venta !== tipo) return false;

            const dia = String(v.fecha_hora).substring(0, 10);
            if (desde && dia < desde) return false;
            if (hasta && dia > hasta) return false;

            if (!q) return true;
            // Se busca por código (V-000012 o solo 12), cliente o DUI
            return [codigo(v.id_venta), String(v.id_venta), v.cliente, v.dui]
                .some((x) => String(x ?? "").toLowerCase().includes(q));
        });

        const numerico = CAMPOS_NUMERICOS.includes(orden.campo);
        lista.sort((a, b) => {
            const x = numerico ? Number(a[orden.campo]) : String(a[orden.campo] ?? "").toLowerCase();
            const y = numerico ? Number(b[orden.campo]) : String(b[orden.campo] ?? "").toLowerCase();
            return (x < y ? -1 : x > y ? 1 : 0) * orden.dir;
        });
        return lista;
    }

    function hayFiltros() {
        return estado !== "" || tipo !== "" || desde !== "" || hasta !== "" || texto.trim() !== "";
    }

    /* ---------------------- DIBUJO ---------------------- */

    function dibujarChips() {
        chipsEstado.forEach((c) => c.classList.toggle("activo", c.dataset.estado === estado));
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

    function filaHTML(v) {
        const anulada = v.estado === "anulado";
        const clasePill = v.estado === "pagado" ? "activo" : v.estado === "pendiente" ? "pendiente" : "inactivo";

        return `
        <tr class="${anulada ? "lp-baja" : ""}" data-id="${esc(v.id_venta)}">
            <td>
                <div class="lp-prod">
                    <span class="lp-prod-ico"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                    <div>
                        <div class="lp-prod-nom">${esc(codigo(v.id_venta))}</div>
                        <div class="lp-prod-sub">${v.usuario ? "Vendedor: " + esc(v.usuario) : "&nbsp;"}</div>
                    </div>
                </div>
            </td>
            <td>${fechaBonita(v.fecha_hora)}</td>
            <td>
                <div>${esc(v.cliente)}</div>
                <div class="lp-prod-sub">${esc(v.dui)}</div>
            </td>
            <td class="lp-centro"><span class="lp-cat">${tipoTexto(v.tipo_venta)}</span></td>
            <td class="lp-num">${dinero(v.subtotal)}</td>
            <td class="lp-num">${dinero(v.iva_debito)}</td>
            <td class="lp-num"><strong>${dinero(v.total)}</strong></td>
            <td class="lp-centro"><span class="lp-pill ${clasePill}">${estadoTexto[v.estado] ?? esc(v.estado)}</span></td>
            <td class="lp-centro lp-col-acc">
                <span class="lp-acc">
                    <button type="button" class="lp-ib btn-detalle" title="Ver detalle" aria-label="Ver detalle">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                    ${anulada || !puedeAnular ? "" : `
                    <button type="button" class="lp-ib baja btn-anular" title="Anular venta" aria-label="Anular venta">
                        <i class="bi bi-slash-circle" aria-hidden="true"></i>
                    </button>`}
                </span>
            </td>
        </tr>`;
    }

    function dibujarVacio(hayDatos) {
        const titulo = document.getElementById("vacio-titulo");
        const detalle = document.getElementById("vacio-texto");
        const accion = document.getElementById("vacio-accion");

        if (cargando) {
            titulo.textContent = "Cargando ventas...";
            detalle.textContent = "Un momento por favor.";
            accion.hidden = true;
            return;
        }

        if (hayDatos) {
            titulo.textContent = "Sin resultados";
            detalle.textContent = "Ninguna venta coincide con los filtros aplicados.";
            accion.outerHTML = '<button type="button" id="vacio-accion" class="lp-btn"><i class="bi bi-x-circle"></i> Limpiar filtros</button>';
        } else {
            titulo.textContent = "No hay ventas para mostrar";
            detalle.textContent = "Aún no se han registrado ventas en el sistema. Puedes registrar una nueva venta o ajustar tus filtros.";
            if (puedeRegistrar) {
                accion.outerHTML = '<a id="vacio-accion" href="Ventas.php" class="lp-btn"><i class="bi bi-plus-lg"></i> Registrar Venta</a>';
            } else {
                accion.hidden = true;
            }
        }
    }

    function dibujarPaginador(totalPaginas) {
        const boton = (txt, ir, extra = "", deshabilitado = false) =>
            `<button type="button" data-ir="${ir}" class="${extra}" ${deshabilitado ? "disabled" : ""}>${txt}</button>`;

        let html = boton('<i class="bi bi-chevron-left"></i>', pagina - 1, "", pagina <= 1);

        const visibles = [];
        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || Math.abs(i - pagina) <= 1) visibles.push(i);
        }
        let anterior = 0;
        visibles.forEach((i) => {
            if (i - anterior > 1) html += '<button type="button" disabled>…</button>';
            html += boton(i, i, i === pagina ? "activo" : "");
            anterior = i;
        });

        html += boton('<i class="bi bi-chevron-right"></i>', pagina + 1, "", pagina >= totalPaginas);
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
        if (sinFilas) dibujarVacio(ventas.length > 0 && hayFiltros());

        contador.textContent = `Total de Registros: ${lista.length}`;
        dibujarEncabezados();
        dibujarChips();
        dibujarPaginador(totalPaginas);
    }

    /* ---------------------- EVENTOS DE FILTROS ---------------------- */

    function limpiarFiltros() {
        estado = "";
        tipo = "";
        desde = "";
        hasta = "";
        texto = "";
        buscador.value = "";
        inpDesde.value = "";
        inpHasta.value = "";
        selTipo.value = "";
        pagina = 1;
        dibujar();
    }

    chipsEstado.forEach((chip) => chip.addEventListener("click", () => {
        estado = chip.dataset.estado;
        pagina = 1;
        dibujar();
    }));

    buscador.addEventListener("input", () => { texto = buscador.value; pagina = 1; dibujar(); });
    inpDesde.addEventListener("change", () => { desde = inpDesde.value; pagina = 1; dibujar(); });
    inpHasta.addEventListener("change", () => { hasta = inpHasta.value; pagina = 1; dibujar(); });
    selTipo.addEventListener("change", () => { tipo = selTipo.value; pagina = 1; dibujar(); });
    selTam.addEventListener("change", () => { tam = Number(selTam.value); pagina = 1; dibujar(); });
    btnLimpiar.addEventListener("click", () => { limpiarFiltros(); cerrarFiltros(); });

    vacio.addEventListener("click", (e) => {
        if (e.target.closest("button#vacio-accion")) limpiarFiltros();
    });

    encabezados.forEach((th) => th.addEventListener("click", () => {
        const campo = th.dataset.orden;
        orden = { campo, dir: orden.campo === campo ? -orden.dir : 1 };
        pagina = 1;
        dibujar();
    }));

    pager.addEventListener("click", (e) => {
        const b = e.target.closest("button[data-ir]");
        if (!b || b.disabled) return;
        pagina = Number(b.dataset.ir);
        dibujar();
    });

    // Menú "Filtros"
    function cerrarFiltros() {
        panelFiltros.hidden = true;
        btnFiltros.setAttribute("aria-expanded", "false");
    }
    btnFiltros.addEventListener("click", (e) => {
        e.stopPropagation();
        panelFiltros.hidden = !panelFiltros.hidden;
        btnFiltros.setAttribute("aria-expanded", String(!panelFiltros.hidden));
    });
    panelFiltros.addEventListener("click", (e) => e.stopPropagation());
    document.addEventListener("click", cerrarFiltros);
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") cerrarFiltros(); });

    btnImprimir.addEventListener("click", () => {
        cerrarFiltros();
        window.print();
    });

    /* ---------------------- ACCIONES DE LA FILA ---------------------- */

    cuerpo.addEventListener("click", (e) => {
        const fila = e.target.closest("tr[data-id]");
        if (!fila) return;
        const venta = ventas.find((v) => String(v.id_venta) === fila.dataset.id);
        if (!venta) return;

        if (e.target.closest(".btn-detalle")) verDetalle(venta);
        if (e.target.closest(".btn-anular")) anularVenta(venta);
    });

    /* ---------------------- DETALLE DE LA VENTA ---------------------- */

    modalEl.querySelectorAll("[data-cerrar]").forEach((b) => b.addEventListener("click", () => modalDetalle.hide()));

    async function verDetalle(venta) {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarDetalle");
            datos.append("id_venta", venta.id_venta);

            const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }
            const r = await respuesta.json();
            if (!r.estado) {
                notificar("danger", r.mensaje);
                return;
            }

            const v = r.venta;
            document.getElementById("detalleTitulo").textContent = "Detalle de la venta " + codigo(v.id_venta);
            document.getElementById("detalle-cabecera").innerHTML = `
                <div><small>Fecha</small><strong>${fechaBonita(v.fecha_hora)}</strong></div>
                <div><small>Cliente</small><strong>${esc(v.cliente)}</strong></div>
                <div><small>DUI</small><strong>${esc(v.dui)}</strong></div>
                <div><small>Tipo de venta</small><strong>${tipoTexto(v.tipo_venta)}</strong></div>
                <div><small>Estado</small><strong>${estadoTexto[v.estado] ?? esc(v.estado)}</strong></div>
                <div><small>Vendedor</small><strong>${esc(v.usuario) || "—"}</strong></div>`;

            document.getElementById("detalle-cuerpo").innerHTML = r.detalle.map((d) => `
                <tr>
                    <td>${esc(d.nombre)}</td>
                    <td class="text-end">${dinero(d.precio_unitario)}</td>
                    <td class="text-end">${esc(d.cantidad)}</td>
                    <td class="text-end">${dinero(d.subtotal)}</td>
                </tr>`).join("");

            document.getElementById("detalle-subtotal").textContent = dinero(v.subtotal);
            document.getElementById("detalle-iva").textContent = dinero(v.iva_debito);
            document.getElementById("detalle-total").textContent = dinero(v.total);

            modalDetalle.show();
        } catch (error) {
            console.error("Error al consultar el detalle:", error);
            notificar("danger", "No se pudo cargar el detalle de la venta");
        }
    }

    /* ---------------------- ANULAR VENTA ---------------------- */

    async function anularVenta(venta) {
        const confirmado = await confirmarAccion(
            `¿Confirma anular la venta ${codigo(venta.id_venta)} de ${venta.cliente} por ${dinero(venta.total)}? ` +
            "Se cancelará completa y los productos regresarán al inventario.",
            "Sí, anular",
            "danger"
        );
        if (!confirmado) return;

        try {
            const datos = new FormData();
            datos.append("opcion", "anular");
            datos.append("id_venta", venta.id_venta);

            const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }
            const resultado = await respuesta.json();

            if (resultado.estado) {
                notificar("success", resultado.mensaje);
                await cargarDatos();
            } else {
                notificar("danger", resultado.mensaje);
            }
        } catch (error) {
            console.error("Error al anular la venta:", error);
            notificar("danger", "No se pudo anular la venta");
        }
    }

    /* ---------------------- CARGA DE DATOS ---------------------- */

    async function cargarDatos() {
        cargando = true;
        dibujar();
        try {
            const datos = new FormData();
            datos.append("opcion", "consultar");
            const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }
            const resultado = await respuesta.json();
            if (!Array.isArray(resultado)) {
                throw new Error(resultado.mensaje || "Respuesta no válida");
            }
            ventas = resultado.map((v) => ({ ...v, estado: String(v.estado).toLowerCase() }));
            cargando = false;
            dibujar();
        } catch (error) {
            console.error("Error al cargar registros:", error);
            ventas = [];
            cargando = false;
            dibujar();
            notificar("danger", "No se pudieron cargar los registros");
        }
    }

    await cargarDatos();
});
