<?php

/*
 * Fallback UTF-8 untuk shared hosting yang belum mengaktifkan ext-mbstring.
 * Fungsi hanya didefinisikan saat ekstensi asli tidak tersedia, sehingga aman
 * pada server yang sudah memiliki mbstring.
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen($string, $encoding = null): int {
        $string = (string) $string;
        if ($string === '') return 0;
        $count = preg_match_all('/./us', $string, $matches);
        return $count === false ? strlen($string) : $count;
    }
}
if (!function_exists('mb_substr')) {
    function mb_substr($string, $start, $length = null, $encoding = null): string {
        $string = (string) $string;
        $chars = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return $length === null ? substr($string, (int) $start) : substr($string, (int) $start, (int) $length);
        }
        $count = count($chars);
        $start = (int) $start;
        if ($start < 0) $start = max(0, $count + $start);
        if ($length === null) {
            $slice = array_slice($chars, $start);
        } else {
            $length = (int) $length;
            if ($length < 0) $length = max(0, $count - $start + $length);
            $slice = array_slice($chars, $start, $length);
        }
        return implode('', $slice);
    }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($string, $encoding = null): string {
        $map = [
            'À'=>'à','Á'=>'á','Â'=>'â','Ã'=>'ã','Ä'=>'ä','Å'=>'å','Æ'=>'æ','Ç'=>'ç',
            'È'=>'è','É'=>'é','Ê'=>'ê','Ë'=>'ë','Ì'=>'ì','Í'=>'í','Î'=>'î','Ï'=>'ï',
            'Ð'=>'ð','Ñ'=>'ñ','Ò'=>'ò','Ó'=>'ó','Ô'=>'ô','Õ'=>'õ','Ö'=>'ö','Ø'=>'ø',
            'Ù'=>'ù','Ú'=>'ú','Û'=>'û','Ü'=>'ü','Ý'=>'ý','Þ'=>'þ'
        ];
        return strtolower(strtr((string) $string, $map));
    }
}


function cfg(string $key, $default = null) {
    return $GLOBALS['app_config'][$key] ?? $default;
}

/* ---------- Sakelar fitur ---------- */
function features_all(): array {
    static $features = null;
    if ($features !== null) return $features;

    $file = BASE_PATH . '/config/features.php';
    $loaded = is_file($file) ? require $file : [];
    $features = is_array($loaded) ? $loaded : [];
    return $features;
}

function feature_enabled(string $feature): bool {
    $features = features_all();
    return !array_key_exists($feature, $features) || (bool) $features[$feature];
}

/**
 * Pemetaan segmen URL publik ke fitur.
 * URL eksternal, anchor, dan rute inti selalu diizinkan.
 */
function feature_for_public_path(string $path): ?string {
    $path = trim($path);
    if ($path === '' || $path === '#' || preg_match('#^(?:https?:)?//#i', $path)) return null;
    $segments = explode('/', trim($path, '/'));
    $segment = strtolower((string) ($segments[0] ?? ''));
    $sub = strtolower((string) ($segments[1] ?? ''));

    if ($segment === 'media') {
        return match ($sub) {
            'foto' => 'village_profile',
            'musik' => 'music_event',
            'galeri' => 'gallery',
            default => 'gallery',
        };
    }
    if ($segment === 'transparansi' && in_array($sub, ['idm', 'bansos', 'stunting'], true)) {
        return 'public_data';
    }

    $map = [
        'profil' => 'village_profile',
        'pemerintahan' => 'village_profile',
        'sotk' => 'village_profile',
        'sdgs' => 'village_profile',
        'berita' => 'news',
        'pengumuman' => 'news',
        'agenda' => 'news',
        'galeri' => 'gallery',
        'layanan' => 'services',
        'pengaduan' => 'complaints',
        'kontak' => 'contact',
        'statistik' => 'population',
        'transparansi' => 'transparency',
        'idm' => 'public_data',
        'bansos' => 'public_data',
        'stunting' => 'public_data',
        'dokumen' => 'documents',
        'umkm' => 'economy',
        'potensi' => 'economy',
        'halaman' => 'static_pages',
        'hiburan' => 'entertainment',
    ];
    return $map[$segment] ?? null;
}

function feature_public_path_enabled(string $path): bool {
    $feature = feature_for_public_path($path);
    return $feature === null || feature_enabled($feature);
}


/* ---------- Database (PDO) ---------- */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . cfg('db_host') . ';dbname=' . cfg('db_name') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, cfg('db_user'), cfg('db_pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function db_all(string $sql, array $p = []): array {
    $st = db()->prepare($sql); $st->execute($p); return $st->fetchAll();
}
function db_row(string $sql, array $p = []) {
    $st = db()->prepare($sql); $st->execute($p); $r = $st->fetch(); return $r ?: null;
}
function db_val(string $sql, array $p = []) {
    $st = db()->prepare($sql); $st->execute($p); return $st->fetchColumn();
}
function db_exec(string $sql, array $p = []): int {
    $st = db()->prepare($sql); $st->execute($p); return $st->rowCount();
}
function db_insert(string $table, array $data): int {
    $cols = array_keys($data);
    $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
    db()->prepare($sql)->execute(array_values($data));
    return (int) db()->lastInsertId();
}
function db_update(string $table, array $data, string $where, array $wp = []): int {
    $sets = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
    $st = db()->prepare("UPDATE `$table` SET $sets WHERE $where");
    $st->execute(array_merge(array_values($data), $wp));
    return $st->rowCount();
}
function paginate(string $sql, array $p = [], int $perPage = 15): array {
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $total = (int) db_val("SELECT COUNT(*) FROM ($sql) c", $p);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $offset = ($page - 1) * $perPage;
    $data = db_all($sql . " LIMIT $perPage OFFSET $offset", $p);
    return ['data' => $data, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
}

/* ---------- Output & URL ---------- */
function e($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function base_url(): string {
    static $base = null;
    if ($base === null) {
        $base = rtrim((string) cfg('base_url'), '/');
        if ($base === '') {
            $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $base = "$https://$host$dir";
        }
    }
    return $base;
}
function url(string $path = ''): string { return base_url() . '/' . ltrim($path, '/'); }
function asset(string $path): string {
    static $v = null;
    if ($v === null) { try { $v = setting('asset_version', '1') ?: '1'; } catch (Throwable $e) { $v = '1'; } }

    // Gabungkan versi pengaturan dengan waktu perubahan file agar browser/PWA
    // tidak mempertahankan CSS atau JavaScript lama setelah deployment.
    $cleanPath = explode('?', $path, 2)[0];
    $file = BASE_PATH . '/assets/' . ltrim($cleanPath, '/');
    $mtime = is_file($file) ? (string) @filemtime($file) : '';
    $version = $v . ($mtime !== '' ? '-' . $mtime : '');

    return base_url() . '/assets/' . ltrim($path, '/') . (strpos($path, '?') !== false ? '&' : '?') . 'v=' . rawurlencode($version);
}
function upload_url(string $path): string { return base_url() . '/uploads/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }

/* ---------- Settings & menu (cache file + cache per request) ---------- */
function settings_all(): array {
    if (isset($GLOBALS['settings_runtime_cache']) && is_array($GLOBALS['settings_runtime_cache'])) {
        return $GLOBALS['settings_runtime_cache'];
    }

    $version = Cache::version('settings');
    $settings = Cache::remember('settings:v' . $version, 300, static function (): array {
        $out = [];
        foreach (db_all("SELECT setting_key, setting_value FROM site_settings") as $row) {
            $out[$row['setting_key']] = $row['setting_value'];
        }
        return $out;
    });

    return $GLOBALS['settings_runtime_cache'] = is_array($settings) ? $settings : [];
}

function setting(string $key, string $default = ''): string {
    $settings = settings_all();
    return (isset($settings[$key]) && $settings[$key] !== '') ? (string) $settings[$key] : $default;
}

function set_setting(string $key, ?string $value): void {
    db_exec("INSERT INTO site_settings (setting_key, setting_value) VALUES (?,?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", [$key, $value]);
    unset($GLOBALS['settings_runtime_cache']);
    Cache::bumpVersion('settings');
    Cache::bumpVersion('public');
}

function cached_menus(string $location): array {
    $location = in_array($location, ['main', 'footer'], true) ? $location : 'main';
    $version = Cache::version('menus');
    return Cache::remember('menus:' . $location . ':v' . $version, 300, static function () use ($location): array {
        return db_all(
            "SELECT * FROM menus WHERE location = ? AND is_active = 1 ORDER BY sort_order ASC",
            [$location]
        );
    });
}

function invalidate_public_cache(string $namespace = 'public'): void {
    Cache::bumpVersion($namespace);
    if ($namespace !== 'public') Cache::bumpVersion('public');
}

/** Ambil warna CSS yang aman dari pengaturan. */
function setting_color(string $key, string $default): string {
    $value = trim(setting($key, $default));
    return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $default;
}

/** Ikon verifikasi yang diizinkan untuk badge website resmi. */
function verify_icon_class(): string {
    $allowed = [
        'bi-patch-check-fill',
        'bi-patch-check',
        'bi-check-circle-fill',
        'bi-shield-check',
        'bi-award-fill',
    ];
    $icon = trim(setting('verify_icon', 'bi-patch-check-fill'));
    return in_array($icon, $allowed, true) ? $icon : 'bi-patch-check-fill';
}

/** Render tanda verifikasi resmi; logo unggahan memakai mask agar mengikuti warna admin. */
function verify_mark_html(string $extraClass = ''): string {
    $classes = trim('verify-mark ' . $extraClass);
    if (trim(setting('verify_logo')) !== '') {
        return '<span class="' . e($classes . ' verify-mark-image') . '" aria-hidden="true"></span>';
    }
    return '<i class="' . e('bi ' . verify_icon_class() . ' ' . $classes) . '" aria-hidden="true"></i>';
}

/** True jika IP ada di daftar blokir. */
function ip_is_blocked(string $ip): bool {
    if ($ip === '') return false;
    static $cache = [];
    if (array_key_exists($ip, $cache)) return $cache[$ip];
    try {
        return $cache[$ip] = (bool) db_val("SELECT 1 FROM blocked_ips WHERE ip = ? LIMIT 1", [$ip]);
    } catch (Throwable $e) {
        return $cache[$ip] = false;
    }
}

/* ---------- Flash message ---------- */
function flash(?string $type = null, ?string $msg = null) {
    if ($type !== null) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; return null; }
    if (!empty($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}


/* ---------- Input lama setelah redirect validasi ---------- */
function remember_old_input(array $input, array $exclude = ['password', 'password_baru', 'password_konfirmasi', 'csrf_token', 'captcha']): void {
    foreach ($exclude as $key) unset($input[$key]);
    $clean = [];
    foreach ($input as $key => $value) {
        if (!is_string($key) || is_array($value)) continue;
        $clean[$key] = mb_substr((string) $value, 0, 3000);
    }
    $_SESSION['old_input'] = $clean;
}

function old(string $key, string $default = ''): string {
    $input = $GLOBALS['old_input'] ?? ($_SESSION['old_input'] ?? []);
    return isset($input[$key]) ? (string) $input[$key] : $default;
}

function old_checked(string $key, string $value = '1'): string {
    $input = $GLOBALS['old_input'] ?? ($_SESSION['old_input'] ?? []);
    if (!isset($input[$key])) return '';
    $stored = strtolower(trim((string) $input[$key]));
    if ($stored === strtolower($value)) return 'checked';
    return $value === '1' && in_array($stored, ['on', 'true', 'yes'], true) ? 'checked' : '';
}

function old_selected(string $key, string $value): string {
    $input = $GLOBALS['old_input'] ?? ($_SESSION['old_input'] ?? []);
    return isset($input[$key]) && (string) $input[$key] === $value ? 'selected' : '';
}

function clear_old_input(): void {
    unset($_SESSION['old_input'], $GLOBALS['old_input']);
}

function valid_email_or_empty(string $email): bool {
    return $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function normalize_phone_id(string $phone): string {
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') return '';
    if (str_starts_with($digits, '0')) $digits = '62' . substr($digits, 1);
    elseif (str_starts_with($digits, '8')) $digits = '62' . $digits;
    return preg_match('/^62[0-9]{8,13}$/', $digits) ? $digits : '';
}

/** URL thumbnail WEBP; file lama dibuat sekali saat pertama dibutuhkan. */
function thumbnail_url(?string $relativePath, int $width = 640, int $height = 420): string {
    $relativePath = trim((string) $relativePath);
    if ($relativePath === '') return '';
    try {
        $thumb = Upload::thumbnailWebp($relativePath, $width, $height);
        return $thumb !== '' ? upload_url($thumb) : upload_url($relativePath);
    } catch (Throwable $e) {
        return upload_url($relativePath);
    }
}

/* ---------- Format Indonesia ---------- */
function tgl_id(?string $date, bool $withTime = false): string {
    if (!$date || $date === '0000-00-00') return '-';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($date);
    if (!$ts) return '-';
    $out = date('j', $ts) . ' ' . $bulan[(int) date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) $out .= ' ' . date('H:i', $ts) . ' WIB';
    return $out;
}
function rupiah($n): string { return 'Rp ' . number_format((float) $n, 0, ',', '.'); }
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'n-a';
}
function unique_slug(string $table, string $text, int $exceptId = 0): string {
    $slug = slugify($text); $base = $slug; $i = 1;
    while (db_val("SELECT id FROM `$table` WHERE slug = ? AND id != ?", [$slug, $exceptId])) {
        $slug = $base . '-' . (++$i);
    }
    return $slug;
}
function excerpt(?string $html, int $len = 150): string {
    $text = strip_tags((string) $html);
    for ($i = 0; $i < 2 && str_contains($text, '&'); $i++) {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $text = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', strip_tags($text)) ?? '');
    return mb_strlen($text) > $len ? rtrim(mb_substr($text, 0, $len)) . '…' : $text;
}

/** Rapikan kapitalisasi judul (mis. "KERAJINAN KAYU" / "kembang goyang" -> Title Case). */
function title_case_id(?string $text): string {
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text) ?? '');
    if ($text === '') return '';
    $allCaps = mb_strtoupper($text, 'UTF-8') === $text;
    $words = explode(' ', $text);
    foreach ($words as &$w) {
        $isAcronym = !$allCaps && mb_strlen($w) <= 4 && preg_match('/^\p{Lu}{2,}$/u', $w);
        if (!$isAcronym) $w = mb_convert_case(mb_strtolower($w, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }
    return implode(' ', $words);
}

/** FAQ publik (tabel dibuat otomatis + data awal bila belum ada). */
function emergency_categories(): array {
    return ['ambulans' => 'Ambulans', 'mobil_siaga' => 'Mobil Siaga Desa', 'polisi' => 'Polisi', 'tni' => 'TNI / Koramil', 'damkar' => 'Pemadam Kebakaran', 'bpbd' => 'BPBD / SAR', 'kesehatan' => 'Puskesmas / Kesehatan', 'pln' => 'Listrik (PLN)', 'darurat' => 'Darurat Umum', 'lainnya' => 'Lainnya'];
}

function emergency_icons(): array {
    return ['bi-heart-pulse-fill' => 'Detak jantung (Ambulans)', 'bi-hospital-fill' => 'Rumah sakit', 'bi-truck-front-fill' => 'Mobil siaga', 'bi-car-front-fill' => 'Mobil', 'bi-shield-fill-check' => 'Perisai (Polisi)', 'bi-star-fill' => 'Bintang (TNI)', 'bi-fire' => 'Api (Damkar)', 'bi-life-preserver' => 'Pelampung (SAR)', 'bi-water' => 'Air / banjir', 'bi-lightning-charge-fill' => 'Petir (Listrik)', 'bi-telephone-fill' => 'Telepon', 'bi-megaphone-fill' => 'Pengeras suara', 'bi-people-fill' => 'Warga / Linmas'];
}

function emergency_contacts(): array {
    AdminSchema::ensureEmergency();
    return Cache::remember('emergency:v' . Cache::version('public'), 300, static function (): array {
        try {
            return db_all("SELECT id, name, category, phone, has_whatsapp, icon, color FROM emergency_contacts WHERE deleted_at IS NULL AND is_active=1 ORDER BY sort_order ASC, id ASC");
        } catch (Throwable $e) { return []; }
    });
}

function faq_items(): array {
    AdminSchema::ensureFaq();
    return Cache::remember('faq-items:v' . Cache::version('public'), 300, static fn(): array =>
        db_all("SELECT id, question, answer FROM faqs WHERE deleted_at IS NULL AND is_active=1 ORDER BY sort_order ASC, id ASC"));
}

/** Gambar lapisan pemandangan hero: unggahan admin atau bawaan. */
function hero_layer_url(string $key, string $default): string {
    $v = setting($key);
    return $v !== '' ? upload_url($v) : asset('img/hero/' . $default);
}
function umur(?string $tglLahir): int {
    if (!$tglLahir) return 0;
    return (int) date_diff(date_create($tglLahir), date_create('now'))->y;
}

/* ---------- Auth shortcuts ---------- */
function auth_user(): ?array { return Auth::user(); }
function can(string $perm): bool { return Auth::can($perm); }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
function verify_csrf(): void {
    $t = $_POST['csrf_token'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf_token'] ?? '', $t)) {
        http_response_code(419);
        exit('Sesi kedaluwarsa atau token tidak valid. Silakan muat ulang halaman.');
    }
}

/* ---------- Log aktivitas ---------- */
function client_ip(): string {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
function log_activity(string $action, string $module, string $description = ''): void {
    $u = Auth::user();
    db_insert('activity_logs', [
        'user_id' => $u['id'] ?? null,
        'action' => $action,
        'module' => $module,
        'description' => mb_substr($description, 0, 500),
        'ip_address' => mb_substr(client_ip(), 0, 45),
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}

/* ---------- Util lain ---------- */
function ticket_number(string $prefix): string {
    return $prefix . '-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}
function active_menu(string $path): string {
    $current = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $base = trim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if ($base && str_starts_with($current, $base)) $current = trim(substr($current, strlen($base)), '/');
    $seg = explode('/', $current)[0] ?? '';
    return $seg === trim($path, '/') ? 'active' : '';
}
function pagination_links(array $pg): string {
    if ($pg['pages'] <= 1) return '';
    $qs = $_GET; unset($qs['page']);
    $mk = function ($p) use ($qs) { $qs['page'] = $p; return '?' . http_build_query($qs); };
    $html = '<nav><ul class="pagination pagination-sm flex-wrap">';
    $cur = $pg['page']; $last = $pg['pages'];
    $html .= '<li class="page-item ' . ($cur <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . e($mk(max(1, $cur - 1))) . '">&laquo;</a></li>';
    $start = max(1, $cur - 2); $end = min($last, $cur + 2);
    if ($start > 1) $html .= '<li class="page-item"><a class="page-link" href="' . e($mk(1)) . '">1</a></li><li class="page-item disabled"><span class="page-link">…</span></li>';
    for ($i = $start; $i <= $end; $i++) {
        $html .= '<li class="page-item ' . ($i === $cur ? 'active' : '') . '"><a class="page-link" href="' . e($mk($i)) . '">' . $i . '</a></li>';
    }
    if ($end < $last) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li><li class="page-item"><a class="page-link" href="' . e($mk($last)) . '">' . $last . '</a></li>';
    $html .= '<li class="page-item ' . ($cur >= $last ? 'disabled' : '') . '"><a class="page-link" href="' . e($mk(min($last, $cur + 1))) . '">&raquo;</a></li>';
    return $html . '</ul></nav>';
}

/** Versi ringan (WEBP) untuk tampilan grid; foto terproteksi tetap lewat rute media. */
function gallery_thumb(array $g, int $w = 800, int $h = 600): string {
    if (empty($g['file_path'])) return gallery_src($g);
    if (!empty($g['protected']) || !empty($g['watermark'])) return gallery_sized($g, $w);
    return thumbnail_url($g['file_path'], $w, $h);
}

/** Versi foto terproteksi yang sudah diperkecil (watermark tetap ada) agar cepat di koneksi lambat. */
function gallery_sized(array $g, int $w): string {
    if (empty($g['protected']) && empty($g['watermark'])) return thumbnail_url($g['file_path'], $w, (int) round($w * 9 / 16));
    $size = $w <= 480 ? 480 : ($w <= 768 ? 768 : 1440);
    return url('media/galeri/' . (int) $g['id'] . '/' . $size);
}

/** Placeholder super kecil (±1 KB, base64) yang tampil buram selama foto asli dimuat. */
function image_lqip(?string $relPath): string {
    $relPath = ltrim(str_replace('\\', '/', trim((string) $relPath)), '/');
    $src = BASE_PATH . '/uploads/' . $relPath;
    if ($relPath === '' || str_contains($relPath, '..') || !is_file($src) || !function_exists('imagecreatetruecolor')) return '';
    $cacheDir = BASE_PATH . '/uploads/cache';
    $cache = $cacheDir . '/lqip_' . md5($relPath . '|' . (string) @filemtime($src)) . '.txt';
    if (is_file($cache)) return (string) file_get_contents($cache);
    $mime = (string) @mime_content_type($src);
    $img = match ($mime) {
        'image/png' => @imagecreatefrompng($src),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        'image/jpeg' => @imagecreatefromjpeg($src),
        default => false,
    };
    if (!$img) return '';
    $w = imagesx($img); $h = imagesy($img);
    $tw = 32; $th = max(1, (int) round($h * $tw / max(1, $w)));
    $tiny = imagecreatetruecolor($tw, $th);
    imagecopyresampled($tiny, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    ob_start(); imagejpeg($tiny, null, 55); $bin = (string) ob_get_clean();
    imagedestroy($img); imagedestroy($tiny);
    $uri = 'data:image/jpeg;base64,' . base64_encode($bin);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    @file_put_contents($cache, $uri);
    return $uri;
}

function gallery_src(array $g): string {
    if (!empty($g['protected']) || !empty($g['watermark'])) return url('media/galeri/' . $g['id']);
    return $g['file_path'] ? upload_url($g['file_path']) : '';
}

function youtube_id(?string $urlStr): string {
    $urlStr = trim((string) $urlStr);
    if ($urlStr === '') return '';

    $host = strtolower((string) parse_url($urlStr, PHP_URL_HOST));
    $path = trim((string) parse_url($urlStr, PHP_URL_PATH), '/');
    if (social_host_matches($host, 'youtu.be') && preg_match('/^([A-Za-z0-9_-]{6,20})/', $path, $m)) return $m[1];

    if (social_host_matches($host, 'youtube.com')) {
        parse_str((string) parse_url($urlStr, PHP_URL_QUERY), $query);
        if (!empty($query['v']) && preg_match('/^[A-Za-z0-9_-]{6,20}$/', (string) $query['v'])) {
            return (string) $query['v'];
        }
        if (preg_match('#^(?:embed|shorts|live|v)/([A-Za-z0-9_-]{6,20})#', $path, $m)) return $m[1];
    }
    return '';
}

function status_badge(string $status): string {
    $map = [
        'baru' => 'info', 'diterima' => 'info', 'diproses' => 'warning', 'selesai' => 'success',
        'ditolak' => 'danger', 'draft' => 'secondary', 'published' => 'success', 'scheduled' => 'info',
        'akan_datang' => 'info', 'berlangsung' => 'warning', 'dibatalkan' => 'danger',
        'hidup' => 'success', 'aktif' => 'success', 'meninggal' => 'dark', 'pindah' => 'warning', 'tidak_aktif' => 'secondary',
    ];
    $c = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $c . '">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}


/** Tentukan apakah launcher feedback benar-benar akan dirender untuk pengunjung ini. */
function feedback_available(): bool {
    static $available = null;
    if (!feature_enabled('feedback')) return false;
    if ($available !== null) return $available;
    try {
        if (setting('feedback_enabled', '1') !== '1') return $available = false;
        if (!(bool) db_val("SELECT 1 FROM feedback_questions WHERE is_active=1 LIMIT 1")) return $available = false;
        $responded = (bool) db_val(
            "SELECT 1 FROM feedback_responses WHERE ip_address = ? AND created_at > NOW() - INTERVAL 30 DAY LIMIT 1",
            [Visitor::ip()]
        );
        return $available = !$responded;
    } catch (Throwable $e) {
        return $available = false;
    }
}

/** Normalisasi URL sosial dan inferensi platform. */
function normalize_social_url(string $url, string $platform = ''): string {
    $url = trim($url);
    $platform = strtolower(trim($platform));
    if ($url === '') return '';

    // Admin boleh menempel handle saja, termasuk handle bertitik seperti @desa.resmi.
    if (!preg_match('#^https?://#i', $url) && in_array($platform, ['youtube', 'instagram', 'tiktok'], true)) {
        $looksLikeOfficialHost = (bool) preg_match(
            '#^(?:www\.)?(?:youtube\.com|youtu\.be|instagram\.com|tiktok\.com)(?:/|$)#i',
            ltrim($url, '/')
        );
        $handle = ltrim($url, '@');
        if (!$looksLikeOfficialHost && !str_contains($handle, '/')
            && $handle !== '' && preg_match('/^[A-Za-z0-9._-]+$/', $handle)) {
            if ($platform === 'youtube') $url = 'https://www.youtube.com/@' . $handle;
            elseif ($platform === 'instagram') $url = 'https://www.instagram.com/' . $handle . '/';
            elseif ($platform === 'tiktok') $url = 'https://www.tiktok.com/@' . $handle;
        }
    }

    if (!preg_match('#^https?://#i', $url)) $url = 'https://' . ltrim($url, '/');
    $url = preg_replace('#^http://#i', 'https://', $url);
    return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
}

function social_host_matches(string $host, string $domain): bool {
    $host = strtolower(trim($host, '.'));
    $domain = strtolower(trim($domain, '.'));
    return $host === $domain || str_ends_with($host, '.' . $domain);
}

function social_platform_from_url(string $url): string {
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    if (social_host_matches($host, 'youtu.be') || social_host_matches($host, 'youtube.com')) return 'youtube';
    if (social_host_matches($host, 'instagram.com')) return 'instagram';
    if (social_host_matches($host, 'tiktok.com')) return 'tiktok';
    return '';
}

/**
 * Bangun detail embed resmi untuk satu URL konten sosial.
 * Profil umum sengaja tidak dipaksa menjadi iframe karena YouTube, Instagram,
 * dan TikTok tidak menyediakan embed profil yang stabil tanpa API tambahan.
 */
function social_embed_details(string $sourceUrl, string $platform): array {
    $platform = strtolower(trim($platform));
    $host = strtolower((string) parse_url($sourceUrl, PHP_URL_HOST));
    $embed = '';
    $poster = '';
    $ratio = 'portrait';
    $kind = 'post';

    if ($platform === 'youtube'
        && (social_host_matches($host, 'youtube.com') || social_host_matches($host, 'youtu.be'))) {
        $ratio = 'wide';
        $videoId = youtube_id($sourceUrl);

        if ($videoId !== '') {
            $embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($videoId) . '?rel=0&modestbranding=1';
            $poster = 'https://i.ytimg.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg';
            $kind = 'video';
        } elseif (preg_match('/[?&]list=([A-Za-z0-9_-]+)/', $sourceUrl, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/videoseries?list=' . rawurlencode($m[1]) . '&rel=0';
            $kind = 'playlist';
        } elseif (preg_match('#youtube\.com/channel/(UC[A-Za-z0-9_-]+)#i', $sourceUrl, $m)) {
            $embed = 'https://www.youtube-nocookie.com/embed/videoseries?list=UU' . rawurlencode(substr($m[1], 2)) . '&rel=0';
            $kind = 'channel';
        }
    } elseif ($platform === 'instagram' && social_host_matches($host, 'instagram.com')) {
        if (preg_match('#instagram\.com/(p|reel|tv)/([A-Za-z0-9_-]+)#i', $sourceUrl, $m)) {
            $embed = 'https://www.instagram.com/' . strtolower($m[1]) . '/' . rawurlencode($m[2]) . '/embed/captioned/';
            $kind = strtolower($m[1]) === 'reel' ? 'reel' : 'post';
        }
    } elseif ($platform === 'tiktok' && social_host_matches($host, 'tiktok.com')) {
        if (preg_match('#/video/(\d+)#', $sourceUrl, $m)) {
            $embed = 'https://www.tiktok.com/player/v1/' . rawurlencode($m[1]) . '?autoplay=0&loop=0&controls=1&progress_bar=1&play_button=1&volume_control=1';
            $kind = 'video';
        }
    }

    return [
        'embed_url' => $embed,
        'poster_url' => $poster,
        'ratio' => $ratio,
        'kind' => $kind,
    ];
}

/**
 * Parse konten sosial beranda.
 * Mendukung URL saja atau format platform|URL|judul.
 * Hanya konten yang benar-benar dapat ditanam di halaman yang dikembalikan.
 */
function social_embed_items(): array {
    $lines = preg_split('/\R+/', trim(setting('home_social_items'))) ?: [];

    // Kolom sosial umum tetap dapat dipakai bila admin mengisinya dengan URL
    // video/post/playlist yang memang mendukung embed. URL profil tetap dipakai
    // oleh ikon footer, tetapi tidak dibuat menjadi kartu kosong.
    foreach (['youtube', 'instagram', 'tiktok'] as $platform) {
        $url = normalize_social_url(trim(setting($platform)), $platform);
        if ($url === '' || social_platform_from_url($url) !== $platform) continue;
        $details = social_embed_details($url, $platform);
        if ($details['embed_url'] !== '') {
            $lines[] = $platform . '|' . $url . '|' . ucfirst($platform) . ' Desa';
        }
    }

    $items = [];
    $seen = [];

    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') continue;

        $parts = array_map('trim', explode('|', $line, 3));
        if (count($parts) === 1) {
            $sourceUrl = normalize_social_url($parts[0]);
            $platform = social_platform_from_url($sourceUrl);
            $title = $platform ? ucfirst($platform) . ' Desa' : '';
        } else {
            $platform = strtolower($parts[0] ?? '');
            $sourceUrl = normalize_social_url($parts[1] ?? '', $platform);
            $title = $parts[2] ?? ucfirst($platform);
        }

        if (!in_array($platform, ['youtube', 'instagram', 'tiktok'], true) || $sourceUrl === '') continue;
        if (social_platform_from_url($sourceUrl) !== $platform) continue;

        $details = social_embed_details($sourceUrl, $platform);
        if ($details['embed_url'] === '') continue;

        $dedupeKey = $platform . '|' . strtolower(rtrim($sourceUrl, '/'));
        if (isset($seen[$dedupeKey])) continue;
        $seen[$dedupeKey] = true;

        $cleanTitle = trim($title ?: ucfirst($platform) . ' Desa');
        $items[] = [
            'platform' => $platform,
            'title' => function_exists('mb_substr') ? mb_substr($cleanTitle, 0, 120) : substr($cleanTitle, 0, 120),
            'url' => $sourceUrl,
            'embed_url' => $details['embed_url'],
            'poster_url' => $details['poster_url'],
            'ratio' => $details['ratio'],
            'kind' => $details['kind'],
            'type' => 'embed',
        ];

        if (count($items) >= 12) break;
    }

    return $items;
}
