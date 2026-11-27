<?php $phTitle = 'Potensi Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($pg['data'] as $p): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up">
        <div class="card-3d h-100">
          <?php if ($p['photo']): ?><img src="<?= e(thumbnail_url($p['photo'], 640, 420)) ?>" class="news-thumb" alt="<?= e($p['title']) ?>" loading="lazy">
          <?php else: ?><div class="news-thumb-fallback"><i class="bi <?= ['wisata' => 'bi-tree', 'pertanian' => 'bi-flower1', 'peternakan' => 'bi-egg-fried', 'perikanan' => 'bi-water', 'budaya' => 'bi-music-note-list', 'kerajinan' => 'bi-palette'][$p['category']] ?? 'bi-star' ?>"></i></div><?php endif; ?>
          <div class="card-body">
            <span class="badge-cat text-capitalize mb-2 d-inline-block"><?= e($p['category']) ?></span>
            <h5 class="fs-6 fw-bold"><?= e($p['title']) ?></h5>
            <div class="content-html small text-muted"><?= $p['description'] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Belum ada data potensi desa.</p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination_links($pg) ?></div>
  </div>
</section>
