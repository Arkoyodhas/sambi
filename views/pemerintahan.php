<?php $phTitle = 'Pemerintahan Desa'; $phDesc = 'Struktur organisasi dan perangkat ' . setting('village_name'); include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php foreach ($labels as $key => $label): if (empty($groups[$key])) continue; ?>
    <div class="mb-5">
      <div class="section-head mb-4" data-aos="fade-up">
        <span class="eyebrow"><?= e($label) ?></span>
        <h2 class="fs-3"><?= e($label) ?></h2>
      </div>
      <div class="row g-4">
        <?php foreach ($groups[$key] as $i => $o): ?>
        <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 80 ?>">
          <div class="card-3d official-card card-body" data-testid="official-card-<?= $o['id'] ?>">
            <div class="official-photo protected-img">
              <div class="photo-bg" style="background-image:url('<?= url('media/foto/' . $o['id']) ?>')"></div>
              <div class="photo-shield"></div>
            </div>
            <div class="official-name"><?= e($o['nama']) ?></div>
            <div class="official-role"><?= e($o['jabatan']) ?></div>
            <div class="official-extra mt-1">
              <?php if ($o['pendidikan']): ?><span><i class="bi bi-mortarboard me-1"></i><?= e($o['pendidikan']) ?></span><br><?php endif; ?>
              <?php if ($o['masa_jabatan']): ?><span><i class="bi bi-calendar-range me-1"></i><?= e($o['masa_jabatan']) ?></span><?php endif; ?>
            </div>
            <?php if ($o['tugas']): ?>
            <button class="btn btn-sm btn-outline-primary mt-2" data-bs-toggle="modal" data-bs-target="#tugasModal<?= $o['id'] ?>" data-testid="official-tugas-btn-<?= $o['id'] ?>">Tugas</button>
            <?php ob_start(); ?>
            <div class="modal fade" id="tugasModal<?= $o['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background:var(--surface);color:var(--text);border-radius:var(--radius)">
                  <div class="modal-header border-0">
                    <h6 class="modal-title"><?= e($o['nama']) ?> — <?= e($o['jabatan']) ?></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup" data-testid="official-tugas-close-<?= $o['id'] ?>"></button>
                  </div>
                  <div class="modal-body pt-0">
                    <p class="mb-2"><?= nl2br(e($o['tugas'])) ?></p>
                    <?php if ($o['kontak']): ?><small class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($o['kontak']) ?></small><?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
            <?php $modalsHtml = ($modalsHtml ?? '') . ob_get_clean(); ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if ($rtrw): ?>
    <div class="section-head mb-4" data-aos="fade-up">
      <span class="eyebrow">Wilayah</span>
      <h2 class="fs-3">Ketua RT / RW</h2>
    </div>
    <div class="card-3d" data-aos="fade-up">
      <div class="table-responsive">
        <table class="table table-modern mb-0">
          <thead><tr><th>Wilayah</th><th>Nama Ketua</th></tr></thead>
          <tbody>
            <?php foreach ($rtrw as $r): ?>
            <tr><td><span class="badge-cat text-uppercase"><?= e($r['type']) ?></span> <?= e($r['name']) ?></td><td><?= e($r['kepala']) ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?= $modalsHtml ?? '' ?>
