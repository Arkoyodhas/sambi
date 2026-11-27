<?php $phTitle = 'Berita Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-8">
        <form class="d-flex gap-2 mb-4" method="get" action="<?= url('berita') ?>">
          <input type="text" name="q" class="form-control" placeholder="Cari berita..." value="<?= e($q) ?>" data-testid="news-search-input">
          <button class="btn btn-primary px-4" data-testid="news-search-btn"><i class="bi bi-search"></i></button>
        </form>
        <?php if (!$pg['data']): ?><p class="text-muted">Tidak ada berita ditemukan.</p><?php endif; ?>
        <div class="row g-4">
          <?php foreach ($pg['data'] as $n): ?>
          <div class="col-md-6" data-aos="fade-up">
            <article class="card-3d news-card h-100">
              <a href="<?= url('berita/' . $n['slug']) ?>">
                <?php if ($n['thumbnail']): ?><img src="<?= e(thumbnail_url($n['thumbnail'], 720, 440)) ?>" alt="<?= e($n['title']) ?>" loading="lazy">
                <?php else: ?><div class="news-thumb-fallback"><i class="bi bi-newspaper"></i></div><?php endif; ?>
              </a>
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                  <span class="badge-cat"><?= e($n['category'] ?: 'Umum') ?></span>
                  <small class="text-muted"><?= tgl_id($n['published_at']) ?> · <i class="bi bi-eye"></i> <?= (int) $n['views'] ?></small>
                </div>
                <h5 class="news-title fs-6 fw-bold"><a href="<?= url('berita/' . $n['slug']) ?>"><?= e($n['title']) ?></a></h5>
                <p class="text-muted small mb-0"><?= e(excerpt($n['excerpt'] ?: $n['content'], 110)) ?></p>
              </div>
            </article>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-4"><?= pagination_links($pg) ?></div>
      </div>
      <div class="col-lg-4">
        <div class="sticky-sidebar d-flex flex-column gap-4">
          <div class="glass-panel p-4">
            <h6 class="mb-3">Kategori</h6>
            <div class="d-flex flex-column gap-2">
              <?php foreach ($categories as $c): ?>
              <a class="d-flex justify-content-between text-decoration-none <?= $cat === $c['slug'] ? 'fw-bold text-primary-c' : '' ?>" style="color:var(--text)" href="<?= url('berita?kategori=' . $c['slug']) ?>">
                <span><?= e($c['name']) ?></span><span class="badge-cat"><?= (int) $c['total'] ?></span>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="glass-panel p-4">
            <h6 class="mb-3"><i class="bi bi-fire text-danger me-1"></i>Berita Populer</h6>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($popular as $p): ?>
              <a class="d-flex gap-3 text-decoration-none align-items-center" href="<?= url('berita/' . $p['slug']) ?>">
                <?php if ($p['thumbnail']): ?><img src="<?= e(thumbnail_url($p['thumbnail'], 256, 168)) ?>" style="width:64px;height:52px;object-fit:cover;border-radius:10px" alt="" loading="lazy">
                <?php else: ?><div class="avatar-fallback" style="width:64px;height:52px;border-radius:10px;font-size:1rem"><i class="bi bi-newspaper"></i></div><?php endif; ?>
                <div>
                  <small class="fw-semibold d-block lh-sm" style="color:var(--text)"><?= e(excerpt($p['title'], 60)) ?></small>
                  <small class="text-muted"><i class="bi bi-eye me-1"></i><?= (int) $p['views'] ?> dibaca</small>
                </div>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
