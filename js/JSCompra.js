document.addEventListener("DOMContentLoaded", async () => {
  const formulario = document.getElementById("formularioCompra");
  if (!formulario) return;

  const selProveedor = document.getElementById("id_proveedor");
  const cuerpo = document.getElementById("detalleCompra");
  const btnAgregar = document.getElementById("btnAgregarLinea");
  const btnGuardar = document.getElementById("btnGuardarCompra");
  const mensaje = document.getElementById("mensajeCompra");
  const ivaPorcentaje = 0.13;
  let productos = [];

  function escapar(valor) {
    return String(valor ?? "").replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[c]));
  }

  function formatoMoneda(cantidadCentavos) {
    return "$" + (cantidadCentavos / 100).toFixed(2);
  }

  // La interfaz centraliza las solicitudes HTTP al endpoint de compras.
  async function llamarDaoCompra(opcion, campos = {}) {
    const datos = new FormData();
    datos.append("opcion", opcion);
    Object.entries(campos).forEach(([nombre, valor]) => datos.append(nombre, valor));

    const respuesta = await fetch("../dao/DAOCompras.php", {
      method: "POST",
      body: datos,
    });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    return respuesta.json();
  }

  function actualizarDisponibilidad() {
    const hayCatalogos = productos.length > 0 && selProveedor.value !== "";
    btnGuardar.disabled = !hayCatalogos;
    if (productos.length === 0 || selProveedor.options.length <= 1) {
      mensaje.className = "alert alert-warning compra-mensaje";
      mensaje.textContent = "Registre al menos un proveedor activo y un producto activo antes de crear una compra.";
    } else {
      mensaje.className = "alert alert-info compra-mensaje";
      mensaje.textContent = "El costo unitario se registra antes del IVA. El sistema calcula el crédito fiscal al 13%.";
    }
  }

  function opcionesProductos() {
    return '<option value="">Seleccione un producto</option>' + productos.map((p) =>
      `<option value="${escapar(p.id_producto)}">${escapar(p.nombre)}</option>`
    ).join("");
  }

  function agregarLinea() {
    const fila = document.createElement("tr");
    fila.className = "linea-compra";
    fila.innerHTML = `
      <td><select class="form-select producto-linea" required>${opcionesProductos()}</select></td>
      <td><input type="number" class="form-control cantidad-linea" min="1" step="1" value="1" autocomplete="off" required></td>
      <td><input type="number" class="form-control costo-linea" min="0" step="0.01" placeholder="0.00" autocomplete="off" required></td>
      <td class="text-end subtotal-linea">$0.00</td>
      <td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm quitar-linea" aria-label="Quitar producto"><i class="bi bi-trash" aria-hidden="true"></i></button></td>`;
    cuerpo.appendChild(fila);
    fila.querySelector(".producto-linea").addEventListener("change", () => {
      actualizarProductosRepetidos();
      calcularTotales();
    });
    fila.querySelector(".cantidad-linea").addEventListener("input", calcularTotales);
    fila.querySelector(".costo-linea").addEventListener("input", calcularTotales);
    fila.querySelector(".quitar-linea").addEventListener("click", () => {
      fila.remove();
      if (!cuerpo.querySelector(".linea-compra")) agregarLinea();
      actualizarProductosRepetidos();
      calcularTotales();
    });
    actualizarProductosRepetidos();
    calcularTotales();
  }

  function actualizarProductosRepetidos() {
    const filas = [...cuerpo.querySelectorAll(".linea-compra")];
    const selecciones = filas.map((fila) => fila.querySelector(".producto-linea").value).filter(Boolean);
    filas.forEach((fila) => {
      const select = fila.querySelector(".producto-linea");
      [...select.options].forEach((opcion) => {
        if (!opcion.value) return;
        const seleccionadoEnOtraFila = selecciones.includes(opcion.value) && select.value !== opcion.value;
        opcion.disabled = seleccionadoEnOtraFila;
      });
    });
  }

  function costoEnCentavos(texto) {
    const valor = String(texto ?? "").trim();
    if (!/^\d{1,8}(\.\d{1,2})?$/.test(valor)) return null;
    return Math.round(Number(valor) * 100);
  }

  function calcularTotales() {
    let subtotalCentavos = 0;
    cuerpo.querySelectorAll(".linea-compra").forEach((fila) => {
      const cantidad = Number(fila.querySelector(".cantidad-linea").value);
      const costo = costoEnCentavos(fila.querySelector(".costo-linea").value);
      const subtotalLinea = Number.isInteger(cantidad) && cantidad > 0 && costo !== null ? cantidad * costo : 0;
      fila.querySelector(".subtotal-linea").textContent = formatoMoneda(subtotalLinea);
      subtotalCentavos += subtotalLinea;
    });
    const ivaCentavos = Math.round(subtotalCentavos * ivaPorcentaje);
    document.getElementById("subtotalCompra").textContent = formatoMoneda(subtotalCentavos);
    document.getElementById("ivaCompra").textContent = formatoMoneda(ivaCentavos);
    document.getElementById("totalCompra").textContent = formatoMoneda(subtotalCentavos + ivaCentavos);
  }

  // Validaciones de interfaz para avisar al usuario antes de enviar la compra.
  function validarYRecopilarDetalles() {
    const detalles = [];
    const vistos = new Set();
    for (const fila of cuerpo.querySelectorAll(".linea-compra")) {
      const idProducto = fila.querySelector(".producto-linea").value;
      const textoCantidad = fila.querySelector(".cantidad-linea").value.trim();
      const textoCosto = fila.querySelector(".costo-linea").value.trim();
      const cantidad = Number(textoCantidad);
      if (!idProducto) throw new Error("Seleccione un producto en cada línea");
      if (!/^\d+$/.test(textoCantidad) || !Number.isInteger(cantidad) || cantidad <= 0) {
        throw new Error("La cantidad debe ser un entero mayor que cero");
      }
      if (costoEnCentavos(textoCosto) === null) throw new Error("Ingrese un costo válido con hasta dos decimales");
      if (vistos.has(idProducto)) throw new Error("Cada producto debe aparecer una sola vez; combine las cantidades repetidas");
      vistos.add(idProducto);
      detalles.push({ id_producto: idProducto, cantidad, costo_unitario: textoCosto });
    }
    return detalles;
  }

  async function cargarCatalogos() {
    const resultado = await llamarDaoCompra("consultarCatalogos");
    if (!resultado.estado) throw new Error(resultado.mensaje || "No se pudieron cargar los datos");

    const catalogos = resultado.datos;
    productos = catalogos.productos || [];
    selProveedor.innerHTML = '<option value="">Seleccione un proveedor</option>' +
      (catalogos.proveedores || []).map((p) => `<option value="${escapar(p.id_proveedor)}">${escapar(p.nombre_razon_social)}</option>`).join("");
    agregarLinea();
    actualizarDisponibilidad();
  }

  btnAgregar.addEventListener("click", agregarLinea);
  selProveedor.addEventListener("change", actualizarDisponibilidad);

  formulario.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (btnGuardar.disabled) {
      notificar("warning", mensaje.textContent);
      return;
    }
    if (!selProveedor.value) {
      notificar("warning", "Seleccione un proveedor");
      return;
    }

    let detalles;
    try {
      detalles = validarYRecopilarDetalles();
    } catch (error) {
      notificar("warning", error.message);
      return;
    }

    const confirmado = await confirmarAccion(
      `¿Registrar esta compra de ${detalles.length} producto(s)? Se actualizarán inventario, kardex y precio de venta.`,
      "Registrar compra",
      "primary"
    );
    if (!confirmado) return;

    btnGuardar.disabled = true;
    btnGuardar.textContent = "Registrando...";
    try {
      const resultado = await llamarDaoCompra("guardar", {
        id_proveedor: selProveedor.value,
        detalle: JSON.stringify(detalles),
      });
      if (!resultado.estado) {
        notificar("danger", resultado.mensaje || "No se pudo registrar la compra");
        return;
      }
      notificar("success", resultado.mensaje);
      setTimeout(() => { window.location.href = "Lista_Compras.php"; }, 900);
    } catch (error) {
      console.error("Error al registrar la compra:", error);
      notificar("danger", "No se pudo registrar la compra");
    } finally {
      btnGuardar.disabled = productos.length === 0 || selProveedor.options.length <= 1 || !selProveedor.value;
      btnGuardar.innerHTML = '<i class="bi bi-save" aria-hidden="true"></i> Registrar compra';
    }
  });

  try {
    await cargarCatalogos();
  } catch (error) {
    console.error("Error al cargar los catálogos de compra:", error);
    mensaje.className = "alert alert-danger compra-mensaje";
    mensaje.textContent = "No se pudieron cargar los datos de la compra.";
    notificar("danger", error.message);
    btnGuardar.disabled = true;
  }
});
