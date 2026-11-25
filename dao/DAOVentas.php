<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
$permisosVenta = [
    'guardar' => 'ventas.registrar',
    'consultarClientes' => 'ventas.registrar',
    'buscarClientes' => 'ventas.registrar',
    'consultarProductos' => 'ventas.registrar',
    'consultar' => 'ventas.consultar',
    'consultarDetalle' => 'ventas.consultar',
    'anular' => 'ventas.anular',
];

if (!isset($permisosVenta[$opcion])) {
    http_response_code(400);
    echo json_encode(['estado' => false, 'mensaje' => 'La operación de ventas no es válida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

requerir_permiso($permisosVenta[$opcion], true);
if (in_array($opcion, ['guardar', 'anular'], true)) {
    requerir_post_api();
}

const IVA_TASA = 0.13;   // IVA Débito Fiscal (13%)

$idventa = $_POST['id_venta'] ?? '';
$idcliente = $_POST['id_cliente'] ?? '';
$tipo = $_POST['tipo_venta'] ?? '';
$detalleJson = $_POST['detalle'] ?? '';
$primaEntrada = $_POST['prima'] ?? '';
$numeroCuotasEntrada = $_POST['numero_cuotas'] ?? '';
$fechaPrimerVencimientoEntrada = $_POST['fecha_primer_vencimiento'] ?? '';

const CREDITO_TASA_INTERES = 20.00;
const CREDITO_TASA_MORATORIA = 5.00;

// El permiso de ventas requiere una sesión autenticada; el usuario proviene de ella.
$idUsuario = (int) $_SESSION['id_usuario'];

/* ============================================================
 * GUARDAR VENTA (cabecera + detalle + inventario + kardex)
 * ============================================================ */
if ($opcion == "guardar") {
    require_once __DIR__ . '/../conexion.php';

    $lineas = json_decode($detalleJson, true);

    if (!ctype_digit((string) $idcliente) || (int) $idcliente <= 0) {
        responder(false, "Selecciona un cliente");
        $conexion->close();
        exit;
    }
    if ($tipo != "contado" && $tipo != "credito") {
        responder(false, "El tipo de venta no es válido");
        $conexion->close();
        exit;
    }
    if (!is_array($lineas) || count($lineas) == 0) {
        responder(false, "Agrega al menos un producto a la venta");
        $conexion->close();
        exit;
    }

    // Un producto no puede repetirse en la misma venta: si viene repetido se suman las cantidades
    $pedido = [];
    foreach ($lineas as $l) {
        $idp = filter_var($l['id_producto'] ?? 0, FILTER_VALIDATE_INT);
        $cant = filter_var($l['cantidad'] ?? 0, FILTER_VALIDATE_INT);
        if ($idp === false || $cant === false || $idp <= 0 || $cant <= 0) {
            responder(false, "Hay productos o cantidades no válidos");
            $conexion->close();
            exit;
        }
        $pedido[$idp] = ($pedido[$idp] ?? 0) + $cant;
    }

    try {
        // Cliente activo
        $stmt = $conexion->prepare("SELECT estado FROM cliente WHERE id_cliente=?");
        $stmt->bind_param("i", $idcliente);
        $stmt->execute();
        $cli = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$cli) {
            throw new RuntimeException("El cliente no existe");
        }
        if ($cli['estado'] != 'activo') {
            throw new RuntimeException("El cliente está inactivo");
        }

        // Usuario que registra
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuario WHERE id_usuario=?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();
        $usr = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$usr) {
            throw new RuntimeException("No hay un usuario válido para registrar la venta (id_usuario = $idUsuario). Crea un usuario en la tabla usuario");
        }

        $conexion->begin_transaction();

        // 1. Revisar cada producto (con bloqueo de fila) y calcular los montos con el precio de la BD
        $items = [];
        $subtotal = 0;

        foreach ($pedido as $idp => $cant) {
            $stmt = $conexion->prepare(
                "SELECT p.nombre, p.precio_venta, p.stock_minimo, p.estado,
                        i.id_inventario, IFNULL(i.existencia, 0) AS existencia, IFNULL(i.costo_promedio, 0) AS costo_promedio
                 FROM producto AS p
                 LEFT JOIN inventario AS i ON i.id_producto = p.id_producto
                 WHERE p.id_producto = ? FOR UPDATE"
            );
            $stmt->bind_param("i", $idp);
            $stmt->execute();
            $f = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$f) {
                throw new RuntimeException("El producto $idp no existe");
            }
            if ($f['estado'] != 'activo') {
                throw new RuntimeException("El producto \"" . $f['nombre'] . "\" está inactivo");
            }
            if ($f['id_inventario'] === null || (int) $f['existencia'] < $cant) {
                throw new RuntimeException("Existencia insuficiente de \"" . $f['nombre'] . "\" (disponible: " . (int) $f['existencia'] . ")");
            }

            $precio = round((float) $f['precio_venta'], 2);
            $sub = round($precio * $cant, 2);
            $subtotal += $sub;

            $items[] = [
                'id_producto' => $idp,
                'nombre' => $f['nombre'],
                'cantidad' => $cant,
                'precio' => $precio,
                'subtotal' => $sub,
                'existencia' => (int) $f['existencia'],
                'costo_promedio' => (float) $f['costo_promedio'],
                'stock_minimo' => (int) $f['stock_minimo']
            ];
        }

        $subtotal = round($subtotal, 2);
        $iva = round($subtotal * IVA_TASA, 2);
        $total = round($subtotal + $iva, 2);

        $planCredito = null;
        if ($tipo === 'credito') {
            $prima = normalizarMontoCredito($primaEntrada);
            $numeroCuotas = filter_var($numeroCuotasEntrada, FILTER_VALIDATE_INT);
            $fechaPrimerVencimiento = validarFechaPrimerVencimiento($fechaPrimerVencimientoEntrada);

            if ($prima === null || $prima <= 0 || $prima >= $total) {
                throw new RuntimeException("La prima debe ser mayor que cero y menor que el total de la venta.");
            }
            if ($numeroCuotas === false || $numeroCuotas < 1 || $numeroCuotas > 120) {
                throw new RuntimeException("El número de cuotas mensuales debe estar entre 1 y 120.");
            }

            $capitalFinanciado = round($total - $prima, 2);
            if ($capitalFinanciado < round(((int) $numeroCuotas) * 0.01, 2)) {
                throw new RuntimeException("El capital financiado no alcanza para distribuir al menos $0.01 de capital en cada cuota.");
            }
            $hoyCredito = new DateTimeImmutable('today', new DateTimeZone('America/El_Salvador'));
            $planCredito = generarPlanCredito(
                $capitalFinanciado,
                (int) $numeroCuotas,
                $hoyCredito,
                $fechaPrimerVencimiento,
                CREDITO_TASA_INTERES
            );
            $planCredito['prima'] = $prima;
            $planCredito['capital_financiado'] = $capitalFinanciado;
            $planCredito['numero_cuotas'] = (int) $numeroCuotas;
            $planCredito['fecha_primer_vencimiento'] = $fechaPrimerVencimiento->format('Y-m-d');
            $planCredito['fecha_aprobacion'] = $hoyCredito->format('Y-m-d');
        }

        // Al contado la venta queda pagada, al crédito queda pendiente
        $estadoVenta = $tipo == "contado" ? "pagado" : "pendiente";

        // 2. Cabecera de la venta
        $stmt = $conexion->prepare(
            "INSERT INTO venta (id_cliente, id_usuario, tipo_venta, estado, subtotal, iva_debito, total)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("iissddd", $idcliente, $idUsuario, $tipo, $estadoVenta, $subtotal, $iva, $total);
        $stmt->execute();
        $idNuevo = $conexion->insert_id;
        $stmt->close();

        // 3. Detalle, inventario y kardex
        $idTipoMov = obtenerTipoMovimiento($conexion, "Venta", "salida");
        $advertencias = [];

        foreach ($items as $it) {
            $stmt = $conexion->prepare(
                "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iiidd", $idNuevo, $it['id_producto'], $it['cantidad'], $it['precio'], $it['subtotal']);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("UPDATE inventario SET existencia = existencia - ? WHERE id_producto = ?");
            $stmt->bind_param("ii", $it['cantidad'], $it['id_producto']);
            $stmt->execute();
            $stmt->close();

            $nueva = $it['existencia'] - $it['cantidad'];

            registrarKardex($conexion, $it['id_producto'], $idTipoMov, $idUsuario, $idNuevo, $it['cantidad'], $it['costo_promedio'], $nueva);

            // Advertencia cuando la existencia queda menor o igual al stock mínimo
            if ($nueva <= $it['stock_minimo']) {
                $advertencias[] = "\"" . $it['nombre'] . "\" quedó con " . $nueva . " unidades (stock mínimo: " . $it['stock_minimo'] . ")";
            }
        }

        $idCreditoNuevo = null;
        if ($planCredito !== null) {
            $tasaInteres = CREDITO_TASA_INTERES;
            $tasaMoratoria = CREDITO_TASA_MORATORIA;
            $frecuencia = 'mensual';
            $plazo = $planCredito['numero_cuotas'];
            $estadoCredito = 'pendiente';
            $fechaPrimer = $planCredito['fecha_primer_vencimiento'];
            $fechaCalculo = $planCredito['fecha_aprobacion'];
            $cuota = $planCredito['cuota'];
            $prima = $planCredito['prima'];
            $capitalFinanciado = $planCredito['capital_financiado'];
            $saldoCapital = $capitalFinanciado;
            $numeroCuotas = $planCredito['numero_cuotas'];

            $stmtCredito = $conexion->prepare(
                "INSERT INTO credito
                    (id_venta, prima, capital_financiado, saldo_capital, tasa_interes,
                     tasa_interes_moratoria, cuota, numero_cuotas, frecuencia_pago, plazo,
                     fecha_aprobacion, fecha_primer_vencimiento, fecha_ultimo_calculo_interes, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtCredito->bind_param(
                'iddddddisissss',
                $idNuevo,
                $prima,
                $capitalFinanciado,
                $saldoCapital,
                $tasaInteres,
                $tasaMoratoria,
                $cuota,
                $numeroCuotas,
                $frecuencia,
                $plazo,
                $fechaCalculo,
                $fechaPrimer,
                $fechaCalculo,
                $estadoCredito
            );
            $stmtCredito->execute();
            $idCreditoNuevo = (int) $stmtCredito->insert_id;
            $stmtCredito->close();

            $stmtPlan = $conexion->prepare(
                "INSERT INTO plan_pago
                    (id_credito, numero_cuota, monto_cuota, capital_proyectado,
                     interes_proyectado, saldo_capital_proyectado, fecha_vencimiento, estado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente')"
            );
            $stmtEstadoCuota = $conexion->prepare(
                "INSERT INTO credito_cuota_estado
                    (id_plan_pago, capital_pendiente, interes_pendiente, mora_pendiente, fecha_ultimo_calculo_mora)
                 VALUES (?, ?, 0, 0, ?)"
            );
            $primerPlanId = null;
            foreach ($planCredito['cuotas'] as $cuotaPlan) {
                $numeroCuota = $cuotaPlan['numero'];
                $montoCuota = $cuotaPlan['monto'];
                $capitalCuota = $cuotaPlan['capital'];
                $interesCuota = $cuotaPlan['interes'];
                $saldoProyectado = $cuotaPlan['saldo'];
                $fechaVencimiento = $cuotaPlan['fecha'];
                $stmtPlan->bind_param(
                    'iidddds',
                    $idCreditoNuevo,
                    $numeroCuota,
                    $montoCuota,
                    $capitalCuota,
                    $interesCuota,
                    $saldoProyectado,
                    $fechaVencimiento
                );
                $stmtPlan->execute();
                $idPlanNuevo = (int) $stmtPlan->insert_id;
                if ($primerPlanId === null) {
                    $primerPlanId = $idPlanNuevo;
                }
                $capitalPendiente = $capitalCuota;
                $stmtEstadoCuota->bind_param('ids', $idPlanNuevo, $capitalPendiente, $fechaVencimiento);
                $stmtEstadoCuota->execute();
            }
            $stmtPlan->close();
            $stmtEstadoCuota->close();

            // La prima se registra como recibida en la venta; el crédito abre con el capital neto financiado.
            $fechaPagoPrima = (new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador')))->format('Y-m-d H:i:s');
            $tipoPagoPrima = 'prima';
            $montoCero = 0.00;
            $comprobantePrima = null;
            $stmtPrima = $conexion->prepare(
                "INSERT INTO pago
                    (id_plan_pago, id_usuario, fecha_pago, monto, tipo_pago, interes_corriente,
                     abono_capital, numero_comprobante, mora)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtPrima->bind_param(
                'iisdsddsd',
                $primerPlanId,
                $idUsuario,
                $fechaPagoPrima,
                $prima,
                $tipoPagoPrima,
                $montoCero,
                $montoCero,
                $comprobantePrima,
                $montoCero
            );
            $stmtPrima->execute();
            $idPagoPrima = (int) $stmtPrima->insert_id;
            $stmtPrima->close();

            $stmtEstadoPrima = $conexion->prepare(
                'INSERT INTO estado_cuenta (id_pago, monto_cuotas, abono_interes, abono_mora, abono_capital, saldo_final)
                 VALUES (?, ?, 0, 0, 0, ?)'
            );
            $stmtEstadoPrima->bind_param('idd', $idPagoPrima, $prima, $capitalFinanciado);
            $stmtEstadoPrima->execute();
            $stmtEstadoPrima->close();
        }

        $conexion->commit();

        responder(true, "Venta registrada correctamente", [
            "id_venta" => $idNuevo,
            "id_credito" => $idCreditoNuevo,
            "total" => $total,
            "advertencias" => $advertencias
        ]);
    } catch (RuntimeException $e) {
        $conexion->rollback();
        responder(false, $e->getMessage());
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log("DAOVentas guardar: " . $e->getMessage());
        responder(false, "Error al registrar la venta");
    }
    $conexion->close();
}

/* ============================================================
 * ANULAR VENTA: se cancela completa y se devuelve todo al inventario
 * ============================================================ */
if ($opcion == "anular") {
    require_once __DIR__ . '/../conexion.php';

    if (!ctype_digit((string) $idventa) || (int) $idventa <= 0) {
        responder(false, "Venta no válida");
        $conexion->close();
        exit;
    }

    try {
        $conexion->begin_transaction();

        $stmt = $conexion->prepare("SELECT estado, tipo_venta FROM venta WHERE id_venta = ? FOR UPDATE");
        $stmt->bind_param("i", $idventa);
        $stmt->execute();
        $venta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$venta) {
            throw new RuntimeException("La venta no existe");
        }
        if ($venta['estado'] == 'anulado') {
            throw new RuntimeException("La venta ya está anulada");
        }
        if ($venta['tipo_venta'] === 'credito') {
            throw new RuntimeException("Las ventas al crédito no se pueden anular desde ventas porque requieren gestionar la prima y el saldo del crédito.");
        }

        $stmt = $conexion->prepare("SELECT id_producto, cantidad FROM detalle_venta WHERE id_venta = ?");
        $stmt->bind_param("i", $idventa);
        $stmt->execute();
        $lineas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $idTipoMov = obtenerTipoMovimiento($conexion, "Anulación de venta", "entrada");

        foreach ($lineas as $l) {
            $idp = (int) $l['id_producto'];
            $cant = (int) $l['cantidad'];

            $stmt = $conexion->prepare("SELECT existencia, costo_promedio FROM inventario WHERE id_producto = ? FOR UPDATE");
            $stmt->bind_param("i", $idp);
            $stmt->execute();
            $inv = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($inv) {
                $stmt = $conexion->prepare("UPDATE inventario SET existencia = existencia + ? WHERE id_producto = ?");
                $stmt->bind_param("ii", $cant, $idp);
                $stmt->execute();
                $stmt->close();
                $nueva = (int) $inv['existencia'] + $cant;
                $costo = (float) $inv['costo_promedio'];
            } else {
                // No debería pasar, pero si el producto no tiene inventario se crea con lo devuelto
                $stmt = $conexion->prepare("INSERT INTO inventario (id_producto, existencia, costo_promedio) VALUES (?, ?, 0)");
                $stmt->bind_param("ii", $idp, $cant);
                $stmt->execute();
                $stmt->close();
                $nueva = $cant;
                $costo = 0;
            }

            registrarKardex($conexion, $idp, $idTipoMov, $idUsuario, (int) $idventa, $cant, $costo, $nueva);
        }

        $stmt = $conexion->prepare("UPDATE venta SET estado = 'anulado' WHERE id_venta = ?");
        $stmt->bind_param("i", $idventa);
        $stmt->execute();
        $stmt->close();

        $conexion->commit();

        responder(true, "Venta anulada correctamente. Los productos regresaron al inventario");
    } catch (RuntimeException $e) {
        $conexion->rollback();
        responder(false, $e->getMessage());
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log("DAOVentas anular: " . $e->getMessage());
        responder(false, "Error al anular la venta");
    }
    $conexion->close();
}

/* ============================================================
 * CONSULTAS
 * ============================================================ */

// Clientes activos para el select del formulario
if ($opcion == "buscarClientes") {
    require_once __DIR__ . '/../conexion.php';

    $termino = trim($_POST['termino'] ?? '');
    if (strlen($termino) < 2) {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $patron = '%' . $termino . '%';
    $stmt = $conexion->prepare(
        "SELECT id_cliente, nombre, IFNULL(apellido, '') AS apellido, dui
         FROM cliente
         WHERE estado = 'activo'
           AND (CONCAT_WS(' ', nombre, apellido) LIKE ? OR dui LIKE ?)
         ORDER BY nombre, apellido"
    );
    $stmt->bind_param('ss', $patron, $patron);
    $stmt->execute();
    $resultado = $stmt->get_result();

    $clientes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = [
            'id_cliente' => $fila['id_cliente'],
            'nombre' => trim($fila['nombre'] . ' ' . $fila['apellido']),
            'dui' => $fila['dui']
        ];
    }

    echo json_encode($clientes, JSON_UNESCAPED_UNICODE);
    $stmt->close();
    $conexion->close();
    exit;
}

if ($opcion == "consultarClientes") {
    require_once __DIR__ . '/../conexion.php';

    $sql = "SELECT id_cliente, nombre, IFNULL(apellido, '') AS apellido, dui
            FROM cliente WHERE estado = 'activo' ORDER BY nombre, apellido";
    $resultado = $conexion->query($sql);

    $clientes = [];
    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = [
            'id_cliente' => $fila['id_cliente'],
            'nombre' => trim($fila['nombre'] . ' ' . $fila['apellido']),
            'dui' => $fila['dui']
        ];
    }
    $resultado->free();

    echo json_encode($clientes, JSON_UNESCAPED_UNICODE);
    $conexion->close();
}

// Productos activos con su existencia para el formulario de venta
if ($opcion == "consultarProductos") {
    require_once __DIR__ . '/../conexion.php';

    $sql = "SELECT p.id_producto, p.nombre, p.precio_venta, p.stock_minimo, IFNULL(i.existencia, 0) AS existencia
            FROM producto AS p
            LEFT JOIN inventario AS i ON i.id_producto = p.id_producto
            WHERE p.estado = 'activo'
            ORDER BY p.nombre";
    $resultado = $conexion->query($sql);

    $productos = [];
    while ($fila = $resultado->fetch_assoc()) {
        $productos[] = [
            'id_producto' => $fila['id_producto'],
            'nombre' => $fila['nombre'],
            'precio_venta' => $fila['precio_venta'],
            'stock_minimo' => $fila['stock_minimo'],
            'existencia' => $fila['existencia']
        ];
    }
    $resultado->free();

    echo json_encode($productos, JSON_UNESCAPED_UNICODE);
    $conexion->close();
}

// Lista de ventas
if ($opcion == "consultar") {
    require_once __DIR__ . '/../conexion.php';

    try {
        $sql = "SELECT v.id_venta, v.fecha_hora,
                       CONCAT(c.nombre, ' ', IFNULL(c.apellido, '')) AS cliente, c.dui,
                       v.tipo_venta, v.estado, v.subtotal, v.iva_debito, v.total,
                       IFNULL(u.nombre, '') AS usuario
                FROM venta AS v
                INNER JOIN cliente AS c ON c.id_cliente = v.id_cliente
                LEFT JOIN usuario AS u ON u.id_usuario = v.id_usuario
                ORDER BY v.fecha_hora DESC, v.id_venta DESC";
        $resultado = $conexion->query($sql);

        if (!$resultado) {
            throw new Exception("Error al consultar las ventas");
        }

        $ventas = [];
        while ($fila = $resultado->fetch_assoc()) {
            $ventas[] = [
                'id_venta' => $fila['id_venta'],
                'fecha_hora' => $fila['fecha_hora'],
                'cliente' => trim($fila['cliente']),
                'dui' => $fila['dui'],
                'tipo_venta' => $fila['tipo_venta'],
                'estado' => $fila['estado'],
                'subtotal' => $fila['subtotal'],
                'iva_debito' => $fila['iva_debito'],
                'total' => $fila['total'],
                'usuario' => $fila['usuario']
            ];
        }

        echo json_encode($ventas, JSON_UNESCAPED_UNICODE);

        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
}

// Cabecera + detalle de una venta
if ($opcion == "consultarDetalle") {
    require_once __DIR__ . '/../conexion.php';

    $stmt = $conexion->prepare(
        "SELECT v.id_venta, v.fecha_hora,
                CONCAT(c.nombre, ' ', IFNULL(c.apellido, '')) AS cliente, c.dui,
                c.telefono, c.direccion_entrega,
                v.tipo_venta, v.estado, v.subtotal, v.iva_debito, v.total,
                IFNULL(u.nombre, '') AS usuario
         FROM venta AS v
         INNER JOIN cliente AS c ON c.id_cliente = v.id_cliente
         LEFT JOIN usuario AS u ON u.id_usuario = v.id_usuario
         WHERE v.id_venta = ?"
    );
    $stmt->bind_param("i", $idventa);
    $stmt->execute();
    $venta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$venta) {
        responder(false, "La venta no existe");
        $conexion->close();
        exit;
    }
    $venta['cliente'] = trim($venta['cliente']);

    $stmt = $conexion->prepare(
        "SELECT d.id_producto, p.nombre, d.cantidad, d.precio_unitario, d.subtotal
         FROM detalle_venta AS d
         INNER JOIN producto AS p ON p.id_producto = d.id_producto
         WHERE d.id_venta = ?
         ORDER BY d.id_detalle_venta"
    );
    $stmt->bind_param("i", $idventa);
    $stmt->execute();
    $detalle = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        "estado" => true,
        "venta" => $venta,
        "detalle" => $detalle
    ], JSON_UNESCAPED_UNICODE);
    $conexion->close();
}

/* ============================================================
 * FUNCIONES
 * ============================================================ */

function normalizarMontoCredito($valor): ?float
{
    $texto = trim((string) $valor);
    if ($texto === '' || !preg_match('/^\d+(?:\.\d{1,2})?$/', $texto)) {
        return null;
    }
    return round((float) $texto, 2);
}

function validarFechaPrimerVencimiento($valor): DateTimeImmutable
{
    $texto = trim((string) $valor);
    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto, new DateTimeZone('America/El_Salvador'));
    $errores = DateTimeImmutable::getLastErrors();
    if (!$fecha || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
        || $fecha->format('Y-m-d') !== $texto) {
        throw new RuntimeException('La fecha del primer vencimiento no es válida.');
    }

    $hoy = new DateTimeImmutable('today', new DateTimeZone('America/El_Salvador'));
    if ($fecha <= $hoy) {
        throw new RuntimeException('El primer vencimiento debe ser una fecha futura.');
    }
    return $fecha;
}

function fechaCuotaMensual(DateTimeImmutable $ancla, int $desplazamiento): DateTimeImmutable
{
    $primerDia = $ancla->modify('first day of this month')->modify('+' . $desplazamiento . ' months');
    $diaAncla = (int) $ancla->format('j');
    $ultimoDia = (int) $primerDia->format('t');
    return $primerDia->setDate(
        (int) $primerDia->format('Y'),
        (int) $primerDia->format('n'),
        min($diaAncla, $ultimoDia)
    );
}

function interesProyectadoPorDias(float $capital, float $tasaAnual, DateTimeImmutable $desde, DateTimeImmutable $hasta): float
{
    if ($capital <= 0 || $tasaAnual <= 0 || $hasta <= $desde) {
        return 0.0;
    }

    $cursor = $desde->setTime(0, 0);
    $fin = $hasta->setTime(0, 0);
    $total = 0.0;
    while ($cursor < $fin) {
        $inicioSiguienteAnio = $cursor->setDate((int) $cursor->format('Y') + 1, 1, 1);
        $finSegmento = $inicioSiguienteAnio < $fin ? $inicioSiguienteAnio : $fin;
        $dias = (int) $cursor->diff($finSegmento)->days;
        $diasAnio = $cursor->format('L') === '1' ? 366 : 365;
        $total += $capital * ($tasaAnual / 100) * $dias / $diasAnio;
        $cursor = $finSegmento;
    }
    return round($total, 2);
}

function generarPlanCredito(
    float $principal,
    int $numeroCuotas,
    DateTimeImmutable $fechaInicio,
    DateTimeImmutable $primerVencimiento,
    float $tasaAnual
): array {
    $simularSaldo = static function (float $cuota) use ($principal, $numeroCuotas, $fechaInicio, $primerVencimiento, $tasaAnual): float {
        $saldo = $principal;
        $fechaAnterior = $fechaInicio;
        for ($i = 0; $i < $numeroCuotas; $i++) {
            $vencimiento = fechaCuotaMensual($primerVencimiento, $i);
            $interes = interesProyectadoPorDias($saldo, $tasaAnual, $fechaAnterior, $vencimiento);
            $capitalPagado = min($saldo, max(0, round($cuota - $interes, 2)));
            $saldo = round(max(0, $saldo - $capitalPagado), 2);
            $fechaAnterior = $vencimiento;
        }
        return $saldo;
    };

    $inferior = 0.0;
    $superior = $principal * (1 + ($tasaAnual / 100) * ($numeroCuotas / 12)) + 100;
    for ($i = 0; $i < 80; $i++) {
        $medio = ($inferior + $superior) / 2;
        if ($simularSaldo($medio) > 0) {
            $inferior = $medio;
        } else {
            $superior = $medio;
        }
    }
    $cuotaRegular = round($superior, 2);
    while ($simularSaldo($cuotaRegular) > 0 && $cuotaRegular < $principal * 10) {
        $cuotaRegular = round($cuotaRegular + 0.01, 2);
    }

    $cuotas = [];
    $saldo = round($principal, 2);
    $fechaAnterior = $fechaInicio;
    $interesTotal = 0.0;
    for ($i = 0; $i < $numeroCuotas; $i++) {
        if ($saldo <= 0.009 && $i < $numeroCuotas - 1) {
            throw new RuntimeException('Con ese capital no se pueden distribuir pagos positivos en todas las cuotas; reduce el número de cuotas.');
        }
        $vencimiento = fechaCuotaMensual($primerVencimiento, $i);
        $interes = interesProyectadoPorDias($saldo, $tasaAnual, $fechaAnterior, $vencimiento);
        $monto = $i === $numeroCuotas - 1
            ? round($saldo + $interes, 2)
            : min($cuotaRegular, round($saldo + $interes, 2));
        $capital = min($saldo, max(0, round($monto - $interes, 2)));
        if ($i === $numeroCuotas - 1) {
            $capital = $saldo;
            $monto = round($capital + $interes, 2);
        }
        $saldo = round(max(0, $saldo - $capital), 2);
        $interesTotal += $interes;
        $cuotas[] = [
            'numero' => $i + 1,
            'fecha' => $vencimiento->format('Y-m-d'),
            'monto' => $monto,
            'capital' => $capital,
            'interes' => $interes,
            'saldo' => $saldo,
        ];
        $fechaAnterior = $vencimiento;
    }

    return [
        'cuota' => $cuotas[0]['monto'] ?? 0,
        'interes_total' => round($interesTotal, 2),
        'cuotas' => $cuotas,
    ];
}

function responder($estado, $mensaje, $extra = [])
{
    echo json_encode(
        array_merge(["estado" => $estado, "mensaje" => $mensaje], $extra),
        JSON_UNESCAPED_UNICODE
    );
}

// Devuelve el id del tipo de movimiento; si todavía no existe en el catálogo lo crea
function obtenerTipoMovimiento($conexion, $nombre, $naturaleza)
{
    $stmt = $conexion->prepare("SELECT id_tipo_movimiento FROM tipo_movimiento WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($fila) {
        return (int) $fila['id_tipo_movimiento'];
    }

    $stmt = $conexion->prepare("INSERT INTO tipo_movimiento (nombre, naturaleza) VALUES (?, ?)");
    $stmt->bind_param("ss", $nombre, $naturaleza);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    return (int) $id;
}

// Registra una fila del Kardex ligada a una venta
function registrarKardex($conexion, $idProducto, $idTipoMov, $idUsuario, $idVenta, $cantidad, $costoPromedio, $existencias)
{
    $saldo = round($existencias * $costoPromedio, 2);

    $stmt = $conexion->prepare(
        "INSERT INTO kardex (id_producto, id_tipo_movimiento, id_usuario, id_venta, cantidad, costo_unitario, existencias, costo_promedio, saldo_total)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("iiiiididd", $idProducto, $idTipoMov, $idUsuario, $idVenta, $cantidad, $costoPromedio, $existencias, $costoPromedio, $saldo);
    $stmt->execute();
    $stmt->close();
}
