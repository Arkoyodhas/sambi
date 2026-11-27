<?php

/**
 * Cache file ringan untuk shared hosting tanpa Redis/Memcached.
 * Nilai disimpan di storage/cache yang diblokir dari akses web.
 */
class AppCache {
    private static function dir(): string {
        $dir = BASE_PATH . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }
        if (is_dir($dir)) {
            $deny = $dir . '/.htaccess';
            if (!is_file($deny)) @file_put_contents($deny, "Deny from all\n");
        }
        return $dir;
    }

    private static function path(string $key): string {
        return self::dir() . '/' . hash('sha256', $key) . '.cache.php';
    }

    public static function get(string $key, $default = null) {
        $file = self::path($key);
        if (!is_file($file)) return $default;
        $payload = @include $file;
        if (!is_array($payload) || !isset($payload['expires'])) {
            @unlink($file);
            return $default;
        }
        if ((int) $payload['expires'] < time()) {
            @unlink($file);
            return $default;
        }
        return $payload['value'] ?? $default;
    }

    public static function set(string $key, $value, int $ttl = 180): void {
        $ttl = max(5, min(86400, $ttl));
        $payload = '<?php return ' . var_export([
            'expires' => time() + $ttl,
            'value' => $value,
        ], true) . ';';
        $file = self::path($key);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $payload, LOCK_EX) !== false) {
            @chmod($tmp, 0640);
            @rename($tmp, $file);
        } else {
            @unlink($tmp);
        }
    }

    public static function remember(string $key, int $ttl, callable $resolver) {
        $sentinel = new stdClass();
        $cached = self::get($key, $sentinel);
        if ($cached !== $sentinel) return $cached;
        $value = $resolver();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): void {
        @unlink(self::path($key));
    }

    public static function clearPrefix(string $prefix): void {
        // File cache memakai hash; versi namespace dipakai untuk invalidasi murah.
        $versionFile = self::dir() . '/namespace-' . preg_replace('/[^a-z0-9_-]/i', '_', $prefix);
        @file_put_contents($versionFile, (string) microtime(true), LOCK_EX);
    }

    public static function namespaceVersion(string $prefix): string {
        $file = self::dir() . '/namespace-' . preg_replace('/[^a-z0-9_-]/i', '_', $prefix);
        return is_file($file) ? (string) @file_get_contents($file) : '1';
    }

    public static function prune(int $maxFiles = 20): void {
        $files = glob(self::dir() . '/*.cache.php') ?: [];
        if (count($files) < 100) return;
        $checked = 0;
        foreach ($files as $file) {
            if ($checked++ >= $maxFiles) break;
            $payload = @include $file;
            if (!is_array($payload) || (int) ($payload['expires'] ?? 0) < time()) @unlink($file);
        }
    }
}
