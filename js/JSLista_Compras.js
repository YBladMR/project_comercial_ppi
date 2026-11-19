document.addEventListener("DOMContentLoaded", async () => {
  const cuerpo = document.getElementById("cuerpoCompras");
  if (!cuerpo) return;

  const filtroEstado = document.getElementById("filtroEstadoCompra");
  const totalCompras = document.getElementById("totalCompras");
  const usuarioAnulacion = document.getElementById("usuarioAnulacion");
  const avisoUsuario = document.getElementById("avisoUsuarioAnular");
  const modalDetalleEl = document.getElementById("modalDetalleCompra");
  const contenidoDetalle = document.getElementById("contenidoDetalleCompra");
  const modalDetalle =
    typeof coreui !== "undefined" && coreui.Modal
      ? coreui.Modal.getOrCreateInstance(modalDetalleEl)
      : null;
  let compras = [];
  let usuarios = [];

  function escapar(valor) {
    return String(valor ?? "").replace(
      /[&<>"']/g,
      (c) =>
        ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        })[c],
    );
  }

  function moneda(valor) {
    return "$" + Number(valor ?? 0).toFixed(2);
  }

  function dibujar() {
    const estado = filtroEstado.value;
    const lista = compras.filter((c) => !estado || c.estado === estado);
    totalCompras.textContent = `Total de registros: ${lista.length}`;
    if (lista.length === 0) {
      cuerpo.innerHTML =
        '<tr><td colspan="10" class="text-center py-4">No hay compras para mostrar.</td></tr>';
      return;
    }

    cuerpo.innerHTML = lista
      .map((c) => {
        const registrada = c.estado === "registrada";
        return `
        <tr data-id="${escapar(c.id_compra)}">
          <td>#${escapar(c.id_compra)}</td>
          <td>${escapar(c.fecha)}</td>
          <td>${escapar(c.proveedor)}</td>
          <td>${escapar(c.usuario)}</td>
          <td class="text-center">${Number(c.lineas)}</td>
          <td class="text-end">${moneda(c.subtotal)}</td>
          <td class="text-end">${moneda(c.iva_credito)}</td>
          <td class="text-end fw-semibold">${moneda(c.total)}</td>
          <td class="text-center"><span class="badge ${registrada ? "text-bg-success" : "text-bg-secondary"}">${registrada ? "Registrada" : "Anulada"}</span></td>
          <td><span class="compras-acciones">
            <button type="button" class="btn btn-outline-primary btn-sm btn-ver-compra" aria-label="Ver detalle" title="Ver detalle"><i class="bi bi-eye"></i></button>
            ${registrada ? `<button type="button" class="btn btn-outline-danger btn-sm btn-anular-compra" ${usuarios.length === 0 || !usuarioAnulacion.value ? "disabled" : ""} aria-label="Anular compra" title="Anular compra"><i class="bi bi-x-circle"></i></button>` : ""}
          </span></td>
        </tr>`;
      })
      .join("");
  }

  async function cargarUsuarios() {
    const datos = new FormData();
    datos.append("opcion", "consultarCatalogos");
    const respuesta = await fetch("../dao/DAOCompras.php", {
      method: "POST",
      body: datos,
    });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    const resultado = await respuesta.json();
    if (!resultado.estado)
      throw new Error(
        resultado.mensaje || "No se pudieron cargar los usuarios",
      );
    usuarios = resultado.datos.usuarios || [];
    usuarioAnulacion.innerHTML =
      '<option value="">Usuario que anula</option>' +
      usuarios
        .map(
          (u) =>
            `<option value="${escapar(u.id_usuario)}">${escapar(u.nombre)}</option>`,
        )
        .join("");
    avisoUsuario.hidden = usuarios.length > 0;
    if (usuarios.length === 0) {
      avisoUsuario.textContent =
        "No hay usuarios activos. Las compras se pueden consultar, pero para anularlas se requiere un usuario activo.";
    }
  }

  async function cargarCompras() {
    cuerpo.innerHTML =
      '<tr><td colspan="10" class="text-center py-4">Cargando compras...</td></tr>';
    const datos = new FormData();
    datos.append("opcion", "consultar");
    const respuesta = await fetch("../dao/DAOCompras.php", {
      method: "POST",
      body: datos,
    });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    const resultado = await respuesta.json();
    if (!resultado.estado || !Array.isArray(resultado.datos))
      throw new Error(resultado.mensaje || "Respuesta no válida");
    compras = resultado.datos;
    dibujar();
  }

  async function verDetalle(idCompra) {
    const datos = new FormData();
    datos.append("opcion", "consultarDetalle");
    datos.append("id_compra", idCompra);
    const respuesta = await fetch("../dao/DAOCompras.php", {
      method: "POST",
      body: datos,
    });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    const resultado = await respuesta.json();
    if (!resultado.estado)
      throw new Error(resultado.mensaje || "No se pudo cargar el detalle");

    const { compra, detalles } = resultado.datos;
    document.getElementById("tituloDetalleCompra").textContent =
      `Compra #${compra.id_compra}`;
    contenidoDetalle.innerHTML = `
      <div class="row g-2 mb-3">
        <div class="col-md-6"><strong>Proveedor:</strong> ${escapar(compra.proveedor)}</div>
        <div class="col-md-6"><strong>Fecha:</strong> ${escapar(compra.fecha)}</div>
        <div class="col-md-6"><strong>Registró:</strong> ${escapar(compra.usuario)}</div>
        <div class="col-md-6"><strong>Estado:</strong> ${escapar(compra.estado)}</div>
      </div>
      <div class="table-responsive"><table class="table table-sm table-bordered align-middle">
        <thead class="table-light"><tr><th>Producto</th><th class="text-end">Cantidad</th><th class="text-end">Costo unitario</th><th class="text-end">Subtotal</th></tr></thead>
        <tbody>${detalles.map((d) => `<tr><td>${escapar(d.producto)}</td><td class="text-end">${Number(d.cantidad)}</td><td class="text-end">${moneda(d.costo_unitario)}</td><td class="text-end">${moneda(d.subtotal)}</td></tr>`).join("")}</tbody>
        <tfoot>
          <tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">${moneda(compra.subtotal)}</th></tr>
          <tr><th colspan="3" class="text-end">IVA crédito</th><th class="text-end">${moneda(compra.iva_credito)}</th></tr>
          <tr><th colspan="3" class="text-end">Total</th><th class="text-end">${moneda(compra.total)}</th></tr>
        </tfoot>
      </table></div>`;
    modalDetalle?.show();
  }

  async function anularCompra(compra) {
    if (!usuarioAnulacion.value) {
      notificar("warning", "Seleccione el usuario que anula la compra");
      usuarioAnulacion.focus();
      return;
    }
    const confirmado = await confirmarAccion(
      `¿Anular la compra #${compra.id_compra} de ${compra.proveedor}? Solo se anulará si sus productos no tienen movimientos posteriores.`,
      "Anular compra",
      "danger",
    );
    if (!confirmado) return;

    try {
      const datos = new FormData();
      datos.append("opcion", "anular");
      datos.append("id_compra", compra.id_compra);
      datos.append("id_usuario", usuarioAnulacion.value);
      const respuesta = await fetch("../dao/DAOCompras.php", {
        method: "POST",
        body: datos,
      });
      if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
      const resultado = await respuesta.json();
      if (!resultado.estado) {
        notificar(
          "warning",
          resultado.mensaje || "No se pudo anular la compra",
        );
        return;
      }
      notificar("success", resultado.mensaje);
      await cargarCompras();
    } catch (error) {
      console.error("Error al anular la compra:", error);
      notificar("danger", "No se pudo anular la compra");
    }
  }

  filtroEstado.addEventListener("change", dibujar);
  usuarioAnulacion.addEventListener("change", dibujar);
  cuerpo.addEventListener("click", async (e) => {
    const fila = e.target.closest("tr[data-id]");
    if (!fila) return;
    const compra = compras.find((c) => String(c.id_compra) === fila.dataset.id);
    if (!compra) return;
    try {
      if (e.target.closest(".btn-ver-compra"))
        await verDetalle(compra.id_compra);
      if (e.target.closest(".btn-anular-compra")) await anularCompra(compra);
    } catch (error) {
      console.error("Error al consultar la compra:", error);
      notificar("danger", error.message || "No se pudo completar la acción");
    }
  });

  try {
    await cargarUsuarios();
    await cargarCompras();
  } catch (error) {
    console.error("Error al cargar compras:", error);
    cuerpo.innerHTML =
      '<tr><td colspan="10" class="text-center text-danger py-4">No se pudieron cargar las compras.</td></tr>';
    notificar("danger", error.message || "No se pudieron cargar las compras");
  }
});
