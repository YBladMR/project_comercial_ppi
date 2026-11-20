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

    let existencias = [];
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
            [fila.producto, fila.ubicacion, fila.id_producto]
                .some((dato) => String(dato ?? '').toLocaleLowerCase('es').includes(busqueda))
        );
    }

    function dibujar() {
        const filas = obtenerFiltradas();
        const paginas = Math.max(1, Math.ceil(filas.length / cantidadPorPagina));
        if (pagina > paginas) pagina = paginas;
        const visibles = filas.slice((pagina - 1) * cantidadPorPagina, pagina * cantidadPorPagina);

        cuerpo.innerHTML = visibles.map((fila) => `
            <tr>
                <td>
                    <div class="li-producto">
                        <span class="li-icono"><i class="bi bi-box-seam" aria-hidden="true"></i></span>
                        <span>
                            <strong class="d-block">${escapar(fila.producto)}</strong>
                            <span class="li-secundario">ID: ${escapar(fila.id_producto)}</span>
                        </span>
                    </div>
                </td>
                <td>${escapar(fila.ubicacion || 'Sin ubicación')}</td>
                <td class="li-numero">${Number(fila.existencia) || 0}</td>
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
});
