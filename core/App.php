<?php

class App {

    private array $publicRoutes = [
        '' => ['HomeController', 'index', null],
        'profil' => ['ProfilController', 'index', null],
        'pemerintahan' => ['PemerintahanController', 'index', null],
        'berita' => ['BeritaController', 'index', 'detail'],
        'pengumuman' => ['InfoController', 'pengumuman', 'pengumumanDetail'],
        'agenda' => ['InfoController', 'agenda', null],
        'galeri' => ['GaleriController', 'index', 'album'],
        'layanan' => ['LayananController', 'index', 'detail'],
        'statistik' => ['StatistikController', 'index', null],
        'sotk' => ['SotkController', 'index', null],
        'sdgs' => ['SdgsController', 'index', null],
        'transparansi' => ['TransparansiController', 'index', null],
        'idm' => ['TransparansiController', 'idm', null],
        'bansos' => ['TransparansiController', 'bansos', null],
        'stunting' => ['TransparansiController', 'stunting', null],
        'pengaduan' => ['PengaduanController', 'index', null],
        'umkm' => ['UmkmController', 'index', null],
        'potensi' => ['UmkmController', 'potensi', null],
        'dokumen' => ['DokumenController', 'index', null],
        'kontak' => ['KontakController', 'index', null],
        'halaman' => ['HalamanController', 'show', 'show'],
        'media' => ['MediaController', 'index', null],
        'hiburan' => ['EntertainmentController', 'index', null],
        'api' => ['ApiController', 'index', null],
        'sitemap.xml' => ['ApiController', 'sitemap', null],
        'robots.txt' => ['ApiController', 'robots', null],
        'manifest.webmanifest' => ['ApiController', 'manifest', null],
    ];

    // modul admin dengan controller khusus
    private array $adminCustom = [
        'login' => ['AuthController', 'login'],
        'logout' => ['AuthController', 'logout'],
        'lupa-password' => ['AuthController', 'forgot'],
        'dashboard' => ['DashboardController', 'index'],
        'penduduk' => ['PendudukController', null],
        'keluarga' => ['KeluargaController', null],
        'berita' => ['BeritaAdminController', null],
        'permohonan' => ['PermohonanController', null],
        'pengaduan' => ['PengaduanAdminController', null],
        'pengaturan' => ['SettingsController', null],
        'menu' => ['MenuController', null],
        'users' => ['UserController', null],
        'roles' => ['RoleController', null],
        'audit' => ['AuditController', null],
        'akun' => ['AkunController', null],
        'panduan' => ['PanduanController', null],
        'kunjungan' => ['KunjunganController', null],
        'chat' => ['ChatController', null],
        'screenshot-log' => ['ScreenshotLogController', null],
        'feedback' => ['FeedbackController', null],
        'idm' => ['IdmController', null],
        'bansos' => ['BansosController', null],
        'stunting' => ['StuntingController', null],
        'cloning' => ['CloningController', null],
        'qr' => ['QrController', null],
        'pwa' => ['PwaController', null],
        'push' => ['PushController', null],
        'profil-desa' => ['ProfilDesaController', null],
        'face-auth' => ['FaceAuthController', null],
        'hiburan' => ['EntertainmentAdminController', null],
        'sistem' => ['SistemController', null],
        'kontak-darurat' => ['SiagaController', null],
    ];

    /** Modul publik yang dapat dinonaktifkan melalui config/features.php. */
    private array $publicFeatureMap = [
        'profil' => 'village_profile',
        'pemerintahan' => 'village_profile',
        'sotk' => 'village_profile',
        'sdgs' => 'village_profile',
        'berita' => 'news',
        'pengumuman' => 'news',
        'agenda' => 'news',
        'galeri' => 'gallery',
        'media' => 'gallery',
        'layanan' => 'services',
        'statistik' => 'population',
        'transparansi' => 'transparency',
        'idm' => 'public_data',
        'bansos' => 'public_data',
        'stunting' => 'public_data',
        'pengaduan' => 'complaints',
        'kontak' => 'contact',
        'umkm' => 'economy',
        'potensi' => 'economy',
        'dokumen' => 'documents',
        'halaman' => 'static_pages',
        'hiburan' => 'entertainment',
        'manifest.webmanifest' => 'push_pwa',
    ];

    /** Modul admin yang dapat dinonaktifkan melalui config/features.php. */
    private array $adminFeatureMap = [
        'penduduk' => 'population',
        'keluarga' => 'population',
        'wilayah' => 'population',
        'berita' => 'news',
        'kategori-berita' => 'news',
        'komentar' => 'news',
        'pengumuman' => 'news',
        'agenda' => 'news',
        'galeri' => 'gallery',
        'album' => 'gallery',
        'permohonan' => 'services',
        'layanan' => 'services',
        'pengaduan' => 'complaints',
        'pesan' => 'contact',
        'transparansi' => 'transparency',
        'pembangunan' => 'transparency',
        'dokumen' => 'documents',
        'profil-desa' => 'village_profile',
        'perangkat' => 'village_profile',
        'sotk' => 'village_profile',
        'sdgs' => 'village_profile',
        'idm' => 'public_data',
        'bansos' => 'public_data',
        'stunting' => 'public_data',
        'umkm' => 'economy',
        'potensi' => 'economy',
        'halaman' => 'static_pages',
        'musik' => 'music_event',
        'event' => 'music_event',
        'hiburan' => 'entertainment',
        'chat' => 'live_chat',
        'push' => 'push_pwa',
        'pwa' => 'push_pwa',
        'kunjungan' => 'visitor_audit',
        'screenshot-log' => 'visitor_audit',
        'audit' => 'visitor_audit',
        'feedback' => 'feedback',
        'face-auth' => 'face_auth',
    ];

    public function run(): void {
        try {
            $segments = $this->segments();
            // Blokir IP: pengunjung publik yang IP-nya diblokir tidak bisa mengakses situs
            // (kecuali sesi admin yang sedang login, agar admin tidak terkunci sendiri).
            if (($segments[0] ?? '') !== 'admin' && empty($_SESSION['user_id']) && ip_is_blocked(Visitor::ip())) {
                http_response_code(403);
                header('Content-Type: text/html; charset=utf-8');
                echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Akses Ditolak</title><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:system-ui,sans-serif;background:#0b1220;color:#e8f2ec;display:grid;place-items:center;height:100vh;margin:0;text-align:center;padding:20px}h1{font-size:3rem;margin:0}p{opacity:.7;max-width:420px}</style></head><body><div><h1>403</h1><h2>Akses Diblokir</h2><p>Perangkat Anda tidak diizinkan mengakses situs ini. Jika ini keliru, silakan hubungi pemerintah desa.</p></div></body></html>';
                return;
            }
            if (($segments[0] ?? '') !== 'admin' && empty($_SESSION['user_id']) && Maintenance::active()) {
                Maintenance::render();
                return;
            }
            if (($segments[0] ?? '') === 'admin') {
                $this->dispatchAdmin(array_slice($segments, 1));
            } else {
                $this->dispatchPublic($segments);
            }
        } catch (PDOException $ex) {
            http_response_code(500);
            if (cfg('debug')) throw $ex;
            echo '<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>Terjadi kesalahan database</h2><p>Periksa konfigurasi database di config/config.php dan pastikan file SQL sudah diimport.</p></div>';
        }
    }

    private function segments(): array {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($base !== '' && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));
        $uri = trim(urldecode($uri), '/');
        return $uri === '' ? [''] : explode('/', $uri);
    }

    private function dispatchPublic(array $seg): void {
        $key = $seg[0];
        $sub = $seg[1] ?? null;
        $requiredFeature = $this->publicFeatureMap[$key] ?? null;

        // Beberapa controller publik memuat action dari kelompok fitur berbeda.
        if ($key === 'media') {
            $requiredFeature = match ($sub) {
                'foto' => 'village_profile',
                'musik' => 'music_event',
                'galeri' => 'gallery',
                default => 'gallery',
            };
        } elseif ($key === 'transparansi' && in_array($sub, ['idm', 'bansos', 'stunting'], true)) {
            $requiredFeature = 'public_data';
        }

        if ($requiredFeature !== null && !feature_enabled($requiredFeature)) {
            $this->show404(); return;
        }
        if (!isset($this->publicRoutes[$key])) { $this->show404(); return; }
        [$class, $default, $fallback] = $this->publicRoutes[$key];
        $ctrl = new $class();
        $args = array_slice($seg, 2);

        if ($sub !== null && $sub !== '') {
            $method = str_replace('-', '_', $sub);
            if (in_array($method, $ctrl->actions ?? [], true) && method_exists($ctrl, $method)) {
                $ctrl->$method(...$args); return;
            }
            if ($fallback && method_exists($ctrl, $fallback)) { $ctrl->$fallback($sub, ...$args); return; }
            $this->show404(); return;
        }
        $ctrl->$default();
    }

    private function dispatchAdmin(array $seg): void {
        $module = $seg[0] ?? 'dashboard';
        if ($module === '') $module = 'dashboard';
        $action = $seg[1] ?? 'index';
        $args = array_slice($seg, 2);

        $requiredFeature = $this->adminFeatureMap[$module] ?? null;
        if ($requiredFeature !== null && !feature_enabled($requiredFeature)) {
            $this->show404(); return;
        }

        if (isset($this->adminCustom[$module])) {
            [$class, $fixed] = $this->adminCustom[$module];
            $ctrl = new $class();
            if ($fixed !== null) { $ctrl->$fixed(...array_slice($seg, 1)); return; }
            $method = str_replace('-', '_', $action);
            if (in_array($method, $ctrl->actions ?? [], true) && method_exists($ctrl, $method)) {
                $ctrl->$method(...$args); return;
            }
            $this->show404(); return;
        }

        $modules = require BASE_PATH . '/config/modules.php';
        if (isset($modules[$module])) {
            $ctrl = new CrudController($module, $modules[$module]);
            $method = str_replace('-', '_', $action);
            if (in_array($method, $ctrl->actions, true)) { $ctrl->$method(...$args); return; }
        }
        $this->show404();
    }

    private function show404(): void {
        http_response_code(404);
        $c = new class extends Controller {
            public function render() { $this->view('404', ['pageTitle' => 'Halaman Tidak Ditemukan']); }
        };
        $c->render();
    }
}
