<?php $phTitle = $item['title']; $phCrumb = 'Pengumuman'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container" style="max-width:820px">
    <div class="glass-panel p-4 p-md-5" data-aos="fade-up">
      <div class="d-flex gap-2 align-items-center mb-3">
        <?php if ($item['is_important']): ?><span class="badge-important">PENTING</span><?php endif; ?>
        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?= tgl_id($item['start_date']) ?><?= $item['end_date'] ? ' s.d. ' . tgl_id($item['end_date']) : '' ?></small>
      </div>
      <div class="content-html"><?= $item['content'] ?></div>
      <?php if ($item['attachment']): ?>
      <a href="<?= e(upload_url($item['attachment'])) ?>" class="btn btn-primary mt-3" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i> Unduh Lampiran</a>
      <?php endif; ?>
      <?php $shareUrl = url('pengumuman/' . $item['id']); ?>
      <div class="d-flex align-items-center gap-3 flex-wrap mt-4 pt-3 border-top">
        <strong class="small">Bagikan:</strong>
        <div class="share-btns d-flex gap-2">
          <a href="https://wa.me/?text=<?= urlencode($item['title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" style="background:#25d366" aria-label="WhatsApp" data-testid="announce-share-wa"><i class="bi bi-whatsapp"></i></a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" style="background:#1877f2" aria-label="Facebook" data-testid="announce-share-fb"><i class="bi bi-facebook"></i></a>
          <button type="button" class="btn-copy-link" data-url="<?= e($shareUrl) ?>" style="background:#5b6470" aria-label="Salin tautan" title="Salin tautan" data-testid="announce-share-copy"><i class="bi bi-link-45deg"></i></button>
          <button type="button" class="btn-native-share d-none" data-url="<?= e($shareUrl) ?>" data-title="<?= e($item['title']) ?>" style="background:var(--c-primary)" aria-label="Bagikan" data-testid="announce-share-native"><i class="bi bi-share"></i></button>
        </div>
      </div>
    </div>
    <a href="<?= url('pengumuman') ?>" class="btn btn-outline-primary mt-4"><i class="bi bi-arrow-left me-1"></i> Semua Pengumuman</a>
  </div>
</section>
