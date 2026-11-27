<?php

class UserController extends Controller
{
    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'face_store', 'face_delete', 'face_toggle'];

    public function __construct()
    {
        Auth::requireLogin();
    }

    private function requireUserManagement(): void
    {
        Auth::requireSuperAdmin();
        AccessControl::ensureSchema();
    }

    private function canManageFaceForUser(int $userId): bool
    {
        $current = Auth::user();
        return Auth::isSuperAdmin() || ((int) ($current['id'] ?? 0) === $userId);
    }

    private function denyFaceAccess(): void
    {
        http_response_code(403);
        $this->json(['ok' => false, 'error' => 'Anda hanya dapat mengelola dataset wajah milik akun sendiri.']);
    }

    public function index(): void
    {
        $this->requireUserManagement();
        $pg = paginate(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    (SELECT COUNT(*) FROM user_permissions up WHERE up.user_id = u.id) AS override_count
             FROM users u
             JOIN roles r ON r.id = u.role_id
             ORDER BY CASE WHEN r.slug = 'super-admin' THEN 0 ELSE 1 END, u.id ASC",
            [],
            20
        );
        $this->adminView('user_list', ['pageTitle' => 'User Admin', 'pg' => $pg]);
    }

    public function create(): void
    {
        $this->requireUserManagement();
        $this->form(null);
    }

    public function edit($id = null): void
    {
        $id = (int) $id;
        if (!Auth::isSuperAdmin()) {
            $current = Auth::user();
            if ((int) ($current['id'] ?? 0) === $id) {
                redirect('admin/akun#faceDatasetCard');
            }
            Auth::requireSuperAdmin();
        }

        $this->requireUserManagement();
        $user = db_row("SELECT * FROM users WHERE id = ?", [$id]);
        if (!$user) {
            flash('danger', 'User tidak ditemukan.');
            redirect('admin/users');
        }
        $this->form($user);
    }

    private function form(?array $user): void
    {
        AccessControl::ensureSchema();
        $permissions = AccessControl::permissions();
        $userOverrides = [];
        if ($user) {
            foreach (db_all(
                "SELECT permission_id, effect FROM user_permissions WHERE user_id = ?",
                [(int) $user['id']]
            ) as $row) {
                $userOverrides[(int) $row['permission_id']] = (string) $row['effect'];
            }
        }

        $this->adminView('user_form', [
            'pageTitle' => ($user ? 'Edit' : 'Tambah') . ' User',
            'u' => $user,
            'roles' => db_all(
                "SELECT r.*, COUNT(rp.permission_id) AS permission_count
                 FROM roles r
                 LEFT JOIN role_permissions rp ON rp.role_id = r.id
                 GROUP BY r.id, r.name, r.slug, r.description, r.created_at, r.updated_at
                 ORDER BY CASE WHEN r.slug = 'super-admin' THEN 0 ELSE 1 END, r.name ASC"
            ),
            'permissions' => $permissions,
            'permissionGroups' => AccessControl::groupedPermissions($permissions),
            'rolePerms' => db_all("SELECT role_id, permission_id FROM role_permissions"),
            'userOverrides' => $userOverrides,
            'faceProfiles' => $user ? FaceAuth::profilesForUser((int) $user['id']) : [],
            'canEnrollFace' => $user && (int) $user['id'] === (int) (Auth::user()['id'] ?? 0),
        ]);
    }

    public function store(): void
    {
        $this->requireUserManagement();
        $this->save(null);
    }

    public function update($id = null): void
    {
        $this->requireUserManagement();
        $this->save((int) $id);
    }

    private function save(?int $id): void
    {
        verify_csrf();
        AccessControl::ensureSchema();

        $existing = $id ? db_row(
            "SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?",
            [$id]
        ) : null;
        if ($id && !$existing) {
            flash('danger', 'User tidak ditemukan.');
            redirect('admin/users');
        }

        $username = trim((string) ($_POST['username'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $name = trim((string) ($_POST['name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $role = db_row("SELECT id, name, slug FROM roles WHERE id = ?", [$roleId]);
        $password = (string) ($_POST['password'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $mustChangePassword = isset($_POST['must_change_password']) ? 1 : 0;

        $errors = [];
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            $errors[] = 'Nama harus 2–150 karakter.';
        }
        if (!preg_match('/^[a-zA-Z0-9_.]{3,60}$/', $username)) {
            $errors[] = 'Username harus 3–60 karakter dan hanya boleh berisi huruf, angka, titik, atau underscore.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $errors[] = 'Email tidak valid.';
        }
        if (mb_strlen($phone) > 30) {
            $errors[] = 'Nomor HP maksimal 30 karakter.';
        }
        if (!$role) {
            $errors[] = 'Role yang dipilih tidak tersedia.';
        }
        if (db_val("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id ?? 0])) {
            $errors[] = 'Username sudah digunakan.';
        }
        if (db_val("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $id ?? 0])) {
            $errors[] = 'Email sudah digunakan.';
        }
        if ($id === null && strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter.';
        }
        if ($id !== null && $password !== '' && strlen($password) < 8) {
            $errors[] = 'Password baru minimal 8 karakter.';
        }

        $newRoleSlug = (string) ($role['slug'] ?? '');
        $existingIsSuper = ($existing['role_slug'] ?? '') === 'super-admin';
        $newIsSuper = $newRoleSlug === 'super-admin';
        $currentUserId = (int) (Auth::user()['id'] ?? 0);

        if ($id !== null && $id === $currentUserId && (!$newIsSuper || !$isActive)) {
            $errors[] = 'Super Admin yang sedang login tidak dapat menurunkan role atau menonaktifkan akunnya sendiri.';
        }

        if ($existingIsSuper && (!$newIsSuper || !$isActive)) {
            $activeSuperAdmins = (int) db_val(
                "SELECT COUNT(*)
                 FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'super-admin' AND u.is_active = 1"
            );
            if ($activeSuperAdmins <= 1) {
                $errors[] = 'Minimal harus ada satu Super Admin aktif.';
            }
        }

        $permissions = AccessControl::permissions();
        $validPermissionIds = array_fill_keys(array_map('intval', array_column($permissions, 'id')), true);
        $effects = [];
        $submittedEffects = is_array($_POST['permission_effect'] ?? null) ? $_POST['permission_effect'] : [];
        if (!$newIsSuper) {
            foreach ($submittedEffects as $permissionId => $effect) {
                $permissionId = (int) $permissionId;
                if (isset($validPermissionIds[$permissionId]) && in_array($effect, ['allow', 'deny'], true)) {
                    $effects[$permissionId] = $effect;
                }
            }
        }

        if ($errors) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['old_user_form'] = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'role_id' => $roleId,
                'is_active' => $isActive,
                'must_change_password' => $mustChangePassword,
                'permission_effect' => $effects,
            ];
            redirect('admin/users/' . ($id ? 'edit/' . $id : 'create'));
        }

        $data = [
            'name' => mb_substr($name, 0, 150),
            'username' => $username,
            'email' => $email,
            'role_id' => $roleId,
            'phone' => $phone !== '' ? $phone : null,
            'is_active' => $isActive,
            'must_change_password' => $mustChangePassword,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($password !== '') {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $pdo = db();
        try {
            $pdo->beginTransaction();
            if ($id === null) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $id = db_insert('users', $data);
            } else {
                db_update('users', $data, 'id = ?', [$id]);
            }

            db_exec("DELETE FROM user_roles WHERE user_id = ?", [$id]);
            db_exec("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)", [$id, $roleId]);

            db_exec("DELETE FROM user_permissions WHERE user_id = ?", [$id]);
            foreach ($effects as $permissionId => $effect) {
                db_exec(
                    "INSERT INTO user_permissions (user_id, permission_id, effect, created_at, updated_at)
                     VALUES (?, ?, ?, NOW(), NOW())",
                    [$id, $permissionId, $effect]
                );
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'User gagal disimpan. Tidak ada perubahan yang diterapkan.');
            redirect('admin/users/' . ($existing ? 'edit/' . $existing['id'] : 'create'));
        }

        unset($_SESSION['old_user_form']);
        log_activity($existing ? 'edit' : 'tambah', 'user', ($existing ? 'Edit' : 'Tambah') . " user {$username}");
        flash('success', $existing ? 'User berhasil diperbarui.' : 'User berhasil ditambahkan.');
        redirect('admin/users');
    }

    public function face_store($id = null): void {
        verify_csrf();
        $userId = (int) $id;
        $currentUserId = (int) (auth_user()['id'] ?? 0);
        if ($userId <= 0 || $currentUserId !== $userId) {
            http_response_code(403);
            $this->json(['ok' => false, 'error' => 'Dataset wajah harus dipindai sendiri dari halaman Akun Saya pengguna tersebut.']);
        }

        $user = db_row("SELECT id, username FROM users WHERE id = ? AND is_active = 1", [$userId]);
        $descriptor = FaceAuth::normalizeDescriptor($_POST['descriptor'] ?? null);
        $templates = FaceAuth::normalizeTemplates($_POST['templates'] ?? null, 7);
        $sampleCount = max(1, min(20, (int) ($_POST['sample_count'] ?? count($templates))));
        if (!$user || !$descriptor || count($templates) < FaceAuth::MIN_ENROLLMENT_SAMPLES || $sampleCount < FaceAuth::MIN_ENROLLMENT_SAMPLES) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Dataset wajah belum lengkap. Ambil minimal lima sampel sesuai instruksi.']);
        }

        $enrollment = FaceAuth::validateEnrollment($userId, $descriptor);
        if (!$enrollment['ok']) {
            $reason = $enrollment['reason'] ?? 'invalid';
            $message = match ($reason) {
                'other_user_conflict' => 'Wajah ini terdeteksi sudah terhubung ke akun lain. Dataset tidak disimpan.',
                'own_duplicate' => 'Dataset wajah yang sama sudah tersimpan pada akun Anda.',
                'limit' => 'Batas dataset wajah tercapai. Hapus dataset lama sebelum menambahkan yang baru.',
                default => 'Dataset wajah tidak dapat diverifikasi. Silakan ulangi pemindaian.',
            };
            http_response_code($reason === 'other_user_conflict' ? 409 : 422);
            $this->json(['ok' => false, 'error' => $message]);
        }

        $label = trim((string) ($_POST['label'] ?? ''));
        FaceAuth::add($userId, $label, $descriptor, $currentUserId, $templates, $sampleCount);
        log_activity('tambah', 'user', 'Tambah dataset wajah terenkripsi sendiri untuk ' . $user['username']);
        $this->json(['ok' => true, 'message' => 'Dataset wajah berhasil ditambahkan.']);
    }

    public function face_delete($profileId = null): void {
        verify_csrf();
        FaceAuth::ensureSchema();
        $profile = db_row("SELECT p.id, p.user_id, u.username FROM admin_face_profiles p JOIN users u ON u.id=p.user_id WHERE p.id = ?", [(int) $profileId]);
        if (!$profile) {
            http_response_code(404);
            $this->json(['ok' => false, 'error' => 'Dataset tidak ditemukan.']);
        }
        if (!$this->canManageFaceForUser((int) $profile['user_id'])) {
            $this->denyFaceAccess();
        }

        db_exec("DELETE FROM admin_face_profiles WHERE id = ?", [(int) $profileId]);
        log_activity('hapus', 'user', 'Hapus dataset wajah untuk ' . $profile['username']);
        $this->json(['ok' => true, 'message' => 'Dataset wajah dihapus.']);
    }

    public function face_toggle($profileId = null): void {
        verify_csrf();
        FaceAuth::ensureSchema();
        $profile = db_row("SELECT id, user_id, is_active FROM admin_face_profiles WHERE id = ?", [(int) $profileId]);
        if (!$profile) {
            http_response_code(404);
            $this->json(['ok' => false, 'error' => 'Dataset tidak ditemukan.']);
        }
        if (!$this->canManageFaceForUser((int) $profile['user_id'])) {
            $this->denyFaceAccess();
        }

        $active = (int) !$profile['is_active'];
        db_update('admin_face_profiles', ['is_active' => $active], 'id = ?', [(int) $profileId]);
        $this->json(['ok' => true, 'active' => $active]);
    }

    public function delete($id = null): void
    {
        $this->requireUserManagement();
        verify_csrf();
        $id = (int) $id;
        $currentUserId = (int) (Auth::user()['id'] ?? 0);

        if ($id === $currentUserId) {
            flash('danger', 'Akun yang sedang digunakan tidak dapat dihapus.');
            redirect('admin/users');
        }

        $user = db_row(
            "SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?",
            [$id]
        );
        if (!$user) {
            flash('danger', 'User tidak ditemukan.');
            redirect('admin/users');
        }

        if ($user['role_slug'] === 'super-admin' && (int) $user['is_active'] === 1) {
            $activeSuperAdmins = (int) db_val(
                "SELECT COUNT(*)
                 FROM users u JOIN roles r ON r.id = u.role_id
                 WHERE r.slug = 'super-admin' AND u.is_active = 1"
            );
            if ($activeSuperAdmins <= 1) {
                flash('danger', 'Minimal harus ada satu Super Admin aktif.');
                redirect('admin/users');
            }
        }

        FaceAuth::ensureSchema();
        $pdo = db();
        try {
            $pdo->beginTransaction();
            db_exec("DELETE FROM admin_face_profiles WHERE user_id = ?", [$id]);
            db_exec("DELETE FROM user_permissions WHERE user_id = ?", [$id]);
            db_exec("DELETE FROM user_roles WHERE user_id = ?", [$id]);
            db_exec("DELETE FROM users WHERE id = ?", [$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            flash('danger', 'User gagal dihapus karena masih terhubung dengan data lain.');
            redirect('admin/users');
        }

        log_activity('hapus', 'user', 'Hapus user ' . $user['username']);
        flash('success', 'User berhasil dihapus.');
        redirect('admin/users');
    }
}
