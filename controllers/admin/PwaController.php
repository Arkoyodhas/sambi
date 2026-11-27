<?php

class PwaController extends Controller {

    public array $actions = ['index', 'simpan'];

    public function __construct() { Auth::requirePermission('kelola_konten'); }

    public function index(): void {
        $this->adminView('pwa_settings', [
            'pageTitle' => 'Pengaturan PWA (Aplikasi Web)',
        ]);
    }

    public function simpan(): void {
        verify_csrf();
        set_setting('pwa_name', mb_substr(trim((string)($_POST['pwa_name'] ?? '')), 0, 60));
        set_setting('pwa_short_name', mb_substr(trim((string)($_POST['pwa_short_name'] ?? '')), 0, 14));
        set_setting('pwa_description', mb_substr(trim((string)($_POST['pwa_description'] ?? '')), 0, 200));
        set_setting('pwa_theme_color', mb_substr(trim((string)($_POST['pwa_theme_color'] ?? '')), 0, 20));
        set_setting('pwa_bg_color', mb_substr(trim((string)($_POST['pwa_bg_color'] ?? '')), 0, 20));

        // Upload icon 192 & 512
        foreach ([['pwa_icon_192', 192], ['pwa_icon_512', 512]] as [$key, $size]) {
            if (!empty($_FILES[$key]['tmp_name'])) {
                $res = Upload::image($_FILES[$key], 'pwa', $size);
                if (is_string($res)) set_setting($key, $res);
                else flash('danger', 'Ikon ' . $size . ': ' . ($res['error'] ?? 'gagal diunggah'));
            }
        }
        flash('success', 'Pengaturan PWA disimpan. Warga yang instal ulang akan mendapat ikon & nama baru.');
        redirect('admin/pwa');
    }
}
