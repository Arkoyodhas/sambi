<?php

class BansosController extends Controller {

    public array $actions = ['index', 'simpan_program', 'hapus_program', 'simpan_penerima', 'hapus_penerima', 'cetak'];

    public function __construct() { Auth::requirePermission('kelola_konten'); }

    public function index(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');
        $programId = max(0, (int) ($_GET['program'] ?? 0));
        $program = $programId > 0
            ? db_row("SELECT id, tahun FROM bansos_programs WHERE id = ?", [$programId])
            : null;
        if ($programId > 0 && !$program) $programId = 0;

        $this->adminView('bansos_admin', [
            'pageTitle' => 'Kelola Bansos',
            'tahun' => $tahun,
            'programId' => $programId,
            'programs' => db_all(
                "SELECT p.*, COUNT(r.id) jml
                 FROM bansos_programs p
                 LEFT JOIN bansos_recipients r ON r.program_id=p.id
                 WHERE p.tahun=?
                 GROUP BY p.id
                 ORDER BY p.nama ASC",
                [$tahun]
            ),
            'recipients' => $programId > 0
                ? db_all("SELECT * FROM bansos_recipients WHERE program_id=? ORDER BY nama ASC", [$programId])
                : [],
            'tahunList' => db_all("SELECT DISTINCT tahun FROM bansos_programs ORDER BY tahun DESC"),
        ]);
    }

    public function simpan_program(): void {
        $this->requirePost('admin/bansos');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $isNew = $id === 0;
        $tahun = (int) ($_POST['tahun'] ?? date('Y'));
        $nama = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 150, 'UTF-8');
        $errors = [];
        if ($nama === '') $errors[] = 'Nama program wajib diisi.';
        if ($tahun < 2000 || $tahun > 2100) $errors[] = 'Tahun program tidak valid.';

        if ($id > 0 && !db_val("SELECT id FROM bansos_programs WHERE id = ?", [$id])) {
            $errors[] = 'Program yang akan diperbarui tidak ditemukan.';
        }
        if ($nama !== '' && db_val(
            "SELECT id FROM bansos_programs WHERE nama = ? AND tahun = ? AND id != ? LIMIT 1",
            [$nama, $tahun, $id]
        )) {
            $errors[] = 'Program dengan nama dan tahun yang sama sudah tersedia.';
        }

        if ($errors) {
            flash('danger', implode(' ', $errors));
            redirect('admin/bansos?tahun=' . ($tahun >= 2000 && $tahun <= 2100 ? $tahun : date('Y')));
        }

        $data = [
            'nama' => $nama,
            'deskripsi' => mb_substr(trim((string) ($_POST['deskripsi'] ?? '')), 0, 1000, 'UTF-8') ?: null,
            'sumber_dana' => mb_substr(trim((string) ($_POST['sumber_dana'] ?? '')), 0, 100, 'UTF-8') ?: null,
            'tahun' => $tahun,
            'nilai_per_penerima' => max(0, (int) preg_replace('/\D/', '', (string) ($_POST['nilai_per_penerima'] ?? 0))),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        try {
            if ($id > 0) {
                db_exec(
                    "UPDATE bansos_programs
                     SET nama=?, deskripsi=?, sumber_dana=?, tahun=?, nilai_per_penerima=?, is_active=?
                     WHERE id=?",
                    [$data['nama'], $data['deskripsi'], $data['sumber_dana'], $data['tahun'],
                     $data['nilai_per_penerima'], $data['is_active'], $id]
                );
            } else {
                $id = db_insert('bansos_programs', $data);
            }
        } catch (Throwable $e) {
            flash('danger', 'Program bansos gagal disimpan. Periksa data dan koneksi database.');
            redirect('admin/bansos?tahun=' . $tahun);
        }

        log_activity($isNew ? 'tambah' : 'edit', 'bansos', "Program bansos: $nama ($tahun)");
        flash('success', 'Program bansos berhasil disimpan.');
        redirect('admin/bansos?tahun=' . $tahun);
    }

    public function hapus_program(): void {
        $this->requirePost('admin/bansos');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $program = $id > 0 ? db_row("SELECT id, nama FROM bansos_programs WHERE id = ?", [$id]) : null;
        if (!$program) {
            flash('warning', 'Program bansos tidak ditemukan.');
            redirect('admin/bansos');
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_exec("DELETE FROM bansos_recipients WHERE program_id = ?", [$id]);
            db_exec("DELETE FROM bansos_programs WHERE id = ?", [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Program bansos gagal dihapus.');
            redirect('admin/bansos');
        }

        log_activity('hapus', 'bansos', 'Hapus program bansos: ' . $program['nama']);
        flash('success', 'Program dan daftar penerimanya berhasil dihapus.');
        redirect('admin/bansos');
    }

    public function simpan_penerima(): void {
        $this->requirePost('admin/bansos');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $programId = max(0, (int) ($_POST['program_id'] ?? 0));
        $program = $programId > 0
            ? db_row("SELECT id, tahun FROM bansos_programs WHERE id = ?", [$programId])
            : null;
        if (!$program) {
            flash('danger', 'Program bansos tidak valid atau sudah dihapus.');
            redirect('admin/bansos');
        }

        $nama = mb_substr(trim((string) ($_POST['nama'] ?? '')), 0, 150, 'UTF-8');
        $nikRaw = preg_replace('/\D/', '', (string) ($_POST['nik'] ?? '')) ?: '';
        $errors = [];
        if ($nama === '') $errors[] = 'Nama penerima wajib diisi.';
        if ($nikRaw !== '' && strlen($nikRaw) !== 16) $errors[] = 'NIK harus tepat 16 digit.';
        if ($id > 0 && !db_val("SELECT id FROM bansos_recipients WHERE id = ?", [$id])) {
            $errors[] = 'Penerima yang akan diperbarui tidak ditemukan.';
        }
        if ($nikRaw !== '' && db_val(
            "SELECT id FROM bansos_recipients WHERE program_id = ? AND nik = ? AND id != ? LIMIT 1",
            [$programId, $nikRaw, $id]
        )) {
            $errors[] = 'NIK tersebut sudah terdaftar pada program yang sama.';
        }

        if ($errors) {
            flash('danger', implode(' ', $errors));
            redirect('admin/bansos?tahun=' . $program['tahun'] . '&program=' . $programId);
        }

        $data = [
            'program_id' => $programId,
            'nama' => $nama,
            'nik' => $nikRaw !== '' ? $nikRaw : null,
            'alamat' => mb_substr(trim((string) ($_POST['alamat'] ?? '')), 0, 200, 'UTF-8') ?: null,
            'rt' => mb_substr(trim((string) ($_POST['rt'] ?? '')), 0, 5, 'UTF-8') ?: null,
            'rw' => mb_substr(trim((string) ($_POST['rw'] ?? '')), 0, 5, 'UTF-8') ?: null,
            'keterangan' => mb_substr(trim((string) ($_POST['keterangan'] ?? '')), 0, 200, 'UTF-8') ?: null,
        ];

        try {
            if ($id > 0) {
                db_exec(
                    "UPDATE bansos_recipients
                     SET program_id=?, nama=?, nik=?, alamat=?, rt=?, rw=?, keterangan=?
                     WHERE id=?",
                    [$data['program_id'], $data['nama'], $data['nik'], $data['alamat'],
                     $data['rt'], $data['rw'], $data['keterangan'], $id]
                );
            } else {
                $id = db_insert('bansos_recipients', $data + ['created_at' => date('Y-m-d H:i:s')]);
            }
        } catch (Throwable $e) {
            flash('danger', 'Data penerima gagal disimpan.');
            redirect('admin/bansos?tahun=' . $program['tahun'] . '&program=' . $programId);
        }

        log_activity('edit', 'bansos', "Simpan penerima #$id pada program #$programId");
        flash('success', 'Data penerima berhasil disimpan.');
        redirect('admin/bansos?tahun=' . $program['tahun'] . '&program=' . $programId);
    }

    public function hapus_penerima(): void {
        $this->requirePost('admin/bansos');
        verify_csrf();

        $id = max(0, (int) ($_POST['id'] ?? 0));
        $programId = max(0, (int) ($_POST['program_id'] ?? 0));
        $row = $id > 0 ? db_row("SELECT id, program_id FROM bansos_recipients WHERE id = ?", [$id]) : null;
        if (!$row) {
            flash('warning', 'Penerima tidak ditemukan.');
            redirect('admin/bansos?program=' . $programId);
        }
        db_exec("DELETE FROM bansos_recipients WHERE id = ?", [$id]);
        log_activity('hapus', 'bansos', "Hapus penerima #$id");
        flash('success', 'Penerima berhasil dihapus.');
        redirect('admin/bansos?program=' . (int) $row['program_id']);
    }

    public function cetak(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        if ($tahun < 2000 || $tahun > 2100) $tahun = (int) date('Y');
        redirect('transparansi/cetak?jenis=bansos&tahun=' . $tahun);
    }

    private function requirePost(string $fallback): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect($fallback);
    }
}
