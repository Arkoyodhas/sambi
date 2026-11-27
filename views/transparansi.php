<?php $phTitle = 'Transparansi Desa'; $phDesc = 'APBDes, realisasi anggaran, dan progres pembangunan desa secara terbuka.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <form class="row g-2 mb-4" method="get" action="<?= url('transparansi') ?>">
      <div class="col-6 col-md-3">
        <select class="form-select" name="tahun" data-testid="filter-tahun">
          <?php foreach ($tahunList as $t): ?><option value="<?= (int) $t['year'] ?>" <?= $tahun === (int) $t['year'] ? 'selected' : '' ?>>Tahun <?= (int) $t['year'] ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select" name="sumber">
          <option value="">Semua Sumber Dana</option>
          <?php foreach ($sumberList as $s): ?><option value="<?= e($s['source']) ?>" <?= $sumber === $s['source'] ? 'selected' : '' ?>><?= e($s['source']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>

    <div class="row g-3 mb-5">
      <?php foreach ([['Pendapatan', $summary['pendapatan'], $summary['realisasi_pendapatan'], 'bi-graph-up-arrow'], ['Belanja', $summary['belanja'], $summary['realisasi_belanja'], 'bi-cart']] as [$lab, $anggaran, $realisasi, $ic]): ?>
      <div class="col-md-6" data-aos="fade-up">
        <div class="card-3d card-body">
          <div class="d-flex align-items-center gap-3 mb-2">
            <div class="icon-tile" style="width:46px;height:46px;font-size:1.2rem"><i class="bi <?= $ic ?>"></i></div>
            <div>
              <small class="text-muted text-uppercase" style="letter-spacing:1px"><?= $lab ?> <?= (int) $tahun ?></small>
              <h4 class="mb-0"><?= rupiah($anggaran) ?></h4>
            </div>
          </div>
          <?php $pct = $anggaran > 0 ? round($realisasi / $anggaran * 100) : 0; ?>
          <div class="d-flex justify-content-between small text-muted mb-1"><span>Realisasi: <?= rupiah($realisasi) ?></span><span><?= $pct ?>%</span></div>
          <div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="section-head" data-aos="fade-up"><span class="eyebrow">APBDes</span><h2 class="fs-4">Rincian Anggaran <?= (int) $tahun ?></h2></div>
    <div class="card-3d mb-3" data-aos="fade-up">
      <div class="table-responsive">
        <table class="table table-modern mb-0">
          <thead><tr><th>Jenis</th><th>Kategori / Bidang</th><th>Sumber Dana</th><th class="text-end">Anggaran</th><th class="text-end">Realisasi</th><th style="width:130px">Capaian</th></tr></thead>
          <tbody>
            <?php foreach ($budgets as $b): $pct = $b['budget_amount'] > 0 ? round($b['realization_amount'] / $b['budget_amount'] * 100) : 0; ?>
            <tr>
              <td><span class="badge-cat text-capitalize"><?= e($b['type']) ?></span></td>
              <td><strong class="small"><?= e($b['category']) ?></strong><?php if ($b['description']): ?><br><small class="text-muted"><?= e(excerpt($b['description'], 70)) ?></small><?php endif; ?></td>
              <td class="small"><?= e($b['source']) ?></td>
              <td class="text-end small"><?= rupiah($b['budget_amount']) ?></td>
              <td class="text-end small"><?= rupiah($b['realization_amount']) ?></td>
              <td><div class="progress" style="height:6px"><div class="progress-bar" style="width:<?= min(100, $pct) ?>%"></div></div><small class="text-muted"><?= $pct ?>%</small></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$budgets): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada data anggaran tahun ini.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-3d card-body mb-5" data-aos="fade-up"><h6 class="mb-3">Grafik Anggaran vs Realisasi</h6><canvas id="chartAnggaran" height="120"></canvas></div>

    <div class="section-head" data-aos="fade-up"><span class="eyebrow">Pembangunan</span><h2 class="fs-4">Program Pembangunan <?= (int) $tahun ?></h2></div>
    <div class="row g-4 mb-4">
      <?php foreach ($projects as $p): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up">
        <div class="card-3d h-100">
          <?php if ($p['photo']): ?><img src="<?= e(thumbnail_url($p['photo'], 640, 420)) ?>" class="news-thumb" alt="<?= e($p['name']) ?>" loading="lazy">
          <?php else: ?><div class="news-thumb-fallback"><i class="bi bi-cone-striped"></i></div><?php endif; ?>
          <div class="card-body">
            <div class="d-flex justify-content-between gap-2 align-items-start mb-2">
              <h6 class="mb-0"><?= e($p['name']) ?></h6>
              <?= status_badge($p['status']) ?>
            </div>
            <div class="small text-muted mb-2">
              <div><i class="bi bi-geo-alt me-1"></i><?= e($p['location'] ?: '-') ?></div>
              <div><i class="bi bi-wallet2 me-1"></i><?= rupiah($p['budget']) ?> · <?= e($p['source']) ?></div>
            </div>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Progres</span><strong><?= (int) $p['progress'] ?>%</strong></div>
            <div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= (int) $p['progress'] ?>%"></div></div>
            <?php if ($p['document']): ?><a class="btn btn-sm btn-outline-primary mt-3" href="<?= e(upload_url($p['document'])) ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-pdf me-1"></i>Dokumen</a><?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$projects): ?><p class="text-muted">Belum ada program pembangunan tahun ini.</p><?php endif; ?>
    </div>

    <?php if ($projects): ?>
    <div class="section-head" data-aos="fade-up"><span class="eyebrow">Linimasa</span><h2 class="fs-4">Timeline Pembangunan</h2></div>
    <div class="timeline" data-aos="fade-up">
      <?php foreach ($projects as $p): ?>
      <div class="timeline-item">
        <div class="d-flex gap-2 align-items-center flex-wrap"><strong><?= e($p['name']) ?></strong> <?= status_badge($p['status']) ?></div>
        <small class="text-muted"><?= tgl_id($p['start_date']) ?> — <?= tgl_id($p['end_date']) ?> · Progres <?= (int) $p['progress'] ?>%</small>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php
$needsChart = true;
$labels = array_map(fn($b) => $b['category'], $budgets);
$anggaranArr = array_map(fn($b) => (float) $b['budget_amount'], $budgets);
$realisasiArr = array_map(fn($b) => (float) $b['realization_amount'], $budgets);
$inlineScript = '<script>
document.addEventListener("DOMContentLoaded", function(){
  var css = getComputedStyle(document.documentElement);
  new Chart(document.getElementById("chartAnggaran"), {type:"bar",
    data:{labels:' . json_encode($labels) . ',
      datasets:[{label:"Anggaran", data:' . json_encode($anggaranArr) . ', backgroundColor:css.getPropertyValue("--c-primary").trim(), borderRadius:6},
                {label:"Realisasi", data:' . json_encode($realisasiArr) . ', backgroundColor:css.getPropertyValue("--c-accent").trim(), borderRadius:6}]},
    options:{plugins:{legend:{position:"bottom"}}, scales:{y:{ticks:{callback:function(v){return "Rp "+(v/1000000)+" jt";}}}}}});
});
</script>';
?>
