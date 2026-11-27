<?php $phTitle = 'Pengaduan & Aspirasi Warga'; $phDesc = 'Sampaikan pengaduan, keluhan, atau aspirasi Anda. Identitas dapat dirahasiakan.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php $flowType = 'aduan'; include BASE_PATH . '/views/partials/flow_illustration.php'; ?>
    <div class="row g-5">
      <div class="col-lg-7">
        <div class="glass-panel p-4 p-md-5" data-aos="fade-up">
          <h5 class="mb-1"><i class="bi bi-chat-square-text text-primary-c me-2"></i>Form Pengaduan</h5>
          <p class="text-muted small mb-4">Pengaduan Anda akan diverifikasi dan ditindaklanjuti petugas desa. Simpan nomor tiket untuk memantau status.</p>
          <form method="post" action="<?= url('pengaduan/kirim') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nama Pelapor *</label><input class="form-control" name="nama" value="<?= e(old('nama')) ?>" maxlength="150" autocomplete="name" required data-testid="complaint-nama"></div>
              <div class="col-md-6"><label class="form-label">No. HP / WhatsApp</label><input class="form-control" name="kontak" inputmode="tel" autocomplete="tel" value="<?= e(old('kontak')) ?>" maxlength="150"></div>
              <div class="col-md-6">
                <label class="form-label">Kategori *</label>
                <select class="form-select" name="category" data-testid="complaint-category">
                  <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>" <?= old_selected('category', $c) ?>><?= e($c) ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Bukti Foto/File (opsional)</label>
                <input class="form-control" type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx">
              </div>
              <div class="col-12"><label class="form-label">Isi Pengaduan *</label><textarea class="form-control" name="content" rows="4" minlength="10" maxlength="3000" required data-testid="complaint-content"><?= e(old('content')) ?></textarea></div>
              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_anonymous" id="anonim" <?= old_checked('is_anonymous') ?> data-testid="complaint-anonymous">
                  <label class="form-check-label small" for="anonim">Rahasiakan identitas saya (nama tidak ditampilkan ke publik)</label>
                </div>
              </div>
              <div class="col-md-6"><input type="hidden" name="captcha_id" value="<?= e($captcha['id']) ?>"><label class="form-label">Captcha: <strong><?= e($captcha['question']) ?></strong></label><input class="form-control" name="captcha" inputmode="numeric" autocomplete="off" required placeholder="Jawaban" data-testid="complaint-captcha"></div>
              <div class="col-12"><button class="btn btn-primary px-4" data-testid="complaint-submit"><i class="bi bi-send me-1"></i> Kirim Pengaduan</button></div>
            </div>
          </form>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="glass-panel p-4 mb-4" data-aos="fade-left">
          <h6 class="mb-2"><i class="bi bi-search text-primary-c me-1"></i> Cek Status Pengaduan</h6>
          <form method="get" action="<?= url('pengaduan/cek') ?>" class="d-flex gap-2">
            <input class="form-control" name="tiket" placeholder="Nomor tiket (ADU-...)" data-testid="complaint-cek-input">
            <button class="btn btn-primary"><i class="bi bi-arrow-right"></i></button>
          </form>
        </div>
        <h6 class="mb-3">Pengaduan Terbaru</h6>
        <div class="d-flex flex-column gap-3">
          <?php foreach ($recent as $r): ?>
          <div class="card-3d card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="badge-cat"><?= e($r['category']) ?></span>
              <?= status_badge($r['status']) ?>
            </div>
            <p class="small mb-1"><?= e(excerpt($r['preview'], 90)) ?></p>
            <small class="text-muted"><?= e($r['is_anonymous'] ? 'Pelapor dirahasiakan' : $r['nama']) ?> · <?= tgl_id($r['created_at']) ?></small>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
