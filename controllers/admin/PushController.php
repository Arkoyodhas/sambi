<?php

class PushController extends Controller {

    public array $actions = ['index', 'kirim'];

    public function __construct() { Auth::requirePermission('kelola_tampilan'); }

    public function index(): void {
        $this->adminView('push_list', [
            'pageTitle' => 'Notifikasi Push',
            'subCount' => (int) db_val("SELECT COUNT(*) FROM push_subscriptions"),
            'history' => db_all("SELECT p.*, u.name AS user_name FROM push_notifications p LEFT JOIN users u ON u.id = p.created_by ORDER BY p.id DESC LIMIT 20"),
            'vapidReady' => WebPush::publicKey() !== '',
        ]);
    }

    public function kirim(): void {
        verify_csrf();
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $url = trim($_POST['url'] ?? '');
        if ($title === '' || $body === '') { flash('danger', 'Judul dan isi notifikasi wajib diisi.'); redirect('admin/push'); }
        if ($url !== '' && !str_starts_with($url, 'http')) $url = url(ltrim($url, '/'));

        $result = PushNotifier::send($title, $body, $url, Auth::user()['id']);
        $sent = (int) ($result['sent'] ?? 0);
        $fail = (int) ($result['fail'] ?? 0);
        log_activity('push', 'notifikasi', "Kirim push \"$title\": $sent terkirim, $fail gagal");
        if ($sent > 0) flash('success', "Notifikasi terkirim ke <b>$sent</b> perangkat." . ($fail ? " ($fail gagal/kedaluwarsa)" : ''));
        else flash('warning', $fail > 0 ? "Pengiriman gagal ke $fail perangkat. Coba lagi atau periksa koneksi server." : 'Belum ada perangkat yang berlangganan notifikasi.');
        redirect('admin/push');
    }
}
