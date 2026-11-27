<?php

/**
 * Katalog dan utilitas Role-Based Access Control.
 *
 * Role menentukan izin dasar. Tabel user_permissions dapat menambah (allow)
 * atau menolak (deny) izin tertentu untuk satu pengguna.
 */
final class AccessControl
{
    private static bool $schemaReady = false;

    public static function catalog(): array
    {
        return [
            'Pemerintahan & Data Desa' => [
                'kelola_profil' => [
                    'name' => 'Kelola Profil Desa',
                    'description' => 'Profil desa, perangkat, SOTK, SDGs, UMKM, dan potensi desa.',
                ],
                'kelola_penduduk' => [
                    'name' => 'Kelola Penduduk',
                    'description' => 'Data penduduk, keluarga, dan wilayah.',
                ],
                'import_penduduk' => [
                    'name' => 'Import Penduduk',
                    'description' => 'Mengimpor data penduduk dari file yang didukung.',
                ],
                'export_penduduk' => [
                    'name' => 'Export Penduduk',
                    'description' => 'Mengunduh atau mengekspor data penduduk.',
                ],
                'kelola_konten' => [
                    'name' => 'Kelola Data Publik',
                    'description' => 'IDM, bansos, stunting, pengaturan PWA, dan generator QR.',
                ],
            ],
            'Konten & Pelayanan' => [
                'kelola_berita' => [
                    'name' => 'Kelola Berita',
                    'description' => 'Berita, kategori, komentar, pengumuman, dan agenda.',
                ],
                'kelola_galeri' => [
                    'name' => 'Kelola Galeri',
                    'description' => 'Album serta koleksi foto galeri.',
                ],
                'kelola_layanan' => [
                    'name' => 'Kelola Layanan',
                    'description' => 'Jenis layanan dan permohonan layanan masyarakat.',
                ],
                'kelola_pengaduan' => [
                    'name' => 'Kelola Pengaduan',
                    'description' => 'Pengaduan warga dan pesan kontak.',
                ],
                'kelola_transparansi' => [
                    'name' => 'Kelola Transparansi',
                    'description' => 'APBDes, realisasi, dan pembangunan desa.',
                ],
                'kelola_dokumen' => [
                    'name' => 'Kelola Dokumen',
                    'description' => 'Dokumen publik dan lampiran yang dapat diunduh.',
                ],
            ],
            'Website & Pengalaman' => [
                'kelola_tampilan' => [
                    'name' => 'Kelola Tampilan Website',
                    'description' => 'Pengaturan, navigasi, halaman statis, push, Zona Hiburan, dan Live Chat.',
                ],
                'kelola_musik' => [
                    'name' => 'Kelola Musik',
                    'description' => 'Musik latar dan jadwal pemutaran website.',
                ],
                'kelola_event' => [
                    'name' => 'Kelola Event Effect',
                    'description' => 'Efek visual, tema, dan pengalaman event website.',
                ],
            ],
            'Keamanan & Sistem' => [
                'lihat_audit' => [
                    'name' => 'Lihat Audit & Monitoring',
                    'description' => 'Audit log, kunjungan, upaya screenshot, dan feedback.',
                ],
                'kelola_user' => [
                    'name' => 'Kelola User Admin',
                    'description' => 'Izin sistem lama. Halaman user dan role tetap dibatasi khusus Super Admin.',
                ],
            ],
            'Interaksi Warga' => [
                'balas_chat' => [
                    'name' => 'Balas Live Chat',
                    'description' => 'Membuka, membalas, dan menutup percakapan Live Chat warga.',
                    'inherit' => 'kelola_tampilan',
                ],
                'lihat_feedback' => [
                    'name' => 'Lihat Feedback / Survei',
                    'description' => 'Melihat, mencetak, dan mengelola pertanyaan survei warga.',
                    'inherit' => 'lihat_audit',
                ],
                'lihat_kunjungan' => [
                    'name' => 'Lihat Statistik Kunjungan',
                    'description' => 'Melihat data kunjungan website dan upaya screenshot.',
                    'inherit' => 'lihat_audit',
                ],
                'kelola_hiburan' => [
                    'name' => 'Kelola Zona Hiburan',
                    'description' => 'Mengatur game, hadiah, dan data Zona Hiburan.',
                    'inherit' => 'kelola_tampilan',
                ],
                'kelola_kontak_darurat' => [
                    'name' => 'Kelola Kontak Darurat',
                    'description' => 'Mengatur tombol Siaga di beranda: daftar kontak darurat, ikon, dan warna tombol.',
                    'inherit' => 'kelola_tampilan',
                ],
            ],
        ];
    }

    public static function slugs(): array
    {
        $slugs = [];
        foreach (self::catalog() as $items) {
            $slugs = array_merge($slugs, array_keys($items));
        }
        return $slugs;
    }

    public static function metadata(string $slug): array
    {
        foreach (self::catalog() as $group => $items) {
            if (isset($items[$slug])) {
                return $items[$slug] + ['group' => $group, 'slug' => $slug];
            }
        }
        return [
            'group' => 'Lainnya',
            'slug' => $slug,
            'name' => ucwords(str_replace('_', ' ', $slug)),
            'description' => '',
        ];
    }

    /**
     * Memastikan tabel override tersedia dan katalog izin utama lengkap.
     * Dipanggil hanya pada halaman manajemen akses, bukan pada setiap request.
     */
    public static function ensureSchema(): void
    {
        if (self::$schemaReady) {
            return;
        }

        db_exec(
            "CREATE TABLE IF NOT EXISTS `user_permissions` (
                `user_id` int(10) UNSIGNED NOT NULL,
                `permission_id` int(10) UNSIGNED NOT NULL,
                `effect` enum('allow','deny') NOT NULL DEFAULT 'allow',
                `created_at` datetime NOT NULL DEFAULT current_timestamp(),
                `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`user_id`,`permission_id`),
                KEY `idx_user_permissions_permission` (`permission_id`),
                KEY `idx_user_permissions_effect` (`effect`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        foreach (self::catalog() as $items) {
            foreach ($items as $slug => $meta) {
                $isNew = !db_val("SELECT id FROM permissions WHERE slug = ? LIMIT 1", [$slug]);
                db_exec(
                    "INSERT INTO permissions (name, slug, created_at, updated_at)
                     VALUES (?, ?, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE name = VALUES(name)",
                    [$meta['name'], $slug]
                );
                if ($isNew && !empty($meta['inherit'])) {
                    // Izin baru: salin pemberian dari izin lama agar akses yang sudah ada tidak hilang
                    db_exec(
                        "INSERT IGNORE INTO role_permissions (role_id, permission_id)
                         SELECT rp.role_id, (SELECT id FROM permissions WHERE slug = ?) FROM role_permissions rp
                         JOIN permissions p ON p.id = rp.permission_id WHERE p.slug = ?",
                        [$slug, $meta['inherit']]
                    );
                }
            }
        }

        self::$schemaReady = true;
    }

    public static function permissions(): array
    {
        self::ensureSchema();
        $order = array_flip(self::slugs());
        $rows = db_all("SELECT id, name, slug FROM permissions ORDER BY name ASC");
        usort($rows, static function (array $a, array $b) use ($order): int {
            $ai = $order[$a['slug']] ?? PHP_INT_MAX;
            $bi = $order[$b['slug']] ?? PHP_INT_MAX;
            return $ai <=> $bi ?: strcmp($a['name'], $b['name']);
        });
        return $rows;
    }

    public static function groupedPermissions(array $permissions): array
    {
        $groups = [];
        foreach ($permissions as $permission) {
            $meta = self::metadata((string) $permission['slug']);
            $permission['description'] = $meta['description'];
            $groups[$meta['group']][] = $permission;
        }
        return $groups;
    }

    public static function normalizePermissionIds(array $input, array $permissions): array
    {
        $valid = array_map('intval', array_column($permissions, 'id'));
        $lookup = array_fill_keys($valid, true);
        $result = [];
        foreach ($input as $id) {
            $id = (int) $id;
            if ($id > 0 && isset($lookup[$id])) {
                $result[$id] = $id;
            }
        }
        return array_values($result);
    }

    public static function isSuperAdmin(?array $user = null): bool
    {
        $user ??= Auth::user();
        return ($user['role_slug'] ?? '') === 'super-admin';
    }
}
