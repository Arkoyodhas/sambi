<?php

/** Integrasi WhatsApp Fonnte (api.fonnte.com) — token diatur admin via Pengaturan */
class Fonnte {

    public static function enabled(): bool {
        return setting('fonnte_enabled') === '1' && trim((string) setting('fonnte_token')) !== '';
    }

    /** Normalisasi nomor Indonesia: 0812xxx / +62812xxx → 62812xxx */
    public static function normalize(string $phone): string {
        $d = preg_replace('/\D/', '', $phone);
        if ($d === '') return '';
        $d = ltrim($d, '0');
        if (str_starts_with($d, '62')) return $d;
        return '62' . $d;
    }

    /** Kirim pesan teks. Return ['ok'=>bool, 'error'=>string|null, 'response'=>array|null] */
    public static function send(string $target, string $message): array {
        $target = self::normalize($target);
        if ($target === '' || strlen($target) < 9) return ['ok' => false, 'error' => 'Nomor tujuan tidak valid.', 'response' => null];

        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => ['target' => $target, 'message' => $message, 'countryCode' => '62'],
            CURLOPT_HTTPHEADER => ['Authorization: ' . trim((string) setting('fonnte_token'))],
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 12,
        ]);
        $body = curl_exec($ch);
        $errNo = curl_errno($ch);
        $errMsg = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errNo !== 0) return ['ok' => false, 'error' => 'Koneksi ke Fonnte gagal: ' . $errMsg, 'response' => null];
        $decoded = json_decode((string) $body, true);
        $apiOk = is_array($decoded) && (($decoded['status'] ?? false) === true);
        if ($http < 200 || $http >= 300 || !$apiOk) {
            $reason = is_array($decoded) ? ($decoded['reason'] ?? ($decoded['message'] ?? 'Ditolak API Fonnte')) : 'Respons tidak dikenal (HTTP ' . $http . ')';
            return ['ok' => false, 'error' => is_array($reason) ? implode(', ', $reason) : (string) $reason, 'response' => $decoded];
        }
        return ['ok' => true, 'error' => null, 'response' => $decoded];
    }

    /** Jenis notifikasi admin yang bisa diarahkan ke nomor berbeda (setting wa_route_{jenis}). */
    public const ROUTES = [
        'chat' => 'Live Chat baru',
        'surat' => 'Pengajuan / status surat',
        'aduan' => 'Pengaduan warga',
        'kontak' => 'Pesan kontak',
        'feedback' => 'Survei / feedback warga',
        'keamanan' => 'Peringatan keamanan (screenshot, login)',
    ];

    /** Daftar nomor admin tujuan untuk satu jenis notifikasi (fallback ke nomor admin umum). */
    public static function adminTargets(string $type = ''): array {
        $raw = $type !== '' ? trim((string) setting('wa_route_' . $type)) : '';
        if ($raw === '') $raw = trim((string) setting('wa_admin_number'));
        $out = [];
        foreach (preg_split('/[,;\s]+/', $raw) ?: [] as $n) {
            $n = self::normalize($n);
            if (strlen($n) >= 9 && !in_array($n, $out, true)) $out[] = $n;
        }
        return array_slice($out, 0, 5);
    }

    /** Notifikasi non-blocking: ke pemohon (jika diizinkan) dan ke nomor admin sesuai jenis notifikasi. */
    public static function notify(?string $applicantPhone, string $applicantMsg, string $adminMsg = '', string $type = ''): void {
        if (!self::enabled()) return;
        try {
            if ($applicantPhone && setting('wa_notify_applicant', '1') === '1') {
                $r = self::send($applicantPhone, $applicantMsg);
                log_activity('wa', 'notifikasi', 'WA ke pemohon ' . self::normalize($applicantPhone) . ': ' . ($r['ok'] ? 'terkirim' : 'gagal - ' . $r['error']));
            }
            if ($adminMsg !== '' && setting('wa_notify_admin', '1') === '1') {
                foreach (self::adminTargets($type) as $adminNo) {
                    $r = self::send($adminNo, $adminMsg);
                    log_activity('wa', 'notifikasi', 'WA ' . ($type ?: 'umum') . ' ke admin ' . $adminNo . ': ' . ($r['ok'] ? 'terkirim' : 'gagal - ' . $r['error']));
                }
            }
        } catch (Throwable $e) {
            error_log('Fonnte notify error: ' . $e->getMessage());
        }
    }
}
