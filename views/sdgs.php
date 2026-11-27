<?php $phTitle = 'SDGs Desa'; $phDesc = 'Capaian 18 Tujuan Pembangunan Berkelanjutan (SDGs) ' . setting('village_name') . '.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <div class="row g-3 align-items-stretch mb-4">
      <div class="col-md-4" data-aos="fade-up">
        <div class="glass-panel p-4 h-100 text-center" data-testid="sdgs-average">
          <div class="text-muted small text-uppercase" style="letter-spacing:1.5px">Skor Rata-rata SDGs Desa</div>
          <div class="display-3 fw-bold my-1 counter" data-count="<?= e($avg) ?>"><?= e(number_format((float) $avg, 2, ',', '.')) ?></div>
          <div class="progress mt-2" style="height:10px"><div class="progress-bar" style="width:<?= min(100, (float) $avg) ?>%"></div></div>
          <small class="text-muted d-block mt-2">dari skala 0 - 100</small>
        </div>
      </div>
      <div class="col-md-8" data-aos="fade-up" data-aos-delay="80">
        <div class="card-3d card-body h-100">
          <h2 class="h5 mb-2">Apa itu SDGs Desa?</h2>
          <p class="mb-0 text-muted">SDGs Desa adalah 18 Tujuan Pembangunan Berkelanjutan tingkat desa sesuai Permendesa PDTT — arah pembangunan desa menuju desa tanpa kemiskinan, sehat, berpendidikan, ramah lingkungan, hingga kelembagaan yang dinamis. Skor tiap tujuan diperbarui oleh pemerintah desa secara berkala.</p>
        </div>
      </div>
    </div>
    <div class="row g-3" data-testid="sdgs-grid">
      <?php foreach ($goals as $i => $g): ?>
      <div class="col-6 col-md-4 col-lg-3" data-aos="fade-up" data-aos-delay="<?= ($i % 4) * 60 ?>">
        <div class="sdgs-card" style="--sdg-color:<?= e($g['color']) ?>" data-testid="sdgs-card-<?= (int) $g['goal_number'] ?>">
          <div class="sdgs-head">
            <span class="sdgs-num"><?= (int) $g['goal_number'] ?></span>
            <i class="bi <?= e($g['icon'] ?: 'bi-globe-asia-australia') ?>"></i>
          </div>
          <div class="sdgs-title"><?= e($g['title']) ?></div>
          <div class="sdgs-score">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <small>Skor</small><strong><?= e(rtrim(rtrim(number_format((float) $g['score'], 2, '.', ''), '0'), '.')) ?></strong>
            </div>
            <div class="sdgs-bar"><span style="width:<?= min(100, (float) $g['score']) ?>%"></span></div>
          </div>
          <?php if ($g['description']): ?><div class="sdgs-desc"><?= e(excerpt($g['description'], 90)) ?></div><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$goals): ?><div class="col-12 text-center text-muted py-5" data-testid="sdgs-empty">Data SDGs belum tersedia.</div><?php endif; ?>
    </div>
  </div>
</section>
