<?php

class KeluargaController extends Controller
{
    public array $actions = [
        'index', 'create', 'store', 'edit', 'update', 'delete', 'restore', 'trash',
        'detail', 'anggota_tambah', 'anggota_baru', 'anggota_update', 'anggota_hapus',
        'sinkronkan', 'resident_search',
    ];

    public function __construct()
    {
        Auth::requirePermission('kelola_penduduk');
        PopulationService::ensureSchema();
    }

    public function index(): void
    {
        $this->renderList(false);
    }

    public function trash(): void
    {
        $this->renderList(true);
    }

    private function renderList(bool $trashed): void
    {
        $where = [$trashed ? 'f.deleted_at IS NOT NULL' : 'f.deleted_at IS NULL'];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));

        if ($q !== '') {
            $where[] = "(f.no_kk LIKE ? OR f.kepala_keluarga LIKE ? OR f.alamat LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%");
        }
        if (!empty($_GET['dusun'])) {
            $where[] = 'f.dusun_id = ?';
            $params[] = (int) $_GET['dusun'];
        }
        if (!empty($_GET['status'])) {
            $where[] = 'f.status = ?';
            $params[] = in_array($_GET['status'], ['aktif', 'pindah', 'tidak_aktif'], true)
                ? $_GET['status']
                : 'aktif';
        }

        $sql = "SELECT f.*, d.name AS dusun_name, w.name AS rw_name, t.name AS rt_name,
                    (SELECT COUNT(*) FROM residents r
                     WHERE r.no_kk = f.no_kk AND r.deleted_at IS NULL) AS jumlah_anggota
                FROM families f
                LEFT JOIN regions d ON d.id = f.dusun_id
                LEFT JOIN regions w ON w.id = f.rw_id
                LEFT JOIN regions t ON t.id = f.rt_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY f.updated_at DESC, f.id DESC";

        $pg = paginate($sql, $params, 20);
        $this->adminView('keluarga_list', [
            'pageTitle' => $trashed ? 'Sampah Data Keluarga' : 'Data Keluarga',
            'pg' => $pg,
            'trashed' => $trashed,
            'health' => $trashed ? null : PopulationService::health(),
            'dusunList' => db_all("SELECT id, name FROM regions WHERE type = 'dusun' ORDER BY name"),
        ]);
    }

    public function detail($id = null): void
    {
        $familyId = (int) $id;
        $f = db_row(
            "SELECT f.*, d.name AS dusun_name, w.name AS rw_name, t.name AS rt_name
             FROM families f
             LEFT JOIN regions d ON d.id = f.dusun_id
             LEFT JOIN regions w ON w.id = f.rw_id
             LEFT JOIN regions t ON t.id = f.rt_id
             WHERE f.id = ? AND f.deleted_at IS NULL",
            [$familyId]
        );
        if (!$f) redirect('admin/keluarga');

        $members = db_all(
            "SELECT * FROM residents
             WHERE no_kk = ? AND deleted_at IS NULL
             ORDER BY FIELD(hubungan_keluarga, 'Kepala Keluarga', 'Istri', 'Suami', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'), tanggal_lahir, nama",
            [$f['no_kk']]
        );
        $memberOld = $_SESSION['member_new_old'] ?? [];
        unset($_SESSION['member_new_old']);

        $this->adminView('keluarga_detail', [
            'pageTitle' => 'Detail Keluarga',
            'f' => $f,
            'members' => $members,
            'memberOld' => $memberOld,
            'hubunganList' => PendudukController::HUBUNGAN,
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit($id = null): void
    {
        $f = db_row("SELECT * FROM families WHERE id = ?", [(int) $id]);
        if (!$f) redirect('admin/keluarga');
        $this->form($f);
    }

    private function form(?array $f): void
    {
        $currentHead = null;
        $oldHeadId = (int) ($_SESSION['form_old']['head_resident_id'] ?? 0);
        if ($oldHeadId > 0) {
            $currentHead = db_row(
                "SELECT id, nik, nama, no_kk, alamat, dusun_id, rw_id, rt_id
                 FROM residents
                 WHERE id = ? AND deleted_at IS NULL",
                [$oldHeadId]
            );
        } elseif ($f) {
            $currentHead = db_row(
                "SELECT id, nik, nama, no_kk, alamat, dusun_id, rw_id, rt_id
                 FROM residents
                 WHERE no_kk = ? AND deleted_at IS NULL
                   AND hubungan_keluarga = 'Kepala Keluarga'
                 ORDER BY id LIMIT 1",
                [$f['no_kk']]
            );
        }

        $this->adminView('keluarga_form', [
            'pageTitle' => ($f ? 'Edit' : 'Tambah') . ' Keluarga',
            'f' => $f,
            'currentHead' => $currentHead,
            'dusunList' => db_all("SELECT id, name FROM regions WHERE type = 'dusun' ORDER BY name"),
            'rwList' => db_all("SELECT id, name FROM regions WHERE type = 'rw' ORDER BY name"),
            'rtList' => db_all("SELECT id, name FROM regions WHERE type = 'rt' ORDER BY name"),
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update($id = null): void
    {
        $this->save((int) $id);
    }

    private function save(?int $id): void
    {
        verify_csrf();

        try {
            $result = PopulationService::saveFamily($id, $_POST, (int) (auth_user()['id'] ?? 0));
            unset($_SESSION['form_errors'], $_SESSION['form_old']);

            $familyId = (int) $result['family_id'];
            $family = db_row("SELECT no_kk, kepala_keluarga FROM families WHERE id = ?", [$familyId]);
            $action = $id === null ? 'tambah' : 'edit';
            log_activity($action, 'keluarga', ucfirst($action) . ' KK ' . ($family['no_kk'] ?? '-'));

            $message = $id === null
                ? 'Data keluarga berhasil ditambahkan.'
                : 'Data keluarga berhasil diperbarui.';
            if (!empty($result['resident_id'])) {
                $message .= ' Kepala keluarga juga dibuat sebagai data penduduk dan langsung dihubungkan.';
            } else {
                $message .= ' Relasi anggota keluarga telah disinkronkan otomatis.';
            }
            flash('success', $message);
            redirect('admin/keluarga/detail/' . $familyId);
        } catch (PopulationValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['form_old'] = $_POST;
            redirect('admin/keluarga' . ($id ? "/edit/$id" : '/create'));
        } catch (Throwable $e) {
            error_log('Simpan keluarga gagal: ' . $e->getMessage());
            $_SESSION['form_errors'] = [
                'Data keluarga belum tersimpan karena terjadi kendala database. Seluruh perubahan telah dibatalkan otomatis.',
            ];
            $_SESSION['form_old'] = $_POST;
            redirect('admin/keluarga' . ($id ? "/edit/$id" : '/create'));
        }
    }

    public function delete($id = null): void
    {
        verify_csrf();
        $familyId = (int) $id;

        try {
            PopulationService::deleteFamily($familyId);
            log_activity('hapus', 'keluarga', "Pindahkan keluarga #$familyId ke sampah");
            flash('success', 'Keluarga kosong dipindahkan ke sampah.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Hapus keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Data keluarga belum dapat dihapus. Tidak ada perubahan sebagian yang disimpan.');
        }
        redirect('admin/keluarga');
    }

    public function restore($id = null): void
    {
        verify_csrf();
        $familyId = (int) $id;

        try {
            PopulationService::restoreFamily($familyId);
            log_activity('restore', 'keluarga', "Pulihkan keluarga #$familyId");
            flash('success', 'Data keluarga berhasil dipulihkan.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Pulihkan keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Data keluarga belum dapat dipulihkan.');
        }
        redirect('admin/keluarga/trash');
    }

    public function anggota_tambah(): void
    {
        verify_csrf();
        $familyId = (int) ($_POST['family_id'] ?? 0);
        $residentId = (int) ($_POST['resident_id'] ?? 0);
        $relationship = trim((string) ($_POST['hubungan'] ?? 'Famili Lain'));

        try {
            PopulationService::addMember($familyId, $residentId, $relationship, (int) (auth_user()['id'] ?? 0));
            log_activity('edit', 'keluarga', "Tambah penduduk #$residentId ke keluarga #$familyId");
            flash('success', 'Anggota berhasil ditambahkan. Nomor KK dan relasi keluarga sudah diperbarui otomatis.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Tambah anggota keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Anggota belum dapat ditambahkan. Seluruh perubahan telah dibatalkan.');
        }
        redirect('admin/keluarga/detail/' . $familyId);
    }

    public function anggota_baru(): void
    {
        verify_csrf();
        $familyId = (int) ($_POST['family_id'] ?? 0);
        $relationship = trim((string) ($_POST['hubungan'] ?? 'Anak'));
        $userId = (int) (auth_user()['id'] ?? 0);

        try {
            if (!db_val("SELECT id FROM families WHERE id = ? AND deleted_at IS NULL", [$familyId])) {
                throw new PopulationValidationException(['Data keluarga tidak ditemukan.']);
            }
            $residentId = PopulationService::saveResident(null, [
                'nik' => $_POST['nik'] ?? '',
                'nama' => $_POST['nama'] ?? '',
                'jenis_kelamin' => $_POST['jenis_kelamin'] ?? 'L',
                'tempat_lahir' => $_POST['tempat_lahir'] ?? '',
                'tanggal_lahir' => $_POST['tanggal_lahir'] ?? '',
                'family_id' => $familyId,
                'inherit_family' => 1,
                'hubungan_keluarga' => in_array($relationship, PendudukController::HUBUNGAN, true) ? $relationship : 'Famili Lain',
            ], $userId);
            PopulationService::addMember($familyId, $residentId, $relationship, $userId);
            log_activity('tambah', 'keluarga', "Buat penduduk #$residentId langsung di keluarga #$familyId");
            flash('success', 'Anggota baru berhasil dibuat dan langsung masuk ke KK ini. Alamat & wilayah mengikuti data keluarga.');
        } catch (PopulationValidationException $e) {
            $_SESSION['member_new_old'] = array_intersect_key($_POST, array_flip(['nik', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'hubungan']));
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Buat anggota keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Anggota baru belum dapat dibuat. Seluruh perubahan telah dibatalkan.');
        }
        redirect('admin/keluarga/detail/' . $familyId . '#tambah-anggota');
    }

    public function anggota_update($residentId = null): void
    {
        verify_csrf();
        $familyId = (int) ($_POST['family_id'] ?? 0);
        $relationship = trim((string) ($_POST['hubungan'] ?? 'Famili Lain'));

        try {
            PopulationService::updateMember($familyId, (int) $residentId, $relationship, (int) (auth_user()['id'] ?? 0));
            log_activity('edit', 'keluarga', "Ubah hubungan penduduk #$residentId pada keluarga #$familyId");
            flash('success', 'Hubungan keluarga berhasil diperbarui.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Ubah anggota keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Hubungan keluarga belum dapat diperbarui.');
        }
        redirect('admin/keluarga/detail/' . $familyId);
    }

    public function anggota_hapus($residentId = null): void
    {
        verify_csrf();
        $familyId = (int) ($_POST['family_id'] ?? 0);

        try {
            PopulationService::removeMember($familyId, (int) $residentId, (int) (auth_user()['id'] ?? 0));
            log_activity('edit', 'keluarga', "Keluarkan penduduk #$residentId dari keluarga #$familyId");
            flash('success', 'Anggota dikeluarkan dari keluarga. Nomor KK pada data penduduk dikosongkan.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Hapus anggota keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Anggota belum dapat dikeluarkan.');
        }
        redirect('admin/keluarga/detail/' . $familyId);
    }

    public function sinkronkan(): void
    {
        verify_csrf();

        try {
            $result = PopulationService::syncAll((int) (auth_user()['id'] ?? 0));
            log_activity(
                'sinkronisasi',
                'keluarga',
                'Sinkronisasi penduduk-keluarga: ' . $result['member_links'] . ' relasi anggota'
            );
            flash(
                'success',
                'Sinkronisasi selesai: ' . (int) $result['created_families'] . ' keluarga dibuat, '
                . (int) $result['restored_families'] . ' dipulihkan, dan '
                . (int) $result['member_links'] . ' relasi anggota dibangun ulang.'
            );
        } catch (Throwable $e) {
            error_log('Sinkronisasi keluarga gagal: ' . $e->getMessage());
            flash('danger', 'Sinkronisasi gagal dan seluruh perubahan telah dibatalkan otomatis.');
        }
        redirect('admin/keluarga');
    }

    public function resident_search(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        if (self::textLength($q) < 2) {
            $this->json(['ok' => true, 'data' => []]);
            return;
        }

        $like = '%' . $q . '%';
        $rows = db_all(
            "SELECT id, nik, no_kk, nama, alamat, dusun_id, rw_id, rt_id, hubungan_keluarga
             FROM residents
             WHERE deleted_at IS NULL
               AND (nama LIKE ? OR nik LIKE ? OR no_kk LIKE ?)
             ORDER BY nama
             LIMIT 20",
            [$like, $like, $like]
        );
        $this->json(['ok' => true, 'data' => $rows]);
    }

    private static function textLength(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    }
}
