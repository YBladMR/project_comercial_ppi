/* ============================================================
 * JSVentas.js
 * Registro de ventas: cliente, tipo de venta y detalle de productos.
 * Calcula subtotal, IVA Débito Fiscal (13%) y total.
 * Usa notificar() y confirmarAccion() de Notificaciones.js.
 * ============================================================ */

document.addEventListener("DOMContentLoaded", async () => {
    const formulario = document.getElementById("formVenta");
    if (!formulario) return;

    const selCliente = document.getElementById("id_cliente");
    const selTipo = document.getElementById("tipo_venta");
    const txtFecha = document.getElementById("fecha_hora");
    const selProducto = document.getElementById("producto");
    const inpCantidad = document.getElementById("cantidad");
    const btnAgregar = document.getElementById("btnAgregar");
    const infoProducto = document.getElementById("infoProducto");
    const cuerpo = document.getElementById("cuerpo-detalle");
    const txtSubtotal = document.getElementById("txtSubtotal");
    const txtIva = document.getElementById("txtIva");
    const txtTotal = document.getElementById("txtTotal");
    const btnGuardar = document.getElementById("btnGuardar");
    const btnCancelar = document.getElementById("btnCancelar");
    const textoGuardar = document.getElementById("textoGuardar");

    const URL_DAO = "../dao/DAOVentas.php";
    const URL_LISTA = "Lista-ventas.php";
    const IVA = 0.13;

    let productos = [];   // catálogo con existencia
    let detalle = [];     // líneas de la venta en curso

    /* ---------------------- UTILIDADES ---------------------- */

    function esc(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (c) => (
            { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
        ));
    }

    const dinero = (n) => "$" + Number(n).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const redondear = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

    async function peticion(datos) {
        const respuesta = await fetch(URL_DAO, { method: "POST", body: datos });
        if (!respuesta.ok) {
            throw new Error("Error HTTP: " + respuesta.status);
        }
        return respuesta.json();
    }

    function ahora() {
        const d = new Date();
        const dos = (n) => String(n).padStart(2, "0");
        return `${dos(d.getDate())}/${dos(d.getMonth() + 1)}/${d.getFullYear()} ${dos(d.getHours())}:${dos(d.getMinutes())}`;
    }

    const buscarProducto = (id) => productos.find((p) => String(p.id_producto) === String(id));

    // Unidades que aún se pueden agregar de un producto (existencia menos lo ya agregado)
    function disponible(id) {
        const p = buscarProducto(id);
        if (!p) return 0;
        const enVenta = detalle.find((l) => String(l.id_producto) === String(id));
        return p.existencia - (enVenta ? enVenta.cantidad : 0);
    }

    /* ---------------------- CARGA DE DATOS ---------------------- */

    async function cargarClientes() {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarClientes");
            const clientes = await peticion(datos);

            selCliente.innerHTML = '<option value="">[Seleccione un cliente]</option>' +
                clientes.map((c) => `<option value="${esc(c.id_cliente)}">${esc(c.nombre)} — ${esc(c.dui)}</option>`).join("");
        } catch (error) {
            console.error("Error al cargar los clientes:", error);
            notificar("danger", "No se pudieron cargar los clientes");
        }
    }

    async function cargarProductos() {
        try {
            const datos = new FormData();
            datos.append("opcion", "consultarProductos");
            const lista = await peticion(datos);

            productos = lista.map((p) => ({
                id_producto: p.id_producto,
                nombre: p.nombre,
                precio: Number(p.precio_venta),
                stock_minimo: Number(p.stock_minimo),
                existencia: Number(p.existencia)
            }));
            dibujarSelectProductos();
        } catch (error) {
            console.error("Error al cargar los productos:", error);
            notificar("danger", "No se pudieron cargar los productos");
        }
    }

    function dibujarSelectProductos() {
        selProducto.innerHTML = '<option value="">[Seleccione un producto]</option>' +
            productos.map((p) => {
                const sinStock = p.existencia <= 0;
                return `<option value="${esc(p.id_producto)}" ${sinStock ? "disabled" : ""}>` +
                    `${esc(p.nombre)} — ${dinero(p.precio)} — ${sinStock ? "sin existencia" : "disp.: " + p.existencia}</option>`;
            }).join("");
        mostrarInfoProducto();
    }

    function mostrarInfoProducto() {
        const p = buscarProducto(selProducto.value);
        if (!p) {
            infoProducto.textContent = "Selecciona un producto para ver su precio y existencia.";
            return;
        }
        let texto = `Precio: ${dinero(p.precio)} | Existencia: ${p.existencia} | Stock mínimo: ${p.stock_minimo}`;
        if (p.existencia <= p.stock_minimo) texto += " | ⚠ Existencia en el nivel mínimo";
        infoProducto.textContent = texto;
    }

    /* ---------------------- DETALLE DE LA VENTA ---------------------- */

    function totales() {
        const subtotal = redondear(detalle.reduce((s, l) => s + redondear(l.precio * l.cantidad), 0));
        const iva = redondear(subtotal * IVA);
        const total = redondear(subtotal + iva);
        return { subtotal, iva, total };
    }

    function dibujarDetalle() {
        if (detalle.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="5" class="text-center text-body-secondary py-4">Aún no has agregado productos.</td></tr>';
        } else {
            cuerpo.innerHTML = detalle.map((l) => `
                <tr data-id="${esc(l.id_producto)}">
                    <td>${esc(l.nombre)}</td>
                    <td class="text-end">${dinero(l.precio)}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm in-cant" min="1" step="1"
                               max="${esc(buscarProducto(l.id_producto)?.existencia ?? l.cantidad)}" value="${esc(l.cantidad)}">
                    </td>
                    <td class="text-end">${dinero(redondear(l.precio * l.cantidad))}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-danger btn-quitar" title="Quitar" aria-label="Quitar">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>`).join("");
        }

        const t = totales();
        txtSubtotal.textContent = dinero(t.subtotal);
        txtIva.textContent = dinero(t.iva);
        txtTotal.textContent = dinero(t.total);
    }

    function agregar() {
        const p = buscarProducto(selProducto.value);
        if (!p) {
            notificar("danger", "Selecciona un producto.");
            selProducto.focus();
            return;
        }

        const cantidad = Number(inpCantidad.value);
        if (!Number.isInteger(cantidad) || cantidad <= 0) {
            notificar("danger", "La cantidad debe ser un número entero mayor que cero.");
            inpCantidad.focus();
            return;
        }

        const libres = disponible(p.id_producto);
        if (cantidad > libres) {
            notificar("danger", `Solo hay ${libres} unidades disponibles de "${p.nombre}".`);
            inpCantidad.focus();
            return;
        }

        const existente = detalle.find((l) => String(l.id_producto) === String(p.id_producto));
        if (existente) {
            existente.cantidad += cantidad;
        } else {
            detalle.push({ id_producto: p.id_producto, nombre: p.nombre, precio: p.precio, cantidad });
        }

        dibujarDetalle();
        selProducto.value = "";
        inpCantidad.value = 1;
        mostrarInfoProducto();
        selProducto.focus();
    }

    /* ---------------------- EVENTOS ---------------------- */

    selProducto.addEventListener("change", mostrarInfoProducto);
    btnAgregar.addEventListener("click", agregar);

    // Enter en la cantidad agrega la línea (sin enviar el formulario)
    inpCantidad.addEventListener("keydown", (e) => {
        if (e.key === "Enter") { e.preventDefault(); agregar(); }
    });
    formulario.addEventListener("submit", (e) => e.preventDefault());

    // Cambiar la cantidad o quitar una línea
    cuerpo.addEventListener("change", (e) => {
        const input = e.target.closest(".in-cant");
        if (!input) return;

        const id = input.closest("tr").dataset.id;
        const linea = detalle.find((l) => String(l.id_producto) === id);
        const p = buscarProducto(id);
        if (!linea || !p) return;

        const nueva = Number(input.value);
        if (!Number.isInteger(nueva) || nueva <= 0) {
            notificar("danger", "La cantidad debe ser un número entero mayor que cero.");
        } else if (nueva > p.existencia) {
            notificar("danger", `Solo hay ${p.existencia} unidades disponibles de "${p.nombre}".`);
        } else {
            linea.cantidad = nueva;
        }
        dibujarDetalle();
    });

    cuerpo.addEventListener("click", (e) => {
        if (!e.target.closest(".btn-quitar")) return;
        const id = e.target.closest("tr").dataset.id;
        detalle = detalle.filter((l) => String(l.id_producto) !== id);
        dibujarDetalle();
    });

    btnCancelar.addEventListener("click", async () => {
        if (detalle.length > 0) {
            const descartar = await confirmarAccion("¿Descartar la venta en curso?", "Sí, descartar", "danger");
            if (!descartar) return;
        }
        window.location.href = URL_LISTA;
    });

    /* ---------------------- GUARDAR ---------------------- */

    function limpiarVenta() {
        detalle = [];
        selCliente.value = "";
        selCliente.classList.remove("is-invalid", "is-valid");
        selTipo.value = "contado";
        selProducto.value = "";
        inpCantidad.value = 1;
        txtFecha.value = ahora();
        dibujarDetalle();
    }

    btnGuardar.addEventListener("click", async () => {
        const clienteValido = selCliente.value !== "";
        selCliente.classList.toggle("is-invalid", !clienteValido);
        selCliente.classList.toggle("is-valid", clienteValido);

        if (!clienteValido) {
            notificar("danger", "Selecciona un cliente.");
            selCliente.focus();
            return;
        }
        if (detalle.length === 0) {
            notificar("danger", "Agrega al menos un producto a la venta.");
            selProducto.focus();
            return;
        }

        const t = totales();
        const confirmado = await confirmarAccion(
            `¿Confirma registrar la venta por ${dinero(t.total)}?`,
            "Sí, registrar",
            "success"
        );
        if (!confirmado) return;

        const textoOriginal = textoGuardar.textContent;

        try {
            const datos = new FormData();
            datos.append("opcion", "guardar");
            datos.append("id_cliente", selCliente.value);
            datos.append("tipo_venta", selTipo.value);
            datos.append("detalle", JSON.stringify(
                detalle.map((l) => ({ id_producto: l.id_producto, cantidad: l.cantidad }))
            ));

            btnGuardar.disabled = true;
            textoGuardar.textContent = "Procesando...";

            const resultado = await peticion(datos);

            if (resultado.estado) {
                const codigo = "V-" + String(resultado.id_venta).padStart(6, "0");
                notificar("success", `${resultado.mensaje} (${codigo})`);

                // Avisos de existencia en el nivel mínimo
                (resultado.advertencias || []).forEach((a) => notificar("warning", a));

                limpiarVenta();
                await cargarProductos();   // la existencia cambió
            } else {
                notificar("danger", resultado.mensaje);
                await cargarProductos();   // por si la existencia cambió mientras tanto
            }
        } catch (error) {
            console.error("Error en la petición:", error);
            notificar("danger", "No se pudo completar la operación");
        } finally {
            btnGuardar.disabled = false;
            textoGuardar.textContent = textoOriginal;
        }
    });

    /* ---------------------- INICIO ---------------------- */

    txtFecha.value = ahora();
    dibujarDetalle();
    await Promise.all([cargarClientes(), cargarProductos()]);
});
