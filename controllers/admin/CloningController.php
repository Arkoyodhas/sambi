<?php

/**
 * FITUR KHUSUS SUPER ADMIN: Cloning Website.
 * Paket dibuat bertahap (beberapa request pendek) agar tidak terkena batas waktu hosting.
 * Yang ikut: kode program, struktur database terbaru, tampilan/desain, dan pengaturan sistem.
 * Yang tidak ikut: berita, dokumen, foto perangkat, data warga, log, akun, dan konten operasional lain.
 */
class CloningController extends Controller {

    public array $actions = ['index', 'start', 'step', 'download', 'cancel'];

    private const STEP_SECONDS = 4.0;
    private const STEP_BYTES = 8 * 1024 * 1024;
    private const JOB_TTL = 21600;

    private const FEATURE_OPTIONS = [
        'population' => ['Data Penduduk & Keluarga', 'Penduduk, keluarga, wilayah, statistik penduduk'],
        'news' => ['Berita & Informasi', 'Berita, kategori, komentar, pengumuman, dan agenda'],
        'gallery' => ['Galeri', 'Album, foto, dan media galeri'],
        'services' => ['Layanan Administrasi', 'Jenis layanan, permohonan, dan cek status'],
        'complaints' => ['Pengaduan Warga', 'Pengaduan, tanggapan, dan cek status'],
        'contact' => ['Kontak Desa', 'Form kontak publik dan pesan masuk admin'],
        'transparency' => ['Transparansi & Pembangunan', 'APBDes dan data pembangunan'],
        'documents' => ['Dokumen Publik', 'Pengelolaan dan unduhan dokumen'],
        'village_profile' => ['Profil Desa', 'Profil, pemerintahan, perangkat, SOTK, dan SDGs'],
        'public_data' => ['Data Publik', 'IDM, bansos, dan stunting'],
        'economy' => ['UMKM & Potensi', 'Produk UMKM dan potensi desa'],
        'static_pages' => ['Halaman Statis', 'Halaman tambahan yang dibuat dari admin'],
        'music_event' => ['Musik & Event Effect', 'Musik latar dan efek event website'],
        'entertainment' => ['Zona Hiburan', 'Game, kuis, dan fitur hiburan pengunjung'],
        'live_chat' => ['Live Chat', 'Percakapan langsung pengunjung dengan admin'],
        'push_pwa' => ['PWA & Notifikasi Push', 'Instalasi aplikasi dan push notification'],
        'visitor_audit' => ['Statistik & Audit Pengunjung', 'Kunjungan, screenshot log, dan audit'],
        'feedback' => ['Feedback Pengunjung', 'Form masukan dan hasil survei'],
        'face_auth' => ['Login Wajah', 'Dataset dan autentikasi wajah admin'],
    ];

    /** Tabel sistem/desain yang datanya ikut disalin (bukan konten desa). */
    private const COPY_TABLES = [
        'roles' => '',
        'permissions' => '',
        'role_permissions' => '',
        'news_categories' => '',
        'services' => 'deleted_at IS NULL',
        'service_requirements' => '',
        'feedback_questions' => '',
        'chat_quick_replies' => '',
        'event_effects' => 'deleted_at IS NULL',
        'event_effect_schedules' => '',
        'background_music' => '',
    ];

    /** Pengaturan identitas, rahasia, dan konten desa sumber yang tidak boleh terbawa. */
    private const RESET_SETTINGS = [
        'logo', 'favicon', 'og_image', 'kepala_desa_foto', 'pwa_icon_192', 'pwa_icon_512',
        'facebook', 'instagram', 'youtube', 'twitter', 'tiktok', 'home_social_items',
        'fonnte_token', 'fonnte_enabled', 'wa_admin_number', 'vapid_public', 'vapid_private',
        'wa_route_chat', 'wa_route_surat', 'wa_route_aduan', 'wa_route_kontak', 'wa_route_feedback', 'wa_route_keamanan',
        'maintenance_mode', 'maintenance_message', 'maintenance_until', 'luas_wilayah', 'sambutan',
        'pwa_description', 'jam_pelayanan', 'map_zoom',
    ];

    /** Pengaturan yang dihapus agar migrasi/seed default dijalankan ulang di website baru. */
    private const DROP_SETTINGS = [
        'entertainment_schema_version', 'performance_schema_last_attempt', 'livechat_schema_version',
    ];

    /** Pengaturan berisi file tampilan (background, video hero, layer hero) yang ikut disalin. */
    private const DESIGN_FILE_SETTINGS = [
        'hero_image', 'hero_video', 'hero_layer_sky', 'hero_layer_hills', 'hero_layer_village',
        'hero_diorama_image', 'verify_logo', 'music_url',
    ];

    private const STORE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'mp4', 'webm', 'mov', 'mp3', 'ogg', 'wav', 'm4a', 'woff', 'woff2', 'zip', 'gz', 'pdf'];

    public function __construct() {
        Auth::requireSuperAdmin();
    }

    public function index(): void {
        $this->adminView('cloning', [
            'pageTitle' => 'Cloning Website',
            'featureOptions' => self::FEATURE_OPTIONS,
        ]);
    }

    /* ================= Tahap 1: validasi & buat pekerjaan ================= */
    public function start(): void {
        $this->guardPost();
        if (!function_exists('gzdeflate')) $this->fail('Ekstensi PHP zlib belum aktif. Aktifkan melalui cPanel (Select PHP Version → Extensions).');

        $f = static fn(string $key, string $default = ''): string => trim((string) ($_POST[$key] ?? $default));
        $nama = $f('village_name');
        $adminUser = $f('admin_username', 'admin');
        $adminPass = (string) ($_POST['admin_password'] ?? '');
        $adminEmail = $f('admin_email', 'admin@desa.id');

        if ($nama === '' || mb_strlen($nama) > 150) $this->fail('Nama desa wajib diisi dan maksimal 150 karakter.');
        if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $adminUser)) $this->fail('Username admin harus 3-30 karakter (huruf, angka, titik, atau garis bawah).');
        if (strlen($adminPass) < 8) $this->fail('Password admin minimal 8 karakter.');
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $this->fail('Email admin tidak valid.');
        $email = $f('email');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->fail('Email desa tidak valid.');

        $requested = $_POST['disabled_features'] ?? [];
        $disabled = array_values(array_intersect(array_keys(self::FEATURE_OPTIONS), array_map('strval', is_array($requested) ? $requested : [])));

        $this->cleanupOldJobs();
        $id = bin2hex(random_bytes(12));
        $dir = $this->jobDir($id);
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) $this->fail('Folder storage/clone_jobs tidak dapat dibuat. Pastikan folder storage dapat ditulis (permission 755/775).');

        $state = [
            'id' => $id,
            'owner' => (int) (Auth::user()['id'] ?? 0),
            'created' => time(),
            'phase' => 'sql',
            'message' => 'Menyiapkan struktur database...',
            'tables' => $this->listTables(),
            'table_index' => 0,
            'file_index' => 0,
            'files_total' => 0,
            'bytes_total' => 0,
            'bytes_done' => 0,
            'zip_size' => 0,
            'cd_size' => 0,
            'zip_count' => 0,
            'sql_size' => 0,
            'disabled' => $disabled,
            'sample' => !empty($_POST['include_sample']),
            'admin' => ['username' => $adminUser, 'email' => $adminEmail, 'hash' => password_hash($adminPass, PASSWORD_BCRYPT)],
            'ident' => [
                'village_name' => $nama,
                'village_tagline' => $f('village_tagline'),
                'village_description' => $f('village_description'),
                'address' => $f('address'),
                'kecamatan' => $f('kecamatan'),
                'kabupaten' => $f('kabupaten'),
                'provinsi' => $f('provinsi'),
                'kode_pos' => $f('kode_pos'),
                'phone' => $f('phone'),
                'whatsapp' => $f('whatsapp'),
                'email' => $email,
                'kepala_desa_nama' => $f('kepala_desa_nama'),
                'map_lat' => $f('map_lat'),
                'map_lng' => $f('map_lng'),
            ],
            'colors' => [
                'copy' => !empty($_POST['keep_colors']),
                'primary_color' => $this->validColor($f('primary_color'), ''),
                'secondary_color' => $this->validColor($f('secondary_color'), ''),
                'accent_color' => $this->validColor($f('accent_color'), ''),
            ],
        ];

        [$files, $bytes] = $this->collectFiles();
        file_put_contents($dir . '/files.json', json_encode($files, JSON_UNESCAPED_SLASHES));
        $state['files_total'] = count($files);
        $state['bytes_total'] = $bytes;
        $state['sql_size'] = (int) file_put_contents($dir . '/desa_db.sql', $this->sqlHeader($nama));
        $this->saveState($state);

        $this->json(['ok' => true, 'job' => $id, 'progress' => 1, 'message' => $state['message'], 'done' => false]);
    }

    /* ================= Tahap 2..n: kerjakan sebagian, lalu kembali ================= */
    public function step(): void {
        $this->guardPost();
        $state = $this->loadState((string) ($_POST['job'] ?? ''));
        @set_time_limit(60);
        @ignore_user_abort(true);
        $deadline = microtime(true) + self::STEP_SECONDS;

        try {
            if ($state['phase'] === 'sql') $this->stepSql($state, $deadline);
            elseif ($state['phase'] === 'files') $this->stepFiles($state, $deadline);
            elseif ($state['phase'] === 'finalize') $this->stepFinalize($state);
        } catch (Throwable $e) {
            error_log('Cloning step: ' . $e->getMessage());
            $this->saveState($state);
            $this->fail('Gagal pada tahap "' . $state['phase'] . '": ' . $e->getMessage());
        }
        $this->saveState($state);
        if ($state['phase'] === 'done') {
            foreach (['desa_db.sql', 'files.json', 'central.bin'] as $tmp) @unlink($this->jobDir($state['id']) . '/' . $tmp);
        }

        $this->json([
            'ok' => true,
            'job' => $state['id'],
            'progress' => $this->progress($state),
            'message' => $state['message'],
            'done' => $state['phase'] === 'done',
            'download' => $state['phase'] === 'done' ? url('admin/cloning/download/' . $state['id']) : null,
            'size' => $state['phase'] === 'done' ? (int) @filesize($this->jobDir($state['id']) . '/package.zip') : 0,
        ]);
    }

    public function download(string $id = ''): void {
        $state = $this->loadState($id, false);
        $zipFile = $this->jobDir($state['id']) . '/package.zip';
        if (($state['phase'] ?? '') !== 'done' || !is_file($zipFile)) {
            flash('danger', 'Paket belum selesai dibuat atau sudah kedaluwarsa. Silakan buat ulang.');
            redirect('admin/cloning');
        }
        $name = trim('website-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($state['ident']['village_name'])) . '-' . date('Ymd'), '-') . '.zip';
        @set_time_limit(0);
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($zipFile));
        header('Cache-Control: no-store, private');
        $fh = fopen($zipFile, 'rb');
        while ($fh && !feof($fh)) { echo fread($fh, 1048576); flush(); }
        if ($fh) fclose($fh);
        exit;
    }

    public function cancel(): void {
        $this->guardPost();
        $id = (string) ($_POST['job'] ?? '');
        if (preg_match('/^[a-f0-9]{24}$/', $id)) $this->removeDir($this->jobDir($id));
        $this->json(['ok' => true]);
    }

    /* ================= Database ================= */
    private function stepSql(array &$state, float $deadline): void {
        $sqlFile = $this->jobDir($state['id']) . '/desa_db.sql';
        $out = fopen($sqlFile, 'c+b');
        if (!$out) throw new RuntimeException('File SQL sementara tidak dapat ditulis.');
        // Buang tulisan setengah jadi bila tahap sebelumnya terputus.
        ftruncate($out, (int) $state['sql_size']);
        fseek($out, 0, SEEK_END);
        $tables = $state['tables'];
        while ($state['table_index'] < count($tables) && microtime(true) < $deadline) {
            $table = $tables[$state['table_index']];
            fwrite($out, $this->tableSql($table, $state));
            $state['table_index']++;
            $state['message'] = 'Menyalin struktur tabel ' . $state['table_index'] . '/' . count($tables) . ' (' . $table . ')';
        }
        if ($state['table_index'] >= count($tables)) {
            fwrite($out, $this->contentSql($state));
            fwrite($out, "\nSET FOREIGN_KEY_CHECKS=1;\nCOMMIT;\n");
            $state['phase'] = 'files';
            $state['message'] = 'Database siap. Mengemas file website...';
        }
        fflush($out);
        $state['sql_size'] = ftell($out);
        fclose($out);
    }

    private function listTables(): array {
        $rows = db()->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        return array_values(array_filter(array_map(static fn($r) => (string) $r[0], $rows), static fn($t) => preg_match('/^[A-Za-z0-9_]+$/', $t)));
    }

    private function tableSql(string $table, array $state): string {
        $row = db()->query('SHOW CREATE TABLE `' . $table . '`')->fetch(PDO::FETCH_NUM);
        $create = preg_replace('/\sAUTO_INCREMENT=\d+/i', '', (string) ($row[1] ?? ''));
        $create = preg_replace('/\sDEFINER=`[^`]*`@`[^`]*`/i', '', $create);
        $sql = "\n-- ----------------------------------------\n-- Tabel `$table`\n-- ----------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `$table`;\n" . $create . ";\n";

        if (array_key_exists($table, self::COPY_TABLES)) {
            $where = self::COPY_TABLES[$table];
            $rows = db()->query('SELECT * FROM `' . $table . '`' . ($where ? ' WHERE ' . $where : ''))->fetchAll(PDO::FETCH_ASSOC);
            $rows = array_map(fn($r) => $this->replaceIdentity($r, $state), $rows);
            $sql .= $this->insertSql($table, $rows);
        } elseif ($table === 'menus') {
            $sql .= $this->insertSql($table, $this->menuRows());
        } elseif ($table === 'site_settings') {
            $sql .= $this->insertSql($table, $this->settingRows($state));
        } elseif ($table === 'village_profiles') {
            $sql .= $this->insertSql($table, $this->profileRows($state));
        } elseif ($table === 'faqs' && $state['sample']) {
            $rows = db()->query('SELECT question, answer, sort_order, is_active FROM faqs WHERE deleted_at IS NULL ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
            $sql .= $this->insertSql($table, array_map(fn($r) => $this->replaceIdentity($r, $state), $rows));
        }
        return $sql;
    }

    private function menuRows(): array {
        $rows = db()->query('SELECT * FROM menus ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        // Tautan eksternal milik desa sumber (mis. domain e-perpus/e-commerce) tidak dibawa.
        $rows = array_values(array_filter($rows, static fn($r) => !preg_match('#^(?:https?:)?//#i', trim((string) $r['url']))));
        $ids = array_column($rows, 'id');
        $rows = array_values(array_filter($rows, static fn($r) => $r['parent_id'] === null || in_array($r['parent_id'], $ids)));
        $parents = array_column($rows, 'parent_id');
        return array_values(array_filter($rows, static fn($r) => trim((string) $r['url']) !== '#' || in_array($r['id'], $parents)));
    }

    private function settingRows(array $state): array {
        $settings = db()->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $ident = $state['ident'];
        $nama = $ident['village_name'];

        foreach (self::RESET_SETTINGS as $key) if (array_key_exists($key, $settings)) $settings[$key] = '';
        foreach (self::DROP_SETTINGS as $key) unset($settings[$key]);
        foreach (self::DESIGN_FILE_SETTINGS as $key) {
            $value = (string) ($settings[$key] ?? '');
            if ($value !== '' && !preg_match('#^(?:https?:)?//#i', $value) && !is_file(BASE_PATH . '/uploads/' . ltrim($value, '/'))) $settings[$key] = '';
        }
        foreach ($ident as $key => $value) $settings[$key] = $value;

        $settings['meta_title'] = $nama . ' - Website Resmi Pemerintah Desa';
        $settings['meta_description'] = 'Website resmi ' . $nama . ($ident['kabupaten'] !== '' ? ', Kabupaten ' . $ident['kabupaten'] : '') . '. Informasi, layanan administrasi, dan transparansi desa.';
        $settings['watermark_text'] = $nama;
        $settings['pwa_name'] = $nama;
        $settings['pwa_short_name'] = mb_substr($this->bareName($nama), 0, 30);
        $settings['hero_video_enabled'] = ($settings['hero_video'] ?? '') !== '' ? ($settings['hero_video_enabled'] ?? '0') : '0';
        $settings['maintenance_mode'] = '0';
        $settings['fonnte_enabled'] = '0';
        $settings['map_zoom'] = '14';
        $settings['jam_pelayanan'] = 'Senin - Jumat: 08.00 - 15.00 WIB';
        $settings['asset_version'] = (string) time();
        $settings['sambutan'] = $state['sample']
            ? 'Assalamualaikum warahmatullahi wabarakatuh. Selamat datang di website resmi ' . $nama . '. Website ini kami hadirkan sebagai wujud keterbukaan informasi publik serta untuk mempermudah pelayanan administrasi bagi seluruh warga. (Teks contoh — silakan ubah melalui menu Pengaturan.)'
            : '';

        $colors = $state['colors'];
        if (!$colors['copy']) {
            foreach (['primary_color', 'secondary_color', 'accent_color'] as $key) {
                if ($colors[$key] !== '') $settings[$key] = $colors[$key];
            }
            if (!empty($colors['primary_color'])) $settings['pwa_theme_color'] = $colors['primary_color'];
        }

        $featureSettings = [
            'music_event' => ['music_enabled', 'bgfx_enabled'],
            'entertainment' => ['entertainment_enabled'],
            'live_chat' => ['livechat_enabled', 'livechat_wa_notify'],
            'push_pwa' => ['webnotif_enabled'],
            'feedback' => ['feedback_enabled'],
            'face_auth' => ['face_login_enabled'],
        ];
        foreach ($featureSettings as $feature => $keys) {
            if (in_array($feature, $state['disabled'], true)) foreach ($keys as $key) $settings[$key] = '0';
        }

        $rows = [];
        foreach ($settings as $key => $value) $rows[] = ['setting_key' => (string) $key, 'setting_value' => $value];
        return $rows;
    }

    private function profileRows(array $state): array {
        $rows = db()->query('SELECT section_key, title, sort_order FROM village_profiles ORDER BY sort_order, id')->fetchAll(PDO::FETCH_ASSOC);
        $nama = $state['ident']['village_name'];
        foreach ($rows as &$r) {
            $r['title'] = $this->replaceIdentity(['t' => $r['title']], $state)['t'];
            $r['content'] = $state['sample']
                ? '<p>Tuliskan informasi ' . e(mb_strtolower($r['title'])) . ' ' . e($nama) . ' di sini. (Teks contoh — ubah melalui menu Profil Desa.)</p>'
                : '';
            $r['image'] = null;
        }
        return $rows;
    }

    private function contentSql(array $state): string {
        $ident = $state['ident'];
        $nama = $ident['village_name'];
        $admin = $state['admin'];
        $roleId = (int) (db_val("SELECT id FROM roles WHERE slug = 'super-admin' LIMIT 1") ?: 1);
        $now = date('Y-m-d H:i:s');

        $sql = "\n-- ============================================================\n-- AKUN SUPER ADMIN BARU\n-- ============================================================\n";
        $sql .= $this->insertSql('users', [[
            'id' => 1, 'name' => 'Administrator ' . $nama, 'username' => $admin['username'], 'email' => $admin['email'],
            'password' => $admin['hash'], 'role_id' => $roleId, 'is_active' => 1, 'must_change_password' => 1,
            'failed_attempts' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]]);
        if (in_array('user_roles', $state['tables'], true)) $sql .= $this->insertSql('user_roles', [['user_id' => 1, 'role_id' => $roleId]]);

        if (!$state['sample']) return $sql;

        $sql .= "\n-- ============================================================\n-- CONTOH TEKS (boleh diedit atau dihapus dari panel admin)\n-- ============================================================\n";
        $kades = $ident['kepala_desa_nama'] !== '' ? $ident['kepala_desa_nama'] : 'Nama Kepala Desa';
        $officials = [
            [$kades, 'Kepala Desa', 1, 1],
            ['Nama Sekretaris Desa', 'Sekretaris Desa', 2, 1],
            ['Nama Kaur Keuangan', 'Kaur Keuangan', 3, 1],
            ['Nama Kasi Pemerintahan', 'Kasi Pemerintahan', 4, 0],
        ];
        $rows = [];
        foreach ($officials as [$n, $j, $o, $home]) {
            $rows[] = ['nama' => $n, 'jabatan' => $j, 'kategori' => 'pemerintah', 'foto' => null, 'sort_order' => $o, 'is_active' => 1, 'show_on_home' => $home, 'created_at' => $now, 'updated_at' => $now];
        }
        $sql .= $this->insertSql('village_officials', $this->onlyExistingColumns('village_officials', $rows));

        $catId = db_val('SELECT id FROM news_categories ORDER BY id LIMIT 1');
        $content = '<p>Selamat datang di website resmi ' . e($nama) . '. Melalui website ini masyarakat dapat memperoleh informasi kegiatan desa, mengajukan layanan surat secara online, serta menyampaikan aspirasi dan pengaduan.</p><p><em>Ini adalah berita contoh. Silakan ubah atau hapus melalui menu Berita di panel admin.</em></p>';
        $sql .= $this->insertSql('news', $this->onlyExistingColumns('news', [[
            'title' => 'Selamat Datang di Website Resmi ' . $nama,
            'slug' => 'selamat-datang-di-website-resmi-' . trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-'),
            'category_id' => $catId ? (int) $catId : null,
            'excerpt' => 'Website resmi ' . $nama . ' kini hadir sebagai pusat informasi dan layanan desa.',
            'content' => $content, 'thumbnail' => null, 'status' => 'published', 'published_at' => $now,
            'views' => 0, 'is_featured' => 1, 'allow_comments' => 1, 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]]));

        $sql .= $this->insertSql('announcements', $this->onlyExistingColumns('announcements', [[
            'title' => 'Website Resmi ' . $nama . ' Telah Diluncurkan',
            'content' => '<p>Pengumuman contoh. Silakan ubah atau hapus melalui menu Pengumuman di panel admin.</p>',
            'attachment' => null, 'is_important' => 0, 'is_active' => 1, 'start_date' => date('Y-m-d'), 'end_date' => null,
            'created_by' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]]));
        return $sql;
    }

    private function onlyExistingColumns(string $table, array $rows): array {
        if (!$rows) return [];
        $cols = db()->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
        return array_map(static fn($r) => array_intersect_key($r, array_flip($cols)), $rows);
    }

    private function insertSql(string $table, array $rows): string {
        if (!$rows) return '';
        $cols = array_keys($rows[0]);
        $head = 'INSERT INTO `' . $table . '` (`' . implode('`,`', $cols) . '`) VALUES ';
        $sql = '';
        foreach (array_chunk($rows, 100) as $chunk) {
            $values = [];
            foreach ($chunk as $row) {
                $values[] = '(' . implode(',', array_map(fn($v) => $this->sqlValue($v), array_values($row))) . ')';
            }
            $sql .= $head . "\n" . implode(",\n", $values) . ";\n";
        }
        return $sql;
    }

    private function sqlValue($v): string {
        if ($v === null) return 'NULL';
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_int($v) || is_float($v)) return (string) $v;
        return db()->quote((string) $v);
    }

    private function sqlHeader(string $nama): string {
        return "-- ============================================================\n" .
            "-- Installer database website " . str_replace(["\r", "\n"], ' ', $nama) . "\n" .
            "-- Dibuat oleh fitur Cloning Website: " . date('d/m/Y H:i') . "\n" .
            "-- Import file ini ke database kosong melalui phpMyAdmin.\n" .
            "-- ============================================================\n" .
            "SET NAMES utf8mb4;\nSET time_zone = '+07:00';\nSET FOREIGN_KEY_CHECKS=0;\n" .
            "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\nSTART TRANSACTION;\n";
    }

    /** Ganti nama desa/wilayah sumber pada teks sistem yang disalin (mis. template surat). */
    private function replaceIdentity(array $row, array $state): array {
        static $map = null;
        if ($map === null) {
            $map = [];
            $src = db()->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key IN ('village_name','kecamatan','kabupaten','provinsi','kepala_desa_nama')")->fetchAll(PDO::FETCH_KEY_PAIR);
            $new = $state['ident'];
            $pairs = [[(string) ($src['village_name'] ?? ''), $new['village_name']], [$this->bareName((string) ($src['village_name'] ?? '')), $this->bareName($new['village_name'])]];
            foreach (['kecamatan', 'kabupaten', 'provinsi', 'kepala_desa_nama'] as $k) $pairs[] = [(string) ($src[$k] ?? ''), $new[$k]];
            foreach ($pairs as [$from, $to]) {
                if (mb_strlen(trim($from)) < 3 || trim($to) === '') continue;
                $map[$from] = $to;
                $map[mb_strtoupper($from)] = mb_strtoupper($to);
            }
            uksort($map, static fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        }
        if (!$map) return $row;
        foreach ($row as $k => $v) if (is_string($v) && $v !== '') $row[$k] = strtr($v, $map);
        return $row;
    }

    private function bareName(string $name): string {
        return trim(preg_replace('/^(desa|kelurahan|nagari|gampong|kampung)\s+/i', '', trim($name)));
    }

    /* ================= File ================= */
    private function collectFiles(): array {
        $base = realpath(BASE_PATH);
        if ($base === false) $this->fail('Direktori aplikasi tidak dapat dibaca.');

        $excludeFiles = [
            'controllers/admin/CloningController.php', 'views/admin/cloning.php', 'router.php',
            'config/config.php', 'config/features.php', 'error_log', '.gitconfig', 'README.md',
        ];
        $skipDirs = ['.git', '.emergent', '.screenshots', 'database', 'plan', 'storage', 'uploads', 'node_modules', 'test_reports', 'tests', 'memory'];

        $files = [];
        $bytes = 0;
        // Folder data/riwayat besar tidak dimasuki sama sekali agar pemindaian cepat.
        $filter = new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $f, $key, RecursiveDirectoryIterator $it) use ($skipDirs): bool {
                return !($f->isDir() && $it->getSubPath() === '' && in_array($f->getFilename(), $skipDirs, true));
            }
        );
        foreach (new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            if (!$file->isFile() || !$file->isReadable()) continue;
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            if (in_array($rel, $excludeFiles, true)) continue;
            if (preg_match('/(^|\/)error_log$|\.(zip|log|bak|tmp)$/i', $rel)) continue;
            $files[] = $rel;
            $bytes += (int) $file->getSize();
        }

        // File pelindung folder storage & uploads (hanya 2 tingkat, tanpa membaca isi data).
        foreach (['storage', 'uploads'] as $root) {
            $dirs = [$root];
            foreach (@scandir($base . '/' . $root) ?: [] as $name) {
                if ($name[0] === '.' || $name === 'clone_jobs') continue;
                if (is_dir($base . '/' . $root . '/' . $name)) $dirs[] = $root . '/' . $name;
            }
            foreach ($dirs as $dir) {
                foreach (['.htaccess', 'index.html', 'index.php', '.gitkeep'] as $guard) {
                    if (is_file($base . '/' . $dir . '/' . $guard)) {
                        $files[] = $dir . '/' . $guard;
                        $bytes += (int) filesize($base . '/' . $dir . '/' . $guard);
                    }
                }
            }
        }

        // File tampilan: background hero, video hero, layer hero, ikon verifikasi, dan musik latar.
        foreach ($this->designUploads() as $rel) {
            $files[] = $rel;
            $bytes += (int) filesize($base . '/' . $rel);
        }

        return [array_values(array_unique($files)), $bytes];
    }

    private function designUploads(): array {
        $paths = [];
        $in = implode(',', array_map(static fn($k) => db()->quote($k), self::DESIGN_FILE_SETTINGS));
        foreach (db()->query("SELECT setting_value FROM site_settings WHERE setting_key IN ($in)")->fetchAll(PDO::FETCH_COLUMN) as $v) $paths[] = (string) $v;
        try {
            foreach (db()->query('SELECT file_path FROM background_music')->fetchAll(PDO::FETCH_COLUMN) as $v) $paths[] = (string) $v;
        } catch (Throwable $e) {}

        $out = [];
        foreach ($paths as $p) {
            $p = ltrim(str_replace('\\', '/', trim($p)), '/');
            if ($p === '' || str_contains($p, '..') || preg_match('#^(?:https?:)?//#i', $p)) continue;
            if (str_starts_with($p, 'uploads/')) $p = substr($p, 8);
            if (is_file(BASE_PATH . '/uploads/' . $p)) $out[] = 'uploads/' . $p;
        }
        return array_values(array_unique($out));
    }

    private function stepFiles(array &$state, float $deadline): void {
        $dir = $this->jobDir($state['id']);
        $files = json_decode((string) file_get_contents($dir . '/files.json'), true) ?: [];
        $base = realpath(BASE_PATH);
        $batchBytes = 0;
        $total = count($files);
        $zip = $this->zipOpen($state);

        while ($state['file_index'] < $total && microtime(true) < $deadline && $batchBytes < self::STEP_BYTES) {
            $rel = $files[$state['file_index']];
            $path = $base . '/' . $rel;
            $state['file_index']++;
            if (!is_file($path)) continue;
            $size = (int) filesize($path);

            if ($rel === 'core/App.php') {
                $this->zipAddString($zip, $rel, (string) preg_replace("/^.*'cloning'\\s*=>.*\\R/m", '', (string) file_get_contents($path)));
            } elseif ($rel === 'views/layouts/admin_header.php') {
                $layout = (string) file_get_contents($path);
                $layout = (string) preg_replace("/\\s*<\\?php if \\(Auth::isSuperAdmin\\(\\) && is_file\\(BASE_PATH \\. '\\/controllers\\/admin\\/CloningController\\.php'\\)\\): \\?>.*?<\\?php endif; \\?>/s", '', $layout);
                $this->zipAddString($zip, $rel, $layout);
            } else {
                $this->zipAddFile($zip, $path, $rel);
            }
            $batchBytes += $size;
            $state['bytes_done'] += $size;
        }
        $this->zipCommit($zip, $state);

        $state['message'] = 'Mengemas file ' . min($state['file_index'], $total) . '/' . $total;
        if ($state['file_index'] >= $total) {
            $state['phase'] = 'finalize';
            $state['message'] = 'Menyelesaikan paket...';
        }
    }

    private function stepFinalize(array &$state): void {
        $dir = $this->jobDir($state['id']);
        $zip = $this->zipOpen($state);

        $features = array_fill_keys(array_keys(self::FEATURE_OPTIONS), true);
        foreach ($state['disabled'] as $feature) $features[$feature] = false;
        $this->zipAddString($zip, 'config/features.php', "<?php\n\n/** Modul aktif pada paket hasil cloning. Ubah ke true untuk mengaktifkan kembali. */\nreturn " . var_export($features, true) . ";\n");

        $this->zipAddString($zip, 'config/config.php', "<?php\nreturn [\n" .
            "    'db_host' => 'localhost',\n" .
            "    'db_name' => '',\n" .
            "    'db_user' => '',\n" .
            "    'db_pass' => '',\n" .
            "    'base_url' => '',\n" .
            "    'timezone' => " . var_export((string) cfg('timezone', 'Asia/Jakarta'), true) . ",\n" .
            "    'app_key' => '" . bin2hex(random_bytes(32)) . "',\n" .
            "    'face_encryption_key' => '" . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=') . "',\n" .
            "    'debug' => false,\n" .
            "    'session_timeout' => 3600,\n" .
            "    'max_login_attempts' => 5,\n" .
            "    'lockout_minutes' => 15,\n" .
            "    'max_upload_image' => 15 * 1024 * 1024,\n" .
            "    'max_upload_doc' => 15 * 1024 * 1024,\n" .
            "    'max_upload_music' => 12 * 1024 * 1024,\n" .
            "    'max_upload_video' => 25 * 1024 * 1024,\n" .
            "];\n");

        $this->zipAddFile($zip, $dir . '/desa_db.sql', 'database/desa_db.sql');
        foreach (['storage/cache/', 'storage/backups/', 'uploads/tmp/', 'uploads/cache/', 'uploads/protected/'] as $empty) $this->zipAddString($zip, $empty, '');

        $nama = $state['ident']['village_name'];
        $labels = array_map(static fn($f) => self::FEATURE_OPTIONS[$f][0], $state['disabled']);
        $this->zipAddString($zip, 'MULAI-DISINI.txt',
            "WEBSITE " . mb_strtoupper($nama) . "\n" .
            "==============================================\n" .
            "1. Upload file ZIP ini ke public_html lalu Extract (index.php & .htaccess harus langsung di public_html).\n" .
            "2. Buat database MySQL + user database di cPanel (centang ALL PRIVILEGES).\n" .
            "3. Import database/desa_db.sql melalui phpMyAdmin ke database yang masih kosong.\n" .
            "4. Edit config/config.php: isi db_name, db_user, db_pass (base_url boleh dikosongkan).\n" .
            "5. Pastikan folder uploads/ dan storage/ dapat ditulis (permission 755 atau 775).\n" .
            "6. Login melalui /admin/login\n" .
            "   Username: " . $state['admin']['username'] . "\n" .
            "   Password: password yang diisi saat cloning; wajib diganti pada login pertama.\n" .
            "7. Unggah logo, favicon, foto kepala desa, dan ikon aplikasi melalui menu Pengaturan.\n\n" .
            "YANG IKUT TERSALIN\n- Seluruh kode program, struktur database terbaru, tema, warna, font, tata letak,\n  background/video hero, menu navigasi, efek event, jenis layanan surat, peran & hak akses.\n\n" .
            "YANG TIDAK TERSALIN\n- Berita, pengumuman, agenda, dokumen, galeri, foto & data perangkat desa, data penduduk,\n  permohonan, pengaduan, pesan, log, akun admin lama, token WhatsApp/push dari desa sumber.\n" .
            ($state['sample'] ? "- Sebagai gantinya disediakan contoh teks (1 berita, 1 pengumuman, perangkat desa tanpa foto, profil desa) yang bisa diedit.\n" : '') . "\n" .
            "FITUR YANG DINONAKTIFKAN\n" . ($labels ? '- ' . implode("\n- ", $labels) : 'Tidak ada; semua fitur aktif.') . "\n" .
            "Fitur dapat diaktifkan kembali melalui config/features.php (ubah nilainya menjadi true).\n"
        );
        $this->zipCommit($zip, $state);
        $this->zipWriteCentralDirectory($state);

        $state['phase'] = 'done';
        $state['message'] = 'Paket cloning siap diunduh.';
        log_activity('clone', 'sistem', 'Generate cloning website untuk ' . $nama . '; fitur nonaktif: ' . ($state['disabled'] ? implode(', ', $state['disabled']) : 'tidak ada'));
    }

    /* ===== Penulis ZIP tambah-di-akhir: setiap tahap hanya menulis file baru, tidak menulis ulang arsip ===== */
    private function zipOpen(array $state): array {
        $dir = $this->jobDir($state['id']);
        $fh = fopen($dir . '/package.zip', 'c+b');
        $cd = fopen($dir . '/central.bin', 'c+b');
        if (!$fh || !$cd) throw new RuntimeException('Berkas ZIP sementara tidak dapat ditulis (cek izin folder storage).');
        // Pulihkan posisi terakhir yang sukses bila tahap sebelumnya terputus di tengah jalan.
        ftruncate($fh, (int) $state['zip_size']);
        ftruncate($cd, (int) $state['cd_size']);
        fseek($fh, 0, SEEK_END);
        fseek($cd, 0, SEEK_END);
        return ['fh' => $fh, 'cd' => $cd, 'count' => (int) $state['zip_count']];
    }

    private function zipCommit(array &$zip, array &$state): void {
        fflush($zip['fh']);
        fflush($zip['cd']);
        $state['zip_size'] = ftell($zip['fh']);
        $state['cd_size'] = ftell($zip['cd']);
        $state['zip_count'] = $zip['count'];
        fclose($zip['fh']);
        fclose($zip['cd']);
    }

    private function zipAddString(array &$zip, string $name, string $data): void {
        $isDir = str_ends_with($name, '/');
        $crc = $isDir ? 0 : crc32($data);
        $method = 0;
        $payload = $data;
        if (!$isDir && strlen($data) > 64 && !in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), self::STORE_EXT, true)) {
            $deflated = gzdeflate($data, 6);
            if ($deflated !== false && strlen($deflated) < strlen($data)) { $payload = $deflated; $method = 8; }
        }
        $offset = $this->zipLocalHeader($zip, $name, $method, $crc, strlen($payload), strlen($data));
        if ($payload !== '' && fwrite($zip['fh'], $payload) !== strlen($payload)) throw new RuntimeException('Disk hosting penuh saat menulis ' . $name);
        $this->zipCentralEntry($zip, $name, $method, $crc, strlen($payload), strlen($data), $offset, $isDir);
    }

    private function zipAddFile(array &$zip, string $path, string $name): void {
        $size = (int) filesize($path);
        if ($size <= 8 * 1024 * 1024) {
            $data = file_get_contents($path);
            if ($data === false) throw new RuntimeException('File tidak dapat dibaca: ' . $name);
            $this->zipAddString($zip, $name, $data);
            return;
        }
        // File besar (mis. video hero) disalin langsung tanpa kompresi agar cepat dan hemat memori.
        $crc = (int) hexdec(hash_file('crc32b', $path));
        $offset = $this->zipLocalHeader($zip, $name, 0, $crc, $size, $size);
        $in = fopen($path, 'rb');
        if (!$in) throw new RuntimeException('File tidak dapat dibaca: ' . $name);
        $copied = stream_copy_to_stream($in, $zip['fh']);
        fclose($in);
        if ($copied !== $size) throw new RuntimeException('Disk hosting penuh saat menyalin ' . $name);
        $this->zipCentralEntry($zip, $name, 0, $crc, $size, $size, $offset, false);
    }

    private function zipLocalHeader(array &$zip, string $name, int $method, int $crc, int $csize, int $usize): int {
        if ($usize > 0xFFFFFFFF - 1024 || ftell($zip['fh']) > 0xFFFFFFFF - $csize - 1024) throw new RuntimeException('Ukuran paket melebihi 4 GB.');
        [$time, $date] = $this->dosTime();
        $offset = ftell($zip['fh']);
        fwrite($zip['fh'], pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, $method, $time, $date, $crc, $csize, $usize, strlen($name), 0) . $name);
        return $offset;
    }

    private function zipCentralEntry(array &$zip, string $name, int $method, int $crc, int $csize, int $usize, int $offset, bool $isDir): void {
        [$time, $date] = $this->dosTime();
        $attr = $isDir ? (0x10 | (040755 << 16)) : (0100644 << 16);
        fwrite($zip['cd'], pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 0x0314, 20, 0x0800, $method, $time, $date, $crc, $csize, $usize, strlen($name), 0, 0, 0, 0, $attr, $offset) . $name);
        $zip['count']++;
    }

    private function zipWriteCentralDirectory(array $state): void {
        $dir = $this->jobDir($state['id']);
        $fh = fopen($dir . '/package.zip', 'c+b');
        $cd = fopen($dir . '/central.bin', 'rb');
        if (!$fh || !$cd) throw new RuntimeException('Berkas ZIP sementara tidak dapat dibuka.');
        ftruncate($fh, (int) $state['zip_size']);
        fseek($fh, 0, SEEK_END);
        $cdOffset = ftell($fh);
        $cdSize = (int) stream_copy_to_stream($cd, $fh);
        fclose($cd);
        if ($cdSize !== (int) $state['cd_size']) throw new RuntimeException('Gagal menulis daftar isi ZIP.');
        $count = (int) $state['zip_count'];
        if ($count > 0xFFFF) throw new RuntimeException('Jumlah file melebihi batas ZIP (65.535).');
        fwrite($fh, pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $cdSize, $cdOffset, 0));
        fclose($fh);
    }

    private function dosTime(): array {
        $t = getdate();
        return [
            ($t['hours'] << 11) | ($t['minutes'] << 5) | intdiv($t['seconds'], 2),
            (max(0, $t['year'] - 1980) << 9) | ($t['mon'] << 5) | $t['mday'],
        ];
    }

    /* ================= Utilitas pekerjaan ================= */
    private function progress(array $s): int {
        if ($s['phase'] === 'done') return 100;
        if ($s['phase'] === 'finalize') return 97;
        if ($s['phase'] === 'sql') return 1 + (int) floor(14 * $s['table_index'] / max(1, count($s['tables'])));
        return 15 + (int) floor(81 * $s['bytes_done'] / max(1, $s['bytes_total']));
    }

    private function jobDir(string $id): string {
        return BASE_PATH . '/storage/clone_jobs/' . $id;
    }

    private function saveState(array $state): void {
        file_put_contents($this->jobDir($state['id']) . '/state.json', json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function loadState(string $id, bool $asJson = true): array {
        $file = preg_match('/^[a-f0-9]{24}$/', $id) ? $this->jobDir($id) . '/state.json' : '';
        $state = ($file !== '' && is_file($file)) ? json_decode((string) file_get_contents($file), true) : null;
        if (!is_array($state) || (int) ($state['owner'] ?? -1) !== (int) (Auth::user()['id'] ?? 0)) {
            if ($asJson) $this->fail('Proses cloning tidak ditemukan atau sudah kedaluwarsa. Silakan mulai ulang.');
            flash('danger', 'Paket cloning tidak ditemukan atau sudah kedaluwarsa.');
            redirect('admin/cloning');
        }
        return $state;
    }

    private function cleanupOldJobs(): void {
        $root = BASE_PATH . '/storage/clone_jobs';
        if (!is_dir($root)) {
            @mkdir($root, 0775, true);
            @file_put_contents($root . '/.htaccess', "Require all denied\nDeny from all\n");
            @file_put_contents($root . '/index.html', '');
            return;
        }
        foreach (glob($root . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (filemtime($dir) < time() - self::JOB_TTL) $this->removeDir($dir);
        }
    }

    private function removeDir(string $dir): void {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $name) {
            $f = $dir . '/' . $name;
            is_dir($f) ? $this->removeDir($f) : @unlink($f);
        }
        @rmdir($dir);
    }

    private function guardPost(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') $this->fail('Metode tidak diizinkan.', 405);
        $t = (string) ($_POST['csrf_token'] ?? '');
        if ($t === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $t)) $this->fail('Sesi kedaluwarsa. Muat ulang halaman lalu coba lagi.', 419);
        session_write_close();
    }

    private function fail(string $msg, int $code = 422): void {
        http_response_code($code);
        $this->json(['ok' => false, 'message' => $msg]);
    }

    private function validColor(string $value, string $default): string {
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $default;
    }
}
