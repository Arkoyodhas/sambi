<?php
$pageTitleText = trim((string) ($page['title'] ?? 'Halaman'));
$phTitle = $pageTitleText;
include BASE_PATH . '/views/partials/page_header.php';
$template = in_array((string) ($page['template'] ?? 'default'), ['default', 'full'], true)
  ? (string) ($page['template'] ?? 'default')
  : 'default';
$image = trim((string) ($page['image'] ?? ''));
$attachment = trim((string) ($page['attachment'] ?? ''));
?>
<section class="section">
  <div class="container" style="<?= $template === 'full' ? '' : 'max-width:860px' ?>">
    <?php if (!empty($isPreview)): ?>
    <div class="alert alert-warning d-flex align-items-center gap-2" role="status">
      <i class="bi bi-eye-slash fs-5"></i>
      <div><b>Mode pratinjau admin.</b> Halaman ini belum aktif dan tidak terlihat oleh pengunjung.</div>
    </div>
    <?php endif; ?>

    <?php if ($image): ?>
    <img src="<?= e(thumbnail_url($image, 960, 560)) ?>" class="w-100 rounded-4 mb-4" loading="lazy" decoding="async"
      style="max-height:420px;object-fit:cover" alt="<?= e($pageTitleText) ?>">
    <?php endif; ?>

    <div class="content-html" data-aos="fade-up"><?= (string) ($page['content'] ?? '') ?></div>

    <?php if ($attachment): ?>
    <a href="<?= e(upload_url($attachment)) ?>" class="btn btn-primary mt-4" target="_blank" rel="noopener">
      <i class="bi bi-paperclip me-1"></i>Unduh Lampiran
    </a>
    <?php endif; ?>
  </div>
</section>
