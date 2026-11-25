document.addEventListener('DOMContentLoaded', () => {
    const URL_DAO = '../dao/DAOCreditos.php';
    const idCredito = document.body.dataset.idCredito;
    const puedePagar = document.body.dataset.puedePagar === '1';
    const tokenCsrf = document.body.dataset.csrfToken || '';
    const dinero = (n) => '$' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const escapar = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;' }[c]));
    const fecha = (valor) => valor ? new Date(`${valor}T00:00:00`).toLocaleDateString('es-SV') : '—';
    let creditoActual = null;

    async function pedir(url, opciones = {}) {
        const configuracion = { credentials: 'same-origin', ...opciones };
        if (configuracion.method === 'POST') {
            configuracion.headers = { ...(configuracion.headers || {}), 'X-CSRF-Token': tokenCsrf };
        }
        const respuesta = await fetch(url, configuracion);
        const datos = await respuesta.json();
        if (!respuesta.ok || datos.estado === false) throw new Error(datos.mensaje || 'No se pudo completar la operación.');
        return datos;
    }

    function dibujarCredito(datos) {
        const c = datos.credito;
        creditoActual = c;
        document.getElementById('tituloCredito').textContent = `Crédito C-${String(c.id_credito).padStart(6, '0')}`;
        document.getElementById('subtituloCredito').textContent = `${c.cliente} · DUI ${c.dui} · Venta V-${String(c.id_venta).padStart(6, '0')} · Prima ${dinero(c.prima)} · Interés ${c.tasa_interes}% anual · Mora ${c.tasa_interes_moratoria}% anual`;
        const etiquetaEstado = document.getElementById('estadoCredito');
        etiquetaEstado.className = `dc-estado ${c.estado}`;
        etiquetaEstado.textContent = c.estado === 'en_mora' ? 'En mora' : c.estado === 'pagado' ? 'Pagado' : 'Al día';
        document.getElementById('capitalInicial').textContent = dinero(c.capital_financiado);
        document.getElementById('capitalPendiente').textContent = dinero(c.saldo_capital);
        document.getElementById('interesPendiente').textContent = dinero(c.saldo_interes);
        document.getElementById('moraPendiente').textContent = dinero(c.saldo_mora);
        document.getElementById('saldoActual').textContent = dinero(c.saldo_actual);

        document.getElementById('cuerpoPlan').innerHTML = datos.cuotas.map((q) => {
            const etiqueta = q.estado_real === 'pagado' ? 'Pagada' : q.estado_real === 'abono_parcial' ? 'Abono parcial' : 'Pendiente';
            return `<tr>
              <td>${q.numero_cuota}</td><td>${fecha(q.fecha_vencimiento)}</td>
              <td class="text-end">${dinero(q.capital_proyectado)}</td>
              <td class="text-end">${dinero(q.interes_proyectado)}</td>
              <td class="text-end">${dinero(q.monto_cuota)}</td>
              <td class="text-end">${dinero(q.capital_pendiente)}</td>
              <td><span class="dc-pill ${escapar(q.estado_real)}">${etiqueta}</span></td>
            </tr>`;
        }).join('') || '<tr><td colspan="7" class="text-center py-4">El crédito no tiene cuotas.</td></tr>';

        const tbodyPagos = document.getElementById('cuerpoEstadoCuenta');
        const pagos = datos.pagos || [];
        tbodyPagos.innerHTML = pagos.map((p) => {
            const tipo = p.tipo_pago === 'prima' ? 'Prima recibida' : p.tipo_pago === 'liquidacion' ? 'Liquidación' : p.tipo_pago === 'abono_capital' ? 'Abono anticipado' : 'Pago o abono a cuotas';
            const fechaHora = p.fecha_pago ? new Date(`${p.fecha_pago.replace(' ', 'T')}`).toLocaleString('es-SV') : '—';
            const esPrima = p.tipo_pago === 'prima';
            return `<tr><td>${escapar(fechaHora)}</td><td>${tipo}</td><td>${escapar(p.usuario)}</td>
              <td class="text-end">${dinero(p.monto)}</td><td class="text-end">${esPrima ? '—' : dinero(p.abono_interes)}</td>
              <td class="text-end">${esPrima ? '—' : dinero(p.abono_mora)}</td><td class="text-end">${esPrima ? '—' : dinero(p.capital_aplicado)}</td>
              <td class="text-end fw-semibold">${dinero(p.saldo_final)}</td></tr>`;
        }).join('');
        document.getElementById('sinMovimientos').hidden = pagos.length !== 0;

        if (puedePagar) {
            const form = document.getElementById('formPago');
            const selectTipo = document.getElementById('tipoPago');
            const inputMonto = document.getElementById('montoPago');
            const cuotaPendiente = datos.cuotas.find((q) => q.estado_real !== 'pagado');
            const terminado = c.estado === 'pagado';
            form.querySelectorAll('input,select,button').forEach((el) => { el.disabled = terminado; });
            if (!terminado && cuotaPendiente) {
                const saldoCuota = Number(cuotaPendiente.capital_pendiente) + Number(cuotaPendiente.interes_pendiente) + Number(cuotaPendiente.mora_pendiente);
                inputMonto.value = saldoCuota.toFixed(2);
            }
            actualizarAyudaPago();
        }
    }

    function actualizarAyudaPago() {
        if (!puedePagar) return;
        const tipo = document.getElementById('tipoPago').value;
        const inputMonto = document.getElementById('montoPago');
        const ayuda = document.getElementById('ayudaTipoPago');
        if (tipo === 'liquidacion') {
            inputMonto.value = Number(creditoActual?.saldo_actual || 0).toFixed(2);
            inputMonto.readOnly = true;
            ayuda.textContent = 'Se cobrará el capital pendiente más el interés corriente y la mora acumulados hasta hoy. No se incluye interés futuro.';
        } else {
            inputMonto.readOnly = false;
            ayuda.textContent = tipo === 'abono_capital'
                ? 'Puedes adelantar un monto. Primero se cubren la mora y el interés ya acumulados; el remanente reduce capital.'
                : 'Puedes pagar una cuota completa, hacer un abono menor o pagar más. El excedente, después de intereses y mora acumulados, reduce capital.';
        }
    }

    async function cargar() {
        try {
            const datos = await pedir(`${URL_DAO}?opcion=consultar&id_credito=${encodeURIComponent(idCredito)}`);
            dibujarCredito(datos);
        } catch (error) {
            console.error(error);
            document.getElementById('tituloCredito').textContent = 'No se pudo cargar el crédito';
            if (typeof notificar === 'function') notificar('danger', error.message);
        }
    }

    if (puedePagar) {
        document.getElementById('tipoPago').addEventListener('change', async () => {
            if (document.getElementById('tipoPago').value === 'liquidacion') {
                await cargar();
            }
            actualizarAyudaPago();
        });
        document.getElementById('formPago').addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const boton = document.getElementById('btnRegistrarPago');
            const tipoPago = document.getElementById('tipoPago').value;
            if (tipoPago === 'liquidacion') await cargar();
            const monto = Number(document.getElementById('montoPago').value);
            if (!Number.isFinite(monto) || monto <= 0) {
                notificar('danger', 'Ingresa un monto mayor que cero.');
                return;
            }
            const confirmacion = await confirmarAccion(
                `¿Confirmas registrar ${tipoPago === 'liquidacion' ? 'la liquidación' : 'el pago'} por ${dinero(monto)} con fecha y hora actuales?`,
                'Sí, registrar',
                'success'
            );
            if (!confirmacion) return;

            const datos = new FormData();
            datos.append('opcion', 'registrarPago');
            datos.append('id_credito', idCredito);
            datos.append('tipo_pago', tipoPago);
            datos.append('monto', monto.toFixed(2));
            boton.disabled = true;
            try {
                const respuesta = await pedir(URL_DAO, { method: 'POST', body: datos });
                notificar('success', `${respuesta.mensaje} Saldo actual: ${dinero(respuesta.saldo_final)}. No se generó ticket.`);
                await cargar();
            } catch (error) {
                notificar('danger', error.message);
            } finally {
                boton.disabled = creditoActual?.estado === 'pagado';
            }
        });
    }

    cargar();
});
