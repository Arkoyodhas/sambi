<?php

class StuntingController extends Controller {

    public array $actions = ['index', 'simpan', 'hapus', 'cetak'];

    public function __construct() { Auth::requirePermission('kelola_konten'); }

    public function index(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');
        $this->adminView('stunting_admin', [
            'pageTitle' => 'Kelola Data Stunting',
            'tahun' => $tahun,
            'rows' => db_all(
                "SELECT * FROM stunting_data
                 WHERE YEAR(tanggal_pemeriksaan)=?
                 ORDER BY tanggal_pemeriksaan DESC, id DESC",
                [$tahun]
            ),
            'byStatus' => db_all(
                "SELECT status, COUNT(*) total FROM stunting_data
                 WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY status",
                [$tahun]
            ),
            'byRt' => db_all(
                "SELECT rt, rw, COUNT(*) total,
                        SUM(status IN ('pendek','sangat_pendek','beresiko')) stunting
                 FROM stunting_data
                 WHERE YEAR(tanggal_pemeriksaan)=?
                 GROUP BY rt, rw ORDER BY rt, rw",
                [$tahun]
            ),
            'tahunList' => db_all(
                "SELECT DISTINCT YEAR(tanggal_pemeriksaan) tahun
                 FROM stunting_data ORDER BY tahun DESC"
            ),
        ]);
    }

    public function simpan(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/stunting');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $nama = mb_substr(trim((string) ($_POST['nama_inisial'] ?? '')), 0, 20, 'UTF-8');
        $lahir = trim((string) ($_POST['tanggal_lahir'] ?? ''));
        $periksa = trim((string) ($_POST['tanggal_pemeriksaan'] ?? ''));
        $tinggiRaw = str_replace(',', '.', trim((string) ($_POST['tinggi_cm'] ?? '')));
        $beratRaw = str_replace(',', '.', trim((string) ($_POST['berat_kg'] ?? '')));
        $errors = [];

        if ($nama === '') $errors[] = 'Inisial nama wajib diisi.';
        if (!$this->validDate($lahir)) $errors[] = 'Tanggal lahir tidak valid.';
        if (!$this->validDate($periksa)) $errors[] = 'Tanggal pemeriksaan tidak valid.';
        if ($this->validDate($lahir) && $this->validDate($periksa) && $periksa < $lahir) {
            $errors[] = 'Tanggal pemeriksaan tidak boleh lebih awal daripada tanggal lahir.';
        }
        if ($lahir > date('Y-m-d') || $periksa > date('Y-m-d')) {
            $errors[] = 'Tanggal lahir dan pemeriksaan tidak boleh berada di masa depan.';
        }
        if (!is_numeric($tinggiRaw) || (float) $tinggiRaw <= 0 || (float) $tinggiRaw > 250) {
            $errors[] = 'Tinggi badan harus lebih dari 0 dan maksimal 250 cm.';
        }
        if (!is_numeric($beratRaw) || (float) $beratRaw <= 0 || (float) $beratRaw > 300) {
            $errors[] = 'Berat badan harus lebih dari 0 dan maksimal 300 kg.';
        }
        if ($id > 0 && !db_val("SELECT id FROM stunting_data WHERE id = ?", [$id])) {
            $errors[] = 'Data pemeriksaan yang akan diperbarui tidak ditemukan.';
        }

        if ($errors) {
            flash('danger', implode(' ', array_unique($errors)));
            redirect('admin/stunting?tahun=' . ($this->validDate($periksa) ? date('Y', strtotime($periksa)) : date('Y')));
        }

        $data = [
            'nama_inisial' => $nama,
            'tanggal_lahir' => $lahir,
            'jenis_kelamin' => in_array($_POST['jenis_kelamin'] ?? '', ['L', 'P'], true)
                ? (string) $_POST['jenis_kelamin'] : 'L',
            'rt' => mb_substr(trim((string) ($_POST['rt'] ?? '')), 0, 5, 'UTF-8') ?: null,
            'rw' => mb_substr(trim((string) ($_POST['rw'] ?? '')), 0, 5, 'UTF-8') ?: null,
            'tinggi_cm' => round((float) $tinggiRaw, 2),
            'berat_kg' => round((float) $beratRaw, 2),
            'status' => in_array($_POST['status'] ?? '', ['normal', 'pendek', 'sangat_pendek', 'beresiko'], true)
                ? (string) $_POST['status'] : 'normal',
            'tindakan' => mb_substr(trim((string) ($_POST['tindakan'] ?? '')), 0, 255, 'UTF-8') ?: null,
            'tanggal_pemeriksaan' => $periksa,
        ];

        try {
            if ($id > 0) {
                db_exec(
                    "UPDATE stunting_data
                     SET nama_inisial=?, tanggal_lahir=?, jenis_kelamin=?, rt=?, rw=?,
                         tinggi_cm=?, berat_kg=?, status=?, tindakan=?, tanggal_pemeriksaan=?
                     WHERE id=?",
                    [$data['nama_inisial'], $data['tanggal_lahir'], $data['jenis_kelamin'],
                     $data['rt'], $data['rw'], $data['tinggi_cm'], $data['berat_kg'],
                     $data['status'], $data['tindakan'], $data['tanggal_pemeriksaan'], $id]
                );
            } else {
                $id = db_insert('stunting_data', $data + ['created_at' => date('Y-m-d H:i:s')]);
            }
        } catch (Throwable $e) {
            flash('danger', 'Data pemeriksaan gagal disimpan.');
            redirect('admin/stunting?tahun=' . date('Y', strtotime($periksa)));
        }

        log_activity('edit', 'stunting', "Simpan pemeriksaan stunting #$id");
        flash('success', 'Data pemeriksaan berhasil disimpan.');
        redirect('admin/stunting?tahun=' . date('Y', strtotime($periksa)));
    }

    public function hapus(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/stunting');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $changed = $id > 0 ? db_exec("DELETE FROM stunting_data WHERE id = ?", [$id]) : 0;
        flash($changed ? 'success' : 'warning', $changed ? 'Data berhasil dihapus.' : 'Data tidak ditemukan.');
        if ($changed) log_activity('hapus', 'stunting', "Hapus pemeriksaan #$id");
        redirect('admin/stunting');
    }

    public function cetak(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');
        redirect('transparansi/cetak?jenis=stunting&tahun=' . $tahun);
    }

    private function validDate(string $value): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year);
    }
}
