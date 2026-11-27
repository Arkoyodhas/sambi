<?php

class PendudukController extends Controller {

    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'restore', 'trash', 'detail', 'family_search', 'export', 'cetak', 'template', 'import', 'preview', 'import_simpan'];

    const FIELDS = ['nik', 'no_kk', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'pendidikan', 'pekerjaan', 'status_perkawinan', 'hubungan_keluarga', 'alamat', 'dusun', 'rw', 'rt', 'status_penduduk', 'no_hp', 'email', 'keterangan'];
    const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan'];
    const PENDIDIKAN = ['Belum Sekolah', 'Tidak Sekolah', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat', 'D1', 'D2', 'D3', 'S1', 'S2', 'S3'];
    const KAWIN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];
    const HUBUNGAN = ['Kepala Keluarga', 'Istri', 'Suami', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'];

    public function __construct() {
        Auth::requirePermission('kelola_penduduk');
        PopulationService::ensureSchema();
    }

    private function filters(bool $trashed = false): array {
        $where = [$trashed ? 'r.deleted_at IS NOT NULL' : 'r.deleted_at IS NULL'];
        $params = [];

        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = "(r.nama LIKE ? OR r.nik LIKE ? OR r.no_kk LIKE ?)";
            array_push($params, "%$q%", "%$q%", "%$q%");
        }

        // Filter dusun memakai nama, bukan hanya ID. Basis data lama dapat
        // memiliki beberapa ID dusun dengan nama yang sama; pendekatan ini
        // memastikan seluruh RT dalam dusun tersebut ikut tampil.
        $dusun = trim((string) ($_GET['dusun'] ?? ''));
        if ($dusun !== '') {
            if ($dusun === '__unmapped__') {
                $where[] = "(r.dusun_id IS NULL OR d.id IS NULL)";
            } elseif (ctype_digit($dusun)) {
                // Kompatibilitas tautan/filter versi lama yang masih mengirim ID.
                $dusunName = (string) db_val(
                    "SELECT name FROM regions WHERE id = ? AND type = 'dusun' LIMIT 1",
                    [(int) $dusun]
                );
                if ($dusunName !== '') {
                    $where[] = "d.name = ?";
                    $params[] = $dusunName;
                } else {
                    $where[] = "r.dusun_id = ?";
                    $params[] = (int) $dusun;
                }
            } else {
                $where[] = "d.name = ?";
                $params[] = $dusun;
            }
        }

        foreach (['rw' => 'rw_id', 'rt' => 'rt_id'] as $key => $column) {
            $value = trim((string) ($_GET[$key] ?? ''));
            if ($value !== '' && ctype_digit($value)) {
                $where[] = "r.$column = ?";
                $params[] = (int) $value;
            }
        }

        if (!empty($_GET['jk'])) {
            $where[] = "r.jenis_kelamin = ?";
            $params[] = $_GET['jk'] === 'P' ? 'P' : 'L';
        }
        if (!empty($_GET['status'])) {
            $allowed = ['hidup', 'meninggal', 'pindah', 'tidak_aktif'];
            $status = (string) $_GET['status'];
            if (in_array($status, $allowed, true)) {
                $where[] = "r.status_penduduk = ?";
                $params[] = $status;
            }
        }
        if (!empty($_GET['pendidikan'])) {
            $where[] = "r.pendidikan = ?";
            $params[] = (string) $_GET['pendidikan'];
        }
        if (!empty($_GET['pekerjaan'])) {
            $where[] = "r.pekerjaan LIKE ?";
            $params[] = '%' . trim((string) $_GET['pekerjaan']) . '%';
        }

        if (isset($_GET['umur_min']) && $_GET['umur_min'] !== '') {
            $umurMin = max(0, min(150, (int) $_GET['umur_min']));
            $where[] = "r.tanggal_lahir IS NOT NULL AND TIMESTAMPDIFF(YEAR, r.tanggal_lahir, CURDATE()) >= ?";
            $params[] = $umurMin;
        }
        if (isset($_GET['umur_max']) && $_GET['umur_max'] !== '') {
            $umurMax = max(0, min(150, (int) $_GET['umur_max']));
            $where[] = "r.tanggal_lahir IS NOT NULL AND TIMESTAMPDIFF(YEAR, r.tanggal_lahir, CURDATE()) <= ?";
            $params[] = $umurMax;
        }

        return [$where, $params];
    }

    private function perPage(): int {
        $requested = (int) ($_GET['per_page'] ?? 20);
        return in_array($requested, [20, 50, 100], true) ? $requested : 20;
    }

    private function listSql(array $where): string {
        return "SELECT r.*, d.name AS dusun_name, w.name AS rw_name, t.name AS rt_name
            FROM residents r
            LEFT JOIN regions d ON d.id = r.dusun_id
            LEFT JOIN regions w ON w.id = r.rw_id
            LEFT JOIN regions t ON t.id = r.rt_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                CASE WHEN d.name IS NULL OR d.name = '' THEN 1 ELSE 0 END,
                d.name ASC,
                CASE WHEN w.name IS NULL OR w.name = '' THEN 1 ELSE 0 END,
                w.name ASC,
                CASE WHEN t.name IS NULL OR t.name = '' THEN 1 ELSE 0 END,
                t.name ASC,
                r.nama ASC";
    }

    private function filterStats(array $where, array $params): array {
        $row = db_row(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN r.jenis_kelamin = 'L' THEN 1 ELSE 0 END) AS laki_laki,
                SUM(CASE WHEN r.jenis_kelamin = 'P' THEN 1 ELSE 0 END) AS perempuan,
                COUNT(DISTINCT CASE WHEN r.no_kk IS NOT NULL AND r.no_kk <> '' THEN r.no_kk END) AS keluarga,
                COUNT(DISTINCT d.name) AS jumlah_dusun,
                COUNT(DISTINCT r.rw_id) AS jumlah_rw,
                COUNT(DISTINCT r.rt_id) AS jumlah_rt
             FROM residents r
             LEFT JOIN regions d ON d.id = r.dusun_id
             LEFT JOIN regions w ON w.id = r.rw_id
             LEFT JOIN regions t ON t.id = r.rt_id
             WHERE " . implode(' AND ', $where),
            $params
        ) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'laki_laki' => (int) ($row['laki_laki'] ?? 0),
            'perempuan' => (int) ($row['perempuan'] ?? 0),
            'keluarga' => (int) ($row['keluarga'] ?? 0),
            'jumlah_dusun' => (int) ($row['jumlah_dusun'] ?? 0),
            'jumlah_rw' => (int) ($row['jumlah_rw'] ?? 0),
            'jumlah_rt' => (int) ($row['jumlah_rt'] ?? 0),
        ];
    }

    public function index(): void {
        [$where, $params] = $this->filters();
        $pg = paginate($this->listSql($where), $params, $this->perPage());
        $this->adminView('penduduk_list', [
            'pageTitle' => 'Data Penduduk',
            'pg' => $pg,
            'trashed' => false,
            'filterStats' => $this->filterStats($where, $params),
        ] + $this->refs());
    }

    public function trash(): void {
        [$where, $params] = $this->filters(true);
        $pg = paginate($this->listSql($where), $params, $this->perPage());
        $this->adminView('penduduk_list', [
            'pageTitle' => 'Sampah Data Penduduk',
            'pg' => $pg,
            'trashed' => true,
            'filterStats' => $this->filterStats($where, $params),
        ] + $this->refs());
    }

    /**
     * Menyiapkan referensi form dan struktur filter wilayah.
     *
     * Struktur filter diturunkan dari kombinasi wilayah pada data penduduk,
     * lalu dilengkapi dengan master wilayah. Dengan cara ini database lama
     * yang belum mempunyai parent_id sempurna tetap dapat difilter dengan benar.
     */
    private function baseRefs(): array {
        return [
            'dusunList' => db_all("SELECT id, name FROM regions WHERE type='dusun' ORDER BY name, id"),
            'rwList' => db_all("SELECT id, name FROM regions WHERE type='rw' ORDER BY name, id"),
            'rtList' => db_all("SELECT id, name FROM regions WHERE type='rt' ORDER BY name, id"),
        ];
    }

    private function refs(): array {
        $baseRefs = $this->baseRefs();

        // Nama dusun digabung berdasarkan nama agar database lama yang memiliki
        // beberapa ID untuk dusun yang sama tetap muncul sebagai satu pilihan.
        $dusunRows = db_all(
            "SELECT
                dn.name AS dusun_name,
                COUNT(r.id) AS total
             FROM (
                SELECT DISTINCT name
                FROM regions
                WHERE type = 'dusun' AND name IS NOT NULL AND name <> ''
             ) dn
             LEFT JOIN regions d ON d.type = 'dusun' AND d.name = dn.name
             LEFT JOIN residents r ON r.dusun_id = d.id AND r.deleted_at IS NULL
             GROUP BY dn.name
             ORDER BY dn.name"
        );

        $filterDusun = [];
        foreach ($dusunRows as $row) {
            $filterDusun[] = [
                'key' => (string) $row['dusun_name'],
                'name' => (string) $row['dusun_name'],
                'jumlah' => (int) ($row['total'] ?? 0),
            ];
        }

        $unmappedTotal = (int) db_val(
            "SELECT COUNT(*)
             FROM residents r
             LEFT JOIN regions d ON d.id = r.dusun_id
             WHERE r.deleted_at IS NULL
               AND (r.dusun_id IS NULL OR d.id IS NULL)"
        );
        if ($unmappedTotal > 0) {
            $filterDusun[] = [
                'key' => '__unmapped__',
                'name' => 'Belum Terpetakan',
                'jumlah' => $unmappedTotal,
            ];
        }

        // Daftar RT dibentuk dari data penduduk agar relasi lama tetap terbaca.
        $rtRows = db_all(
            "SELECT
                COALESCE(d.name, '__unmapped__') AS dusun_key,
                t.id AS rt_id,
                t.name AS rt_name,
                COUNT(*) AS jumlah
             FROM residents r
             LEFT JOIN regions d ON d.id = r.dusun_id
             LEFT JOIN regions t ON t.id = r.rt_id
             WHERE r.deleted_at IS NULL
               AND t.id IS NOT NULL
             GROUP BY COALESCE(d.name, '__unmapped__'), t.id, t.name
             ORDER BY COALESCE(d.name, '__unmapped__'), t.name, t.id"
        );

        $filterRt = [];
        $knownRtIds = [];
        foreach ($rtRows as $row) {
            $rtId = (int) ($row['rt_id'] ?? 0);
            if ($rtId <= 0) {
                continue;
            }
            $dusunKey = (string) $row['dusun_key'];
            $key = $rtId . '|' . $dusunKey;
            $filterRt[$key] = [
                'id' => $rtId,
                'name' => (string) ($row['rt_name'] ?: ('RT #' . $rtId)),
                'dusun' => $dusunKey,
                'jumlah' => (int) ($row['jumlah'] ?? 0),
            ];
            $knownRtIds[$rtId] = true;
        }

        // RT kosong tetap ditampilkan bila hubungan parent wilayahnya jelas.
        $masterRt = db_all(
            "SELECT
                t.id,
                t.name,
                p.type AS parent_type,
                p.name AS parent_name,
                g.type AS grand_type,
                g.name AS grand_name
             FROM regions t
             LEFT JOIN regions p ON p.id = t.parent_id
             LEFT JOIN regions g ON g.id = p.parent_id
             WHERE t.type = 'rt'
             ORDER BY t.name, t.id"
        );
        foreach ($masterRt as $row) {
            $rtId = (int) $row['id'];
            if ($rtId <= 0 || isset($knownRtIds[$rtId])) {
                continue;
            }

            $dusunKey = '';
            if (($row['parent_type'] ?? '') === 'dusun') {
                $dusunKey = (string) $row['parent_name'];
            } elseif (($row['grand_type'] ?? '') === 'dusun') {
                $dusunKey = (string) $row['grand_name'];
            }
            if ($dusunKey === '') {
                continue;
            }

            $key = $rtId . '|' . $dusunKey;
            $filterRt[$key] = [
                'id' => $rtId,
                'name' => (string) $row['name'],
                'dusun' => $dusunKey,
                'jumlah' => 0,
            ];
        }

        $filterRt = array_values($filterRt);
        usort($filterRt, static function (array $a, array $b): int {
            return [$a['dusun'], $a['name'], $a['id']] <=> [$b['dusun'], $b['name'], $b['id']];
        });

        return $baseRefs + [
            'filterDusunList' => $filterDusun,
            'filterRtList' => $filterRt,
        ];
    }

    private function formRefs(?array $resident = null): array {
        $oldFamilyId = (int) ($_SESSION['form_old']['family_id'] ?? 0);
        $requestedFamilyId = (int) ($_GET['family_id'] ?? 0);
        $selectedFamilyId = $oldFamilyId ?: $requestedFamilyId;

        if ($selectedFamilyId <= 0 && !empty($resident['no_kk'])) {
            $selectedFamilyId = (int) db_val(
                "SELECT id FROM families WHERE no_kk = ? AND deleted_at IS NULL LIMIT 1",
                [$resident['no_kk']]
            );
        }

        $selectedFamily = $selectedFamilyId > 0
            ? db_row(
                "SELECT id, no_kk, kepala_keluarga, alamat, dusun_id, rw_id, rt_id, status
                 FROM families
                 WHERE id = ? AND deleted_at IS NULL",
                [$selectedFamilyId]
            )
            : null;

        return $this->baseRefs() + [
            'selectedFamily' => $selectedFamily,
        ];
    }

    public function detail($id = null): void {
        $r = db_row($this->listSql(['r.id = ?']), [(int) $id]);
        if (!$r) redirect('admin/penduduk');
        $riwayat = db_all("SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id
            WHERE l.module='penduduk' AND l.description LIKE ? ORDER BY l.id DESC LIMIT 20", ['%NIK ' . $r['nik'] . '%']);
        $familyId = !empty($r['no_kk'])
            ? (int) db_val("SELECT id FROM families WHERE no_kk = ? AND deleted_at IS NULL LIMIT 1", [$r['no_kk']])
            : 0;
        $this->adminView('penduduk_detail', [
            'pageTitle' => 'Detail Penduduk',
            'r' => $r,
            'riwayat' => $riwayat,
            'familyId' => $familyId,
        ]);
    }

    public function family_search(): void {
        $q = trim((string) ($_GET['q'] ?? ''));
        $length = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
        if ($length < 2) {
            $this->json(['ok' => true, 'data' => []]);
            return;
        }

        $like = '%' . $q . '%';
        $rows = db_all(
            "SELECT f.id, f.no_kk, f.kepala_keluarga, f.alamat,
                    f.dusun_id, f.rw_id, f.rt_id, f.status,
                    (SELECT COUNT(*) FROM residents r
                     WHERE r.no_kk = f.no_kk AND r.deleted_at IS NULL) AS jumlah_anggota
             FROM families f
             WHERE f.deleted_at IS NULL
               AND (f.no_kk LIKE ? OR f.kepala_keluarga LIKE ? OR f.alamat LIKE ?)
             ORDER BY
               CASE WHEN f.no_kk = ? THEN 0 ELSE 1 END,
               f.kepala_keluarga IS NULL,
               f.kepala_keluarga,
               f.no_kk
             LIMIT 20",
            [$like, $like, $like, $q]
        );
        $this->json(['ok' => true, 'data' => $rows]);
    }

    public function create(): void {
        $this->adminView('penduduk_form', ['pageTitle' => 'Tambah Penduduk', 'r' => null] + $this->formRefs());
    }

    public function edit($id = null): void {
        $r = db_row("SELECT * FROM residents WHERE id = ?", [(int) $id]);
        if (!$r) redirect('admin/penduduk');
        $this->adminView('penduduk_form', ['pageTitle' => 'Edit Penduduk', 'r' => $r] + $this->formRefs($r));
    }

    public function store(): void { $this->save(null); }
    public function update($id = null): void { $this->save((int) $id); }

    private function save(?int $id): void {
        verify_csrf();

        try {
            $savedId = PopulationService::saveResident($id, $_POST, (int) (auth_user()['id'] ?? 0));
            unset($_SESSION['form_errors'], $_SESSION['form_old']);

            $resident = db_row("SELECT nik, nama, no_kk FROM residents WHERE id = ?", [$savedId]);
            $action = $id === null ? 'tambah' : 'edit';
            log_activity($action, 'penduduk', ucfirst($action) . ' penduduk NIK ' . ($resident['nik'] ?? '-') . ' (' . ($resident['nama'] ?? '-') . ')');

            $message = $id === null ? 'Data penduduk berhasil ditambahkan.' : 'Data penduduk berhasil diperbarui.';
            if (!empty($resident['no_kk'])) {
                $message .= ' Data keluarga dan anggota KK telah disinkronkan otomatis.';
            }
            flash('success', $message);
            redirect('admin/penduduk/detail/' . $savedId);
        } catch (PopulationValidationException $e) {
            $_SESSION['form_errors'] = $e->errors;
            $_SESSION['form_old'] = $_POST;
            redirect('admin/penduduk' . ($id ? "/edit/$id" : '/create'));
        } catch (Throwable $e) {
            error_log('Simpan penduduk gagal: ' . $e->getMessage());
            $_SESSION['form_errors'] = ['Data belum tersimpan karena terjadi kendala database. Tidak ada perubahan sebagian yang disimpan.'];
            $_SESSION['form_old'] = $_POST;
            redirect('admin/penduduk' . ($id ? "/edit/$id" : '/create'));
        }
    }

    public function delete($id = null): void {
        verify_csrf();
        $residentId = (int) $id;
        $r = db_row("SELECT nik, nama FROM residents WHERE id = ?", [$residentId]);

        try {
            PopulationService::deleteResident($residentId, (int) (auth_user()['id'] ?? 0));
            log_activity('hapus', 'penduduk', 'Hapus penduduk NIK ' . ($r['nik'] ?? $residentId));
            flash('success', 'Data dipindahkan ke sampah dan relasi keluarga diperbarui otomatis.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Hapus penduduk gagal: ' . $e->getMessage());
            flash('danger', 'Data belum dapat dihapus. Tidak ada perubahan sebagian yang disimpan.');
        }
        redirect('admin/penduduk');
    }

    public function restore($id = null): void {
        verify_csrf();
        $residentId = (int) $id;

        try {
            PopulationService::restoreResident($residentId, (int) (auth_user()['id'] ?? 0));
            log_activity('restore', 'penduduk', "Pulihkan penduduk #$residentId");
            flash('success', 'Data berhasil dipulihkan dan relasi keluarga disinkronkan.');
        } catch (PopulationValidationException $e) {
            flash('danger', e(implode(' ', $e->errors)));
        } catch (Throwable $e) {
            error_log('Pulihkan penduduk gagal: ' . $e->getMessage());
            flash('danger', 'Data belum dapat dipulihkan.');
        }
        redirect('admin/penduduk/trash');
    }

    /* ---------- EXPORT ---------- */
    public function export(): void {
        Auth::requirePermission('export_penduduk');
        [$where, $params] = $this->filters();
        $rows = db_all($this->listSql($where), $params);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [$r['nik'], $r['no_kk'], $r['nama'], $r['tempat_lahir'], $r['tanggal_lahir'], $r['jenis_kelamin'],
                $r['agama'], $r['pendidikan'], $r['pekerjaan'], $r['status_perkawinan'], $r['hubungan_keluarga'],
                $r['alamat'], $r['dusun_name'], $r['rw_name'], $r['rt_name'], $r['status_penduduk'], $r['no_hp'], $r['email'], $r['keterangan']];
        }
        log_activity('export', 'penduduk', 'Export ' . count($out) . ' data penduduk (Excel)');
        XLSXWriter::download('data-penduduk-' . date('Ymd') . '.xlsx', array_map('strtoupper', self::FIELDS), $out);
    }

    public function cetak(): void {
        Auth::requirePermission('export_penduduk');
        [$where, $params] = $this->filters();
        $rows = db_all($this->listSql($where), $params);
        log_activity('export', 'penduduk', 'Cetak PDF ' . count($rows) . ' data penduduk');
        $this->raw('admin/penduduk_cetak', ['rows' => $rows, 'pageTitle' => 'Cetak Data Penduduk']);
    }

    public function template(): void {
        Auth::requirePermission('import_penduduk');
        $contoh = [
            ['3201010101900001', '3201010101900001', 'Contoh Nama Warga', 'Bandung', '1990-01-31', 'L', 'Islam', 'SMA/Sederajat', 'Petani', 'Kawin', 'Kepala Keluarga', 'Jl. Contoh No. 1', 'Dusun Krajan', 'RW 01', 'RT 01', 'hidup', '6281234567890', '', ''],
        ];
        XLSXWriter::download('template-import-penduduk.xlsx', array_map('strtoupper', self::FIELDS), $contoh);
    }

    /* ---------- IMPORT ---------- */
    public function import(): void {
        Auth::requirePermission('import_penduduk');
        $logs = db_all("SELECT il.*, u.name AS user_name FROM import_logs il LEFT JOIN users u ON u.id=il.user_id ORDER BY il.id DESC LIMIT 10");
        $this->adminView('penduduk_import', ['pageTitle' => 'Import Data Penduduk', 'logs' => $logs, 'preview' => null]);
    }

    public function preview(): void {
        Auth::requirePermission('import_penduduk');
        verify_csrf();
        $path = Upload::excelTemp($_FILES['file'] ?? []);
        if (is_array($path)) { flash('danger', $path['error']); redirect('admin/penduduk/import'); }
        $rows = SimpleXLSX::rows($path);
        @unlink($path);
        if (!$rows || count($rows) < 2) { flash('danger', 'File Excel kosong atau tidak dapat dibaca.'); redirect('admin/penduduk/import'); }

        $regions = [];
        foreach (db_all("SELECT id, type, LOWER(name) AS name FROM regions") as $rg) $regions[$rg['type']][$rg['name']] = $rg['id'];

        array_shift($rows); // buang header
        $valid = []; $invalid = []; $seenNik = [];
        foreach ($rows as $i => $row) {
            if (!array_filter($row, fn($v) => trim((string) $v) !== '')) continue;
            $line = $i + 2;
            $d = [];
            foreach (self::FIELDS as $k => $col) $d[$col] = trim((string) ($row[$k] ?? ''));
            $d['nik'] = preg_replace('/\D+/', '', $d['nik']) ?? '';
            $d['no_kk'] = preg_replace('/\D+/', '', $d['no_kk']) ?? '';

            $errs = [];
            if (!Security::validNik($d['nik'])) $errs[] = 'NIK harus 16 digit';
            if ($d['nama'] === '') $errs[] = 'Nama kosong';
            if ($d['no_kk'] !== '' && !Security::validKk($d['no_kk'])) $errs[] = 'No KK harus 16 digit';
            if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errs[] = 'Email tidak valid';

            if ($d['hubungan_keluarga'] !== '') {
                $relationshipMap = [];
                foreach (self::HUBUNGAN as $relationship) {
                    $relationshipMap[strtolower($relationship)] = $relationship;
                }
                $relationshipKey = strtolower($d['hubungan_keluarga']);
                if (isset($relationshipMap[$relationshipKey])) {
                    $d['hubungan_keluarga'] = $relationshipMap[$relationshipKey];
                } else {
                    $errs[] = 'Hubungan keluarga tidak dikenali';
                }
            }
            $tgl = SimpleXLSX::toDate($d['tanggal_lahir']);
            if ($d['tanggal_lahir'] !== '' && !$tgl) $errs[] = 'Format tanggal lahir salah';
            $d['tanggal_lahir'] = $tgl;
            if (isset($seenNik[$d['nik']])) $errs[] = 'NIK duplikat di file (baris ' . $seenNik[$d['nik']] . ')';
            $seenNik[$d['nik']] = $line;
            $d['jenis_kelamin'] = strtoupper(substr($d['jenis_kelamin'], 0, 1)) === 'P' ? 'P' : 'L';
            $d['dusun_id'] = $regions['dusun'][strtolower($d['dusun'])] ?? null;
            $d['rw_id'] = $regions['rw'][strtolower($d['rw'])] ?? null;
            $d['rt_id'] = $regions['rt'][strtolower($d['rt'])] ?? null;
            $d['status_penduduk'] = in_array(strtolower($d['status_penduduk']), ['hidup', 'meninggal', 'pindah', 'tidak_aktif'], true) ? strtolower($d['status_penduduk']) : 'hidup';
            $d['exists'] = (bool) db_val("SELECT id FROM residents WHERE nik = ?", [$d['nik']]);
            $d['line'] = $line;
            if ($errs) { $d['errors'] = $errs; $invalid[] = $d; } else { $valid[] = $d; }
        }
        $token = bin2hex(random_bytes(8));
        $tmpFile = BASE_PATH . '/uploads/tmp/import_' . $token . '.json';
        if (!is_dir(dirname($tmpFile))) mkdir(dirname($tmpFile), 0755, true);
        file_put_contents($tmpFile, json_encode($valid));
        $_SESSION['import_token'] = $token;
        $_SESSION['import_filename'] = $_FILES['file']['name'] ?? 'import.xlsx';

        $logs = db_all("SELECT il.*, u.name AS user_name FROM import_logs il LEFT JOIN users u ON u.id=il.user_id ORDER BY il.id DESC LIMIT 10");
        $this->adminView('penduduk_import', [
            'pageTitle' => 'Preview Import Penduduk', 'logs' => $logs,
            'preview' => ['valid' => $valid, 'invalid' => $invalid, 'token' => $token],
        ]);
    }

    public function import_simpan(): void {
        Auth::requirePermission('import_penduduk');
        verify_csrf();
        $token = $_POST['token'] ?? '';
        if (!$token || $token !== ($_SESSION['import_token'] ?? '') || !preg_match('/^[a-f0-9]{16}$/', $token)) {
            flash('danger', 'Sesi import tidak valid. Ulangi upload.'); redirect('admin/penduduk/import');
        }
        $tmpFile = BASE_PATH . '/uploads/tmp/import_' . $token . '.json';
        $valid = is_file($tmpFile) ? json_decode(file_get_contents($tmpFile), true) : null;
        @unlink($tmpFile); unset($_SESSION['import_token']);
        if (!$valid) { flash('danger', 'Data preview tidak ditemukan. Ulangi upload.'); redirect('admin/penduduk/import'); }

        $mode = ($_POST['mode'] ?? 'skip') === 'update' ? 'update' : 'skip';
        $inserted = 0; $updated = 0; $skipped = 0;
        $pdo = db();
        $pdo->beginTransaction();
        try {
            foreach ($valid as $d) {
                $data = [
                    'nik' => $d['nik'], 'no_kk' => $d['no_kk'] ?: null, 'nama' => $d['nama'],
                    'tempat_lahir' => $d['tempat_lahir'] ?: null, 'tanggal_lahir' => $d['tanggal_lahir'],
                    'jenis_kelamin' => $d['jenis_kelamin'], 'agama' => $d['agama'] ?: null,
                    'pendidikan' => $d['pendidikan'] ?: null, 'pekerjaan' => $d['pekerjaan'] ?: null,
                    'status_perkawinan' => $d['status_perkawinan'] ?: null, 'hubungan_keluarga' => $d['hubungan_keluarga'] ?: null,
                    'alamat' => $d['alamat'] ?: null, 'dusun_id' => $d['dusun_id'], 'rw_id' => $d['rw_id'], 'rt_id' => $d['rt_id'],
                    'status_penduduk' => $d['status_penduduk'], 'no_hp' => $d['no_hp'] ?: null,
                    'email' => $d['email'] ?: null, 'keterangan' => $d['keterangan'] ?: null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $existing = db_row("SELECT id FROM residents WHERE nik = ?", [$d['nik']]);
                $userId = (int) (auth_user()['id'] ?? 0);

                if ($existing) {
                    if ($mode === 'update') {
                        $data['_restore'] = '1';
                        PopulationService::saveResident((int) $existing['id'], $data, $userId);
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    PopulationService::saveResident(null, $data, $userId);
                    $inserted++;
                }
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Import penduduk gagal: ' . $ex->getMessage());
            flash('danger', 'Import dibatalkan otomatis karena ada data yang tidak dapat disimpan. Periksa kembali format NIK, Nomor KK, hubungan keluarga, dan wilayah pada file.');
            redirect('admin/penduduk/import');
        }
        db_insert('import_logs', [
            'user_id' => auth_user()['id'], 'filename' => $_SESSION['import_filename'] ?? 'import.xlsx',
            'total_rows' => count($valid), 'valid_rows' => $inserted + $updated, 'invalid_rows' => $skipped,
            'mode' => $mode, 'note' => "Baru: $inserted, Update: $updated, Dilewati: $skipped",
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        log_activity('import', 'penduduk', "Import penduduk: $inserted baru, $updated update, $skipped dilewati");
        flash('success', "Import selesai! Data baru: $inserted, diperbarui: $updated, dilewati: $skipped. Keluarga dan relasi anggota telah disinkronkan otomatis.");
        redirect('admin/penduduk/import');
    }
}
