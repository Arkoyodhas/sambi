<?php

/**
 * Penghubung aman antara log upaya screenshot dan Live Chat.
 *
 * IP tetap disimpan sebagai metadata/audit, tetapi target pesan dipastikan lewat
 * kunci sesi server dan token acak browser. Ini mencegah IP bersama (NAT/Wi-Fi)
 * dijadikan satu-satunya identitas penerima.
 */
class ScreenshotChat {

    private static ?bool $schemaReady = null;

    public static function ensureSchema(): bool {
        if (self::$schemaReady !== null) return self::$schemaReady;

        try {
            db_exec("CREATE TABLE IF NOT EXISTS screenshot_chat_targets (
                attempt_id BIGINT UNSIGNED NOT NULL,
                session_key CHAR(64) NOT NULL,
                visitor_token CHAR(64) DEFAULT NULL,
                ip_address VARCHAR(45) NOT NULL,
                room_id CHAR(36) DEFAULT NULL,
                invite_pending TINYINT(1) NOT NULL DEFAULT 0,
                claimed_at DATETIME DEFAULT NULL,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (attempt_id),
                KEY idx_ss_chat_session (session_key, invite_pending),
                KEY idx_ss_chat_token (visitor_token, invite_pending),
                KEY idx_ss_chat_ip (ip_address),
                KEY idx_ss_chat_room (room_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            return self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('ScreenshotChat schema: ' . $e->getMessage());
            return self::$schemaReady = false;
        }
    }

    /** Token browser harus acak, bukan alamat IP atau data pribadi. */
    public static function normalizeVisitorToken($value): ?string {
        $token = trim((string) $value);
        if ($token === '' || !preg_match('/^[a-f0-9]{32,64}$/i', $token)) return null;
        return strtolower($token);
    }

    /** Kunci satu arah dari session id; session id asli tidak pernah disimpan. */
    private static function sessionKey(): string {
        $sid = session_id();
        if ($sid === '') $sid = 'no-session';
        return hash_hmac('sha256', $sid, (string) cfg('app_key', 'desa-app'));
    }

    private static function validRoomForIp(?string $roomId, string $ip): ?array {
        $roomId = trim((string) $roomId);
        if ($roomId === '') return null;

        return db_row("SELECT * FROM chat_rooms
                       WHERE id = ? AND ip_address = ? AND status <> 'closed'
                       LIMIT 1", [$roomId, $ip]);
    }

    /**
     * Simpan target saat sebuah upaya screenshot dicatat.
     * Room dari browser hanya diterima bila benar-benar dimiliki IP request yang sama.
     */
    public static function recordTarget(int $attemptId, string $ip, $visitorToken = null, $roomId = null): void {
        if ($attemptId < 1 || !filter_var($ip, FILTER_VALIDATE_IP) || !self::ensureSchema()) return;

        $token = self::normalizeVisitorToken($visitorToken);
        $room = self::validRoomForIp((string) $roomId, $ip);
        $safeRoomId = $room['id'] ?? null;

        try {
            db_exec("INSERT INTO screenshot_chat_targets
                        (attempt_id, session_key, visitor_token, ip_address, room_id, invite_pending, last_seen_at, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        session_key = VALUES(session_key),
                        visitor_token = COALESCE(VALUES(visitor_token), visitor_token),
                        ip_address = VALUES(ip_address),
                        room_id = COALESCE(VALUES(room_id), room_id),
                        last_seen_at = NOW(),
                        updated_at = NOW()", [
                $attemptId,
                self::sessionKey(),
                $token,
                $ip,
                $safeRoomId,
            ]);
        } catch (Throwable $e) {
            // Pencatatan screenshot utama tidak boleh gagal hanya karena fitur chat.
            error_log('ScreenshotChat recordTarget: ' . $e->getMessage());
        }
    }

    /**
     * Kaitkan room yang dibuat/dilanjutkan pengunjung dengan log screenshot
     * dari sesi/perangkat yang sama.
     */
    public static function bindCurrentRoom(string $ip, string $roomId, $visitorToken = null): void {
        if (!self::ensureSchema()) return;
        $room = self::validRoomForIp($roomId, $ip);
        if (!$room) return;

        $token = self::normalizeVisitorToken($visitorToken);
        $params = [$roomId, $ip, self::sessionKey()];
        $tokenSql = '';
        if ($token !== null) {
            $tokenSql = ' OR visitor_token = ?';
            $params[] = $token;
        }
        // Jangan timpa room undangan admin yang belum sempat diklaim oleh browser.
        $params[] = $roomId;

        try {
            db_exec("UPDATE screenshot_chat_targets
                     SET room_id = ?, invite_pending = 0, last_seen_at = NOW(), updated_at = NOW()
                     WHERE ip_address = ?
                       AND (session_key = ?{$tokenSql})
                       AND (invite_pending = 0 OR room_id IS NULL OR room_id = ?)", $params);
        } catch (Throwable $e) {
            error_log('ScreenshotChat bindCurrentRoom: ' . $e->getMessage());
        }
    }

    private static function newRoomId(): string {
        return sprintf(
            '%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
    }

    /**
     * Admin mengirim pesan dari satu log screenshot.
     *
     * Prioritas:
     * 1. Room yang sudah terikat ke sesi/perangkat target.
     * 2. Tepat satu room aktif dengan IP yang sama (kompatibilitas log lama).
     * 3. Buat room undangan baru bila target sesi/perangkat tersedia.
     */
    public static function sendFromAttempt(int $attemptId, string $body): array {
        $body = trim($body);
        if ($attemptId < 1 || $body === '') {
            return ['ok' => false, 'error' => 'Log atau isi pesan tidak valid.'];
        }
        if (!self::ensureSchema()) {
            return ['ok' => false, 'error' => 'Tabel penghubung live chat belum dapat dibuat. Periksa izin database.'];
        }

        $attempt = db_row("SELECT * FROM screenshot_attempts WHERE id = ? LIMIT 1", [$attemptId]);
        if (!$attempt) return ['ok' => false, 'error' => 'Log screenshot tidak ditemukan.'];

        $ip = (string) $attempt['ip_address'];
        $target = db_row("SELECT * FROM screenshot_chat_targets WHERE attempt_id = ? LIMIT 1", [$attemptId]);
        $room = $target ? self::validRoomForIp((string) ($target['room_id'] ?? ''), $ip) : null;

        // Kompatibilitas log lama: gunakan hanya bila tepat satu room aktif cocok.
        if (!$room) {
            $rooms = db_all("SELECT * FROM chat_rooms
                             WHERE ip_address = ? AND status <> 'closed'
                             ORDER BY last_message_at DESC, created_at DESC
                             LIMIT 2", [$ip]);
            if (count($rooms) === 1) $room = $rooms[0];
        }

        // Tanpa target sesi/perangkat, tidak aman membuat room hanya berdasarkan IP.
        if (!$room && !$target) {
            return [
                'ok' => false,
                'error' => 'Perangkat pada log lama ini belum memiliki target sesi. Pesan dapat dikirim setelah perangkat menghasilkan log screenshot baru atau membuka Live Chat.',
            ];
        }

        if (!$room) {
            $roomId = self::newRoomId();
            $name = 'Pengunjung terdeteksi';
            $device = trim((string) ($attempt['device'] ?? ''));
            if ($device !== '') $name .= ' · ' . mb_substr($device, 0, 20);

            db_exec("INSERT INTO chat_rooms
                        (id, visitor_name, ip_address, user_agent, device, browser, os, country, city,
                         status, unread_admin, last_message, last_message_at, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', 0, NULL, NOW(), NOW())", [
                $roomId,
                mb_substr($name, 0, 60),
                $ip,
                mb_substr((string) ($attempt['user_agent'] ?? ''), 0, 255),
                $attempt['device'] ?? null,
                $attempt['browser'] ?? null,
                $attempt['os'] ?? null,
                $attempt['country'] ?? null,
                $attempt['city'] ?? null,
            ]);
            $room = db_row("SELECT * FROM chat_rooms WHERE id = ?", [$roomId]);
        }

        $message = LiveChat::cleanMessage($body, 1000);
        $sent = LiveChat::sendMessage(
            (string) $room['id'],
            'admin',
            $message,
            isset(Auth::user()['id']) ? (int) Auth::user()['id'] : null
        );
        if (!$sent['ok']) return $sent;

        if ($target) {
            $params = [$room['id'], 1, $ip, $target['session_key']];
            $tokenSql = '';
            if (!empty($target['visitor_token'])) {
                $tokenSql = ' OR visitor_token = ?';
                $params[] = $target['visitor_token'];
            }
            db_exec("UPDATE screenshot_chat_targets
                     SET room_id = ?, invite_pending = ?, updated_at = NOW()
                     WHERE ip_address = ? AND (session_key = ?{$tokenSql})", $params);
        }

        log_activity(
            'chat',
            'screenshot',
            'Admin mengirim pesan live chat untuk log screenshot #' . $attemptId . ' (IP ' . $ip . ')'
        );

        return [
            'ok' => true,
            'room_id' => $room['id'],
            'pending' => $target ? true : false,
        ];
    }

    /**
     * Ambil undangan chat untuk browser yang benar.
     * IP hanya menjadi lapisan tambahan; target utama tetap session key/token acak.
     */
    public static function claimPending(string $ip, $visitorToken = null): ?array {
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !self::ensureSchema()) return null;

        $token = self::normalizeVisitorToken($visitorToken);
        $params = [$ip, self::sessionKey()];
        $tokenSql = '';
        if ($token !== null) {
            $tokenSql = ' OR visitor_token = ?';
            $params[] = $token;
        }

        $target = db_row("SELECT *
                          FROM screenshot_chat_targets
                          WHERE ip_address = ?
                            AND invite_pending = 1
                            AND (session_key = ?{$tokenSql})
                          ORDER BY updated_at DESC
                          LIMIT 1", $params);
        if (!$target || empty($target['room_id'])) return null;

        $room = self::validRoomForIp((string) $target['room_id'], $ip);
        if (!$room) {
            db_exec("UPDATE screenshot_chat_targets
                     SET invite_pending = 0, updated_at = NOW()
                     WHERE room_id = ?", [$target['room_id']]);
            return null;
        }

        $_SESSION['chat_room'] = $room['id'];

        $claimParams = [$room['id'], $ip, $target['session_key']];
        $claimTokenSql = '';
        if (!empty($target['visitor_token'])) {
            $claimTokenSql = ' OR visitor_token = ?';
            $claimParams[] = $target['visitor_token'];
        }
        db_exec("UPDATE screenshot_chat_targets
                 SET invite_pending = 0, claimed_at = NOW(), last_seen_at = NOW(), updated_at = NOW()
                 WHERE room_id = ? AND ip_address = ? AND (session_key = ?{$claimTokenSql})", $claimParams);

        $rows = db_all("SELECT id, sender, body, created_at
                        FROM chat_messages
                        WHERE room_id = ?
                        ORDER BY id ASC
                        LIMIT 300", [$room['id']]);

        return [
            'room_id' => $room['id'],
            'name' => $room['visitor_name'],
            'status' => $room['status'],
            'messages' => array_map(static fn(array $m): array => [
                'id' => (int) $m['id'],
                'sender' => $m['sender'],
                'body' => $m['body'],
                'time' => date('H:i', strtotime($m['created_at'])),
            ], $rows),
        ];
    }

    /** Apakah satu log memiliki target sesi/perangkat yang dapat diundang. */
    public static function targetAvailable(int $attemptId): bool {
        if ($attemptId < 1 || !self::ensureSchema()) return false;
        return (bool) db_val("SELECT 1 FROM screenshot_chat_targets WHERE attempt_id = ? LIMIT 1", [$attemptId]);
    }
}
