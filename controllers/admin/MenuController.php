<?php

class MenuController extends Controller {

    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'reorder'];

    public function __construct() { Auth::requirePermission('kelola_tampilan'); }

    public function index(): void {
        $this->adminView('menu_list', [
            'pageTitle' => 'Manajemen Menu',
            'mainMenus' => db_all("SELECT * FROM menus WHERE location='main' AND parent_id IS NULL ORDER BY sort_order, id"),
            'subMenus' => db_all("SELECT * FROM menus WHERE location='main' AND parent_id IS NOT NULL ORDER BY sort_order, id"),
            'footerMenus' => db_all("SELECT * FROM menus WHERE location='footer' ORDER BY sort_order, id"),
        ]);
    }

    public function create(): void { $this->form(null); }

    public function edit($id = null): void {
        $m = db_row("SELECT * FROM menus WHERE id = ?", [(int) $id]);
        if (!$m) {
            flash('warning', 'Menu yang akan diedit tidak ditemukan.');
            redirect('admin/menu');
        }
        $this->form($m);
    }

    private function form(?array $m): void {
        $params = [];
        $where = "parent_id IS NULL AND location='main'";
        if ($m) {
            $where .= ' AND id != ?';
            $params[] = (int) $m['id'];
        }
        $this->adminView('menu_form', [
            'pageTitle' => ($m ? 'Edit' : 'Tambah') . ' Menu',
            'm' => $m,
            'parents' => db_all("SELECT id, title FROM menus WHERE $where ORDER BY sort_order, title", $params),
        ]);
    }

    public function store(): void { $this->save(null); }
    public function update($id = null): void { $this->save((int) $id); }

    private function save(?int $id): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/menu');
        verify_csrf();

        $old = $id ? db_row("SELECT * FROM menus WHERE id = ?", [$id]) : null;
        if ($id && !$old) {
            flash('warning', 'Menu yang akan diperbarui tidak ditemukan.');
            redirect('admin/menu');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $location = in_array($_POST['location'] ?? '', ['main', 'footer'], true)
            ? (string) $_POST['location']
            : 'main';
        $parentId = (int) ($_POST['parent_id'] ?? 0);
        $url = $this->normalizeMenuUrl((string) ($_POST['url'] ?? '#'));

        $errors = [];
        if ($title === '') $errors[] = 'Judul menu wajib diisi.';
        if ($url === null) $errors[] = 'URL menu tidak valid. Gunakan path internal, tanda #, atau URL HTTPS.';
        if ($location === 'footer') $parentId = 0;
        if ($id && $parentId === $id) $errors[] = 'Menu tidak boleh menjadi induk dirinya sendiri.';

        if ($parentId > 0) {
            $parent = db_row("SELECT id, parent_id, location FROM menus WHERE id = ?", [$parentId]);
            if (!$parent || $parent['location'] !== 'main' || !empty($parent['parent_id'])) {
                $errors[] = 'Menu induk tidak valid.';
            }
        }

        if ($errors) {
            $_SESSION['form_old'] = $_POST;
            flash('danger', implode(' ', array_unique($errors)));
            redirect('admin/menu' . ($id ? "/edit/$id" : '/create'));
        }

        $data = [
            'title' => mb_substr($title, 0, 100, 'UTF-8'),
            'url' => $url,
            'icon' => mb_substr(trim((string) ($_POST['icon'] ?? '')), 0, 100, 'UTF-8') ?: null,
            'target' => ($_POST['target'] ?? '_self') === '_blank' ? '_blank' : '_self',
            'location' => $location,
            'parent_id' => $parentId > 0 ? $parentId : null,
            'sort_order' => max(0, min(9999, (int) ($_POST['sort_order'] ?? 99))),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($id === null) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $id = db_insert('menus', $data);
            } else {
                db_update('menus', $data, 'id = ?', [$id]);
                // Bila menu induk dipindah ke footer, lepaskan seluruh anak agar
                // tidak membentuk struktur yang tidak dapat ditampilkan.
                if ($location === 'footer') {
                    db_exec("UPDATE menus SET parent_id = NULL, updated_at = NOW() WHERE parent_id = ?", [$id]);
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Menu gagal disimpan. Periksa koneksi database dan coba kembali.');
            redirect('admin/menu' . ($old ? "/edit/$id" : '/create'));
        }

        unset($_SESSION['form_old']);
        Cache::bumpVersion('menus');
        invalidate_public_cache();
        log_activity($old ? 'edit' : 'tambah', 'menu', "Menu: $title");
        flash('success', $old ? 'Menu berhasil diperbarui.' : 'Menu berhasil ditambahkan.');
        redirect('admin/menu');
    }

    public function delete($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/menu');
        verify_csrf();

        $id = (int) $id;
        $menu = db_row("SELECT id, title FROM menus WHERE id = ?", [$id]);
        if (!$menu) {
            flash('warning', 'Menu tidak ditemukan.');
            redirect('admin/menu');
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_exec("UPDATE menus SET parent_id = NULL, updated_at = NOW() WHERE parent_id = ?", [$id]);
            db_exec("DELETE FROM menus WHERE id = ?", [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', 'Menu gagal dihapus karena masih digunakan atau database bermasalah.');
            redirect('admin/menu');
        }

        Cache::bumpVersion('menus');
        invalidate_public_cache();
        log_activity('hapus', 'menu', 'Hapus menu: ' . $menu['title']);
        flash('success', 'Menu berhasil dihapus. Submenu yang terkait dipindahkan menjadi menu utama.');
        redirect('admin/menu');
    }

    public function reorder(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Metode tidak valid.']);
        }
        verify_csrf();

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['ids'] ?? [])))));
        if (!$ids) $this->json(['ok' => false, 'error' => 'Data urutan kosong.']);

        $pdo = db();
        try {
            $pdo->beginTransaction();
            foreach ($ids as $i => $rowId) {
                db_exec("UPDATE menus SET sort_order = ?, updated_at = NOW() WHERE id = ?", [$i + 1, $rowId]);
            }
            $pdo->commit();
            Cache::bumpVersion('menus');
            invalidate_public_cache();
            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $this->json(['ok' => false, 'error' => 'Urutan menu gagal disimpan.']);
        }
    }

    private function normalizeMenuUrl(string $value): ?string {
        $url = trim($value);
        if ($url === '' || $url === '#') return '#';
        if ($url === '/') return '/';

        // Path internal seperti /berita atau berita/kategori tetap diperbolehkan.
        if (preg_match('#^/?[a-zA-Z0-9][a-zA-Z0-9/_\-.?=&%#]*$#', $url)) {
            return '/' . ltrim($url, '/');
        }

        if (preg_match('#^(mailto:|tel:)#i', $url)) {
            return preg_match('/[\r\n]/', $url) ? null : $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) return null;
        $parts = parse_url($url);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') return null;
        return $url;
    }
}
