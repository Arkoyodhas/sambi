<?php

class HomeController extends Controller {

    public function index(): void {
        if (feature_enabled('gallery')) AdminSchema::ensureGalleryBanner();
        $featureKey = hash('sha256', json_encode([
            'cache_version' => Cache::version('public'),
            'population' => feature_enabled('population'),
            'profile' => feature_enabled('village_profile'),
            'news' => feature_enabled('news'),
            'services' => feature_enabled('services'),
            'gallery' => feature_enabled('gallery'),
            'economy' => feature_enabled('economy'),
            'public_data' => feature_enabled('public_data'),
            'sections' => setting('home_sections'),
            'banner' => 'v1',
        ]));

        $cached = Cache::remember('home:' . $featureKey, 180, static function (): array {
            $stats = [
                'penduduk' => 0, 'kk' => 0, 'laki' => 0, 'perempuan' => 0,
                'dusun' => 0, 'rw' => 0, 'rt' => 0, 'perangkat' => 0,
                'berita' => 0, 'layanan' => 0,
            ];

            if (feature_enabled('population')) {
                $resident = db_row("SELECT
                    COUNT(*) AS penduduk,
                    SUM(jenis_kelamin='L') AS laki,
                    SUM(jenis_kelamin='P') AS perempuan
                    FROM residents
                    WHERE deleted_at IS NULL AND status_penduduk='hidup'") ?: [];
                $regions = db_row("SELECT
                    SUM(type='dusun') AS dusun,
                    SUM(type='rw') AS rw,
                    SUM(type='rt') AS rt
                    FROM regions") ?: [];
                $stats = array_replace($stats, [
                    'penduduk' => (int) ($resident['penduduk'] ?? 0),
                    'kk' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
                    'laki' => (int) ($resident['laki'] ?? 0),
                    'perempuan' => (int) ($resident['perempuan'] ?? 0),
                    'dusun' => (int) ($regions['dusun'] ?? 0),
                    'rw' => (int) ($regions['rw'] ?? 0),
                    'rt' => (int) ($regions['rt'] ?? 0),
                ]);
            }
            if (feature_enabled('village_profile')) {
                $stats['perangkat'] = (int) db_val("SELECT COUNT(*) FROM village_officials WHERE deleted_at IS NULL AND is_active=1");
            }
            if (feature_enabled('news')) {
                $stats['berita'] = (int) db_val("SELECT COUNT(*) FROM news
                    WHERE deleted_at IS NULL AND status='published' AND published_at <= NOW()");
            }
            if (feature_enabled('services')) {
                $stats['layanan'] = (int) db_val("SELECT COUNT(*) FROM services WHERE deleted_at IS NULL AND is_active=1");
            }

            return [
                'stats' => $stats,
                'featured' => feature_enabled('news') ? db_all("SELECT n.*, c.name AS category FROM news n
                    LEFT JOIN news_categories c ON c.id=n.category_id
                    WHERE n.deleted_at IS NULL AND n.status='published' AND n.published_at <= NOW() AND n.is_featured=1
                    ORDER BY n.published_at DESC LIMIT 3") : [],
                'latestNews' => feature_enabled('news') ? db_all("SELECT n.*, c.name AS category FROM news n
                    LEFT JOIN news_categories c ON c.id=n.category_id
                    WHERE n.deleted_at IS NULL AND n.status='published' AND n.published_at <= NOW()
                    ORDER BY n.published_at DESC LIMIT 6") : [],
                'announcements' => feature_enabled('news') ? db_all("SELECT * FROM announcements
                    WHERE deleted_at IS NULL AND is_active=1
                    AND (start_date IS NULL OR start_date <= CURDATE())
                    AND (end_date IS NULL OR end_date >= CURDATE())
                    ORDER BY is_important DESC, start_date DESC LIMIT 4") : [],
                'agendas' => feature_enabled('news') ? db_all("SELECT * FROM agendas
                    WHERE deleted_at IS NULL AND status != 'dibatalkan'
                    AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 4") : [],
                'galleries' => feature_enabled('gallery') ? db_all("SELECT * FROM galleries
                    WHERE deleted_at IS NULL AND is_active=1 AND type='foto' AND file_path IS NOT NULL
                    ORDER BY id DESC LIMIT 8") : [],
                'banners' => feature_enabled('gallery') ? self::banners() : [],
                'officials' => feature_enabled('village_profile') ? db_all("SELECT * FROM village_officials
                    WHERE deleted_at IS NULL AND is_active=1 AND show_on_home=1
                    ORDER BY sort_order ASC LIMIT 4") : [],
                'potentials' => feature_enabled('economy') ? db_all("SELECT * FROM village_potentials
                    WHERE deleted_at IS NULL AND is_active=1 ORDER BY id DESC LIMIT 8") : [],
                'umkm' => feature_enabled('economy') ? db_all("SELECT * FROM umkm_products
                    WHERE deleted_at IS NULL AND is_active=1 ORDER BY id DESC LIMIT 8") : [],
                'services' => feature_enabled('services') ? db_all("SELECT * FROM services
                    WHERE deleted_at IS NULL AND is_active=1 ORDER BY id ASC LIMIT 8") : [],
                'transparansiIdm' => feature_enabled('public_data')
                    ? db_row("SELECT tahun, iks, ike, ikl, idm FROM idm_data ORDER BY tahun DESC LIMIT 1") : null,
                'transparansiBansos' => feature_enabled('public_data')
                    ? db_row("SELECT COUNT(DISTINCT p.id) programs, COUNT(r.id) recipients
                        FROM bansos_programs p LEFT JOIN bansos_recipients r ON r.program_id=p.id
                        WHERE p.tahun=YEAR(CURDATE()) AND p.is_active=1") : null,
                'transparansiStunting' => feature_enabled('public_data')
                    ? db_row("SELECT COUNT(*) total,
                        SUM(status IN ('pendek','sangat_pendek','beresiko')) stunting
                        FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=YEAR(CURDATE())") : null,
            ];
        });

        $sectionFeature = [
            'stats' => 'population', 'transparansi' => 'public_data',
            'layanan' => 'services', 'berita' => 'news',
            'pengumuman' => 'news', 'agenda' => 'news',
            'perangkat' => 'village_profile', 'galeri' => 'gallery',
            'potensi' => 'economy', 'umkm' => 'economy',
        ];
        $sections = array_values(array_filter(
            array_map('trim', explode(',', setting('home_sections',
                'stats,transparansi,sambutan,layanan,berita,pengumuman,agenda,perangkat,galeri,potensi,umkm,social,peta'))),
            static function (string $section) use ($sectionFeature): bool {
                $feature = $sectionFeature[$section] ?? null;
                return $section !== '' && ($feature === null || feature_enabled($feature));
            }
        ));

        $socialEmbeds = Cache::remember('home-social:v' . Cache::version('public'), 300, static fn(): array => social_embed_items());

        if (setting('home_banner_enabled', '1') !== '1' || empty($cached['banners'])) {
            $sections = array_values(array_diff($sections, ['banner']));
        } elseif (!in_array('banner', $sections, true)) {
            $statsIndex = array_search('stats', $sections, true);
            array_splice($sections, $statsIndex === false ? 0 : $statsIndex + 1, 0, ['banner']);
        }

        $data = array_merge($cached, [
            'pageTitle' => setting('meta_title', setting('village_name')),
            'socialEmbeds' => $socialEmbeds,
            'faqs' => faq_items(),
            'sections' => $sections,
            'isHome' => true,
            'needsLightbox' => !empty($cached['galleries']) || !empty($cached['banners']),
        ]);
        $this->view('home', $data);
    }

    private static function banners(): array {
        $base = "SELECT g.id, g.title, g.file_path, g.category, g.year, g.banner_title, g.banner_desc, g.protected, g.watermark,
                a.name AS album, a.description AS album_desc, a.slug AS album_slug
            FROM galleries g LEFT JOIN gallery_albums a ON a.id = g.album_id
            WHERE g.deleted_at IS NULL AND g.is_active=1 AND g.type='foto' AND g.file_path IS NOT NULL AND g.file_path != ''";
        try {
            $rows = db_all($base . " AND g.is_banner=1 ORDER BY g.banner_order ASC, g.id DESC LIMIT 8");
        } catch (Throwable $e) {
            return [];
        }
        return array_values(array_filter($rows, static fn(array $r): bool => is_file(BASE_PATH . '/uploads/' . ltrim((string) $r['file_path'], '/'))));
    }
}
