document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formRol');
  const tabla = document.getElementById('tablaRoles');
  const listaPermisos = document.getElementById('listaPermisos');
  const btnCancelar = document.getElementById('cancelarEdicionRol');
  const titulo = document.getElementById('tituloFormularioRol');
  if (!form || !tabla) return;

  let roles = [];
  let permisos = [];
  const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (caracter) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
  })[caracter]);

  async function solicitar(datos) {
    const respuesta = await fetch('../dao/DAORoles.php', { method: 'POST', body: datos });
    const resultado = await respuesta.json();
    if (!respuesta.ok || resultado.estado === false) throw new Error(resultado.mensaje || 'No se pudo procesar la solicitud.');
    return resultado;
  }

  function dibujarPermisos(seleccionados = []) {
    const ids = new Set(seleccionados.map(Number));
    listaPermisos.innerHTML = permisos.map((p) => `<div class="col-md-6 col-xl-4"><label class="form-check border rounded p-2 h-100"><input class="form-check-input me-2" type="checkbox" name="permisos[]" value="${p.id_permiso}" ${ids.has(Number(p.id_permiso)) ? 'checked' : ''}><span><strong>${esc(p.codigo)}</strong><br><small class="text-body-secondary">${esc(p.descripcion)}</small></span></label></div>`).join('');
  }

  function dibujarRoles() {
    if (!roles.length) {
      tabla.innerHTML = '<tr><td colspan="5" class="text-center text-body-secondary">No hay roles registrados.</td></tr>';
      return;
    }
    tabla.innerHTML = roles.map((r) => {
      const acciones = r.protegido
        ? '<span class="badge text-bg-primary">Protegido</span>'
        : `<div class="d-flex gap-1"><button class="btn btn-sm btn-outline-primary" type="button" data-accion="editar" data-id="${r.id_rol}" title="Editar"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" type="button" data-accion="eliminar" data-id="${r.id_rol}" title="Eliminar" ${r.total_usuarios > 0 ? 'disabled' : ''}><i class="bi bi-trash"></i></button></div>`;
      const resumenPermisos = r.protegido ? 'Acceso raíz' : `${r.permisos.length} permiso(s)`;
      return `<tr><td>${esc(r.nombre)}</td><td>${esc(r.descripcion || '—')}</td><td>${r.total_usuarios}</td><td>${resumenPermisos}</td><td>${acciones}</td></tr>`;
    }).join('');
  }

  function limpiarFormulario() {
    form.reset();
    document.getElementById('idRol').value = '0';
    titulo.textContent = 'Crear rol';
    btnCancelar.hidden = true;
    dibujarPermisos();
  }

  async function cargar() {
    tabla.innerHTML = '<tr><td colspan="5" class="text-center text-body-secondary">Cargando roles...</td></tr>';
    const datos = new FormData();
    datos.append('opcion', 'consultar');
    const resultado = await solicitar(datos);
    roles = resultado.roles || [];
    permisos = resultado.permisos || [];
    dibujarPermisos();
    dibujarRoles();
  }

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const datos = new FormData();
    datos.append('opcion', 'guardar');
    datos.append('id_rol', document.getElementById('idRol').value);
    datos.append('nombre', document.getElementById('nombreRol').value.trim());
    datos.append('descripcion', document.getElementById('descripcionRol').value.trim());
    form.querySelectorAll('input[name="permisos[]"]:checked').forEach((input) => datos.append('permisos[]', input.value));
    try {
      const resultado = await solicitar(datos);
      notificar('success', resultado.mensaje);
      limpiarFormulario();
      await cargar();
    } catch (error) {
      notificar('danger', error.message);
    }
  });

  tabla.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) return;
    const rol = roles.find((item) => Number(item.id_rol) === Number(boton.dataset.id));
    if (!rol || rol.protegido) return;
    if (boton.dataset.accion === 'editar') {
      document.getElementById('idRol').value = String(rol.id_rol);
      document.getElementById('nombreRol').value = rol.nombre;
      document.getElementById('descripcionRol').value = rol.descripcion || '';
      titulo.textContent = `Editar rol: ${rol.nombre}`;
      btnCancelar.hidden = false;
      dibujarPermisos(rol.permisos);
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      return;
    }
    if (boton.dataset.accion === 'eliminar') {
      const confirmar = typeof confirmarAccion === 'function'
        ? await confirmarAccion(`¿Eliminar el rol ${rol.nombre}?`, 'Eliminar rol', 'danger')
        : window.confirm(`¿Eliminar el rol ${rol.nombre}?`);
      if (!confirmar) return;
      const datos = new FormData();
      datos.append('opcion', 'eliminar');
      datos.append('id_rol', rol.id_rol);
      boton.disabled = true;
      try {
        const resultado = await solicitar(datos);
        notificar('success', resultado.mensaje);
        await cargar();
      } catch (error) {
        notificar('danger', error.message);
      } finally {
        boton.disabled = false;
      }
    }
  });

  btnCancelar.addEventListener('click', limpiarFormulario);
  cargar().catch((error) => {
    tabla.innerHTML = `<tr><td colspan="5" class="text-danger">${esc(error.message)}</td></tr>`;
    notificar('danger', 'No se pudieron cargar los roles y permisos.');
  });
});
