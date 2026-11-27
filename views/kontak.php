<?php $phTitle = 'Kontak Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-5">
        <div class="d-flex flex-column gap-3" data-aos="fade-up">
          <?php foreach ([
              ['bi-geo-alt-fill', 'Alamat Kantor Desa', setting('address') . ', ' . setting('kecamatan') . ', ' . setting('kabupaten') . ' ' . setting('kode_pos')],
              ['bi-telephone-fill', 'Telepon', setting('phone')],
              ['bi-whatsapp', 'WhatsApp', setting('whatsapp')],
              ['bi-envelope-fill', 'Email', setting('email')],
              ['bi-clock-fill', 'Jam Pelayanan', setting('jam_pelayanan')],
              ['bi-person-badge-fill', 'Pejabat Penghubung', 'Sekretaris Desa — melalui nomor kantor pada jam pelayanan'],
          ] as [$ic, $lab, $val]): if (!$val) continue; ?>
          <div class="card-3d card-body d-flex gap-3 align-items-center py-3">
            <div class="icon-tile" style="width:46px;height:46px;font-size:1.15rem"><i class="bi <?= $ic ?>"></i></div>
            <div><small class="text-muted d-block"><?= e($lab) ?></small><strong class="small"><?= e($val) ?></strong></div>
          </div>
          <?php endforeach; ?>
          <div class="glass-panel p-2"><div id="villageMap" style="height:260px"></div></div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="glass-panel p-4 p-md-5" data-aos="fade-left">
          <h5 class="mb-1"><i class="bi bi-chat-dots text-primary-c me-2"></i>Kirim Pesan</h5>
          <p class="text-muted small mb-4">Pesan akan masuk ke kotak masuk admin desa dan dibalas melalui kontak yang Anda berikan.</p>
          <form method="post" action="<?= url('kontak/kirim') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Nama *</label><input class="form-control" name="name" value="<?= e(old('name')) ?>" maxlength="150" autocomplete="name" required data-testid="contact-name"></div>
              <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" maxlength="150" autocomplete="email"></div>
              <div class="col-md-6"><label class="form-label">No. HP</label><input class="form-control" name="phone" value="<?= e(old('phone')) ?>" maxlength="20" autocomplete="tel"></div>
              <div class="col-md-6"><label class="form-label">Subjek</label><input class="form-control" name="subject" value="<?= e(old('subject')) ?>" maxlength="200"></div>
              <div class="col-12"><label class="form-label">Pesan *</label><textarea class="form-control" name="message" rows="5" minlength="10" maxlength="3000" required data-testid="contact-message"><?= e(old('message')) ?></textarea></div>
              <div class="col-md-6"><input type="hidden" name="captcha_id" value="<?= e($captcha['id']) ?>"><label class="form-label">Captcha: <strong><?= e($captcha['question']) ?></strong></label><input class="form-control" name="captcha" inputmode="numeric" autocomplete="off" required placeholder="Jawaban"></div>
              <div class="col-12"><button class="btn btn-primary px-4" data-testid="contact-submit"><i class="bi bi-send me-1"></i> Kirim Pesan</button></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
<?php
$needsMap = true;
$inlineScript = '<script>
document.addEventListener("DOMContentLoaded", function(){
  window.desaMap && desaMap({lat: ' . json_encode((float) setting('map_lat', '-6.914744')) . ', lng: ' . json_encode((float) setting('map_lng', '107.60981')) . ', zoom: ' . (int) setting('map_zoom', '14') . ', title: ' . json_encode('Kantor ' . setting('village_name')) . ', address: ' . json_encode(setting('address')) . '});
});
</script>';
?>
