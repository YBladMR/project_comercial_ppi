<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$idproducto  = $_POST['id_producto'] ?? '';
$nombre      = trim($_POST['nombre'] ?? '');
$idcategoria = $_POST['id_categoria'] ?? '';
$estado      = $_POST['estado'] ?? '';

$stockminimo = vacioANulo($_POST['stock_minimo'] ?? '') ?? '0';

$descripcion = vacioANulo($_POST['descripcion'] ?? '');
$material    = vacioANulo($_POST['material'] ?? '');
$alto        = vacioANulo($_POST['alto_cm'] ?? '');
$ancho       = vacioANulo($_POST['ancho_cm'] ?? '');
$profundidad = vacioANulo($_POST['profundidad_cm'] ?? '');
$peso        = vacioANulo($_POST['peso_kg'] ?? '');

$opcion = $_POST['opcion'] ?? $_GET['opcion'] ?? '';
$operacionesGestionProducto = ['guardar', 'modificar', 'asignarMargen', 'cambiarEstado', 'consultarAtributos'];
requerir_permiso(
    in_array($opcion, $operacionesGestionProducto, true) ? 'productos.gestionar' : 'productos.consultar',
    true
);
if (in_array($opcion, $operacionesGestionProducto, true)) {
    requerir_post_api();
}

if ($opcion === 'consultarAtributos') {
    require_once __DIR__ . '/../conexion.php';
    $idCategoriaAtributos = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
    if (!$idCategoriaAtributos || $idCategoriaAtributos <= 0) {
        echo json_encode(['estado' => true, 'atributos' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmtAtributos = $conexion->prepare(
            "SELECT id_atributo, codigo, nombre, ayuda, tipo_dato, unidad, opciones,
                    obligatorio, orden
             FROM atributo_categoria
             WHERE id_categoria = ? AND estado = 'activo'
             ORDER BY orden, id_atributo"
        );
        $stmtAtributos->bind_param('i', $idCategoriaAtributos);
        $stmtAtributos->execute();
        $resultadoAtributos = $stmtAtributos->get_result();
        $atributos = [];
        while ($atributo = $resultadoAtributos->fetch_assoc()) {
            $atributo['obligatorio'] = (bool) $atributo['obligatorio'];
            $atributos[] = $atributo;
        }
        $stmtAtributos->close();

        $stmtCamposBase = $conexion->prepare(
            'SELECT mostrar_alto_cm, mostrar_ancho_cm, mostrar_profundidad_cm, mostrar_peso_kg, mostrar_material
             FROM categoria_campo_base WHERE id_categoria = ? LIMIT 1'
        );
        $stmtCamposBase->bind_param('i', $idCategoriaAtributos);
        $stmtCamposBase->execute();
        $camposBase = $stmtCamposBase->get_result()->fetch_assoc();
        $stmtCamposBase->close();
        if (!$camposBase) {
            $camposBase = [
                'mostrar_alto_cm' => true,
                'mostrar_ancho_cm' => true,
                'mostrar_profundidad_cm' => true,
                'mostrar_peso_kg' => true,
                'mostrar_material' => true,
            ];
        } else {
            foreach ($camposBase as $campo => $mostrar) {
                $camposBase[$campo] = (bool) $mostrar;
            }
        }
        echo json_encode([
            'estado' => true,
            'atributos' => $atributos,
            'campos_base' => $camposBase,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $error) {
        error_log('DAOProductos consultarAtributos: ' . $error->getMessage());
        http_response_code(500);
        echo json_encode(['estado' => false, 'mensaje' => 'No se pudieron cargar las especificaciones.'], JSON_UNESCAPED_UNICODE);
    }
    $conexion->close();
    exit;
}

if ($opcion == "guardar") {
    $error = validarProducto($nombre, $idcategoria, $descripcion, $material, $alto, $ancho, $profundidad, $peso, $stockminimo);
    if ($error !== null) {
        echo json_encode(["estado" => false, "mensaje" => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once "../conexion.php";

    if (productoDuplicado($conexion, $nombre, $idcategoria, 0)) {
        echo json_encode([
            "estado"  => false,
            "mensaje" => "Ya existe un producto con ese nombre en la categoría seleccionada"
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $stmtCategoria = $conexion->prepare(
        "SELECT id_categoria FROM categoria WHERE id_categoria = ? AND estado = 'activo' LIMIT 1"
    );
    $stmtCategoria->bind_param('i', $idcategoria);
    $stmtCategoria->execute();
    $categoriaProducto = $stmtCategoria->get_result()->fetch_assoc();
    $stmtCategoria->close();
    if (!$categoriaProducto) {
        echo json_encode(['estado' => false, 'mensaje' => 'La subcategoría seleccionada no está activa.'], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }
    // El margen es propio de cada producto; no se hereda desde su categoría.
    $margenInicial = 0.00;

    $validacionEspecificaciones = validarEspecificacionesProducto(
        $conexion,
        (int) $idcategoria,
        $_POST['especificaciones'] ?? []
    );
    if ($validacionEspecificaciones['error'] !== null) {
        echo json_encode([
            'estado' => false,
            'mensaje' => $validacionEspecificaciones['error']
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $conexion->begin_transaction();
    try {
        $sql = "INSERT INTO producto (nombre, descripcion, id_categoria, alto_cm, ancho_cm, profundidad_cm, peso_kg, material, stock_minimo, margen_ganancia)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssisssssid", $nombre, $descripcion, $idcategoria, $alto, $ancho, $profundidad, $peso, $material, $stockminimo, $margenInicial);
        $stmt->execute();
        $idProductoNuevo = (int) $conexion->insert_id;
        $stmt->close();
        guardarEspecificacionesProducto($conexion, $idProductoNuevo, $validacionEspecificaciones['valores']);
        $conexion->commit();
        echo json_encode([
            'estado' => true,
            'mensaje' => 'Datos guardados correctamente.'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $error) {
        $conexion->rollback();
        error_log('DAOProductos guardar: ' . $error->getMessage());
        echo json_encode(['estado' => false, 'mensaje' => 'No se pudo guardar el producto.'], JSON_UNESCAPED_UNICODE);
    }

    $conexion->close();
    exit;
}

if ($opcion == "modificar") {
    if (!ctype_digit((string) $idproducto) || (int) $idproducto <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "Producto no válido"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $error = validarProducto($nombre, $idcategoria, $descripcion, $material, $alto, $ancho, $profundidad, $peso, $stockminimo);
    if ($error !== null) {
        echo json_encode(["estado" => false, "mensaje" => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once "../conexion.php";

    if (productoDuplicado($conexion, $nombre, $idcategoria, (int) $idproducto)) {
        echo json_encode([
            "estado"  => false,
            "mensaje" => "Ya existe otro producto con ese nombre en la categoría seleccionada"
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $stmtAnterior = $conexion->prepare('SELECT id_categoria FROM producto WHERE id_producto = ? LIMIT 1');
    $stmtAnterior->bind_param('i', $idproducto);
    $stmtAnterior->execute();
    $productoAnterior = $stmtAnterior->get_result()->fetch_assoc();
    $stmtAnterior->close();
    if (!$productoAnterior) {
        echo json_encode([
            'estado' => false,
            'mensaje' => 'El producto ya no existe.'
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $validacionEspecificaciones = validarEspecificacionesProducto(
        $conexion,
        (int) $idcategoria,
        $_POST['especificaciones'] ?? []
    );
    if ($validacionEspecificaciones['error'] !== null) {
        echo json_encode([
            'estado' => false,
            'mensaje' => $validacionEspecificaciones['error']
        ], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $conexion->begin_transaction();
    try {
        $sql = "UPDATE producto
                SET nombre=?, descripcion=?, id_categoria=?, alto_cm=?, ancho_cm=?, profundidad_cm=?, peso_kg=?, material=?, stock_minimo=?
                WHERE id_producto=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssisssssii", $nombre, $descripcion, $idcategoria, $alto, $ancho, $profundidad, $peso, $material, $stockminimo, $idproducto);
        $stmt->execute();
        $stmt->close();

        if ((int) $productoAnterior['id_categoria'] !== (int) $idcategoria) {
            $stmtLimpiar = $conexion->prepare('DELETE FROM producto_especificacion WHERE id_producto = ?');
            $stmtLimpiar->bind_param('i', $idproducto);
        } else {
            $stmtLimpiar = $conexion->prepare(
                "DELETE pe FROM producto_especificacion AS pe
                 INNER JOIN atributo_categoria AS ac ON ac.id_atributo = pe.id_atributo
                 WHERE pe.id_producto = ? AND ac.id_categoria = ? AND ac.estado = 'activo'"
            );
            $stmtLimpiar->bind_param('ii', $idproducto, $idcategoria);
        }
        $stmtLimpiar->execute();
        $stmtLimpiar->close();
        guardarEspecificacionesProducto($conexion, (int) $idproducto, $validacionEspecificaciones['valores']);
        $conexion->commit();
        echo json_encode(['estado' => true, 'mensaje' => 'Producto y especificaciones actualizados correctamente.'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $error) {
        $conexion->rollback();
        error_log('DAOProductos modificar: ' . $error->getMessage());
        echo json_encode(['estado' => false, 'mensaje' => 'No se pudieron actualizar los datos del producto.'], JSON_UNESCAPED_UNICODE);
    }

    $conexion->close();
    exit;
}

if ($opcion == "asignarMargen") {
    $margen = vacioANulo($_POST['margen_ganancia'] ?? '');
    if (!ctype_digit((string) $idproducto) || (int) $idproducto <= 0 || !esDecimal($margen, 100) || (float) $margen <= 0) {
        echo json_encode(["estado" => false, "mensaje" => "El margen debe ser un número mayor que 0 y máximo de 100%"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once "../conexion.php";

    $sqlCosto = "SELECT i.costo_promedio
                 FROM producto AS p
                 LEFT JOIN inventario AS i ON i.id_producto = p.id_producto
                 WHERE p.id_producto=?";
    $stmtCosto = $conexion->prepare($sqlCosto);
    $stmtCosto->bind_param("i", $idproducto);
    $stmtCosto->execute();
    $resultadoCosto = $stmtCosto->get_result();

    if ($resultadoCosto->num_rows === 0) {
        echo json_encode(["estado" => false, "mensaje" => "El producto no existe"], JSON_UNESCAPED_UNICODE);
        $stmtCosto->close();
        $conexion->close();
        exit;
    }

    $filaCosto = $resultadoCosto->fetch_assoc();
    $stmtCosto->close();
    $costoPromedio = $filaCosto['costo_promedio'];
    if ($costoPromedio === null || (float) $costoPromedio <= 0) {
        $sqlGuardarMargen = "UPDATE producto SET margen_ganancia=? WHERE id_producto=?";
        $stmtGuardarMargen = $conexion->prepare($sqlGuardarMargen);
        $stmtGuardarMargen->bind_param("di", $margen, $idproducto);
        if ($stmtGuardarMargen->execute()) {
            echo json_encode([
                "estado"  => true,
                "mensaje" => "Margen guardado. El precio no se recalculó porque aún no hay costo promedio de inventario"
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(["estado" => false, "mensaje" => "Error al asignar el margen de ganancia"], JSON_UNESCAPED_UNICODE);
        }
        $stmtGuardarMargen->close();
        $conexion->close();
        exit;
    }

    $margen = (float) $margen;
    $precioCalculado = round((float) $costoPromedio * (1 + $margen / 100), 2);
    if ($precioCalculado > 9999.99) {
        echo json_encode(["estado" => false, "mensaje" => "El precio calculado supera el máximo permitido de $9,999.99"], JSON_UNESCAPED_UNICODE);
        $conexion->close();
        exit;
    }

    $sqlActualizar = "UPDATE producto SET margen_ganancia=?, precio_venta=? WHERE id_producto=?";
    $stmtActualizar = $conexion->prepare($sqlActualizar);
    $stmtActualizar->bind_param("ddi", $margen, $precioCalculado, $idproducto);

    if ($stmtActualizar->execute()) {
        echo json_encode([
            "estado"  => true,
            "mensaje" => "Margen asignado y precio de venta actualizado correctamente"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "estado"  => false,
            "mensaje" => "Error al asignar el margen de ganancia"
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmtActualizar->close();
    $conexion->close();
}

if ($opcion == "cambiarEstado") {
    if (!ctype_digit((string) $idproducto) || (int) $idproducto <= 0 || !in_array($estado, ['activo', 'inactivo'], true)) {
        echo json_encode(["estado" => false, "mensaje" => "Datos no válidos"], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require_once "../conexion.php";

    $sql = "UPDATE producto SET estado=? WHERE id_producto=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $estado, $idproducto);

    if ($stmt->execute()) {
        echo json_encode([
            "estado"  => true,
            "mensaje" => $estado == "inactivo" ? "Producto dado de baja correctamente" : "Producto reactivado correctamente"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "estado"  => false,
            "mensaje" => "Error al cambiar el estado"
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt->close();
    $conexion->close();
}

if ($opcion == "consultar") {
    require_once "../conexion.php";
    try {
        $sql = "SELECT p.id_producto, p.nombre, c.nombre AS categoria,
                       p.material, p.alto_cm, p.ancho_cm, p.profundidad_cm, p.peso_kg,
                       p.stock_minimo, p.precio_venta, p.margen_ganancia, p.estado,
                       COALESCE(i.costo_promedio, 0) AS costo_promedio,
                       COALESCE(i.existencia, 0) AS existencia
                FROM producto AS p
                INNER JOIN categoria AS c ON c.id_categoria = p.id_categoria
                LEFT JOIN inventario AS i ON i.id_producto = p.id_producto
                ORDER BY p.nombre";
        $resultado = $conexion->query($sql);
        if (!$resultado) {
            throw new Exception("Error al consultar los registros");
        }

        $productos = [];
        while ($fila = $resultado->fetch_assoc()) {
            $productos[] = [
                'id_producto'  => $fila['id_producto'],
                'nombre'       => $fila['nombre'],
                'categoria'    => $fila['categoria'],
                'material'     => $fila['material'],
                'alto'         => $fila['alto_cm'],
                'ancho'        => $fila['ancho_cm'],
                'profundidad'  => $fila['profundidad_cm'],
                'peso_kg'      => $fila['peso_kg'],
                'stock_minimo' => $fila['stock_minimo'],
                'existencia'   => $fila['existencia'],
                'precio_venta' => $fila['precio_venta'],
                'margen_ganancia' => $fila['margen_ganancia'],
                'costo_promedio' => $fila['costo_promedio'],
                'estado'       => $fila['estado']
            ];
        }

        echo json_encode($productos, JSON_UNESCAPED_UNICODE);
        $resultado->free();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
}

if ($opcion == "consultarIndividual") {
    require_once "../conexion.php";

    $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.id_categoria,
                   c.nombre AS categoria, c.id_padre AS categoria_id_padre, c.estado AS estado_categoria,
                   p.alto_cm, p.ancho_cm, p.profundidad_cm,
                   p.peso_kg, p.material, p.precio_venta, p.stock_minimo, p.estado
            FROM producto AS p
            INNER JOIN categoria AS c ON c.id_categoria = p.id_categoria
            WHERE p.id_producto=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idproducto);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $datos = $resultado->fetch_assoc();
        $stmtEspecificaciones = $conexion->prepare(
            'SELECT id_atributo, valor FROM producto_especificacion WHERE id_producto = ?'
        );
        $stmtEspecificaciones->bind_param('i', $idproducto);
        $stmtEspecificaciones->execute();
        $resultadoEspecificaciones = $stmtEspecificaciones->get_result();
        $especificaciones = [];
        while ($especificacion = $resultadoEspecificaciones->fetch_assoc()) {
            $especificaciones[(string) $especificacion['id_atributo']] = $especificacion['valor'];
        }
        $stmtEspecificaciones->close();

        echo json_encode([
            "estado" => true,
            "datos"  => [
                "id_producto"  => $datos["id_producto"],
                "nombre"       => $datos["nombre"],
                "descripcion"  => $datos["descripcion"],
                "id_categoria" => $datos["id_categoria"],
                "categoria"    => $datos["categoria"],
                "categoria_id_padre" => $datos["categoria_id_padre"],
                "estado_categoria" => $datos["estado_categoria"],
                "alto"         => $datos["alto_cm"],
                "ancho"        => $datos["ancho_cm"],
                "profundidad"  => $datos["profundidad_cm"],
                "peso_kg"      => $datos["peso_kg"],
                "material"     => $datos["material"],
                "precio_venta" => $datos["precio_venta"],
                "stock_minimo" => $datos["stock_minimo"],
                "estado"       => $datos["estado"],
                "especificaciones" => $especificaciones
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "estado" => false,
            "datos"  => "No Existe"
        ], JSON_UNESCAPED_UNICODE);
    }

    $stmt->close();
    $conexion->close();
}

function vacioANulo($valor) {
    $valor = trim((string) $valor);
    return $valor === '' ? null : $valor;
}

function esDecimal($valor, $maximo) {
    return preg_match('/^\d{1,10}(\.\d{1,2})?$/', (string) $valor) === 1 && (float) $valor <= $maximo;
}

function validarProducto($nombre, $idcategoria, $descripcion, $material, $alto, $ancho, $profundidad, $peso, $stockminimo) {
    if ($nombre == "" || mb_strlen($nombre) > 120) {
        return "El nombre es obligatorio (máximo 120 caracteres)";
    }
    if (!ctype_digit((string) $idcategoria) || (int) $idcategoria <= 0) {
        return "Seleccione una categoría";
    }
    if ($descripcion !== null && mb_strlen($descripcion) > 255) {
        return "La descripción no puede pasar de 255 caracteres";
    }
    if ($material !== null && mb_strlen($material) > 50) {
        return "El material no puede pasar de 50 caracteres";
    }
    $medidas = ["El alto" => $alto, "El ancho" => $ancho, "La profundidad" => $profundidad, "El peso" => $peso];
    foreach ($medidas as $etiqueta => $valor) {
        if ($valor !== null && !esDecimal($valor, 999.99)) {
            return "$etiqueta debe ser un número entre 0 y 999.99 (máximo 2 decimales)";
        }
    }
    if (preg_match('/^\d{1,9}$/', (string) $stockminimo) !== 1) {
        return "El stock mínimo debe ser un entero mayor o igual a 0";
    }
    return null;
}

function validarEspecificacionesProducto($conexion, int $idCategoria, $enviadas): array {
    if (!is_array($enviadas)) {
        return ['error' => 'Las especificaciones enviadas no son válidas.', 'valores' => []];
    }

    $stmtAtributos = $conexion->prepare(
        "SELECT id_atributo, nombre, tipo_dato, opciones, obligatorio
         FROM atributo_categoria
         WHERE id_categoria = ? AND estado = 'activo'"
    );
    $stmtAtributos->bind_param('i', $idCategoria);
    $stmtAtributos->execute();
    $resultadoAtributos = $stmtAtributos->get_result();
    $atributos = [];
    while ($atributo = $resultadoAtributos->fetch_assoc()) {
        $atributos[(string) $atributo['id_atributo']] = $atributo;
    }
    $stmtAtributos->close();

    foreach ($enviadas as $idAtributo => $valor) {
        if (!ctype_digit((string) $idAtributo) || !isset($atributos[(string) $idAtributo])) {
            return ['error' => 'Una especificación no pertenece a la subcategoría seleccionada.', 'valores' => []];
        }
        if (!is_scalar($valor)) {
            return ['error' => 'El valor de una especificación no es válido.', 'valores' => []];
        }
    }

    $valores = [];
    foreach ($atributos as $idAtributo => $atributo) {
        $valor = trim((string) ($enviadas[$idAtributo] ?? ''));
        $obligatorio = (bool) $atributo['obligatorio'];
        if ($valor === '') {
            if ($obligatorio) {
                return ['error' => 'Completa el campo "' . $atributo['nombre'] . '".', 'valores' => []];
            }
            continue;
        }

        $tipo = $atributo['tipo_dato'];
        if ($tipo === 'texto' && mb_strlen($valor, 'UTF-8') > 500) {
            return ['error' => 'El campo "' . $atributo['nombre'] . '" no puede superar 500 caracteres.', 'valores' => []];
        }
        if ($tipo === 'numero' && preg_match('/^\d{1,12}(?:\.\d{1,4})?$/', $valor) !== 1) {
            return ['error' => 'El campo "' . $atributo['nombre'] . '" debe ser un número mayor o igual a cero.', 'valores' => []];
        }
        if ($tipo === 'booleano' && !in_array($valor, ['0', '1'], true)) {
            return ['error' => 'Selecciona Sí o No en el campo "' . $atributo['nombre'] . '".', 'valores' => []];
        }
        if ($tipo === 'opcion') {
            $opciones = json_decode((string) $atributo['opciones'], true);
            if (!is_array($opciones) || !in_array($valor, $opciones, true)) {
                return ['error' => 'Selecciona una opción válida en el campo "' . $atributo['nombre'] . '".', 'valores' => []];
            }
        }

        $valores[] = ['id_atributo' => (int) $idAtributo, 'valor' => $valor];
    }

    return ['error' => null, 'valores' => $valores];
}

function guardarEspecificacionesProducto($conexion, int $idProducto, array $valores): void {
    if ($valores === []) {
        return;
    }

    $stmt = $conexion->prepare(
        'INSERT INTO producto_especificacion (id_producto, id_atributo, valor)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    foreach ($valores as $especificacion) {
        $idAtributo = (int) $especificacion['id_atributo'];
        $valor = (string) $especificacion['valor'];
        $stmt->bind_param('iis', $idProducto, $idAtributo, $valor);
        $stmt->execute();
    }
    $stmt->close();
}

function productoDuplicado($conexion, $nombre, $idcategoria, $idexcluir) {
    $sql = "SELECT id_producto FROM producto WHERE nombre=? AND id_categoria=? AND id_producto<>?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sii", $nombre, $idcategoria, $idexcluir);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $existe = $resultado->num_rows > 0;
    $stmt->close();
    return $existe;
}
