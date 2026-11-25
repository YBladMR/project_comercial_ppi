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
    const inpBuscarCliente = document.getElementById("buscar_cliente");
    const resultadosClientes = document.getElementById("resultados_clientes");
    const panelClienteSeleccionado = document.getElementById("cliente_seleccionado");
    const textoClienteSeleccionado = document.getElementById("texto_cliente_seleccionado");
    const duiClienteSeleccionado = document.getElementById("dui_cliente_seleccionado");
    const ayudaBuscarCliente = document.getElementById("ayuda_buscar_cliente");
    const btnCambiarCliente = document.getElementById("btn_cambiar_cliente");
    const btnRegistrarCliente = document.getElementById("btn_registrar_cliente");
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
    const panelCredito = document.getElementById("panelCredito");
    const inputPrima = document.getElementById("prima");
    const inputNumeroCuotas = document.getElementById("numero_cuotas");
    const inputPrimerVencimiento = document.getElementById("fecha_primer_vencimiento");
    const resumenCredito = document.getElementById("resumenCredito");
    const envolturaPlanPreview = document.getElementById("envolturaPlanPreview");
    const cuerpoPlanPreview = document.getElementById("cuerpoPlanPreview");

    const URL_DAO = "../dao/DAOVentas.php";
    const URL_LISTA = "Lista-ventas.php";
    const tokenCsrf = document.body.dataset.csrfToken || "";
    const CLAVE_BORRADOR_VENTA = "comercial_venta_borrador";
    const IVA = 0.13;

    let productos = [];   // catálogo con existencia
    let detalle = [];     // líneas de la venta en curso
    let clienteActivo = null;
    let temporizadorBusqueda = null;
    let solicitudBusqueda = 0;

    /* ---------------------- UTILIDADES ---------------------- */

    function esc(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, (c) => (
            { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]
        ));
    }

    const dinero = (n) => "$" + Number(n).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const redondear = (n) => Math.round((n + Number.EPSILON) * 100) / 100;

    function fechaLocal(d) {
        const anio = d.getFullYear();
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const dia = String(d.getDate()).padStart(2, "0");
        return `${anio}-${mes}-${dia}`;
    }

    function fechaMensual(fechaBase, desplazamiento) {
        const [anio, mes, dia] = fechaBase.split("-").map(Number);
        const primerDia = new Date(anio, mes - 1 + desplazamiento, 1);
        const ultimoDia = new Date(primerDia.getFullYear(), primerDia.getMonth() + 1, 0).getDate();
        return fechaLocal(new Date(primerDia.getFullYear(), primerDia.getMonth(), Math.min(dia, ultimoDia)));
    }

    function interesPeriodo(capital, inicio, fin) {
        let cursor = Date.UTC(...inicio.split("-").map((valor, indice) => indice === 1 ? Number(valor) - 1 : Number(valor)));
        const finUtc = Date.UTC(...fin.split("-").map((valor, indice) => indice === 1 ? Number(valor) - 1 : Number(valor)));
        let total = 0;
        while (cursor < finUtc) {
            const anio = new Date(cursor).getUTCFullYear();
            const inicioSiguienteAnio = Date.UTC(anio + 1, 0, 1);
            const finSegmento = Math.min(inicioSiguienteAnio, finUtc);
            const dias = (finSegmento - cursor) / 86400000;
            const diasAnio = ((Date.UTC(anio + 1, 0, 1) - Date.UTC(anio, 0, 1)) / 86400000);
            total += capital * 0.20 * dias / diasAnio;
            cursor = finSegmento;
        }
        return redondear(total);
    }

    function calcularPlanCredito(principal, numeroCuotas, primerVencimiento) {
        if (!(principal > 0) || !Number.isInteger(numeroCuotas) || numeroCuotas < 1 || !primerVencimiento) return null;
        const inicio = fechaLocal(new Date());
        function simular(cuotaFija, incluirFilas) {
            let saldo = principal;
            let fechaAnterior = inicio;
            const filas = [];
            for (let i = 1; i <= numeroCuotas; i++) {
                const vencimiento = fechaMensual(primerVencimiento, i - 1);
                const interes = interesPeriodo(saldo, fechaAnterior, vencimiento);
                const pago = i === numeroCuotas ? redondear(saldo + interes) : redondear(cuotaFija);
                const capital = Math.min(saldo, redondear(Math.max(0, pago - interes)));
                saldo = redondear(Math.max(0, saldo - capital));
                if (incluirFilas) filas.push({ numero: i, vencimiento, capital, interes, pago });
                fechaAnterior = vencimiento;
            }
            return { saldo, filas };
        }

        let inferior = 0;
        let superior = principal * (1 + 0.20 * numeroCuotas / 12) + 1;
        for (let i = 0; i < 70; i++) {
            const medio = (inferior + superior) / 2;
            if (simular(medio, false).saldo > 0) inferior = medio;
            else superior = medio;
        }
        const cuotaFija = redondear(superior);
        return { cuotaFija, filas: simular(cuotaFija, true).filas };
    }

    function actualizarProyeccionCredito() {
        if (!panelCredito || panelCredito.hidden) return;
        const totalVenta = totales().total;
        const prima = Math.round(Number(inputPrima.value || 0) * 100) / 100;
        const numeroCuotas = Number(inputNumeroCuotas.value);
        const fechaPrimera = inputPrimerVencimiento.value;
        const financiado = redondear(totalVenta - prima);

        if (!Number.isInteger(numeroCuotas) || numeroCuotas < 1 || numeroCuotas > 120 || prima <= 0 || financiado < numeroCuotas * 0.01 || !fechaPrimera) {
            resumenCredito.textContent = "Indica una prima menor que el total, entre 1 y 120 cuotas y una fecha futura para ver la proyección.";
            envolturaPlanPreview.hidden = true;
            return;
        }

        const plan = calcularPlanCredito(financiado, numeroCuotas, fechaPrimera);
        if (!plan) return;
        const intereses = redondear(plan.filas.reduce((suma, fila) => suma + fila.interes, 0));
        const totalFinanciado = redondear(financiado + intereses);
        resumenCredito.innerHTML = `<strong>Total venta:</strong> ${dinero(totalVenta)} &nbsp; <strong>Prima:</strong> ${dinero(prima)} &nbsp; <strong>Capital financiado:</strong> ${dinero(financiado)}<br><strong>Cuota estimada:</strong> ${dinero(plan.cuotaFija)} &nbsp; <strong>Interés total proyectado:</strong> ${dinero(intereses)} &nbsp; <strong>Total proyectado:</strong> ${dinero(totalFinanciado)}`;
        cuerpoPlanPreview.innerHTML = plan.filas.map((fila) => `<tr><td>${fila.numero}</td><td>${esc(fila.vencimiento)}</td><td class="text-end">${dinero(fila.capital)}</td><td class="text-end">${dinero(fila.interes)}</td><td class="text-end">${dinero(fila.pago)}</td></tr>`).join("");
        envolturaPlanPreview.hidden = false;
    }

    function actualizarPanelCredito() {
        if (!panelCredito) return;
        const esCredito = selTipo.value === "credito";
        panelCredito.hidden = !esCredito;
        if (esCredito && !inputPrimerVencimiento.value) {
            const hoy = new Date();
            inputPrimerVencimiento.value = fechaMensual(fechaLocal(hoy), 1);
        }
        actualizarProyeccionCredito();
    }

    async function peticion(datos) {
        const respuesta = await fetch(URL_DAO, {
            method: "POST",
            body: datos,
            credentials: "same-origin",
            headers: { "X-CSRF-Token": tokenCsrf }
        });
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

    function seleccionarCliente(cliente) {
        solicitudBusqueda++;
        if (temporizadorBusqueda) clearTimeout(temporizadorBusqueda);
        clienteActivo = {
            id_cliente: String(cliente.id_cliente),
            nombre: cliente.nombre || "",
            dui: cliente.dui || ""
        };
        selCliente.value = clienteActivo.id_cliente;
        textoClienteSeleccionado.textContent = clienteActivo.nombre;
        duiClienteSeleccionado.textContent = clienteActivo.dui;
        panelClienteSeleccionado.classList.remove("d-none");
        resultadosClientes.classList.add("d-none");
        btnRegistrarCliente.classList.add("d-none");
        inpBuscarCliente.value = "";
        inpBuscarCliente.disabled = true;
        inpBuscarCliente.classList.add("d-none");
        ayudaBuscarCliente.classList.add("d-none");
        inpBuscarCliente.classList.remove("is-invalid", "is-valid");
        selCliente.classList.remove("is-invalid");
    }

    function limpiarClienteSeleccionado() {
        solicitudBusqueda++;
        if (temporizadorBusqueda) clearTimeout(temporizadorBusqueda);
        clienteActivo = null;
        selCliente.value = "";
        panelClienteSeleccionado.classList.add("d-none");
        inpBuscarCliente.disabled = false;
        inpBuscarCliente.classList.remove("d-none");
        ayudaBuscarCliente.classList.remove("d-none");
        inpBuscarCliente.classList.remove("is-valid", "is-invalid");
    }

    async function buscarClientes(termino) {
        const busqueda = termino.trim();
        if (busqueda.length < 2) return;

        const numeroSolicitud = ++solicitudBusqueda;
        const datos = new FormData();
        datos.append("opcion", "buscarClientes");
        datos.append("termino", busqueda);
        btnRegistrarCliente.classList.add("d-none");
        resultadosClientes.classList.remove("d-none");
        resultadosClientes.innerHTML = '<div class="list-group-item text-body-secondary">Buscando clientes...</div>';

        try {
            const clientes = await peticion(datos);
            if (numeroSolicitud !== solicitudBusqueda) return;
            if (!Array.isArray(clientes)) throw new Error("Respuesta de clientes no válida");

            if (clientes.length === 0) {
                ayudaBuscarCliente.textContent = "No encontramos ese cliente.";
                resultadosClientes.innerHTML = '<div class="list-group-item text-body-secondary">No hay coincidencias.</div>';
                btnRegistrarCliente.classList.remove("d-none");
                return;
            }

            ayudaBuscarCliente.textContent = "Selecciona el cliente de la lista.";
            resultadosClientes.innerHTML = clientes.map((cliente) =>
                `<button type="button" class="list-group-item list-group-item-action" role="option"
                         data-cliente-id="${esc(cliente.id_cliente)}"
                         data-cliente-nombre="${esc(cliente.nombre)}"
                         data-cliente-dui="${esc(cliente.dui)}">
                    <span class="fw-semibold">${esc(cliente.nombre)}</span>
                    <span class="text-body-secondary ms-2">DUI: ${esc(cliente.dui)}</span>
                 </button>`
            ).join("");
            btnRegistrarCliente.classList.add("d-none");
        } catch (error) {
            if (numeroSolicitud !== solicitudBusqueda) return;
            console.error("Error al buscar clientes:", error);
            ayudaBuscarCliente.textContent = "No se pudo completar la búsqueda.";
            resultadosClientes.innerHTML = '<div class="list-group-item text-danger">No se pudo completar la búsqueda.</div>';
            btnRegistrarCliente.classList.add("d-none");
        }
    }

    function guardarBorradorVenta() {
        const borrador = {
            tipo_venta: selTipo.value,
            fecha_hora: txtFecha.value,
            detalle: detalle.map((linea) => ({
                id_producto: linea.id_producto,
                cantidad: linea.cantidad
            })),
            cliente: clienteActivo
        };
        try {
            sessionStorage.setItem(CLAVE_BORRADOR_VENTA, JSON.stringify(borrador));
            return true;
        } catch (error) {
            console.error("No se pudo conservar la venta en curso:", error);
            notificar("danger", "No se pudo conservar el detalle de la venta. Intenta de nuevo.");
            return false;
        }
    }

    function restaurarBorradorVenta() {
        let borrador;
        try {
            const guardado = sessionStorage.getItem(CLAVE_BORRADOR_VENTA);
            if (!guardado) return;
            borrador = JSON.parse(guardado);
        } catch (error) {
            sessionStorage.removeItem(CLAVE_BORRADOR_VENTA);
            return;
        }

        if (borrador.tipo_venta === "contado" || borrador.tipo_venta === "credito") {
            selTipo.value = borrador.tipo_venta;
        }
        if (borrador.fecha_hora) txtFecha.value = borrador.fecha_hora;
        if (borrador.cliente && borrador.cliente.id_cliente) seleccionarCliente(borrador.cliente);

        const sinExistencia = [];
        if (Array.isArray(borrador.detalle)) {
            detalle = borrador.detalle.reduce((lineas, linea) => {
                const producto = buscarProducto(linea.id_producto);
                const cantidad = Number(linea.cantidad);
                if (!producto || !Number.isInteger(cantidad) || cantidad <= 0) return lineas;
                if (cantidad > producto.existencia) sinExistencia.push(producto.nombre);
                lineas.push({
                    id_producto: producto.id_producto,
                    nombre: producto.nombre,
                    precio: producto.precio,
                    cantidad
                });
                return lineas;
            }, []);
        }
        dibujarDetalle();
        sessionStorage.removeItem(CLAVE_BORRADOR_VENTA);
        if (sinExistencia.length) {
            notificar("warning", `Revisa la cantidad disponible de: ${[...new Set(sinExistencia)].join(", ")}.`);
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
        actualizarProyeccionCredito();
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

    inpBuscarCliente.addEventListener("input", () => {
        if (selCliente.value) limpiarClienteSeleccionado();
        solicitudBusqueda++;
        btnRegistrarCliente.classList.add("d-none");
        if (temporizadorBusqueda) clearTimeout(temporizadorBusqueda);

        const termino = inpBuscarCliente.value.trim();
        if (termino.length < 2) {
            resultadosClientes.innerHTML = "";
            resultadosClientes.classList.add("d-none");
            ayudaBuscarCliente.textContent = "Empieza a escribir para buscar un cliente.";
            return;
        }

        ayudaBuscarCliente.textContent = "Buscando coincidencias...";
        temporizadorBusqueda = setTimeout(() => buscarClientes(termino), 300);
    });

    resultadosClientes.addEventListener("click", (evento) => {
        const botonCliente = evento.target.closest("[data-cliente-id]");
        if (!botonCliente) return;
        seleccionarCliente({
            id_cliente: botonCliente.dataset.clienteId,
            nombre: botonCliente.dataset.clienteNombre,
            dui: botonCliente.dataset.clienteDui
        });
    });

    btnCambiarCliente.addEventListener("click", () => {
        limpiarClienteSeleccionado();
        inpBuscarCliente.focus();
    });

    btnRegistrarCliente.addEventListener("click", () => {
        if (!guardarBorradorVenta()) return;
        window.location.href = "cliente.php?retorno=venta";
    });

    selProducto.addEventListener("change", mostrarInfoProducto);
    selTipo.addEventListener("change", actualizarPanelCredito);
    [inputPrima, inputNumeroCuotas, inputPrimerVencimiento].forEach((input) => {
        input?.addEventListener("input", actualizarProyeccionCredito);
        input?.addEventListener("change", actualizarProyeccionCredito);
    });
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
        sessionStorage.removeItem(CLAVE_BORRADOR_VENTA);
        window.location.href = URL_LISTA;
    });

    /* ---------------------- GUARDAR ---------------------- */

    function limpiarVenta() {
        detalle = [];
        limpiarClienteSeleccionado();
        inpBuscarCliente.value = "";
        resultadosClientes.innerHTML = "";
        resultadosClientes.classList.add("d-none");
        btnRegistrarCliente.classList.add("d-none");
        selTipo.value = "contado";
        selProducto.value = "";
        inpCantidad.value = 1;
        txtFecha.value = ahora();
        inputPrima.value = "1.00";
        inputNumeroCuotas.value = "6";
        inputPrimerVencimiento.value = fechaMensual(fechaLocal(new Date()), 1);
        actualizarPanelCredito();
        dibujarDetalle();
    }

    btnGuardar.addEventListener("click", async () => {
        const clienteValido = selCliente.value !== "";
        inpBuscarCliente.classList.toggle("is-invalid", !clienteValido);
        if (clienteValido) inpBuscarCliente.classList.remove("is-valid");

        if (!clienteValido) {
            notificar("danger", "Busca y selecciona un cliente.");
            inpBuscarCliente.focus();
            return;
        }
        if (detalle.length === 0) {
            notificar("danger", "Agrega al menos un producto a la venta.");
            selProducto.focus();
            return;
        }

        const t = totales();
        let credito = null;
        if (selTipo.value === "credito") {
            const prima = Math.round(Number(inputPrima.value || 0) * 100) / 100;
            const numeroCuotas = Number(inputNumeroCuotas.value);
            const hoy = fechaLocal(new Date());
            if (!(prima > 0) || prima >= t.total) {
                notificar("danger", "La prima debe ser mayor que cero y menor que el total de la venta.");
                inputPrima.focus();
                return;
            }
            if (!Number.isInteger(numeroCuotas) || numeroCuotas < 1 || numeroCuotas > 120) {
                notificar("danger", "El número de cuotas debe estar entre 1 y 120.");
                inputNumeroCuotas.focus();
                return;
            }
            if (!inputPrimerVencimiento.value || inputPrimerVencimiento.value <= hoy) {
                notificar("danger", "El primer vencimiento debe ser una fecha futura.");
                inputPrimerVencimiento.focus();
                return;
            }
            credito = { prima, numeroCuotas, fechaPrimerVencimiento: inputPrimerVencimiento.value };
        }

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
            if (credito) {
                datos.append("prima", String(credito.prima));
                datos.append("numero_cuotas", String(credito.numeroCuotas));
                datos.append("fecha_primer_vencimiento", credito.fechaPrimerVencimiento);
            }
            datos.append("detalle", JSON.stringify(
                detalle.map((l) => ({ id_producto: l.id_producto, cantidad: l.cantidad }))
            ));

            btnGuardar.disabled = true;
            textoGuardar.textContent = "Procesando...";

            const resultado = await peticion(datos);

            if (resultado.estado) {
                (resultado.advertencias || []).forEach((a) => notificar("warning", a));
                sessionStorage.removeItem(CLAVE_BORRADOR_VENTA);
                window.location.href = `FacturaVenta.php?id_venta=${encodeURIComponent(resultado.id_venta)}`;
                return;
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

    if (new URLSearchParams(window.location.search).get("tipo") === "credito") {
        selTipo.value = "credito";
    }
    txtFecha.value = ahora();
    const hoy = new Date();
    inputPrimerVencimiento.min = fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + 1));
    inputPrimerVencimiento.value = fechaMensual(fechaLocal(hoy), 1);
    actualizarPanelCredito();
    dibujarDetalle();
    await cargarProductos();
    restaurarBorradorVenta();
});
