<?php

class IdmController extends Controller {

    public array $actions = ['index', 'simpan', 'hapus', 'cetak'];

    public function __construct() { Auth::requirePermission('kelola_konten'); }

    public function index(): void {
        $this->adminView('idm_admin', [
            'pageTitle' => 'Kelola IDM (Indeks Desa Membangun)',
            'rows' => db_all("SELECT * FROM idm_data ORDER BY tahun DESC"),
        ]);
    }

    public function simpan(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/idm');
        verify_csrf();

        $tahun = (int) ($_POST['tahun'] ?? 0);
        $errors = [];
        if ($tahun < 2000 || $tahun > 2100) $errors[] = 'Tahun tidak valid.';

        $indices = [];
        foreach (['iks' => 'IKS', 'ike' => 'IKE', 'ikl' => 'IKL'] as $key => $label) {
            $raw = str_replace(',', '.', trim((string) ($_POST[$key] ?? '')));
            if ($raw === '' || !is_numeric($raw)) {
                $errors[] = "$label harus berupa angka antara 0 dan 1.";
                continue;
            }
            $value = (float) $raw;
            if ($value < 0 || $value > 1) {
                $errors[] = "$label harus berada pada rentang 0–1.";
                continue;
            }
            $indices[$key] = round($value, 4);
        }

        if ($errors) {
            flash('danger', implode(' ', array_unique($errors)));
            redirect('admin/idm');
        }

        try {
            db_exec(
                "INSERT INTO idm_data (tahun, iks, ike, ikl, catatan)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    iks=VALUES(iks), ike=VALUES(ike), ikl=VALUES(ikl),
                    catatan=VALUES(catatan), updated_at=NOW()",
                [
                    $tahun,
                    $indices['iks'],
                    $indices['ike'],
                    $indices['ikl'],
                    mb_substr(trim((string) ($_POST['catatan'] ?? '')), 0, 1000, 'UTF-8') ?: null,
                ]
            );
        } catch (Throwable $e) {
            flash('danger', 'Data IDM gagal disimpan. Periksa struktur database dan coba kembali.');
            redirect('admin/idm');
        }

        log_activity('edit', 'idm', "Simpan IDM tahun $tahun");
        flash('success', 'Data IDM tahun ' . $tahun . ' berhasil disimpan.');
        redirect('admin/idm');
    }

    public function hapus(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/idm');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $changed = $id > 0 ? db_exec("DELETE FROM idm_data WHERE id = ?", [$id]) : 0;
        flash($changed ? 'success' : 'warning', $changed ? 'Data IDM berhasil dihapus.' : 'Data IDM tidak ditemukan.');
        if ($changed) log_activity('hapus', 'idm', "Hapus IDM #$id");
        redirect('admin/idm');
    }

    public function cetak(): void { redirect('transparansi/cetak?jenis=idm'); }
}
