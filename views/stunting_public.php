<?php $phTitle = 'Data Stunting Balita'; $phDesc = 'Statistik agregat per RT. Data individu balita tidak ditampilkan demi menjaga privasi warga.'; include BASE_PATH . '/views/partials/page_header.php';
$statusLabel = ['normal'=>'Normal','pendek'=>'Pendek','sangat_pendek'=>'Sangat Pendek','beresiko'=>'Beresiko'];
$statusColor = ['normal'=>'success','pendek'=>'warning','sangat_pendek'=>'danger','beresiko'=>'info'];
$totalYear = array_sum(array_column($byStatus,'total'));
$stuntedByStatus = []; foreach ($byStatus as $s) $stuntedByStatus[$s['status']] = (int)$s['total'];
?>
<section class="section"><div class="container py-4">
  <form class="row g-2 mb-4" method="get">
    <div class="col-6 col-md-3"><select class="form-select" name="tahun" onchange="this.form.submit()" data-testid="stunting-tahun">
      <?php $years = array_unique(array_merge([date('Y')], array_column($tahunList,'tahun'))); rsort($years); foreach ($years as $y): ?>
      <option value="<?= (int)$y ?>" <?= (int)$tahun===(int)$y?'selected':'' ?>>Tahun <?= (int)$y ?></option>
      <?php endforeach; ?>
    </select></div>
    <div class="col-md-9 text-md-end"><a href="<?= url('transparansi/cetak?jenis=stunting&tahun='.$tahun) ?>" target="_blank" class="btn btn-outline-primary" data-testid="stunting-print-btn"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF</a></div>
  </form>

  <div class="row g-3 mb-4">
    <?php foreach ($statusLabel as $sv => $sl): $val = $stuntedByStatus[$sv] ?? 0; $pct = $totalYear>0 ? round($val/$totalYear*100,1) : 0; ?>
    <div class="col-md-3 col-6">
      <div class="card border-0 shadow-sm h-100" data-testid="stunting-status-card">
        <div class="card-body text-center">
          <div class="small text-muted mb-1"><?= $sl ?></div>
          <div class="h2 fw-bold text-<?= $statusColor[$sv] ?>"><?= (int)$val ?></div>
          <div class="small text-muted"><?= $pct ?>% dari total</div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <h5 class="mb-3">Sebaran per RT/RW</h5>
  <div class="row g-2">
    <?php foreach ($byRt as $r): $pct = $r['total']>0 ? round($r['stunting']/$r['total']*100,1) : 0; ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm" data-testid="stunting-rt-card">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-2"><strong>RT <?= e($r['rt']??'-') ?> / RW <?= e($r['rw']??'-') ?></strong><span class="text-<?= $pct>15?'danger':($pct>5?'warning':'success') ?>"><?= $pct ?>%</span></div>
          <div class="progress mb-2" style="height:10px"><div class="progress-bar bg-<?= $pct>15?'danger':($pct>5?'warning':'success') ?>" style="width:<?= $pct ?>%"></div></div>
          <small class="text-muted"><?= (int)$r['stunting'] ?> stunting dari <?= (int)$r['total'] ?> balita diperiksa</small>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$byRt): ?><div class="col-12 text-center text-muted py-4">Data belum tersedia untuk tahun <?= (int)$tahun ?>.</div><?php endif; ?>
  </div>

  <?php if ($byMonth): ?>
  <h5 class="mt-4 mb-3">Tren Bulanan</h5>
  <div class="table-responsive"><table class="table table-hover"><thead><tr><th>Periode</th><th class="text-end">Total Diperiksa</th><th class="text-end">Stunting/Beresiko</th></tr></thead><tbody>
    <?php foreach ($byMonth as $m): ?>
    <tr><td><?= e($m['periode']) ?></td><td class="text-end"><?= (int)$m['total'] ?></td><td class="text-end text-danger"><strong><?= (int)$m['stunting'] ?></strong></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
  <?php endif; ?>

  <div class="alert alert-info mt-4 mb-0"><i class="bi bi-info-circle me-2"></i><strong>Privasi:</strong> data individu balita (nama, alamat lengkap) tidak ditampilkan di halaman publik ini. Hanya agregat per RT/RW yang bisa dilihat masyarakat.</div>
</div></section>
