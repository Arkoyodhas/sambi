<?php

class DashboardController extends Controller {

    public function index(): void {
        Auth::requireLogin();
        $now = date('Y-m-d H:i:s');

        $access = [
            'population' => feature_enabled('population') && can('kelola_penduduk'),
            'news' => feature_enabled('news') && can('kelola_berita'),
            'services' => feature_enabled('services') && can('kelola_layanan'),
            'complaints' => feature_enabled('complaints') && can('kelola_pengaduan'),
            'contact' => feature_enabled('contact') && can('kelola_pengaduan'),
            'chat' => feature_enabled('live_chat') && can('balas_chat'),
            'feedback' => feature_enabled('feedback') && can('lihat_feedback'),
            'audit' => can('lihat_audit'),
            'music' => feature_enabled('music_event') && can('kelola_musik'),
            'event' => feature_enabled('music_event') && can('kelola_event'),
        ];

        [$inbox, $feed] = $this->inbox($access);
        usort($feed, static fn($a, $b) => strcmp((string) $b['time'], (string) $a['time']));
        $feed = array_slice($feed, 0, 12);
        foreach ($feed as &$item) $item['ago'] = $this->ago((string) $item['time']);
        unset($item);

        $stats = [];
        if ($access['population']) {
            $stats += [
                'penduduk' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup'"),
                'kk' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
                'laki' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup' AND jenis_kelamin='L'"),
                'perempuan' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup' AND jenis_kelamin='P'"),
            ];
        }
        if ($access['news']) $stats['berita'] = (int) db_val("SELECT COUNT(*) FROM news WHERE deleted_at IS NULL");

        $currentUser = auth_user();
        $needFaceSetup = false;
        if ($currentUser && feature_enabled('face_auth')) {
            try {
                $needFaceSetup = !FaceAuth::hasUsableProfile((int) $currentUser['id']);
            } catch (Throwable $e) {
                $needFaceSetup = false;
            }
        }

        $this->adminView('dashboard', [
            'pageTitle' => 'Dashboard',
            'access' => $access,
            'inbox' => $inbox,
            'feed' => $feed,
            'stats' => $stats,
            'userName' => (string) ($currentUser['name'] ?? 'Admin'),
            'agendas' => $access['news']
                ? db_all("SELECT title, event_date, start_time, location FROM agendas WHERE deleted_at IS NULL AND event_date >= CURDATE() AND status != 'dibatalkan' ORDER BY event_date ASC LIMIT 4")
                : [],
            'recentLogs' => $access['audit']
                ? db_all("SELECT l.action, l.description, l.created_at, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 4")
                : [],
            'dusunChart' => $access['population']
                ? db_all("SELECT d.name AS label, COUNT(r.id) AS total FROM regions d
                    LEFT JOIN residents r ON r.dusun_id=d.id AND r.deleted_at IS NULL AND r.status_penduduk='hidup'
                    WHERE d.type='dusun' GROUP BY d.id, d.name HAVING total > 0 ORDER BY total DESC LIMIT 8")
                : [],
            'activeMusic' => $access['music']
                ? db_row("SELECT title FROM background_music WHERE is_active=1 ORDER BY id DESC LIMIT 1")
                : null,
            'activeEvent' => $access['event']
                ? db_row("SELECT event_name, effect_type FROM event_effects WHERE deleted_at IS NULL AND is_active=1
                    AND CONCAT(start_date,' ',start_time) <= ? AND CONCAT(end_date,' ',end_time) >= ? ORDER BY priority ASC LIMIT 1", [$now, $now])
                : null,
            'needFaceSetup' => $needFaceSetup,
        ]);
    }

    /** Ringkasan semua hal dari warga yang menunggu tanggapan admin, sesuai hak akses. */
    private function inbox(array $access): array {
        $inbox = [];
        $feed = [];
        $safe = static function (callable $fn) { try { return $fn(); } catch (Throwable $e) { error_log('Dashboard inbox: ' . $e->getMessage()); return null; } };

        if ($access['services']) {
            $safe(function () use (&$inbox, &$feed) {
                $rows = db_all("SELECT r.id, r.ticket, r.nama, r.status, r.created_at, s.name AS service_name FROM service_requests r
                    LEFT JOIN services s ON s.id=r.service_id WHERE r.deleted_at IS NULL AND r.status IN ('baru','diproses') ORDER BY r.created_at DESC, r.id DESC LIMIT 12");
                $inbox[] = [
                    'key' => 'surat', 'label' => 'Surat', 'icon' => 'bi-file-earmark-text',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM service_requests WHERE deleted_at IS NULL AND status='baru'"),
                    'progress' => (int) db_val("SELECT COUNT(*) FROM service_requests WHERE deleted_at IS NULL AND status='diproses'"),
                    'pendingLabel' => 'baru masuk', 'progressLabel' => 'sedang diproses',
                    'url' => url('admin/permohonan?status=baru'), 'latest' => $this->firstWith($rows, 'baru')['nama'] ?? null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'surat', 'typeLabel' => 'Surat', 'name' => $r['nama'], 'text' => ($r['service_name'] ?: 'Permohonan layanan') . ' · ' . $r['ticket'], 'time' => $r['created_at'], 'status' => $r['status'], 'url' => url('admin/permohonan/detail/' . $r['id'])];
            });
        }

        if ($access['complaints']) {
            $safe(function () use (&$inbox, &$feed) {
                $rows = db_all("SELECT id, ticket, nama, is_anonymous, category, content, status, created_at FROM complaints
                    WHERE deleted_at IS NULL AND status IN ('diterima','diproses') ORDER BY created_at DESC, id DESC LIMIT 12");
                $first = $this->firstWith($rows, 'diterima');
                $inbox[] = [
                    'key' => 'aduan', 'label' => 'Pengaduan', 'icon' => 'bi-megaphone',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM complaints WHERE deleted_at IS NULL AND status='diterima'"),
                    'progress' => (int) db_val("SELECT COUNT(*) FROM complaints WHERE deleted_at IS NULL AND status='diproses'"),
                    'pendingLabel' => 'belum ditanggapi', 'progressLabel' => 'sedang diproses',
                    'url' => url('admin/pengaduan?status=diterima'), 'latest' => $first ? ($first['is_anonymous'] || !$first['nama'] ? 'Anonim' : $first['nama']) : null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'aduan', 'typeLabel' => 'Pengaduan', 'name' => $r['is_anonymous'] || !$r['nama'] ? 'Anonim' : $r['nama'], 'text' => ($r['category'] ? $r['category'] . ' — ' : '') . excerpt($r['content'], 90), 'time' => $r['created_at'], 'status' => $r['status'], 'url' => url('admin/pengaduan/detail/' . $r['id'])];
            });
        }

        if ($access['contact']) {
            $safe(function () use (&$inbox, &$feed) {
                $rows = db_all("SELECT id, name, subject, message, created_at FROM contact_messages WHERE is_read=0 ORDER BY created_at DESC, id DESC LIMIT 12");
                $inbox[] = [
                    'key' => 'pesan', 'label' => 'Pesan', 'icon' => 'bi-envelope-open',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM contact_messages WHERE is_read=0"),
                    'progress' => null, 'pendingLabel' => 'belum dibaca', 'progressLabel' => '',
                    'url' => url('admin/pesan'), 'latest' => $rows[0]['name'] ?? null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'pesan', 'typeLabel' => 'Pesan', 'name' => $r['name'], 'text' => trim(($r['subject'] ? $r['subject'] . ' — ' : '') . excerpt($r['message'], 90)), 'time' => $r['created_at'], 'status' => 'belum dibaca', 'url' => url('admin/pesan/edit/' . $r['id'])];
            });
        }

        if ($access['chat']) {
            $safe(function () use (&$inbox, &$feed) {
                $rows = db_all("SELECT id, visitor_name, last_message, last_message_at, created_at, unread_admin FROM chat_rooms
                    WHERE status='open' AND unread_admin > 0 ORDER BY COALESCE(last_message_at, created_at) DESC LIMIT 12");
                $inbox[] = [
                    'key' => 'chat', 'label' => 'Live Chat', 'icon' => 'bi-chat-dots',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM chat_rooms WHERE status='open' AND unread_admin > 0"),
                    'progress' => (int) db_val("SELECT COUNT(*) FROM chat_rooms WHERE status='open'"),
                    'pendingLabel' => 'menunggu balasan', 'progressLabel' => 'percakapan terbuka',
                    'url' => url('admin/chat'), 'latest' => $rows[0]['visitor_name'] ?? null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'chat', 'typeLabel' => 'Chat', 'name' => $r['visitor_name'] ?: 'Pengunjung', 'text' => excerpt((string) $r['last_message'], 90) ?: 'Pesan baru', 'time' => $r['last_message_at'] ?: $r['created_at'], 'status' => (int) $r['unread_admin'] . ' belum dibaca', 'url' => url('admin/chat')];
            });
        }

        if ($access['news']) {
            $safe(function () use (&$inbox, &$feed) {
                $rows = db_all("SELECT c.id, c.name, c.content, c.created_at, n.title FROM news_comments c LEFT JOIN news n ON n.id=c.news_id WHERE c.is_approved=0 ORDER BY c.created_at DESC, c.id DESC LIMIT 12");
                $inbox[] = [
                    'key' => 'komentar', 'label' => 'Komentar', 'icon' => 'bi-chat-quote',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM news_comments WHERE is_approved=0"),
                    'progress' => null, 'pendingLabel' => 'menunggu persetujuan', 'progressLabel' => '',
                    'url' => url('admin/komentar'), 'latest' => $rows[0]['name'] ?? null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'komentar', 'typeLabel' => 'Komentar', 'name' => $r['name'], 'text' => excerpt($r['content'], 90) . ($r['title'] ? ' · ' . excerpt($r['title'], 40) : ''), 'time' => $r['created_at'], 'status' => 'menunggu', 'url' => url('admin/komentar')];
            });
        }

        if ($access['feedback']) {
            $safe(function () use (&$inbox, &$feed) {
                $since = date('Y-m-d H:i:s', strtotime('-7 days'));
                $rows = db_all("SELECT id, respondent_name, created_at FROM feedback_responses WHERE created_at >= ? ORDER BY created_at DESC, id DESC LIMIT 6", [$since]);
                $inbox[] = [
                    'key' => 'masukan', 'label' => 'Masukan', 'icon' => 'bi-hand-thumbs-up',
                    'pending' => (int) db_val("SELECT COUNT(*) FROM feedback_responses WHERE created_at >= ?", [$since]),
                    'progress' => null, 'pendingLabel' => 'dalam 7 hari', 'progressLabel' => '', 'informational' => true,
                    'url' => url('admin/feedback'), 'latest' => $rows[0]['respondent_name'] ?? null,
                ];
                foreach ($rows as $r) $feed[] = ['type' => 'masukan', 'typeLabel' => 'Masukan', 'name' => $r['respondent_name'] ?: 'Pengunjung', 'text' => 'Mengisi survei kepuasan website', 'time' => $r['created_at'], 'status' => 'baru', 'url' => url('admin/feedback')];
            });
        }

        return [$inbox, $feed];
    }

    private function firstWith(array $rows, string $status): ?array {
        foreach ($rows as $r) if (($r['status'] ?? '') === $status) return $r;
        return null;
    }

    private function ago(string $time): string {
        $ts = strtotime($time);
        if (!$ts) return '';
        $diff = time() - $ts;
        if ($diff < 60) return 'Baru saja';
        if ($diff < 3600) return floor($diff / 60) . ' mnt lalu';
        if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
        if ($diff < 172800) return 'Kemarin';
        if ($diff < 604800) return floor($diff / 86400) . ' hari lalu';
        return date('d/m/Y', $ts);
    }
}
