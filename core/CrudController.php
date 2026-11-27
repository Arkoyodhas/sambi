<?php
/** CRUD generik berbasis konfigurasi modul (config/modules.php). */
class CrudController extends Controller {

    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'restore', 'trash', 'reorder', 'toggle'];
    protected string $module;
    protected array $cfg;
    protected string $table;

    public function __construct(string $module, array $cfg) {
        if (!preg_match('/^[a-z0-9-]+$/', $module)) {
            throw new InvalidArgumentException('Nama modul tidak valid.');
        }
        $table = (string) ($cfg['table'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('Nama tabel modul tidak valid.');
        }

        $this->module = $module;
        $this->cfg = $cfg;
        $this->table = $table;
        Auth::requirePermission((string) $cfg['perm']);

        if ($module === 'faq') AdminSchema::ensureFaq();
        if ($module === 'kontak-darurat') AdminSchema::ensureEmergency();
        if ($module === 'galeri') AdminSchema::ensureGalleryBanner();
        if ($module === 'halaman') {
            $schema = AdminSchema::ensureStaticPages();
            if (!$schema['ok'] && empty($_SESSION['static_pages_schema_warning'])) {
                $_SESSION['static_pages_schema_warning'] = true;
                flash('warning', $schema['messages'][0] ?? 'Skema Halaman Statis perlu diperbarui.');
            }
        }
    }

    protected function baseQuery(bool $trashed = false): array {
        $sql = $this->cfg['list_sql'] ?? "SELECT t.* FROM `{$this->table}` t";
        $where = [];
        $params = [];

        if (!empty($this->cfg['soft_delete'])) {
            $where[] = $trashed ? 't.deleted_at IS NOT NULL' : 't.deleted_at IS NULL';
        }

        $q = trim((string) ($_GET['q'] ?? ''));
        $q = mb_substr($q, 0, 100, 'UTF-8');
        if ($q !== '' && !empty($this->cfg['search'])) {
            $like = [];
            foreach ((array) $this->cfg['search'] as $col) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', (string) $col)) continue;
                $like[] = "t.`$col` LIKE ?";
                $params[] = '%' . $q . '%';
            }
            if ($like) $where[] = '(' . implode(' OR ', $like) . ')';
        }

        if ($where) {
            $sql .= stripos($sql, ' WHERE ') === false ? ' WHERE ' : ' AND ';
            $sql .= implode(' AND ', $where);
        }
        $sql .= ' ORDER BY ' . ($this->cfg['order'] ?? 't.id DESC');
        return [$sql, $params];
    }

    public function index(): void {
        [$sql, $params] = $this->baseQuery();
        $pg = paginate($sql, $params, (int) ($this->cfg['per_page'] ?? 15));
        $this->adminView('crud_list', [
            'module' => $this->module,
            'cfg' => $this->cfg,
            'pg' => $pg,
            'trashed' => false,
            'pageTitle' => $this->cfg['title'],
        ]);
    }

    public function trash(): void {
        if (empty($this->cfg['soft_delete'])) redirect('admin/' . $this->module);
        [$sql, $params] = $this->baseQuery(true);
        $pg = paginate($sql, $params, (int) ($this->cfg['per_page'] ?? 15));
        $this->adminView('crud_list', [
            'module' => $this->module,
            'cfg' => $this->cfg,
            'pg' => $pg,
            'trashed' => true,
            'pageTitle' => 'Sampah: ' . $this->cfg['title'],
        ]);
    }

    public function create(): void {
        $this->adminView('crud_form', [
            'module' => $this->module,
            'cfg' => $this->cfg,
            'row' => null,
            'pageTitle' => 'Tambah ' . $this->cfg['title'],
        ]);
    }

    public function edit($id = null): void {
        $id = (int) $id;
        $row = $id > 0 ? db_row("SELECT * FROM `{$this->table}` WHERE id = ?", [$id]) : null;
        if (!$row) {
            flash('warning', 'Data yang akan diedit tidak ditemukan.');
            redirect('admin/' . $this->module);
        }
        $this->adminView('crud_form', [
            'module' => $this->module,
            'cfg' => $this->cfg,
            'row' => $row,
            'pageTitle' => 'Edit ' . $this->cfg['title'],
        ]);
    }

    public function store(): void {
        $this->save(null);
    }

    public function update($id = null): void {
        $this->save((int) $id);
    }

    protected function save(?int $id): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/' . $this->module);
        verify_csrf();

        $isNew = $id === null;
        $old = $isNew ? null : db_row("SELECT * FROM `{$this->table}` WHERE id = ?", [(int) $id]);
        if (!$isNew && !$old) {
            flash('warning', 'Data yang akan diperbarui tidak ditemukan.');
            redirect('admin/' . $this->module);
        }

        $data = [];
        $errors = [];
        $uploaded = [];
        $replaceAfterCommit = [];
        $hasMultiUpload = $isNew
            && !empty($this->cfg['multi_image'])
            && !empty($_FILES['multi_files']['name'][0]);

        foreach ((array) ($this->cfg['fields'] ?? []) as $f) {
            $name = (string) ($f['name'] ?? '');
            $type = (string) ($f['type'] ?? 'text');
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) continue;

            switch ($type) {
                case 'checkbox':
                    $data[$name] = isset($_POST[$name]) ? 1 : 0;
                    break;

                case 'number':
                    $raw = trim((string) ($_POST[$name] ?? ''));
                    if ($raw === '') {
                        $data[$name] = array_key_exists('default', $f) ? (int) $f['default'] : null;
                    } elseif (filter_var($raw, FILTER_VALIDATE_INT) === false) {
                        $errors[] = ($f['label'] ?? $name) . ' harus berupa bilangan bulat.';
                        $data[$name] = null;
                    } else {
                        $data[$name] = (int) $raw;
                    }
                    break;

                case 'decimal':
                    $raw = trim((string) ($_POST[$name] ?? ''));
                    if ($raw === '') {
                        $data[$name] = null;
                    } else {
                        $normalized = $this->normalizeDecimal($raw);
                        if ($normalized === null) {
                            $errors[] = ($f['label'] ?? $name) . ' harus berupa angka yang valid.';
                            $data[$name] = null;
                        } else {
                            $data[$name] = $normalized;
                        }
                    }
                    break;

                case 'editor':
                    $data[$name] = Security::cleanHtml((string) ($_POST[$name] ?? ''));
                    break;

                case 'image':
                case 'image_protected':
                case 'file':
                case 'audio':
                    if ($name === 'file_path' && $hasMultiUpload) {
                        break;
                    }
                    $file = $_FILES[$name] ?? null;
                    $hasUpload = is_array($file)
                        && ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE
                            || trim((string) ($file['name'] ?? '')) !== '');
                    if ($hasUpload) {
                        $result = match ($type) {
                            'image', 'image_protected' => Upload::image($file, (string) ($f['dir'] ?? 'images')),
                            'file' => Upload::document($file, (string) ($f['dir'] ?? 'documents')),
                            'audio' => Upload::audio($file, (string) ($f['dir'] ?? 'music')),
                        };
                        if (is_array($result)) {
                            $errors[] = (string) ($result['error'] ?? 'File gagal diunggah.');
                        } else {
                            $data[$name] = $result;
                            $uploaded[] = $result;
                            if ($old && !empty($old[$name])) $replaceAfterCommit[] = (string) $old[$name];

                            if (!empty($f['store_meta'])) {
                                $data['file_type'] = strtolower((string) pathinfo($result, PATHINFO_EXTENSION));
                                $data['file_size'] = max(0, (int) ($file['size'] ?? 0));
                            }
                        }
                    }
                    break;

                default:
                    $value = trim((string) ($_POST[$name] ?? ''));
                    if ($value === '' && array_key_exists('default', $f)) $value = (string) $f['default'];
                    $data[$name] = $value === '' ? null : $value;
                    break;
            }

            if (!empty($f['slug_from'])) {
                $source = trim((string) ($_POST[(string) $f['slug_from']] ?? ''));
                $requested = trim((string) ($data[$name] ?? ''));
                $data[$name] = unique_slug($this->table, $requested !== '' ? $requested : $source, (int) ($id ?? 0));
            }

            $this->validateField($f, $data, $old, $isNew, $errors);

            if (!empty($f['unique']) && array_key_exists($name, $data) && $data[$name] !== null && $data[$name] !== '') {
                $exists = db_val(
                    "SELECT id FROM `{$this->table}` WHERE `$name` = ? AND id != ? LIMIT 1",
                    [$data[$name], (int) ($id ?? 0)]
                );
                if ($exists) $errors[] = ($f['label'] ?? $name) . ' sudah digunakan.';
            }
        }

        $this->validateCrossFields($data, $errors);

        $multiRows = [];
        if ($hasMultiUpload) {
            $names = (array) ($_FILES['multi_files']['name'] ?? []);
            foreach ($names as $i => $originalName) {
                $file = [
                    'name' => $originalName,
                    'type' => $_FILES['multi_files']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['multi_files']['tmp_name'][$i] ?? '',
                    'error' => $_FILES['multi_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $_FILES['multi_files']['size'][$i] ?? 0,
                ];
                $result = Upload::image($file, 'gallery');
                if (is_array($result)) {
                    $errors[] = basename((string) $originalName) . ': ' . ($result['error'] ?? 'gagal diunggah');
                    continue;
                }
                $uploaded[] = $result;
                $rowData = $data;
                $rowData['file_path'] = $result;
                if (empty($rowData['title'])) {
                    $rowData['title'] = mb_substr(pathinfo((string) $originalName, PATHINFO_FILENAME), 0, 255, 'UTF-8');
                }
                $multiRows[] = $rowData;
            }
        }

        if ($errors) {
            $this->cleanupUploads($uploaded);
            $this->returnToForm($errors, $id);
        }

        $now = date('Y-m-d H:i:s');
        $pdo = db();
        $savedId = (int) ($id ?? 0);
        $multiCount = 0;

        try {
            $pdo->beginTransaction();

            if ($multiRows) {
                foreach ($multiRows as $rowData) {
                    $rowData['created_at'] = $now;
                    $rowData['updated_at'] = $now;
                    if (!empty($this->cfg['track_user'])) $rowData['created_by'] = (int) (auth_user()['id'] ?? 0);
                    $savedId = db_insert($this->table, $rowData);
                    $multiCount++;
                }
            } elseif ($isNew) {
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
                if (!empty($this->cfg['track_user'])) $data['created_by'] = (int) (auth_user()['id'] ?? 0);
                $savedId = db_insert($this->table, $data);
            } else {
                $data['updated_at'] = $now;
                if (!empty($this->cfg['track_user'])) $data['updated_by'] = (int) (auth_user()['id'] ?? 0);
                db_update($this->table, $data, 'id = ?', [(int) $id]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->cleanupUploads($uploaded);
            $this->returnToForm([$this->databaseErrorMessage($e)], $id);
        }

        // Data publik, statistik, dan daftar beranda menggunakan cache TTL.
        // Naikkan namespace setelah transaksi sukses agar perubahan segera terlihat.
        invalidate_public_cache();

        foreach (array_unique($replaceAfterCommit) as $path) Upload::delete($path);

        if ($multiCount > 0) {
            $this->safeActivity('tambah', $this->module, "Upload massal $multiCount foto");
            flash('success', "$multiCount foto berhasil diunggah.");
        } elseif ($isNew) {
            $this->safeActivity('tambah', (string) $this->cfg['title'], $this->cfg['title'] . " #$savedId ditambahkan");
            flash('success', $this->cfg['title'] . ' berhasil ditambahkan.');
        } else {
            $this->safeActivity('edit', (string) $this->cfg['title'], $this->cfg['title'] . " #$savedId diperbarui");
            flash('success', $this->cfg['title'] . ' berhasil diperbarui.');
        }

        unset($_SESSION['form_errors'], $_SESSION['form_old']);

        if (!empty($this->cfg['push']) && ($_POST['send_push'] ?? '0') === '1' && $savedId > 0) {
            try {
                $saved = db_row("SELECT * FROM `{$this->table}` WHERE id = ?", [$savedId]) ?: $data;
                PushNotifier::flashResult(PushNotifier::fromCrud($this->module, $this->cfg, $saved, $savedId));
            } catch (Throwable $e) {
                flash('warning', 'Data tersimpan, tetapi notifikasi push gagal dikirim.');
            }
        }

        redirect('admin/' . $this->module);
    }

    private function validateField(array $f, array $data, ?array $old, bool $isNew, array &$errors): void {
        $name = (string) ($f['name'] ?? '');
        $type = (string) ($f['type'] ?? 'text');
        $label = (string) ($f['label'] ?? $name);
        $isFile = in_array($type, ['image', 'image_protected', 'file', 'audio'], true);
        $value = $data[$name] ?? null;

        $required = !empty($f['required']);
        if (!empty($f['required_if']) && is_array($f['required_if'])) {
            foreach ($f['required_if'] as $other => $expected) {
                if ((string) ($_POST[$other] ?? '') === (string) $expected) $required = true;
            }
        }

        if ($required) {
            if ($isFile) {
                $bulkImagePresent = $name === 'file_path'
                    && $isNew
                    && !empty($this->cfg['multi_image'])
                    && !empty($_FILES['multi_files']['name'][0]);
                $present = !empty($value) || $bulkImagePresent || (!$isNew && !empty($old[$name]));
                $attempted = trim((string) ($_FILES[$name]['name'] ?? '')) !== '';
                if (!$present && !$attempted) $errors[] = "$label wajib diunggah.";
            } elseif ($type === 'editor') {
                $plain = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $hasMedia = preg_match('/<(?:img|iframe|table)\b/i', (string) $value) === 1;
                if ($plain === '' && !$hasMedia) $errors[] = "$label wajib diisi.";
            } elseif ($value === null || $value === '') {
                $errors[] = "$label wajib diisi.";
            }
        }

        if ($value === null || $value === '') return;

        if ($type === 'select' && isset($f['options']) && !array_key_exists((string) $value, (array) $f['options'])) {
            $errors[] = "$label berisi pilihan yang tidak valid.";
        }

        if ($type === 'select_db' && (!ctype_digit((string) $value) || (int) $value < 1)) {
            $errors[] = "$label berisi pilihan yang tidak valid.";
        }

        if (isset($f['maxlength']) && mb_strlen((string) $value, 'UTF-8') > (int) $f['maxlength']) {
            $errors[] = "$label maksimal " . (int) $f['maxlength'] . ' karakter.';
        }

        if (isset($f['min']) && is_numeric($value) && (float) $value < (float) $f['min']) {
            $errors[] = "$label minimal " . $f['min'] . '.';
        }
        if (isset($f['max']) && is_numeric($value) && (float) $value > (float) $f['max']) {
            $errors[] = "$label maksimal " . $f['max'] . '.';
        }

        if ($type === 'date' && !$this->validDate((string) $value, 'Y-m-d')) {
            $errors[] = "$label bukan tanggal yang valid.";
        }
        if ($type === 'time' && !$this->validDate((string) $value, 'H:i')) {
            $errors[] = "$label bukan jam yang valid.";
        }
        if ($type === 'color' && !preg_match('/^#[0-9a-f]{6}$/i', (string) $value)) {
            $errors[] = "$label bukan kode warna yang valid.";
        }
        if (($f['format'] ?? '') === 'url' && !filter_var((string) $value, FILTER_VALIDATE_URL)) {
            $errors[] = "$label harus berupa URL lengkap yang valid.";
        }
    }

    private function validateCrossFields(array $data, array &$errors): void {
        foreach ((array) ($this->cfg['date_order'] ?? []) as $pair) {
            if (!is_array($pair) || count($pair) < 2) continue;
            [$start, $end] = array_values($pair);
            if (empty($data[$start]) || empty($data[$end])) continue;

            // Jam akhir boleh lebih kecil jika rentang melewati hari yang berbeda.
            $isTimePair = str_ends_with((string) $start, '_time') && str_ends_with((string) $end, '_time');
            if ($isTimePair && !empty($data['start_date']) && !empty($data['end_date'])
                && (string) $data['end_date'] > (string) $data['start_date']) {
                continue;
            }

            if ((string) $data[$end] < (string) $data[$start]) {
                $errors[] = $isTimePair
                    ? 'Jam selesai tidak boleh lebih awal daripada jam mulai pada tanggal yang sama.'
                    : 'Tanggal selesai tidak boleh lebih awal daripada tanggal mulai.';
            }
        }

        if (!empty($this->cfg['validate']) && is_callable($this->cfg['validate'])) {
            foreach ((array) call_user_func($this->cfg['validate'], $data) as $message) {
                if (is_string($message) && $message !== '') $errors[] = $message;
            }
        }
    }

    private function normalizeDecimal(string $raw): ?float {
        $value = preg_replace('/[\s\x{00A0}]/u', '', trim($raw));
        if ($value === null || $value === '') return null;
        if (!preg_match('/^-?[0-9.,]+$/', $value)) return null;

        $comma = strrpos($value, ',');
        $dot = strrpos($value, '.');

        if ($comma !== false && $dot !== false) {
            if ($comma > $dot) {
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            } else {
                $value = str_replace(',', '', $value);
            }
        } elseif ($comma !== false) {
            if (substr_count($value, ',') === 1) {
                $value = str_replace(',', '.', $value);
            } else {
                $last = strrpos($value, ',');
                $integer = str_replace(',', '', substr($value, 0, $last));
                $value = $integer . '.' . substr($value, $last + 1);
            }
        } elseif (substr_count($value, '.') > 1) {
            $last = strrpos($value, '.');
            $integer = str_replace('.', '', substr($value, 0, $last));
            $value = $integer . '.' . substr($value, $last + 1);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function validDate(string $value, string $format): bool {
        $date = DateTime::createFromFormat('!' . $format, $value);
        return $date !== false && $date->format($format) === $value;
    }

    private function cleanupUploads(array $paths): void {
        foreach (array_unique(array_filter($paths, 'is_string')) as $path) Upload::delete($path);
    }

    private function returnToForm(array $errors, ?int $id): void {
        $_SESSION['form_errors'] = array_values(array_unique(array_filter($errors)));
        $_SESSION['form_old'] = $_POST;
        redirect('admin/' . $this->module . ($id === null ? '/create' : '/edit/' . $id));
    }

    private function databaseErrorMessage(Throwable $e): string {
        $code = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        if ($code === '23000' || str_contains($message, 'duplicate entry')) {
            return 'Data tidak dapat disimpan karena ada nilai yang sudah digunakan. Periksa slug, nama, atau kode unik.';
        }
        if (str_contains($message, 'unknown column') || str_contains($message, "doesn't exist") || str_contains($message, 'base table')) {
            return 'Struktur database belum sesuai dengan versi website. Jalankan migrasi database terbaru atau hubungi pengelola teknis.';
        }
        if (str_contains($message, 'data too long')) {
            return 'Salah satu isian terlalu panjang. Ringkas isian lalu coba kembali.';
        }
        if (str_contains($message, 'cannot be null')) {
            return 'Ada data wajib yang masih kosong. Periksa kembali formulir.';
        }
        return 'Data gagal disimpan. Tidak ada perubahan yang diterapkan. Silakan coba kembali atau periksa log server.';
    }

    private function safeActivity(string $action, string $module, string $description): void {
        try {
            log_activity($action, $module, $description);
        } catch (Throwable $e) {
            // Penyimpanan utama tidak boleh gagal hanya karena audit log bermasalah.
        }
    }

    public function delete($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/' . $this->module);
        verify_csrf();

        $id = (int) $id;
        $row = $id > 0 ? db_row("SELECT * FROM `{$this->table}` WHERE id = ?", [$id]) : null;
        if (!$row) {
            flash('warning', 'Data tidak ditemukan atau sudah dihapus.');
            redirect('admin/' . $this->module);
        }

        $hardDeleteFiles = [];
        $pdo = db();
        try {
            $pdo->beginTransaction();
            if (!empty($this->cfg['soft_delete'])) {
                db_update($this->table, [
                    'deleted_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = ?', [$id]);
            } else {
                foreach ((array) ($this->cfg['fields'] ?? []) as $f) {
                    if (in_array($f['type'] ?? '', ['image', 'image_protected', 'file', 'audio'], true)
                        && !empty($row[$f['name']])) {
                        $hardDeleteFiles[] = (string) $row[$f['name']];
                    }
                }
                db_exec("DELETE FROM `{$this->table}` WHERE id = ?", [$id]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $this->databaseErrorMessage($e));
            redirect('admin/' . $this->module);
        }

        invalidate_public_cache();
        foreach ($hardDeleteFiles as $path) Upload::delete($path);
        $this->safeActivity('hapus', (string) $this->cfg['title'], $this->cfg['title'] . " #$id dihapus");
        flash('success', !empty($this->cfg['soft_delete'])
            ? 'Data berhasil dipindahkan ke sampah.'
            : 'Data berhasil dihapus permanen.');
        redirect('admin/' . $this->module . (str_contains($_SERVER['HTTP_REFERER'] ?? '', '/trash') ? '/trash' : ''));
    }

    public function restore($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/' . $this->module);
        verify_csrf();
        if (empty($this->cfg['soft_delete'])) redirect('admin/' . $this->module);

        $id = (int) $id;
        $changed = db_update($this->table, [
            'deleted_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND deleted_at IS NOT NULL', [$id]);

        if ($changed) {
            invalidate_public_cache();
            $this->safeActivity('restore', (string) $this->cfg['title'], $this->cfg['title'] . " #$id dipulihkan");
            flash('success', 'Data berhasil dipulihkan.');
        } else {
            flash('warning', 'Data tidak ditemukan atau sudah aktif.');
        }
        redirect('admin/' . $this->module . '/trash');
    }

    public function toggle($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/' . $this->module);
        verify_csrf();

        $field = (string) ($_POST['field'] ?? 'is_active');
        $allowed = array_values((array) ($this->cfg['toggles'] ?? ['is_active']));
        if (!in_array($field, $allowed, true) || !preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            flash('danger', 'Kolom status tidak valid.');
            redirect('admin/' . $this->module);
        }

        db_exec(
            "UPDATE `{$this->table}` SET `$field` = CASE WHEN COALESCE(`$field`,0)=1 THEN 0 ELSE 1 END, updated_at = NOW() WHERE id = ?",
            [(int) $id]
        );
        invalidate_public_cache();
        redirect('admin/' . $this->module);
    }

    public function reorder(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') $this->json(['ok' => false, 'error' => 'Metode tidak valid.']);
        verify_csrf();
        if (empty($this->cfg['reorder'])) $this->json(['ok' => false, 'error' => 'Urutan tidak didukung modul ini.']);

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));
        if (!$ids) $this->json(['ok' => false, 'error' => 'Data urutan kosong.']);

        $pdo = db();
        try {
            $pdo->beginTransaction();
            foreach ($ids as $i => $rowId) {
                db_exec("UPDATE `{$this->table}` SET sort_order = ?, updated_at = NOW() WHERE id = ?", [$i + 1, $rowId]);
            }
            $pdo->commit();
            invalidate_public_cache();
            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Urutan gagal disimpan.']);
        }
    }
}
