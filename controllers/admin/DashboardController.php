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
            'audit' => feature_enabled('visitor_audit') && can('lihat_audit'),
            'music' => feature_enabled('music_event') && can('kelola_musik'),
            'event' => feature_enabled('music_event') && can('kelola_event'),
        ];

        $stats = [];
        if ($access['population']) {
            $stats += [
                'penduduk' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup'"),
                'kk' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
                'laki' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup' AND jenis_kelamin='L'"),
                'perempuan' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup' AND jenis_kelamin='P'"),
            ];
        }
        if ($access['news']) {
            $stats['berita'] = (int) db_val("SELECT COUNT(*) FROM news WHERE deleted_at IS NULL");
        }
        if ($access['services']) {
            $stats['layanan_baru'] = (int) db_val("SELECT COUNT(*) FROM service_requests WHERE deleted_at IS NULL AND status='baru'");
        }
        if ($access['complaints']) {
            $stats['pengaduan_baru'] = (int) db_val("SELECT COUNT(*) FROM complaints WHERE deleted_at IS NULL AND status='diterima'");
            $stats['pesan'] = (int) db_val("SELECT COUNT(*) FROM contact_messages WHERE is_read=0");
        }

        $visit = ['daily' => [], 'top' => [], 'today' => ['pv' => 0, 'uv' => 0]];
        if ($access['audit']) {
            try {
                $d30 = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
                $todayStart = date('Y-m-d') . ' 00:00:00';
                $visit['daily'] = db_all("SELECT DATE(visited_at) d, COUNT(*) pv, COALESCE(SUM(is_unique),0) uv FROM visit_logs WHERE visited_at >= ? GROUP BY DATE(visited_at) ORDER BY d", [$d30]);
                $visit['top'] = db_all("SELECT path, COUNT(*) total FROM visit_logs WHERE visited_at >= ? GROUP BY path ORDER BY total DESC LIMIT 7", [$d30]);
                $visit['today'] = db_row("SELECT COUNT(*) pv, COALESCE(SUM(is_unique),0) uv FROM visit_logs WHERE visited_at >= ?", [$todayStart]) ?: $visit['today'];
            } catch (Throwable $e) {
                // Database lama mungkin belum memiliki tabel visit_logs.
            }
        }

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
            'stats' => $stats,
            'agendas' => $access['news']
                ? db_all("SELECT * FROM agendas WHERE deleted_at IS NULL AND event_date >= CURDATE() AND status != 'dibatalkan' ORDER BY event_date ASC LIMIT 5")
                : [],
            'recentLogs' => $access['audit']
                ? db_all("SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 8")
                : [],
            'recentRequests' => $access['services']
                ? db_all("SELECT r.*, s.name AS service_name FROM service_requests r JOIN services s ON s.id=r.service_id WHERE r.deleted_at IS NULL ORDER BY r.id DESC LIMIT 5")
                : [],
            'recentComplaints' => $access['complaints']
                ? db_all("SELECT * FROM complaints WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 5")
                : [],
            'dusunChart' => $access['population']
                ? db_all("SELECT d.name AS label, COUNT(r.id) AS total FROM regions d
                    LEFT JOIN residents r ON r.dusun_id=d.id AND r.deleted_at IS NULL AND r.status_penduduk='hidup'
                    WHERE d.type='dusun' GROUP BY d.id, d.name")
                : [],
            'activeMusic' => $access['music']
                ? db_row("SELECT title FROM background_music WHERE is_active=1 ORDER BY id DESC LIMIT 1")
                : null,
            'activeEvent' => $access['event']
                ? db_row("SELECT event_name, effect_type FROM event_effects WHERE deleted_at IS NULL AND is_active=1
                    AND CONCAT(start_date,' ',start_time) <= ? AND CONCAT(end_date,' ',end_time) >= ? ORDER BY priority ASC LIMIT 1", [$now, $now])
                : null,
            'visitDaily' => $visit['daily'],
            'visitTop' => $visit['top'],
            'visitToday' => $visit['today'],
            'needFaceSetup' => $needFaceSetup,
        ]);
    }
}
