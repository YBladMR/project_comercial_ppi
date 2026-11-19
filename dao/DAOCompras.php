<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
$permisosCompra = [
    'consultarCatalogos' => 'compras.registrar',
    'guardar' => 'compras.registrar',
    'consultar' => 'compras.consultar',
    'consultarDetalle' => 'compras.consultar',
    'anular' => 'compras.anular',
];
requerir_permiso($permisosCompra[$opcion] ?? 'compras.operacion_desconocida', true);
if (in_array($opcion, ['guardar', 'anular'], true)) {
    requerir_post_api();
}
require_once __DIR__ . '/../conexion.php';

function responderCompra($estado, $mensaje, $datos = null) {
    $respuesta = ["estado" => $estado, "mensaje" => $mensaje];
    if ($datos !== null) $respuesta["datos"] = $datos;
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

function enteroPositivo($valor) {
    return ctype_digit((string) $valor) && (int) $valor > 0;
}

function decimalCompraValido($valor) {
    return preg_match('/^\d{1,8}(\.\d{1,2})?$/', (string) $valor) === 1;
}

function obtenerTipoMovimiento($conexion, $nombre, $naturaleza) {
    $sql = "INSERT INTO tipo_movimiento (nombre, naturaleza) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE id_tipo_movimiento=LAST_INSERT_ID(id_tipo_movimiento)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $nombre, $naturaleza);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException("No se pudo preparar el tipo de movimiento");
    }
    $stmt->close();

    $stmt = $conexion->prepare("SELECT id_tipo_movimiento, naturaleza FROM tipo_movimiento WHERE nombre=?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $tipo = $resultado->fetch_assoc();
    $stmt->close();

    if (!$tipo || $tipo['naturaleza'] !== $naturaleza) {
        throw new DomainException("El tipo de movimiento '$nombre' ya existe con una naturaleza distinta");
    }
    return (int) $tipo['id_tipo_movimiento'];
}

function calcularPrecioVenta($costoPromedio, $margen) {
    if ($costoPromedio <= 0) return 0.00;
    $precio = round($costoPromedio * (1 + $margen / 100), 2);
    if ($precio > 9999.99) {
        throw new DomainException("El precio calculado supera el máximo permitido de $9,999.99. Revise costo, margen o precisión de precio_venta.");
    }
    return $precio;
}

if ($opcion === "consultarCatalogos") {
    try {
        $proveedores = [];
        $resultado = $conexion->query("SELECT id_proveedor, nombre_razon_social FROM proveedor WHERE estado='activo' ORDER BY nombre_razon_social");
        while ($fila = $resultado->fetch_assoc()) $proveedores[] = $fila;

        $productos = [];
        $sqlProductos = "SELECT p.id_producto, p.nombre, p.precio_venta,
                                p.margen_ganancia, c.margen_ganancia AS margen_categoria
                         FROM producto AS p
                         INNER JOIN categoria AS c ON c.id_categoria=p.id_categoria
                         WHERE p.estado='activo'
                         ORDER BY p.nombre";
        $resultado = $conexion->query($sqlProductos);
        while ($fila = $resultado->fetch_assoc()) $productos[] = $fila;

        responderCompra(true, "Catálogos cargados", [
            "proveedores" => $proveedores,
            "productos" => $productos
        ]);
    } catch (Throwable $e) {
        error_log("DAOCompras consultarCatalogos: " . $e->getMessage());
        responderCompra(false, "No se pudieron cargar los datos para la compra");
    }
}

if ($opcion === "consultar") {
    try {
        $sql = "SELECT c.id_compra, c.fecha, c.estado, c.subtotal, c.iva_credito, c.total,
                       p.nombre_razon_social AS proveedor, u.nombre AS usuario,
                       COUNT(d.id_detalle_compra) AS lineas
                FROM compra AS c
                INNER JOIN proveedor AS p ON p.id_proveedor=c.id_proveedor
                INNER JOIN usuario AS u ON u.id_usuario=c.id_usuario
                LEFT JOIN detalle_compra AS d ON d.id_compra=c.id_compra
                GROUP BY c.id_compra, c.fecha, c.estado, c.subtotal, c.iva_credito, c.total,
                         p.nombre_razon_social, u.nombre
                ORDER BY c.fecha DESC, c.id_compra DESC";
        $resultado = $conexion->query($sql);
        $compras = [];
        while ($fila = $resultado->fetch_assoc()) $compras[] = $fila;
        responderCompra(true, "Compras consultadas", $compras);
    } catch (Throwable $e) {
        error_log("DAOCompras consultar: " . $e->getMessage());
        responderCompra(false, "No se pudieron consultar las compras");
    }
}

if ($opcion === "consultarDetalle") {
    $idCompra = $_POST['id_compra'] ?? '';
    if (!enteroPositivo($idCompra)) responderCompra(false, "Compra no válida");

    $stmt = $conexion->prepare("SELECT c.id_compra, c.fecha, c.estado, c.subtotal, c.iva_credito, c.total,
                                       p.nombre_razon_social AS proveedor, u.nombre AS usuario
                                FROM compra AS c
                                INNER JOIN proveedor AS p ON p.id_proveedor=c.id_proveedor
                                INNER JOIN usuario AS u ON u.id_usuario=c.id_usuario
                                WHERE c.id_compra=?");
    $stmt->bind_param("i", $idCompra);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $compra = $resultado->fetch_assoc();
    $stmt->close();
    if (!$compra) responderCompra(false, "La compra no existe");

    $stmt = $conexion->prepare("SELECT d.id_producto, p.nombre AS producto, d.cantidad,
                                       d.costo_unitario, d.subtotal
                                FROM detalle_compra AS d
                                INNER JOIN producto AS p ON p.id_producto=d.id_producto
                                WHERE d.id_compra=? ORDER BY p.nombre");
    $stmt->bind_param("i", $idCompra);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $detalles = [];
    while ($fila = $resultado->fetch_assoc()) $detalles[] = $fila;
    $stmt->close();
    responderCompra(true, "Detalle consultado", ["compra" => $compra, "detalles" => $detalles]);
}

if ($opcion === "guardar") {
    $idProveedor = $_POST['id_proveedor'] ?? '';
    // El responsable se obtiene de la sesión, nunca de un ID enviado por el navegador.
    $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
    $detalleJson = $_POST['detalle'] ?? '';
    $detalles = json_decode($detalleJson, true);

    if (!enteroPositivo($idProveedor)) responderCompra(false, "Seleccione un proveedor válido");
    if (!enteroPositivo($idUsuario)) responderCompra(false, "La sesión de usuario no es válida");
    if (!is_array($detalles) || count($detalles) === 0) responderCompra(false, "Agregue al menos un producto a la compra");

    $lineas = [];
    $productosVistos = [];
    foreach ($detalles as $detalle) {
        $idProducto = $detalle['id_producto'] ?? '';
        $cantidad = $detalle['cantidad'] ?? '';
        $costoUnitario = trim((string) ($detalle['costo_unitario'] ?? ''));
        if (!enteroPositivo($idProducto)) responderCompra(false, "Hay un producto no válido en el detalle");
        if (!ctype_digit((string) $cantidad) || (int) $cantidad <= 0) responderCompra(false, "La cantidad de cada producto debe ser un entero mayor que cero");
        if (!decimalCompraValido($costoUnitario)) responderCompra(false, "Revise los costos unitarios; use hasta 8 enteros y 2 decimales");
        if (isset($productosVistos[(int) $idProducto])) responderCompra(false, "Cada producto debe aparecer una sola vez. Combine las cantidades repetidas");
        $productosVistos[(int) $idProducto] = true;
        $lineas[] = [
            "id_producto" => (int) $idProducto,
            "cantidad" => (int) $cantidad,
            "costo_unitario" => (float) $costoUnitario
        ];
    }
    usort($lineas, fn($a, $b) => $a['id_producto'] <=> $b['id_producto']);

    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("SELECT id_proveedor FROM proveedor WHERE id_proveedor=? AND estado='activo' FOR UPDATE");
        $stmt->bind_param("i", $idProveedor);
        $stmt->execute();
        $proveedorExiste = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$proveedorExiste) throw new DomainException("El proveedor no existe o está inactivo");

        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE id_usuario=? AND estado='activo' FOR UPDATE");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $usuarioExiste = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$usuarioExiste) throw new DomainException("El usuario seleccionado no existe o está inactivo");

        $tipoCompra = obtenerTipoMovimiento($conexion, "Compra", "entrada");
        $subtotal = 0.00;
        foreach ($lineas as &$linea) {
            $linea['subtotal'] = round($linea['cantidad'] * $linea['costo_unitario'], 2);
            $subtotal += $linea['subtotal'];
        }
        unset($linea);
        $subtotal = round($subtotal, 2);
        $iva = round($subtotal * 0.13, 2);
        $total = round($subtotal + $iva, 2);

        $stmtCompra = $conexion->prepare("INSERT INTO compra (id_proveedor, id_usuario, subtotal, iva_credito, total) VALUES (?, ?, ?, ?, ?)");
        $stmtCompra->bind_param("iiddd", $idProveedor, $idUsuario, $subtotal, $iva, $total);
        if (!$stmtCompra->execute()) throw new RuntimeException("No se pudo guardar el encabezado de la compra");
        $idCompra = $conexion->insert_id;
        $stmtCompra->close();

        $stmtProducto = $conexion->prepare("SELECT p.margen_ganancia, c.margen_ganancia AS margen_categoria
                                            FROM producto AS p
                                            INNER JOIN categoria AS c ON c.id_categoria=p.id_categoria
                                            WHERE p.id_producto=? AND p.estado='activo' FOR UPDATE");
        $stmtDetalle = $conexion->prepare("INSERT INTO detalle_compra (id_compra, id_producto, cantidad, costo_unitario, subtotal)
                                           VALUES (?, ?, ?, ?, ?)");
        $stmtInventarioCrear = $conexion->prepare("INSERT INTO inventario (id_producto, existencia, costo_promedio)
                                                   VALUES (?, 0, 0)
                                                   ON DUPLICATE KEY UPDATE id_producto=VALUES(id_producto)");
        $stmtInventarioLeer = $conexion->prepare("SELECT existencia, costo_promedio FROM inventario WHERE id_producto=? FOR UPDATE");
        $stmtInventarioActualizar = $conexion->prepare("UPDATE inventario SET existencia=?, costo_promedio=? WHERE id_producto=?");
        $stmtPrecio = $conexion->prepare("UPDATE producto SET precio_venta=? WHERE id_producto=?");
        $stmtKardex = $conexion->prepare("INSERT INTO kardex
            (id_producto, id_tipo_movimiento, id_usuario, id_compra, cantidad, costo_unitario, existencias, costo_promedio, saldo_total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($lineas as $linea) {
            $idProducto = $linea['id_producto'];
            $cantidad = $linea['cantidad'];
            $costoUnitario = $linea['costo_unitario'];
            $subtotalLinea = $linea['subtotal'];

            $stmtProducto->bind_param("i", $idProducto);
            $stmtProducto->execute();
            $producto = $stmtProducto->get_result()->fetch_assoc();
            if (!$producto) throw new DomainException("Uno de los productos no existe o está inactivo");

            $stmtDetalle->bind_param("iiidd", $idCompra, $idProducto, $cantidad, $costoUnitario, $subtotalLinea);
            if (!$stmtDetalle->execute()) throw new RuntimeException("No se pudo guardar un detalle de la compra");

            $stmtInventarioCrear->bind_param("i", $idProducto);
            if (!$stmtInventarioCrear->execute()) throw new RuntimeException("No se pudo preparar el inventario del producto");
            $stmtInventarioLeer->bind_param("i", $idProducto);
            $stmtInventarioLeer->execute();
            $inventario = $stmtInventarioLeer->get_result()->fetch_assoc();
            if (!$inventario) throw new RuntimeException("No se pudo consultar el inventario del producto");

            $existenciaAnterior = (int) $inventario['existencia'];
            $costoAnterior = (float) $inventario['costo_promedio'];
            $existenciaNueva = $existenciaAnterior + $cantidad;
            if ($existenciaNueva > 4294967295) throw new DomainException("La existencia supera el límite permitido");

            $costoPromedioNuevo = $existenciaAnterior === 0
                ? round($costoUnitario, 2)
                : round((($existenciaAnterior * $costoAnterior) + ($cantidad * $costoUnitario)) / $existenciaNueva, 2);
            if ($costoPromedioNuevo > 9999.99) throw new DomainException("El costo promedio supera el máximo permitido en inventario (9,999.99)");

            $margen = $producto['margen_ganancia'] !== null
                ? (float) $producto['margen_ganancia']
                : (float) $producto['margen_categoria'];
            if ($margen < 0 || $margen > 100) throw new DomainException("El margen configurado para un producto supera el 100%");
            $precioVenta = calcularPrecioVenta($costoPromedioNuevo, $margen);
            $saldoTotal = round($existenciaNueva * $costoPromedioNuevo, 2);

            $stmtInventarioActualizar->bind_param("idi", $existenciaNueva, $costoPromedioNuevo, $idProducto);
            if (!$stmtInventarioActualizar->execute()) throw new RuntimeException("No se pudo actualizar el inventario");
            $stmtPrecio->bind_param("di", $precioVenta, $idProducto);
            if (!$stmtPrecio->execute()) throw new RuntimeException("No se pudo recalcular el precio de venta");
            $stmtKardex->bind_param("iiiiididd", $idProducto, $tipoCompra, $idUsuario, $idCompra, $cantidad, $costoUnitario, $existenciaNueva, $costoPromedioNuevo, $saldoTotal);
            if (!$stmtKardex->execute()) throw new RuntimeException("No se pudo registrar el movimiento en kardex");
        }

        foreach ([$stmtProducto, $stmtDetalle, $stmtInventarioCrear, $stmtInventarioLeer, $stmtInventarioActualizar, $stmtPrecio, $stmtKardex] as $stmt) $stmt->close();
        $conexion->commit();
        responderCompra(true, "Compra registrada; inventario, kardex y precios actualizados", [
            "id_compra" => $idCompra,
            "subtotal" => $subtotal,
            "iva_credito" => $iva,
            "total" => $total
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log("DAOCompras guardar: " . $e->getMessage());
        $mensaje = $e instanceof DomainException ? $e->getMessage() : "No se pudo registrar la compra; no se guardaron cambios";
        responderCompra(false, $mensaje);
    }
}

if ($opcion === "anular") {
    $idCompra = $_POST['id_compra'] ?? '';
    // La anulación también queda atribuida al usuario autenticado.
    $idUsuario = (int) ($_SESSION['id_usuario'] ?? 0);
    if (!enteroPositivo($idCompra)) responderCompra(false, "Compra no válida");
    if (!enteroPositivo($idUsuario)) responderCompra(false, "La sesión de usuario no es válida");

    $conexion->begin_transaction();
    try {
        $stmtCompra = $conexion->prepare("SELECT id_compra, estado FROM compra WHERE id_compra=? FOR UPDATE");
        $stmtCompra->bind_param("i", $idCompra);
        $stmtCompra->execute();
        $compra = $stmtCompra->get_result()->fetch_assoc();
        $stmtCompra->close();
        if (!$compra) throw new DomainException("La compra no existe");
        if ($compra['estado'] !== 'registrada') throw new DomainException("La compra ya está anulada");

        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE id_usuario=? AND estado='activo' FOR UPDATE");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $usuarioExiste = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        if (!$usuarioExiste) throw new DomainException("El usuario seleccionado no existe o está inactivo");

        $tipoAnulacion = obtenerTipoMovimiento($conexion, "Anulación de compra", "salida");
        $stmtDetalles = $conexion->prepare("SELECT id_producto, cantidad, costo_unitario
                                            FROM detalle_compra WHERE id_compra=? ORDER BY id_producto");
        $stmtDetalles->bind_param("i", $idCompra);
        $stmtDetalles->execute();
        $resultadoDetalles = $stmtDetalles->get_result();
        $detalles = [];
        while ($fila = $resultadoDetalles->fetch_assoc()) $detalles[] = $fila;
        $stmtDetalles->close();
        if (!$detalles) throw new DomainException("La compra no tiene detalles para anular");

        $stmtUltimo = $conexion->prepare("SELECT k.id_kardex, k.id_compra, k.existencias, k.costo_promedio, t.naturaleza
                                         FROM kardex AS k
                                         INNER JOIN tipo_movimiento AS t ON t.id_tipo_movimiento=k.id_tipo_movimiento
                                         WHERE k.id_producto=? ORDER BY k.id_kardex DESC LIMIT 1 FOR UPDATE");
        $stmtAnterior = $conexion->prepare("SELECT existencias, costo_promedio FROM kardex
                                           WHERE id_producto=? AND id_kardex<? ORDER BY id_kardex DESC LIMIT 1");
        $stmtInventario = $conexion->prepare("SELECT existencia, costo_promedio FROM inventario WHERE id_producto=? FOR UPDATE");
        $stmtProducto = $conexion->prepare("SELECT p.margen_ganancia, c.margen_ganancia AS margen_categoria
                                           FROM producto AS p INNER JOIN categoria AS c ON c.id_categoria=p.id_categoria
                                           WHERE p.id_producto=? FOR UPDATE");
        $stmtActualizarInventario = $conexion->prepare("UPDATE inventario SET existencia=?, costo_promedio=? WHERE id_producto=?");
        $stmtActualizarPrecio = $conexion->prepare("UPDATE producto SET precio_venta=? WHERE id_producto=?");
        $stmtKardex = $conexion->prepare("INSERT INTO kardex
            (id_producto, id_tipo_movimiento, id_usuario, id_compra, cantidad, costo_unitario, existencias, costo_promedio, saldo_total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($detalles as $detalle) {
            $idProducto = (int) $detalle['id_producto'];
            $cantidad = (int) $detalle['cantidad'];
            $costoUnitario = (float) $detalle['costo_unitario'];

            $stmtUltimo->bind_param("i", $idProducto);
            $stmtUltimo->execute();
            $ultimo = $stmtUltimo->get_result()->fetch_assoc();
            if (!$ultimo || (int) $ultimo['id_compra'] !== (int) $idCompra || $ultimo['naturaleza'] !== 'entrada') {
                throw new DomainException("No se puede anular: el producto tuvo movimientos posteriores a esta compra");
            }

            $stmtInventario->bind_param("i", $idProducto);
            $stmtInventario->execute();
            $inventario = $stmtInventario->get_result()->fetch_assoc();
            if (!$inventario || (int) $inventario['existencia'] !== (int) $ultimo['existencias'] || round((float) $inventario['costo_promedio'], 2) !== round((float) $ultimo['costo_promedio'], 2)) {
                throw new DomainException("No se puede anular: el inventario actual no coincide con el último movimiento de kardex");
            }

            $idKardexActual = (int) $ultimo['id_kardex'];
            $stmtAnterior->bind_param("ii", $idProducto, $idKardexActual);
            $stmtAnterior->execute();
            $anterior = $stmtAnterior->get_result()->fetch_assoc();
            if ($anterior) {
                $existenciaNueva = (int) $anterior['existencias'];
                $costoPromedioNuevo = (float) $anterior['costo_promedio'];
            } else {
                if ((int) $ultimo['existencias'] !== $cantidad) {
                    throw new DomainException("No se puede anular con seguridad: había inventario previo que no está registrado en kardex");
                }
                $existenciaNueva = 0;
                $costoPromedioNuevo = 0.00;
            }

            $stmtProducto->bind_param("i", $idProducto);
            $stmtProducto->execute();
            $producto = $stmtProducto->get_result()->fetch_assoc();
            if (!$producto) throw new RuntimeException("No se encontró el producto asociado a la compra");
            $margen = $producto['margen_ganancia'] !== null
                ? (float) $producto['margen_ganancia']
                : (float) $producto['margen_categoria'];
            $precioVenta = calcularPrecioVenta($costoPromedioNuevo, $margen);
            $saldoTotal = round($existenciaNueva * $costoPromedioNuevo, 2);

            $stmtActualizarInventario->bind_param("idi", $existenciaNueva, $costoPromedioNuevo, $idProducto);
            if (!$stmtActualizarInventario->execute()) throw new RuntimeException("No se pudo revertir el inventario");
            $stmtActualizarPrecio->bind_param("di", $precioVenta, $idProducto);
            if (!$stmtActualizarPrecio->execute()) throw new RuntimeException("No se pudo recalcular el precio");
            $stmtKardex->bind_param("iiiiididd", $idProducto, $tipoAnulacion, $idUsuario, $idCompra, $cantidad, $costoUnitario, $existenciaNueva, $costoPromedioNuevo, $saldoTotal);
            if (!$stmtKardex->execute()) throw new RuntimeException("No se pudo registrar la anulación en kardex");
        }

        foreach ([$stmtUltimo, $stmtAnterior, $stmtInventario, $stmtProducto, $stmtActualizarInventario, $stmtActualizarPrecio, $stmtKardex] as $stmt) $stmt->close();
        $stmtCompra = $conexion->prepare("UPDATE compra SET estado='anulada' WHERE id_compra=? AND estado='registrada'");
        $stmtCompra->bind_param("i", $idCompra);
        if (!$stmtCompra->execute() || $stmtCompra->affected_rows !== 1) throw new DomainException("No se pudo cambiar el estado de la compra");
        $stmtCompra->close();
        $conexion->commit();
        responderCompra(true, "Compra anulada e inventario revertido");
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log("DAOCompras anular: " . $e->getMessage());
        $mensaje = $e instanceof DomainException ? $e->getMessage() : "No se pudo anular la compra; no se guardaron cambios";
        responderCompra(false, $mensaje);
    }
}

responderCompra(false, "Opción de compra no válida");
