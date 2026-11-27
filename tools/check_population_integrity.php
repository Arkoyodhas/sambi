<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('BASE_PATH', dirname(__DIR__));
$config = require BASE_PATH . '/config/config.php';

$dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';
$pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$value = static function (string $sql) use ($pdo): int {
    return (int) $pdo->query($sql)->fetchColumn();
};

$checks = [
    'Penduduk aktif' => $value("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL"),
    'Keluarga aktif' => $value("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
    'Nomor KK penduduk tanpa keluarga' => $value(
        "SELECT COUNT(*) FROM (
            SELECT DISTINCT r.no_kk
            FROM residents r
            LEFT JOIN families f ON f.no_kk = r.no_kk AND f.deleted_at IS NULL
            WHERE r.deleted_at IS NULL
              AND r.no_kk REGEXP '^[0-9]{16}$'
              AND f.id IS NULL
        ) x"
    ),
    'Penduduk ber-KK tanpa relasi anggota' => $value(
        "SELECT COUNT(*)
         FROM residents r
         LEFT JOIN families f ON f.no_kk = r.no_kk AND f.deleted_at IS NULL
         LEFT JOIN family_members fm ON fm.resident_id = r.id AND fm.family_id = f.id
         WHERE r.deleted_at IS NULL
           AND r.no_kk REGEXP '^[0-9]{16}$'
           AND (f.id IS NULL OR fm.id IS NULL)"
    ),
    'Penduduk memiliki relasi ganda' => $value(
        "SELECT COUNT(*) FROM (
            SELECT resident_id
            FROM family_members
            GROUP BY resident_id
            HAVING COUNT(*) > 1
        ) x"
    ),
    'Keluarga memiliki kepala ganda' => $value(
        "SELECT COUNT(*) FROM (
            SELECT no_kk
            FROM residents
            WHERE deleted_at IS NULL
              AND no_kk REGEXP '^[0-9]{16}$'
              AND hubungan_keluarga = 'Kepala Keluarga'
            GROUP BY no_kk
            HAVING COUNT(*) > 1
        ) x"
    ),
    'Keluarga tanpa anggota' => $value(
        "SELECT COUNT(*) FROM (
            SELECT f.id
            FROM families f
            LEFT JOIN residents r ON r.no_kk = f.no_kk AND r.deleted_at IS NULL
            WHERE f.deleted_at IS NULL
            GROUP BY f.id
            HAVING COUNT(r.id) = 0
        ) x"
    ),
];

echo "PEMERIKSAAN INTEGRITAS PENDUDUK & KELUARGA\n";
echo str_repeat('=', 48) . "\n";
foreach ($checks as $label => $count) {
    printf("%-40s %8d\n", $label, $count);
}

$critical = $checks['Nomor KK penduduk tanpa keluarga']
    + $checks['Penduduk ber-KK tanpa relasi anggota']
    + $checks['Penduduk memiliki relasi ganda']
    + $checks['Keluarga memiliki kepala ganda'];

echo str_repeat('-', 48) . "\n";
if ($critical === 0) {
    echo "HASIL: LULUS — relasi utama konsisten.\n";
    exit(0);
}

echo "HASIL: PERLU SINKRONISASI.\n";
echo "Buka Admin > Data Keluarga lalu tekan Sinkronkan.\n";
exit(1);
