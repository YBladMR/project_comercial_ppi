<?php

// Detecta si estás en local o en producción (hosting)
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    // Cambia 'project_comercial_ppi' por el nombre exacto de la carpeta en tu htdocs local
    define('BASE_URL', 'http://localhost/project_comercial_ppi/');
} else {
    // Si estás en el hosting (ej: tu subdominio)
    define('BASE_URL', 'https://comercial.kesug.com/');
}
?>