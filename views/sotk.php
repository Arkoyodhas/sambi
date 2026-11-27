<?php $phTitle = 'Struktur Organisasi & Tata Kerja (SOTK)'; $phDesc = 'Bagan struktur organisasi pemerintahan ' . setting('village_name') . '.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <?php if (!$levels): ?>
    <div class="text-center text-muted py-5" data-testid="sotk-empty"><i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>Data struktur organisasi belum tersedia.</div>
    <?php else: ?>
    <div class="sotk-chart" data-testid="sotk-chart">
      <?php $labels = [1 => 'Kepala Desa', 2 => 'Sekretariat', 3 => 'Pelaksana Teknis', 4 => 'Pelaksana Kewilayahan'];
      $first = true;
      foreach ([1, 2, 3, 4] as $lv): if (empty($levels[$lv])) continue; ?>
      <?php if (!$first): ?><div class="sotk-connector" aria-hidden="true"></div><?php endif; $first = false; ?>
      <div class="sotk-level" data-aos="fade-up">
        <span class="sotk-level-label"><?= $labels[$lv] ?></span>
        <div class="sotk-row">
          <?php foreach ($levels[$lv] as $p): ?>
          <div class="sotk-card <?= $lv === 1 ? 'lead-card' : '' ?>" data-testid="sotk-card-<?= $p['id'] ?>">
            <div class="sotk-photo">
              <?php if ($p['foto']): ?><img src="<?= e(upload_url($p['foto'])) ?>" alt="<?= e($p['nama']) ?>" loading="lazy">
              <?php else: ?><span class="avatar-fallback"><?= strtoupper(mb_substr($p['nama'], 0, 1)) ?></span><?php endif; ?>
            </div>
            <div class="sotk-role"><?= e($p['jabatan']) ?></div>
            <div class="sotk-name"><?= e($p['nama']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
