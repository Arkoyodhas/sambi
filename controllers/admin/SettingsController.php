<?php

class SettingsController extends Controller {

    public array $actions = ['index', 'simpan', 'testwa', 'resetcache'];

    const TEXT_KEYS = [
        'umum' => ['village_name', 'village_tagline', 'village_description', 'address', 'kecamatan', 'kabupaten', 'provinsi', 'kode_pos', 'phone', 'whatsapp', 'email', 'jam_pelayanan', 'kepala_desa_nama', 'sambutan', 'luas_wilayah', 'map_lat', 'map_lng', 'map_zoom', 'meta_title', 'meta_description'],
        'tampilan' => ['primary_color', 'secondary_color', 'accent_color', 'button_floating_color', 'btn_hero_primary_color', 'btn_section_cta_color', 'btn_livechat_color', 'btn_feedback_color', 'btn_backtotop_color', 'btn_bell_color', 'btn_notif_toast_color', 'btn_install_color', 'verify_color', 'verify_icon', 'font_heading', 'font_body', 'layout_style', 'card_radius', 'button_style', 'navbar_style', 'footer_style', 'default_theme', 'home_sections', 'home_banner_enabled', 'hero_video_enabled', 'hero_style', 'hero_elements', 'hero_buttons'],
        'sosial' => ['facebook', 'instagram', 'youtube', 'twitter', 'tiktok', 'home_social_enabled', 'home_social_title', 'home_social_items'],
        'fitur' => ['photo_protection', 'photo_watermark', 'watermark_text', 'comments_enabled', 'comment_moderation', 'music_enabled', 'music_autoplay', 'music_button_visible', 'bgfx_enabled', 'bgfx_density', 'screenshot_protection', 'screenshot_mode', 'screenshot_wa_notify', 'screenshot_wa_threshold', 'face_login_enabled'],
        'notifikasi' => ['fonnte_enabled', 'fonnte_token', 'wa_admin_number', 'wa_notify_applicant', 'wa_notify_admin', 'wa_route_chat', 'wa_route_surat', 'wa_route_aduan', 'wa_route_kontak', 'wa_route_feedback', 'wa_route_keamanan'],
        'notifweb' => ['webnotif_enabled', 'webnotif_berita', 'webnotif_pengumuman', 'webnotif_agenda', 'webnotif_limit', 'webnotif_popup', 'webnotif_popup_important', 'livechat_enabled', 'livechat_wa_notify'],
    ];

    const CHECKBOX_KEYS = ['photo_protection', 'photo_watermark', 'comments_enabled', 'comment_moderation', 'music_enabled', 'music_autoplay', 'music_button_visible', 'bgfx_enabled', 'screenshot_protection', 'screenshot_wa_notify', 'hero_video_enabled', 'fonnte_enabled', 'wa_notify_applicant', 'wa_notify_admin', 'webnotif_enabled', 'webnotif_berita', 'webnotif_pengumuman', 'webnotif_agenda', 'webnotif_popup', 'webnotif_popup_important', 'livechat_enabled', 'livechat_wa_notify', 'home_social_enabled', 'home_banner_enabled', 'face_login_enabled'];

    public function __construct() { Auth::requirePermission('kelola_tampilan'); }

    public function index(): void {
        $this->adminView('pengaturan', ['pageTitle' => 'Pengaturan Website', 'tab' => $_GET['tab'] ?? 'umum']);
    }

    public function simpan(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/pengaturan');
        verify_csrf();
        $group = (string) ($_POST['group'] ?? 'umum');
        if (!array_key_exists($group, self::TEXT_KEYS)) {
            flash('danger', 'Kelompok pengaturan tidak valid.');
            redirect('admin/pengaturan');
        }
        $keys = self::TEXT_KEYS[$group];
        $colorDefaults = [
            'primary_color' => '#0f6b4e',
            'secondary_color' => '#0b3d2e',
            'accent_color' => '#e8b44c',
            'button_floating_color' => '#0f6b4e',
            'btn_hero_primary_color' => '#0f6b4e',
            'btn_section_cta_color' => '#0f6b4e',
            'btn_livechat_color' => '#0f6b4e',
            'btn_feedback_color' => '#0f6b4e',
            'btn_backtotop_color' => '#0f6b4e',
            'btn_bell_color' => '#0f6b4e',
            'btn_notif_toast_color' => '#0f6b4e',
            'btn_install_color' => '#0f6b4e',
            'verify_color' => '#34d399',
        ];
        $verifyIcons = ['bi-patch-check-fill', 'bi-patch-check', 'bi-check-circle-fill', 'bi-shield-check', 'bi-award-fill'];

        foreach ($keys as $key) {
            if (in_array($key, self::CHECKBOX_KEYS, true)) {
                set_setting($key, isset($_POST[$key]) ? '1' : '0');
            } elseif (isset($_POST[$key])) {
                $val = $_POST[$key];
                if (is_array($val)) $val = implode(',', array_map('trim', $val));
                $val = trim((string) $val);

                if (isset($colorDefaults[$key])) {
                    $val = preg_match('/^#[0-9a-f]{6}$/i', $val) ? strtolower($val) : $colorDefaults[$key];
                } elseif ($key === 'verify_icon') {
                    $val = in_array($val, $verifyIcons, true) ? $val : 'bi-patch-check-fill';
                } elseif (in_array($key, ['facebook', 'instagram', 'youtube', 'twitter', 'tiktok'], true) && $val !== '') {
                    $normalized = normalize_social_url($val, $key);
                    if ($normalized === '') {
                        flash('danger', 'Tautan ' . ucfirst($key) . ' tidak valid. Gunakan URL lengkap atau handle akun yang benar.');
                        redirect('admin/pengaturan?tab=' . $group);
                    }
                    if (in_array($key, ['youtube', 'instagram', 'tiktok'], true)
                        && social_platform_from_url($normalized) !== $key) {
                        flash('danger', 'Tautan pada kolom ' . ucfirst($key) . ' harus berasal dari domain resmi ' . ucfirst($key) . '.');
                        redirect('admin/pengaturan?tab=' . $group);
                    }
                    $val = $normalized;
                } elseif ($key === 'home_social_items') {
                    $cleanLines = [];
                    $invalidLines = [];

                    foreach (preg_split('/\R+/', $val) ?: [] as $line) {
                        $line = trim((string) $line);
                        if ($line === '') continue;

                        $parts = array_map('trim', explode('|', $line, 3));
                        if (count($parts) === 1) {
                            $socialUrl = normalize_social_url($parts[0]);
                            $socialPlatform = social_platform_from_url($socialUrl);
                            $socialTitle = '';
                        } else {
                            $socialPlatform = strtolower($parts[0] ?? '');
                            $socialUrl = normalize_social_url($parts[1] ?? '', $socialPlatform);
                            $socialTitle = trim($parts[2] ?? '');
                        }

                        $details = ($socialUrl !== '' && in_array($socialPlatform, ['youtube', 'instagram', 'tiktok'], true))
                            ? social_embed_details($socialUrl, $socialPlatform)
                            : ['embed_url' => ''];

                        if ($socialUrl === '' || social_platform_from_url($socialUrl) !== $socialPlatform || ($details['embed_url'] ?? '') === '') {
                            $invalidLines[] = $line;
                            continue;
                        }

                        $normalizedLine = $socialTitle !== ''
                            ? $socialPlatform . '|' . $socialUrl . '|' . $socialTitle
                            : $socialUrl;
                        $cleanLines[$socialPlatform . '|' . strtolower(rtrim($socialUrl, '/'))] = $normalizedLine;
                    }

                    if ($invalidLines) {
                        flash('danger', 'Ada URL sosial yang tidak dapat ditampilkan langsung. Gunakan URL video/playlist/channel ID YouTube, post/reel Instagram, atau video TikTok.');
                        redirect('admin/pengaturan?tab=' . $group);
                    }

                    $val = implode(PHP_EOL, array_values($cleanLines));
                } elseif ($key === 'home_sections') {
                    $val = implode(',', array_values(array_unique(array_filter(
                        array_map(static fn($x) => preg_replace('/[^a-z_]/', '', strtolower(trim($x))), explode(',', $val))
                    ))));
                }

                set_setting($key, $val);
            }
        }

        // Pertahankan kompatibilitas dengan tema lama: button_color mengikuti
        // warna tombol floating supaya komponen lama tetap konsisten.
        if ($group === 'tampilan' && isset($_POST['button_floating_color'])) {
            $floatingButton = preg_match('/^#[0-9a-f]{6}$/i', (string) $_POST['button_floating_color'])
                ? strtolower((string) $_POST['button_floating_color'])
                : '#0f6b4e';
            set_setting('button_color', $floatingButton);
        }

        // Saat slider sosial diaktifkan, section beranda ditambahkan otomatis.
        if ($group === 'sosial' && isset($_POST['home_social_enabled'])) {
            $hasSocial = trim((string) ($_POST['home_social_items'] ?? '')) !== '';
            foreach (['youtube', 'instagram', 'tiktok'] as $socialKey) {
                if (trim((string) ($_POST[$socialKey] ?? '')) !== '') $hasSocial = true;
            }
            if ($hasSocial) {
                $sections = array_values(array_filter(array_map('trim', explode(',', setting('home_sections', 'stats,transparansi,sambutan,layanan,berita,pengumuman,agenda,perangkat,galeri,potensi,umkm,peta')))));
                if (!in_array('social', $sections, true)) {
                    $mapIndex = array_search('peta', $sections, true);
                    if ($mapIndex === false) $sections[] = 'social';
                    else array_splice($sections, $mapIndex, 0, ['social']);
                    set_setting('home_sections', implode(',', $sections));
                }
            }
        }
        // upload branding
        foreach (['logo' => 'logo', 'favicon' => 'logo', 'verify_logo' => 'logo', 'hero_image' => 'image', 'kepala_desa_foto' => 'image', 'og_image' => 'image', 'hero_layer_sky' => 'image', 'hero_layer_hills' => 'image', 'hero_layer_village' => 'image', 'hero_diorama_image' => 'image'] as $key => $type) {
            if (!empty($_FILES[$key]['name'])) {
                $res = $type === 'logo' ? Upload::logo($_FILES[$key], 'branding') : Upload::image($_FILES[$key], 'branding', 1920);
                if (is_array($res)) { flash('danger', $res['error']); redirect('admin/pengaturan?tab=' . $group); }
                $oldPath = setting($key);
                try {
                    set_setting($key, $res);
                } catch (Throwable $e) {
                    Upload::delete($res);
                    flash('danger', 'File berhasil diunggah, tetapi pengaturan gagal disimpan. Coba kembali.');
                    redirect('admin/pengaturan?tab=' . $group);
                }
                if ($oldPath && $oldPath !== $res) Upload::delete($oldPath);
                log_activity('edit', 'pengaturan', "Ganti $key website");
            }
        }
        foreach (['hero_layer_sky', 'hero_layer_hills', 'hero_layer_village', 'hero_diorama_image'] as $layerKey) {
            if (isset($_POST['remove_' . $layerKey]) && empty($_FILES[$layerKey]['name']) && setting($layerKey)) {
                $oldLayer = setting($layerKey);
                set_setting($layerKey, '');
                Upload::delete($oldLayer);
                log_activity('edit', 'pengaturan', "Kembalikan $layerKey ke bawaan");
            }
        }
        if (isset($_POST['remove_verify_logo']) && setting('verify_logo')) {
            $oldVerifyLogo = setting('verify_logo');
            set_setting('verify_logo', '');
            Upload::delete($oldVerifyLogo);
            log_activity('edit', 'pengaturan', 'Hapus logo verifikasi khusus');
        }

        // video hero
        if (!empty($_FILES['hero_video']['name'])) {
            $res = Upload::video($_FILES['hero_video'], 'video');
            if (is_array($res)) { flash('danger', $res['error']); redirect('admin/pengaturan?tab=' . $group); }
            $oldHeroVideo = setting('hero_video');
            try {
                set_setting('hero_video', $res);
            } catch (Throwable $e) {
                Upload::delete($res);
                flash('danger', 'Video berhasil diunggah, tetapi pengaturan gagal disimpan. Coba kembali.');
                redirect('admin/pengaturan?tab=' . $group);
            }
            if ($oldHeroVideo && $oldHeroVideo !== $res) Upload::delete($oldHeroVideo);
            log_activity('edit', 'pengaturan', 'Ganti video background hero');
        }
        if (isset($_POST['remove_hero_video']) && setting('hero_video')) {
            $oldHeroVideo = setting('hero_video');
            set_setting('hero_video', '');
            set_setting('hero_video_enabled', '0');
            Upload::delete($oldHeroVideo);
            log_activity('edit', 'pengaturan', 'Hapus video background hero');
        }
        if (in_array($group, ['tampilan', 'sosial'], true)) {
            set_setting('asset_version', (string) time());
        }
        log_activity('edit', 'pengaturan', "Update pengaturan grup: $group");
        $message = $group === 'sosial'
            ? 'Pengaturan media sosial berhasil disimpan. Tautan YouTube/Instagram/TikTok juga otomatis digunakan pada slider beranda.'
            : 'Pengaturan berhasil disimpan.';
        flash('success', $message);
        redirect('admin/pengaturan?tab=' . $group);
    }

    /** Naikkan versi aset: memaksa browser semua pengunjung memuat ulang CSS/JS terbaru,
        membersihkan cache PWA (service worker) & penyimpanan lokal agar kode lama tidak bentrok dengan kode baru. */
    public function resetcache(): void {
        verify_csrf();
        set_setting('asset_version', (string) time());
        log_activity('edit', 'pengaturan', 'Reset cache & naikkan versi aset pengunjung');
        flash('success', 'Berhasil! Versi kode website dinaikkan. Saat pengunjung membuka website berikutnya, browser mereka otomatis mengunduh file terbaru serta membersihkan cache & penyimpanan lama — tidak ada lagi bentrok kode lama vs baru.');
        redirect('admin/pengaturan?tab=fitur');
    }

    public function testwa(): void {
        verify_csrf();
        $target = trim($_POST['test_number'] ?? '') ?: trim((string) setting('wa_admin_number'));
        if (!Fonnte::enabled()) { flash('danger', 'Aktifkan integrasi dan isi Token Fonnte terlebih dahulu, lalu simpan.'); redirect('admin/pengaturan?tab=notifikasi'); }
        if ($target === '') { flash('danger', 'Isi Nomor WA Admin atau nomor uji terlebih dahulu.'); redirect('admin/pengaturan?tab=notifikasi'); }
        $r = Fonnte::send($target, "✅ Tes koneksi WhatsApp berhasil!\nWebsite " . setting('village_name') . " terhubung dengan Fonnte.\n" . date('d/m/Y H:i'));
        if ($r['ok']) {
            flash('success', 'Pesan uji BERHASIL dikirim ke ' . e(Fonnte::normalize($target)) . '. Cek WhatsApp Anda.');
        } else {
            flash('danger', 'Gagal mengirim pesan uji: ' . e($r['error']) . '. Periksa token & status device di dashboard Fonnte.');
        }
        log_activity('wa', 'notifikasi', 'Tes koneksi Fonnte: ' . ($r['ok'] ? 'berhasil' : 'gagal - ' . $r['error']));
        redirect('admin/pengaturan?tab=notifikasi');
    }
}
