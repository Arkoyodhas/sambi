<?php $phTitle = 'Pengumuman Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container" style="max-width:880px">
    <div class="d-flex flex-column gap-3">
      <?php foreach ($pg['data'] as $a): ?>
      <div class="announce-item <?= $a['is_important'] ? 'important' : '' ?>" data-aos="fade-up">
        <div class="d-flex justify-content-between align-items-start gap-2">
          <h5 class="mb-1"><a class="text-decoration-none" style="color:var(--text)" href="<?= url('pengumuman/' . $a['id']) ?>"><?= e($a['title']) ?></a></h5>
          <?php if ($a['is_important']): ?><span class="badge-important">PENTING</span><?php endif; ?>
        </div>
        <p class="text-muted small mb-2"><?= e(excerpt($a['content'], 180)) ?></p>
        <div class="d-flex gap-3 align-items-center small">
          <span class="text-muted"><i class="bi bi-calendar3 me-1"></i>Berlaku <?= tgl_id($a['start_date']) ?><?= $a['end_date'] ? ' s.d. ' . tgl_id($a['end_date']) : '' ?></span>
          <a href="<?= url('pengumuman/' . $a['id']) ?>">Baca selengkapnya →</a>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Belum ada pengumuman aktif saat ini.</p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination_links($pg) ?></div>
  </div>
</section>
