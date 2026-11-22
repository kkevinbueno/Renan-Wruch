<?php
// Roteador para o servidor embutido do PHP (somente desenvolvimento/testes):
//   php -S 127.0.0.1:8080 -t public public/router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path !== '/' && is_file(__DIR__ . $path) && !str_ends_with($path, '.php')) {
    if (str_ends_with($path, '.webmanifest')) {
        header('Content-Type: application/manifest+json');
        readfile(__DIR__ . $path);
        return true;
    }
    return false;
}
require __DIR__ . '/index.php';
