<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80000) {
    http_response_code(500);
    exit('Website memerlukan PHP 8.0 atau lebih baru. Ubah versi PHP melalui panel hosting.');
}

define('BASE_PATH', __DIR__);

$config = require BASE_PATH . '/config/config.php';
$GLOBALS['app_config'] = $config;

date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');

ini_set('session.use_strict_mode', '1');
session_name('DESA_SESSION');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
]);
session_start();
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(self), camera=(self), microphone=()');
}

if (($config['debug'] ?? false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

require BASE_PATH . '/core/Cache.php';
require BASE_PATH . '/core/Helpers.php';
require BASE_PATH . '/core/Security.php';
require BASE_PATH . '/core/Auth.php';
require BASE_PATH . '/core/Upload.php';
require BASE_PATH . '/core/Controller.php';
require BASE_PATH . '/core/App.php';

spl_autoload_register(function ($class) {
    foreach (['/controllers/admin/', '/controllers/', '/core/'] as $dir) {
        $file = BASE_PATH . $dir . $class . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

(new App())->run();
