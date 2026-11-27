<?php

/** Web Push (W3C) murni PHP: VAPID ES256 + push tanpa payload (SW mengambil isi dari /api/push-latest). */
class WebPush {

    /** Kunci publik VAPID (base64url, 65-byte uncompressed). Dibuat otomatis sekali lalu disimpan di site_settings. */
    public static function publicKey(): string {
        $pub = setting('vapid_public');
        if ($pub !== '' && setting('vapid_private') !== '') return $pub;
        return self::generateKeys();
    }

    private static function generateKeys(): string {
        try {
            $res = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
            if (!$res || !openssl_pkey_export($res, $privPem)) return '';
            $det = openssl_pkey_get_details($res);
            if (empty($det['ec']['x']) || empty($det['ec']['y'])) return '';
            $raw = "\x04" . str_pad($det['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($det['ec']['y'], 32, "\0", STR_PAD_LEFT);
            $pub = self::b64url($raw);
            set_setting('vapid_public', $pub);
            set_setting('vapid_private', $privPem);
            return $pub;
        } catch (Throwable $e) {
            error_log('WebPush keygen: ' . $e->getMessage());
            return '';
        }
    }

    public static function b64url(string $d): string {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }

    /** JWT VAPID ES256 per push-service origin (di-cache selama satu proses). */
    private static function jwt(string $aud): string {
        static $cache = [];
        if (isset($cache[$aud])) return $cache[$aud];
        $priv = openssl_pkey_get_private(setting('vapid_private'));
        if (!$priv) return '';
        $head = self::b64url(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $sub = 'mailto:' . (setting('email') !== '' ? setting('email') : 'admin@desa.id');
        $payload = self::b64url(json_encode(['aud' => $aud, 'exp' => time() + 43200, 'sub' => $sub]));
        $input = $head . '.' . $payload;
        if (!openssl_sign($input, $der, $priv, OPENSSL_ALGO_SHA256)) return '';
        return $cache[$aud] = $input . '.' . self::b64url(self::derToRaw($der));
    }

    /** Konversi tanda tangan ECDSA DER -> raw R||S 64 byte (wajib untuk JWS). */
    private static function derToRaw(string $der): string {
        $pos = 1;
        $lenByte = ord($der[$pos++]);
        if ($lenByte & 0x80) $pos += ($lenByte & 0x7f);
        $r = self::readDerInt($der, $pos);
        $s = self::readDerInt($der, $pos);
        return $r . $s;
    }

    private static function readDerInt(string $der, int &$pos): string {
        $pos++;
        $len = ord($der[$pos++]);
        $int = substr($der, $pos, $len);
        $pos += $len;
        $int = ltrim($int, "\0");
        return str_pad($int, 32, "\0", STR_PAD_LEFT);
    }

    /** Kirim push kosong ke satu endpoint. Return kode HTTP push service. */
    public static function push(string $endpoint): int {
        $p = parse_url($endpoint);
        if (empty($p['scheme']) || empty($p['host'])) return 0;
        $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
        $jwt = self::jwt($aud);
        if ($jwt === '') return 0;
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                'Authorization: vapid t=' . $jwt . ',k=' . setting('vapid_public'),
                'TTL: 86400',
                'Content-Length: 0',
            ],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 6,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    /** Kirim ke semua subscriber; endpoint mati (404/410) dihapus. Return [terkirim, gagal]. */
    public static function sendToAll(): array {
        if (self::publicKey() === '') return [0, 0];
        $sent = 0; $fail = 0;
        foreach (db_all("SELECT id, endpoint FROM push_subscriptions") as $s) {
            $code = self::push($s['endpoint']);
            if ($code >= 200 && $code < 300) {
                $sent++;
            } else {
                $fail++;
                if (in_array($code, [404, 410], true)) db_exec("DELETE FROM push_subscriptions WHERE id = ?", [$s['id']]);
            }
        }
        return [$sent, $fail];
    }
}
