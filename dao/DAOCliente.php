<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../conexion.php";

$opcion = $_POST["opcion"] ?? $_GET["opcion"] ?? "";

switch ($opcion) {

    case "guardar":
        guardarCliente($conexion);
        break;

    case "modificar":
        modificarCliente($conexion);
        break;

    case "eliminar":
        cambiarEstadoCliente($conexion);
        break;

    case "consultarDUI":
        consultarDUI($conexion);
        break;

    case "consultar":
        consultarClientes($conexion);
        break;

    case "consultarIndividual":
        consultarClienteIndividual($conexion);
        break;

    default:
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "Opción no válida"
        ]);
        break;
}

function guardarCliente($conexion)
{
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $dui = trim($_POST["dui"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $direccion = trim($_POST["direccion_entrega"] ?? "");
    $correo = trim($_POST["correo_electronico"] ?? "");
    $estado = strtolower(trim($_POST["estado"] ?? "activo"));

    if (
        $nombre === "" || $apellido === "" || $dui === "" ||
        $telefono === "" || $direccion === "" || $correo === ""
    ) {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "Debe completar todos los campos"
        ]);
        return;
    }

    $sql = "INSERT INTO cliente
            (nombre, apellido, dui, telefono, direccion_entrega,
             correo_electronico, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sssssss",
        $nombre,
        $apellido,
        $dui,
        $telefono,
        $direccion,
        $correo,
        $estado
    );

    if ($stmt->execute()) {
        echo json_encode([
            "resultado" => "correcto",
            "mensaje" => "Cliente guardado correctamente"
        ]);
    } else {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "No se pudo guardar el cliente"
        ]);
    }

    $stmt->close();
}

function modificarCliente($conexion)
{
    $id = intval($_POST["id_cliente"] ?? 0);
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $dui = trim($_POST["dui"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $direccion = trim($_POST["direccion_entrega"] ?? "");
    $correo = trim($_POST["correo_electronico"] ?? "");
    $estado = strtolower(trim($_POST["estado"] ?? "activo"));

    if ($id <= 0) {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "Identificador de cliente no válido"
        ]);
        return;
    }

    $sql = "UPDATE cliente
            SET nombre = ?, apellido = ?, dui = ?, telefono = ?,
                direccion_entrega = ?, correo_electronico = ?, estado = ?
            WHERE id_cliente = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sssssssi",
        $nombre,
        $apellido,
        $dui,
        $telefono,
        $direccion,
        $correo,
        $estado,
        $id
    );

    if ($stmt->execute()) {
        echo json_encode([
            "resultado" => "correcto",
            "mensaje" => "Cliente modificado correctamente"
        ]);
    } else {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "No se pudo modificar el cliente"
        ]);
    }

    $stmt->close();
}

function cambiarEstadoCliente($conexion)
{
    $id = intval($_POST["id_cliente"] ?? 0);

    if ($id <= 0) {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "Identificador de cliente no válido"
        ]);
        return;
    }

    $sql = "UPDATE cliente SET estado = 'inactivo' WHERE id_cliente = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo json_encode([
            "resultado" => "correcto",
            "mensaje" => "Cliente desactivado correctamente"
        ]);
    } else {
        echo json_encode([
            "resultado" => "error",
            "mensaje" => "No se pudo desactivar el cliente"
        ]);
    }

    $stmt->close();
}

function consultarDUI($conexion)
{
    $dui = trim($_POST["dui"] ?? $_GET["dui"] ?? "");
    $id = intval($_POST["id_cliente"] ?? $_GET["id_cliente"] ?? 0);

    $sql = "SELECT id_cliente FROM cliente WHERE dui = ? AND id_cliente <> ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $dui, $id);
    $stmt->execute();

    $resultado = $stmt->get_result();

    echo json_encode([
        "existe" => $resultado->num_rows > 0
    ]);

    $stmt->close();
}

function consultarClientes($conexion)
{
    $sql = "SELECT id_cliente, nombre, apellido, dui, telefono,
                   direccion_entrega, correo_electronico, estado
            FROM cliente
            ORDER BY id_cliente DESC";

    $resultado = $conexion->query($sql);
    $clientes = [];

    while ($fila = $resultado->fetch_assoc()) {
        $clientes[] = $fila;
    }

    echo json_encode($clientes);
}

function consultarClienteIndividual($conexion)
{
    $id = intval($_GET["id"] ?? $_POST["id_cliente"] ?? 0);

    $sql = "SELECT id_cliente, nombre, apellido, dui, telefono,
                   direccion_entrega, correo_electronico, estado
            FROM cliente
            WHERE id_cliente = ?";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $resultado = $stmt->get_result();
    $cliente = $resultado->fetch_assoc();

    echo json_encode($cliente);

    $stmt->close();
}
?>
