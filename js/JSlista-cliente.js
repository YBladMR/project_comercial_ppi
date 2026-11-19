document.addEventListener("DOMContentLoaded", async function () {
    const cuerpo = document.getElementById("tablaClientes");
    if (!cuerpo) return;

    const tablaWrap = document.getElementById("tabla-wrap");
    const buscador = document.getElementById("buscarCliente");
    const chipsEstado = document.querySelectorAll(".lp-chip[data-estado]");
    const encabezados = document.querySelectorAll("#tabla-clientes th[data-orden]");
    const botonFiltros = document.getElementById("btnFiltros");
    const panelFiltros = document.getElementById("panelFiltros");
    const botonLimpiar = document.getElementById("limpiar-filtros");
    const botonImprimir = document.getElementById("print-table");
    const botonVacioLimpiar = document.getElementById("vacio-limpiar");
    const accionAgregar = document.getElementById("vacio-agregar");
    const vacio = document.getElementById("estado-vacio");
    const selectorTamano = document.getElementById("tam-pagina");
    const paginador = document.getElementById("table-pagination");
    const contador = document.getElementById("total-registros");
    const urlDao = "../dao/DAOCliente.php";

    let clientes = [];
    let estadoFiltro = "";
    let textoFiltro = "";
    let cargando = true;
    let errorCarga = false;
    let pagina = 1;
    let tamanoPagina = Number(selectorTamano.value) || 10;
    let orden = { campo: "nombre_completo", direccion: 1 };

    function escapar(valor) {
        return String(valor ?? "").replace(/[&<>"']/g, function (caracter) {
            const reemplazos = { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" };
            return reemplazos[caracter];
        });
    }

    function prepararCliente(cliente) {
        return {
            ...cliente,
            id_cliente: String(cliente.id_cliente ?? ""),
            nombre: String(cliente.nombre ?? ""),
            apellido: String(cliente.apellido ?? ""),
            nombre_completo: `${cliente.nombre ?? ""} ${cliente.apellido ?? ""}`.trim(),
            dui: String(cliente.dui ?? ""),
            telefono: String(cliente.telefono ?? ""),
            direccion_entrega: String(cliente.direccion_entrega ?? ""),
            correo_electronico: String(cliente.correo_electronico ?? ""),
            estado: String(cliente.estado ?? "").toLowerCase()
        };
    }

    function filtrarClientes() {
        const texto = textoFiltro.trim().toLowerCase();
        const lista = clientes.filter(function (cliente) {
            if (estadoFiltro && cliente.estado !== estadoFiltro) return false;
            if (!texto) return true;

            return [cliente.nombre_completo, cliente.dui, cliente.telefono,
                cliente.direccion_entrega, cliente.correo_electronico, cliente.estado]
                .some(function (dato) { return dato.toLowerCase().includes(texto); });
        });

        lista.sort(function (a, b) {
            const valorA = String(a[orden.campo] ?? "").toLowerCase();
            const valorB = String(b[orden.campo] ?? "").toLowerCase();
            return valorA.localeCompare(valorB, "es", { numeric: true }) * orden.direccion;
        });

        return lista;
    }

    function crearFila(cliente) {
        const estaActivo = cliente.estado === "activo";
        return `
            <tr class="${estaActivo ? "" : "lp-inactivo"}" data-id="${escapar(cliente.id_cliente)}">
                <td>
                    <div class="lp-cliente">
                        <span class="lp-cliente-ico"><i class="bi bi-person" aria-hidden="true"></i></span>
                        <span>
                            <span class="lp-cliente-nom d-block">${escapar(cliente.nombre_completo)}</span>
                            <span class="lp-sub">ID: ${escapar(cliente.id_cliente)}</span>
                        </span>
                    </div>
                </td>
                <td>${escapar(cliente.dui)}</td>
                <td class="lp-contacto">
                    <div><i class="bi bi-telephone me-1" aria-hidden="true"></i>${escapar(cliente.telefono)}</div>
                    <div class="lp-sub"><i class="bi bi-envelope me-1" aria-hidden="true"></i>${escapar(cliente.correo_electronico)}</div>
                </td>
                <td>${escapar(cliente.direccion_entrega)}</td>
                <td class="lp-centro"><span class="lp-pill ${estaActivo ? "activo" : "inactivo"}">${estaActivo ? "Activo" : "Inactivo"}</span></td>
                <td class="lp-centro lp-col-acc">
                    <span class="lp-acc">
                        <a class="lp-ib" href="cliente.php?id=${encodeURIComponent(cliente.id_cliente)}" title="Modificar cliente" aria-label="Modificar ${escapar(cliente.nombre_completo)}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        ${estaActivo ? `<button type="button" class="lp-ib baja btn-desactivar" title="Desactivar cliente" aria-label="Desactivar ${escapar(cliente.nombre_completo)}"><i class="bi bi-slash-circle" aria-hidden="true"></i></button>` : ""}
                    </span>
                </td>
            </tr>`;
    }

    function mostrarVacio(hayResultados) {
        const titulo = document.getElementById("vacio-titulo");
        const detalle = document.getElementById("vacio-texto");
        accionAgregar.hidden = true;
        botonVacioLimpiar.hidden = true;

        if (cargando) {
            titulo.textContent = "Cargando clientes...";
            detalle.textContent = "Un momento, por favor.";
        } else if (errorCarga) {
            titulo.textContent = "No se pudieron cargar los clientes";
            detalle.textContent = "Intenta recargar la página.";
        } else if (hayResultados) {
            titulo.textContent = "Sin resultados";
            detalle.textContent = "Ningún cliente coincide con los filtros aplicados.";
            botonVacioLimpiar.hidden = false;
        } else {
            titulo.textContent = "No hay clientes para mostrar";
            detalle.textContent = "Aún no se han registrado clientes. Puedes agregar uno nuevo.";
            accionAgregar.hidden = false;
        }
    }

    function mostrarPaginador(totalPaginas) {
        function boton(texto, paginaDestino, clase, deshabilitado) {
            return `<button type="button" data-pagina="${paginaDestino}" class="${clase || ""}" ${deshabilitado ? "disabled" : ""}>${texto}</button>`;
        }

        let html = boton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', pagina - 1, "", pagina <= 1);
        const paginas = [];

        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || Math.abs(i - pagina) <= 1) paginas.push(i);
        }

        let anterior = 0;
        paginas.forEach(function (numero) {
            if (numero - anterior > 1) html += '<button type="button" disabled aria-label="Páginas omitidas">…</button>';
            html += boton(numero, numero, numero === pagina ? "activo" : "", false);
            anterior = numero;
        });

        html += boton('<i class="bi bi-chevron-right" aria-hidden="true"></i>', pagina + 1, "", pagina >= totalPaginas);
        paginador.innerHTML = html;
    }

    function dibujar() {
        const lista = filtrarClientes();
        const totalPaginas = Math.max(1, Math.ceil(lista.length / tamanoPagina));
        if (pagina > totalPaginas) pagina = totalPaginas;

        const inicio = (pagina - 1) * tamanoPagina;
        const visibles = lista.slice(inicio, inicio + tamanoPagina);
        cuerpo.innerHTML = visibles.map(crearFila).join("");

        const sinFilas = visibles.length === 0;
        tablaWrap.hidden = sinFilas;
        vacio.hidden = !sinFilas;
        if (sinFilas) mostrarVacio(clientes.length > 0 && (estadoFiltro !== "" || textoFiltro.trim() !== ""));

        contador.textContent = `Total de clientes: ${lista.length}`;
        chipsEstado.forEach(function (chip) {
            chip.classList.toggle("activo", chip.dataset.estado === estadoFiltro);
        });
        mostrarPaginador(totalPaginas);
    }

    function limpiarFiltros() {
        estadoFiltro = "";
        textoFiltro = "";
        buscador.value = "";
        pagina = 1;
        dibujar();
    }

    function cerrarFiltros() {
        panelFiltros.hidden = true;
        botonFiltros.setAttribute("aria-expanded", "false");
    }

    async function cargarClientes() {
        cargando = true;
        errorCarga = false;
        dibujar();

        try {
            const respuesta = await fetch(urlDao + "?opcion=consultar", {
                headers: { Accept: "application/json" }
            });
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();
            if (!Array.isArray(resultado)) throw new Error(resultado.mensaje || "Respuesta no válida");
            clientes = resultado.map(prepararCliente);
            cargando = false;
            dibujar();
        } catch (error) {
            console.error("Error al cargar clientes:", error);
            clientes = [];
            cargando = false;
            errorCarga = true;
            dibujar();
            notificar("danger", "No se pudieron cargar los clientes.");
        }
    }

    async function desactivarCliente(cliente) {
        const confirmado = await confirmarAccion(
            `¿Confirmas desactivar al cliente "${cliente.nombre_completo}"?`,
            "Sí, desactivar",
            "danger"
        );
        if (!confirmado) return;

        try {
            const datos = new FormData();
            datos.append("opcion", "eliminar");
            datos.append("id_cliente", cliente.id_cliente);

            const respuesta = await fetch(urlDao, { method: "POST", body: datos });
            if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);

            const resultado = await respuesta.json();
            if (resultado.resultado !== "correcto") {
                notificar("danger", resultado.mensaje || "No se pudo desactivar al cliente.");
                return;
            }

            notificar("success", resultado.mensaje || "Cliente desactivado correctamente.");
            await cargarClientes();
        } catch (error) {
            console.error("Error al desactivar el cliente:", error);
            notificar("danger", "No se pudo desactivar al cliente.");
        }
    }

    chipsEstado.forEach(function (chip) {
        chip.addEventListener("click", function () {
            estadoFiltro = chip.dataset.estado;
            pagina = 1;
            dibujar();
        });
    });

    buscador.addEventListener("input", function () {
        textoFiltro = buscador.value;
        pagina = 1;
        dibujar();
    });

    encabezados.forEach(function (encabezado) {
        encabezado.addEventListener("click", function () {
            const campo = encabezado.dataset.orden;
            orden = { campo: campo, direccion: orden.campo === campo ? -orden.direccion : 1 };
            encabezados.forEach(function (th) { th.querySelector(".bi")?.remove(); });
            encabezado.insertAdjacentHTML("beforeend", `<i class="bi ${orden.direccion === 1 ? "bi-caret-up-fill" : "bi-caret-down-fill"}" aria-hidden="true"></i>`);
            pagina = 1;
            dibujar();
        });
    });

    selectorTamano.addEventListener("change", function () {
        tamanoPagina = Number(selectorTamano.value) || 10;
        pagina = 1;
        dibujar();
    });

    paginador.addEventListener("click", function (evento) {
        const boton = evento.target.closest("button[data-pagina]");
        if (!boton || boton.disabled) return;
        pagina = Number(boton.dataset.pagina);
        dibujar();
    });

    botonFiltros.addEventListener("click", function (evento) {
        evento.stopPropagation();
        panelFiltros.hidden = !panelFiltros.hidden;
        botonFiltros.setAttribute("aria-expanded", String(!panelFiltros.hidden));
    });
    panelFiltros.addEventListener("click", function (evento) { evento.stopPropagation(); });
    document.addEventListener("click", cerrarFiltros);
    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape") cerrarFiltros();
    });
    botonLimpiar.addEventListener("click", function () { limpiarFiltros(); cerrarFiltros(); });
    botonVacioLimpiar.addEventListener("click", limpiarFiltros);
    botonImprimir.addEventListener("click", function () {
        cerrarFiltros();
        window.print();
    });

    cuerpo.addEventListener("click", async function (evento) {
        const fila = evento.target.closest("tr[data-id]");
        if (!fila) return;

        const cliente = clientes.find(function (item) {
            return item.id_cliente === fila.dataset.id;
        });
        if (!cliente) return;

        if (evento.target.closest(".btn-desactivar")) {
            await desactivarCliente(cliente);
        }
    });

    await cargarClientes();
});
