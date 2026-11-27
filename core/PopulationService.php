<?php

/**
 * Menjaga data penduduk, keluarga, dan anggota keluarga tetap konsisten.
 *
 * Sumber hubungan utama tetap Nomor KK pada tabel residents. Tabel
 * family_members dipelihara sebagai indeks relasi agar modul lain dapat
 * menggunakannya tanpa menghasilkan data ganda.
 */
class PopulationService
{
    private const SYNC_VERSION = '20260726_population_crud_v1';
    private static bool $schemaChecked = false;

    public static function ensureSchema(): void
    {
        if (self::$schemaChecked) return;
        self::$schemaChecked = true;

        try {
            if (!self::tableExists('family_members')) {
                db_exec("CREATE TABLE `family_members` (
                    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `family_id` int(10) UNSIGNED NOT NULL,
                    `resident_id` int(10) UNSIGNED NOT NULL,
                    `hubungan` varchar(40) DEFAULT NULL,
                    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    KEY `idx_fm_family` (`family_id`),
                    KEY `idx_fm_resident` (`resident_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }

            // Satu penduduk hanya boleh memiliki satu relasi keluarga.
            if (!self::hasUniqueResidentIndex()) {
                db_exec("DELETE fm1 FROM family_members fm1
                         INNER JOIN family_members fm2
                           ON fm1.resident_id = fm2.resident_id AND fm1.id > fm2.id");
                db_exec("ALTER TABLE family_members
                         ADD UNIQUE KEY uq_family_members_resident (resident_id)");
            }
        } catch (Throwable $e) {
            // Shared hosting tertentu membatasi ALTER TABLE. CRUD tetap dapat
            // berjalan karena semua penulisan relasi menghapus duplikat dahulu.
            error_log('Population schema check: ' . $e->getMessage());
        }

        // Perbaikan satu kali untuk database lama. Setelah berhasil, versi
        // disimpan agar permintaan berikutnya tidak mengulang proses berat.
        try {
            $appliedVersion = (string) db_val(
                "SELECT setting_value FROM site_settings WHERE setting_key = 'population_sync_version' LIMIT 1"
            );
            if ($appliedVersion !== self::SYNC_VERSION) {
                $userId = (int) (auth_user()['id'] ?? 0);
                self::syncAll($userId);
                db_exec(
                    "INSERT INTO site_settings (setting_key, setting_value)
                     VALUES ('population_sync_version', ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                    [self::SYNC_VERSION]
                );
            }
        } catch (Throwable $e) {
            // Halaman admin tetap dapat dibuka. Tombol Sinkronkan pada daftar
            // keluarga dapat digunakan setelah kendala izin database diperbaiki.
            error_log('Population automatic sync: ' . $e->getMessage());
        }
    }

    public static function saveResident(?int $id, array $input, int $userId): int
    {
        self::ensureSchema();

        return self::atomic(function () use ($id, $input, $userId): int {
            $old = null;
            if ($id !== null) {
                $old = db_row("SELECT * FROM residents WHERE id = ?", [$id]);
                if (!$old) {
                    throw new PopulationValidationException(['Data penduduk tidak ditemukan atau sudah dihapus.']);
                }
            }

            $nik = self::digits($input['nik'] ?? '');
            $noKk = self::digits($input['no_kk'] ?? '');
            $familyId = (int) ($input['family_id'] ?? 0);

            $selectedFamily = null;
            if ($familyId > 0) {
                $selectedFamily = db_row(
                    "SELECT * FROM families WHERE id = ? AND deleted_at IS NULL",
                    [$familyId]
                );
                if (!$selectedFamily) {
                    throw new PopulationValidationException(['Keluarga yang dipilih tidak ditemukan. Muat ulang formulir lalu pilih kembali.']);
                }
                $noKk = (string) $selectedFamily['no_kk'];
            }

            $name = self::cut(trim((string) ($input['nama'] ?? '')), 150);
            $errors = [];
            if (!Security::validNik($nik)) $errors[] = 'NIK wajib terdiri dari 16 digit angka.';
            if ($name === '') $errors[] = 'Nama lengkap wajib diisi.';
            if ($noKk !== '' && !Security::validKk($noKk)) $errors[] = 'Nomor KK harus terdiri dari 16 digit angka.';

            $duplicate = db_row(
                "SELECT id, deleted_at FROM residents WHERE nik = ? AND id != ? LIMIT 1",
                [$nik, $id ?? 0]
            );
            if ($duplicate) {
                $errors[] = $duplicate['deleted_at']
                    ? 'NIK sudah ada di sampah data penduduk. Pulihkan data tersebut atau gunakan NIK lain.'
                    : 'NIK sudah terdaftar pada data penduduk.';
            }

            $birthDate = self::dateOrNull($input['tanggal_lahir'] ?? null);
            if (trim((string) ($input['tanggal_lahir'] ?? '')) !== '' && $birthDate === null) {
                $errors[] = 'Tanggal lahir tidak valid.';
            }
            if ($birthDate !== null && $birthDate > date('Y-m-d')) {
                $errors[] = 'Tanggal lahir tidak boleh melebihi tanggal hari ini.';
            }

            $email = trim((string) ($input['email'] ?? ''));
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Alamat email tidak valid.';
            }

            $relationship = trim((string) ($input['hubungan_keluarga'] ?? ''));
            if ($relationship !== '' && !in_array($relationship, PendudukController::HUBUNGAN, true)) {
                $errors[] = 'Hubungan keluarga tidak valid.';
            }

            $regionIds = [
                'dusun_id' => self::regionId($input['dusun_id'] ?? null, 'dusun'),
                'rw_id' => self::regionId($input['rw_id'] ?? null, 'rw'),
                'rt_id' => self::regionId($input['rt_id'] ?? null, 'rt'),
            ];

            if ($selectedFamily && !empty($input['inherit_family'])) {
                foreach ($regionIds as $column => $value) {
                    if ($value === null) $regionIds[$column] = $selectedFamily[$column] ? (int) $selectedFamily[$column] : null;
                }
                if (trim((string) ($input['alamat'] ?? '')) === '' && !empty($selectedFamily['alamat'])) {
                    $input['alamat'] = $selectedFamily['alamat'];
                }
            }

            if ($errors) throw new PopulationValidationException($errors);

            $status = (string) ($input['status_penduduk'] ?? 'hidup');
            if (!in_array($status, ['hidup', 'meninggal', 'pindah', 'tidak_aktif'], true)) $status = 'hidup';

            $data = [
                'nik' => $nik,
                'no_kk' => $noKk !== '' ? $noKk : null,
                'nama' => $name,
                'tempat_lahir' => self::nullableText($input['tempat_lahir'] ?? null, 100),
                'tanggal_lahir' => $birthDate,
                'jenis_kelamin' => (($input['jenis_kelamin'] ?? 'L') === 'P') ? 'P' : 'L',
                'agama' => self::nullableText($input['agama'] ?? null, 30),
                'pendidikan' => self::nullableText($input['pendidikan'] ?? null, 60),
                'pekerjaan' => self::nullableText($input['pekerjaan'] ?? null, 100),
                'status_perkawinan' => self::nullableText($input['status_perkawinan'] ?? null, 30),
                'hubungan_keluarga' => $relationship !== '' ? $relationship : null,
                'alamat' => self::nullableText($input['alamat'] ?? null, 255),
                'dusun_id' => $regionIds['dusun_id'],
                'rw_id' => $regionIds['rw_id'],
                'rt_id' => $regionIds['rt_id'],
                'status_penduduk' => $status,
                'no_hp' => self::nullableText($input['no_hp'] ?? null, 20),
                'email' => $email !== '' ? self::cut($email, 150) : null,
                'keterangan' => self::nullableText($input['keterangan'] ?? null, 3000),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($id === null) {
                $data['created_by'] = $userId ?: null;
                $data['created_at'] = date('Y-m-d H:i:s');
                $id = db_insert('residents', $data);
            } else {
                $data['updated_by'] = $userId ?: null;
                if (!empty($input['_restore'])) $data['deleted_at'] = null;
                db_update('residents', $data, 'id = ?', [$id]);
            }

            self::syncResidentMembership($id, $userId);
            $oldNoKk = (string) ($old['no_kk'] ?? '');
            if ($oldNoKk !== '' && $oldNoKk !== $noKk) {
                $oldFamilyId = (int) db_val("SELECT id FROM families WHERE no_kk = ? LIMIT 1", [$oldNoKk]);
                if ($oldFamilyId > 0) self::refreshFamilyHead($oldFamilyId, true);
            }
            return $id;
        });
    }

    public static function deleteResident(int $residentId, int $userId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($residentId, $userId): void {
            $resident = db_row("SELECT * FROM residents WHERE id = ? AND deleted_at IS NULL", [$residentId]);
            if (!$resident) throw new PopulationValidationException(['Data penduduk tidak ditemukan.']);

            db_update('residents', [
                'deleted_at' => date('Y-m-d H:i:s'),
                'updated_by' => $userId ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$residentId]);
            db_exec("DELETE FROM family_members WHERE resident_id = ?", [$residentId]);

            if (Security::validKk((string) ($resident['no_kk'] ?? ''))) {
                $familyId = (int) db_val("SELECT id FROM families WHERE no_kk = ? LIMIT 1", [$resident['no_kk']]);
                if ($familyId > 0) self::refreshFamilyHead($familyId, true);
            }
        });
    }

    public static function restoreResident(int $residentId, int $userId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($residentId, $userId): void {
            $resident = db_row("SELECT * FROM residents WHERE id = ?", [$residentId]);
            if (!$resident) throw new PopulationValidationException(['Data penduduk tidak ditemukan.']);

            db_update('residents', [
                'deleted_at' => null,
                'updated_by' => $userId ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$residentId]);
            self::syncResidentMembership($residentId, $userId);
        });
    }

    public static function saveFamily(?int $id, array $input, int $userId): array
    {
        self::ensureSchema();

        return self::atomic(function () use ($id, $input, $userId): array {
            $old = null;
            if ($id !== null) {
                $old = db_row("SELECT * FROM families WHERE id = ?", [$id]);
                if (!$old) {
                    throw new PopulationValidationException(['Data keluarga tidak ditemukan.']);
                }
            }

            $noKk = self::digits($input['no_kk'] ?? '');
            $errors = [];
            if (!Security::validKk($noKk)) $errors[] = 'Nomor KK wajib terdiri dari 16 digit angka.';

            $existing = db_row(
                "SELECT id, deleted_at FROM families WHERE no_kk = ? AND id != ? LIMIT 1",
                [$noKk, $id ?? 0]
            );
            if ($existing) {
                if ($id !== null || empty($existing['deleted_at'])) {
                    $errors[] = !empty($existing['deleted_at'])
                        ? 'Nomor KK sudah ada di sampah data keluarga. Pulihkan data tersebut atau gunakan Nomor KK lain.'
                        : 'Nomor KK sudah terdaftar. Gunakan menu pencarian untuk membuka keluarga tersebut.';
                }
            }

            $headResidentId = (int) ($input['head_resident_id'] ?? 0);
            $createHead = !empty($input['create_head']);
            if ($headResidentId > 0 && $createHead) {
                $errors[] = 'Pilih salah satu: gunakan penduduk yang sudah ada atau buat kepala keluarga baru.';
            }

            $headResident = null;
            if ($headResidentId > 0) {
                $headResident = db_row(
                    "SELECT * FROM residents WHERE id = ? AND deleted_at IS NULL",
                    [$headResidentId]
                );
                if (!$headResident) $errors[] = 'Penduduk yang dipilih sebagai kepala keluarga tidak ditemukan.';
            }

            if ($createHead) {
                $headNik = self::digits($input['head_nik'] ?? '');
                $headName = trim((string) ($input['head_nama'] ?? ''));
                if (!Security::validNik($headNik)) $errors[] = 'NIK kepala keluarga baru wajib 16 digit.';
                if ($headName === '') $errors[] = 'Nama kepala keluarga baru wajib diisi.';
                if (db_val("SELECT id FROM residents WHERE nik = ? LIMIT 1", [$headNik])) {
                    $errors[] = 'NIK kepala keluarga baru sudah terdaftar. Pilih penduduk yang sudah ada.';
                }
            }

            $regionIds = [
                'dusun_id' => self::regionId($input['dusun_id'] ?? null, 'dusun'),
                'rw_id' => self::regionId($input['rw_id'] ?? null, 'rw'),
                'rt_id' => self::regionId($input['rt_id'] ?? null, 'rt'),
            ];
            if ($errors) throw new PopulationValidationException($errors);

            $status = (string) ($input['status'] ?? 'aktif');
            if (!in_array($status, ['aktif', 'pindah', 'tidak_aktif'], true)) $status = 'aktif';

            $familyData = [
                'no_kk' => $noKk,
                'kepala_keluarga' => self::nullableText($input['kepala_keluarga'] ?? null, 150),
                'alamat' => self::nullableText($input['alamat'] ?? null, 255),
                'dusun_id' => $regionIds['dusun_id'],
                'rw_id' => $regionIds['rw_id'],
                'rt_id' => $regionIds['rt_id'],
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
                'deleted_at' => null,
            ];

            if ($id === null && $existing && !empty($existing['deleted_at'])) {
                $id = (int) $existing['id'];
                db_update('families', $familyData, 'id = ?', [$id]);
            } elseif ($id === null) {
                $familyData['created_by'] = $userId ?: null;
                $familyData['created_at'] = date('Y-m-d H:i:s');
                $id = db_insert('families', $familyData);
            } else {
                db_update('families', $familyData, 'id = ?', [$id]);

                $oldNoKk = (string) ($old['no_kk'] ?? '');
                if ($oldNoKk !== '' && $oldNoKk !== $noKk) {
                    db_update(
                        'residents',
                        ['no_kk' => $noKk, 'updated_by' => $userId ?: null, 'updated_at' => date('Y-m-d H:i:s')],
                        'no_kk = ? AND deleted_at IS NULL',
                        [$oldNoKk]
                    );
                }
            }

            $createdResidentId = null;
            if ($createHead) {
                $createdResidentId = self::saveResident(null, [
                    'nik' => $input['head_nik'] ?? '',
                    'no_kk' => $noKk,
                    'nama' => $input['head_nama'] ?? '',
                    'tanggal_lahir' => $input['head_tanggal_lahir'] ?? '',
                    'jenis_kelamin' => $input['head_jenis_kelamin'] ?? 'L',
                    'hubungan_keluarga' => 'Kepala Keluarga',
                    'status_penduduk' => 'hidup',
                    'alamat' => $familyData['alamat'],
                    'dusun_id' => $familyData['dusun_id'],
                    'rw_id' => $familyData['rw_id'],
                    'rt_id' => $familyData['rt_id'],
                    'family_id' => $id,
                    'inherit_family' => '1',
                ], $userId);
                $headResidentId = $createdResidentId;
            } elseif ($headResident) {
                $previousHeadNoKk = (string) ($headResident['no_kk'] ?? '');
                db_update('residents', [
                    'no_kk' => $noKk,
                    'hubungan_keluarga' => 'Kepala Keluarga',
                    'updated_by' => $userId ?: null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$headResidentId]);
                self::syncResidentMembership($headResidentId, $userId);

                if ($previousHeadNoKk !== '' && $previousHeadNoKk !== $noKk) {
                    $previousFamilyId = (int) db_val("SELECT id FROM families WHERE no_kk = ? LIMIT 1", [$previousHeadNoKk]);
                    if ($previousFamilyId > 0) self::refreshFamilyHead($previousFamilyId, true);
                }
            }

            if ($headResidentId > 0) {
                self::makeHead($id, $headResidentId, $userId);
            }

            self::syncFamilyMembers($id, $userId);
            self::refreshFamilyHead($id);

            return ['family_id' => $id, 'resident_id' => $createdResidentId];
        });
    }

    public static function addMember(int $familyId, int $residentId, string $relationship, int $userId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($familyId, $residentId, $relationship, $userId): void {
            $family = db_row("SELECT * FROM families WHERE id = ? AND deleted_at IS NULL", [$familyId]);
            $resident = db_row("SELECT * FROM residents WHERE id = ? AND deleted_at IS NULL", [$residentId]);
            if (!$family) throw new PopulationValidationException(['Data keluarga tidak ditemukan.']);
            if (!$resident) throw new PopulationValidationException(['Data penduduk tidak ditemukan.']);

            if (!in_array($relationship, PendudukController::HUBUNGAN, true)) {
                throw new PopulationValidationException(['Hubungan keluarga tidak valid.']);
            }

            $previousNoKk = (string) ($resident['no_kk'] ?? '');
            $update = [
                'no_kk' => $family['no_kk'],
                'hubungan_keluarga' => $relationship,
                'updated_by' => $userId ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            // Pindah KK = ikut alamat keluarga tujuan (bila keluarga sudah punya alamat/wilayah).
            if ($previousNoKk !== (string) $family['no_kk']) {
                foreach (['alamat', 'dusun_id', 'rw_id', 'rt_id'] as $col) {
                    if (!empty($family[$col])) $update[$col] = $family[$col];
                }
            }
            db_update('residents', $update, 'id = ?', [$residentId]);

            self::syncResidentMembership($residentId, $userId);
            if ($relationship === 'Kepala Keluarga') self::makeHead($familyId, $residentId, $userId);
            self::refreshFamilyHead($familyId, true);

            if ($previousNoKk !== '' && $previousNoKk !== (string) $family['no_kk']) {
                $previousFamilyId = (int) db_val("SELECT id FROM families WHERE no_kk = ? LIMIT 1", [$previousNoKk]);
                if ($previousFamilyId > 0) self::refreshFamilyHead($previousFamilyId, true);
            }
        });
    }

    public static function updateMember(int $familyId, int $residentId, string $relationship, int $userId): void
    {
        self::addMember($familyId, $residentId, $relationship, $userId);
    }

    public static function removeMember(int $familyId, int $residentId, int $userId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($familyId, $residentId, $userId): void {
            $family = db_row("SELECT * FROM families WHERE id = ?", [$familyId]);
            $resident = db_row("SELECT * FROM residents WHERE id = ?", [$residentId]);
            if (!$family || !$resident || (string) $resident['no_kk'] !== (string) $family['no_kk']) {
                throw new PopulationValidationException(['Anggota tidak ditemukan pada keluarga tersebut.']);
            }

            $wasHead = (($resident['hubungan_keluarga'] ?? '') === 'Kepala Keluarga');
            db_update('residents', [
                'no_kk' => null,
                'hubungan_keluarga' => null,
                'updated_by' => $userId ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$residentId]);
            db_exec("DELETE FROM family_members WHERE resident_id = ?", [$residentId]);
            if ($wasHead) {
                db_update('families', ['kepala_keluarga' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$familyId]);
            }
            self::refreshFamilyHead($familyId, true);
        });
    }

    public static function deleteFamily(int $familyId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($familyId): void {
            $family = db_row("SELECT * FROM families WHERE id = ? AND deleted_at IS NULL", [$familyId]);
            if (!$family) throw new PopulationValidationException(['Data keluarga tidak ditemukan.']);

            $memberCount = (int) db_val(
                "SELECT COUNT(*) FROM residents WHERE no_kk = ? AND deleted_at IS NULL",
                [$family['no_kk']]
            );
            if ($memberCount > 0) {
                throw new PopulationValidationException([
                    "Keluarga belum dapat dipindahkan ke sampah karena masih memiliki {$memberCount} anggota. Pindahkan atau keluarkan anggotanya terlebih dahulu."
                ]);
            }

            db_update('families', ['deleted_at' => date('Y-m-d H:i:s')], 'id = ?', [$familyId]);
            db_exec("DELETE FROM family_members WHERE family_id = ?", [$familyId]);
        });
    }

    public static function restoreFamily(int $familyId): void
    {
        self::ensureSchema();
        self::atomic(function () use ($familyId): void {
            $family = db_row("SELECT * FROM families WHERE id = ?", [$familyId]);
            if (!$family) throw new PopulationValidationException(['Data keluarga tidak ditemukan.']);
            db_update('families', ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')], 'id = ?', [$familyId]);
        });
    }

    public static function syncAll(int $userId = 0): array
    {
        self::ensureSchema();

        return self::atomic(function () use ($userId): array {
            $now = date('Y-m-d H:i:s');

            db_exec("DROP TEMPORARY TABLE IF EXISTS tmp_population_heads");
            db_exec(
                "CREATE TEMPORARY TABLE tmp_population_heads (
                    no_kk CHAR(16) NOT NULL PRIMARY KEY,
                    head_id INT(10) UNSIGNED NOT NULL,
                    KEY idx_tmp_population_head (head_id)
                 ) ENGINE=MEMORY
                 SELECT no_kk,
                        COALESCE(
                            MIN(CASE WHEN hubungan_keluarga = 'Kepala Keluarga' THEN id END),
                            MIN(id)
                        ) AS head_id
                 FROM residents
                 WHERE deleted_at IS NULL
                   AND no_kk REGEXP '^[0-9]{16}$'
                 GROUP BY no_kk"
            );

            // Tentukan tepat satu kepala untuk setiap Nomor KK.
            db_exec(
                "UPDATE residents r
                 INNER JOIN tmp_population_heads h ON h.no_kk = r.no_kk
                 SET r.hubungan_keluarga = 'Famili Lain',
                     r.updated_by = ?,
                     r.updated_at = ?
                 WHERE r.deleted_at IS NULL
                   AND r.hubungan_keluarga = 'Kepala Keluarga'
                   AND r.id != h.head_id",
                [$userId ?: null, $now]
            );
            db_exec(
                "UPDATE residents r
                 INNER JOIN tmp_population_heads h ON h.head_id = r.id
                 SET r.hubungan_keluarga = 'Kepala Keluarga',
                     r.updated_by = ?,
                     r.updated_at = ?
                 WHERE r.deleted_at IS NULL
                   AND COALESCE(r.hubungan_keluarga, '') != 'Kepala Keluarga'",
                [$userId ?: null, $now]
            );

            $restoredFamilies = (int) db_val(
                "SELECT COUNT(*)
                 FROM families f
                 INNER JOIN tmp_population_heads h ON h.no_kk = f.no_kk
                 WHERE f.deleted_at IS NOT NULL"
            );

            $createdFamilies = db_exec(
                "INSERT INTO families
                    (no_kk, kepala_keluarga, alamat, dusun_id, rw_id, rt_id, status, created_by, created_at, updated_at, deleted_at)
                 SELECT r.no_kk, r.nama, r.alamat, r.dusun_id, r.rw_id, r.rt_id,
                        'aktif', ?, ?, ?, NULL
                 FROM tmp_population_heads h
                 INNER JOIN residents r ON r.id = h.head_id
                 LEFT JOIN families f ON f.no_kk = h.no_kk
                 WHERE f.id IS NULL",
                [$userId ?: null, $now, $now]
            );

            db_exec(
                "UPDATE families f
                 INNER JOIN tmp_population_heads h ON h.no_kk = f.no_kk
                 INNER JOIN residents r ON r.id = h.head_id
                 SET f.kepala_keluarga = r.nama,
                     f.alamat = r.alamat,
                     f.dusun_id = r.dusun_id,
                     f.rw_id = r.rw_id,
                     f.rt_id = r.rt_id,
                     f.status = IF(f.status IN ('aktif', 'pindah', 'tidak_aktif'), f.status, 'aktif'),
                     f.deleted_at = NULL,
                     f.updated_at = ?",
                [$now]
            );

            // family_members adalah indeks turunan: bangun ulang secara atomik
            // dari residents.no_kk agar data lama yang bolong ikut diperbaiki.
            db_exec("DELETE FROM family_members");
            $links = db_exec(
                "INSERT INTO family_members (family_id, resident_id, hubungan, created_at, updated_at)
                 SELECT f.id, r.id, r.hubungan_keluarga, ?, ?
                 FROM residents r
                 INNER JOIN families f ON f.no_kk = r.no_kk AND f.deleted_at IS NULL
                 WHERE r.deleted_at IS NULL
                   AND r.no_kk REGEXP '^[0-9]{16}$'",
                [$now, $now]
            );

            db_exec("DROP TEMPORARY TABLE IF EXISTS tmp_population_heads");

            return [
                'created_families' => (int) $createdFamilies,
                'restored_families' => $restoredFamilies,
                'member_links' => (int) $links,
                'health' => self::health(),
            ];
        });
    }

    public static function health(): array
    {
        self::ensureSchema();

        return [
            'families' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
            'residents' => (int) db_val("SELECT COUNT(*) FROM residents WHERE deleted_at IS NULL"),
            'missing_families' => (int) db_val(
                "SELECT COUNT(*) FROM (
                    SELECT DISTINCT r.no_kk
                    FROM residents r
                    LEFT JOIN families f ON f.no_kk = r.no_kk AND f.deleted_at IS NULL
                    WHERE r.deleted_at IS NULL
                      AND r.no_kk REGEXP '^[0-9]{16}$'
                      AND f.id IS NULL
                ) x"
            ),
            'missing_links' => (int) db_val(
                "SELECT COUNT(*)
                 FROM residents r
                 LEFT JOIN families f ON f.no_kk = r.no_kk AND f.deleted_at IS NULL
                 LEFT JOIN family_members fm ON fm.resident_id = r.id AND fm.family_id = f.id
                 WHERE r.deleted_at IS NULL
                   AND r.no_kk REGEXP '^[0-9]{16}$'
                   AND (f.id IS NULL OR fm.id IS NULL)"
            ),
            'empty_families' => (int) db_val(
                "SELECT COUNT(*) FROM (
                    SELECT f.id
                    FROM families f
                    LEFT JOIN residents r ON r.no_kk = f.no_kk AND r.deleted_at IS NULL
                    WHERE f.deleted_at IS NULL
                    GROUP BY f.id
                    HAVING COUNT(r.id) = 0
                ) x"
            ),
            'multiple_heads' => (int) db_val(
                "SELECT COUNT(*) FROM (
                    SELECT no_kk
                    FROM residents
                    WHERE deleted_at IS NULL
                      AND no_kk REGEXP '^[0-9]{16}$'
                      AND hubungan_keluarga = 'Kepala Keluarga'
                    GROUP BY no_kk
                    HAVING COUNT(*) > 1
                ) x"
            ),
        ];
    }

    public static function syncResidentMembership(int $residentId, int $userId = 0): void
    {
        self::ensureSchema();

        // Simpan keluarga asal sebelum relasi dibangun ulang. Ini penting saat
        // Nomor KK berubah melalui edit maupun import massal.
        $previousFamilyIds = array_map(
            static fn(array $row): int => (int) $row['family_id'],
            db_all("SELECT DISTINCT family_id FROM family_members WHERE resident_id = ?", [$residentId])
        );

        $resident = db_row("SELECT * FROM residents WHERE id = ?", [$residentId]);
        db_exec("DELETE FROM family_members WHERE resident_id = ?", [$residentId]);

        if (!$resident || $resident['deleted_at'] || !Security::validKk((string) ($resident['no_kk'] ?? ''))) {
            foreach ($previousFamilyIds as $previousFamilyId) {
                self::refreshFamilyHead($previousFamilyId, true);
            }
            return;
        }

        $familyId = self::ensureFamilyForResident($resident, $userId);
        $relationship = trim((string) ($resident['hubungan_keluarga'] ?? ''));

        if ($relationship === '') {
            $hasHead = (bool) db_val(
                "SELECT id FROM residents
                 WHERE no_kk = ? AND deleted_at IS NULL
                   AND hubungan_keluarga = 'Kepala Keluarga' AND id != ?
                 LIMIT 1",
                [$resident['no_kk'], $residentId]
            );
            $relationship = $hasHead ? 'Famili Lain' : 'Kepala Keluarga';
            db_update('residents', [
                'hubungan_keluarga' => $relationship,
                'updated_by' => $userId ?: null,
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$residentId]);
            $resident['hubungan_keluarga'] = $relationship;
        }

        db_insert('family_members', [
            'family_id' => $familyId,
            'resident_id' => $residentId,
            'hubungan' => $relationship,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($relationship === 'Kepala Keluarga') {
            self::makeHead($familyId, $residentId, $userId);
        } else {
            self::refreshFamilyHead($familyId, true);
        }

        foreach ($previousFamilyIds as $previousFamilyId) {
            if ($previousFamilyId !== $familyId) {
                self::refreshFamilyHead($previousFamilyId, true);
            }
        }
    }

    private static function ensureFamilyForResident(array $resident, int $userId): int
    {
        $noKk = (string) $resident['no_kk'];
        $family = db_row("SELECT * FROM families WHERE no_kk = ? LIMIT 1", [$noKk]);

        $head = (($resident['hubungan_keluarga'] ?? '') === 'Kepala Keluarga');
        $data = [
            'kepala_keluarga' => $head ? $resident['nama'] : null,
            'alamat' => $resident['alamat'],
            'dusun_id' => $resident['dusun_id'],
            'rw_id' => $resident['rw_id'],
            'rt_id' => $resident['rt_id'],
            'updated_at' => date('Y-m-d H:i:s'),
            'deleted_at' => null,
        ];

        if (!$family) {
            $data['no_kk'] = $noKk;
            $data['status'] = 'aktif';
            $data['created_by'] = $userId ?: null;
            $data['created_at'] = date('Y-m-d H:i:s');
            return db_insert('families', $data);
        }

        $update = ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')];
        if ($head) {
            $update += [
                'kepala_keluarga' => $resident['nama'],
                'alamat' => $resident['alamat'],
                'dusun_id' => $resident['dusun_id'],
                'rw_id' => $resident['rw_id'],
                'rt_id' => $resident['rt_id'],
            ];
        } else {
            foreach (['alamat', 'dusun_id', 'rw_id', 'rt_id'] as $column) {
                if (empty($family[$column]) && !empty($resident[$column])) $update[$column] = $resident[$column];
            }
        }
        db_update('families', $update, 'id = ?', [(int) $family['id']]);
        return (int) $family['id'];
    }

    private static function syncFamilyMembers(int $familyId, int $userId): void
    {
        $family = db_row("SELECT * FROM families WHERE id = ?", [$familyId]);
        if (!$family) return;

        $residentIds = db_all(
            "SELECT id FROM residents WHERE no_kk = ? AND deleted_at IS NULL",
            [$family['no_kk']]
        );
        foreach ($residentIds as $row) self::syncResidentMembership((int) $row['id'], $userId);

        db_exec(
            "DELETE fm FROM family_members fm
             LEFT JOIN residents r ON r.id = fm.resident_id
             WHERE fm.family_id = ? AND (r.id IS NULL OR r.deleted_at IS NOT NULL OR r.no_kk != ?)",
            [$familyId, $family['no_kk']]
        );
    }

    private static function makeHead(int $familyId, int $residentId, int $userId): void
    {
        $family = db_row("SELECT * FROM families WHERE id = ?", [$familyId]);
        $resident = db_row("SELECT * FROM residents WHERE id = ?", [$residentId]);
        if (!$family || !$resident) return;

        db_update('residents', [
            'hubungan_keluarga' => 'Famili Lain',
            'updated_by' => $userId ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], "no_kk = ? AND deleted_at IS NULL AND hubungan_keluarga = 'Kepala Keluarga' AND id != ?", [$family['no_kk'], $residentId]);
        db_exec(
            "UPDATE family_members fm
             INNER JOIN residents r ON r.id = fm.resident_id
             SET fm.hubungan = r.hubungan_keluarga, fm.updated_at = ?
             WHERE fm.family_id = ?",
            [date('Y-m-d H:i:s'), $familyId]
        );

        db_update('residents', [
            'no_kk' => $family['no_kk'],
            'hubungan_keluarga' => 'Kepala Keluarga',
            'updated_by' => $userId ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$residentId]);
        db_exec("UPDATE family_members SET hubungan = 'Kepala Keluarga', updated_at = ? WHERE resident_id = ?", [date('Y-m-d H:i:s'), $residentId]);

        db_update('families', [
            'kepala_keluarga' => $resident['nama'],
            'alamat' => $resident['alamat'] ?: $family['alamat'],
            'dusun_id' => $resident['dusun_id'] ?: $family['dusun_id'],
            'rw_id' => $resident['rw_id'] ?: $family['rw_id'],
            'rt_id' => $resident['rt_id'] ?: $family['rt_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$familyId]);
    }

    private static function refreshFamilyHead(int $familyId, bool $clearWhenMissing = false): void
    {
        $family = db_row("SELECT * FROM families WHERE id = ?", [$familyId]);
        if (!$family) return;

        $head = db_row(
            "SELECT * FROM residents
             WHERE no_kk = ? AND deleted_at IS NULL
               AND hubungan_keluarga = 'Kepala Keluarga'
             ORDER BY id ASC LIMIT 1",
            [$family['no_kk']]
        );

        if ($head) {
            db_update('families', [
                'kepala_keluarga' => $head['nama'],
                'updated_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$familyId]);
        } elseif ($clearWhenMissing) {
            $candidate = db_row(
                "SELECT id FROM residents
                 WHERE no_kk = ? AND deleted_at IS NULL
                 ORDER BY id ASC LIMIT 1",
                [$family['no_kk']]
            );
            if ($candidate) {
                self::makeHead($familyId, (int) $candidate['id'], 0);
            } else {
                db_update('families', [
                    'kepala_keluarga' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$familyId]);
            }
        }
    }

    private static function atomic(callable $callback)
    {
        $pdo = db();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();

        try {
            $result = $callback();
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();

            if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                throw new PopulationValidationException([
                    'Data tidak dapat disimpan karena NIK atau Nomor KK sudah digunakan.'
                ], 0, $e);
            }
            throw $e;
        }
    }

    private static function digits($value): string
    {
        return preg_replace('/\D+/', '', trim((string) $value)) ?? '';
    }

    private static function nullableText($value, int $length): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : self::cut($value, $length);
    }

    private static function cut(string $value, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length, 'UTF-8')
            : substr($value, 0, $length);
    }

    private static function dateOrNull($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') return null;
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return ($date && $date->format('Y-m-d') === $value) ? $value : null;
    }

    private static function regionId($value, string $type): ?int
    {
        $id = (int) $value;
        if ($id <= 0) return null;
        return db_val("SELECT id FROM regions WHERE id = ? AND type = ? LIMIT 1", [$id, $type]) ? $id : null;
    }

    private static function tableExists(string $table): bool
    {
        return (bool) db_val(
            "SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?",
            [$table]
        );
    }

    private static function hasUniqueResidentIndex(): bool
    {
        return (bool) db_val(
            "SELECT COUNT(*) FROM (
                SELECT index_name
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = 'family_members'
                  AND non_unique = 0
                GROUP BY index_name
                HAVING COUNT(*) = 1 AND MAX(column_name) = 'resident_id'
             ) unique_resident_indexes"
        );
    }
}

class PopulationValidationException extends RuntimeException
{
    public array $errors;

    public function __construct(array $errors, int $code = 0, ?Throwable $previous = null)
    {
        $this->errors = array_values(array_filter(array_map('strval', $errors)));
        parent::__construct($this->errors[0] ?? 'Data tidak valid.', $code, $previous);
    }
}
