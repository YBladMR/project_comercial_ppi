<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';
header('Content-Type: application/json; charset=utf-8');

$tipoP       = strtolower(trim($_POST['tipo_persona'] ?? $_POST['tipoP'] ?? ''));
$nombreRS    = trim($_POST['nombreRS'] ?? '');
$dui         = trim($_POST['dui'] ?? '');
$nit         = trim($_POST['nit'] ?? '');
$telefono    = trim($_POST['telefono'] ?? '');
$correo      = trim($_POST['correo'] ?? '');
$direccion   = trim($_POST['direccion'] ?? '');
$estado      = strtolower(trim($_POST['estado'] ?? ''));
$idproveedor = (int)($_POST['idproveedor'] ?? 0);
$opcion      = $_POST['opcion'] ?? '';
$operacionesGestionProveedor = ['guardar', 'modificar', 'baja', 'consultarDUI', 'consultarNIT'];
requerir_permiso(
    in_array($opcion, $operacionesGestionProveedor, true) ? 'proveedores.gestionar' : 'proveedores.consultar',
    true
);
if (in_array($opcion, ['guardar', 'modificar', 'baja'], true)) {
    requerir_post_api();
}

// Filtros de la tabla
$filtroNombre = trim($_POST['filtroNombre'] ?? '');
$filtroEstado = strtolower(trim($_POST['filtroEstado'] ?? ''));

// Vacío -> NULL (necesario por los UNIQUE de dui y nit)
$dui       = ($dui === '') ? null : $dui;
$nit       = ($nit === '') ? null : $nit;
$telefono  = ($telefono === '') ? null : $telefono;
$correo    = ($correo === '') ? null : $correo;
$direccion = ($direccion === '') ? null : $direccion;

function responder($ok, $msg)
{
    echo json_encode(["estado" => $ok, "mensaje" => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($opcion == "guardar") {
    if (!in_array($tipoP, ['natural', 'juridica'], true) || $nombreRS === '') {
        responder(false, "El tipo de persona y el nombre son obligatorios");
    }
    if ($tipoP === 'natural' && $dui === null) {
        responder(false, "El DUI es obligatorio para persona natural");
    }
    if ($tipoP === 'juridica' && $nit === null) {
        responder(false, "El NIT es obligatorio para persona jurídica");
    }
    if ($dui !== null && !preg_match('/^\d{8}-\d$/', $dui)) {
        responder(false, "Formato de DUI inválido (00000000-0)");
    }
    if ($nit !== null && !preg_match('/^\d{4}-\d{6}-\d{3}-\d$/', $nit)) {
        responder(false, "Formato de NIT inválido (0000-000000-000-0)");
    }
    if ($telefono !== null && !preg_match('/^\d{4}-\d{4}$/', $telefono)) {
        responder(false, "Formato de teléfono inválido (0000-0000)");
    }
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        responder(false, "Seleccione un estado");
    }

    require_once "../conexion.php";

    $sql = "INSERT INTO proveedor
            (tipo_persona, nombre_razon_social, dui, nit, telefono, correo_electronico, direccion, estado)
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssss", $tipoP, $nombreRS, $dui, $nit, $telefono, $correo, $direccion, $estado);

    if ($stmt->execute()) {
        responder(true, "Datos guardados correctamente");
    } elseif ($stmt->errno === 1062) {
        responder(false, "El DUI o NIT ya está registrado");
    } else {
        responder(false, "Error al guardar los datos");
    }
}

if ($opcion == "modificar") {
    if ($idproveedor <= 0) {
        responder(false, "Proveedor no válido");
    }
    if ($telefono !== null && !preg_match('/^\d{4}-\d{4}$/', $telefono)) {
        responder(false, "Formato de teléfono inválido (0000-0000)");
    }
    if (!in_array($estado, ['activo', 'inactivo'], true)) {
        responder(false, "Seleccione un estado");
    }

    require_once "../conexion.php";

    $sql = "UPDATE proveedor
            SET telefono=?, correo_electronico=?, direccion=?, estado=?
            WHERE id_proveedor=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssi", $telefono, $correo, $direccion, $estado, $idproveedor);

    if ($stmt->execute()) {
        responder(true, "Datos modificados correctamente");
    } else {
        responder(false, "Error al modificar los datos");
    }
}

if ($opcion == "baja") {
    if ($idproveedor <= 0) {
        responder(false, "Proveedor no válido");
    }

    require_once "../conexion.php";

    // Baja lógica: conserva el historial de compras asociadas
    $sql = "UPDATE proveedor SET estado='inactivo' WHERE id_proveedor=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idproveedor);

    if ($stmt->execute()) {
        responder(true, "Proveedor dado de baja correctamente");
    } else {
        responder(false, "Error al intentar dar de baja este proveedor");
    }
}

// Verifica si un DUI ya existe (para avisar al salir del campo)
if ($opcion == "consultarDUI") {
    require_once "../conexion.php";

    $sql = "SELECT dui FROM proveedor WHERE dui=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $dui);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        responder(true, "Este DUI ya existe");
    }
    responder(false, "");
}

// Verifica si un NIT ya existe
if ($opcion == "consultarNIT") {
    require_once "../conexion.php";

    $sql = "SELECT nit FROM proveedor WHERE nit=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $nit);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        responder(true, "Este NIT ya existe");
    }
    responder(false, "");
}

// Listado con filtros por nombre y estado
if ($opcion == "consultar") {
    require_once "../conexion.php";

    try {
        $sql = "SELECT id_proveedor, nombre_razon_social, tipo_persona, dui, nit,
                       telefono, direccion, correo_electronico, fecha_registro, estado
                FROM proveedor
                WHERE nombre_razon_social LIKE ?
                  AND (? = '' OR estado = ?)
                ORDER BY nombre_razon_social";

        $stmt = $conexion->prepare($sql);
        $like = "%" . $filtroNombre . "%";
        $stmt->bind_param("sss", $like, $filtroEstado, $filtroEstado);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $proveedores = [];

        while ($fila = $resultado->fetch_assoc()) {
            $proveedores[] = [
                'id_proveedor'        => $fila['id_proveedor'],
                'nombre_razon_social' => $fila['nombre_razon_social'],
                'tipo_persona'        => $fila['tipo_persona'],
                'dui'                 => $fila['dui'] ?? '',
                'nit'                 => $fila['nit'] ?? '',
                'telefono'            => $fila['telefono'] ?? '',
                'direccion'           => $fila['direccion'] ?? '',
                'correo_electronico'  => $fila['correo_electronico'] ?? '',
                'fecha_registro'      => date('d-m-Y', strtotime($fila['fecha_registro'])),
                'estado'              => $fila['estado']
            ];
        }

        echo json_encode($proveedores, JSON_UNESCAPED_UNICODE);

        $stmt->close();
        $conexion->close();
    } catch (Throwable $e) {
        echo json_encode([
            "mensaje" => "No se pudieron obtener los registros"
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Un solo proveedor, para llenar el formulario al modificar
if ($opcion == "consultarIndividual") {
    require_once "../conexion.php";

    $sql = "SELECT id_proveedor, nombre_razon_social, tipo_persona, dui, nit,
                   telefono, direccion, correo_electronico, fecha_registro, estado
            FROM proveedor WHERE id_proveedor=?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $idproveedor);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $d = $resultado->fetch_assoc();
        echo json_encode([
            "estado" => true,
            "datos" => [
                "id_proveedor"        => $d["id_proveedor"],
                "nombre_razon_social" => $d["nombre_razon_social"],
                "tipo_persona"        => $d["tipo_persona"],
                "dui"                 => $d["dui"] ?? '',
                "nit"                 => $d["nit"] ?? '',
                "telefono"            => $d["telefono"] ?? '',
                "direccion"           => $d["direccion"] ?? '',
                "correo_electronico"  => $d["correo_electronico"] ?? '',
                "fecha_registro"      => $d["fecha_registro"],
                "estado"              => $d["estado"]
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(["estado" => false, "datos" => "No Existe"]);
    }

    $stmt->close();
    $conexion->close();
}
