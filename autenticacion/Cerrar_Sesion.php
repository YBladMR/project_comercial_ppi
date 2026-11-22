<?php
require_once __DIR__ . '/../autenticacion/seguridad.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}

if (!validar_token_csrf($_POST['token_csrf'] ?? null)) {
    http_response_code(403);
    exit('La solicitud de cierre de sesión no es válida.');
}

cerrar_sesion();
header('Location: ' . BASE_URL . 'autenticacion/login.php?salida=1');
exit;
