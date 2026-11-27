<?php
// Tombol Siaga (morphing circle) + panel kontak darurat.
if (setting('siaga_enabled', '1') !== '1') return;
$siagaContacts = emergency_contacts();
if (!$siagaContacts) return;
$siagaCats = emergency_categories();
$siagaLabel = setting('siaga_label', 'Siaga Darurat');
?>
<div class="siaga" data-siaga data-testid="siaga">
  <button type="button" class="siaga-btn" data-siaga-open aria-haspopup="dialog" aria-controls="siagaPanel" aria-expanded="false" data-testid="siaga-button">
    <span class="siaga-ring" aria-hidden="true"></span>
    <span class="siaga-ic" aria-hidden="true"><i class="bi bi-broadcast-pin"></i></span>
    <span class="siaga-txt"><?= e($siagaLabel) ?></span>
  </button>
  <div class="siaga-backdrop" data-siaga-close hidden></div>
  <div class="siaga-panel" id="siagaPanel" role="dialog" aria-modal="true" aria-labelledby="siagaTitle" hidden data-lenis-prevent data-testid="siaga-panel">
    <div class="siaga-head">
      <span class="siaga-head-ic" aria-hidden="true"><i class="bi bi-broadcast-pin"></i></span>
      <div>
        <h2 id="siagaTitle">Kontak Darurat</h2>
        <p>Tekan Telepon untuk langsung menghubungi.</p>
      </div>
      <button type="button" class="siaga-x" data-siaga-close aria-label="Tutup" data-testid="siaga-close"><i class="bi bi-x-lg"></i></button>
    </div>
    <ul class="siaga-list">
      <?php foreach ($siagaContacts as $i => $c):
        $tel = preg_replace('/[^0-9+]/', '', (string) $c['phone']);
        $wa = !empty($c['has_whatsapp']) ? normalize_phone_id((string) $c['phone']) : '';
        $col = preg_match('/^#[0-9a-f]{6}$/i', (string) $c['color']) ? $c['color'] : '#DC2626';
        $ic = array_key_exists((string) $c['icon'], emergency_icons()) ? $c['icon'] : 'bi-telephone-fill';
      ?>
      <li class="siaga-item" style="--sc:<?= e($col) ?>;--si:<?= (int) $i ?>" data-testid="siaga-item-<?= (int) $c['id'] ?>">
        <span class="siaga-item-ic" aria-hidden="true"><i class="bi <?= e($ic) ?>"></i></span>
        <span class="siaga-item-info">
          <strong><?= e($c['name']) ?></strong>
          <?php $catLabel = $siagaCats[$c['category']] ?? 'Lainnya'; ?>
          <small><?= stripos((string) $c['name'], $catLabel) === false ? e($catLabel) . ' · ' : '' ?><?= e($c['phone']) ?></small>
        </span>
        <span class="siaga-item-act">
          <a class="siaga-call" href="tel:<?= e($tel) ?>" aria-label="Telepon <?= e($c['name']) ?>" data-testid="siaga-call-<?= (int) $c['id'] ?>"><i class="bi bi-telephone-fill"></i><span>Telepon</span></a>
          <?php if ($wa !== ''): ?>
          <a class="siaga-wa" href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp <?= e($c['name']) ?>" data-testid="siaga-wa-<?= (int) $c['id'] ?>"><i class="bi bi-whatsapp"></i></a>
          <?php endif; ?>
        </span>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
