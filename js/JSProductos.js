const formulario = document.getElementById("formulario");
const btnGuardar = document.getElementById("btnGuardar");
const btnCancelar = document.getElementById("btnCancelar");
const tituloFormulario = document.getElementById("tituloFormulario");
const nombre = document.getElementById("nombre");
const categoria = document.getElementById("id_categoria");
const material = document.getElementById("material");
const descripcion = document.getElementById("descripcion");
const alto = document.getElementById("alto_cm");
const ancho = document.getElementById("ancho_cm");
const profundidad = document.getElementById("profundidad_cm");
const pesoKg = document.getElementById("peso_kg");
const stockMinimo = document.getElementById("stock_minimo");
const camposEspecificaciones = document.getElementById("camposEspecificaciones");
const ayudaEspecificaciones = document.getElementById("ayudaEspecificaciones");

const MAX_MEDIDA = 999.99;

let htmlBoton = btnGuardar ? btnGuardar.innerHTML : "";
let solicitudAtributos = 0;

function marcar(campo, valido) {
  campo.classList.toggle("is-invalid", !valido);
  campo.classList.toggle("is-valid", valido);
}

function sinMarca(campo) {
  campo.classList.remove("is-invalid", "is-valid");
}

function validarCampo(campo) {
  const valido = campo.value.trim() !== "";
  marcar(campo, valido);
  return valido;
}

function validarCategoria() {
  const valido = categoria.value !== "" && categoria.value !== "0";
  marcar(categoria, valido);
  return valido;
}

function validarDecimal(campo, obligatorio, maximo) {
  const texto = campo.value.trim();
  if (texto === "") {
    if (!obligatorio) {
      sinMarca(campo);
      return true;
    }
    marcar(campo, false);
    return false;
  }
  const valido = /^\d+(\.\d{1,2})?$/.test(texto) && Number(texto) <= maximo;
  marcar(campo, valido);
  return valido;
}

function validarEntero(campo) {
  const valido = /^\d{1,9}$/.test(campo.value.trim());
  marcar(campo, valido);
  return valido;
}

function validarEspecificaciones() {
  if (!camposEspecificaciones) return true;
  let valido = true;
  camposEspecificaciones.querySelectorAll("[data-especificacion]").forEach((campo) => {
    const valor = campo.value.trim();
    let campoValido = !campo.required || valor !== "";

    if (campoValido && valor !== "" && campo.dataset.tipo === "numero") {
      campoValido = /^\d+(\.\d{1,4})?$/.test(valor) && Number(valor) >= 0;
    }

    marcar(campo, campoValido);
    if (!campoValido) valido = false;
  });
  return valido;
}

function limpiarEspecificaciones() {
  if (camposEspecificaciones) camposEspecificaciones.replaceChildren();
  if (ayudaEspecificaciones) {
    ayudaEspecificaciones.textContent = "Selecciona una subcategoría para mostrar sus campos específicos.";
  }
}

function crearCampoEspecificacion(atributo, valores = {}) {
  const columna = document.createElement("div");
  columna.className = "col-md-6";

  const idCampo = `especificacion_${atributo.id_atributo}`;
  const nombreCampo = `especificaciones[${atributo.id_atributo}]`;
  const etiqueta = document.createElement("label");
  etiqueta.className = "form-label";
  etiqueta.htmlFor = idCampo;
  etiqueta.textContent = `${atributo.nombre}${atributo.unidad ? ` (${atributo.unidad})` : ""}${atributo.obligatorio ? " *" : ""}`;

  let campo;
  if (atributo.tipo_dato === "opcion" || atributo.tipo_dato === "booleano") {
    campo = document.createElement("select");
    campo.className = "form-select";
    campo.add(new Option("[SELECCIONE]", ""));

    let opciones = [];
    if (atributo.tipo_dato === "booleano") {
      opciones = [
        { valor: "1", etiqueta: "Sí" },
        { valor: "0", etiqueta: "No" }
      ];
    } else {
      try {
        const lista = JSON.parse(atributo.opciones || "[]");
        if (Array.isArray(lista)) opciones = lista.map((valor) => ({ valor: String(valor), etiqueta: String(valor) }));
      } catch (error) {
        console.error("Opciones inválidas en el atributo", atributo.codigo, error);
      }
    }
    opciones.forEach((opcion) => campo.add(new Option(opcion.etiqueta, opcion.valor)));
  } else {
    campo = document.createElement("input");
    campo.type = atributo.tipo_dato === "numero" ? "number" : "text";
    campo.className = "form-control";
    if (atributo.tipo_dato === "numero") {
      campo.min = "0";
      campo.step = "any";
    } else {
      campo.maxLength = 500;
    }
  }

  campo.id = idCampo;
  campo.name = nombreCampo;
  campo.required = Boolean(atributo.obligatorio);
  campo.dataset.especificacion = "1";
  campo.dataset.tipo = atributo.tipo_dato;
  campo.value = valores[String(atributo.id_atributo)] ?? "";
  campo.addEventListener("input", () => sinMarca(campo));
  campo.addEventListener("change", () => sinMarca(campo));

  columna.append(etiqueta, campo);
  if (atributo.ayuda) {
    const ayuda = document.createElement("div");
    ayuda.className = "form-text";
    ayuda.textContent = atributo.ayuda;
    columna.appendChild(ayuda);
  }
  camposEspecificaciones.appendChild(columna);
}

async function cargarAtributosCategoria(idCategoria, valores = {}) {
  const numeroSolicitud = ++solicitudAtributos;
  limpiarEspecificaciones();
  if (!idCategoria || idCategoria === "0") return;

  ayudaEspecificaciones.textContent = "Cargando campos de la subcategoría...";
  const datos = new FormData();
  datos.append("opcion", "consultarAtributos");
  datos.append("id_categoria", idCategoria);

  try {
    const respuesta = await fetch("../dao/DAOProductos.php", { method: "POST", body: datos });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    const resultado = await respuesta.json();
    if (numeroSolicitud !== solicitudAtributos) return;
    if (!resultado.estado || !Array.isArray(resultado.atributos)) {
      throw new Error(resultado.mensaje || "No se pudieron leer los atributos");
    }

    if (resultado.atributos.length === 0) {
      ayudaEspecificaciones.textContent = "Esta categoría aún no tiene especificaciones adicionales definidas.";
      return;
    }
    ayudaEspecificaciones.textContent = "Completa las características específicas de este tipo de producto.";
    resultado.atributos.forEach((atributo) => crearCampoEspecificacion(atributo, valores));
  } catch (error) {
    if (numeroSolicitud !== solicitudAtributos) return;
    console.error("Error al cargar especificaciones:", error);
    ayudaEspecificaciones.textContent = "No se pudieron cargar las especificaciones de esta subcategoría.";
  }
}

function soloNumeros(campo, conDecimales) {
  campo.addEventListener("keydown", function (e) {
    const bloqueadas = ["e", "E", "+", "-"];
    if (!conDecimales) bloqueadas.push(".", ",");
    if (bloqueadas.includes(e.key)) e.preventDefault();
  });

  if (conDecimales) {
    campo.addEventListener("input", function () {
      const valor = campo.value;
      const punto = valor.indexOf(".");
      if (punto !== -1 && valor.length - punto - 1 > 2) {
        campo.value = valor.substring(0, punto + 3);
      }
    });
  }
}

function limpiarCampos() {
  [nombre, material, descripcion, alto, ancho, profundidad, pesoKg].forEach(function (campo) {
    campo.value = "";
  });
  stockMinimo.value = "0";
  categoria.value = "0";
  limpiarEspecificaciones();
  [nombre, categoria, material, descripcion, alto, ancho, profundidad, pesoKg, stockMinimo]
    .forEach(sinMarca);
  nombre.focus();
}

if (formulario) {
  nombre.addEventListener("input", function () {
    nombre.value = nombre.value.toUpperCase();
    validarCampo(nombre);
  });
  nombre.addEventListener("blur", () => validarCampo(nombre));
  categoria.addEventListener("change", async function () {
    validarCategoria();
    await cargarAtributosCategoria(categoria.value);
  });

  soloNumeros(alto, true);
  soloNumeros(ancho, true);
  soloNumeros(profundidad, true);
  soloNumeros(pesoKg, true);
  soloNumeros(stockMinimo, false);

  alto.addEventListener("blur", () => validarDecimal(alto, false, MAX_MEDIDA));
  ancho.addEventListener("blur", () => validarDecimal(ancho, false, MAX_MEDIDA));
  profundidad.addEventListener("blur", () => validarDecimal(profundidad, false, MAX_MEDIDA));
  pesoKg.addEventListener("blur", () => validarDecimal(pesoKg, false, MAX_MEDIDA));
  stockMinimo.addEventListener("blur", () => validarEntero(stockMinimo));

  btnCancelar.addEventListener("click", function () {
    window.location.href = "Lista_Productos.php";
  });

  btnGuardar.addEventListener("click", async function () {
    const resultados = [
      validarCampo(nombre),
      validarCategoria(),
      validarDecimal(alto, false, MAX_MEDIDA),
      validarDecimal(ancho, false, MAX_MEDIDA),
      validarDecimal(profundidad, false, MAX_MEDIDA),
      validarDecimal(pesoKg, false, MAX_MEDIDA),
      validarEntero(stockMinimo),
      validarEspecificaciones()
    ];

    if (!resultados.every(Boolean)) {
      notificar("warning", "Revise los campos marcados en rojo");
      return;
    }

    const idProducto = btnGuardar.dataset.id || "";
    const datos = new FormData(formulario);
    datos.append("opcion", idProducto ? "modificar" : "guardar");
    if (idProducto) datos.append("id_producto", idProducto);

    btnGuardar.disabled = true;
    btnGuardar.textContent = "Procesando...";

    try {
      const respuesta = await fetch("../dao/DAOProductos.php", {
        method: "POST",
        body: datos
      });

      if (!respuesta.ok) {
        throw new Error("Error HTTP: " + respuesta.status);
      }

      const resultado = await respuesta.json();
      if (resultado.estado) {
        notificar("success", resultado.mensaje);
        if (idProducto) {
          setTimeout(() => {
            window.location.href = "Lista_Productos.php";
          }, 1200);
        } else {
          limpiarCampos();
        }
      } else {
        notificar("danger", resultado.mensaje);
      }
    } catch (error) {
      console.error("Error al guardar el producto:", error);
      notificar("danger", "No se pudo guardar el producto");
    } finally {
      btnGuardar.disabled = false;
      btnGuardar.innerHTML = htmlBoton;
    }
  });
}

async function cargarCategorias() {
  const datos = new FormData();
  datos.append("opcion", "consultar");

  const respuesta = await fetch("../dao/DAOCateg.php", {
    method: "POST",
    body: datos
  });

  if (!respuesta.ok) {
    throw new Error("Error HTTP: " + respuesta.status);
  }

  const categorias = await respuesta.json();
  if (!Array.isArray(categorias)) {
    throw new Error(categorias.mensaje || "Respuesta no válida de DAOCateg.php");
  }

  categoria.innerHTML = '<option value="0">[SELECCIONE CATEGORÍA]</option>';
  categorias.forEach(function (c) {
    const opcion = document.createElement("option");
    opcion.value = c.id_categoria;
    opcion.textContent = c.nombre_padre ? `${c.nombre_padre} / ${c.nombre}` : c.nombre;
    categoria.appendChild(opcion);
  });

  if (categorias.length === 0) {
    notificar("warning", "No hay categorías registradas. Agregue al menos una antes de crear productos.");
  }
}

async function cargarProducto(idProducto) {
  const datos = new FormData();
  datos.append("opcion", "consultarIndividual");
  datos.append("id_producto", idProducto);

  const respuesta = await fetch("../dao/DAOProductos.php", {
    method: "POST",
    body: datos
  });

  if (!respuesta.ok) {
    throw new Error("Error HTTP: " + respuesta.status);
  }

  const resultado = await respuesta.json();
  if (!resultado.estado) {
    notificar("warning", "El producto no existe");
    return;
  }

  const d = resultado.datos;
  nombre.value = d.nombre ?? "";
  material.value = d.material ?? "";
  descripcion.value = d.descripcion ?? "";
  alto.value = d.alto ?? "";
  ancho.value = d.ancho ?? "";
  profundidad.value = d.profundidad ?? "";
  pesoKg.value = d.peso_kg ?? "";
  stockMinimo.value = d.stock_minimo ?? 0;

  categoria.value = d.id_categoria;
  await cargarAtributosCategoria(d.id_categoria, d.especificaciones ?? {});
  if (categoria.value !== String(d.id_categoria)) {
    const etiquetaExtra = d.estado_categoria === "inactivo"
      ? " (inactiva)"
      : d.categoria_id_padre === null
        ? " (categoría principal existente)"
        : " (categoría no disponible)";
    categoria.add(new Option(d.categoria + etiquetaExtra, d.id_categoria));
  }
  categoria.value = d.id_categoria;

  btnGuardar.dataset.id = d.id_producto;
  btnGuardar.innerHTML = '<i class="bi bi-save"></i> Modificar';
  htmlBoton = btnGuardar.innerHTML;

  if (tituloFormulario) tituloFormulario.textContent = "Modificar Producto";
  const miga = document.querySelector(".breadcrumb-item.active span");
  if (miga) miga.textContent = "Modificar Producto";

  if (d.estado === "inactivo") {
    notificar("info", "Este producto está dado de baja");
  }
}

document.addEventListener("DOMContentLoaded", async () => {
  if (!formulario) return;

  try {
    await cargarCategorias();
  } catch (error) {
    console.error("Error al cargar categorías:", error);
    notificar("danger", "No se pudieron cargar las categorías: " + error.message);
  }

  const idProducto = new URLSearchParams(window.location.search).get("id");
  if (idProducto) {
    try {
      await cargarProducto(idProducto);
    } catch (error) {
      console.error("Error al cargar el producto:", error);
      notificar("danger", "No se pudo cargar el producto");
    }
  }
});
