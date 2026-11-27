<?php $phTitle = 'Profil Desa'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-8">
        <?php foreach (['sejarah', 'visi', 'misi'] as $key): if (isset($byKey[$key])): $s = $byKey[$key]; ?>
        <div class="mb-5" data-aos="fade-up" id="<?= e($key) ?>">
          <h2 class="fs-3 mb-3"><span class="text-primary-c">#</span> <?= e($s['title']) ?></h2>
          <?php if ($s['image']): ?><img src="<?= e(thumbnail_url($s['image'], 960, 560)) ?>" class="rounded-4 mb-3 w-100" style="max-height:380px;object-fit:cover" alt="<?= e($s['title']) ?>" loading="lazy"><?php endif; ?>
          <div class="content-html"><?= $s['content'] ?></div>
        </div>
        <?php endif; endforeach; ?>

        <div class="mb-5" data-aos="fade-up">
          <h2 class="fs-3 mb-3"><span class="text-primary-c">#</span> Struktur Organisasi Pemerintah Desa</h2>
          <div class="row g-3">
            <?php foreach ($officials as $o): ?>
            <div class="col-6 col-md-4">
              <div class="card-3d card-body text-center py-3">
                <div class="official-photo protected-img mx-auto mb-2" style="max-width:110px;aspect-ratio:1;border-radius:50%">
                  <div class="photo-bg" style="background-image:url('<?= url('media/foto/' . $o['id']) ?>')"></div>
                  <div class="photo-shield" style="background:transparent"></div>
                </div>
                <strong class="d-block small"><?= e($o['nama']) ?></strong>
                <small class="official-role"><?= e($o['jabatan']) ?></small>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <a href="<?= url('pemerintahan') ?>" class="btn btn-outline-primary btn-sm mt-3">Lihat Struktur Lengkap</a>
        </div>

        <?php foreach (['wilayah_administratif', 'batas_wilayah', 'geografis', 'demografi', 'sarana_prasarana', 'lembaga_desa', 'sosial_ekonomi'] as $key): if (isset($byKey[$key])): $s = $byKey[$key]; ?>
        <div class="mb-5" data-aos="fade-up">
          <h2 class="fs-3 mb-3"><span class="text-primary-c">#</span> <?= e($s['title']) ?></h2>
          <div class="content-html"><?= $s['content'] ?></div>
        </div>
        <?php endif; endforeach; ?>

        <div class="mb-4" data-aos="fade-up">
          <h2 class="fs-3 mb-3"><span class="text-primary-c">#</span> Potensi Desa</h2>
          <div class="row g-3">
            <?php foreach ($potentials as $p): ?>
            <div class="col-md-6">
              <div class="card-3d card-body">
                <span class="badge-cat text-capitalize mb-2 d-inline-block"><?= e($p['category']) ?></span>
                <h6><?= e($p['title']) ?></h6>
                <p class="text-muted small mb-0"><?= e(excerpt($p['description'], 130)) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="sticky-sidebar d-flex flex-column gap-4">
          <div class="glass-panel p-4" data-aos="fade-left">
            <h5 class="mb-3"><i class="bi bi-card-list text-primary-c me-2"></i>Identitas Desa</h5>
            <table class="table table-modern table-sm mb-0">
              <tr><td class="text-muted">Nama Desa</td><td class="fw-semibold"><?= e(setting('village_name')) ?></td></tr>
              <tr><td class="text-muted">Kecamatan</td><td><?= e(setting('kecamatan')) ?></td></tr>
              <tr><td class="text-muted">Kabupaten</td><td><?= e(setting('kabupaten')) ?></td></tr>
              <tr><td class="text-muted">Provinsi</td><td><?= e(setting('provinsi')) ?></td></tr>
              <tr><td class="text-muted">Kode Pos</td><td><?= e(setting('kode_pos')) ?></td></tr>
              <tr><td class="text-muted">Luas Wilayah</td><td><?= e(setting('luas_wilayah')) ?></td></tr>
              <tr><td class="text-muted">Dusun</td><td><?= (int) $wilayah['dusun'] ?></td></tr>
              <tr><td class="text-muted">RW / RT</td><td><?= (int) $wilayah['rw'] ?> / <?= (int) $wilayah['rt'] ?></td></tr>
              <tr><td class="text-muted">Kepala Desa</td><td><?= e(setting('kepala_desa_nama')) ?></td></tr>
            </table>
          </div>
          <div class="glass-panel p-4" data-aos="fade-left" data-aos-delay="100">
            <h5 class="mb-3"><i class="bi bi-telephone text-primary-c me-2"></i>Kontak Resmi</h5>
            <ul class="footer-links footer-contact" style="color:var(--text)">
              <li><i class="bi bi-geo-alt"></i> <?= e(setting('address')) ?></li>
              <li><i class="bi bi-telephone"></i> <?= e(setting('phone')) ?></li>
              <li><i class="bi bi-envelope"></i> <?= e(setting('email')) ?></li>
              <li><i class="bi bi-clock"></i> <?= e(setting('jam_pelayanan')) ?></li>
            </ul>
            <a href="<?= url('kontak') ?>" class="btn btn-primary btn-sm w-100 mt-2">Hubungi Kami</a>
          </div>
          <div class="glass-panel p-2" data-aos="fade-left" data-aos-delay="150">
            <div id="villageMap" style="height:260px"></div>
          </div>
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
