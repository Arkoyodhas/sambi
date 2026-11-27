<?php

class PengaduanAdminController extends Controller {

    public array $actions = ['index', 'detail', 'balas', 'status', 'export', 'delete'];

    public function __construct() { Auth::requirePermission('kelola_pengaduan'); }

    public function index(): void {
        $where = ['c.deleted_at IS NULL']; $params = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = "(c.ticket LIKE ? OR c.nama LIKE ? OR c.content LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if (!empty($_GET['status'])) { $where[] = "c.status = ?"; $params[] = $_GET['status']; }
        if (isset($_GET['prioritas']) && $_GET['prioritas'] !== '') { $where[] = "c.priority = ?"; $params[] = (int) $_GET['prioritas']; }
        $sql = "SELECT c.* FROM complaints c WHERE " . implode(' AND ', $where) . " ORDER BY c.priority DESC, c.id DESC";
        $pg = paginate($sql, $params, 20);
        $this->adminView('pengaduan_list', ['pageTitle' => 'Pengaduan Warga', 'pg' => $pg]);
    }

    public function detail($id = null): void {
        $c = db_row("SELECT * FROM complaints WHERE id = ? AND deleted_at IS NULL", [(int) $id]);
        if (!$c) redirect('admin/pengaduan');
        $responses = db_all("SELECT cr.*, u.name AS admin_name FROM complaint_responses cr
            LEFT JOIN users u ON u.id=cr.user_id WHERE cr.complaint_id = ? ORDER BY cr.created_at ASC", [$c['id']]);
        $this->adminView('pengaduan_detail', ['pageTitle' => 'Pengaduan ' . $c['ticket'], 'c' => $c, 'responses' => $responses]);
    }

    public function balas($id = null): void {
        verify_csrf();
        $response = trim($_POST['response'] ?? '');
        if ($response !== '') {
            db_insert('complaint_responses', [
                'complaint_id' => (int) $id, 'user_id' => auth_user()['id'],
                'response' => mb_substr($response, 0, 2000), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            if (($_POST['set_status'] ?? '') !== '') {
                $status = in_array($_POST['set_status'], ['diterima', 'diproses', 'selesai', 'ditolak'], true) ? $_POST['set_status'] : null;
                if ($status) db_update('complaints', ['status' => $status], 'id = ?', [(int) $id]);
            }
            log_activity('edit', 'pengaduan', "Balas pengaduan #$id");
            flash('success', 'Balasan terkirim.');
        }
        redirect('admin/pengaduan/detail/' . (int) $id);
    }

    public function status($id = null): void {
        verify_csrf();
        $data = [];
        if (isset($_POST['status']) && in_array($_POST['status'], ['diterima', 'diproses', 'selesai', 'ditolak'], true)) $data['status'] = $_POST['status'];
        if (isset($_POST['toggle_priority'])) db_exec("UPDATE complaints SET priority = 1 - priority WHERE id = ?", [(int) $id]);
        if (isset($_POST['toggle_anonymous'])) db_exec("UPDATE complaints SET is_anonymous = 1 - is_anonymous WHERE id = ?", [(int) $id]);
        if ($data) db_update('complaints', $data, 'id = ?', [(int) $id]);
        log_activity('edit', 'pengaduan', "Update pengaduan #$id");
        flash('success', 'Pengaduan diperbarui.');
        redirect($_POST['back'] ?? 'admin/pengaduan/detail/' . (int) $id);
    }

    public function export(): void {
        $rows = db_all("SELECT ticket, CASE WHEN is_anonymous=1 THEN '(Dirahasiakan)' ELSE nama END AS nama,
            category, content, status, IF(priority=1,'Prioritas','Normal') AS prioritas, created_at
            FROM complaints WHERE deleted_at IS NULL ORDER BY id DESC");
        log_activity('export', 'pengaduan', 'Export laporan pengaduan');
        XLSXWriter::download('laporan-pengaduan-' . date('Ymd') . '.xlsx',
            ['TIKET', 'PELAPOR', 'KATEGORI', 'ISI', 'STATUS', 'PRIORITAS', 'TANGGAL'],
            array_map('array_values', $rows));
    }

    public function delete($id = null): void {
        verify_csrf();
        db_update('complaints', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $id]);
        flash('success', 'Pengaduan ditutup/dihapus.');
        redirect('admin/pengaduan');
    }
}
