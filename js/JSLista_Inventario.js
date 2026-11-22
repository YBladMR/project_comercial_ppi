document.addEventListener('DOMContentLoaded', async () => {
    const cuerpo = document.getElementById('cuerpo-inventario');
    const tabla = document.getElementById('tabla-wrap');
    const estado = document.getElementById('estado-inventario');
    const tituloEstado = document.getElementById('estado-titulo');
    const detalleEstado = document.getElementById('estado-detalle');
    const buscador = document.getElementById('buscar-inventario');
    const tamPagina = document.getElementById('tam-pagina');
    const paginador = document.getElementById('paginador');
    const contador = document.getElementById('total-registros');
    const modalUbicacionEl = document.getElementById('modalUbicacionInventario');
    const formularioUbicacion = document.getElementById('formUbicacionInventario');
    const selectUbicacion = document.getElementById('selectUbicacionInventario');
    const botonGuardarUbicacion = document.getElementById('btnGuardarUbicacionInventario');
    const nombreProductoUbicacion = document.getElementById('nombreProductoUbicacion');
    const modalUbicacion = typeof coreui !== 'undefined' && coreui.Modal
        ? coreui.Modal.getOrCreateInstance(modalUbicacionEl)
        : null;

    let existencias = [];
    let ubicaciones = [];
    let ubicacionesCargadas = false;
    let productoSeleccionado = null;
    let texto = '';
    let pagina = 1;
    let cantidadPorPagina = Number(tamPagina.value) || 10;

    function escapar(valor) {
        return String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[caracter]);
    }

    function obtenerFiltradas() {
        const busqueda = texto.trim().toLocaleLowerCase('es');
        return existencias.filter((fila) =>
            [fila.producto, fila.categoria, fila.ubicacion, fila.id_producto, fila.id_inventario, fila.existencia]
                .some((dato) => String(dato ?? '').toLocaleLowerCase('es').includes(busqueda))
        );
    }

    function dibujar() {
        const filas = obtenerFiltradas();
        const paginas = Math.max(1, Math.ceil(filas.length / cantidadPorPagina));
        if (pagina > paginas) pagina = paginas;
        const visibles = filas.slice((pagina - 1) * cantidadPorPagina, pagina * cantidadPorPagina);

        cuerpo.innerHTML = visibles.map((fila) => `
            <tr data-id="${escapar(fila.id_producto)}">
                <td>
                    <div class="li-producto">
                        <span class="li-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        <span>
                            <strong class="d-block">${escapar(fila.producto)}</strong>
                            <span class="li-secundario">${escapar(fila.categoria)}</span>
                        </span>
                    </div>
                </td>
                <td>${escapar(fila.ubicacion || 'Sin ubicación')}</td>
                <td class="li-numero">${Number(fila.existencia) || 0}</td>
                <td class="li-accion">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-asignar-ubicacion"
                            title="${fila.id_ubicacion ? 'Cambiar ubicación' : 'Asignar ubicación'}"
                            aria-label="${fila.id_ubicacion ? 'Cambiar ubicación' : 'Asignar ubicación'} de ${escapar(fila.producto)}">
                        <i class="bi bi-geo-alt" aria-hidden="true"></i>
                        <span class="d-none d-md-inline">${fila.id_ubicacion ? 'Cambiar' : 'Asignar'}</span>
                    </button>
                </td>
            </tr>
        `).join('');

        const hayFilas = visibles.length > 0;
        tabla.hidden = !hayFilas;
        estado.hidden = hayFilas;
        if (!hayFilas) {
            tituloEstado.textContent = existencias.length === 0 ? 'No hay existencias registradas' : 'Sin resultados';
            detalleEstado.textContent = existencias.length === 0
                ? 'Todavía no hay productos en la tabla de inventario.'
                : 'Ningún producto coincide con la búsqueda.';
        }

        contador.textContent = `Total de productos: ${filas.length}`;
        dibujarPaginador(paginas);
    }

    function dibujarPaginador(totalPaginas) {
        const crearBoton = (textoBoton, destino, deshabilitado = false) =>
            `<button type="button" data-pagina="${destino}" ${deshabilitado ? 'disabled' : ''}>${textoBoton}</button>`;

        let html = crearBoton('<i class="bi bi-chevron-left" aria-hidden="true"></i>', pagina - 1, pagina <= 1);
        for (let numero = 1; numero <= totalPaginas; numero++) {
            if (totalPaginas <= 5 || numero === 1 || numero === totalPaginas || Math.abs(numero - pagina) <= 1) {
                html += crearBoton(numero, numero, false).replace('<button', `<button class="${numero === pagina ? 'activo' : ''}"`);
            }
        }
        html += crearBoton('<i class="bi bi-chevron-right" aria-hidden="true"></i>', pagina + 1, pagina >= totalPaginas);
        paginador.innerHTML = html;
    }

    buscador.addEventListener('input', () => {
        texto = buscador.value;
        pagina = 1;
        dibujar();
    });

    tamPagina.addEventListener('change', () => {
        cantidadPorPagina = Number(tamPagina.value) || 10;
        pagina = 1;
        dibujar();
    });

    paginador.addEventListener('click', (evento) => {
        const boton = evento.target.closest('button[data-pagina]');
        if (!boton || boton.disabled) return;
        pagina = Number(boton.dataset.pagina);
        dibujar();
    });

    async function cargarUbicaciones() {
        if (ubicacionesCargadas) return;

        const respuesta = await fetch('../dao/DAOInventario.php?opcion=consultarUbicaciones', {
            headers: { Accept: 'application/json' }
        });
        if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);

        const resultado = await respuesta.json();
        if (!Array.isArray(resultado)) throw new Error(resultado.mensaje || 'No se pudieron cargar las ubicaciones');
        ubicaciones = resultado;
        ubicacionesCargadas = true;
    }

    cuerpo.addEventListener('click', async (evento) => {
        const boton = evento.target.closest('.btn-asignar-ubicacion');
        if (!boton) return;

        const fila = boton.closest('tr[data-id]');
        productoSeleccionado = existencias.find((item) => String(item.id_producto) === fila?.dataset.id);
        if (!productoSeleccionado) return;

        boton.disabled = true;
        try {
            await cargarUbicaciones();
            if (ubicaciones.length === 0) throw new Error('Primero registra al menos una ubicación.');

            selectUbicacion.innerHTML = '<option value="">Seleccione una ubicación</option>' + ubicaciones.map((ubicacion) =>
                `<option value="${escapar(ubicacion.id_ubicacion)}">${escapar(ubicacion.nombre)}</option>`
            ).join('');
            selectUbicacion.value = String(productoSeleccionado.id_ubicacion ?? '');
            nombreProductoUbicacion.textContent = productoSeleccionado.producto;
            modalUbicacion?.show();
        } catch (error) {
            console.error('Error al cargar ubicaciones:', error);
            productoSeleccionado = null;
            notificar('danger', error.message || 'No se pudieron cargar las ubicaciones.');
        } finally {
            boton.disabled = false;
        }
    });

    formularioUbicacion.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        if (!productoSeleccionado) return;
        if (!formularioUbicacion.checkValidity()) {
            formularioUbicacion.reportValidity();
            return;
        }

        const textoOriginal = botonGuardarUbicacion.innerHTML;
        botonGuardarUbicacion.disabled = true;
        botonGuardarUbicacion.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Guardando...';

        try {
            const datos = new FormData();
            datos.append('opcion', 'asignarUbicacion');
            datos.append('id_producto', productoSeleccionado.id_producto);
            datos.append('id_ubicacion', selectUbicacion.value);

            const respuesta = await fetch('../dao/DAOInventario.php', { method: 'POST', body: datos });
            const resultado = await respuesta.json();
            if (!respuesta.ok || !resultado.estado) {
                throw new Error(resultado.mensaje || `Error HTTP: ${respuesta.status}`);
            }

            modalUbicacion?.hide();
            productoSeleccionado = null;
            notificar('success', resultado.mensaje || 'La ubicación se asignó correctamente.');
            await cargarExistencias();
        } catch (error) {
            console.error('Error al asignar ubicación:', error);
            notificar('danger', error.message || 'No se pudo asignar la ubicación.');
        } finally {
            botonGuardarUbicacion.disabled = false;
            botonGuardarUbicacion.innerHTML = textoOriginal;
        }
    });

    async function cargarExistencias() {
        try {
            const respuesta = await fetch('../dao/DAOInventario.php?opcion=consultar', {
                headers: { Accept: 'application/json' }
            });
            if (!respuesta.ok) throw new Error(`Error HTTP: ${respuesta.status}`);

            const resultado = await respuesta.json();
            if (!Array.isArray(resultado)) throw new Error(resultado.mensaje || 'Respuesta no válida');
            existencias = resultado;
            dibujar();
        } catch (error) {
            console.error('Error al cargar existencias:', error);
            tituloEstado.textContent = 'No se pudieron cargar las existencias';
            detalleEstado.textContent = 'Recarga la página e intenta de nuevo.';
            tabla.hidden = true;
            estado.hidden = false;
            contador.textContent = 'Total de productos: 0';
            if (typeof notificar === 'function') notificar('danger', 'No se pudieron cargar las existencias.');
        }
    }

    await cargarExistencias();
});
