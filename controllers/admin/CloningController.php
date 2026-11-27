<?php

/**
 * FITUR KHUSUS SUPER ADMIN: Cloning Website.
 * Menghasilkan paket ZIP tanpa data operasional situs sumber.
 */
class CloningController extends Controller {

    public array $actions = ['index', 'generate'];

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

    public function __construct() {
        Auth::requireSuperAdmin();
    }

    public function index(): void {
        $this->adminView('cloning', [
            'pageTitle' => 'Cloning Website',
            'featureOptions' => self::FEATURE_OPTIONS,
        ]);
    }

    public function generate(): void {
        verify_csrf();
        @set_time_limit(180);

        $f = static fn(string $key, string $default = ''): string => trim((string) ($_POST[$key] ?? $default));
        $nama = $f('village_name');
        $adminUser = $f('admin_username', 'admin');
        $adminPass = (string) ($_POST['admin_password'] ?? '');
        $adminEmail = $f('admin_email', 'admin@desa.id');

        if ($nama === '' || mb_strlen($nama) > 150) {
            flash('danger', 'Nama desa wajib diisi dan maksimal 150 karakter.');
            redirect('admin/cloning');
        }
        if (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $adminUser)) {
            flash('danger', 'Username admin harus 3-30 karakter dan hanya boleh berisi huruf, angka, titik, atau garis bawah.');
            redirect('admin/cloning');
        }
        if (strlen($adminPass) < 8) {
            flash('danger', 'Password admin minimal 8 karakter.');
            redirect('admin/cloning');
        }
        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            flash('danger', 'Email admin tidak valid.');
            redirect('admin/cloning');
        }

        $requestedDisabled = $_POST['disabled_features'] ?? [];
        if (!is_array($requestedDisabled)) $requestedDisabled = [];
        $disabledFeatures = array_values(array_intersect(
            array_keys(self::FEATURE_OPTIONS),
            array_map('strval', $requestedDisabled)
        ));

        $seedFile = BASE_PATH . '/database/desa_db.sql';
        if (!is_file($seedFile) || filesize($seedFile) < 1000) {
            flash('danger', 'File installer database/desa_db.sql tidak ditemukan atau tidak valid. Paket cloning dibatalkan.');
            redirect('admin/cloning');
        }
        $sql = file_get_contents($seedFile);
        if ($sql === false) {
            flash('danger', 'File installer database tidak dapat dibaca.');
            redirect('admin/cloning');
        }

        $ident = [
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
            'email' => $f('email'),
            'kepala_desa_nama' => $f('kepala_desa_nama'),
            'primary_color' => $this->validColor($f('primary_color'), '#0f6b4e'),
            'secondary_color' => $this->validColor($f('secondary_color'), '#0b3d2e'),
            'accent_color' => $this->validColor($f('accent_color'), '#e8b44c'),
            'map_lat' => $f('map_lat'),
            'map_lng' => $f('map_lng'),
            'meta_title' => $nama . ' - Website Resmi',
            'meta_description' => 'Website resmi ' . $nama . ($f('kabupaten') !== '' ? ', ' . $f('kabupaten') : '') . '. Informasi, layanan, dan transparansi desa.',
            'facebook' => '',
            'instagram' => '',
            'youtube' => '',
            'twitter' => '',
            'tiktok' => '',
            'logo' => '',
            'favicon' => '',
            'hero_image' => '',
            'og_image' => '',
            'kepala_desa_foto' => '',
            'hero_video' => '',
            'hero_video_enabled' => '0',
            'fonnte_token' => '',
            'wa_admin_number' => '',
            'fonnte_enabled' => '0',
            'vapid_public' => '',
            'vapid_private' => '',
            'asset_version' => '1',
        ];

        $featureSettings = [
            'music_event' => ['music_enabled', 'bgfx_enabled'],
            'entertainment' => ['entertainment_enabled'],
            'live_chat' => ['livechat_enabled', 'livechat_wa_notify'],
            'push_pwa' => ['webnotif_enabled'],
            'feedback' => ['feedback_enabled'],
            'face_auth' => ['face_login_enabled'],
        ];
        foreach ($featureSettings as $feature => $keys) {
            if (!in_array($feature, $disabledFeatures, true)) continue;
            foreach ($keys as $key) $ident[$key] = '0';
        }

        $sql .= "\n\n-- ============================================================\n";
        $sql .= "-- IDENTITAS DAN KONFIGURASI WEBSITE HASIL CLONING\n";
        $sql .= "-- Dibuat: " . date('d/m/Y H:i') . "\n";
        $sql .= "-- ============================================================\n";
        foreach ($ident as $key => $value) {
            $sql .= "INSERT INTO site_settings (setting_key, setting_value) VALUES (" .
                $this->sqlString($key) . ", " . $this->sqlString($value) . ") " .
                "ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);\n";
        }

        $hash = password_hash($adminPass, PASSWORD_BCRYPT);
        $sql .= "\n-- Akun Super Admin baru\n";
        $sql .= "DELETE FROM users;\n";
        $sql .= "INSERT INTO users (id, name, username, email, password, role_id, is_active, must_change_password, failed_attempts, created_at, updated_at) VALUES (" .
            "1, " . $this->sqlString('Administrator ' . $nama) . ", " .
            $this->sqlString($adminUser) . ", " . $this->sqlString($adminEmail) . ", " .
            $this->sqlString($hash) . ", 1, 1, 1, 0, NOW(), NOW());\n";

        $sql .= "\n-- Pastikan data operasional kosong\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
        foreach ([
            'activity_logs', 'visit_logs', 'ip_locations', 'push_subscriptions',
            'push_notifications', 'import_logs', 'service_requests', 'complaints',
            'contact_messages', 'chat_messages', 'chat_rooms', 'feedback_responses',
            'screenshot_attempts', 'admin_face_profiles', 'residents', 'families', 'family_members',
        ] as $table) {
            $sql .= "DELETE FROM `" . $table . "`;\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

        if (!class_exists('ZipArchive')) {
            flash('danger', 'Ekstensi PHP ZIP belum aktif. Aktifkan ekstensi zip/ZipArchive di cPanel sebelum membuat paket cloning.');
            redirect('admin/cloning');
        }

        $tmpBase = tempnam(sys_get_temp_dir(), 'desa-clone-');
        if ($tmpBase === false) {
            flash('danger', 'Folder sementara server tidak tersedia.');
            redirect('admin/cloning');
        }
        @unlink($tmpBase);
        $tmp = $tmpBase . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            flash('danger', 'Gagal membuat berkas ZIP di server.');
            redirect('admin/cloning');
        }

        $exclude = [
            'controllers/admin/CloningController.php',
            'views/admin/cloning.php',
            'router.php',
            'database/dawungsr_dbdesa.sql',
            'database/desa_db.sql',
            'config/config.php',
            'config/features.php',
        ];

        $base = realpath(BASE_PATH);
        if ($base === false) {
            $zip->close();
            @unlink($tmp);
            flash('danger', 'Direktori aplikasi tidak dapat dibaca.');
            redirect('admin/cloning');
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) continue;
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));

            if (str_starts_with($rel, '.git') || str_starts_with($rel, 'uploads/')) continue;
            if (in_array($rel, $exclude, true)) continue;

            if ($rel === 'core/App.php') {
                $app = file_get_contents($file->getPathname());
                if ($app === false) continue;
                $app = preg_replace("/^.*'cloning'\\s*=>.*\\R/m", '', $app);
                $zip->addFromString($rel, (string) $app);
                continue;
            }
            if ($rel === 'views/layouts/admin_header.php') {
                $layout = file_get_contents($file->getPathname());
                if ($layout === false) continue;
                $layout = preg_replace(
                    "/\\s*<\\?php if \\(Auth::isSuperAdmin\\(\\) && is_file\\(BASE_PATH \\. '\\/controllers\\/admin\\/CloningController\\.php'\\)\\): \\?>.*?<\\?php endif; \\?>/s",
                    '',
                    $layout
                );
                $zip->addFromString($rel, (string) $layout);
                continue;
            }
            $zip->addFile($file->getPathname(), $rel);
        }

        $features = array_fill_keys(array_keys(self::FEATURE_OPTIONS), true);
        foreach ($disabledFeatures as $feature) $features[$feature] = false;
        $featureConfig = "<?php\n\n/** Modul aktif pada paket hasil cloning. */\nreturn " .
            var_export($features, true) . ";\n";
        $zip->addFromString('config/features.php', $featureConfig);

        $newConfig = "<?php\nreturn [\n" .
            "    'db_host' => 'localhost',\n" .
            "    'db_name' => '',\n" .
            "    'db_user' => '',\n" .
            "    'db_pass' => '',\n" .
            "    'base_url' => '',\n" .
            "    'timezone' => 'Asia/Jakarta',\n" .
            "    'app_key' => '" . bin2hex(random_bytes(32)) . "',\n" .
            "    'face_encryption_key' => '" . bin2hex(random_bytes(32)) . "',\n" .
            "    'debug' => false,\n" .
            "    'session_timeout' => 3600,\n" .
            "    'max_login_attempts' => 5,\n" .
            "    'lockout_minutes' => 15,\n" .
            "    'max_upload_image' => 15 * 1024 * 1024,\n" .
            "    'max_upload_doc' => 15 * 1024 * 1024,\n" .
            "    'max_upload_music' => 12 * 1024 * 1024,\n" .
            "    'max_upload_video' => 25 * 1024 * 1024,\n" .
            "];\n";
        $zip->addFromString('config/config.php', $newConfig);
        $zip->addFromString('database/desa_db.sql', $sql);
        $zip->addFromString('uploads/.htaccess', (string) @file_get_contents($base . '/uploads/.htaccess'));

        $disabledLabels = [];
        foreach ($disabledFeatures as $feature) $disabledLabels[] = self::FEATURE_OPTIONS[$feature][0];
        $zip->addFromString(
            'MULAI-DISINI.txt',
            "WEBSITE " . strtoupper($nama) . "\n" .
            "==============================================\n" .
            "1. Buat database MySQL dan user database di cPanel.\n" .
            "2. Import database/desa_db.sql melalui phpMyAdmin.\n" .
            "3. Edit config/config.php dan isi db_name, db_user, db_pass, serta base_url.\n" .
            "4. Upload semua file ke folder website.\n" .
            "5. Login melalui /admin/login\n" .
            "   Username: " . $adminUser . "\n" .
            "   Password: password yang diisi saat cloning; wajib diganti pada login pertama.\n\n" .
            "FITUR YANG DINONAKTIFKAN\n" .
            ($disabledLabels ? "- " . implode("\n- ", $disabledLabels) : "Tidak ada; semua fitur aktif.") . "\n\n" .
            "Fitur dinonaktifkan melalui config/features.php dan dapat diaktifkan kembali dengan mengubah nilainya menjadi true.\n"
        );

        if (!$zip->close() || !is_file($tmp) || filesize($tmp) < 1000) {
            @unlink($tmp);
            flash('danger', 'Paket ZIP tidak berhasil diselesaikan.');
            redirect('admin/cloning');
        }

        log_activity(
            'clone',
            'sistem',
            'Generate cloning website untuk ' . $nama . '; fitur nonaktif: ' .
            ($disabledFeatures ? implode(', ', $disabledFeatures) : 'tidak ada')
        );

        $filename = 'website-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)) . '-' . date('Ymd') . '.zip';
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . trim($filename, '-') . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: no-store, private');
        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    private function sqlString(string $value): string {
        return "'" . str_replace(
            ["\\", "'", "\0", "\n", "\r", "\x1a"],
            ["\\\\", "\\'", "\\0", "\\n", "\\r", "\\Z"],
            $value
        ) . "'";
    }

    private function validColor(string $value, string $default): string {
        return preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : $default;
    }
}
