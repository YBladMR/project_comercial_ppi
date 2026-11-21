document.addEventListener('DOMContentLoaded', async () => {
    const formulario = document.getElementById('formularioAjusteInventario');
    const selectProducto = document.getElementById('productoAjuste');
    const buscarProducto = document.getElementById('buscarProductoAjuste');
    const contenedorBusqueda = document.getElementById('contenedorBusquedaProducto');
    const sugerencias = document.getElementById('sugerenciasProductoAjuste');
    const panelFormulario = document.getElementById('panelFormularioAjuste');
    const botonNuevo = document.getElementById('btnNuevoAjuste');
    const botonCancelar = document.getElementById('btnCancelarAjuste');
    const selectTipo = document.getElementById('tipoAjuste');
    const inputCantidad = document.getElementById('cantidadAjuste');
    const inputMotivo = document.getElementById('motivoAjuste');
    const mensaje = document.getElementById('mensajeAjuste');
    const existenciaActual = document.getElementById('existenciaActual');
    const existenciaResultante = document.getElementById('existenciaResultante');
    const botonGuardar = document.getElementById('btnRegistrarAjuste');
    const cuerpoHistorial = document.getElementById('cuerpoHistorialAjustes');
    const contenedorHistorial = document.getElementById('contenedorHistorial');
    const historialVacio = document.getElementById('historialVacio');
    const totalAjustes = document.getElementById('totalAjustes');
    const buscarHistorial = document.getElementById('buscarHistorialAjustes');
    const fechaDesde = document.getElementById('fechaDesdeAjustes');
    const fechaHasta = document.getElementById('fechaHastaAjustes');
    const tipoFiltro = document.getElementById('tipoFiltroAjustes');
    const usuarioFiltro = document.getElementById('usuarioFiltroAjustes');
    const botonLimpiarFiltros = document.getElementById('limpiarFiltrosAjustes');
    const tamPaginaHistorial = document.getElementById('tamPaginaAjustes');
    const paginadorHistorial = document.getElementById('paginadorAjustes');
    const resumenPaginaHistorial = document.getElementById('resumenPaginaAjustes');

    let productos = [];
    let ajustes = [];
    let guardando = false;
    let sugerenciasActuales = [];
    let sugerenciaActiva = -1;
    let paginaAjustes = 1;
    let cantidadPorPagina = Number(tamPaginaHistorial.value) || 10;

    function escapar(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[caracter]);
    }

    function productoActual() {
        return productos.find((producto) => String(producto.id_producto) === selectProducto.value);
    }

    function cerrarSugerencias() {
        sugerencias.hidden = true;
        buscarProducto.setAttribute('aria-expanded', 'false');
        buscarProducto.removeAttribute('aria-activedescendant');
        sugerenciaActiva = -1;
    }

    function elegirProducto(producto) {
        selectProducto.value = String(producto.id_producto);
        buscarProducto.value = producto.nombre;
        cerrarSugerencias();
        actualizarVista();
    }

    function mostrarSugerencias() {
        const consulta = buscarProducto.value.trim().toLocaleLowerCase('es');
        if (consulta === '') {
            sugerenciasActuales = [];
            cerrarSugerencias();
            return;
        }

        const coincidencias = productos.filter((producto) => {
            const nombre = String(producto.nombre ?? '').toLocaleLowerCase('es');
            const id = String(producto.id_producto ?? '');
            return nombre.includes(consulta) || id.includes(consulta);
        }).slice(0, 3);

        sugerenciasActuales = coincidencias;
        sugerenciaActiva = -1;
        buscarProducto.removeAttribute('aria-activedescendant');

        if (coincidencias.length === 0) {
            sugerencias.innerHTML = '<div class="ai-sugerencias-vacio">No hay coincidencias en inventario.</div>';
        } else {
            sugerencias.innerHTML = coincidencias.map((producto, indice) => `
                <button type="button" class="ai-sugerencia" role="option" aria-selected="false"
                        id="sugerencia-producto-${escapar(producto.id_producto)}" data-indice="${indice}">
                    <strong>${escapar(producto.nombre)}</strong>
                    <span class="ai-ayuda d-block">Existencia: ${Number(producto.existencia) || 0} · ID: ${escapar(producto.id_producto)}</span>
                </button>
            `).join('');
        }

        sugerencias.hidden = false;
        buscarProducto.setAttribute('aria-expanded', 'true');
    }

    function dibujarProductos() {
        botonNuevo.disabled = productos.length === 0;
        if (productos.length === 0) {
            buscarProducto.placeholder = 'No hay productos registrados en inventario';
            mensaje.className = 'alert alert-warning';
            mensaje.textContent = 'Registra primero el producto en inventario para poder hacerle ajustes.';
            selectProducto.value = '';
            buscarProducto.value = '';
            return;
        }

        buscarProducto.placeholder = 'Escriba para buscar un producto...';
        mensaje.className = 'alert alert-info';
        mensaje.textContent = 'La entrada aumenta las existencias y la salida las disminuye. El promedio de costo actual se conserva.';
    }

    function actualizarVista() {
        const producto = productoActual();
        const stock = Number(producto?.existencia ?? 0);
        const textoCantidad = inputCantidad.value.trim();
        const cantidadValida = /^\d+$/.test(textoCantidad)
            && Number(textoCantidad) > 0
            && Number(textoCantidad) <= 4294967295;
        const cantidad = cantidadValida ? Number(textoCantidad) : 0;
        const esSalida = selectTipo.value === 'salida';
        const superaStock = esSalida && cantidadValida && cantidad > stock;
        const stockNuevo = esSalida ? stock - cantidad : stock + cantidad;

        existenciaActual.textContent = producto ? stock.toLocaleString('es') : '—';
        existenciaResultante.textContent = producto && cantidadValida && !superaStock
            ? stockNuevo.toLocaleString('es')
            : '—';

        if (superaStock) {
            mensaje.className = 'alert alert-warning';
            mensaje.textContent = `No hay existencias suficientes. Disponibles: ${stock}.`;
        } else if (productos.length === 0) {
            mensaje.className = 'alert alert-warning';
            mensaje.textContent = 'Registra primero el producto en inventario para poder hacerle ajustes.';
        } else {
            mensaje.className = 'alert alert-info';
            mensaje.textContent = 'La entrada aumenta las existencias y la salida las disminuye. El promedio de costo actual se conserva.';
        }

        botonGuardar.disabled = guardando
            || !producto
            || !cantidadValida
            || superaStock
            || inputMotivo.value.trim() === '';
    }

    function obtenerAjustesFiltrados() {
        const consulta = buscarHistorial.value.trim().toLocaleLowerCase('es');
        return ajustes.filter((ajuste) => {
            const fecha = String(ajuste.fecha_hora ?? '').slice(0, 10);
            if (fechaDesde.value && fecha < fechaDesde.value) return false;
            if (fechaHasta.value && fecha > fechaHasta.value) return false;
            if (tipoFiltro.value && ajuste.tipo !== tipoFiltro.value) return false;
            if (usuarioFiltro.value && String(ajuste.id_usuario) !== usuarioFiltro.value) return false;
            if (!consulta) return true;

            return [ajuste.id_ajuste, ajuste.fecha_hora, ajuste.tipo, ajuste.cantidad,
                ajuste.motivo, ajuste.producto, ajuste.usuario]
                .some((dato) => String(dato ?? '').toLocaleLowerCase('es').includes(consulta));
        });
    }

    function dibujarUsuarios() {
        const usuarioSeleccionado = usuarioFiltro.value;
        const usuarios = new Map();
        ajustes.forEach((ajuste) => {
            usuarios.set(String(ajuste.id_usuario), ajuste.usuario);
        });

        const opciones = [...usuarios.entries()].sort((a, b) =>
            String(a[1]).localeCompare(String(b[1]), 'es', { sensitivity: 'base' })
        );
        usuarioFiltro.innerHTML = '<option value="">Todos</option>' + opciones.map(([id, nombre]) =>
            `<option value="${escapar(id)}">${escapar(nombre)}</option>`
        ).join('');
        usuarioFiltro.value = usuarios.has(usuarioSeleccionado) ? usuarioSeleccionado : '';
    }

    function dibujarPaginador(totalPaginas) {
        const boton = (texto, pagina, clase = '', deshabilitado = false) =>
            `<button type="button" data-ir="${pagina}" class="${clase}" ${deshabilitado ? 'disabled' : ''}>${texto}</button>`;

        let html = boton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', paginaAjustes - 1, '', paginaAjustes <= 1);
        const paginasVisibles = [];
        for (let numero = 1; numero <= totalPaginas; numero++) {
            if (numero === 1 || numero === totalPaginas || Math.abs(numero - paginaAjustes) <= 1) {
                paginasVisibles.push(numero);
            }
        }

        let anterior = 0;
        paginasVisibles.forEach((numero) => {
            if (numero - anterior > 1) html += '<button type="button" disabled aria-label="Páginas omitidas">…</button>';
            html += boton(numero, numero, numero === paginaAjustes ? 'activo' : '');
            anterior = numero;
        });
        html += boton('<i class="bi bi-chevron-right" aria-hidden="true"></i>', paginaAjustes + 1, '', paginaAjustes >= totalPaginas);
        paginadorHistorial.innerHTML = html;
    }

    function dibujarHistorial() {
        const filtrados = obtenerAjustesFiltrados();
        const totalPaginas = Math.max(1, Math.ceil(filtrados.length / cantidadPorPagina));
        if (paginaAjustes > totalPaginas) paginaAjustes = totalPaginas;
        const inicio = (paginaAjustes - 1) * cantidadPorPagina;
        const visibles = filtrados.slice(inicio, inicio + cantidadPorPagina);

        cuerpoHistorial.innerHTML = visibles.map((ajuste) => {
            const entrada = ajuste.tipo === 'entrada';
            const clase = entrada ? 'entrada' : 'salida';
            const signo = entrada ? '+' : '−';
            return `
                <tr>
                    <td>${escapar(ajuste.fecha_hora)}</td>
                    <td>${escapar(ajuste.producto)}</td>
                    <td><span class="ai-tipo ${clase}">${entrada ? 'Entrada' : 'Salida'}</span></td>
                    <td class="ai-cantidad">${signo}${escapar(ajuste.cantidad)}</td>
                    <td>${escapar(ajuste.motivo)}</td>
                    <td>${escapar(ajuste.usuario)}</td>
                </tr>`;
        }).join('');

        contenedorHistorial.hidden = visibles.length === 0;
        historialVacio.hidden = visibles.length > 0;
        historialVacio.textContent = ajustes.length === 0
            ? 'Todavía no hay ajustes registrados.'
            : 'No hay ajustes que coincidan con la búsqueda y los filtros.';
        totalAjustes.textContent = `Mostrando ${filtrados.length} de ${ajustes.length} ajustes`;
        const primero = filtrados.length === 0 ? 0 : inicio + 1;
        const ultimo = Math.min(inicio + cantidadPorPagina, filtrados.length);
        resumenPaginaHistorial.textContent = `Mostrando ${primero}-${ultimo} de ${filtrados.length} registros`;
        dibujarPaginador(totalPaginas);
    }

    async function cargarDatos() {
        const respuesta = await fetch('../dao/DAOAjusteInventario.php?opcion=consultar', {
            headers: { Accept: 'application/json' }
        });
        const resultado = await respuesta.json();
        if (!respuesta.ok || !resultado.estado) {
            throw new Error(resultado.mensaje || `Error HTTP: ${respuesta.status}`);
        }

        productos = resultado.datos.productos || [];
        ajustes = resultado.datos.ajustes || [];
        dibujarProductos();
        dibujarUsuarios();
        dibujarHistorial();
        actualizarVista();
    }

    botonNuevo.addEventListener('click', () => {
        panelFormulario.hidden = false;
        panelFormulario.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(() => buscarProducto.focus(), 150);
    });

    botonCancelar.addEventListener('click', () => {
        formulario.reset();
        selectProducto.value = '';
        buscarProducto.value = '';
        inputCantidad.value = '1';
        cerrarSugerencias();
        panelFormulario.hidden = true;
        actualizarVista();
    });

    buscarProducto.addEventListener('input', () => {
        selectProducto.value = '';
        mostrarSugerencias();
        actualizarVista();
    });

    buscarProducto.addEventListener('keydown', (evento) => {
        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            if (sugerencias.hidden) mostrarSugerencias();
            if (sugerenciasActuales.length > 0) {
                sugerenciaActiva = Math.min(sugerenciaActiva + 1, sugerenciasActuales.length - 1);
            }
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            if (sugerencias.hidden) mostrarSugerencias();
            if (sugerenciasActuales.length > 0) {
                sugerenciaActiva = sugerenciaActiva <= 0 ? 0 : sugerenciaActiva - 1;
            }
        } else if (evento.key === 'Enter' && !sugerencias.hidden && sugerenciasActuales.length > 0) {
            evento.preventDefault();
            elegirProducto(sugerenciasActuales[sugerenciaActiva < 0 ? 0 : sugerenciaActiva]);
        } else if (evento.key === 'Escape') {
            cerrarSugerencias();
        }

        sugerencias.querySelectorAll('.ai-sugerencia').forEach((opcion, indice) => {
            const activa = indice === sugerenciaActiva;
            opcion.classList.toggle('activa', activa);
            opcion.setAttribute('aria-selected', String(activa));
            if (activa) buscarProducto.setAttribute('aria-activedescendant', opcion.id);
        });
    });

    sugerencias.addEventListener('click', (evento) => {
        const opcion = evento.target.closest('.ai-sugerencia[data-indice]');
        if (!opcion) return;
        const producto = sugerenciasActuales[Number(opcion.dataset.indice)];
        if (producto) elegirProducto(producto);
    });

    document.addEventListener('click', (evento) => {
        if (!contenedorBusqueda.contains(evento.target)) cerrarSugerencias();
    });

    selectTipo.addEventListener('change', actualizarVista);
    inputCantidad.addEventListener('input', actualizarVista);
    inputMotivo.addEventListener('input', actualizarVista);
    const aplicarFiltrosHistorial = () => {
        paginaAjustes = 1;
        dibujarHistorial();
    };
    buscarHistorial.addEventListener('input', aplicarFiltrosHistorial);
    fechaDesde.addEventListener('input', aplicarFiltrosHistorial);
    fechaHasta.addEventListener('input', aplicarFiltrosHistorial);
    tipoFiltro.addEventListener('change', aplicarFiltrosHistorial);
    usuarioFiltro.addEventListener('change', aplicarFiltrosHistorial);
    botonLimpiarFiltros.addEventListener('click', () => {
        buscarHistorial.value = '';
        fechaDesde.value = '';
        fechaHasta.value = '';
        tipoFiltro.value = '';
        usuarioFiltro.value = '';
        paginaAjustes = 1;
        dibujarHistorial();
    });

    tamPaginaHistorial.addEventListener('change', () => {
        cantidadPorPagina = Number(tamPaginaHistorial.value) || 10;
        paginaAjustes = 1;
        dibujarHistorial();
    });

    paginadorHistorial.addEventListener('click', (evento) => {
        const boton = evento.target.closest('button[data-ir]');
        if (!boton || boton.disabled) return;
        paginaAjustes = Number(boton.dataset.ir);
        dibujarHistorial();
    });

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        actualizarVista();
        const producto = productoActual();
        if (!producto) {
            notificar('warning', 'Escriba y seleccione un producto de las coincidencias.');
            buscarProducto.focus();
            mostrarSugerencias();
            return;
        }
        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }
        if (botonGuardar.disabled) return;

        const tipoTexto = selectTipo.value === 'entrada' ? 'entrada' : 'salida';
        const confirmado = await confirmarAccion(
            `¿Registrar una ${tipoTexto} de ${inputCantidad.value} unidad(es) para ${producto.nombre}?`,
            'Registrar ajuste',
            tipoTexto === 'salida' ? 'danger' : 'primary'
        );
        if (!confirmado) return;

        guardando = true;
        const textoOriginal = botonGuardar.innerHTML;
        botonGuardar.disabled = true;
        botonGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Registrando...';

        try {
            const datos = new FormData();
            datos.append('opcion', 'guardar');
            datos.append('id_producto', producto.id_producto);
            datos.append('tipo', tipoTexto);
            datos.append('cantidad', inputCantidad.value.trim());
            datos.append('motivo', inputMotivo.value.trim());

            const respuesta = await fetch('../dao/DAOAjusteInventario.php', {
                method: 'POST',
                body: datos
            });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.estado) {
                throw new Error(resultado.mensaje || `Error HTTP: ${respuesta.status}`);
            }

            notificar('success', resultado.mensaje || 'El ajuste se registró correctamente.');
            inputCantidad.value = '1';
            inputMotivo.value = '';
            paginaAjustes = 1;
            await cargarDatos();
        } catch (error) {
            console.error('Error al registrar el ajuste:', error);
            notificar('danger', error.message || 'No se pudo registrar el ajuste.');
        } finally {
            guardando = false;
            botonGuardar.innerHTML = textoOriginal;
            actualizarVista();
        }
    });

    try {
        await cargarDatos();
    } catch (error) {
        console.error('Error al cargar los ajustes:', error);
        buscarProducto.placeholder = 'No se pudieron cargar los productos';
        mensaje.className = 'alert alert-danger';
        mensaje.textContent = 'No se pudieron cargar los datos de inventario.';
        historialVacio.textContent = 'No se pudo cargar el historial de ajustes.';
        totalAjustes.textContent = '0 registros';
        botonNuevo.disabled = true;
        botonGuardar.disabled = true;
        notificar('danger', error.message || 'No se pudo cargar el formulario de ajustes.');
    }
});
