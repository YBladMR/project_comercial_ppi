document.addEventListener('DOMContentLoaded', () => {
    const URL_DAO = '../dao/DAOCreditos.php';
    const cuerpo = document.getElementById('cuerpoCreditos');
    const filtroTexto = document.getElementById('filtroTexto');
    const chips = [...document.querySelectorAll('.lc-chip')];
    const mensajeVacio = document.getElementById('mensajeVacio');
    const selectTam = document.getElementById('tamPagina');
    const anterior = document.getElementById('paginaAnterior');
    const siguiente = document.getElementById('paginaSiguiente');
    let creditos = [];
    let estadoFiltro = '';
    let pagina = 1;

    const dinero = (n) => '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const escapar = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
    const fecha = (valor) => valor ? new Date(`${valor.replace(' ', 'T')}`).toLocaleDateString('es-SV') : '—';

    function estadoLabel(estado) {
        return estado === 'en_mora' ? 'En mora' : estado === 'pagado' ? 'Pagado' : 'Al día';
    }

    function filtrados() {
        const texto = filtroTexto.value.trim().toLocaleLowerCase('es');
        return creditos.filter((credito) => {
            if (estadoFiltro && credito.estado !== estadoFiltro) return false;
            const buscar = `${credito.id_credito} ${credito.id_venta} ${credito.cliente} ${credito.dui}`.toLocaleLowerCase('es');
            return !texto || buscar.includes(texto);
        });
    }

    function dibujar() {
        const lista = filtrados();
        const porPagina = Number(selectTam.value);
        const totalPaginas = porPagina === 0 ? 1 : Math.max(1, Math.ceil(lista.length / porPagina));
        pagina = Math.min(pagina, totalPaginas);
        const inicio = porPagina === 0 ? 0 : (pagina - 1) * porPagina;
        const visibles = porPagina === 0 ? lista : lista.slice(inicio, inicio + porPagina);

        cuerpo.innerHTML = visibles.map((c) => `
            <tr>
              <td><strong>C-${String(c.id_credito).padStart(6, '0')}</strong><div class="small text-body-secondary">Venta V-${String(c.id_venta).padStart(6, '0')}</div></td>
              <td>${escapar(fecha(c.fecha_hora))}</td>
              <td>${escapar(c.cliente)}<div class="small text-body-secondary">${escapar(c.dui)}</div></td>
              <td class="text-end">${dinero(c.capital_financiado)}</td>
              <td class="text-end">${dinero(c.saldo_capital)}</td>
              <td class="text-end">${dinero(Number(c.saldo_interes) + Number(c.saldo_mora))}</td>
              <td class="text-end fw-semibold">${dinero(c.saldo_actual)}</td>
              <td><span class="lc-pill ${escapar(c.estado)}">${estadoLabel(c.estado)}</span></td>
              <td class="text-center"><a class="btn btn-sm btn-outline-primary" href="Detalle-Credito.php?id_credito=${encodeURIComponent(c.id_credito)}" aria-label="Ver crédito"><i class="bi bi-eye"></i> Ver</a></td>
            </tr>`).join('');

        mensajeVacio.hidden = visibles.length !== 0;
        document.getElementById('totalRegistros').textContent = `Total de registros: ${lista.length}`;
        document.getElementById('paginaActual').textContent = `${pagina} / ${totalPaginas}`;
        anterior.disabled = pagina <= 1;
        siguiente.disabled = pagina >= totalPaginas;
    }

    function resumen() {
        document.getElementById('totalCreditos').textContent = creditos.length;
        document.getElementById('creditosAlDia').textContent = creditos.filter((c) => c.estado === 'al_dia').length;
        document.getElementById('creditosEnMora').textContent = creditos.filter((c) => c.estado === 'en_mora').length;
        document.getElementById('saldoTotal').textContent = dinero(creditos.reduce((s, c) => s + Number(c.saldo_actual), 0));
    }

    async function cargar() {
        try {
            const respuesta = await fetch(`${URL_DAO}?opcion=listar`, { credentials: 'same-origin' });
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.estado) throw new Error(datos.mensaje || 'No se pudieron cargar los créditos.');
            creditos = datos.creditos || [];
            resumen();
            dibujar();
        } catch (error) {
            console.error(error);
            cuerpo.innerHTML = '<tr><td colspan="9" class="text-center text-danger py-4">No se pudieron cargar los créditos.</td></tr>';
            if (typeof notificar === 'function') notificar('danger', error.message);
        }
    }

    filtroTexto.addEventListener('input', () => { pagina = 1; dibujar(); });
    selectTam.addEventListener('change', () => { pagina = 1; dibujar(); });
    anterior.addEventListener('click', () => { pagina--; dibujar(); });
    siguiente.addEventListener('click', () => { pagina++; dibujar(); });
    chips.forEach((chip) => chip.addEventListener('click', () => {
        chips.forEach((otro) => otro.classList.remove('activo'));
        chip.classList.add('activo');
        estadoFiltro = chip.dataset.estado || '';
        pagina = 1;
        dibujar();
    }));

    cargar();
});
