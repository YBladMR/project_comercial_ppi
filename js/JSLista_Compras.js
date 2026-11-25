document.addEventListener("DOMContentLoaded", async function () {
  const cuerpo = document.getElementById("cuerpoCompras");
  if (!cuerpo) return;

  const tablaWrap = document.getElementById("tabla-wrap");
  const tabla = document.getElementById("tabla-compras");
  const buscador = document.getElementById("buscarCompra");
  const chipsEstado = Array.from(document.querySelectorAll(".lp-chip[data-estado]"));
  const tarjetasEstado = Array.from(document.querySelectorAll(".lp-stat[data-atajo]"));
  const fechaDesde = document.getElementById("fechaDesde");
  const fechaHasta = document.getElementById("fechaHasta");
  const totalMinimo = document.getElementById("totalMinimo");
  const totalMaximo = document.getElementById("totalMaximo");
  const contadorRegistros = document.getElementById("total-registros");
  const tamPagina = document.getElementById("tam-pagina");
  const paginador = document.getElementById("table-pagination");
  const estadoVacio = document.getElementById("estado-vacio");
  const tituloVacio = document.getElementById("vacio-titulo");
  const textoVacio = document.getElementById("vacio-texto");
  const btnFiltros = document.getElementById("btnFiltros");
  const panelFiltros = document.getElementById("panelFiltros");
  const btnLimpiar = document.getElementById("limpiar-filtros");
  const btnImprimir = document.getElementById("print-table");
  const modalDetalleEl = document.getElementById("modalDetalleCompra");
  const contenidoDetalle = document.getElementById("contenidoDetalleCompra");
  const modalDetalle =
    typeof coreui !== "undefined" && coreui.Modal
      ? coreui.Modal.getOrCreateInstance(modalDetalleEl)
      : null;

  let compras = [];
  let estadoSeleccionado = "";
  let textoBusqueda = "";
  let paginaActual = 1;
  let registrosPorPagina = Number(tamPagina.value) || 10;
  let campoOrden = "fecha";
  let direccionOrden = -1;

  function escapar(valor) {
    return String(valor == null ? "" : valor).replace(/[&<>"']/g, function (caracter) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[caracter];
    });
  }

  function moneda(valor) {
    return "$" + Number(valor || 0).toFixed(2);
  }

  // El JS administra la petición y la presentación; el DAO devuelve datos JSON.
  async function llamarDaoCompras(opcion, campos) {
    const datos = new FormData();
    datos.append("opcion", opcion);
    Object.entries(campos || {}).forEach(function (par) {
      datos.append(par[0], par[1]);
    });

    const respuesta = await fetch("../dao/DAOCompras.php", {
      method: "POST",
      body: datos,
    });
    if (!respuesta.ok) throw new Error("Error HTTP: " + respuesta.status);
    return respuesta.json();
  }

  function idCompraValido(idCompra) {
    const numero = Number(idCompra);
    return String(idCompra).trim() !== "" && Number.isSafeInteger(numero) && numero > 0;
  }

  function fechaIso(compra) {
    return String(compra.fecha || "").slice(0, 10);
  }

  function fechaVisible(fecha) {
    const partes = String(fecha || "").split(" ");
    const fechaPartes = (partes[0] || "").split("-");
    if (fechaPartes.length !== 3) return escapar(fecha);
    const hora = partes[1] ? " " + partes[1].slice(0, 5) : "";
    return escapar(fechaPartes[2] + "/" + fechaPartes[1] + "/" + fechaPartes[0] + hora);
  }

  function actualizarResumen() {
    const registradas = compras.filter(function (compra) {
      return compra.estado === "registrada";
    });
    const anuladas = compras.filter(function (compra) {
      return compra.estado === "anulada";
    });
    const montoRegistrado = registradas.reduce(function (suma, compra) {
      return suma + Number(compra.total || 0);
    }, 0);

    document.getElementById("stat-total").textContent = compras.length;
    document.getElementById("stat-registradas").textContent = registradas.length;
    document.getElementById("stat-anuladas").textContent = anuladas.length;
    document.getElementById("stat-monto").textContent = moneda(montoRegistrado);
  }

  function valorOrden(compra, campo) {
    if (["id_compra", "lineas", "subtotal", "iva_credito", "total"].includes(campo)) {
      return Number(compra[campo] || 0);
    }
    if (campo === "fecha") {
      return new Date(String(compra.fecha || "").replace(" ", "T")).getTime() || 0;
    }
    return String(compra[campo] || "").toLocaleLowerCase("es");
  }

  function obtenerComprasFiltradas() {
    const minTexto = totalMinimo.value.trim();
    const maxTexto = totalMaximo.value.trim();
    const minimo = minTexto === "" ? null : Number(minTexto);
    const maximo = maxTexto === "" ? null : Number(maxTexto);

    return compras.filter(function (compra) {
      if (estadoSeleccionado && compra.estado !== estadoSeleccionado) return false;

      const fecha = fechaIso(compra);
      if (fechaDesde.value && fecha < fechaDesde.value) return false;
      if (fechaHasta.value && fecha > fechaHasta.value) return false;

      const total = Number(compra.total || 0);
      if (minimo !== null && Number.isFinite(minimo) && total < minimo) return false;
      if (maximo !== null && Number.isFinite(maximo) && total > maximo) return false;

      if (textoBusqueda) {
        const contenido = [
          compra.id_compra,
          compra.fecha,
          compra.proveedor,
          compra.usuario,
          compra.estado,
          compra.subtotal,
          compra.iva_credito,
          compra.total,
        ].join(" ").toLocaleLowerCase("es");
        if (!contenido.includes(textoBusqueda)) return false;
      }
      return true;
    }).sort(function (a, b) {
      const valorA = valorOrden(a, campoOrden);
      const valorB = valorOrden(b, campoOrden);
      const comparacion = typeof valorA === "string"
        ? valorA.localeCompare(valorB, "es", { sensitivity: "base" })
        : valorA - valorB;
      return comparacion * direccionOrden;
    });
  }

  function dibujarEncabezados() {
    tabla.querySelectorAll("th[data-orden]").forEach(function (encabezado) {
      const icono = encabezado.querySelector("i");
      const campo = encabezado.dataset.orden;
      const seleccionado = campo === campoOrden;
      encabezado.setAttribute("aria-sort", seleccionado
        ? (direccionOrden > 0 ? "ascending" : "descending")
        : "none");
      if (icono) {
        icono.className = seleccionado
          ? (direccionOrden > 0 ? "bi bi-sort-up" : "bi bi-sort-down")
          : "bi bi-arrow-down-up";
      }
    });
  }

  function dibujarPaginador(totalPaginas) {
    function boton(texto, ir, clase, deshabilitado) {
      return '<button type="button" data-pagina="' + ir + '" class="' + clase + '"' +
        (deshabilitado ? " disabled" : "") + ">" + texto + "</button>";
    }

    let html = boton('<i class="bi bi-chevron-left"></i>', paginaActual - 1, "", paginaActual <= 1);
    const paginasVisibles = [];
    for (let numero = 1; numero <= totalPaginas; numero += 1) {
      if (numero === 1 || numero === totalPaginas || Math.abs(numero - paginaActual) <= 1) {
        paginasVisibles.push(numero);
      }
    }

    let paginaAnterior = 0;
    paginasVisibles.forEach(function (numero) {
      if (numero - paginaAnterior > 1) html += '<button type="button" disabled>…</button>';
      html += boton(numero, numero, numero === paginaActual ? "activo" : "", false);
      paginaAnterior = numero;
    });

    html += boton('<i class="bi bi-chevron-right"></i>', paginaActual + 1, "", paginaActual >= totalPaginas);
    paginador.innerHTML = html;
  }

  function dibujar() {
    const filtradas = obtenerComprasFiltradas();
    const totalPaginas = Math.max(1, Math.ceil(filtradas.length / registrosPorPagina));
    paginaActual = Math.min(paginaActual, totalPaginas);

    const inicio = (paginaActual - 1) * registrosPorPagina;
    const visibles = filtradas.slice(inicio, inicio + registrosPorPagina);
    const hayResultados = filtradas.length > 0;

    tablaWrap.hidden = !hayResultados;
    estadoVacio.hidden = hayResultados;
    if (!hayResultados) {
      cuerpo.innerHTML = "";
      tituloVacio.textContent = compras.length === 0
        ? "Aún no hay compras registradas"
        : "No encontramos compras con esos filtros";
      textoVacio.textContent = compras.length === 0
        ? "Registra una compra para que aparezca en este historial."
        : "Prueba con otro texto o limpia los filtros para ver más resultados.";
    } else {
      cuerpo.innerHTML = visibles.map(function (compra) {
        const registrada = compra.estado === "registrada";
        const estado = registrada ? "Registrada" : "Anulada";
        const claseEstado = registrada ? "registrada" : "anulada";
        const acciones = '<span class="lp-acc">' +
          '<button type="button" class="lp-ib btn-ver-compra" aria-label="Ver detalle de compra ' + escapar(compra.id_compra) + '" title="Ver detalle"><i class="bi bi-eye"></i></button>' +
          (registrada
            ? '<button type="button" class="lp-ib anular btn-anular-compra" aria-label="Anular compra ' + escapar(compra.id_compra) + '" title="Anular compra"><i class="bi bi-x-circle"></i></button>'
            : "") +
          "</span>";

        return '<tr data-id="' + escapar(compra.id_compra) + '">' +
          '<td><span class="lp-compra-id">#' + escapar(compra.id_compra) + "</span></td>" +
          '<td class="lp-compra-fecha">' + fechaVisible(compra.fecha) + "</td>" +
          '<td class="lp-compra-proveedor">' + escapar(compra.proveedor) + "</td>" +
          "<td>" + escapar(compra.usuario) + "</td>" +
          '<td class="lp-centro">' + Number(compra.lineas || 0) + "</td>" +
          '<td class="lp-der">' + moneda(compra.subtotal) + "</td>" +
          '<td class="lp-der">' + moneda(compra.iva_credito) + "</td>" +
          '<td class="lp-der fw-semibold">' + moneda(compra.total) + "</td>" +
          '<td class="lp-centro"><span class="lp-pill ' + claseEstado + '">' + estado + "</span></td>" +
          '<td class="lp-centro lp-col-acc">' + acciones + "</td>" +
          "</tr>";
      }).join("");
    }

    contadorRegistros.textContent = "Total de registros: " + filtradas.length;
    dibujarPaginador(totalPaginas);
    dibujarEncabezados();
  }

  function seleccionarEstado(estado) {
    estadoSeleccionado = estado;
    paginaActual = 1;
    chipsEstado.forEach(function (chip) {
      const activo = chip.dataset.estado === estadoSeleccionado;
      chip.classList.toggle("activo", activo);
      chip.setAttribute("aria-pressed", String(activo));
    });
    tarjetasEstado.forEach(function (tarjeta) {
      tarjeta.setAttribute("aria-pressed", String(tarjeta.dataset.atajo === estadoSeleccionado));
    });
    dibujar();
  }

  function limpiarFiltros() {
    buscador.value = "";
    fechaDesde.value = "";
    fechaHasta.value = "";
    totalMinimo.value = "";
    totalMaximo.value = "";
    textoBusqueda = "";
    paginaActual = 1;
    seleccionarEstado("");
    cerrarFiltros();
  }

  function cerrarFiltros() {
    panelFiltros.hidden = true;
    btnFiltros.setAttribute("aria-expanded", "false");
  }

  async function cargarCompras() {
    const resultado = await llamarDaoCompras("consultar");
    if (!resultado.estado || !Array.isArray(resultado.datos)) {
      throw new Error(resultado.mensaje || "Respuesta no válida del servidor");
    }
    compras = resultado.datos;
    actualizarResumen();
    dibujar();
  }

  async function verDetalle(idCompra) {
    if (!idCompraValido(idCompra)) throw new Error("Compra no válida");
    const resultado = await llamarDaoCompras("consultarDetalle", {
      id_compra: idCompra,
    });
    if (!resultado.estado) {
      throw new Error(resultado.mensaje || "No se pudo cargar el detalle");
    }

    const compra = resultado.datos.compra;
    const detalles = resultado.datos.detalles;
    document.getElementById("tituloDetalleCompra").textContent =
      "Detalle de compra #" + compra.id_compra;

    const filasDetalle = detalles.map(function (detalle) {
      return "<tr><td>" + escapar(detalle.producto) + "</td>" +
        '<td class="text-end">' + Number(detalle.cantidad) + "</td>" +
        '<td class="text-end">' + moneda(detalle.costo_unitario) + "</td>" +
        '<td class="text-end">' + moneda(detalle.subtotal) + "</td></tr>";
    }).join("");

    contenidoDetalle.innerHTML =
      '<div class="row g-2 mb-3">' +
      '<div class="col-md-6"><strong>Proveedor:</strong> ' + escapar(compra.proveedor) + "</div>" +
      '<div class="col-md-6"><strong>Fecha:</strong> ' + fechaVisible(compra.fecha) + "</div>" +
      '<div class="col-md-6"><strong>Registró:</strong> ' + escapar(compra.usuario) + "</div>" +
      '<div class="col-md-6"><strong>Estado:</strong> ' + escapar(compra.estado) + "</div>" +
      "</div>" +
      '<div class="table-responsive"><table class="table table-sm table-bordered align-middle">' +
      "<thead class=\"table-light\"><tr><th>Producto</th><th class=\"text-end\">Cantidad</th><th class=\"text-end\">Costo unitario</th><th class=\"text-end\">Subtotal</th></tr></thead>" +
      "<tbody>" + filasDetalle + "</tbody>" +
      "<tfoot>" +
      '<tr><th colspan="3" class="text-end">Subtotal</th><th class="text-end">' + moneda(compra.subtotal) + "</th></tr>" +
      '<tr><th colspan="3" class="text-end">IVA crédito</th><th class="text-end">' + moneda(compra.iva_credito) + "</th></tr>" +
      '<tr><th colspan="3" class="text-end">Total</th><th class="text-end">' + moneda(compra.total) + "</th></tr>" +
      "</tfoot></table></div>";
    modalDetalle && modalDetalle.show();
  }

  async function anularCompra(compra) {
    if (!idCompraValido(compra.id_compra)) {
      notificar("warning", "La compra seleccionada no es válida.");
      return;
    }

    const confirmado = await confirmarAccion(
      "¿Anular la compra #" + compra.id_compra + " de " + compra.proveedor +
        "? Solo se anulará si sus productos no tienen movimientos posteriores.",
      "Anular compra",
      "danger",
    );
    if (!confirmado) return;

    try {
      const resultado = await llamarDaoCompras("anular", {
        id_compra: compra.id_compra,
      });
      if (!resultado.estado) {
        notificar("warning", resultado.mensaje || "No se pudo anular la compra");
        return;
      }
      notificar("success", resultado.mensaje);
      await cargarCompras();
    } catch (error) {
      console.error("Error al anular la compra:", error);
      notificar("danger", "No se pudo anular la compra");
    }
  }

  chipsEstado.forEach(function (chip) {
    chip.addEventListener("click", function () {
      seleccionarEstado(chip.dataset.estado);
    });
  });

  tarjetasEstado.forEach(function (tarjeta) {
    tarjeta.addEventListener("click", function () {
      seleccionarEstado(tarjeta.dataset.atajo);
    });
  });

  buscador.addEventListener("input", function () {
    textoBusqueda = buscador.value.trim().toLocaleLowerCase("es");
    paginaActual = 1;
    dibujar();
  });

  [fechaDesde, fechaHasta, totalMinimo, totalMaximo].forEach(function (entrada) {
    entrada.addEventListener("input", function () {
      paginaActual = 1;
      dibujar();
    });
  });

  tamPagina.addEventListener("change", function () {
    registrosPorPagina = Number(tamPagina.value) || 10;
    paginaActual = 1;
    dibujar();
  });

  tabla.querySelectorAll("th[data-orden]").forEach(function (encabezado) {
    encabezado.addEventListener("click", function () {
      if (campoOrden === encabezado.dataset.orden) {
        direccionOrden *= -1;
      } else {
        campoOrden = encabezado.dataset.orden;
        direccionOrden = 1;
      }
      paginaActual = 1;
      dibujar();
    });
  });

  paginador.addEventListener("click", function (evento) {
    const boton = evento.target.closest("button[data-pagina]");
    if (!boton || boton.disabled) return;
    paginaActual = Number(boton.dataset.pagina);
    dibujar();
  });

  btnLimpiar.addEventListener("click", limpiarFiltros);
  btnImprimir.addEventListener("click", function () {
    cerrarFiltros();
    window.print();
  });

  btnFiltros.addEventListener("click", function (evento) {
    evento.stopPropagation();
    panelFiltros.hidden = !panelFiltros.hidden;
    btnFiltros.setAttribute("aria-expanded", String(!panelFiltros.hidden));
  });
  panelFiltros.addEventListener("click", function (evento) {
    evento.stopPropagation();
  });
  document.addEventListener("click", cerrarFiltros);
  document.addEventListener("keydown", function (evento) {
    if (evento.key === "Escape") cerrarFiltros();
  });

  cuerpo.addEventListener("click", async function (evento) {
    const boton = evento.target.closest(".btn-ver-compra, .btn-anular-compra");
    if (!boton) return;

    const fila = boton.closest("tr[data-id]");
    const compra = compras.find(function (registro) {
      return String(registro.id_compra) === fila.dataset.id;
    });
    if (!compra) return;

    try {
      if (boton.classList.contains("btn-ver-compra")) {
        await verDetalle(compra.id_compra);
      } else {
        await anularCompra(compra);
      }
    } catch (error) {
      console.error("Error al consultar la compra:", error);
      notificar("danger", error.message || "No se pudo completar la acción");
    }
  });

  try {
    await cargarCompras();
  } catch (error) {
    console.error("Error al cargar compras:", error);
    tablaWrap.hidden = true;
    estadoVacio.hidden = false;
    tituloVacio.textContent = "No se pudieron cargar las compras";
    textoVacio.textContent = error.message || "Intenta recargar la página.";
    contadorRegistros.textContent = "Total de registros: 0";
    notificar("danger", error.message || "No se pudieron cargar las compras");
  }
});
