<?php $phTitle = $service['name']; $phCrumb = 'Layanan'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php $flowType = 'surat'; include BASE_PATH . '/views/partials/flow_illustration.php'; ?>
    <div class="row g-5">
      <div class="col-lg-7">
        <div class="content-html mb-4" data-aos="fade-up"><?= $service['description'] ?></div>
        <?php if ($service['requirements']): ?>
        <div class="glass-panel p-4 mb-4" data-aos="fade-up">
          <h5 class="mb-3"><i class="bi bi-list-check text-primary-c me-2"></i>Persyaratan</h5>
          <ul class="list-check mb-0">
            <?php foreach (array_filter(array_map('trim', explode("\n", $service['requirements']))) as $req): ?>
            <li><?= e($req) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>
        <?php if ($service['flow']): ?>
        <div class="glass-panel p-4 mb-4" data-aos="fade-up">
          <h5 class="mb-3"><i class="bi bi-signpost-split text-primary-c me-2"></i>Alur Pelayanan</h5>
          <div class="timeline">
            <?php foreach (array_filter(array_map('trim', explode("\n", $service['flow']))) as $j => $step): ?>
            <div class="timeline-item"><strong class="small text-primary-c">Langkah <?= $j + 1 ?></strong><p class="mb-0 small"><?= e($step) ?></p></div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="d-flex gap-4 small text-muted">
          <span><i class="bi bi-clock me-1"></i>Waktu: <strong><?= e($service['duration'] ?: '1 hari kerja') ?></strong></span>
          <span><i class="bi bi-cash me-1"></i>Biaya: <strong><?= e($service['fee'] ?: 'Gratis') ?></strong></span>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="glass-panel p-4 sticky-sidebar" data-aos="fade-left">
          <h5 class="mb-1"><i class="bi bi-send text-primary-c me-2"></i>Form Permohonan Online</h5>
          <p class="text-muted small">Isi data dengan benar. Nomor tiket akan diberikan setelah permohonan terkirim.</p>
          <form method="post" action="<?= url('layanan/kirim') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
            <div class="d-flex flex-column gap-3">
              <div><label class="form-label">NIK (16 digit) *</label><input class="form-control" name="nik" value="<?= e(old('nik')) ?>" required pattern="\d{16}" maxlength="16" inputmode="numeric" data-testid="request-nik"></div>
              <div><label class="form-label">Nama Lengkap *</label><input class="form-control" name="nama" value="<?= e(old('nama')) ?>" required data-testid="request-nama"></div>
              <div class="row g-2">
                <div class="col-12"><label class="form-label">No. HP / WhatsApp</label><input class="form-control" name="no_hp" value="<?= e(old('no_hp')) ?>" inputmode="tel" autocomplete="tel" maxlength="20" data-testid="request-hp"></div>
              </div>
              <div><label class="form-label">Alamat</label><input class="form-control" name="alamat" value="<?= e(old('alamat')) ?>" maxlength="255"></div>
              <div><label class="form-label">Keperluan *</label><textarea class="form-control" name="keperluan" rows="2" maxlength="1000" required data-testid="request-keperluan"><?= e(old('keperluan')) ?></textarea></div>
              <div>
                <label class="form-label">Dokumen Pendukung (foto/PDF, opsional)</label>
                <input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx">
              </div>
              <div><input type="hidden" name="captcha_id" value="<?= e($captcha['id']) ?>"><label class="form-label">Captcha: <strong><?= e($captcha['question']) ?></strong></label><input class="form-control" name="captcha" required inputmode="numeric" autocomplete="off"  placeholder="Jawaban" data-testid="request-captcha"></div>
              <button class="btn btn-primary" data-testid="request-submit"><i class="bi bi-send me-1"></i> Kirim Permohonan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
