<?php

class Security {

    private const HTML_TAGS = [
        'p','br','b','strong','i','em','u','s','ul','ol','li','a',
        'h2','h3','h4','h5','blockquote','table','thead','tbody','tr',
        'td','th','img','figure','figcaption','span','div','hr','iframe'
    ];

    private const GLOBAL_ATTRS = ['class', 'title', 'aria-label'];
    private const TAG_ATTRS = [
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height', 'loading'],
        'iframe' => ['src', 'width', 'height', 'allow', 'allowfullscreen', 'loading', 'referrerpolicy'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
    ];

    /** Sanitasi HTML berbasis DOM dan whitelist atribut. */
    public static function cleanHtml(?string $html): string {
        $html = trim((string) $html);
        if ($html === '') return '';

        // Hapus blok aktif sebelum parsing. DOM tetap menjadi lapisan utama;
        // pra-filter ini mencegah parser HTML memindahkan isi script/style
        // menjadi text node di luar elemen asal.
        $blocked = 'script|style|form|input|button|textarea|select|option|object|embed|link|meta|base|template|svg|math';
        $html = preg_replace('#<(' . $blocked . ')\b[^>]*>.*?</\1\s*>#is', '', $html) ?? '';
        $html = preg_replace('#</?(?:' . $blocked . ')\b[^>]*>#is', '', $html) ?? '';
        if ($html === '') return '';

        if (!class_exists('DOMDocument')) {
            // Fallback konservatif bila ekstensi DOM tidak tersedia.
            $allowed = '<p><br><b><strong><i><em><u><s><ul><ol><li><a><h2><h3><h4><h5><blockquote><table><thead><tbody><tr><td><th><figure><figcaption><span><div><hr>';
            $clean = strip_tags($html, $allowed);
            $clean = preg_replace('/\s(?:on\w+|style|srcdoc)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $clean) ?? '';
            $clean = preg_replace_callback(
                '/\s(href|src)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/iu',
                static function (array $match): string {
                    $name = strtolower((string) $match[1]);
                    $value = html_entity_decode((string) ($match[3] !== '' ? $match[3] : ($match[4] !== '' ? $match[4] : $match[5])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    if (!self::safeContentUrl(trim($value), false)) return '';
                    return ' ' . $name . '="' . htmlspecialchars(trim($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                },
                $clean
            ) ?? '';
            return $clean;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $wrapped = '<!doctype html><html><body><div id="desa-clean-root">' . $html . '</div></body></html>';
        $doc->loadHTML('<?xml encoding="UTF-8">' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('desa-clean-root');
        if (!$root) return '';

        self::sanitizeDomChildren($root, $doc);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $node) {
            $out .= $doc->saveHTML($node);
        }
        return trim($out);
    }

    private static function sanitizeDomChildren(DOMNode $parent, DOMDocument $doc): void {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                $parent->removeChild($node);
                continue;
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) continue;

            $tag = strtolower($node->nodeName);
            if (!in_array($tag, self::HTML_TAGS, true)) {
                // Elemen aktif/berbahaya dibuang seluruhnya. Elemen tak dikenal
                // yang pasif hanya di-unwrapped setelah seluruh anaknya disanitasi.
                if (in_array($tag, ['script','style','form','input','button','textarea','select','option','object','embed','link','meta','base','template','svg','math'], true)) {
                    $parent->removeChild($node);
                    continue;
                }
                self::sanitizeDomChildren($node, $doc);
                while ($node->firstChild) $parent->insertBefore($node->firstChild, $node);
                $parent->removeChild($node);
                continue;
            }

            $allowed = array_merge(self::GLOBAL_ATTRS, self::TAG_ATTRS[$tag] ?? []);
            if ($node->hasAttributes()) {
                foreach (iterator_to_array($node->attributes) as $attr) {
                    $name = strtolower($attr->name);
                    if (!in_array($name, $allowed, true) || str_starts_with($name, 'on') || in_array($name, ['style', 'srcdoc', 'id'], true)) {
                        $node->removeAttributeNode($attr);
                        continue;
                    }

                    $value = trim($attr->value);
                    if (in_array($name, ['href', 'src'], true) && !self::safeContentUrl($value, $tag === 'img')) {
                        $node->removeAttribute($name);
                    }
                }
            }

            if ($tag === 'a' && $node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
            if ($tag === 'img') {
                $node->setAttribute('loading', 'lazy');
                $node->removeAttribute('srcset');
            }
            if ($tag === 'iframe') {
                $src = $node->getAttribute('src');
                if (!self::allowedIframe($src)) {
                    $parent->removeChild($node);
                    continue;
                }
                $node->setAttribute('loading', 'lazy');
                $node->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            }

            self::sanitizeDomChildren($node, $doc);
        }
    }

    private static function safeContentUrl(string $value, bool $allowImageData = false): bool {
        if ($value === '' || str_starts_with($value, '#')) return true;
        if (str_starts_with($value, '//')) return false;
        // Path lokal satu garis miring diperbolehkan.
        if (str_starts_with($value, '/')) return true;
        if ($allowImageData && preg_match('#^data:image/(?:png|jpeg|gif|webp);base64,[A-Za-z0-9+/=\s]+$#i', $value)) {
            return strlen($value) <= 200000;
        }
        $parts = parse_url($value);
        if ($parts === false || empty($parts['scheme'])) return true;
        return in_array(strtolower((string) $parts['scheme']), ['http', 'https', 'mailto', 'tel'], true);
    }

    private static function allowedIframe(string $value): bool {
        if (!filter_var($value, FILTER_VALIDATE_URL)) return false;
        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $path = (string) parse_url($value, PHP_URL_PATH);
        if (in_array($host, ['www.youtube.com', 'youtube.com', 'www.youtube-nocookie.com', 'youtube-nocookie.com'], true)) {
            return str_starts_with($path, '/embed/');
        }
        return in_array($host, ['www.google.com', 'google.com', 'maps.google.com'], true)
            && (str_contains($path, '/maps') || str_contains($value, '/maps/embed'));
    }

    /** Sanitasi file SVG (logo/icon). */
    public static function sanitizeSvg(string $content): ?string {
        if (stripos($content, '<svg') === false) return null;
        $content = preg_replace('/<\?xml.*?\?>/is', '', $content);
        $content = preg_replace('/<!DOCTYPE.*?>/is', '', $content);
        $content = preg_replace('/<(script|foreignObject|iframe|object|embed).*?<\/\1>/is', '', $content);
        $content = preg_replace('/\s(?:on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/iu', '', $content);
        $content = preg_replace('/(xlink:href|href)\s*=\s*(["\']?)\s*(javascript|data|vbscript):[^"\'>\s]*\2/iu', '', $content);
        if (stripos($content, '<script') !== false || stripos($content, 'javascript:') !== false) return null;
        return trim($content);
    }

    /**
     * Captcha per-form. Return ['id' => ..., 'question' => ...].
     * Token terpisah mencegah tab lain menimpa jawaban captcha saat ini.
     */
    public static function captcha(string $context = 'public'): array {
        self::cleanupCaptchas();
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $id = bin2hex(random_bytes(16));
        $_SESSION['captchas'][$id] = [
            'answer' => $a + $b,
            'context' => mb_substr($context, 0, 40),
            'expires' => time() + 600,
        ];
        return ['id' => $id, 'question' => "$a + $b = ?"];
    }

    /** Kompatibilitas tampilan lama; gunakan captchaId() pada form baru. */
    public static function captchaQuestion(string $context = 'legacy'): string {
        $captcha = self::captcha($context);
        $_SESSION['legacy_captcha_id'] = $captcha['id'];
        return $captcha['question'];
    }

    public static function captchaId(): string {
        return (string) ($_SESSION['legacy_captcha_id'] ?? '');
    }

    public static function verifyCaptcha($answer, ?string $id = null, ?string $context = null): bool {
        self::cleanupCaptchas();
        $id = trim((string) ($id ?: ($_POST['captcha_id'] ?? ($_SESSION['legacy_captcha_id'] ?? ''))));
        if ($id === '' || empty($_SESSION['captchas'][$id])) return false;

        $captcha = $_SESSION['captchas'][$id];
        unset($_SESSION['captchas'][$id]);
        if (hash_equals((string) ($_SESSION['legacy_captcha_id'] ?? ''), $id)) unset($_SESSION['legacy_captcha_id']);

        if (($captcha['expires'] ?? 0) < time()) return false;
        if ($context !== null && !hash_equals((string) ($captcha['context'] ?? ''), $context)) return false;
        return is_numeric($answer) && (int) $answer === (int) ($captcha['answer'] ?? -999);
    }

    private static function cleanupCaptchas(): void {
        if (!isset($_SESSION['captchas']) || !is_array($_SESSION['captchas'])) $_SESSION['captchas'] = [];
        foreach ($_SESSION['captchas'] as $id => $captcha) {
            if (($captcha['expires'] ?? 0) < time()) unset($_SESSION['captchas'][$id]);
        }
        if (count($_SESSION['captchas']) > 20) {
            $_SESSION['captchas'] = array_slice($_SESSION['captchas'], -20, null, true);
        }
    }

    /** Rate limit per-session untuk fallback. */
    public static function rateLimit(string $key, int $max = 5, int $windowSeconds = 300): bool {
        $now = time();
        $bucketKey = 'rl_' . hash('sha256', $key);
        $bucket = $_SESSION[$bucketKey] ?? ['start' => $now, 'count' => 0];
        if ($now - (int) $bucket['start'] >= $windowSeconds) $bucket = ['start' => $now, 'count' => 0];
        $bucket['count']++;
        $_SESSION[$bucketKey] = $bucket;
        return $bucket['count'] <= $max;
    }

    /** Rate limit persisten berbasis endpoint + IP + discriminator opsional. */
    public static function rateLimitPersistent(string $key, int $max = 10, int $windowSeconds = 60, string $discriminator = ''): bool {
        $max = max(1, min(1000, $max));
        $windowSeconds = max(10, min(86400, $windowSeconds));
        $ip = Visitor::ip();
        $bucketKey = hash('sha256', $key . '|' . $ip . '|' . mb_substr($discriminator, 0, 120));
        $windowStart = date('Y-m-d H:i:s', intdiv(time(), $windowSeconds) * $windowSeconds);

        try {
            self::ensureRateLimitSchema();
            db_exec(
                "INSERT INTO request_rate_limits (bucket_key, endpoint, ip_address, window_start, hits, updated_at)
                 VALUES (?, ?, ?, ?, 1, NOW())
                 ON DUPLICATE KEY UPDATE
                    hits = IF(window_start = VALUES(window_start), hits + 1, 1),
                    window_start = VALUES(window_start),
                    updated_at = NOW()",
                [$bucketKey, mb_substr($key, 0, 100), $ip, $windowStart]
            );
            $hits = (int) db_val("SELECT hits FROM request_rate_limits WHERE bucket_key = ? LIMIT 1", [$bucketKey]);
            if (random_int(1, 100) === 1) {
                db_exec("DELETE FROM request_rate_limits WHERE updated_at < NOW() - INTERVAL 2 DAY");
            }
            return $hits <= $max;
        } catch (Throwable $e) {
            error_log('Persistent rate limit fallback: ' . $e->getMessage());
            return self::rateLimit($key . '|' . $discriminator, $max, $windowSeconds);
        }
    }

    private static function ensureRateLimitSchema(): void {
        static $ready = false;
        if ($ready) return;
        db_exec("CREATE TABLE IF NOT EXISTS request_rate_limits (
            bucket_key CHAR(64) NOT NULL,
            endpoint VARCHAR(100) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            window_start DATETIME NOT NULL,
            hits INT UNSIGNED NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (bucket_key),
            KEY idx_rate_updated (updated_at),
            KEY idx_rate_endpoint_ip (endpoint, ip_address, window_start)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $ready = true;
    }

    /**
     * Validasi dasar endpoint JSON publik: POST, Content-Type, same-origin,
     * ukuran body, decode JSON, dan rate limit persisten.
     */
    public static function publicJsonRequest(string $endpoint, int $max = 20, int $windowSeconds = 60, int $maxBytes = 32768): array {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            throw new RuntimeException('Metode tidak diizinkan.');
        }
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (!str_contains($contentType, 'application/json')) {
            http_response_code(415);
            throw new RuntimeException('Format permintaan harus JSON.');
        }

        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '') {
            $originParts = parse_url($origin);
            $baseParts = parse_url(base_url());
            $same = is_array($originParts) && is_array($baseParts)
                && strtolower((string) ($originParts['scheme'] ?? '')) === strtolower((string) ($baseParts['scheme'] ?? ''))
                && strtolower((string) ($originParts['host'] ?? '')) === strtolower((string) ($baseParts['host'] ?? ''))
                && (int) ($originParts['port'] ?? self::defaultPort($originParts['scheme'] ?? '')) === (int) ($baseParts['port'] ?? self::defaultPort($baseParts['scheme'] ?? ''));
            if (!$same) {
                http_response_code(403);
                throw new RuntimeException('Asal permintaan tidak diizinkan.');
            }
        } elseif (strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin')) === 'cross-site') {
            http_response_code(403);
            throw new RuntimeException('Permintaan lintas situs ditolak.');
        }

        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length > $maxBytes) {
            http_response_code(413);
            throw new RuntimeException('Ukuran permintaan terlalu besar.');
        }

        if (!self::rateLimitPersistent('api:' . $endpoint, $max, $windowSeconds)) {
            http_response_code(429);
            throw new RuntimeException('Terlalu banyak permintaan. Coba kembali sebentar lagi.');
        }

        $raw = (string) file_get_contents('php://input');
        if (strlen($raw) > $maxBytes) {
            http_response_code(413);
            throw new RuntimeException('Ukuran permintaan terlalu besar.');
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            http_response_code(400);
            throw new RuntimeException('JSON tidak valid.');
        }
        return $data;
    }

    private static function defaultPort(string $scheme): int {
        return strtolower($scheme) === 'https' ? 443 : 80;
    }

    public static function validNik($nik): bool {
        return (bool) preg_match('/^\d{16}$/', (string) $nik);
    }

    public static function validKk($kk): bool {
        return (bool) preg_match('/^\d{16}$/', (string) $kk);
    }
}
