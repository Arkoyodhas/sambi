<?php

/**
 * Cache file sederhana untuk data publik yang sering dibaca.
 *
 * Nilai selalu diserialisasi, ditulis atomik, dan memiliki TTL. Cache gagal
 * akan dilewati tanpa membuat halaman publik error.
 */
class Cache {

    private static function directory(): string {
        $dir = BASE_PATH . '/storage/cache';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        return $dir;
    }

    private static function file(string $key): string {
        return self::directory() . '/' . hash('sha256', $key) . '.cache';
    }

    public static function get(string $key, $default = null) {
        $file = self::file($key);
        if (!is_file($file) || !is_readable($file)) return $default;

        $handle = @fopen($file, 'rb');
        if (!$handle) return $default;
        try {
            if (!flock($handle, LOCK_SH)) return $default;
            $raw = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        $payload = @unserialize((string) $raw, ['allowed_classes' => false]);
        if (!is_array($payload) || !isset($payload['expires']) || $payload['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $payload['value'] ?? $default;
    }

    public static function put(string $key, $value, int $ttl = 180): void {
        $ttl = max(1, min(86400, $ttl));
        $file = self::file($key);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $payload = serialize(['expires' => time() + $ttl, 'value' => $value]);

        if (@file_put_contents($tmp, $payload, LOCK_EX) === false) return;
        @chmod($tmp, 0640);
        if (!@rename($tmp, $file)) @unlink($tmp);
    }

    public static function remember(string $key, int $ttl, callable $callback) {
        $sentinel = new stdClass();
        $cached = self::get($key, $sentinel);
        if ($cached !== $sentinel) return $cached;

        $value = $callback();
        self::put($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): void {
        @unlink(self::file($key));
    }

    public static function clear(): void {
        foreach (glob(self::directory() . '/*.cache') ?: [] as $file) @unlink($file);
    }

    public static function clearPrefix(string $prefix): void {
        // Nama file di-hash sehingga invalidasi prefix memakai versi namespace.
        self::bumpVersion($prefix);
    }

    public static function version(string $namespace): int {
        $file = self::directory() . '/version-' . hash('sha256', $namespace) . '.txt';
        return is_file($file) ? max(1, (int) @file_get_contents($file)) : 1;
    }

    public static function bumpVersion(string $namespace): int {
        $dir = self::directory();
        $file = $dir . '/version-' . hash('sha256', $namespace) . '.txt';
        $lock = @fopen($file . '.lock', 'c+');
        if (!$lock) return 1;
        try {
            flock($lock, LOCK_EX);
            $next = self::version($namespace) + 1;
            @file_put_contents($file, (string) $next, LOCK_EX);
            flock($lock, LOCK_UN);
            return $next;
        } finally {
            fclose($lock);
        }
    }
}
