<?php
/* Ilustrasi alur bergerak (ringan, CSS/SVG). Butuh: $flowType = surat|aduan|cek */
$flowType = $flowType ?? 'surat';
$flows = [
    'surat' => ['Cara mengajukan surat', [
        ['bi-ui-checks-grid', 'Pilih layanan', 'Tentukan jenis surat'],
        ['bi-pencil-square', 'Isi formulir', 'NIK, nama, keperluan'],
        ['bi-ticket-perforated', 'Simpan tiket', 'Nomor tiket via WhatsApp'],
        ['bi-patch-check', 'Surat selesai', 'Ambil di kantor desa'],
    ]],
    'aduan' => ['Bagaimana aduan diproses', [
        ['bi-chat-square-text', 'Tulis aduan', 'Ceritakan & lampirkan foto'],
        ['bi-ticket-perforated', 'Dapat tiket', 'Simpan untuk melacak'],
        ['bi-people', 'Ditindaklanjuti', 'Perangkat desa memeriksa'],
        ['bi-check2-circle', 'Selesai', 'Status diperbarui'],
    ]],
    'cek' => ['Cara cek status', [
        ['bi-ticket-perforated', 'Siapkan tiket', 'Dari pesan saat mengajukan'],
        ['bi-search', 'Masukkan tiket', 'Ketik pada kolom di bawah'],
        ['bi-hourglass-split', 'Lihat status', 'Baru · Diproses · Selesai'],
    ]],
];
[$flowTitle, $flowSteps] = $flows[$flowType] ?? $flows['surat'];
$n = count($flowSteps);
?>
<div class="tl-howto" style="--steps:<?= $n ?>" data-testid="flow-illustration-<?= e($flowType) ?>" aria-label="<?= e($flowTitle) ?>">
  <div class="tl-howto-head">
    <span class="tl-eyebrow"><?= e($flowTitle) ?></span>
  </div>
  <div class="tl-howto-track">
    <span class="tl-howto-rail" aria-hidden="true"><i></i></span>
    <span class="tl-howto-token" aria-hidden="true"><i class="bi <?= $flowType === 'aduan' ? 'bi-chat-dots-fill' : 'bi-envelope-paper-fill' ?>"></i></span>
    <?php foreach ($flowSteps as $i => [$ic, $t, $d]): ?>
    <div class="tl-howto-step" style="--i:<?= $i ?>">
      <span class="tl-howto-ic"><i class="bi <?= e($ic) ?>"></i></span>
      <strong><?= e($t) ?></strong>
      <small><?= e($d) ?></small>
    </div>
    <?php endforeach; ?>
  </div>
</div>
