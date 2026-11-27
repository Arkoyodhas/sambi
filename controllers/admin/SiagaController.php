<?php
/** Kontak darurat (CRUD generik) + pengaturan tombol Siaga beranda. */
class SiagaController extends CrudController {

    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'restore', 'trash', 'reorder', 'toggle', 'tombol'];

    public function __construct() {
        $modules = require BASE_PATH . '/config/modules.php';
        parent::__construct('kontak-darurat', $modules['kontak-darurat']);
    }

    public function tombol(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/kontak-darurat');
        verify_csrf();
        $color = (string) ($_POST['btn_siaga_color'] ?? '');
        if (!preg_match('/^#[0-9a-f]{6}$/i', $color)) $color = '#DC2626';
        $label = mb_substr(strip_tags(trim((string) ($_POST['siaga_label'] ?? ''))), 0, 30, 'UTF-8');
        set_setting('siaga_enabled', isset($_POST['siaga_enabled']) ? '1' : '0');
        set_setting('siaga_label', $label !== '' ? $label : 'Siaga Darurat');
        set_setting('btn_siaga_color', $color);
        log_activity('edit', 'kontak-darurat', 'Mengubah pengaturan tombol Siaga');
        flash('success', 'Pengaturan tombol Siaga disimpan.');
        redirect('admin/kontak-darurat');
    }
}
