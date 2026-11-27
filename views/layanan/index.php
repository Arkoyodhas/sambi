<?php $phTitle = 'Layanan Desa'; $phDesc = 'Ajukan layanan administrasi secara online dan pantau status permohonan Anda.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php $flowType = 'surat'; include BASE_PATH . '/views/partials/flow_illustration.php'; ?>
    <div class="glass-panel p-3 d-flex flex-wrap gap-2 align-items-center justify-content-between mb-4" data-aos="fade-up">
      <span class="small fw-semibold"><i class="bi bi-search me-1 text-primary-c"></i> Sudah mengajukan permohonan?</span>
      <a href="<?= url('layanan/cek') ?>" class="btn btn-sm btn-primary" data-testid="cek-status-btn">Cek Status dengan Nomor Tiket</a>
    </div>
    <div class="row g-4">
      <?php foreach ($services as $i => $s): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="<?= ($i % 3) * 80 ?>">
        <div class="card-3d card-body h-100 d-flex flex-column">
          <div class="icon-tile mb-3"><i class="bi bi-file-earmark-check"></i></div>
          <h5 class="fs-6 fw-bold"><?= e($s['name']) ?></h5>
          <p class="text-muted small flex-grow-1"><?= e(excerpt($s['description'], 100)) ?></p>
          <div class="d-flex justify-content-between align-items-center small text-muted mb-3">
            <span><i class="bi bi-clock me-1"></i><?= e($s['duration'] ?: '1 hari kerja') ?></span>
            <span><i class="bi bi-cash me-1"></i><?= e($s['fee'] ?: 'Gratis') ?></span>
          </div>
          <a href="<?= url('layanan/' . $s['slug']) ?>" class="btn btn-primary btn-sm" data-testid="service-detail-<?= $s['id'] ?>">Lihat & Ajukan</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
