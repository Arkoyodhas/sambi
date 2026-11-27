<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';
$GLOBALS['app_config'] = $config;
date_default_timezone_set($config['timezone'] ?? 'Asia/Jakarta');
require BASE_PATH . '/core/Helpers.php';

$errors = [];
$warnings = [];
$ok = [];

$pass = static function (string $message) use (&$ok): void { $ok[] = $message; };
$warn = static function (string $message) use (&$warnings): void { $warnings[] = $message; };
$fail = static function (string $message) use (&$errors): void { $errors[] = $message; };

if (PHP_VERSION_ID >= 80000) $pass('PHP 8.0+ tersedia.');
else $fail('PHP minimal 8.0 diperlukan.');

foreach (['pdo', 'pdo_mysql', 'json', 'openssl', 'fileinfo'] as $extension) {
    extension_loaded($extension)
        ? $pass("Ekstensi $extension aktif.")
        : $fail("Ekstensi $extension belum aktif.");
}
if (!extension_loaded('mbstring')) {
    $warn('mbstring tidak aktif; aplikasi akan menggunakan fallback UTF-8.');
}

foreach (['uploads', 'uploads/images', 'uploads/documents', 'uploads/gallery', 'uploads/news'] as $relative) {
    $path = BASE_PATH . '/' . $relative;
    if (!is_dir($path) && !@mkdir($path, 0775, true) && !is_dir($path)) {
        $fail("Folder $relative tidak tersedia dan gagal dibuat.");
        continue;
    }
    is_writable($path)
        ? $pass("Folder $relative dapat ditulis.")
        : $fail("Folder $relative tidak dapat ditulis oleh PHP.");
}

try {
    db()->query('SELECT 1');
    $pass('Koneksi database berhasil.');

    $modules = require BASE_PATH . '/config/modules.php';
    $schema = [];
    foreach (db_all(
        "SELECT TABLE_NAME, COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()"
    ) as $column) {
        $schema[(string) $column['TABLE_NAME']][(string) $column['COLUMN_NAME']] = true;
    }

    foreach ($modules as $module => $moduleConfig) {
        $table = (string) ($moduleConfig['table'] ?? '');
        if (!isset($schema[$table])) {
            $fail("Modul $module: tabel $table tidak ditemukan.");
            continue;
        }

        $required = ['id', 'created_at', 'updated_at'];
        foreach ((array) ($moduleConfig['fields'] ?? []) as $field) {
            if (!empty($field['name'])) $required[] = (string) $field['name'];
            if (!empty($field['store_meta'])) {
                $required[] = 'file_type';
                $required[] = 'file_size';
            }
        }
        if (!empty($moduleConfig['soft_delete'])) $required[] = 'deleted_at';
        if (!empty($moduleConfig['track_user'])) {
            $required[] = 'created_by';
            $required[] = 'updated_by';
        }
        if (!empty($moduleConfig['reorder'])) $required[] = 'sort_order';
        foreach ((array) ($moduleConfig['toggles'] ?? []) as $toggle) $required[] = (string) $toggle;

        $missing = [];
        foreach (array_unique($required) as $column) {
            if (!isset($schema[$table][$column])) $missing[] = $column;
        }
        if ($missing) $fail("Modul $module: kolom hilang pada $table: " . implode(', ', $missing));
        else $pass("Struktur modul $module sesuai.");
    }

    if (isset($schema['pages'])) {
        $emptyPages = (int) db_val(
            "SELECT COUNT(*) FROM pages
             WHERE deleted_at IS NULL
               AND (TRIM(COALESCE(title,''))='' OR TRIM(COALESCE(slug,''))='')"
        );
        $duplicateSlugs = (int) db_val(
            "SELECT COUNT(*) FROM (
                SELECT slug FROM pages
                WHERE deleted_at IS NULL
                GROUP BY slug HAVING COUNT(*) > 1
             ) duplicate_pages"
        );
        if ($emptyPages > 0) $warn("$emptyPages halaman aktif memiliki judul/slug kosong.");
        else $pass('Tidak ada halaman aktif dengan judul/slug kosong.');
        if ($duplicateSlugs > 0) $fail("$duplicateSlugs slug Halaman Statis terduplikasi.");
        else $pass('Slug Halaman Statis unik.');
    }

    $orphanChecks = [
        'Berita tanpa kategori yang valid' =>
            "SELECT COUNT(*) FROM news n LEFT JOIN news_categories c ON c.id=n.category_id
             WHERE n.category_id IS NOT NULL AND c.id IS NULL",
        'Galeri dengan album yang hilang' =>
            "SELECT COUNT(*) FROM gallery g LEFT JOIN gallery_albums a ON a.id=g.album_id
             WHERE g.album_id IS NOT NULL AND a.id IS NULL",
        'Penerima bansos tanpa program' =>
            "SELECT COUNT(*) FROM bansos_recipients r LEFT JOIN bansos_programs p ON p.id=r.program_id
             WHERE p.id IS NULL",
        'Permohonan tanpa jenis layanan' =>
            "SELECT COUNT(*) FROM service_requests r LEFT JOIN services s ON s.id=r.service_id
             WHERE s.id IS NULL",
    ];
    foreach ($orphanChecks as $label => $query) {
        try {
            $count = (int) db_val($query);
            $count > 0 ? $warn("$label: $count data.") : $pass("$label: tidak ditemukan.");
        } catch (Throwable $e) {
            $warn("$label: pemeriksaan dilewati karena struktur instalasi berbeda.");
        }
    }
} catch (Throwable $e) {
    $fail('Koneksi/pemeriksaan database gagal: ' . $e->getMessage());
}

echo "=== PEMERIKSAAN INTEGRITAS ADMIN ===\n";
foreach ($ok as $message) echo "[OK] $message\n";
foreach ($warnings as $message) echo "[PERINGATAN] $message\n";
foreach ($errors as $message) echo "[GAGAL] $message\n";
echo "\nRingkasan: " . count($ok) . " OK, " . count($warnings) . " peringatan, " . count($errors) . " gagal.\n";

exit($errors ? 1 : 0);
