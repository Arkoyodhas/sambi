<?php

class PengaduanController extends Controller {

    public array $actions = ['kirim', 'cek'];

    const CATEGORIES = ['Infrastruktur', 'Pelayanan', 'Kebersihan', 'Keamanan', 'Sosial', 'Administrasi', 'Lainnya'];

    public function index(): void {
        $this->view('pengaduan', [
            'pageTitle' => 'Pengaduan & Aspirasi - ' . setting('village_name'),
            'categories' => self::CATEGORIES,
            'captcha' => Security::captcha('pengaduan'),
            'recent' => db_all("SELECT ticket, category, status, created_at, is_anonymous, nama,
                LEFT(content, 120) AS preview FROM complaints WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 6"),
        ]);
    }

    public function kirim(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('pengaduan');
        verify_csrf();
        $fail = static function (string $message): void {
            remember_old_input($_POST);
            flash('danger', $message);
            redirect('pengaduan');
        };
        if (!Security::rateLimitPersistent('form:pengaduan', 3, 600)) {
            $fail('Terlalu banyak pengaduan dikirim. Coba lagi nanti.');
        }
        if (!Security::verifyCaptcha($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? '', 'pengaduan')) {
            $fail('Jawaban captcha salah atau sudah kedaluwarsa.');
        }
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $rawContact = trim((string) ($_POST['kontak'] ?? ''));
        $contact = $rawContact;
        if (str_contains($rawContact, '@')) {
            $fail('Gunakan nomor HP/WhatsApp sebagai kontak (email tidak lagi digunakan).');
        } elseif ($rawContact !== '') {
            $contact = normalize_phone_id($rawContact);
            if ($contact === '') $fail('Nomor HP tidak valid.');
        }
        if (mb_strlen($nama) < 2 || mb_strlen($nama) > 150 || mb_strlen($content) < 10) {
            $fail('Nama dan isi pengaduan minimal 10 karakter wajib diisi dengan benar.');
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $res = Upload::attachment($_FILES['attachment'], 'complaints');
            if (is_array($res)) $fail($res['error']);
            $attachment = $res;
        }
        $ticket = ticket_number('ADU');
        db_insert('complaints', [
            'ticket' => $ticket, 'nama' => mb_substr($nama, 0, 150),
            'kontak' => $contact !== '' ? mb_substr($contact, 0, 150) : null,
            'category' => in_array($_POST['category'] ?? '', self::CATEGORIES, true) ? $_POST['category'] : 'Lainnya',
            'content' => mb_substr($content, 0, 3000),
            'attachment' => $attachment,
            'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
            'status' => 'diterima', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        clear_old_input();
        flash('success', "Pengaduan berhasil dikirim! Simpan nomor tiket: <strong>$ticket</strong> untuk memantau tindak lanjut.");
        Fonnte::notify(null, '', "📢 *Pengaduan Warga Baru*\nTiket: " . $ticket . "\nDari: " . ($_POST['is_anonymous'] ?? false ? 'Anonim' : $nama) . "\nKategori: " . (in_array($_POST['category'] ?? '', self::CATEGORIES, true) ? $_POST['category'] : 'Lainnya') . "\nIsi: " . mb_substr($content, 0, 300) . "\n\nCek di panel admin → Pengaduan.", 'aduan');
        redirect('pengaduan/cek?tiket=' . $ticket);
    }

    public function cek(): void {
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        $ticket = trim((string) ($_GET['tiket'] ?? ''));
        $complaint = null; $responses = [];
        if ($ticket !== '') {
            $complaint = db_row("SELECT * FROM complaints WHERE ticket = ? AND deleted_at IS NULL", [$ticket]);
            if ($complaint) {
                $responses = db_all("SELECT cr.*, u.name AS admin_name FROM complaint_responses cr
                    LEFT JOIN users u ON u.id = cr.user_id WHERE cr.complaint_id = ? ORDER BY cr.created_at ASC", [$complaint['id']]);
            }
        }
        $this->view('pengaduan_cek', [
            'pageTitle' => 'Cek Status Pengaduan - ' . setting('village_name'),
            'metaRobots' => 'noindex, nofollow',
            'ticket' => $ticket, 'complaint' => $complaint, 'responses' => $responses,
        ]);
    }
}
