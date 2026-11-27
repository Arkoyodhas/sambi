<?php

/**
 * Pemeriksaan skema ringan untuk modul admin yang harus tetap kompatibel
 * dengan instalasi lama. Perubahan hanya menambah tabel/kolom yang hilang.
 */
class AdminSchema {
    private static bool $staticPagesChecked = false;
    private static bool $faqChecked = false;

    public static function ensureFaq(): void {
        if (self::$faqChecked) return;
        self::$faqChecked = true;
        if (Cache::get('schema:faqs') === true) return;
        try {
            db_exec("CREATE TABLE IF NOT EXISTS `faqs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `question` VARCHAR(255) NOT NULL,
                `answer` TEXT NOT NULL,
                `sort_order` INT NOT NULL DEFAULT 99,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` DATETIME NULL,
                PRIMARY KEY (`id`), KEY `idx_faqs_order` (`is_active`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            if ((int) db_val("SELECT COUNT(*) FROM faqs") === 0) {
                $seed = [
                    ['Bagaimana cara mengajukan surat secara online?', 'Buka menu Layanan, pilih jenis surat yang dibutuhkan, isi formulir beserta data pendukung, lalu kirim. Anda akan menerima nomor tiket untuk memantau status pengajuan.'],
                    ['Berapa lama surat selesai diproses?', 'Sebagian besar surat selesai dalam 1 hari kerja setelah data dinyatakan lengkap. Surat tertentu seperti keterangan ahli waris dapat memerlukan waktu lebih lama.'],
                    ['Apakah pengurusan surat dikenakan biaya?', 'Tidak. Seluruh layanan administrasi surat di Kantor Desa tidak dipungut biaya (gratis).'],
                    ['Bagaimana cara mengecek status pengajuan surat?', 'Buka halaman Cek Status Layanan, lalu masukkan nomor tiket yang Anda terima saat mengirim pengajuan.'],
                    ['Bagaimana cara menyampaikan pengaduan atau aspirasi?', 'Gunakan menu Pengaduan untuk mengirim aduan beserta foto pendukung. Pengaduan dapat dilacak dengan nomor tiket dan akan ditindaklanjuti oleh perangkat desa.'],
                    ['Kapan jam pelayanan Kantor Desa?', 'Pelayanan tatap muka dibuka pada hari kerja, Senin sampai Jumat. Jadwal lengkap tercantum di bagian kontak pada bawah halaman.'],
                ];
                foreach ($seed as $i => [$q, $a]) {
                    db_exec("INSERT INTO faqs (question, answer, sort_order, is_active) VALUES (?,?,?,1)", [$q, $a, $i + 1]);
                }
            }
            Cache::put('schema:faqs', true, 86400);
        } catch (Throwable $e) {
            error_log('FAQ schema: ' . $e->getMessage());
        }
    }

    private static bool $emergencyChecked = false;

    public static function ensureGalleryBanner(): void {
        static $checked = false;
        if ($checked) return;
        $checked = true;
        if (Cache::get('schema:gallery_banner') === true) return;
        try {
            $cols = array_column(db_all("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'galleries'"), 'COLUMN_NAME');
            $add = [
                'is_banner' => "ADD COLUMN `is_banner` TINYINT(1) NOT NULL DEFAULT 0",
                'banner_title' => "ADD COLUMN `banner_title` VARCHAR(150) NULL",
                'banner_desc' => "ADD COLUMN `banner_desc` VARCHAR(400) NULL",
                'banner_order' => "ADD COLUMN `banner_order` INT NOT NULL DEFAULT 99",
            ];
            $missing = array_diff_key($add, array_flip($cols));
            if ($missing) db_exec("ALTER TABLE `galleries` " . implode(', ', $missing));
            Cache::put('schema:gallery_banner', true, 86400);
        } catch (Throwable $e) {
            error_log('Skema banner galeri gagal: ' . $e->getMessage());
        }
    }

    public static function ensureEmergency(): void {
        if (self::$emergencyChecked) return;
        self::$emergencyChecked = true;
        if (Cache::get('schema:emergency') === true) return;
        try {
            db_exec("CREATE TABLE IF NOT EXISTS `emergency_contacts` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `category` VARCHAR(30) NOT NULL DEFAULT 'lainnya',
                `phone` VARCHAR(20) NOT NULL,
                `has_whatsapp` TINYINT(1) NOT NULL DEFAULT 0,
                `icon` VARCHAR(40) NOT NULL DEFAULT 'bi-telephone-fill',
                `color` VARCHAR(7) NOT NULL DEFAULT '#DC2626',
                `sort_order` INT NOT NULL DEFAULT 99,
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `deleted_at` DATETIME NULL,
                PRIMARY KEY (`id`), KEY `idx_emergency_order` (`is_active`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            if ((int) db_val("SELECT COUNT(*) FROM emergency_contacts") === 0) {
                $seed = [
                    ['Panggilan Darurat 112', 'darurat', '112', 0, 'bi-telephone-fill', '#DC2626'],
                    ['Ambulans / Gawat Darurat', 'ambulans', '119', 0, 'bi-heart-pulse-fill', '#E11D48'],
                    ['Polisi', 'polisi', '110', 0, 'bi-shield-fill-check', '#1D4ED8'],
                    ['Pemadam Kebakaran', 'damkar', '113', 0, 'bi-fire', '#EA580C'],
                    ['Basarnas (SAR)', 'bpbd', '115', 0, 'bi-life-preserver', '#0891B2'],
                ];
                $wa = normalize_phone_id(setting('whatsapp')) ?: normalize_phone_id(setting('phone'));
                if ($wa !== '') $seed[] = ['Mobil Siaga Desa', 'mobil_siaga', '0' . substr($wa, 2), 1, 'bi-truck-front-fill', '#00B589'];
                foreach ($seed as $i => $r) {
                    db_exec("INSERT INTO emergency_contacts (name, category, phone, has_whatsapp, icon, color, sort_order, is_active) VALUES (?,?,?,?,?,?,?,1)", [...$r, $i + 1]);
                }
            }
            Cache::put('schema:emergency', true, 86400);
        } catch (Throwable $e) {
            error_log('Emergency schema: ' . $e->getMessage());
        }
    }

    public static function ensureStaticPages(): array {
        if (self::$staticPagesChecked) return ['ok' => true, 'changed' => false, 'messages' => []];
        self::$staticPagesChecked = true;

        $changed = false;
        $messages = [];

        try {
            db_exec(
                "CREATE TABLE IF NOT EXISTS `pages` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(200) NOT NULL,
                    `slug` VARCHAR(200) NOT NULL,
                    `content` MEDIUMTEXT NULL,
                    `image` VARCHAR(255) NULL,
                    `attachment` VARCHAR(255) NULL,
                    `meta_title` VARCHAR(200) NULL,
                    `meta_description` VARCHAR(300) NULL,
                    `template` VARCHAR(30) NOT NULL DEFAULT 'default',
                    `sort_order` INT NOT NULL DEFAULT 99,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_by` INT UNSIGNED NULL,
                    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    `deleted_at` DATETIME NULL,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $columns = [];
            foreach (db_all(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pages'"
            ) as $row) {
                $columns[(string) $row['COLUMN_NAME']] = true;
            }

            $required = [
                'title' => "VARCHAR(200) NOT NULL DEFAULT '' AFTER `id`",
                'slug' => "VARCHAR(200) NOT NULL DEFAULT '' AFTER `title`",
                'content' => "MEDIUMTEXT NULL AFTER `slug`",
                'image' => "VARCHAR(255) NULL AFTER `content`",
                'attachment' => "VARCHAR(255) NULL AFTER `image`",
                'meta_title' => "VARCHAR(200) NULL AFTER `attachment`",
                'meta_description' => "VARCHAR(300) NULL AFTER `meta_title`",
                'template' => "VARCHAR(30) NOT NULL DEFAULT 'default' AFTER `meta_description`",
                'sort_order' => "INT NOT NULL DEFAULT 99 AFTER `template`",
                'is_active' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `sort_order`",
                'created_by' => "INT UNSIGNED NULL AFTER `is_active`",
                'created_at' => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER `created_by`",
                'updated_at' => "DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
                'deleted_at' => "DATETIME NULL AFTER `updated_at`",
            ];

            foreach ($required as $name => $definition) {
                if (isset($columns[$name])) continue;
                db_exec("ALTER TABLE `pages` ADD COLUMN `$name` $definition");
                $changed = true;
                $messages[] = "Kolom pages.$name ditambahkan.";
            }

            // Isi slug kosong/duplikat secara deterministik sebelum membuat indeks unik.
            $rows = db_all("SELECT id, title, slug FROM pages ORDER BY id ASC");
            $seen = [];
            foreach ($rows as $row) {
                $base = slugify(trim((string) $row['slug']) !== '' ? (string) $row['slug'] : (string) $row['title']);
                if ($base === '') $base = 'halaman-' . (int) $row['id'];
                $slug = $base;
                $suffix = 1;
                while (isset($seen[$slug])) {
                    $slug = $base . '-' . (++$suffix);
                }
                $seen[$slug] = true;
                if ((string) $row['slug'] !== $slug) {
                    db_update('pages', ['slug' => $slug], 'id = ?', [(int) $row['id']]);
                    $changed = true;
                }
            }

            $hasSlugIndex = (bool) db_val(
                "SELECT 1 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pages'
                   AND COLUMN_NAME = 'slug' AND NON_UNIQUE = 0
                 LIMIT 1"
            );
            if (!$hasSlugIndex) {
                db_exec("ALTER TABLE `pages` ADD UNIQUE KEY `uq_pages_slug` (`slug`)");
                $changed = true;
                $messages[] = 'Indeks unik slug halaman ditambahkan.';
            }

            return ['ok' => true, 'changed' => $changed, 'messages' => $messages];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'changed' => $changed,
                'messages' => ['Skema Halaman Statis belum dapat diperbarui otomatis. Jalankan migrasi database terbaru.'],
            ];
        }
    }
}
