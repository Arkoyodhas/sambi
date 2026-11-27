<?php

class RoleController extends Controller
{
    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete'];

    public function __construct()
    {
        Auth::requireSuperAdmin();
        AccessControl::ensureSchema();
    }

    public function index(): void
    {
        $roles = db_all(
            "SELECT r.id, r.name, r.slug, r.description, r.created_at, r.updated_at,
                    COUNT(DISTINCT u.id) AS user_count,
                    COUNT(DISTINCT rp.permission_id) AS permission_count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id
             LEFT JOIN role_permissions rp ON rp.role_id = r.id
             GROUP BY r.id, r.name, r.slug, r.description, r.created_at, r.updated_at
             ORDER BY CASE WHEN r.slug = 'super-admin' THEN 0 ELSE 1 END, r.name ASC"
        );

        $this->adminView('role_list', [
            'pageTitle' => 'Role & Hak Akses',
            'roles' => $roles,
        ]);
    }

    public function create(): void
    {
        $this->form(null);
    }

    public function edit($id = null): void
    {
        $role = db_row("SELECT * FROM roles WHERE id = ?", [(int) $id]);
        if (!$role) {
            flash('danger', 'Role tidak ditemukan.');
            redirect('admin/roles');
        }
        $this->form($role);
    }

    private function form(?array $role): void
    {
        $permissions = AccessControl::permissions();
        $selected = [];
        if ($role) {
            $selected = array_map(
                'intval',
                array_column(
                    db_all("SELECT permission_id FROM role_permissions WHERE role_id = ?", [(int) $role['id']]),
                    'permission_id'
                )
            );
        }

        $this->adminView('role_form', [
            'pageTitle' => ($role ? 'Edit' : 'Tambah') . ' Role',
            'role' => $role,
            'permissionGroups' => AccessControl::groupedPermissions($permissions),
            'selectedPermissions' => $selected,
            'isProtected' => ($role['slug'] ?? '') === 'super-admin',
        ]);
    }

    public function store(): void
    {
        $this->save(null);
    }

    public function update($id = null): void
    {
        $this->save((int) $id);
    }

    private function save(?int $id): void
    {
        verify_csrf();

        $existing = $id ? db_row("SELECT * FROM roles WHERE id = ?", [$id]) : null;
        if ($id && !$existing) {
            flash('danger', 'Role tidak ditemukan.');
            redirect('admin/roles');
        }
        if (($existing['slug'] ?? '') === 'super-admin') {
            flash('danger', 'Role Super Admin adalah role inti dan tidak dapat diubah.');
            redirect('admin/roles');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = slugify($slugInput !== '' ? $slugInput : $name);
        $description = trim((string) ($_POST['description'] ?? ''));

        $errors = [];
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors[] = 'Nama role harus 3–100 karakter.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || mb_strlen($slug) > 100) {
            $errors[] = 'Slug role tidak valid.';
        }
        if ($slug === 'super-admin') {
            $errors[] = 'Slug super-admin dicadangkan untuk role inti.';
        }
        if (mb_strlen($description) > 255) {
            $errors[] = 'Deskripsi maksimal 255 karakter.';
        }
        if (db_val("SELECT id FROM roles WHERE slug = ? AND id != ?", [$slug, $id ?? 0])) {
            $errors[] = 'Slug role sudah digunakan.';
        }

        $permissions = AccessControl::permissions();
        $permissionIds = AccessControl::normalizePermissionIds(
            is_array($_POST['permission_ids'] ?? null) ? $_POST['permission_ids'] : [],
            $permissions
        );

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_role_form'] = [
                'name' => $name,
                'slug' => $slugInput,
                'description' => $description,
                'permission_ids' => $permissionIds,
            ];
            redirect('admin/roles/' . ($id ? 'edit/' . $id : 'create'));
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            $now = date('Y-m-d H:i:s');

            if ($id === null) {
                $id = db_insert('roles', [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description !== '' ? $description : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                db_update('roles', [
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description !== '' ? $description : null,
                    'updated_at' => $now,
                ], 'id = ?', [$id]);
            }

            db_exec("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            foreach ($permissionIds as $permissionId) {
                db_exec(
                    "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)",
                    [$id, $permissionId]
                );
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Role gagal disimpan. Tidak ada perubahan yang diterapkan.');
            redirect('admin/roles/' . ($existing ? 'edit/' . $existing['id'] : 'create'));
        }

        unset($_SESSION['old_role_form']);
        log_activity($existing ? 'edit' : 'tambah', 'role', ($existing ? 'Edit' : 'Tambah') . " role {$name}");
        flash('success', 'Role dan hak akses berhasil disimpan.');
        redirect('admin/roles');
    }

    public function delete($id = null): void
    {
        verify_csrf();
        $id = (int) $id;
        $role = db_row("SELECT * FROM roles WHERE id = ?", [$id]);
        if (!$role) {
            flash('danger', 'Role tidak ditemukan.');
            redirect('admin/roles');
        }
        if (($role['slug'] ?? '') === 'super-admin') {
            flash('danger', 'Role Super Admin tidak dapat dihapus.');
            redirect('admin/roles');
        }

        $userCount = (int) db_val("SELECT COUNT(*) FROM users WHERE role_id = ?", [$id]);
        if ($userCount > 0) {
            flash('danger', "Role masih digunakan oleh {$userCount} user. Pindahkan role user terlebih dahulu.");
            redirect('admin/roles');
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_exec("DELETE FROM role_permissions WHERE role_id = ?", [$id]);
            db_exec("DELETE FROM user_roles WHERE role_id = ?", [$id]);
            db_exec("DELETE FROM roles WHERE id = ?", [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'Role gagal dihapus karena masih terhubung dengan data lain.');
            redirect('admin/roles');
        }

        log_activity('hapus', 'role', 'Hapus role ' . $role['name']);
        flash('success', 'Role berhasil dihapus.');
        redirect('admin/roles');
    }
}
