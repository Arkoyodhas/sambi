<?php

class KunjunganController extends Controller {

    public array $actions = ['index', 'blokir', 'buka'];

    public function __construct() { Auth::requirePermission('lihat_kunjungan'); }

    public function blokir(): void {
        verify_csrf();
        $ip = trim((string) ($_POST['ip'] ?? ''));
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { flash('danger', 'Alamat IP tidak valid.'); redirect('admin/kunjungan'); }
        db_exec("INSERT INTO blocked_ips (ip, reason, created_by, created_at) VALUES (?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE reason = VALUES(reason)", [$ip, mb_substr(trim((string) ($_POST['reason'] ?? 'Diblokir dari statistik kunjungan')), 0, 255), Auth::user()['id'] ?? null]);
        log_activity('ban', 'kunjungan', 'Blokir IP ' . $ip);
        flash('success', 'IP ' . e($ip) . ' berhasil diblokir. Perangkat itu tidak bisa lagi mengakses situs.');
        redirect('admin/kunjungan');
    }

    public function buka(): void {
        verify_csrf();
        $ip = trim((string) ($_POST['ip'] ?? ''));
        db_exec("DELETE FROM blocked_ips WHERE ip = ?", [$ip]);
        log_activity('ban', 'kunjungan', 'Buka blokir IP ' . $ip);
        flash('success', 'Blokir IP ' . e($ip) . ' dibuka.');
        redirect('admin/kunjungan');
    }

    public function index(): void {
        Visitor::resolvePending(); // lengkapi lokasi kunjungan yang tertunda (batch, di sisi admin)
        $days = (int) ($_GET['hari'] ?? 30);
        if (!in_array($days, [7, 30, 90], true)) $days = 30;
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days')) . ' 00:00:00';

        $this->adminView('kunjungan', [
            'pageTitle' => 'Statistik Kunjungan',
            'days' => $days,
            'daily' => db_all("SELECT DATE(visited_at) d, COUNT(*) pv, COALESCE(SUM(is_unique),0) uv FROM visit_logs WHERE visited_at >= ? GROUP BY DATE(visited_at) ORDER BY d", [$start]),
            'totals' => db_row("SELECT COUNT(*) pv, COALESCE(SUM(is_unique),0) uv FROM visit_logs WHERE visited_at >= ?", [$start]),
            'today' => db_row("SELECT COUNT(*) pv, COALESCE(SUM(is_unique),0) uv FROM visit_logs WHERE visited_at >= ?", [date('Y-m-d') . ' 00:00:00']),
            'topPages' => db_all("SELECT path, COUNT(*) total FROM visit_logs WHERE visited_at >= ? GROUP BY path ORDER BY total DESC LIMIT 12", [$start]),
            'topLocations' => db_all("SELECT COALESCE(NULLIF(city,''), 'Tidak diketahui') city, COALESCE(region_name,'') region_name, COALESCE(country,'') country, COUNT(*) total
                FROM visit_logs WHERE visited_at >= ? GROUP BY city, region_name, country ORDER BY total DESC LIMIT 10", [$start]),
            'devices' => db_all("SELECT COALESCE(device,'lainnya') device, COUNT(*) total FROM visit_logs WHERE visited_at >= ? GROUP BY device ORDER BY total DESC", [$start]),
            'osStats' => db_all("SELECT COALESCE(NULLIF(os,''),'Lainnya') os, COUNT(*) total FROM visit_logs WHERE visited_at >= ? GROUP BY os ORDER BY total DESC LIMIT 6", [$start]),
            'geoPoints' => db_all("SELECT l.lat, l.lon, COALESCE(NULLIF(v.city,''),'Tidak diketahui') city, COALESCE(v.region_name,'') region_name, COUNT(*) total
                FROM visit_logs v JOIN ip_locations l ON l.ip = v.ip_address AND l.status = 'success' AND l.lat IS NOT NULL
                WHERE v.visited_at >= ? GROUP BY l.lat, l.lon, v.city, v.region_name ORDER BY total DESC LIMIT 120", [$start]),
            'recent' => db_all("SELECT * FROM visit_logs ORDER BY id DESC LIMIT 50"),
            'blocked' => db_all("SELECT b.*, u.name AS admin_name FROM blocked_ips b LEFT JOIN users u ON u.id = b.created_by ORDER BY b.id DESC LIMIT 100"),
        ]);
    }
}
