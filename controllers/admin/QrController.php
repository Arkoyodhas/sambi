<?php

class QrController extends Controller {

    public array $actions = ['index'];

    public function __construct() { Auth::requirePermission('kelola_konten'); }

    public function index(): void {
        $text = trim((string) ($_GET['text'] ?? base_url()));
        $size = max(120, min(1024, (int) ($_GET['size'] ?? 320)));
        $color = ltrim((string) ($_GET['color'] ?? '0f6b4e'), '#');
        $bg = ltrim((string) ($_GET['bg'] ?? 'ffffff'), '#');
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($text) . '&size=' . $size . 'x' . $size . '&color=' . $color . '&bgcolor=' . $bg . '&margin=10&qzone=1&ecc=H&format=png';
        $this->adminView('qr_generator', [
            'pageTitle' => 'Generator QR Code',
            'text' => $text, 'size' => $size, 'color' => $color, 'bg' => $bg,
            'qrUrl' => $qrUrl,
            'baseUrl' => base_url(),
        ]);
    }
}
