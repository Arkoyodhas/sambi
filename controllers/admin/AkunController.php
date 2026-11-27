<?php

class AkunController extends Controller {

    public array $actions = ['index', 'simpan', 'face_store', 'face_delete', 'face_toggle'];

    public function __construct() {
        Auth::requireLogin();
    }

    public function index(): void {
        $u = Auth::user();
        $this->adminView('akun', [
            'pageTitle' => 'Akun Saya',
            'u' => $u,
            'faceProfiles' => FaceAuth::profilesForUser((int) $u['id']),
        ]);
    }

    public function simpan(): void {
        $u = Auth::user();
        verify_csrf();
        $errors = [];
        $name = trim($_POST['name'] ?? '');
        if ($name === '') $errors[] = 'Nama wajib diisi.';
        $phone = normalize_phone_id((string) ($_POST['phone'] ?? ''));
        if ($phone === '' || strlen(preg_replace('/\D/', '', $phone)) < 9) $errors[] = 'Nomor WhatsApp wajib diisi dengan benar (contoh 0812xxxxxxx).';
        $data = ['name' => mb_substr($name, 0, 150), 'phone' => $phone !== '' ? mb_substr($phone, 0, 20) : null, 'updated_at' => date('Y-m-d H:i:s')];

        $passBaru = $_POST['password_baru'] ?? '';
        if ($passBaru !== '' || $u['must_change_password']) {
            if (!password_verify($_POST['password_lama'] ?? '', $u['password'])) $errors[] = 'Password lama salah.';
            if (strlen($passBaru) < 8) $errors[] = 'Password baru minimal 8 karakter.';
            if ($passBaru !== ($_POST['password_konfirmasi'] ?? '')) $errors[] = 'Konfirmasi password tidak cocok.';
            if (!$errors) {
                $data['password'] = password_hash($passBaru, PASSWORD_BCRYPT);
                $data['must_change_password'] = 0;
            }
        }
        if ($errors) { flash('danger', implode(' ', $errors)); redirect('admin/akun'); }
        db_update('users', $data, 'id = ?', [$u['id']]);
        log_activity('edit', 'akun', 'Update profil/password akun sendiri');
        flash('success', isset($data['password']) ? 'Password berhasil diganti.' : 'Profil diperbarui.');
        redirect(isset($data['password']) ? 'admin/dashboard' : 'admin/akun');
    }

    public function face_store(): void {
        verify_csrf();
        $user = Auth::user();
        $descriptor = FaceAuth::normalizeDescriptor($_POST['descriptor'] ?? null);
        $templates = FaceAuth::normalizeTemplates($_POST['templates'] ?? null, 7);
        $sampleCount = max(1, min(20, (int) ($_POST['sample_count'] ?? count($templates))));

        if (!$descriptor || count($templates) < FaceAuth::MIN_ENROLLMENT_SAMPLES || $sampleCount < FaceAuth::MIN_ENROLLMENT_SAMPLES) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Dataset wajah belum lengkap. Ikuti seluruh posisi wajah dan ambil minimal lima sampel.']);
        }

        $enrollment = FaceAuth::validateEnrollment((int) $user['id'], $descriptor);
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
        FaceAuth::add(
            (int) $user['id'],
            $label,
            $descriptor,
            (int) $user['id'],
            $templates,
            $sampleCount
        );
        log_activity('tambah', 'akun', 'Tambah dataset wajah terenkripsi akun sendiri dengan ' . $sampleCount . ' sampel');
        $this->json(['ok' => true, 'message' => 'Dataset wajah berhasil ditambahkan dan dienkripsi.']);
    }

    public function face_delete($profileId = null): void {
        verify_csrf();
        $u = Auth::user();
        FaceAuth::ensureSchema();
        $profile = db_row(
            "SELECT id FROM admin_face_profiles WHERE id = ? AND user_id = ?",
            [(int) $profileId, (int) $u['id']]
        );

        if (!$profile) {
            http_response_code(404);
            $this->json(['ok' => false, 'error' => 'Dataset tidak ditemukan atau bukan milik akun Anda.']);
        }

        db_exec("DELETE FROM admin_face_profiles WHERE id = ? AND user_id = ?", [(int) $profileId, (int) $u['id']]);
        log_activity('hapus', 'akun', 'Hapus dataset wajah akun sendiri');
        $this->json(['ok' => true, 'message' => 'Dataset wajah dihapus.']);
    }

    public function face_toggle($profileId = null): void {
        verify_csrf();
        $u = Auth::user();
        FaceAuth::ensureSchema();
        $profile = db_row(
            "SELECT id, is_active FROM admin_face_profiles WHERE id = ? AND user_id = ?",
            [(int) $profileId, (int) $u['id']]
        );

        if (!$profile) {
            http_response_code(404);
            $this->json(['ok' => false, 'error' => 'Dataset tidak ditemukan atau bukan milik akun Anda.']);
        }

        $active = (int) !$profile['is_active'];
        db_update('admin_face_profiles', ['is_active' => $active], 'id = ? AND user_id = ?', [(int) $profileId, (int) $u['id']]);
        log_activity('edit', 'akun', ($active ? 'Aktifkan' : 'Nonaktifkan') . ' dataset wajah akun sendiri');
        $this->json(['ok' => true, 'active' => $active]);
    }
}
