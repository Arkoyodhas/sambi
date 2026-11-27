<?php

class PermohonanController extends Controller {

    public array $actions = ['index', 'detail', 'status', 'cetak', 'export', 'delete'];

    public function __construct() { Auth::requirePermission('kelola_layanan'); }

    public function index(): void {
        $where = ['r.deleted_at IS NULL']; $params = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = "(r.ticket LIKE ? OR r.nama LIKE ? OR r.nik LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if (!empty($_GET['status'])) { $where[] = "r.status = ?"; $params[] = $_GET['status']; }
        $sql = "SELECT r.*, s.name AS service_name FROM service_requests r JOIN services s ON s.id=r.service_id
            WHERE " . implode(' AND ', $where) . " ORDER BY r.id DESC";
        $pg = paginate($sql, $params, 20);
        $this->adminView('permohonan_list', ['pageTitle' => 'Permohonan Layanan', 'pg' => $pg]);
    }

    public function detail($id = null): void {
        $r = db_row("SELECT r.*, s.name AS service_name, s.template FROM service_requests r
            JOIN services s ON s.id=r.service_id WHERE r.id = ? AND r.deleted_at IS NULL", [(int) $id]);
        if (!$r) redirect('admin/permohonan');
        $this->adminView('permohonan_detail', ['pageTitle' => 'Detail Permohonan ' . $r['ticket'], 'r' => $r]);
    }

    public function status($id = null): void {
        verify_csrf();
        $r = db_row("SELECT * FROM service_requests WHERE id = ?", [(int) $id]);
        if (!$r) redirect('admin/permohonan');
        $status = in_array($_POST['status'] ?? '', ['baru', 'diproses', 'selesai', 'ditolak'], true) ? $_POST['status'] : $r['status'];
        $data = [
            'status' => $status,
            'admin_note' => mb_substr(trim($_POST['admin_note'] ?? ''), 0, 1000) ?: null,
            'letter_number' => trim($_POST['letter_number'] ?? '') ?: null,
            'processed_by' => auth_user()['id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($status === 'selesai' && !$data['letter_number']) {
            $count = (int) db_val("SELECT COUNT(*) FROM service_requests WHERE letter_number IS NOT NULL AND YEAR(updated_at) = YEAR(NOW())") + 1;
            $data['letter_number'] = sprintf('%03d/DS/%s/%d', $count, date('m'), date('Y'));
        }
        db_update('service_requests', $data, 'id = ?', [$id]);
        if ($status !== $r['status'] && $r['no_hp']) {
            $statusLabel = ['baru' => 'Baru', 'diproses' => 'Sedang Diproses', 'selesai' => 'Selesai ✅', 'ditolak' => 'Ditolak'][$status] ?? $status;
            $extra = $status === 'selesai' ? "\nNomor Surat: " . ($data['letter_number'] ?: '-') . "\nSilakan ambil surat di kantor desa dengan membawa KTP." : '';
            if ($data['admin_note']) $extra .= "\nCatatan: {$data['admin_note']}";
            Fonnte::notify(
                $r['no_hp'],
                "Halo {$r['nama']}, status permohonan Anda diperbarui. 📋\n\nTiket: *{$r['ticket']}*\nStatus: *{$statusLabel}*{$extra}\n\n- Pemerintah " . setting('village_name'),
                '',
                'surat'
            );
        }
        log_activity('edit', 'layanan', "Update status permohonan {$r['ticket']} menjadi $status");
        flash('success', 'Status permohonan diperbarui.');
        redirect('admin/permohonan/detail/' . $id);
    }

    public function cetak($id = null): void {
        $r = db_row("SELECT r.*, s.name AS service_name, s.template FROM service_requests r
            JOIN services s ON s.id=r.service_id WHERE r.id = ?", [(int) $id]);
        if (!$r) redirect('admin/permohonan');
        $template = $r['template'] ?: "SURAT KETERANGAN\n\nYang bertanda tangan di bawah ini Kepala Desa, menerangkan bahwa:\n\nNama : {nama}\nNIK : {nik}\nAlamat : {alamat}\n\nSurat ini dibuat untuk keperluan: {keperluan}.\n\nNomor Surat: {nomor_surat}\nTanggal: {tanggal}";
        $isi = strtr($template, [
            '{nama}' => $r['nama'], '{nik}' => $r['nik'], '{alamat}' => $r['alamat'] ?: '-',
            '{keperluan}' => $r['keperluan'] ?: '-', '{nomor_surat}' => $r['letter_number'] ?: '.../DS/.../' . date('Y'),
            '{tanggal}' => tgl_id(date('Y-m-d')),
        ]);
        $this->raw('admin/permohonan_cetak', ['r' => $r, 'isi' => $isi, 'pageTitle' => 'Cetak Surat']);
    }

    public function export(): void {
        $rows = db_all("SELECT r.ticket, s.name, r.nik, r.nama, r.no_hp, r.keperluan, r.status, r.letter_number, r.created_at
            FROM service_requests r JOIN services s ON s.id=r.service_id WHERE r.deleted_at IS NULL ORDER BY r.id DESC");
        log_activity('export', 'layanan', 'Export laporan permohonan layanan');
        XLSXWriter::download('laporan-layanan-' . date('Ymd') . '.xlsx',
            ['TIKET', 'LAYANAN', 'NIK', 'NAMA', 'NO HP', 'KEPERLUAN', 'STATUS', 'NO SURAT', 'TANGGAL'],
            array_map('array_values', $rows));
    }

    public function delete($id = null): void {
        verify_csrf();
        db_update('service_requests', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [(int) $id]);
        flash('success', 'Permohonan dihapus.');
        redirect('admin/permohonan');
    }
}
