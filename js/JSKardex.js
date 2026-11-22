document.addEventListener('DOMContentLoaded', () => {
  const cuerpo = document.getElementById('cuerpoKardex');
  if (!cuerpo) return;
  const producto = document.getElementById('filtroProductoKardex');
  const tipo = document.getElementById('filtroTipoKardex');
  const desde = document.getElementById('filtroDesdeKardex');
  const hasta = document.getElementById('filtroHastaKardex');
  const limite = document.getElementById('limiteKardex');
  const tabla = document.getElementById('tablaKardex');
  const estado = document.getElementById('estadoKardex');
  const titulo = document.getElementById('tituloKardex');
  const detalle = document.getElementById('detalleKardex');
  const totalEl = document.getElementById('totalKardex');
  const resumen = document.getElementById('resumenKardex');
  const paginaEl = document.getElementById('paginaKardex');
  const anterior = document.getElementById('anteriorKardex');
  const siguiente = document.getElementById('siguienteKardex');
  let pagina = 1;
  let solicitud = 0;
  let opcionesCargadas = false;

  function escapar(valor) {
    return String(valor ?? '').replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }
  function moneda(valor) { return '$' + Number(valor ?? 0).toFixed(2); }
  function llenar(select, opciones, texto, clave) {
    const valor = select.value;
    select.innerHTML = '<option value="">' + texto + '</option>' + opciones.map((x) =>
      '<option value="' + escapar(x[clave]) + '">' + escapar(x.nombre) + '</option>'
    ).join('');
    if ([...select.options].some((x) => x.value === valor)) select.value = valor;
  }
  function vacio(t, d, error) {
    tabla.hidden = true;
    estado.hidden = false;
    titulo.textContent = t;
    detalle.textContent = d;
    estado.querySelector('i').className = error ? 'bi bi-exclamation-circle text-danger' : 'bi bi-inboxes';
  }
  function origen(m) {
    if (m.id_compra) return 'Compra #' + m.id_compra;
    if (m.id_venta) return 'Venta #' + m.id_venta;
    if (m.id_ajuste) return 'Ajuste #' + m.id_ajuste;
    return '—';
  }
  function dibujar(filas) {
    if (!filas.length) {
      cuerpo.innerHTML = '';
      vacio('No hay movimientos para mostrar', 'Prueba con otros filtros o registra una compra o un ajuste.');
      return;
    }
    cuerpo.innerHTML = filas.map((m) => {
      const n = m.naturaleza === 'salida' ? 'salida' : 'entrada';
      const signo = n === 'salida' ? '− ' : '+ ';
      return '<tr><td class="text-nowrap">' + escapar(m.fecha_hora) + '</td>' +
        '<td><strong>' + escapar(m.producto) + '</strong><span class="kd-muted d-block">ID ' + escapar(m.id_producto) + '</span></td>' +
        '<td><span class="kd-type ' + n + '">' + escapar(m.tipo_movimiento) + '</span></td>' +
        '<td class="kd-number"><span class="kd-qty ' + n + '">' + signo + Number(m.cantidad).toLocaleString('es-SV') + '</span></td>' +
        '<td class="kd-number">' + moneda(m.costo_unitario) + '</td><td class="kd-number">' + Number(m.existencias).toLocaleString('es-SV') + '</td>' +
        '<td class="kd-number">' + moneda(m.costo_promedio) + '</td><td class="kd-number fw-semibold">' + moneda(m.saldo_total) + '</td>' +
        '<td><strong>#' + escapar(m.id_usuario) + '</strong><span class="kd-muted d-block">' + escapar(m.usuario) + '</span></td>' +
        '<td>' + escapar(origen(m)) + '</td></tr>';
    }).join('');
    estado.hidden = true;
    tabla.hidden = false;
  }
  async function cargar() {
    const idSolicitud = ++solicitud;
    const q = new URLSearchParams({opcion:'consultar', pagina:String(pagina), limite:limite.value});
    if (producto.value) q.set('id_producto', producto.value);
    if (tipo.value) q.set('id_tipo_movimiento', tipo.value);
    if (desde.value) q.set('fecha_desde', desde.value);
    if (hasta.value) q.set('fecha_hasta', hasta.value);
    vacio('Cargando movimientos...', 'Un momento, por favor.');
    try {
      const respuesta = await fetch('../dao/DAOKardex.php?' + q.toString(), {headers:{Accept:'application/json'}, cache:'no-store'});
      const r = await respuesta.json();
      if (idSolicitud !== solicitud) return;
      if (!respuesta.ok || !r.estado || !r.datos) throw new Error(r.mensaje || 'No se pudieron cargar los movimientos.');
      const d = r.datos;
      if (!opcionesCargadas) {
        llenar(producto, d.productos || [], 'Todos los productos', 'id_producto');
        llenar(tipo, d.tipos || [], 'Todos los tipos', 'id_tipo_movimiento');
        opcionesCargadas = true;
      }
      dibujar(d.movimientos || []);
      const total = Number(d.total) || 0, pag = Number(d.pagina) || 1, pags = Number(d.total_paginas) || 1, tam = Number(d.limite) || 10;
      totalEl.textContent = total + (total === 1 ? ' movimiento' : ' movimientos');
      paginaEl.textContent = pag + ' / ' + pags;
      anterior.disabled = pag <= 1;
      siguiente.disabled = pag >= pags;
      resumen.textContent = total ? 'Mostrando ' + ((pag - 1) * tam + 1) + '–' + Math.min(pag * tam, total) + ' de ' + total + ' movimientos' : 'Mostrando 0 movimientos';
    } catch (error) {
      if (idSolicitud !== solicitud) return;
      console.error('Error al cargar el kardex:', error);
      totalEl.textContent = 'No disponible';
      resumen.textContent = 'No se pudieron cargar los movimientos';
      vacio('No se pudo cargar el kardex', error.message || 'Intenta recargar la página.', true);
    }
  }
  [producto, tipo, desde, hasta].forEach((x) => x.addEventListener('change', () => { pagina = 1; cargar(); }));
  limite.addEventListener('change', () => { pagina = 1; cargar(); });
  document.getElementById('limpiarKardex').addEventListener('click', () => {
    producto.value = ''; tipo.value = ''; desde.value = ''; hasta.value = ''; pagina = 1; cargar();
  });
  anterior.addEventListener('click', () => { if (pagina > 1) { pagina--; cargar(); } });
  siguiente.addEventListener('click', () => { pagina++; cargar(); });
  cargar();
});