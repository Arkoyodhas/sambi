<?php

/** Live Chat Admin: perangkat aktif, room, status pesan, balasan cepat, mute/ban/tutup. */
class ChatController extends Controller {

    public array $actions = [
        'index', 'poll', 'messages', 'send', 'mute', 'unmute', 'close', 'ban',
        'picker', 'typing', 'arsip', 'reopen', 'unban', 'visitors', 'start_device',
        'settings_save', 'quick_list', 'quick_save', 'quick_delete'
    ];

    public function __construct() {
        Auth::requirePermission('balas_chat');
        LiveChat::ensureSchema();
    }

    public function index(): void {
        $this->adminView('chat', [
            'pageTitle' => 'Live Chat',
            'rooms' => $this->roomList(),
            'quickReplies' => LiveChat::quickReplies(),
            'rateSettings' => LiveChat::rateSettings(),
            'activeVisitorCount' => LiveChat::activeVisitorCount(),
        ]);
    }

    private function roomList(): array {
        return db_all("SELECT * FROM chat_rooms
                       WHERE status <> 'closed'
                       ORDER BY (unread_admin > 0) DESC, last_message_at DESC
                       LIMIT 100");
    }

    /** JSON daftar room, badge pesan, dan jumlah perangkat online. */
    public function poll(): void {
        header('Cache-Control: no-store');
        // Saat panel live chat admin aktif, pesan pengunjung telah sampai ke
        // perangkat admin. Status dibaca tetap baru diberikan ketika room dibuka.
        db_exec("UPDATE chat_messages
                 SET delivered_at = COALESCE(delivered_at, NOW())
                 WHERE sender = 'user' AND delivered_at IS NULL");
        $this->json([
            'ok' => true,
            'total_unread' => (int) db_val("SELECT COALESCE(SUM(unread_admin),0)
                                            FROM chat_rooms WHERE status <> 'closed'"),
            'active_visitors' => LiveChat::activeVisitorCount(),
            'rooms' => array_map(static fn(array $r): array => [
                'id' => $r['id'],
                'name' => $r['visitor_name'],
                'unread' => (int) $r['unread_admin'],
                'status' => $r['status'],
                'last' => $r['last_message'],
                'last_at' => $r['last_message_at'] ? date('d/m H:i', strtotime($r['last_message_at'])) : '',
                'ip' => $r['ip_address'],
                'device' => $r['device'],
                'browser' => $r['browser'],
                'os' => $r['os'],
                'loc' => trim((string) (($r['city'] ?? '') . ' ' . ($r['country'] ?? ''))),
                'admin_started' => !empty($r['initiated_at']),
            ], $this->roomList()),
        ]);
    }

    /** Perangkat yang heartbeat-nya masih aktif; IP hanya metadata. */
    public function visitors(): void {
        header('Cache-Control: no-store');
        $rows = LiveChat::activeVisitors();
        $this->json([
            'ok' => true,
            'count' => count($rows),
            'visitors' => array_map(static fn(array $v): array => [
                'id' => (int) $v['id'],
                'hint' => strtoupper(substr((string) $v['token_hint'], 0, 6)),
                'ip' => $v['ip_address'],
                'device' => $v['device'],
                'browser' => $v['browser'],
                'os' => $v['os'],
                'path' => $v['current_path'] ?: '/',
                'title' => $v['current_title'] ?: 'Halaman website',
                'room_id' => $v['room_id'],
                'room_status' => $v['room_status'],
                'name' => $v['visitor_name'],
                'pending' => (bool) $v['pending_invite'],
                'idle_seconds' => max(0, (int) $v['idle_seconds']),
                'last_seen' => date('H:i:s', strtotime($v['last_seen_at'])),
            ], $rows),
        ]);
    }

    /** Admin memulai chat sebelum pengunjung membuka widget chat. */
    public function start_device(): void {
        verify_csrf();
        $deviceId = (int) ($_POST['device_id'] ?? 0);
        $body = $_POST['body'] ?? '';
        $adminId = isset(Auth::user()['id']) ? (int) Auth::user()['id'] : null;
        $result = LiveChat::startFromDevice($deviceId, $body, $adminId);
        $this->json($result);
    }

    /** Pesan satu room + tandai pesan pengunjung telah dibaca admin. */
    public function messages(): void {
        header('Cache-Control: no-store');
        $roomId = (string) ($_GET['room'] ?? '');
        $room = db_row("SELECT cr.*, u.name AS initiator_name
                        FROM chat_rooms cr
                        LEFT JOIN users u ON u.id = cr.initiated_by_admin_id
                        WHERE cr.id = ? LIMIT 1", [$roomId]);
        if (!$room) { $this->json(['ok' => false]); }

        LiveChat::markRead($roomId, 'user');
        $typing = db_row("SELECT (typing_user > NOW() - INTERVAL 5 SECOND) AS ut
                          FROM chat_rooms WHERE id = ?", [$roomId]);

        $this->json([
            'ok' => true,
            'room' => [
                'id' => $room['id'],
                'name' => $room['visitor_name'],
                'status' => $room['status'],
                'ip' => $room['ip_address'],
                'device' => $room['device'],
                'browser' => $room['browser'],
                'os' => $room['os'],
                'ua' => $room['user_agent'],
                'loc' => trim((string) (($room['city'] ?? '') . ' ' . ($room['country'] ?? ''))) ?: 'Tidak diketahui',
                'created' => date('d/m/Y H:i', strtotime($room['created_at'])),
                'admin_started' => !empty($room['initiated_at']),
                'initiated_by' => $room['initiator_name'] ?: 'Admin',
                'initiated_at' => !empty($room['initiated_at']) ? date('d/m/Y H:i', strtotime($room['initiated_at'])) : '',
            ],
            'user_typing' => (bool) ($typing['ut'] ?? 0),
            'banned' => (bool) db_val("SELECT 1 FROM blocked_ips WHERE ip = ? LIMIT 1", [$room['ip_address']]),
            'messages' => LiveChat::messages($roomId, 300),
        ]);
    }

    public function send(): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        $adminId = isset(Auth::user()['id']) ? (int) Auth::user()['id'] : null;
        $this->json(LiveChat::sendMessage($roomId, 'admin', $_POST['body'] ?? '', $adminId));
    }

    public function typing(): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        if ($roomId !== '') {
            db_exec("UPDATE chat_rooms SET typing_admin = NOW() WHERE id = ?", [$roomId]);
        }
        $this->json(['ok' => true]);
    }

    /** Pengaturan rate limit bisa diubah admin tanpa menyentuh config. */
    public function settings_save(): void {
        verify_csrf();
        $limit = max(1, min(200, (int) ($_POST['limit'] ?? 20)));
        $minutes = max(1, min(60, (int) ($_POST['minutes'] ?? 1)));
        set_setting('livechat_message_limit', (string) $limit);
        set_setting('livechat_rate_minutes', (string) $minutes);
        log_activity('edit', 'livechat', "Batas chat diubah menjadi {$limit} pesan per {$minutes} menit");
        $this->json(['ok' => true, 'limit' => $limit, 'minutes' => $minutes]);
    }

    public function quick_list(): void {
        header('Cache-Control: no-store');
        $this->json(['ok' => true, 'items' => LiveChat::quickReplies()]);
    }

    public function quick_save(): void {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $title = mb_substr(trim(strip_tags((string) ($_POST['title'] ?? ''))), 0, 80);
        $body = LiveChat::cleanMessage($_POST['body'] ?? '', 1000);
        $sort = max(0, min(9999, (int) ($_POST['sort_order'] ?? 0)));
        if ($title === '' || $body === '') {
            $this->json(['ok' => false, 'error' => 'Judul dan isi balasan wajib diisi.']);
        }

        if ($id > 0) {
            $exists = db_row("SELECT id FROM chat_quick_replies WHERE id = ?", [$id]);
            if (!$exists) $this->json(['ok' => false, 'error' => 'Balasan cepat tidak ditemukan.']);
            db_update('chat_quick_replies', [
                'title' => $title,
                'body' => $body,
                'sort_order' => $sort,
                'is_active' => 1,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);
            log_activity('edit', 'livechat', 'Ubah balasan cepat #' . $id);
        } else {
            $id = db_insert('chat_quick_replies', [
                'title' => $title,
                'body' => $body,
                'is_active' => 1,
                'sort_order' => $sort,
                'created_by' => Auth::user()['id'] ?? null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            log_activity('tambah', 'livechat', 'Tambah balasan cepat #' . $id);
        }
        $this->json(['ok' => true, 'id' => $id]);
    }

    public function quick_delete(): void {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            db_exec("DELETE FROM chat_quick_replies WHERE id = ?", [$id]);
            log_activity('hapus', 'livechat', 'Hapus balasan cepat #' . $id);
        }
        $this->json(['ok' => true]);
    }

    /** Daftar konten untuk disisipkan admin ke chat. */
    public function picker(): void {
        header('Cache-Control: no-store');
        $type = (string) ($_GET['type'] ?? 'berita');
        $items = [];
        if ($type === 'berita') {
            foreach (db_all("SELECT title, slug FROM news WHERE status='published' AND deleted_at IS NULL AND published_at <= NOW() ORDER BY published_at DESC LIMIT 8") as $r) {
                $items[] = ['title' => $r['title'], 'url' => url('berita/' . $r['slug'])];
            }
        } elseif ($type === 'agenda') {
            foreach (db_all("SELECT title, event_date FROM agendas WHERE deleted_at IS NULL AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 8") as $r) {
                $items[] = ['title' => $r['title'] . ' (' . tgl_id($r['event_date']) . ')', 'url' => url('agenda')];
            }
        } elseif ($type === 'pengumuman') {
            foreach (db_all("SELECT id, title FROM announcements WHERE is_active=1 AND deleted_at IS NULL ORDER BY id DESC LIMIT 8") as $r) {
                $items[] = ['title' => $r['title'], 'url' => url('pengumuman/' . $r['id'])];
            }
        } elseif ($type === 'layanan') {
            foreach (db_all("SELECT name, slug FROM services WHERE is_active=1 AND deleted_at IS NULL ORDER BY name ASC LIMIT 12") as $r) {
                $items[] = ['title' => $r['name'], 'url' => url('layanan/' . $r['slug'])];
            }
        }
        $this->json(['ok' => true, 'items' => $items]);
    }

    /** Daftar room yang sudah ditutup. */
    public function arsip(): void {
        header('Cache-Control: no-store');
        $this->json(['ok' => true, 'rooms' => array_map(static fn(array $r): array => [
            'id' => $r['id'],
            'name' => $r['visitor_name'],
            'unread' => 0,
            'status' => 'closed',
            'last' => $r['last_message'],
            'last_at' => $r['last_message_at'] ? date('d/m H:i', strtotime($r['last_message_at'])) : '',
            'ip' => $r['ip_address'],
            'device' => $r['device'],
            'browser' => $r['browser'],
            'os' => $r['os'],
            'loc' => trim((string) (($r['city'] ?? '') . ' ' . ($r['country'] ?? ''))),
        ], db_all("SELECT * FROM chat_rooms WHERE status = 'closed' ORDER BY last_message_at DESC LIMIT 100"))]);
    }

    public function reopen(): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        db_exec("UPDATE chat_rooms SET status = 'open' WHERE id = ?", [$roomId]);
        $this->json(['ok' => true]);
    }

    public function unban(): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        $room = db_row("SELECT ip_address FROM chat_rooms WHERE id = ?", [$roomId]);
        if ($room && $room['ip_address']) {
            db_exec("DELETE FROM blocked_ips WHERE ip = ?", [$room['ip_address']]);
            log_activity('ban', 'livechat', 'Buka blokir IP ' . $room['ip_address']);
        }
        $this->json(['ok' => true]);
    }

    public function mute(): void { $this->setStatus('muted', 'Bisukan'); }
    public function unmute(): void { $this->setStatus('open', 'Aktifkan'); }
    public function close(): void { $this->setStatus('closed', 'Tutup'); }

    private function setStatus(string $status, string $label): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        if (!db_row("SELECT id FROM chat_rooms WHERE id = ?", [$roomId])) {
            $this->json(['ok' => false]);
        }
        db_exec("UPDATE chat_rooms SET status = ? WHERE id = ?", [$status, $roomId]);
        log_activity('chat', 'livechat', $label . ' room chat ' . $roomId);
        $this->json(['ok' => true, 'status' => $status]);
    }

    /** Blokir IP pengunjung + tutup room. */
    public function ban(): void {
        verify_csrf();
        $roomId = (string) ($_POST['room_id'] ?? '');
        $room = db_row("SELECT * FROM chat_rooms WHERE id = ?", [$roomId]);
        if (!$room || !$room['ip_address']) {
            $this->json(['ok' => false, 'error' => 'IP tidak diketahui.']);
        }
        db_exec("INSERT INTO blocked_ips (ip, reason, created_by, created_at) VALUES (?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason)", [
            $room['ip_address'],
            'Diblokir dari Live Chat: ' . $room['visitor_name'],
            Auth::user()['id'] ?? null,
        ]);
        db_exec("UPDATE chat_rooms SET status = 'closed' WHERE id = ?", [$roomId]);
        log_activity('ban', 'livechat', 'Blokir IP ' . $room['ip_address'] . ' (' . $room['visitor_name'] . ')');
        $this->json(['ok' => true]);
    }
}
