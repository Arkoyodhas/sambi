<?php /* butuh: $phTitle, opsional $phCrumb */ ?>
<header class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="<?= url('') ?>">Beranda</a></li>
        <li class="breadcrumb-item active"><?= e($phCrumb ?? $phTitle) ?></li>
      </ol>
    </nav>
    <!--<span class="page-verify" data-aos="fade-up"><?= verify_mark_html('page-verify-mark') ?> Portal Resmi Pemerintah Desa</span>-->
    <h1 data-aos="fade-up"><?= e($phTitle) ?></h1>
    <?php if (!empty($phDesc)): ?><p class="mb-0 opacity-75" style="max-width:640px" data-aos="fade-up" data-aos-delay="100"><?= e($phDesc) ?></p><?php endif; ?>
  </div>
</header>
