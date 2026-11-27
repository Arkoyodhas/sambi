<?php

/** Admin: daftar upaya screenshot dari sisi pengunjung.
 *  Aksi: index (lihat), blokir (blokir IP), buka (buka blokir IP), hapus (hapus log). */
class ScreenshotLogController extends Controller {

    public array $actions = ['index', 'blokir', 'buka', 'hapus', 'clear', 'chat'];

    public function __construct() { Auth::requirePermission('lihat_kunjungan'); }

    /** Kirim pesan Live Chat kepada sesi/perangkat yang menghasilkan log screenshot. */
    public function chat(): void {
        verify_csrf();
        if (!Auth::can('kelola_tampilan')) {
            flash('danger', 'Akun Anda tidak memiliki izin untuk mengelola Live Chat.');
            redirect('admin/screenshot-log');
        }
        if (setting('livechat_enabled', '1') !== '1') {
            flash('warning', 'Aktifkan Live Chat terlebih dahulu melalui Pengaturan Website.');
            redirect('admin/screenshot-log');
        }

        $attemptId = (int) ($_POST['attempt_id'] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($attemptId < 1 || $body === '') {
            flash('danger', 'Pilih log screenshot dan isi pesan terlebih dahulu.');
            redirect('admin/screenshot-log');
        }

        try {
            $result = ScreenshotChat::sendFromAttempt($attemptId, $body);
        } catch (Throwable $e) {
            error_log('Screenshot live chat admin: ' . $e->getMessage());
            flash('danger', 'Pesan belum dapat dikirim karena layanan database sedang bermasalah.');
            redirect('admin/screenshot-log');
        }
        if (empty($result['ok'])) {
            flash('danger', e((string) ($result['error'] ?? 'Pesan tidak dapat dikirim.')));
            redirect('admin/screenshot-log');
        }

        flash('success', 'Pesan Live Chat berhasil dikirim. Pengunjung akan menerimanya pada sesi/perangkat yang sesuai.');
        redirect('admin/chat?room=' . rawurlencode((string) $result['room_id']));
    }

    public function blokir(): void {
        verify_csrf();
        $ip = trim((string) ($_POST['ip'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { flash('danger', 'Alamat IP tidak valid.'); redirect('admin/screenshot-log'); }
        db_exec("INSERT INTO blocked_ips (ip, reason, created_by, created_at) VALUES (?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason)",
                 [$ip, 'Diblokir dari log upaya screenshot', Auth::user()['id'] ?? null]);
        log_activity('ban', 'screenshot', 'Blokir IP ' . $ip);
        flash('success', 'IP ' . e($ip) . ' berhasil diblokir. Perangkat itu tidak bisa lagi mengakses situs.');
        redirect('admin/screenshot-log');
    }

    public function buka(): void {
        verify_csrf();
        $ip = trim((string) ($_POST['ip'] ?? ''));
        db_exec("DELETE FROM blocked_ips WHERE ip = ?", [$ip]);
        log_activity('ban', 'screenshot', 'Buka blokir IP ' . $ip);
        flash('success', 'Blokir IP ' . e($ip) . ' dibuka.');
        redirect('admin/screenshot-log');
    }

    public function hapus(): void {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) db_exec("DELETE FROM screenshot_attempts WHERE id = ?", [$id]);
        flash('success', 'Log dihapus.');
        redirect('admin/screenshot-log');
    }

    public function clear(): void {
        verify_csrf();
        db_exec("DELETE FROM screenshot_attempts WHERE created_at < NOW() - INTERVAL 60 DAY");
        log_activity('clear', 'screenshot', 'Bersihkan log > 60 hari');
        flash('success', 'Log > 60 hari dibersihkan.');
        redirect('admin/screenshot-log');
    }

    public function index(): void {
        $ipFilter = trim((string) ($_GET['ip'] ?? ''));
        $method = trim((string) ($_GET['method'] ?? ''));
        $where = ['1=1']; $params = [];
        if ($ipFilter !== '') { $where[] = 'ip_address = ?'; $params[] = $ipFilter; }
        if ($method !== '')   { $where[] = 'method = ?';     $params[] = $method; }
        $whereSql = implode(' AND ', $where);

        $this->adminView('screenshot_log', [
            'pageTitle' => 'Upaya Screenshot',
            'ipFilter'  => $ipFilter,
            'method'    => $method,
            'totals'    => db_row("SELECT COUNT(*) total, COUNT(DISTINCT ip_address) ips FROM screenshot_attempts"),
            'today'     => (int) db_val("SELECT COUNT(*) FROM screenshot_attempts WHERE created_at >= CURDATE()"),
            'topIps'    => db_all("SELECT ip_address, COUNT(*) total, MAX(id) last_id, MAX(created_at) last_at,
                                          MAX(country) country, MAX(city) city, MAX(os) os, MAX(browser) browser
                                   FROM screenshot_attempts GROUP BY ip_address ORDER BY total DESC LIMIT 15"),
            'topMethods'=> db_all("SELECT method, COUNT(*) total FROM screenshot_attempts GROUP BY method ORDER BY total DESC LIMIT 10"),
            'recent'    => db_all("SELECT * FROM screenshot_attempts WHERE $whereSql ORDER BY id DESC LIMIT 200", $params),
            'blocked'   => db_all("SELECT ip FROM blocked_ips"),
        ]);
    }
}
