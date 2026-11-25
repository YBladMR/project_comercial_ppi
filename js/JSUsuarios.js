document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formCrearUsuario');
  const selectRol = document.getElementById('rolUsuario');
  const tabla = document.getElementById('tablaUsuarios');
  const panelCredencial = document.getElementById('credencialTemporal');
  const valorCredencial = document.getElementById('valorCredencial');
  const tituloCredencial = document.getElementById('tituloCredencial');
  const botonCopiar = document.getElementById('copiarCredencial');
  if (!form || !tabla) return;

  let roles = [];
  let usuarios = [];

  const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[caracter]);

  async function solicitar(datos) {
    const respuesta = await fetch('../dao/DAOUsuarios.php', { method: 'POST', body: datos });
    const resultado = await respuesta.json();
    if (!respuesta.ok || resultado.estado === false) throw new Error(resultado.mensaje || 'No se pudo procesar la solicitud.');
    return resultado;
  }

  function mostrarCredencial(contrasena, usuario = '') {
    tituloCredencial.textContent = usuario ? `Contraseña temporal para ${usuario}` : 'Contraseña temporal';
    valorCredencial.textContent = contrasena;
    panelCredencial.hidden = false;
    panelCredencial.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function dibujarUsuarios() {
    if (!usuarios.length) {
      tabla.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary">No hay cuentas registradas.</td></tr>';
      return;
    }
    tabla.innerHTML = usuarios.map((u) => {
      const selectorRol = u.protegido
        ? '<span class="badge text-bg-primary">Administrador raíz</span>'
        : `<select class="form-select form-select-sm" data-campo="id_rol" aria-label="Rol de ${esc(u.nombre)}">${roles.map((r) => `<option value="${r.id_rol}" ${Number(r.id_rol) === Number(u.id_rol) ? 'selected' : ''}>${esc(r.nombre)}</option>`).join('')}</select>`;
      const selectorEstado = u.protegido
        ? `<span class="badge ${u.estado === 'activo' ? 'text-bg-success' : 'text-bg-secondary'}">${esc(u.estado)}</span>`
        : `<select class="form-select form-select-sm" data-campo="estado" aria-label="Estado de ${esc(u.nombre)}"><option value="activo" ${u.estado === 'activo' ? 'selected' : ''}>Activo</option><option value="inactivo" ${u.estado === 'inactivo' ? 'selected' : ''}>Inactivo</option></select>`;
      const correo = u.protegido
        ? esc(u.correo_electronico || '—')
        : `<input class="form-control form-control-sm" type="email" maxlength="254" data-campo="correo_electronico" value="${esc(u.correo_electronico || '')}" autocomplete="off" aria-label="Correo de ${esc(u.nombre)}">`;
      const acciones = u.protegido
        ? '<span class="text-body-secondary small">Protegida</span>'
        : `<div class="d-flex flex-wrap gap-1"><button class="btn btn-sm btn-outline-primary" type="button" data-accion="guardar" data-id="${u.id_usuario}" title="Guardar correo, rol y estado"><i class="bi bi-save"></i></button><button class="btn btn-sm btn-outline-warning" type="button" data-accion="restablecer" data-id="${u.id_usuario}" title="Generar contraseña temporal"><i class="bi bi-key"></i></button></div>`;
      return `<tr data-id="${u.id_usuario}"><td>${esc(u.nombre)}</td><td>${esc(u.usuario)}</td><td>${correo}</td><td>${selectorRol}</td><td>${selectorEstado}</td><td>${esc(u.fecha_creacion)}</td><td>${acciones}</td></tr>`;
    }).join('');
  }

  async function cargarRoles() {
    const datos = new FormData();
    datos.append('opcion', 'consultarRoles');
    const resultado = await solicitar(datos);
    roles = resultado.roles || [];
    selectRol.innerHTML = '<option value="">Selecciona un rol</option>' + roles.map((r) => `<option value="${r.id_rol}">${esc(r.nombre)}</option>`).join('');
    if (!roles.length) {
      selectRol.disabled = true;
      form.querySelector('[type="submit"]').disabled = true;
      selectRol.innerHTML = '<option value="">Primero crea un rol</option>';
    }
  }

  async function cargarUsuarios() {
    tabla.innerHTML = '<tr><td colspan="7" class="text-center text-body-secondary">Cargando cuentas...</td></tr>';
    const datos = new FormData();
    datos.append('opcion', 'consultar');
    const resultado = await solicitar(datos);
    usuarios = resultado.usuarios || [];
    dibujarUsuarios();
  }

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const nombreLogin = form.elements.usuario.value.trim();
    if (/^\d{8}-\d$/.test(nombreLogin)) {
      notificar('warning', 'El DUI no se debe utilizar como nombre de usuario.');
      return;
    }
    const datos = new FormData(form);
    datos.append('opcion', 'crear');
    const boton = form.querySelector('[type="submit"]');
    boton.disabled = true;
    try {
      const resultado = await solicitar(datos);
      form.reset();
      mostrarCredencial(resultado.contrasena_temporal, resultado.usuario);
      notificar('success', resultado.mensaje);
      await cargarUsuarios();
    } catch (error) {
      notificar('danger', error.message);
    } finally {
      boton.disabled = false;
    }
  });

  tabla.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) return;
    const fila = boton.closest('tr[data-id]');
    const usuario = usuarios.find((item) => Number(item.id_usuario) === Number(boton.dataset.id));
    if (!fila || !usuario) return;
    const datos = new FormData();
    datos.append('id_usuario', usuario.id_usuario);

    if (boton.dataset.accion === 'guardar') {
      datos.append('opcion', 'actualizar');
      datos.append('id_rol', fila.querySelector('[data-campo="id_rol"]').value);
      datos.append('estado', fila.querySelector('[data-campo="estado"]').value);
      datos.append('correo_electronico', fila.querySelector('[data-campo="correo_electronico"]').value.trim());
    } else if (boton.dataset.accion === 'restablecer') {
      const confirmar = typeof confirmarAccion === 'function'
        ? await confirmarAccion(`¿Generar una nueva contraseña temporal para ${usuario.nombre}?`, 'Restablecer contraseña', 'warning')
        : window.confirm(`¿Restablecer la contraseña de ${usuario.nombre}?`);
      if (!confirmar) return;
      datos.append('opcion', 'restablecerContrasena');
    } else return;

    boton.disabled = true;
    try {
      const resultado = await solicitar(datos);
      if (resultado.contrasena_temporal) mostrarCredencial(resultado.contrasena_temporal, usuario.usuario);
      notificar('success', resultado.mensaje);
      await cargarUsuarios();
    } catch (error) {
      notificar('danger', error.message);
    } finally {
      boton.disabled = false;
    }
  });

  botonCopiar.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(valorCredencial.textContent);
      notificar('success', 'Contraseña copiada. Entrégala al empleado de forma segura.');
    } catch (_) {
      notificar('warning', 'Selecciona y copia la contraseña manualmente.');
    }
  });

  Promise.all([cargarRoles(), cargarUsuarios()]).catch((error) => {
    tabla.innerHTML = `<tr><td colspan="7" class="text-danger">${esc(error.message)}</td></tr>`;
    notificar('danger', 'No se pudieron cargar los datos de usuarios.');
  });
});
