<?php
/** Pencarian pintar beranda: sinonim, koreksi typo, jawaban cepat, dan pencarian lintas konten (tanpa AI eksternal). */
class SmartSearch {

    private const STOP = ['yang', 'di', 'ke', 'dari', 'dan', 'atau', 'cara', 'bagaimana', 'gimana', 'apa', 'apakah', 'saya', 'aku', 'mau', 'ingin',
        'pengen', 'buat', 'membuat', 'bikin', 'untuk', 'utk', 'info', 'informasi', 'tentang', 'mengurus', 'urus', 'ngurus', 'minta', 'meminta',
        'dong', 'ya', 'kah', 'nya', 'itu', 'ini', 'ada', 'adakah', 'mana', 'dimana', 'kapan', 'siapa', 'berapa', 'tolong', 'mohon', 'bisa',
        'desa', 'syarat', 'persyaratan', 'the', 'pak', 'bu', 'jadwal', 'lihat', 'cari', 'mencari', 'daftar', 'data', 'tentang', 'dengan', 'pada'];

    private const SYN = [
        'ktp' => ['ktp', 'e-ktp', 'kartu tanda penduduk'], 'kk' => ['kk', 'kartu keluarga'], 'sktm' => ['tidak mampu', 'sktm'],
        'nikah' => ['nikah', 'menikah', 'pernikahan'], 'kawin' => ['nikah', 'menikah'], 'lahir' => ['kelahiran', 'lahir'], 'akta' => ['kelahiran', 'akta'],
        'mati' => ['kematian', 'meninggal'], 'meninggal' => ['kematian', 'meninggal'], 'wafat' => ['kematian'],
        'pindah' => ['pindah', 'pindah domisili'], 'domisili' => ['domisili'], 'usaha' => ['usaha', 'umkm'], 'jualan' => ['umkm', 'usaha', 'produk'],
        'dagang' => ['umkm', 'usaha'], 'produk' => ['umkm', 'produk'], 'wisata' => ['wisata', 'potensi'], 'budidaya' => ['budidaya', 'potensi'],
        'surat' => ['surat', 'keterangan', 'layanan'], 'keterangan' => ['keterangan', 'surat'], 'bansos' => ['bansos', 'bantuan sosial', 'bantuan'],
        'bantuan' => ['bantuan', 'bansos'], 'blt' => ['blt', 'bantuan', 'bansos'], 'pkh' => ['pkh', 'bansos'], 'dana' => ['dana', 'anggaran', 'apbdes'],
        'anggaran' => ['anggaran', 'apbdes'], 'apbdes' => ['apbdes', 'anggaran'], 'kades' => ['kepala desa', 'kades'], 'lurah' => ['kepala desa'],
        'acara' => ['agenda', 'kegiatan', 'acara'], 'kegiatan' => ['kegiatan', 'agenda'], 'foto' => ['galeri', 'foto'], 'video' => ['galeri', 'video'],
        'lapor' => ['pengaduan', 'aduan', 'lapor'], 'keluhan' => ['pengaduan', 'aduan'], 'aduan' => ['pengaduan', 'aduan'], 'komplain' => ['pengaduan'],
        'gizi' => ['stunting', 'gizi'], 'balita' => ['stunting', 'balita'], 'posyandu' => ['posyandu', 'stunting'], 'darurat' => ['darurat', 'siaga'],
        'kabar' => ['berita'], 'news' => ['berita'], 'perdes' => ['perdes', 'peraturan desa', 'dokumen'], 'peraturan' => ['peraturan', 'perdes'],
        'struktur' => ['struktur', 'sotk', 'perangkat'], 'perangkat' => ['perangkat', 'aparatur'], 'sejarah' => ['sejarah', 'profil'],
        'visi' => ['visi', 'misi', 'profil'], 'misi' => ['visi', 'misi'], 'pembangunan' => ['pembangunan', 'proyek'], 'jalan' => ['jalan', 'pembangunan'],
        'game' => ['hiburan', 'game', 'kuis'], 'kuis' => ['kuis', 'hiburan'], 'buku' => ['perpus', 'buku'], 'perpustakaan' => ['perpus'],
    ];

    /** Tujuan halaman statis untuk "Aksi Cepat". [judul, path, ikon, deskripsi, kata kunci, fitur] */
    private static function destinations(): array {
        return [
            ['Ajukan Surat Online', 'layanan', 'bi-send-fill', 'Permohonan surat keterangan secara online', 'surat layanan ajukan permohonan keterangan domisili usaha ktp kk sktm kelahiran kematian pindah nikah', 'services'],
            ['Cek Status Surat', 'layanan/cek', 'bi-search', 'Lacak permohonan surat dengan nomor tiket', 'cek status tiket lacak permohonan surat', 'services'],
            ['Kirim Pengaduan', 'pengaduan', 'bi-chat-square-text', 'Sampaikan aduan, keluhan atau laporan warga', 'pengaduan aduan lapor keluhan komplain laporan', 'complaints'],
            ['Profil Desa', 'profil', 'bi-info-circle', 'Sejarah, visi misi, geografis dan demografi', 'profil sejarah visi misi geografis demografi wilayah', 'village_profile'],
            ['Pemerintahan Desa', 'pemerintahan', 'bi-bank', 'Kepala desa dan perangkat desa', 'pemerintahan perangkat kepala kades aparatur pejabat', 'village_profile'],
            ['Struktur Organisasi (SOTK)', 'sotk', 'bi-diagram-3', 'Bagan struktur organisasi pemerintah desa', 'struktur organisasi sotk bagan', 'village_profile'],
            ['Statistik Penduduk', 'statistik', 'bi-bar-chart', 'Grafik jumlah penduduk, usia, pekerjaan', 'statistik penduduk jumlah warga demografi grafik usia pekerjaan agama pendidikan', 'population'],
            ['Transparansi APBDes', 'transparansi', 'bi-shield-check', 'Anggaran dan realisasi dana desa', 'transparansi apbdes anggaran dana pembangunan realisasi keuangan', 'transparency'],
            ['Indeks Desa Membangun (IDM)', 'idm', 'bi-graph-up', 'Status dan skor IDM desa', 'idm indeks desa membangun status mandiri maju', 'public_data'],
            ['Data Bansos', 'bansos', 'bi-people', 'Program dan penerima bantuan sosial', 'bansos bantuan sosial blt pkh penerima program', 'public_data'],
            ['Data Stunting', 'stunting', 'bi-heart-pulse', 'Pemantauan gizi dan tumbuh kembang balita', 'stunting gizi balita posyandu kesehatan', 'public_data'],
            ['SDGs Desa', 'sdgs', 'bi-globe2', 'Capaian tujuan pembangunan berkelanjutan', 'sdgs pembangunan berkelanjutan', 'village_profile'],
            ['Berita Desa', 'berita', 'bi-newspaper', 'Kabar dan kegiatan terbaru', 'berita kabar terbaru artikel', 'news'],
            ['Pengumuman', 'pengumuman', 'bi-megaphone', 'Informasi resmi dari pemerintah desa', 'pengumuman informasi resmi', 'news'],
            ['Agenda Kegiatan', 'agenda', 'bi-calendar-event', 'Jadwal acara dan kegiatan desa', 'agenda jadwal acara kegiatan rapat', 'news'],
            ['Galeri Foto & Video', 'galeri', 'bi-images', 'Dokumentasi kegiatan desa', 'galeri foto video dokumentasi album', 'gallery'],
            ['UMKM Desa', 'umkm', 'bi-shop', 'Produk usaha warga desa', 'umkm usaha produk jualan dagang toko', 'economy'],
            ['Potensi Desa', 'potensi', 'bi-tree', 'Wisata, pertanian dan budidaya', 'potensi wisata budidaya pertanian peternakan', 'economy'],
            ['Dokumen Publik', 'dokumen', 'bi-file-earmark-arrow-down', 'Unduh perdes, laporan dan dokumen resmi', 'dokumen unduh download perdes peraturan laporan', 'documents'],
            ['Kontak & Lokasi Kantor', 'kontak', 'bi-geo-alt', 'Alamat, telepon, email dan peta kantor desa', 'kontak alamat lokasi kantor telepon email peta hubungi jam buka pelayanan whatsapp', 'contact'],
            ['Zona Hiburan', 'hiburan', 'bi-controller', 'Kuis, game dan polling warga', 'hiburan game kuis polling main', 'entertainment'],
        ];
    }

    public static function norm(string $s): string {
        $s = mb_strtolower(strip_tags($s), 'UTF-8');
        $s = preg_replace('/[^\p{L}\p{N}\s\-]+/u', ' ', $s) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $s) ?? '');
    }

    public static function run(string $raw): array {
        $q = self::norm(mb_substr($raw, 0, 120));
        if ($q === '') return self::suggest();
        return Cache::remember('smartsearch:v3:' . Cache::version('public') . ':' . md5($q), 120, static fn(): array => self::search($q));
    }

    private static function suggest(): array {
        $chips = ['Surat domisili', 'Jumlah penduduk', 'Jam pelayanan', 'Bansos', 'Agenda', 'UMKM', 'Pengaduan', 'APBDes'];
        $latest = feature_enabled('news') ? db_all("SELECT title, slug, published_at FROM news WHERE deleted_at IS NULL AND status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3") : [];
        return ['q' => '', 'chips' => $chips, 'groups' => $latest ? [[
            'key' => 'berita', 'label' => 'Berita Terbaru', 'icon' => 'bi-newspaper',
            'items' => array_map(static fn($n) => ['title' => $n['title'], 'desc' => tgl_id($n['published_at']), 'url' => url('berita/' . $n['slug'])], $latest),
        ]] : [], 'answers' => [], 'total' => 0];
    }

    private static function vocabulary(): array {
        return Cache::remember('smartsearch:vocab:' . Cache::version('public'), 600, static function (): array {
            $words = [];
            foreach (self::destinations() as $d) foreach (explode(' ', $d[4]) as $w) $words[$w] = 1;
            foreach (array_keys(self::SYN) as $w) $words[$w] = 1;
            $titles = [];
            try {
                if (feature_enabled('services')) $titles = array_merge($titles, array_column(db_all("SELECT name t FROM services WHERE deleted_at IS NULL AND is_active=1"), 't'));
                if (feature_enabled('news')) $titles = array_merge($titles, array_column(db_all("SELECT title t FROM news WHERE deleted_at IS NULL AND status='published' ORDER BY id DESC LIMIT 200"), 't'));
                if (feature_enabled('economy')) $titles = array_merge($titles, array_column(db_all("SELECT name t FROM umkm_products WHERE deleted_at IS NULL AND is_active=1"), 't'));
            } catch (Throwable $e) {}
            foreach ($titles as $t) foreach (explode(' ', self::norm((string) $t)) as $w) if (mb_strlen($w) >= 4) $words[$w] = 1;
            return array_keys($words);
        });
    }

    /** Koreksi typo ringan memakai jarak Levenshtein terhadap kosakata situs. */
    private static function correct(array $tokens): array {
        $vocab = self::vocabulary();
        $set = array_flip($vocab);
        $out = []; $changed = false;
        foreach ($tokens as $t) {
            if (isset($set[$t]) || mb_strlen($t) < 4 || preg_match('/\d/', $t)) { $out[] = $t; continue; }
            $best = $t; $bestD = mb_strlen($t) >= 5 ? 3 : 2;
            foreach ($vocab as $v) {
                if (abs(strlen($v) - strlen($t)) >= $bestD) continue;
                $d = levenshtein($t, $v);
                if ($d < $bestD) { $bestD = $d; $best = $v; }
            }
            if ($best !== $t) $changed = true;
            $out[] = $best;
        }
        return [$out, $changed];
    }

    private static function search(string $q): array {
        $all = explode(' ', $q);
        $tokens = array_values(array_filter($all, static fn($t) => !in_array($t, self::STOP, true) && mb_strlen($t) >= 2));
        $fallback = !$tokens;
        if ($fallback) $tokens = array_values(array_filter($all, static fn($t) => mb_strlen($t) >= 2));
        [$fixed, $changed] = $fallback ? [$tokens, false] : self::correct($tokens);
        $terms = [];
        foreach ($fixed as $i => $t) {
            $terms[$t] = 1;
            // Kata asli tetap dicari agar ejaan baku (mis. "keripik") tidak hilang setelah dikoreksi.
            if ($tokens[$i] !== $t) $terms[$tokens[$i]] = 1;
            foreach (self::SYN[$t] ?? [] as $s) $terms[$s] = 1;
        }
        // Kata 2 huruf (rt, rw, kk) hanya dipakai lewat sinonim/jawaban agar tidak cocok di tengah kata lain.
        $terms = array_slice(array_values(array_filter(array_keys($terms), static fn($t) => mb_strlen($t) >= 3)), 0, 10);
        $phrase = implode(' ', $fixed);

        $groups = [];
        $actions = self::matchDestinations($fixed, $terms);
        if ($actions) $groups[] = ['key' => 'aksi', 'label' => 'Aksi Cepat', 'icon' => 'bi-lightning-charge-fill', 'items' => $actions];
        foreach (self::contentSources() as $src) {
            if ($src['feature'] !== '' && !feature_enabled($src['feature'])) continue;
            $items = self::queryTable($src, $terms, $phrase);
            if ($items) $groups[] = ['key' => $src['key'], 'label' => $src['label'], 'icon' => $src['icon'], 'items' => $items];
        }
        $answers = self::answers($q . ' ' . $phrase);
        $total = array_sum(array_map(static fn($g) => count($g['items']), $groups)) + count($answers);
        return ['q' => $q, 'corrected' => $changed ? $phrase : '', 'answers' => $answers, 'groups' => $groups, 'total' => $total];
    }

    private static function matchDestinations(array $tokens, array $terms): array {
        $scored = [];
        foreach (self::destinations() as [$title, $path, $icon, $desc, $kw, $feature]) {
            if (!feature_enabled($feature)) continue;
            $hay = ' ' . self::norm($title . ' ' . $kw) . ' ';
            $score = 0;
            foreach ($terms as $t) {
                if (str_contains($hay, ' ' . $t . ' ')) $score += in_array($t, $tokens, true) ? 10 : 5;
                elseif (mb_strlen($t) >= 3 && str_contains($hay, ' ' . $t)) $score += 4;
            }
            if ($score > 0) $scored[] = ['score' => $score, 'title' => $title, 'desc' => $desc, 'url' => url($path), 'icon' => $icon];
        }
        usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);
        return array_map(static function ($x) { unset($x['score']); return $x; }, array_slice($scored, 0, 4));
    }

    private static function contentSources(): array {
        $live = "deleted_at IS NULL";
        return [
            ['key' => 'layanan', 'label' => 'Layanan Surat', 'icon' => 'bi-file-earmark-text', 'feature' => 'services', 'table' => 'services', 'title' => 'name', 'body' => 'description',
                'where' => "$live AND is_active=1", 'select' => 'name AS t, description AS b, slug, duration', 'url' => static fn($r) => url('layanan/' . $r['slug']), 'meta' => static fn($r) => $r['duration'] ? 'Estimasi ' . $r['duration'] : 'Layanan online'],
            ['key' => 'berita', 'label' => 'Berita', 'icon' => 'bi-newspaper', 'feature' => 'news', 'table' => 'news', 'title' => 'title', 'body' => 'content',
                'where' => "$live AND status='published' AND published_at <= NOW()", 'select' => 'title AS t, COALESCE(NULLIF(excerpt,\'\'), content) AS b, slug, published_at, thumbnail', 'order' => 'published_at DESC',
                'url' => static fn($r) => url('berita/' . $r['slug']), 'meta' => static fn($r) => tgl_id($r['published_at']), 'thumb' => static fn($r) => $r['thumbnail'] ? thumbnail_url($r['thumbnail'], 160, 120) : ''],
            ['key' => 'pengumuman', 'label' => 'Pengumuman', 'icon' => 'bi-megaphone', 'feature' => 'news', 'table' => 'announcements', 'title' => 'title', 'body' => 'content',
                'where' => "$live AND is_active=1", 'select' => 'id, title AS t, content AS b, start_date', 'order' => 'id DESC', 'url' => static fn($r) => url('pengumuman/' . $r['id']), 'meta' => static fn($r) => $r['start_date'] ? tgl_id($r['start_date']) : 'Pengumuman'],
            ['key' => 'agenda', 'label' => 'Agenda', 'icon' => 'bi-calendar-event', 'feature' => 'news', 'table' => 'agendas', 'title' => 'title', 'body' => 'description',
                'where' => "$live AND status != 'dibatalkan'", 'select' => 'title AS t, CONCAT(COALESCE(location,\'\'), \' \', COALESCE(description,\'\')) AS b, event_date', 'order' => 'event_date DESC',
                'url' => static fn($r) => url('agenda'), 'meta' => static fn($r) => tgl_id($r['event_date'])],
            ['key' => 'umkm', 'label' => 'UMKM', 'icon' => 'bi-shop', 'feature' => 'economy', 'table' => 'umkm_products', 'title' => 'name', 'body' => 'description',
                'where' => "$live AND is_active=1", 'select' => 'name AS t, CONCAT(COALESCE(category,\'\'), \' \', COALESCE(owner,\'\'), \' \', COALESCE(description,\'\')) AS b, price, photo, owner',
                'url' => static fn($r) => url('umkm'), 'meta' => static fn($r) => trim(($r['owner'] ? $r['owner'] . ' · ' : '') . ((float) $r['price'] > 0 ? rupiah($r['price']) : 'UMKM')), 'thumb' => static fn($r) => $r['photo'] ? thumbnail_url($r['photo'], 160, 120) : '', 'titleCase' => true],
            ['key' => 'potensi', 'label' => 'Potensi Desa', 'icon' => 'bi-tree', 'feature' => 'economy', 'table' => 'village_potentials', 'title' => 'title', 'body' => 'description',
                'where' => "$live AND is_active=1", 'select' => 'title AS t, CONCAT(COALESCE(category,\'\'), \' \', COALESCE(description,\'\')) AS b, category, photo', 'url' => static fn($r) => url('potensi'), 'meta' => static fn($r) => $r['category'] ?: 'Potensi', 'thumb' => static fn($r) => $r['photo'] ? thumbnail_url($r['photo'], 160, 120) : ''],
            ['key' => 'dokumen', 'label' => 'Dokumen Publik', 'icon' => 'bi-file-earmark-arrow-down', 'feature' => 'documents', 'table' => 'documents', 'title' => 'title', 'body' => 'description',
                'where' => "$live AND is_active=1 AND is_public=1", 'select' => 'title AS t, CONCAT(COALESCE(category,\'\'), \' \', COALESCE(description,\'\')) AS b, year, file_type', 'url' => static fn($r) => url('dokumen'), 'meta' => static fn($r) => strtoupper((string) $r['file_type']) . ($r['year'] ? ' · ' . $r['year'] : '')],
            ['key' => 'galeri', 'label' => 'Galeri', 'icon' => 'bi-images', 'feature' => 'gallery', 'table' => 'gallery_albums', 'title' => 'name', 'body' => 'description',
                'where' => "is_active=1", 'select' => 'name AS t, description AS b, slug, year', 'url' => static fn($r) => url('galeri/' . $r['slug']), 'meta' => static fn($r) => 'Album' . ($r['year'] ? ' ' . $r['year'] : '')],
            ['key' => 'perangkat', 'label' => 'Perangkat Desa', 'icon' => 'bi-person-badge', 'feature' => 'village_profile', 'table' => 'village_officials', 'title' => 'nama', 'body' => 'jabatan',
                'where' => "$live AND is_active=1", 'select' => 'nama AS t, jabatan AS b, jabatan', 'url' => static fn($r) => url('pemerintahan'), 'meta' => static fn($r) => $r['jabatan'], 'titleCase' => true],
            ['key' => 'halaman', 'label' => 'Halaman', 'icon' => 'bi-journal-text', 'feature' => 'static_pages', 'table' => 'pages', 'title' => 'title', 'body' => 'content',
                'where' => "$live AND is_active=1", 'select' => 'title AS t, content AS b, slug', 'url' => static fn($r) => url('halaman/' . $r['slug']), 'meta' => static fn($r) => 'Halaman informasi'],
            ['key' => 'faq', 'label' => 'Tanya Jawab', 'icon' => 'bi-patch-question', 'feature' => '', 'table' => 'faqs', 'title' => 'question', 'body' => 'answer',
                'where' => "$live AND is_active=1", 'select' => 'question AS t, answer AS b', 'url' => static fn($r) => url('#faq'), 'meta' => static fn($r) => 'FAQ', 'full' => true],
        ];
    }

    private static function queryTable(array $src, array $terms, string $phrase): array {
        $conds = []; $params = [];
        foreach ($terms as $t) {
            $like = '%' . addcslashes($t, '%_\\') . '%';
            $conds[] = "`{$src['title']}` LIKE ? OR `{$src['body']}` LIKE ?";
            array_push($params, $like, $like);
        }
        if (!$conds) return [];
        $sql = "SELECT {$src['select']} FROM `{$src['table']}` WHERE {$src['where']} AND (" . implode(' OR ', $conds) . ")"
            . (isset($src['order']) ? " ORDER BY {$src['order']}" : '') . " LIMIT 40";
        try { $rows = db_all($sql, $params); } catch (Throwable $e) { return []; }
        $scored = [];
        foreach ($rows as $r) {
            $title = self::norm((string) $r['t']);
            $body = self::norm(excerpt((string) $r['b'], 4000));
            $score = ($phrase !== '' && str_contains($title, $phrase)) ? 40 : 0;
            foreach ($terms as $t) {
                if (str_contains($title, $t)) $score += str_starts_with($title, $t) ? 14 : 10;
                elseif (str_contains($body, $t)) $score += 3;
            }
            if ($score <= 0) continue;
            $scored[] = ['score' => $score, 'r' => $r];
        }
        usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);
        $items = [];
        foreach (array_slice($scored, 0, 5) as $x) {
            $r = $x['r'];
            $title = !empty($src['titleCase']) ? title_case_id((string) $r['t']) : (string) $r['t'];
            $item = ['title' => $title, 'desc' => excerpt((string) $r['b'], !empty($src['full']) ? 260 : 120), 'url' => ($src['url'])($r), 'meta' => (string) ($src['meta'])($r)];
            if (isset($src['thumb'])) { $th = ($src['thumb'])($r); if ($th) $item['thumb'] = $th; }
            $items[] = $item;
        }
        return $items;
    }

    /** Jawaban langsung untuk pertanyaan umum (jumlah penduduk, jam pelayanan, kontak, dst.). */
    private static function answers(string $q): array {
        $has = static fn(string $re): bool => (bool) preg_match('/\b(' . $re . ')/u', $q);
        $out = [];
        $num = static fn($n) => number_format((int) $n, 0, ',', '.');
        $askCount = $has('jumlah|berapa|total|banyak|statistik|data');
        if (feature_enabled('population') && ($askCount || $has('penduduk|warga|jiwa|dusun|rt|rw|kk|keluarga'))) {
            $st = Cache::remember('smartsearch:stats:' . Cache::version('public'), 300, static function (): array {
                $r = db_row("SELECT COUNT(*) p, SUM(jenis_kelamin='L') l, SUM(jenis_kelamin='P') w FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup'") ?: [];
                $g = db_row("SELECT SUM(type='dusun') d, SUM(type='rw') rw, SUM(type='rt') rt FROM regions") ?: [];
                return ['p' => (int) ($r['p'] ?? 0), 'l' => (int) ($r['l'] ?? 0), 'w' => (int) ($r['w'] ?? 0),
                    'kk' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
                    'd' => (int) ($g['d'] ?? 0), 'rw' => (int) ($g['rw'] ?? 0), 'rt' => (int) ($g['rt'] ?? 0)];
            });
            if ($has('penduduk|warga|jiwa|orang')) $out[] = ['icon' => 'bi-people-fill', 'label' => 'Jumlah Penduduk', 'value' => $num($st['p']) . ' jiwa', 'sub' => 'Laki-laki ' . $num($st['l']) . ' · Perempuan ' . $num($st['w']), 'url' => url('statistik')];
            if ($has('laki|pria')) $out[] = ['icon' => 'bi-gender-male', 'label' => 'Penduduk Laki-laki', 'value' => $num($st['l']) . ' jiwa', 'url' => url('statistik')];
            if ($has('perempuan|wanita')) $out[] = ['icon' => 'bi-gender-female', 'label' => 'Penduduk Perempuan', 'value' => $num($st['w']) . ' jiwa', 'url' => url('statistik')];
            if ($has('kk|kepala keluarga|keluarga')) $out[] = ['icon' => 'bi-house-heart', 'label' => 'Jumlah Kartu Keluarga', 'value' => $num($st['kk']) . ' KK', 'url' => url('statistik')];
            if ($has('dusun|rt\\b|rw\\b|wilayah')) $out[] = ['icon' => 'bi-map', 'label' => 'Wilayah Administratif', 'value' => $num($st['d']) . ' Dusun · ' . $num($st['rw']) . ' RW · ' . $num($st['rt']) . ' RT', 'url' => url('profil')];
        }
        if ($has('kepala desa|kades|lurah|pak kades') && setting('kepala_desa_nama') !== '') {
            $out[] = ['icon' => 'bi-person-badge-fill', 'label' => 'Kepala Desa', 'value' => title_case_id(setting('kepala_desa_nama')), 'sub' => setting('village_name'), 'url' => url('pemerintahan')];
        }
        if ($has('jam|buka|tutup|pelayanan|kantor|libur') && setting('jam_pelayanan') !== '') {
            $out[] = ['icon' => 'bi-clock-fill', 'label' => 'Jam Pelayanan Kantor', 'value' => setting('jam_pelayanan'), 'url' => url('kontak')];
        }
        if ($has('alamat|lokasi|kantor|dimana|letak|peta|maps') && setting('address') !== '') {
            $out[] = ['icon' => 'bi-geo-alt-fill', 'label' => 'Alamat Kantor Desa', 'value' => setting('address'), 'url' => url('kontak')];
        }
        if ($has('telepon|telp|nomor|hp|kontak|hubungi|wa|whatsapp|email|surel') && (setting('phone') !== '' || setting('email') !== '')) {
            $out[] = ['icon' => 'bi-telephone-fill', 'label' => 'Kontak Desa', 'value' => trim(setting('phone') . (setting('email') ? ' · ' . setting('email') : ''), ' ·'), 'url' => url('kontak')];
        }
        return array_slice(array_values(array_unique($out, SORT_REGULAR)), 0, 3);
    }
}
