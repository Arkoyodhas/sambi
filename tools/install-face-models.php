<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Jalankan melalui CLI: php tools/install-face-models.php\n");
}

$base = dirname(__DIR__);
$target = $base . '/assets/vendor/human';
$cdn = 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/';
@mkdir($target . '/models', 0755, true);

function fetchFile(string $url): string {
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'follow_location' => 1,
            'user_agent' => 'DesaFaceModelInstaller/1.0',
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $body = @file_get_contents($url, false, $context);
    if (!is_string($body) || strlen($body) < 50 || preg_match('/^\s*<!doctype html/i', $body)) {
        throw new RuntimeException("Gagal mengunduh: $url");
    }
    return $body;
}

function saveAtomic(string $path, string $body): void {
    $tmp = $path . '.tmp-' . bin2hex(random_bytes(4));
    if (file_put_contents($tmp, $body, LOCK_EX) === false) throw new RuntimeException("Gagal menulis: $path");
    @chmod($tmp, 0644);
    if (!rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException("Gagal memindahkan: $path");
    }
}

try {
    echo "Mengunduh human.esm.js...\n";
    saveAtomic($target . '/human.esm.js', fetchFile($cdn . 'dist/human.esm.js'));

    $models = ['blazeface.json', 'facemesh.json', 'iris.json', 'faceres.json', 'antispoof.json', 'liveness.json'];
    $downloaded = [];
    foreach ($models as $model) {
        echo "Mengunduh model $model...\n";
        $json = fetchFile($cdn . 'models/' . $model);
        saveAtomic($target . '/models/' . $model, $json);
        $downloaded[$model] = true;

        $data = json_decode($json, true);
        foreach (($data['weightsManifest'] ?? []) as $manifest) {
            foreach (($manifest['paths'] ?? []) as $path) {
                $path = ltrim((string) $path, '/');
                if ($path === '' || str_contains($path, '..') || isset($downloaded[$path])) continue;
                echo "  bobot $path...\n";
                $body = fetchFile($cdn . 'models/' . $path);
                $dest = $target . '/models/' . $path;
                @mkdir(dirname($dest), 0755, true);
                saveAtomic($dest, $body);
                $downloaded[$path] = true;
            }
        }
    }

    $manifest = [
        'package' => '@vladmandic/human',
        'version' => '3.3.6',
        'installed_at' => date(DATE_ATOM),
        'files' => array_keys($downloaded),
    ];
    saveAtomic($target . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Selesai. Model lokal siap digunakan.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
