<?php
/**
 * Router para el servidor built-in de PHP.
 * Sirve archivos estáticos directamente; todo lo demás va a Laravel.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // PHP sirve el archivo estático directamente
}

require_once __DIR__ . '/index.php';
