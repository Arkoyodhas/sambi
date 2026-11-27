<?php
/** Banner slider galeri — gaya sinematik (zoom halus). Butuh $banners dari HomeController. */
$nfSlides = [];
foreach ($banners as $b) {
    $guarded = !empty($b['protected']) || !empty($b['watermark']);
    $nfSlides[] = [
        'title' => trim((string) ($b['banner_title'] ?: $b['title'] ?: ($b['album'] ?? '') ?: 'Dokumentasi Desa')),
        'desc' => trim((string) ($b['banner_desc'] ?: ($b['album_desc'] ?? '') ?: 'Momen kebersamaan warga ' . setting('village_name') . ' yang terekam dalam galeri desa.')),
        'meta' => array_values(array_filter([$b['album'] ?? '', $b['category'] ?? '', $b['year'] ? (string) $b['year'] : ''])),
        'full' => gallery_src($b),
        'lg' => gallery_sized($b, 1440),
        'sm' => gallery_sized($b, 768),
        'thumb' => gallery_sized($b, 480),
        'lqip' => image_lqip($b['file_path']),
        'guarded' => $guarded,
    ];
}
$nfCount = count($nfSlides);
$nfEager = (($sections ?? [])[0] ?? '') === 'banner';
$nfPad = static fn(int $n): string => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
?>
<!-- BANNER GALERI (sinematik) -->
<section class="nf-billboard is-paused" data-nf tabindex="-1" data-testid="home-banner" aria-roledescription="carousel" aria-label="Sorotan galeri desa">
  <div class="nf-stage" data-nf-stage>
    <?php foreach ($nfSlides as $i => $s): ?>
    <figure class="nf-slide<?= $i === 0 ? ' is-active' : '' ?>" data-nf-slide style="--lqip:url('<?= e($s['lqip']) ?>')" aria-roledescription="slide" aria-label="<?= $i + 1 ?> dari <?= $nfCount ?>" <?= $i === 0 ? '' : 'aria-hidden="true"' ?> data-testid="banner-slide-<?= $i ?>">
      <div class="nf-pan">
        <img class="nf-img<?= $s['guarded'] ? ' protected-img' : '' ?>"
             <?= $i === 0 ? '' : 'data-' ?>src="<?= e($s['sm']) ?>"
             <?= $i === 0 ? '' : 'data-' ?>srcset="<?= e($s['sm']) ?> 768w, <?= e($s['lg']) ?> 1440w" sizes="100vw"
             alt="<?= e($s['title']) ?>" width="1440" height="810"
             loading="<?= $i === 0 && $nfEager ? 'eager' : 'lazy' ?>" <?= $i === 0 && $nfEager ? 'fetchpriority="high"' : '' ?> decoding="async">
      </div>
    </figure>
    <?php endforeach; ?>
    <div class="nf-shade" aria-hidden="true"></div>
    <div class="nf-grain" aria-hidden="true"></div>
    <div class="nf-loader" aria-hidden="true"><i></i></div>

    <div class="container nf-info-wrap">
      <?php foreach ($nfSlides as $i => $s): ?>
      <div class="nf-info<?= $i === 0 ? ' is-active' : '' ?>" data-nf-info <?= $i === 0 ? '' : 'hidden' ?>>
        <span class="nf-badge"><b><?= $nfPad($i + 1) ?></b> Galeri Desa</span>
        <h2 class="nf-title" data-testid="banner-title-<?= $i ?>"><?php foreach (preg_split('/\s+/u', $s['title']) as $wi => $word): ?><span class="nf-w"><span style="--i:<?= $wi ?>"><?= e($word) ?></span></span> <?php endforeach; ?></h2>
        <?php if ($s['meta']): ?>
        <div class="nf-meta">
          <span class="nf-match"><i class="bi bi-stars"></i> Sorotan</span>
          <?php foreach ($s['meta'] as $m): ?><span><?= e($m) ?></span><?php endforeach; ?>
        </div>
        <?php endif; ?>
        <p class="nf-desc"><?= e(mb_strimwidth($s['desc'], 0, 260, '…')) ?></p>
        <div class="nf-actions">
          <a href="<?= e($s['full']) ?>" class="nf-btn nf-btn-play glightbox" data-gallery="banner" data-testid="banner-view-<?= $i ?>"><i class="bi bi-arrows-fullscreen"></i> Lihat Foto</a>
          <a href="<?= url('galeri') ?>" class="nf-btn nf-btn-more" data-testid="banner-more-<?= $i ?>">Selengkapnya <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($nfCount > 1): ?>
    <div class="nf-ctrl container">
      <span class="nf-count" data-testid="banner-counter"><b data-nf-count><?= $nfPad(1) ?></b><i></i><?= $nfPad($nfCount) ?></span>
      <button type="button" class="nf-ic" data-nf-prev aria-label="Foto sebelumnya" data-testid="banner-prev"><i class="bi bi-arrow-left"></i></button>
      <button type="button" class="nf-ic nf-ic-ring" data-nf-toggle aria-label="Jeda slider" data-testid="banner-toggle">
        <svg viewBox="0 0 48 48" aria-hidden="true"><circle cx="24" cy="24" r="22"/></svg><span data-nf-toggle-ic><i class="bi bi-pause-fill"></i></span>
      </button>
      <button type="button" class="nf-ic" data-nf-next aria-label="Foto berikutnya" data-testid="banner-next"><i class="bi bi-arrow-right"></i></button>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($nfCount > 1): ?>
  <div class="container nf-row-wrap">
    <h3 class="nf-row-title">Sorotan Galeri <a href="<?= url('galeri') ?>" data-testid="banner-row-all">Jelajahi semua <i class="bi bi-chevron-right"></i></a></h3>
    <div class="nf-row" data-nf-row role="tablist" aria-label="Pilih foto banner">
      <?php foreach ($nfSlides as $i => $s): ?>
      <button type="button" class="nf-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-nf-thumb="<?= $i ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="<?= e($s['title']) ?>" data-testid="banner-thumb-<?= $i ?>" style="--lqip:url('<?= e($s['lqip']) ?>')">
        <img src="<?= e($s['thumb']) ?>" alt="" width="480" height="270" loading="lazy" decoding="async" onload="this.classList.add('is-loaded')">
        <span class="nf-thumb-no"><?= $nfPad($i + 1) ?></span>
        <span class="nf-thumb-cap"><?= e($s['title']) ?></span>
        <span class="nf-bar"><i></i></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</section>
<script src="<?= asset('js/banner.js') ?>" defer></script>
