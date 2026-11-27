<?php

class ProfilDesaController extends Controller {

    public array $actions = ['index', 'edit', 'update'];

    public function __construct() { Auth::requirePermission('kelola_profil'); }

    public function index(): void {
        $sections = db_all("SELECT * FROM village_profiles ORDER BY sort_order ASC");
        $this->adminView('profil_desa', ['pageTitle' => 'Profil Desa', 'sections' => $sections]);
    }

    public function edit($id = null): void {
        $s = db_row("SELECT * FROM village_profiles WHERE id = ?", [(int) $id]);
        if (!$s) redirect('admin/profil-desa');
        $this->adminView('profil_desa_form', ['pageTitle' => 'Edit: ' . $s['title'], 's' => $s]);
    }

    public function update($id = null): void {
        verify_csrf();
        $s = db_row("SELECT * FROM village_profiles WHERE id = ?", [(int) $id]);
        if (!$s) redirect('admin/profil-desa');
        $data = [
            'title' => mb_substr(trim($_POST['title'] ?? $s['title']), 0, 200),
            'content' => Security::cleanHtml($_POST['content'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? $s['sort_order']),
            'updated_by' => auth_user()['id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if (!empty($_FILES['image']['name'])) {
            $res = Upload::image($_FILES['image'], 'profile');
            if (is_array($res)) { flash('danger', $res['error']); redirect('admin/profil-desa/edit/' . $id); }
            if ($s['image']) Upload::delete($s['image']);
            $data['image'] = $res;
        }
        db_update('village_profiles', $data, 'id = ?', [(int) $id]);
        log_activity('edit', 'profil_desa', 'Update profil desa: ' . $s['section_key']);
        flash('success', 'Konten profil desa diperbarui.');
        redirect('admin/profil-desa');
    }
}
