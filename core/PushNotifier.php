<?php

/**
 * Layanan terpusat untuk menyimpan dan mengirim Web Push.
 * Push dikirim tanpa payload; service worker mengambil notifikasi terbaru dari API.
 */
class PushNotifier {

    public static function send(string $title, string $body, string $targetUrl = '', ?int $userId = null): array {
        $title = trim(mb_substr($title, 0, 150));
        $body = trim(mb_substr(strip_tags($body), 0, 300));
        if ($title === '' || $body === '') {
            return ['ok' => false, 'sent' => 0, 'fail' => 0, 'error' => 'Judul atau isi notifikasi kosong.'];
        }
        if ($targetUrl !== '' && !str_starts_with($targetUrl, 'http://') && !str_starts_with($targetUrl, 'https://')) {
            $targetUrl = url(ltrim($targetUrl, '/'));
        }

        $id = db_insert('push_notifications', [
            'title' => $title,
            'body' => $body,
            'url' => $targetUrl ?: null,
            'created_by' => $userId ?: (Auth::user()['id'] ?? null),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        [$sent, $fail] = WebPush::sendToAll();
        db_exec("UPDATE push_notifications SET sent_count = ?, fail_count = ? WHERE id = ?", [$sent, $fail, $id]);
        return ['ok' => true, 'id' => $id, 'sent' => $sent, 'fail' => $fail, 'error' => ''];
    }

    /** Kirim notifikasi berdasarkan konfigurasi modul CRUD. */
    public static function fromCrud(string $module, array $cfg, array $row, int $id): array {
        if (array_key_exists('is_active', $row) && !(int) $row['is_active']) {
            return ['ok' => false, 'sent' => 0, 'fail' => 0, 'error' => 'konten belum diaktifkan untuk publik'];
        }
        if (array_key_exists('is_public', $row) && !(int) $row['is_public']) {
            return ['ok' => false, 'sent' => 0, 'fail' => 0, 'error' => 'konten belum ditandai sebagai publik'];
        }
        if (($row['status'] ?? '') === 'dibatalkan') {
            return ['ok' => false, 'sent' => 0, 'fail' => 0, 'error' => 'agenda berstatus dibatalkan'];
        }
        $pc = $cfg['push'] ?? [];
        $titleField = $pc['title_field'] ?? 'title';
        $bodyField = $pc['body_field'] ?? 'description';
        $prefix = trim((string) ($pc['prefix'] ?? $cfg['title'] ?? 'Informasi'));
        $contentTitle = trim((string) ($row[$titleField] ?? ''));
        $title = $prefix . ($contentTitle !== '' ? ': ' . $contentTitle : '');

        $body = trim(strip_tags((string) ($row[$bodyField] ?? '')));
        if ($body === '' && $module === 'agenda') {
            $parts = [];
            if (!empty($row['event_date'])) $parts[] = tgl_id((string) $row['event_date']);
            if (!empty($row['start_time'])) $parts[] = 'pukul ' . substr((string) $row['start_time'], 0, 5);
            if (!empty($row['location'])) $parts[] = 'di ' . $row['location'];
            $body = implode(' ', $parts);
        }
        if ($body === '') $body = ($pc['fallback_body'] ?? 'Ada informasi terbaru dari pemerintah desa.');

        $route = (string) ($pc['route'] ?? '');
        if (!empty($pc['route_field']) && !empty($row[$pc['route_field']])) {
            $route = rtrim($route, '/') . '/' . rawurlencode((string) $row[$pc['route_field']]);
        } elseif (!empty($pc['append_id'])) {
            $route = rtrim($route, '/') . '/' . $id;
        }
        return self::send($title, $body, $route, Auth::user()['id'] ?? null);
    }

    public static function flashResult(array $result): void {
        if (empty($result['ok'])) {
            flash('warning', 'Data tersimpan, tetapi push tidak dibuat: ' . e($result['error'] ?? 'kesalahan tidak diketahui'));
            return;
        }
        $sent = (int) ($result['sent'] ?? 0);
        $fail = (int) ($result['fail'] ?? 0);
        if ($sent > 0) {
            flash('success', "Data berhasil disimpan dan push terkirim ke <b>$sent</b> perangkat." . ($fail ? " ($fail gagal/kedaluwarsa)" : ''));
        } elseif ($fail > 0) {
            flash('warning', "Data berhasil disimpan, tetapi push gagal pada $fail perangkat.");
        } else {
            flash('info', 'Data berhasil disimpan. Push dibuat, namun belum ada perangkat yang berlangganan.');
        }
    }
}
