<?php
// Router untuk PHP built-in server (pengembangan lokal saja - tidak digunakan di cPanel)
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') return false;
require __DIR__ . '/index.php';
