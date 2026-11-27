<?php

class LayananController extends Controller {

    public array $actions = ['kirim', 'cek'];

    public function index(): void {
        $services = db_all("SELECT * FROM services WHERE deleted_at IS NULL AND is_active=1 ORDER BY name ASC");
        $this->view('layanan/index', ['pageTitle' => 'Layanan Desa - ' . setting('village_name'), 'services' => $services]);
    }

    public function detail(string $slug): void {
        $service = db_row("SELECT * FROM services WHERE slug = ? AND deleted_at IS NULL AND is_active=1", [$slug]);
        if (!$service) $this->notFound();
        $this->view('layanan/detail', [
            'pageTitle' => $service['name'] . ' - ' . setting('village_name'),
            'service' => $service,
            'captcha' => Security::captcha('layanan'),
        ]);
    }

    public function kirim(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('layanan');
        verify_csrf();
        $service = db_row("SELECT * FROM services WHERE id = ? AND deleted_at IS NULL AND is_active=1", [(int) ($_POST['service_id'] ?? 0)]);
        if (!$service) redirect('layanan');
        $back = 'layanan/' . $service['slug'];
        $fail = static function (string $message) use ($back): void {
            remember_old_input($_POST);
            flash('danger', $message);
            redirect($back);
        };
        if (!Security::rateLimitPersistent('form:layanan', 5, 600)) {
            $fail('Terlalu banyak permohonan. Silakan coba lagi nanti.');
        }
        if (!Security::verifyCaptcha($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? '', 'layanan')) {
            $fail('Jawaban captcha salah atau sudah kedaluwarsa.');
        }

        $nik = trim((string) ($_POST['nik'] ?? ''));
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $rawPhone = trim((string) ($_POST['no_hp'] ?? ''));
        $noHp = normalize_phone_id($rawPhone);
        if (!Security::validNik($nik)) $fail('NIK harus 16 digit angka.');
        if (mb_strlen($nama) < 2 || mb_strlen($nama) > 150) $fail('Nama wajib diisi dengan benar.');
        if ($rawPhone !== '' && $noHp === '') $fail('Nomor HP tidak valid. Gunakan nomor Indonesia yang aktif.');

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $res = Upload::attachment($_FILES['attachment'], 'requests');
            if (is_array($res)) $fail($res['error']);
            $attachment = $res;
        }
        $ticket = ticket_number('LYN');
        db_insert('service_requests', [
            'ticket' => $ticket, 'service_id' => $service['id'], 'nik' => $nik,
            'nama' => mb_substr($nama, 0, 150),
            'no_hp' => $noHp,
            'alamat' => mb_substr(trim($_POST['alamat'] ?? ''), 0, 255),
            'keperluan' => mb_substr(trim($_POST['keperluan'] ?? ''), 0, 1000),
            'attachment' => $attachment, 'status' => 'baru',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $desa = setting('village_name');
        Fonnte::notify(
            $noHp,
            "Halo {$nama}, permohonan *{$service['name']}* Anda telah kami terima. ✅\n\nNomor Tiket: *{$ticket}*\nStatus: Baru\n\nPantau status di: " . base_url() . "/layanan/cek?tiket={$ticket}\n\n- Pemerintah {$desa}",
            "🔔 *Permohonan Surat Baru*\n\nLayanan: {$service['name']}\nTiket: {$ticket}\nNama: {$nama}\nNIK: {$nik}\nNo. HP: " . ($noHp ?: '-') . "\n\nBuka admin: " . base_url() . "/admin/permohonan",
            'surat'
        );
        clear_old_input();
        flash('success', "Permohonan berhasil dikirim! Simpan nomor tiket Anda: <strong>$ticket</strong>. Gunakan untuk cek status.");
        redirect('layanan/cek?tiket=' . $ticket);
    }

    public function cek(): void {
        header('Cache-Control: no-store, private');
        header('Pragma: no-cache');
        $ticket = trim((string) ($_GET['tiket'] ?? ''));
        $request = null;
        if ($ticket !== '') {
            $request = db_row("SELECT r.*, s.name AS service_name FROM service_requests r
                JOIN services s ON s.id = r.service_id WHERE r.ticket = ? AND r.deleted_at IS NULL", [$ticket]);
        }
        $this->view('layanan/cek', [
            'pageTitle' => 'Cek Status Layanan - ' . setting('village_name'),
            'metaRobots' => 'noindex, nofollow',
            'ticket' => $ticket, 'request' => $request,
        ]);
    }
}
