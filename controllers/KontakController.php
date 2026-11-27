<?php

class KontakController extends Controller {

    public array $actions = ['kirim'];

    public function index(): void {
        $this->view('kontak', [
            'pageTitle' => 'Kontak Desa - ' . setting('village_name'),
            'captcha' => Security::captcha('kontak'),
        ]);
    }

    public function kirim(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('kontak');
        verify_csrf();
        $fail = static function (string $message): void {
            remember_old_input($_POST);
            flash('danger', $message);
            redirect('kontak');
        };
        if (!Security::rateLimitPersistent('form:kontak', 3, 600)) {
            $fail('Terlalu banyak pesan dikirim. Coba lagi nanti.');
        }
        if (!Security::verifyCaptcha($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? '', 'kontak')) {
            $fail('Jawaban captcha salah atau sudah kedaluwarsa.');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $rawPhone = trim((string) ($_POST['phone'] ?? ''));
        $phone = normalize_phone_id($rawPhone);
        if (mb_strlen($name) < 2 || mb_strlen($message) < 10) {
            $fail('Nama dan pesan minimal 10 karakter wajib diisi.');
        }
        if (!valid_email_or_empty($email)) $fail('Alamat email tidak valid.');
        if ($rawPhone !== '' && $phone === '') $fail('Nomor HP tidak valid.');
        db_insert('contact_messages', [
            'name' => mb_substr($name, 0, 150),
            'email' => $email !== '' ? mb_substr($email, 0, 150) : null,
            'phone' => $phone !== '' ? $phone : null,
            'subject' => mb_substr(trim($_POST['subject'] ?? ''), 0, 200),
            'message' => mb_substr($message, 0, 3000),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Fonnte::notify(null, '', "📩 *Pesan Kontak Baru*\nDari: " . $name . "\nSubjek: " . (trim($_POST['subject'] ?? '') ?: '-') . "\nPesan: " . mb_substr($message, 0, 300) . "\n\nCek di panel admin → Pesan Masuk.", 'kontak');
        clear_old_input();
        flash('success', 'Pesan Anda berhasil dikirim. Terima kasih!');
        redirect('kontak');
    }
}
