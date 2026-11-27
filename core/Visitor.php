<?php

/** Pencatat kunjungan halaman publik + geolokasi IP (cache di tabel ip_locations, sumber: ip-api.com) */
class Visitor {

    private static bool $queued = false;

    /**
     * Menunda penulisan log sampai fase shutdown agar query INSERT tidak
     * memperlambat render HTML utama.
     */
    public static function queue(): void {
        if (self::$queued) return;
        self::$queued = true;
        register_shutdown_function(static function (): void {
            // Pada PHP-FPM respons dikirim ke pengguna lebih dahulu; INSERT log
            // berjalan setelah koneksi halaman selesai sehingga tidak menahan render.
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }
            self::track();
        });
    }

    public static function track(): void {
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
            if (!empty($_SESSION['user_id'])) return;
            $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            if ($ua === '' || preg_match('/bot|crawl|spider|slurp|curl|wget|python-|monitor|lighthouse|facebookexternalhit|whatsapp\//i', $ua)) return;

            $path = substr(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', 0, 190);
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base)) ?: '/';
            if (http_response_code() >= 400) return;
            if (preg_match('/\.(png|jpe?g|gif|webp|svg|ico|css|js|map|woff2?|ttf|mp4|webm|mp3|wav|pdf|xlsx?|docx?|zip|txt)$/i', $path)) return;

            $ip = self::ip();
            $today = date('Y-m-d');
            $isUnique = 0;
            if (($_SESSION['visit_day'] ?? '') !== $today) { $_SESSION['visit_day'] = $today; $isUnique = 1; }

            [$device, $browser] = self::parseUa($ua);
            $os = self::parseOs($ua);
            $lang = substr(trim(explode(',', (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))[0]), 0, 12) ?: null;
            /* PENTING: tidak ada lookup HTTP di jalur render halaman — hanya baca cache DB.
               Lokasi yang belum ada di-resolve lambat (batch) saat admin membuka Statistik Kunjungan. */
            $loc = self::cachedLocation($ip);

            db_exec("INSERT INTO visit_logs (path, ip_address, user_agent, referer, device, browser, os, language, is_unique, country, region_name, city, visited_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [
                $path, $ip, $ua,
                substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 255) ?: null,
                $device, $browser, $os, $lang, $isUnique,
                $loc['country'] ?? null, $loc['region_name'] ?? null, $loc['city'] ?? null,
                date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            error_log('Visitor track: ' . $e->getMessage());
        }
    }

    public static function ip(): string {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR'] as $h) {
            if (!empty($_SERVER[$h])) {
                $ip = trim(explode(',', $_SERVER[$h])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function parseUa(string $ua): array {
        $device = 'desktop';
        if (preg_match('/iPad|Tablet/i', $ua)) $device = 'tablet';
        elseif (preg_match('/Mobile|Android|iPhone/i', $ua)) $device = 'hp';
        $browser = 'Lainnya';
        if (stripos($ua, 'Edg') !== false) $browser = 'Edge';
        elseif (stripos($ua, 'OPR') !== false || stripos($ua, 'Opera') !== false) $browser = 'Opera';
        elseif (stripos($ua, 'Chrome') !== false) $browser = 'Chrome';
        elseif (stripos($ua, 'Firefox') !== false) $browser = 'Firefox';
        elseif (stripos($ua, 'Safari') !== false) $browser = 'Safari';
        return [$device, $browser];
    }

    public static function parseOs(string $ua): string {
        if (stripos($ua, 'Android') !== false) return 'Android';
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) return 'iOS';
        if (stripos($ua, 'Windows') !== false) return 'Windows';
        if (stripos($ua, 'Mac OS') !== false) return 'macOS';
        if (stripos($ua, 'Linux') !== false) return 'Linux';
        return 'Lainnya';
    }

    /** Lokasi dari cache DB SAJA (tanpa HTTP) — dipakai di jalur render halaman agar cepat. */
    public static function cachedLocation(string $ip): array {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => 'Lokal', 'region_name' => null, 'city' => null];
        }
        $cached = db_row("SELECT * FROM ip_locations WHERE ip = ? AND looked_up_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", [$ip]);
        if ($cached) return $cached['status'] === 'success' ? $cached : ['country' => '', 'region_name' => null, 'city' => null];
        return []; // belum ada di cache → country NULL (pending), di-resolve batch nanti
    }

    /** Resolve lokasi kunjungan yang tertunda (country IS NULL) secara batch.
        Dipanggil dari halaman admin Statistik Kunjungan — bukan dari halaman publik. */
    public static function resolvePending(int $max = 10, float $budgetSeconds = 4.0): void {
        try {
            $ips = db_all("SELECT DISTINCT ip_address FROM visit_logs WHERE country IS NULL LIMIT " . (int) $max);
            if (!$ips) return;
            $t0 = microtime(true);
            foreach ($ips as $row) {
                if (microtime(true) - $t0 > $budgetSeconds) break;
                $ip = (string) $row['ip_address'];
                $loc = self::locate($ip); // cek cache dulu; jika tidak ada baru HTTP (timeout singkat)
                db_exec("UPDATE visit_logs SET country = ?, region_name = ?, city = ? WHERE ip_address = ? AND country IS NULL", [
                    ($loc['country'] ?? '') !== '' ? $loc['country'] : '',
                    $loc['region_name'] ?? null,
                    $loc['city'] ?? null,
                    $ip,
                ]);
            }
        } catch (Throwable $e) {
            error_log('Visitor resolvePending: ' . $e->getMessage());
        }
    }

    /** Lokasi IP: cek cache ip_locations dulu; jika belum ada, lookup ip-api.com (timeout singkat, gagal pun di-cache). */
    public static function locate(string $ip): array {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['country' => 'Lokal', 'region_name' => null, 'city' => null];
        }
        $cached = db_row("SELECT * FROM ip_locations WHERE ip = ? AND looked_up_at > DATE_SUB(NOW(), INTERVAL 30 DAY)", [$ip]);
        if ($cached) return $cached['status'] === 'success' ? $cached : [];

        $data = self::lookup($ip);
        $ok = ($data['status'] ?? '') === 'success';
        db_exec("INSERT INTO ip_locations (ip, country, country_code, region_name, city, isp, lat, lon, status, looked_up_at)
                 VALUES (?,?,?,?,?,?,?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE country=VALUES(country), country_code=VALUES(country_code), region_name=VALUES(region_name),
                 city=VALUES(city), isp=VALUES(isp), lat=VALUES(lat), lon=VALUES(lon), status=VALUES(status), looked_up_at=NOW()", [
            $ip,
            $ok ? substr((string) $data['country'], 0, 80) : null,
            $ok ? substr((string) $data['countryCode'], 0, 2) : null,
            $ok ? substr((string) $data['regionName'], 0, 100) : null,
            $ok ? substr((string) $data['city'], 0, 100) : null,
            $ok ? substr((string) ($data['isp'] ?? ''), 0, 190) : null,
            $ok ? ($data['lat'] ?? null) : null,
            $ok ? ($data['lon'] ?? null) : null,
            $ok ? 'success' : 'fail',
        ]);
        return $ok ? ['country' => $data['country'], 'region_name' => $data['regionName'], 'city' => $data['city']] : [];
    }

    private static function lookup(string $ip): array {
        $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=status,message,country,countryCode,regionName,city,lat,lon,isp&lang=id';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 2,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $body, true);
        return is_array($data) ? $data : [];
    }
}
