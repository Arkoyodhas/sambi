<?php
/** Endpoint JSON publik: experience (musik + event aktif), sitemap, robots. */
class ApiController extends Controller {

    public array $actions = ['experience', 'push_subscribe', 'push_unsubscribe', 'push_latest', 'notifications', 'device_heartbeat', 'chat_start', 'chat_send', 'chat_poll', 'chat_read', 'chat_typing', 'chat_resume', 'chat_invite', 'screenshot_attempt', 'feedback_submit', 'search'];

    /** Simpan respons feedback dari popup pengunjung. */
    public function feedback_submit(): void {
        if (!feature_enabled('feedback')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        if (setting('feedback_enabled', '1') !== '1') { $this->json(['ok' => false, 'error' => 'disabled']); }
        $d = $this->apiBody(__FUNCTION__);
        $answers = $d['answers'] ?? [];
        if (!is_array($answers) || !$answers) { $this->json(['ok' => false, 'error' => 'kosong']); }
        $safeAnswers = [];
        foreach ($answers as $key => $value) {
            if (!preg_match('/^\d+$/', (string) $key) || (!is_scalar($value) && $value !== null)) continue;
            $safeAnswers[(string) $key] = mb_substr(trim((string) $value), 0, 500);
        }
        // Validasi pertanyaan wajib setelah jawaban dinormalisasi.
        $qs = db_all("SELECT id, label, required FROM feedback_questions WHERE is_active = 1");
        foreach ($qs as $q) {
            if ((int) $q['required'] && !isset($safeAnswers[(string) $q['id']])) {
                http_response_code(422);
                $this->json(['ok' => false, 'error' => 'Pertanyaan wajib: ' . $q['label']]);
            }
        }
        db_insert('feedback_responses', [
            'respondent_name' => mb_substr(strip_tags((string) ($d['name'] ?? '')), 0, 120) ?: null,
            'answers' => json_encode($safeAnswers, JSON_UNESCAPED_UNICODE),
            'ip_address' => Visitor::ip(),
            'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        if (trim((string) setting('wa_route_feedback')) !== '') {
            Fonnte::notify(null, '', "⭐ *Survei Warga Baru*\nDari: " . (mb_substr(strip_tags((string) ($d['name'] ?? '')), 0, 60) ?: 'Anonim') . "\n\nLihat hasil di panel admin → Feedback.", 'feedback');
        }
        $reward = ['points' => 0, 'awarded' => false];
        if (!empty($d['visitor_token']) && setting('entertainment_enabled', '1') === '1') {
            try {
                $reward = Entertainment::trackActivity($d['visitor_token'], 'survey', '/feedback');
            } catch (Throwable $e) {
                error_log('Feedback entertainment reward: ' . $e->getMessage());
            }
        }
        $this->json([
            'ok' => true,
            'points' => (int) ($reward['points'] ?? 0),
            'points_awarded' => !empty($reward['awarded']),
        ]);
    }

    /** Log upaya screenshot dari sisi user. Rate-limited per-IP (max 20/menit) untuk cegah spam. */
    public function screenshot_attempt(): void {
        if (!feature_enabled('visitor_audit')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $d = $this->apiBody(__FUNCTION__);
        $method = mb_substr((string) ($d['method'] ?? 'unknown'), 0, 30);
        $path = (string) ($d['path'] ?? '/');
        $pathParts = parse_url($path);
        $path = is_array($pathParts) ? (string) ($pathParts['path'] ?? '/') : '/';
        if ($path === '' || !str_starts_with($path, '/')) $path = '/';
        $path = mb_substr($path, 0, 255);
        $ip = Visitor::ip();
        // Rate limit: max 20 log per IP per menit
        $recent = (int) db_val("SELECT COUNT(*) FROM screenshot_attempts WHERE ip_address = ? AND created_at > NOW() - INTERVAL 1 MINUTE", [$ip]);
        if ($recent >= 20) { $this->json(['ok' => true, 'throttled' => true]); }
        $ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
        [$device, $browser] = Visitor::parseUa($ua);
        $loc = Visitor::cachedLocation($ip);
        $attemptId = db_insert('screenshot_attempts', [
            'ip_address' => $ip, 'path' => $path, 'method' => $method, 'user_agent' => $ua,
            'device' => $device, 'browser' => $browser, 'os' => Visitor::parseOs($ua),
            'country' => $loc['country'] ?? null, 'city' => $loc['city'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        // Simpan target sesi/perangkat secara terpisah. IP tetap metadata, bukan satu-satunya identitas.
        ScreenshotChat::recordTarget(
            $attemptId,
            $ip,
            $d['visitor_token'] ?? null,
            $d['room_id'] ?? null
        );
        // Notifikasi WhatsApp ke admin bila diaktifkan & sudah melewati ambang batas
        if (setting('screenshot_wa_notify', '1') === '1') {
            $threshold = max(1, (int) setting('screenshot_wa_threshold', '1'));
            $lastNotify = (int) db_val("SELECT UNIX_TIMESTAMP(MAX(created_at)) FROM activity_logs WHERE action = 'ss_notified' AND description LIKE ?", ['%' . $ip . '%']);
            $countHour = (int) db_val("SELECT COUNT(*) FROM screenshot_attempts WHERE ip_address = ? AND created_at > NOW() - INTERVAL 1 HOUR", [$ip]);
            // Kirim jika: pertama kali dari IP ini dalam sejam, ATAU sudah lewat 30 menit sejak notif terakhir & sudah lewat threshold
            $shouldNotify = ($countHour === $threshold) || ($lastNotify && time() - $lastNotify > 1800);
            if ($shouldNotify) {
                $loc_str = trim(($loc['city'] ?? '') . ' ' . ($loc['country'] ?? '')) ?: '-';
                Fonnte::notify(null, '', "🚨 *Upaya Screenshot Terdeteksi*\nIP: {$ip}\nLokasi: {$loc_str}\nPerangkat: {$device} · {$browser} · " . Visitor::parseOs($ua) . "\nMetode: {$method}\nHalaman: {$path}\nTotal dalam 1 jam: {$countHour}\n\nLihat detail di panel admin → Upaya Screenshot.", 'keamanan');
                log_activity('ss_notified', 'screenshot', "Notif WA dikirim untuk IP {$ip}");
            }
        }
        $this->json(['ok' => true]);
    }

    /** Heartbeat perangkat publik untuk daftar pengunjung real-time. */
    public function device_heartbeat(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        header('Cache-Control: no-store');
        if (setting('livechat_enabled', '1') !== '1') {
            $this->json(['ok' => false, 'enabled' => false]);
        }
        $d = $this->apiBody(__FUNCTION__);
        $device = LiveChat::heartbeat(
            $d['visitor_token'] ?? null,
            $d['path'] ?? '/',
            $d['title'] ?? '',
            $d['room_id'] ?? null
        );
        if (!$device) {
            $this->json(['ok' => false, 'error' => 'Token perangkat tidak valid.']);
        }
        $this->json([
            'ok' => true,
            'enabled' => true,
            'device_id' => (int) $device['id'],
            'pending_invite' => (bool) $device['pending_invite'],
        ]);
    }

    /** Ambil pesan yang dimulai admin dari log screenshot untuk sesi/perangkat ini. */
    /** Ambil pesan yang dimulai admin untuk perangkat ini. */
    public function chat_invite(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        header('Cache-Control: no-store');
        if (setting('livechat_enabled', '1') !== '1') {
            $this->json(['ok' => false, 'pending' => false, 'error' => 'Live chat nonaktif.']);
        }
        $ip = Visitor::ip();
        if (ip_is_blocked($ip)) {
            http_response_code(403);
            $this->json(['ok' => false, 'pending' => false, 'error' => 'Perangkat Anda diblokir.']);
        }

        $d = $this->apiBody(__FUNCTION__);
        try {
            // Prioritas target perangkat real-time; fallback target screenshot untuk kompatibilitas.
            $invite = LiveChat::claimInvite($d['visitor_token'] ?? null);
            if (!$invite) $invite = ScreenshotChat::claimPending($ip, $d['visitor_token'] ?? null);
        } catch (Throwable $e) {
            error_log('Live chat invite: ' . $e->getMessage());
            $this->json(['ok' => false, 'pending' => false, 'error' => 'Undangan chat belum dapat dimuat.']);
        }
        if (!$invite) $this->json(['ok' => true, 'pending' => false]);
        $this->json(['ok' => true, 'pending' => true] + $invite);
    }

    /** Lanjutkan sesi chat dari localStorage (setelah restart browser). Verifikasi IP cocok agar aman.
     *  Input: {room_id}. Return: {ok, name, status, messages} atau {ok:false} bila room bukan milik IP ini. */
    /** Lanjutkan sesi chat menggunakan token perangkat, bukan IP sebagai identitas utama. */
    public function chat_resume(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $d = $this->apiBody(__FUNCTION__);
        $roomId = (string) ($d['room_id'] ?? '');
        $token = $d['visitor_token'] ?? null;
        if ($roomId === '') { $this->json(['ok' => false]); }

        $room = db_row("SELECT * FROM chat_rooms WHERE id = ? LIMIT 1", [$roomId]);
        if (!$room) { $this->json(['ok' => false, 'error' => 'invalid']); }

        $device = LiveChat::heartbeat($token, $d['path'] ?? '/', $d['title'] ?? '', null);
        $owns = LiveChat::ownsRoom($token, $roomId) || $roomId === ($_SESSION['chat_room'] ?? '');
        // Kompatibilitas room lama: hanya dapat diklaim oleh token baru bila belum
        // terikat perangkat dan IP request masih sama.
        if (!$owns && empty($room['visitor_device_id']) && $room['ip_address'] === Visitor::ip() && $device) {
            $owns = true;
        }
        if (!$owns) $this->json(['ok' => false, 'error' => 'not_owner']);

        $_SESSION['chat_room'] = $room['id'];
        LiveChat::bindRoom($token, $roomId);
        ScreenshotChat::bindCurrentRoom(Visitor::ip(), $roomId, $token);
        LiveChat::markDelivered($roomId, 'admin');

        $this->json(['ok' => true] + LiveChat::roomPayload($room));
    }

    /** Tandai pesan admin dibaca saat popup chat benar-benar terbuka. */
    public function chat_read(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $d = $this->apiBody(__FUNCTION__);
        $roomId = (string) ($d['room_id'] ?? '');
        if ($roomId === '' || $roomId !== ($_SESSION['chat_room'] ?? '')) {
            http_response_code(403);
            $this->json(['ok' => false]);
        }
        LiveChat::markRead($roomId, 'admin');
        $this->json(['ok' => true]);
    }

    public function chat_typing(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $roomId = (string) ($this->apiBody(__FUNCTION__)['room_id'] ?? $_GET['room'] ?? '');
        if ($roomId !== '' && $roomId === ($_SESSION['chat_room'] ?? '')) db_exec("UPDATE chat_rooms SET typing_user = NOW() WHERE id = ?", [$roomId]);
        $this->json(['ok' => true]);
    }

    private function apiBody(string $endpoint): array {
        [$max, $window, $bytes] = match ($endpoint) {
            'feedback_submit' => [5, 3600, 32768],
            'screenshot_attempt' => [20, 60, 8192],
            'device_heartbeat', 'chat_invite', 'chat_read', 'chat_typing' => [60, 60, 8192],
            'chat_resume' => [15, 60, 16384],
            'chat_start' => [6, 600, 16384],
            'chat_send' => [25, 60, 16384],
            'push_subscribe', 'push_unsubscribe' => [8, 300, 16384],
            default => [20, 60, 32768],
        };
        try {
            return Security::publicJsonRequest($endpoint, $max, $window, $bytes);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
        return [];
    }

    /** Mulai/lanjutkan room chat pengunjung. Input: {name}. Return: {room_id, name}. */
    /** Mulai/lanjutkan room chat pengunjung. */
    public function chat_start(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        if (setting('livechat_enabled', '1') !== '1') { $this->json(['ok' => false, 'error' => 'Live chat nonaktif.']); }
        $ip = Visitor::ip();
        if (ip_is_blocked($ip)) { http_response_code(403); $this->json(['ok' => false, 'error' => 'Perangkat Anda diblokir.']); }

        $d = $this->apiBody(__FUNCTION__);
        $token = $d['visitor_token'] ?? null;
        $device = LiveChat::heartbeat($token, $d['path'] ?? '/', $d['title'] ?? '', $d['room_id'] ?? null);
        if (!$device) { $this->json(['ok' => false, 'error' => 'Token perangkat tidak valid. Muat ulang halaman.']); }

        $existing = $_SESSION['chat_room'] ?? '';
        if ($existing) {
            $room = db_row("SELECT id, visitor_name, status, ip_address, visitor_device_id FROM chat_rooms WHERE id = ?", [$existing]);
            $legacyOwner = $room && empty($room['visitor_device_id']) && $room['ip_address'] === $ip;
            if ($room && $room['status'] !== 'closed' && (LiveChat::ownsRoom($token, $existing) || $legacyOwner)) {
                LiveChat::bindRoom($token, $room['id']);
                ScreenshotChat::bindCurrentRoom($ip, $room['id'], $token);
                $this->json(['ok' => true, 'room_id' => $room['id'], 'name' => $room['visitor_name']]);
            }
        }

        $name = trim((string) ($d['name'] ?? ''));
        if (mb_strlen($name) < 2) { $this->json(['ok' => false, 'error' => 'Nama minimal 2 karakter.']); }
        $name = mb_substr(strip_tags($name), 0, 60);

        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        [$deviceType, $browser] = Visitor::parseUa($ua);
        $loc = Visitor::cachedLocation($ip);
        $id = sprintf('%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        db_exec("INSERT INTO chat_rooms
                    (id, visitor_name, ip_address, user_agent, device, browser, os, country, city,
                     visitor_device_id, status, unread_admin, unread_user, created_at, last_message_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?, 'open',0,0,NOW(),NOW())", [
            $id, $name, $ip, $ua, $deviceType, $browser, Visitor::parseOs($ua),
            $loc['country'] ?? null, $loc['city'] ?? null, $device['id'],
        ]);
        $_SESSION['chat_room'] = $id;
        LiveChat::bindRoom($token, $id);
        ScreenshotChat::bindCurrentRoom($ip, $id, $token);
        $this->json(['ok' => true, 'room_id' => $id, 'name' => $name]);
    }

    /** Kirim pesan pengunjung. Input: {room_id, body}. */
    /** Kirim pesan pengunjung. */
    public function chat_send(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $ip = Visitor::ip();
        if (ip_is_blocked($ip)) { http_response_code(403); $this->json(['ok' => false, 'error' => 'Perangkat Anda diblokir.']); }
        $d = $this->apiBody(__FUNCTION__);
        $roomId = (string) ($d['room_id'] ?? '');
        if ($roomId === '' || $roomId !== ($_SESSION['chat_room'] ?? '')) {
            http_response_code(403);
            $this->json(['ok' => false, 'error' => 'Sesi chat tidak valid.']);
        }

        $room = db_row("SELECT * FROM chat_rooms WHERE id = ? LIMIT 1", [$roomId]);
        if (!$room) { $this->json(['ok' => false, 'error' => 'Room chat tidak ditemukan.']); }
        $firstUser = !db_val("SELECT 1 FROM chat_messages WHERE room_id = ? AND sender = 'user' LIMIT 1", [$roomId]);
        $sent = LiveChat::sendMessage($roomId, 'user', $d['body'] ?? '');
        if (!$sent['ok']) $this->json($sent);

        if ($firstUser && setting('livechat_wa_notify', '1') === '1') {
            Fonnte::notify(null, '', "💬 *Live Chat Baru*\nDari: " . $room['visitor_name'] .
                "\nLokasi: " . trim(($room['city'] ?? '') . ' ' . ($room['country'] ?? '')) .
                "\nPesan: " . mb_substr((string) ($d['body'] ?? ''), 0, 200) .
                "\n\nBalas di panel admin → Live Chat.", 'chat');
        }
        $this->json($sent);
    }

    /** Polling pesan untuk pengunjung. Query: room, after. */
    /** Poll pesan, status, dan indikator mengetik untuk pengunjung. */
    public function chat_poll(): void {
        if (!feature_enabled('live_chat')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        header('Cache-Control: no-store');
        if (!Security::rateLimitPersistent('api:chat_poll', 90, 60)) {
            http_response_code(429);
            $this->json(['ok' => false, 'status' => 'throttled', 'messages' => []]);
        }
        $roomId = (string) ($_GET['room'] ?? '');
        $after = (int) ($_GET['after'] ?? 0);
        if ($roomId === '' || $roomId !== ($_SESSION['chat_room'] ?? '')) {
            $this->json(['ok' => false, 'status' => 'invalid', 'messages' => []]);
        }
        $room = db_row("SELECT status, unread_user,
                              (typing_admin > NOW() - INTERVAL 5 SECOND) AS admin_typing
                       FROM chat_rooms WHERE id = ?", [$roomId]);
        if (!$room) { $this->json(['ok' => false, 'status' => 'invalid', 'messages' => []]); }

        LiveChat::markDelivered($roomId, 'admin');
        $rows = LiveChat::messages($roomId, 50, $after);
        $this->json([
            'ok' => true,
            'status' => $room['status'],
            'admin_typing' => (bool) $room['admin_typing'],
            'unread' => (int) $room['unread_user'],
            'messages' => $rows,
            'message_statuses' => LiveChat::statusMap($roomId, 'user'),
        ]);
    }

    public function index(): void { $this->json(['ok' => true]); }

    /** Pencarian pintar beranda (GET ?q=). */
    public function search(): void {
        header('Cache-Control: public, max-age=60');
        if (!Security::rateLimit('smart-search', 90, 60)) { http_response_code(429); $this->json(['ok' => false, 'error' => 'Terlalu banyak permintaan, coba sebentar lagi.']); }
        $this->json(['ok' => true] + SmartSearch::run((string) ($_GET['q'] ?? '')));
    }

    /** Notifikasi Web (lonceng + popup di halaman publik): gabungan pengumuman aktif, berita terbaru, agenda mendatang. */
    public function notifications(): void {
        if (!feature_enabled('push_pwa')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        header('Cache-Control: no-store');
        if (setting('webnotif_enabled', '1') !== '1') { $this->json(['enabled' => false, 'items' => []]); }
        $limit = max(3, min(15, (int) setting('webnotif_limit', '6') ?: 6));
        $items = [];

        if (feature_enabled('news') && setting('webnotif_pengumuman', '1') === '1') {
            $rows = db_all("SELECT id, title, content, is_important, created_at FROM announcements
                            WHERE is_active = 1 AND deleted_at IS NULL
                              AND (start_date IS NULL OR start_date <= CURDATE())
                              AND (end_date IS NULL OR end_date >= CURDATE())
                            ORDER BY is_important DESC, id DESC LIMIT " . $limit);
            foreach ($rows as $a) {
                $items[] = [
                    'id' => 'p' . $a['id'], 'type' => 'pengumuman',
                    'title' => $a['title'],
                    'excerpt' => mb_substr(trim(strip_tags((string) $a['content'])), 0, 110),
                    'url' => url('pengumuman/' . $a['id']),
                    'time' => $a['created_at'],
                    'important' => (int) $a['is_important'] === 1,
                ];
            }
        }
        if (feature_enabled('news') && setting('webnotif_berita', '1') === '1') {
            $rows = db_all("SELECT id, title, slug, excerpt, content, published_at FROM news
                            WHERE status = 'published' AND deleted_at IS NULL AND published_at <= NOW()
                            ORDER BY published_at DESC LIMIT " . $limit);
            foreach ($rows as $n) {
                $items[] = [
                    'id' => 'b' . $n['id'], 'type' => 'berita',
                    'title' => $n['title'],
                    'excerpt' => mb_substr(trim(strip_tags((string) (($n['excerpt'] ?? '') !== '' ? $n['excerpt'] : $n['content']))), 0, 110),
                    'url' => url('berita/' . $n['slug']),
                    'time' => $n['published_at'],
                    'important' => false,
                ];
            }
        }
        if (feature_enabled('news') && setting('webnotif_agenda', '1') === '1') {
            $rows = db_all("SELECT id, title, location, event_date, start_time, created_at FROM agendas
                            WHERE deleted_at IS NULL AND event_date >= CURDATE()
                            ORDER BY event_date ASC LIMIT 3");
            foreach ($rows as $g) {
                $items[] = [
                    'id' => 'g' . $g['id'], 'type' => 'agenda',
                    'title' => 'Agenda: ' . $g['title'],
                    'excerpt' => tgl_id($g['event_date']) . ($g['start_time'] ? ' pukul ' . substr($g['start_time'], 0, 5) : '') . ($g['location'] ? ' di ' . $g['location'] : ''),
                    'url' => url('agenda'),
                    'time' => $g['created_at'],
                    'important' => false,
                ];
            }
        }
        usort($items, fn($a, $b) => ($b['important'] <=> $a['important']) ?: strcmp((string) $b['time'], (string) $a['time']));
        $this->json([
            'enabled' => true,
            'items' => array_slice($items, 0, $limit),
            'popup' => setting('webnotif_popup', '1') === '1',
            'popupImportant' => setting('webnotif_popup_important', '1') === '1',
        ]);
    }

    public function push_subscribe(): void {
        if (!feature_enabled('push_pwa')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $data = $this->apiBody(__FUNCTION__);
        $endpoint = (string) ($data['endpoint'] ?? '');
        $endpointParts = parse_url($endpoint);
        if (!is_array($endpointParts) || strtolower((string) ($endpointParts['scheme'] ?? '')) !== 'https'
            || empty($endpointParts['host']) || strlen($endpoint) > 2048) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Endpoint push tidak valid.']);
        }
        if (mb_strlen((string) ($data['keys']['p256dh'] ?? '')) < 20
            || mb_strlen((string) ($data['keys']['auth'] ?? '')) < 8) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Kunci push tidak valid.']);
        }
        db_exec("INSERT INTO push_subscriptions (endpoint_hash, endpoint, p256dh, auth, user_agent, created_at) VALUES (?,?,?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), user_agent = VALUES(user_agent)", [
            hash('sha256', $endpoint), $endpoint,
            substr((string) ($data['keys']['p256dh'] ?? ''), 0, 255) ?: null,
            substr((string) ($data['keys']['auth'] ?? ''), 0, 100) ?: null,
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
        ]);
        $this->json(['ok' => true]);
    }

    public function push_unsubscribe(): void {
        if (!feature_enabled('push_pwa')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        $data = $this->apiBody(__FUNCTION__);
        $endpoint = (string) ($data['endpoint'] ?? '');
        if ($endpoint !== '') db_exec("DELETE FROM push_subscriptions WHERE endpoint_hash = ?", [hash('sha256', $endpoint)]);
        $this->json(['ok' => true]);
    }

    public function push_latest(): void {
        if (!feature_enabled('push_pwa')) { http_response_code(404); $this->json(['ok' => false, 'error' => 'Fitur nonaktif.']); }
        header('Cache-Control: no-store');
        $n = db_row("SELECT title, body, url FROM push_notifications ORDER BY id DESC LIMIT 1");
        $this->json([
            'title' => $n['title'] ?? setting('village_name', 'Informasi Desa'),
            'body' => $n['body'] ?? 'Ada informasi baru dari desa.',
            'url' => ($n['url'] ?? '') ?: url(''),
            'icon' => asset('img/pwa-192.png'),
        ]);
    }

    public function experience(): void {
        if (!feature_enabled('music_event')) { $this->json(['ok' => true, 'music' => null, 'event' => null]); }
        $page = ($_GET['halaman'] ?? '') === 'beranda' ? 'beranda' : 'lainnya';
        $now = date('Y-m-d H:i:s');

        // Event aktif dengan prioritas terkecil yang jadwalnya mencakup saat ini
        $event = null;
        $events = db_all("SELECT e.*, m.id AS em_id, m.title AS em_title, m.file_path AS em_path,
                   m.volume AS em_volume, m.loop_music AS em_loop, m.autoplay AS em_autoplay
            FROM event_effects e
            LEFT JOIN background_music m
              ON m.id = e.music_id AND m.is_active = 1
            WHERE e.deleted_at IS NULL AND e.is_active = 1
            AND CONCAT(e.start_date,' ',e.start_time) <= ? AND CONCAT(e.end_date,' ',e.end_time) >= ?
            ORDER BY e.priority ASC, e.id DESC", [$now, $now]);
        foreach ($events as $ev) {
            if ($ev['active_pages'] === 'beranda' && $page !== 'beranda') continue;
            $event = [
                'id' => (int) $ev['id'],
                'name' => $ev['event_name'],
                'effect' => $ev['effect_type'],
                'message' => $ev['message'],
                'banner' => $ev['banner_text'],
                'colors' => array_values(array_filter(array_map('trim', explode(',', (string) $ev['color_scheme'])))),
                'theme_color' => $ev['theme_color'],
                'intensity' => $ev['intensity'],
                'duration' => (int) $ev['duration'],
                'frequency' => (int) $ev['frequency'],
                'closable' => (bool) $ev['closable_by_user'],
                'once' => (bool) $ev['show_once_per_visit'],
            ];
            $eventMusicPath = ltrim((string) ($ev['em_path'] ?? ''), '/');
            if ($ev['em_id'] && $eventMusicPath !== ''
                && is_file(BASE_PATH . '/uploads/' . $eventMusicPath)
                && is_readable(BASE_PATH . '/uploads/' . $eventMusicPath)) {
                $event['music'] = [
                    'id' => (int) $ev['em_id'], 'title' => $ev['em_title'],
                    'src' => url('media/musik/' . $ev['em_id']),
                    'volume' => (int) $ev['em_volume'], 'loop' => (bool) $ev['em_loop'], 'autoplay' => (bool) $ev['em_autoplay'] && setting('music_autoplay', '1') === '1',
                ];
            }
            break;
        }

        // Musik aktif: dukung tanggal satu sisi dan rentang jam yang melewati tengah malam.
        $music = null;
        if (setting('music_enabled', '1') === '1') {
            $rows = db_all("SELECT * FROM background_music
                            WHERE is_active = 1
                            ORDER BY (start_date IS NOT NULL OR end_date IS NOT NULL) DESC, id DESC");
            $today = date('Y-m-d');
            $timeNow = date('H:i:s');
            foreach ($rows as $m) {
                if ($m['active_pages'] === 'beranda' && $page !== 'beranda') continue;
                if (!empty($m['start_date']) && $today < $m['start_date']) continue;
                if (!empty($m['end_date']) && $today > $m['end_date']) continue;

                $startTime = (string) ($m['start_time'] ?? '');
                $endTime = (string) ($m['end_time'] ?? '');
                if ($startTime !== '' || $endTime !== '') {
                    $startTime = $startTime !== '' ? $startTime : '00:00:00';
                    $endTime = $endTime !== '' ? $endTime : '23:59:59';
                    if ($startTime <= $endTime) {
                        if ($timeNow < $startTime || $timeNow > $endTime) continue;
                    } else {
                        // Contoh 20:00-05:00.
                        if ($timeNow > $endTime && $timeNow < $startTime) continue;
                    }
                }

                $relativeFile = ltrim((string) ($m['file_path'] ?? ''), '/');
                $absoluteFile = BASE_PATH . '/uploads/' . $relativeFile;
                // Jangan kirim sumber audio 404 ke browser. File yang hilang dapat
                // diunggah ulang dari menu Musik Website pada panel admin.
                if ($relativeFile === '' || !is_file($absoluteFile) || !is_readable($absoluteFile)) continue;

                $music = [
                    'id' => (int) $m['id'],
                    'title' => $m['title'],
                    'src' => url('media/musik/' . $m['id']),
                    'volume' => (int) $m['volume'],
                    'loop' => (bool) $m['loop_music'],
                    'autoplay' => (bool) $m['autoplay'] && setting('music_autoplay', '1') === '1',
                    'position' => $m['display_position'] ?: 'kanan-bawah',
                ];
                break;
            }

            // Sumber URL eksternal/relatif dapat menjadi fallback bila tidak ada
            // file upload aktif yang valid. Skema javascript/data ditolak.
            if ($music === null) {
                $fallbackUrl = trim(setting('music_url', ''));
                $isHttpUrl = filter_var($fallbackUrl, FILTER_VALIDATE_URL)
                    && in_array(strtolower((string) parse_url($fallbackUrl, PHP_URL_SCHEME)), ['http', 'https'], true);
                $isRelativeUrl = str_starts_with($fallbackUrl, '/') && !str_starts_with($fallbackUrl, '//');
                if ($fallbackUrl !== '' && ($isHttpUrl || $isRelativeUrl)) {
                    $music = [
                        'id' => 'url-' . substr(hash('sha256', $fallbackUrl), 0, 12),
                        'title' => setting('village_name', 'Desa') . ' · Musik',
                        'src' => $fallbackUrl,
                        'volume' => 50,
                        'loop' => true,
                        'autoplay' => setting('music_autoplay', '1') === '1',
                        'position' => 'kanan-bawah',
                    ];
                }
            }
        }
        if ($event && isset($event['music']) && setting('music_enabled', '1') === '1') {
            $music = $event['music'] + ['position' => $music['position'] ?? 'kanan-bawah'];
        }
        header('Cache-Control: public, max-age=30');
        $this->json(['music' => $music, 'event' => $event]);
    }

    public function sitemap(): void {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = ['', 'profil', 'pemerintahan', 'berita', 'pengumuman', 'agenda', 'galeri', 'layanan', 'statistik', 'transparansi', 'pengaduan', 'umkm', 'dokumen', 'kontak', 'hiburan'];
        $urls = array_values(array_filter($urls, 'feature_public_path_enabled'));
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) echo '<url><loc>' . e(url($u)) . '</loc></url>';
        if (feature_enabled('news')) {
            foreach (db_all("SELECT slug, updated_at FROM news WHERE deleted_at IS NULL AND status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 500") as $n) {
                echo '<url><loc>' . e(url('berita/' . $n['slug'])) . '</loc><lastmod>' . date('Y-m-d', strtotime($n['updated_at'])) . '</lastmod></url>';
            }
        }
        if (feature_enabled('static_pages')) {
            foreach (db_all("SELECT slug FROM pages WHERE deleted_at IS NULL AND is_active=1") as $p) {
                echo '<url><loc>' . e(url('halaman/' . $p['slug'])) . '</loc></url>';
            }
        }
        echo '</urlset>';
        exit;
    }

    public function manifest(): void {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $name = setting('pwa_name', '') ?: setting('village_name', 'Desa');
        $short = setting('pwa_short_name', '') ?: mb_substr($name, 0, 14);
        $desc = setting('pwa_description', '') ?: setting('village_description', 'Website resmi ' . $name);
        $themeColor = setting('pwa_theme_color', '') ?: setting('primary_color', '#0f6b4e');
        $bgColor = setting('pwa_bg_color', '') ?: '#ffffff';
        $icon192 = setting('pwa_icon_192', '') ? upload_url(setting('pwa_icon_192')) : asset('img/pwa-192.png');
        $icon512 = setting('pwa_icon_512', '') ? upload_url(setting('pwa_icon_512')) : asset('img/pwa-512.png');
        echo json_encode([
            'name' => $name,
            'short_name' => $short,
            'description' => $desc,
            'id' => url(''),
            'start_url' => url(''),
            'scope' => url(''),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => $bgColor,
            'theme_color' => $themeColor,
            'lang' => 'id',
            'icons' => [
                ['src' => $icon192, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('img/pwa-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function robots(): void {
        header('Content-Type: text/plain');
        echo "User-agent: *\nDisallow: /admin\nDisallow: /uploads/protected\nSitemap: " . url('sitemap.xml') . "\n";
        exit;
    }
}
