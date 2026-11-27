<?php

/**
 * Layanan inti Live Chat berbasis perangkat.
 *
 * Token mentah hanya berada di browser. Database menyimpan HMAC token agar token
 * tidak dapat digunakan kembali bila database terbaca pihak yang tidak berwenang.
 */
class LiveChat {

    private static ?bool $schemaReady = null;

    public static function ensureSchema(): bool {
        if (self::$schemaReady !== null) return self::$schemaReady;
        // Hindari DDL/information_schema pada setiap heartbeat publik. Versi ini
        // baru ditulis setelah seluruh perubahan skema berhasil.
        if (setting('livechat_schema_version', '0') === '2') {
            return self::$schemaReady = true;
        }
        try {
            db_exec("CREATE TABLE IF NOT EXISTS visitor_devices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                token_hash CHAR(64) NOT NULL,
                token_hint CHAR(12) NOT NULL,
                session_key CHAR(64) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                device VARCHAR(20) DEFAULT NULL,
                browser VARCHAR(30) DEFAULT NULL,
                os VARCHAR(30) DEFAULT NULL,
                current_path VARCHAR(190) DEFAULT NULL,
                current_title VARCHAR(190) DEFAULT NULL,
                room_id CHAR(36) DEFAULT NULL,
                pending_invite TINYINT(1) NOT NULL DEFAULT 0,
                first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_visitor_device_token (token_hash),
                KEY idx_visitor_device_online (last_seen_at),
                KEY idx_visitor_device_room (room_id),
                KEY idx_visitor_device_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS chat_admin_initiations (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                room_id CHAR(36) NOT NULL,
                visitor_device_id BIGINT UNSIGNED NOT NULL,
                admin_user_id INT UNSIGNED DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                page_path VARCHAR(190) DEFAULT NULL,
                first_message VARCHAR(500) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_chat_init_room (room_id),
                KEY idx_chat_init_device (visitor_device_id),
                KEY idx_chat_init_admin (admin_user_id),
                KEY idx_chat_init_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS chat_quick_replies (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(80) NOT NULL,
                body VARCHAR(1000) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_by INT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_quick_active (is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            self::addColumn('chat_rooms', 'visitor_device_id', "BIGINT UNSIGNED DEFAULT NULL AFTER city");
            self::addColumn('chat_rooms', 'initiated_by_admin_id', "INT UNSIGNED DEFAULT NULL AFTER visitor_device_id");
            self::addColumn('chat_rooms', 'initiated_at', "DATETIME DEFAULT NULL AFTER initiated_by_admin_id");
            self::addColumn('chat_rooms', 'unread_user', "INT NOT NULL DEFAULT 0 AFTER unread_admin");

            self::addColumn('chat_messages', 'sender_user_id', "INT UNSIGNED DEFAULT NULL AFTER sender");
            self::addColumn('chat_messages', 'delivered_at', "DATETIME DEFAULT NULL AFTER body");
            self::addColumn('chat_messages', 'read_at', "DATETIME DEFAULT NULL AFTER delivered_at");

            self::addIndex('chat_rooms', 'idx_room_device', 'visitor_device_id');
            self::addIndex('chat_messages', 'idx_message_status', 'room_id, sender, read_at');

            db_exec("INSERT INTO site_settings (setting_key, setting_value)
                     VALUES ('livechat_message_limit','20')
                     ON DUPLICATE KEY UPDATE setting_key = setting_key");
            db_exec("INSERT INTO site_settings (setting_key, setting_value)
                     VALUES ('livechat_rate_minutes','1')
                     ON DUPLICATE KEY UPDATE setting_key = setting_key");

            if (!(bool) db_val("SELECT 1 FROM chat_quick_replies LIMIT 1")) {
                $adminId = Auth::user()['id'] ?? null;
                db_insert('chat_quick_replies', [
                    'title' => 'Tawarkan bantuan',
                    'body' => 'Ada yang bisa kami bantu?',
                    'is_active' => 1,
                    'sort_order' => 10,
                    'created_by' => $adminId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                db_insert('chat_quick_replies', [
                    'title' => 'Lengkapi data',
                    'body' => 'Silakan lengkapi data berikut agar kami dapat membantu prosesnya.',
                    'is_active' => 1,
                    'sort_order' => 20,
                    'created_by' => $adminId,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }

            set_setting('livechat_schema_version', '2');
            return self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('LiveChat schema: ' . $e->getMessage());
            return self::$schemaReady = false;
        }
    }

    private static function addColumn(string $table, string $column, string $definition): void {
        $exists = (bool) db_val(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
            [$table, $column]
        );
        if (!$exists) db_exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }

    private static function addIndex(string $table, string $index, string $columns): void {
        $exists = (bool) db_val(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$table, $index]
        );
        if (!$exists) db_exec("ALTER TABLE `$table` ADD INDEX `$index` ($columns)");
    }

    public static function normalizeToken($value): ?string {
        $token = strtolower(trim((string) $value));
        return preg_match('/^[a-f0-9]{64}$/', $token) ? $token : null;
    }

    private static function tokenHash(string $token): string {
        return hash_hmac('sha256', $token, (string) cfg('app_key', 'desa-app'));
    }

    private static function sessionKey(): string {
        return hash_hmac('sha256', session_id() ?: 'no-session', (string) cfg('app_key', 'desa-app'));
    }

    public static function cleanMessage($value, int $max = 2000): string {
        $body = str_replace(["\r\n", "\r"], "\n", (string) $value);
        // Hilangkan karakter kontrol dan karakter tak terlihat yang sering dipakai menyamarkan payload.
        $body = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $body) ?? '';
        $body = preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', '', $body) ?? '';
        $body = trim($body);
        return mb_substr($body, 0, $max);
    }

    public static function heartbeat($tokenValue, $pathValue, $titleValue, $roomIdValue = null): ?array {
        if (!self::ensureSchema()) return null;
        $token = self::normalizeToken($tokenValue);
        if ($token === null) return null;

        $hash = self::tokenHash($token);
        $existingDevice = db_row("SELECT id, room_id FROM visitor_devices WHERE token_hash = ? LIMIT 1", [$hash]);
        $ip = Visitor::ip();
        $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        [$device, $browser] = Visitor::parseUa($ua);
        $os = Visitor::parseOs($ua);
        $path = mb_substr((string) $pathValue, 0, 190);
        $title = mb_substr(strip_tags((string) $titleValue), 0, 190);
        $roomId = trim((string) $roomIdValue);
        if ($roomId !== '') {
            $validRoom = db_row("SELECT id, visitor_device_id
                                 FROM chat_rooms
                                 WHERE id = ? AND status <> 'closed' LIMIT 1", [$roomId]);
            $sameStoredRoom = $existingDevice
                && hash_equals((string) ($existingDevice['room_id'] ?? ''), $roomId);
            $boundToDevice = $validRoom && $existingDevice
                && (int) ($validRoom['visitor_device_id'] ?? 0) === (int) $existingDevice['id'];
            $legacySessionOwner = $validRoom
                && hash_equals((string) ($_SESSION['chat_room'] ?? ''), $roomId)
                && (empty($validRoom['visitor_device_id'])
                    || ($existingDevice && (int) $validRoom['visitor_device_id'] === (int) $existingDevice['id']));
            // Client tidak boleh menautkan tokennya ke room acak hanya dengan
            // mengetahui ID room. Room harus sudah tersimpan pada device,
            // terikat di server, atau dimiliki session lama yang sama.
            if (!$validRoom || (!$sameStoredRoom && !$boundToDevice && !$legacySessionOwner)) $roomId = '';
        }

        db_exec("INSERT INTO visitor_devices
                    (token_hash, token_hint, session_key, ip_address, user_agent, device, browser, os,
                     current_path, current_title, room_id, pending_invite, first_seen_at, last_seen_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    session_key = VALUES(session_key),
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent),
                    device = VALUES(device),
                    browser = VALUES(browser),
                    os = VALUES(os),
                    current_path = VALUES(current_path),
                    current_title = VALUES(current_title),
                    room_id = CASE WHEN VALUES(room_id) IS NOT NULL AND VALUES(room_id) <> '' THEN VALUES(room_id) ELSE room_id END,
                    last_seen_at = NOW(),
                    updated_at = NOW()", [
            $hash, substr($hash, 0, 12), self::sessionKey(), $ip, $ua, $device, $browser, $os,
            $path ?: '/', $title ?: null, $roomId ?: null,
        ]);

        return db_row("SELECT * FROM visitor_devices WHERE token_hash = ? LIMIT 1", [$hash]);
    }


    public static function deviceForToken($tokenValue): ?array {
        if (!self::ensureSchema()) return null;
        $token = self::normalizeToken($tokenValue);
        if ($token === null) return null;
        return db_row("SELECT * FROM visitor_devices WHERE token_hash = ? LIMIT 1", [self::tokenHash($token)]);
    }

    public static function ownsRoom($tokenValue, string $roomId): bool {
        $device = self::deviceForToken($tokenValue);
        return $device && (string) ($device['room_id'] ?? '') === $roomId;
    }

    public static function bindRoom($tokenValue, string $roomId): void {
        if (!self::ensureSchema()) return;
        $token = self::normalizeToken($tokenValue);
        if ($token === null || $roomId === '') return;
        db_exec("UPDATE visitor_devices
                 SET room_id = ?, pending_invite = 0, session_key = ?, last_seen_at = NOW(), updated_at = NOW()
                 WHERE token_hash = ?", [$roomId, self::sessionKey(), self::tokenHash($token)]);
        db_exec("UPDATE chat_rooms
                 SET visitor_device_id = (
                    SELECT id FROM visitor_devices WHERE token_hash = ? LIMIT 1
                 )
                 WHERE id = ?", [self::tokenHash($token), $roomId]);
    }

    public static function activeVisitors(int $seconds = 45): array {
        if (!self::ensureSchema()) return [];
        $seconds = max(15, min(300, $seconds));
        return db_all("SELECT vd.id, vd.token_hint, vd.ip_address, vd.device, vd.browser, vd.os,
                              vd.current_path, vd.current_title, vd.room_id, vd.pending_invite,
                              vd.first_seen_at, vd.last_seen_at,
                              TIMESTAMPDIFF(SECOND, vd.last_seen_at, NOW()) AS idle_seconds,
                              cr.status AS room_status, cr.visitor_name
                       FROM visitor_devices vd
                       LEFT JOIN chat_rooms cr ON cr.id = vd.room_id
                       WHERE vd.last_seen_at >= DATE_SUB(NOW(), INTERVAL {$seconds} SECOND)
                       ORDER BY vd.last_seen_at DESC
                       LIMIT 200");
    }

    public static function activeVisitorCount(int $seconds = 45): int {
        if (!self::ensureSchema()) return 0;
        $seconds = max(15, min(300, $seconds));
        return (int) db_val("SELECT COUNT(*) FROM visitor_devices
                             WHERE last_seen_at >= DATE_SUB(NOW(), INTERVAL {$seconds} SECOND)");
    }

    private static function newRoomId(): string {
        return sprintf('%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
    }

    public static function rateSettings(): array {
        return [
            'limit' => max(1, min(200, (int) setting('livechat_message_limit', '20'))),
            'minutes' => max(1, min(60, (int) setting('livechat_rate_minutes', '1'))),
        ];
    }

    private static function enforceRateLimit(string $roomId, string $sender): ?string {
        $settings = self::rateSettings();
        $count = (int) db_val(
            "SELECT COUNT(*) FROM chat_messages
             WHERE room_id = ? AND sender = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL {$settings['minutes']} MINUTE)",
            [$roomId, $sender]
        );
        if ($count >= $settings['limit']) {
            return "Batas {$settings['limit']} pesan per {$settings['minutes']} menit tercapai. Silakan coba kembali setelah jeda.";
        }
        return null;
    }

    public static function sendMessage(string $roomId, string $sender, $bodyValue, ?int $adminUserId = null): array {
        if (!self::ensureSchema()) return ['ok' => false, 'error' => 'Sistem chat belum siap.'];
        if (!in_array($sender, ['admin', 'user'], true)) return ['ok' => false, 'error' => 'Pengirim tidak valid.'];

        $body = self::cleanMessage($bodyValue, $sender === 'admin' ? 2000 : 1000);
        if ($roomId === '' || $body === '') return ['ok' => false, 'error' => 'Pesan tidak boleh kosong.'];

        $room = db_row("SELECT * FROM chat_rooms WHERE id = ? LIMIT 1", [$roomId]);
        if (!$room || $room['status'] === 'closed') return ['ok' => false, 'error' => 'Room chat telah ditutup.'];
        if ($sender === 'user' && $room['status'] === 'muted') return ['ok' => false, 'error' => 'Anda sedang dibisukan oleh admin.'];

        $rateError = self::enforceRateLimit($roomId, $sender);
        if ($rateError !== null) return ['ok' => false, 'error' => $rateError, 'rate_limited' => true];

        $id = db_insert('chat_messages', [
            'room_id' => $roomId,
            'sender' => $sender,
            'sender_user_id' => $sender === 'admin' ? $adminUserId : null,
            'body' => $body,
            'delivered_at' => null,
            'read_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($sender === 'admin') {
            db_exec("UPDATE chat_rooms
                     SET unread_user = unread_user + 1,
                         last_message = ?, last_message_at = NOW()
                     WHERE id = ?", ['Admin: ' . mb_substr($body, 0, 150), $roomId]);
        } else {
            db_exec("UPDATE chat_rooms
                     SET unread_admin = unread_admin + 1,
                         last_message = ?, last_message_at = NOW()
                     WHERE id = ?", [mb_substr($body, 0, 160), $roomId]);
        }

        return ['ok' => true, 'id' => $id, 'status' => 'sent'];
    }

    public static function startFromDevice(int $deviceId, $bodyValue, ?int $adminUserId): array {
        if (!self::ensureSchema()) return ['ok' => false, 'error' => 'Sistem perangkat belum siap.'];
        $device = db_row("SELECT * FROM visitor_devices WHERE id = ? LIMIT 1", [$deviceId]);
        if (!$device) return ['ok' => false, 'error' => 'Perangkat pengunjung tidak ditemukan.'];
        if (strtotime((string) $device['last_seen_at']) < time() - 300) {
            return ['ok' => false, 'error' => 'Perangkat sudah tidak aktif. Muat ulang daftar pengunjung.'];
        }

        $body = self::cleanMessage($bodyValue, 2000);
        if ($body === '') return ['ok' => false, 'error' => 'Pesan tidak boleh kosong.'];

        $room = null;
        if (!empty($device['room_id'])) {
            $room = db_row("SELECT * FROM chat_rooms WHERE id = ? AND status <> 'closed' LIMIT 1", [$device['room_id']]);
        }
        if (!$room) {
            $roomId = self::newRoomId();
            $name = 'Pengunjung #' . strtoupper(substr((string) $device['token_hint'], 0, 6));
            db_exec("INSERT INTO chat_rooms
                        (id, visitor_name, ip_address, user_agent, device, browser, os, visitor_device_id,
                         initiated_by_admin_id, initiated_at, status, unread_admin, unread_user,
                         last_message_at, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'open', 0, 0, NOW(), NOW())", [
                $roomId, $name, $device['ip_address'], $device['user_agent'], $device['device'],
                $device['browser'], $device['os'], $device['id'], $adminUserId,
            ]);
            $room = db_row("SELECT * FROM chat_rooms WHERE id = ?", [$roomId]);
        } else {
            db_exec("UPDATE chat_rooms
                     SET visitor_device_id = ?, initiated_by_admin_id = COALESCE(initiated_by_admin_id, ?),
                         initiated_at = COALESCE(initiated_at, NOW()), status = 'open'
                     WHERE id = ?", [$device['id'], $adminUserId, $room['id']]);
        }

        $sent = self::sendMessage((string) $room['id'], 'admin', $body, $adminUserId);
        if (!$sent['ok']) return $sent;

        db_exec("UPDATE visitor_devices
                 SET room_id = ?, pending_invite = 1, updated_at = NOW()
                 WHERE id = ?", [$room['id'], $device['id']]);

        db_insert('chat_admin_initiations', [
            'room_id' => $room['id'],
            'visitor_device_id' => $device['id'],
            'admin_user_id' => $adminUserId,
            'ip_address' => $device['ip_address'],
            'page_path' => $device['current_path'],
            'first_message' => mb_substr($body, 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity(
            'chat_mulai',
            'livechat',
            'Admin memulai chat ke perangkat #' . $device['id'] .
            ' pada ' . ($device['current_path'] ?: '/') . ' (IP ' . $device['ip_address'] . ')'
        );

        return ['ok' => true, 'room_id' => $room['id'], 'message_id' => $sent['id']];
    }

    public static function claimInvite($tokenValue): ?array {
        if (!self::ensureSchema()) return null;
        $token = self::normalizeToken($tokenValue);
        if ($token === null) return null;

        $device = db_row("SELECT * FROM visitor_devices
                          WHERE token_hash = ? AND pending_invite = 1
                          ORDER BY updated_at DESC LIMIT 1", [self::tokenHash($token)]);
        if (!$device || empty($device['room_id'])) return null;

        $room = db_row("SELECT * FROM chat_rooms WHERE id = ? AND status <> 'closed' LIMIT 1", [$device['room_id']]);
        if (!$room) {
            db_exec("UPDATE visitor_devices SET pending_invite = 0, room_id = NULL WHERE id = ?", [$device['id']]);
            return null;
        }

        $_SESSION['chat_room'] = $room['id'];
        db_exec("UPDATE visitor_devices
                 SET pending_invite = 0, session_key = ?, last_seen_at = NOW(), updated_at = NOW()
                 WHERE id = ?", [self::sessionKey(), $device['id']]);

        self::markDelivered((string) $room['id'], 'admin');
        return self::roomPayload($room);
    }

    public static function roomPayload(array $room): array {
        $rows = self::messages((string) $room['id'], 300);
        return [
            'room_id' => $room['id'],
            'name' => $room['visitor_name'],
            'status' => $room['status'],
            'unread_user' => (int) ($room['unread_user'] ?? 0),
            'messages' => $rows,
        ];
    }

    public static function messages(string $roomId, int $limit = 300, int $after = 0): array {
        $limit = max(1, min(500, $limit));
        $where = $after > 0 ? ' AND id > ?' : '';
        $params = $after > 0 ? [$roomId, $after] : [$roomId];
        $rows = db_all("SELECT id, sender, body, delivered_at, read_at, created_at
                        FROM chat_messages
                        WHERE room_id = ?{$where}
                        ORDER BY id ASC LIMIT {$limit}", $params);
        return array_map(static function (array $m): array {
            return [
                'id' => (int) $m['id'],
                'sender' => $m['sender'],
                'body' => $m['body'],
                'status' => !empty($m['read_at']) ? 'read' : (!empty($m['delivered_at']) ? 'delivered' : 'sent'),
                'time' => date('H:i', strtotime($m['created_at'])),
            ];
        }, $rows);
    }

    public static function markDelivered(string $roomId, string $sender): void {
        db_exec("UPDATE chat_messages
                 SET delivered_at = COALESCE(delivered_at, NOW())
                 WHERE room_id = ? AND sender = ? AND delivered_at IS NULL", [$roomId, $sender]);
    }

    public static function markRead(string $roomId, string $sender): void {
        db_exec("UPDATE chat_messages
                 SET delivered_at = COALESCE(delivered_at, NOW()),
                     read_at = COALESCE(read_at, NOW())
                 WHERE room_id = ? AND sender = ? AND read_at IS NULL", [$roomId, $sender]);
        if ($sender === 'admin') {
            db_exec("UPDATE chat_rooms SET unread_user = 0 WHERE id = ?", [$roomId]);
        } else {
            db_exec("UPDATE chat_rooms SET unread_admin = 0 WHERE id = ?", [$roomId]);
        }
    }

    public static function statusMap(string $roomId, string $sender): array {
        $rows = db_all("SELECT id, delivered_at, read_at FROM chat_messages
                        WHERE room_id = ? AND sender = ?
                        ORDER BY id DESC LIMIT 100", [$roomId, $sender]);
        $out = [];
        foreach ($rows as $row) {
            $out[(string) $row['id']] = !empty($row['read_at']) ? 'read' : (!empty($row['delivered_at']) ? 'delivered' : 'sent');
        }
        return $out;
    }

    public static function quickReplies(): array {
        if (!self::ensureSchema()) return [];
        return db_all("SELECT id, title, body, sort_order
                       FROM chat_quick_replies WHERE is_active = 1
                       ORDER BY sort_order ASC, id ASC");
    }
}
