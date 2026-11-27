<?php $phTitle = $album ? 'Album: ' . $album['name'] : 'Galeri Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php if (!$album): ?>
    <?php if ($albums): ?>
    <div class="section-head" data-aos="fade-up"><span class="eyebrow">Album</span><h2 class="fs-4">Album Kegiatan</h2></div>
    <div class="row g-3 mb-5">
      <?php foreach ($albums as $al): ?>
      <div class="col-6 col-md-3" data-aos="fade-up">
        <a href="<?= url('galeri/' . $al['slug']) ?>" class="gallery-item d-block">
          <?php if ($al['cover']): ?><img src="<?= e(thumbnail_url($al['cover'], 720, 440)) ?>" alt="<?= e($al['name']) ?>" loading="lazy">
          <?php else: ?><div class="avatar-fallback w-100 h-100" style="font-size:2rem"><i class="bi bi-collection"></i></div><?php endif; ?>
          <span class="overlay" style="opacity:1"><?= e($al['name']) ?> (<?= (int) $al['total'] ?>)</span>
        </a>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <form class="row g-2 mb-4" method="get" action="<?= url('galeri') ?>">
      <div class="col-6 col-md-3">
        <select class="form-select" name="kategori">
          <option value="">Semua Kategori</option>
          <?php foreach ($kategoriList as $k): ?><option value="<?= e($k['category']) ?>" <?= $kategori === $k['category'] ? 'selected' : '' ?>><?= e($k['category']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select" name="tahun">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunList as $t): ?><option value="<?= (int) $t['year'] ?>" <?= $tahun === (int) $t['year'] ? 'selected' : '' ?>><?= (int) $t['year'] ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <?php else: ?>
    <a href="<?= url('galeri') ?>" class="btn btn-outline-primary btn-sm mb-4"><i class="bi bi-arrow-left me-1"></i> Semua Galeri</a>
    <?php if ($album['description']): ?><p class="text-muted"><?= e($album['description']) ?></p><?php endif; ?>
    <?php endif; ?>

    <div class="gallery-grid">
      <?php foreach ($pg['data'] as $g): ?>
        <?php if ($g['type'] === 'video' && youtube_id($g['youtube_url'])): $vid = youtube_id($g['youtube_url']); ?>
        <a href="https://www.youtube.com/watch?v=<?= e($vid) ?>" class="gallery-item glightbox video-thumb" data-gallery="galeri" data-aos="zoom-in">
          <img src="https://img.youtube.com/vi/<?= e($vid) ?>/hqdefault.jpg" alt="<?= e($g['title'] ?: 'Video') ?>" loading="lazy">
          <span class="play-btn"><i class="bi bi-play-circle-fill"></i></span>
          <span class="overlay"><?= e($g['title'] ?: 'Video') ?></span>
        </a>
        <?php elseif ($g['file_path']): ?>
        <a href="<?= e(gallery_src($g)) ?>" class="gallery-item glightbox <?= $g['protected'] ? 'protected-img' : '' ?>" data-gallery="galeri" data-aos="zoom-in">
          <img src="<?= e(gallery_thumb($g, 800, 600)) ?>" alt="<?= e($g['title'] ?: 'Foto desa') ?>" width="800" height="600" loading="lazy" decoding="async" <?= $g['protected'] ? 'draggable="false"' : '' ?>>
          <span class="overlay"><?= e($g['title'] ?: '') ?><?= $g['album_name'] ? ' · ' . e($g['album_name']) : '' ?></span>
        </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Belum ada foto/video di galeri ini.</p><?php endif; ?>
    <div class="mt-4"><?= pagination_links($pg) ?></div>
  </div>
</section>
