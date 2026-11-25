<?php

$hostHeader = (string) ($_SERVER['HTTP_HOST'] ?? '');
$hostWithoutPort = strtolower((string) preg_replace('/:\\d+$/', '', $hostHeader));
$isLocalHost = in_array($hostWithoutPort, ['localhost', '127.0.0.1'], true);

if ($isLocalHost) {
    $origin = 'http://' . $hostHeader;
} else {
    $origin = 'https://comercial.kesug.com';
}

// Detecta la carpeta del proyecto para instalaciones en raíz o subcarpetas.
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
$scriptDirectory = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

foreach (['/forms', '/dao', '/autenticacion'] as $applicationDirectory) {
    $suffixLength = strlen($applicationDirectory);
    if (substr($scriptDirectory, -$suffixLength) === $applicationDirectory) {
        $scriptDirectory = substr($scriptDirectory, 0, -$suffixLength);
        break;
    }
}

$basePath = trim($scriptDirectory, '/');
$basePath = $basePath === '' ? '' : $basePath . '/';

if (!defined('BASE_URL')) {
    define('BASE_URL', $origin . '/' . $basePath);
}
?>
