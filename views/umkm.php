<?php $phTitle = 'UMKM & Potensi Desa'; $phDesc = 'Dukung produk lokal dan kenali potensi unggulan desa kami.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="d-flex flex-wrap gap-2 mb-4" data-aos="fade-up">
      <a href="<?= url('umkm') ?>" class="btn btn-sm <?= $kategori === '' ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
      <?php foreach ($kategoriList as $k): ?>
      <a href="<?= url('umkm?kategori=' . urlencode($k['category'])) ?>" class="btn btn-sm <?= $kategori === $k['category'] ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e($k['category']) ?></a>
      <?php endforeach; ?>
      <a href="<?= url('potensi') ?>" class="btn btn-sm btn-accent ms-auto"><i class="bi bi-tree me-1"></i>Potensi Desa</a>
    </div>
    <div class="row g-4">
      <?php foreach ($pg['data'] as $u): ?>
      <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up">
        <div class="card-3d h-100 d-flex flex-column">
          <?php if ($u['photo']): ?><img src="<?= e(thumbnail_url($u['photo'], 640, 420)) ?>" class="news-thumb" alt="<?= e(title_case_id($u['name'])) ?>" loading="lazy">
          <?php else: ?><div class="news-thumb-fallback"><i class="bi bi-shop"></i></div><?php endif; ?>
          <div class="card-body d-flex flex-column">
            <span class="badge-cat mb-2 align-self-start"><?= e($u['category'] ?: 'Umum') ?></span>
            <h6 class="mb-1" data-testid="umkm-page-title"><?= e(title_case_id($u['name'])) ?></h6>
            <small class="text-muted d-block"><i class="bi bi-person me-1"></i><?= e($u['owner']) ?></small>
            <?php if ($u['location']): ?><small class="text-muted d-block"><i class="bi bi-geo-alt me-1"></i><?= e($u['location']) ?></small><?php endif; ?>
            <p class="small text-muted mt-2 flex-grow-1"><?= e(excerpt($u['description'], 70)) ?></p>
            <?php if ($u['price']): ?><strong class="text-primary-c mb-2"><?= e($u['price']) ?></strong><?php endif; ?>
            <div class="d-flex gap-2">
              <?php if ($u['whatsapp']): ?>
              <a class="btn btn-sm w-100 text-white" style="background:#25d366" href="https://wa.me/<?= e(preg_replace('/\D/', '', $u['whatsapp'])) ?>?text=<?= urlencode('Halo, saya tertarik dengan produk ' . $u['name']) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp me-1"></i>Pesan</a>
              <?php endif; ?>
              <?php if ($u['maps_url']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($u['maps_url']) ?>" target="_blank" rel="noopener"><i class="bi bi-map"></i></a><?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Belum ada produk UMKM untuk kategori ini.</p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination_links($pg) ?></div>

    <?php if ($potentials): ?>
    <div class="section-head mt-5" data-aos="fade-up"><span class="eyebrow">Unggulan Desa</span><h2 class="fs-4">Potensi Desa</h2></div>
    <div class="row g-4">
      <?php foreach ($potentials as $p): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up">
        <div class="card-3d h-100">
          <?php if ($p['photo']): ?><img src="<?= e(thumbnail_url($p['photo'], 640, 420)) ?>" class="news-thumb" alt="<?= e($p['title']) ?>" loading="lazy"><?php endif; ?>
          <div class="card-body">
            <span class="badge-cat text-capitalize mb-2 d-inline-block"><?= e($p['category']) ?></span>
            <h6><?= e($p['title']) ?></h6>
            <p class="text-muted small mb-0"><?= e(excerpt($p['description'], 120)) ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
