<?php $phTitle = 'Indeks Desa Membangun (IDM)'; $phDesc = 'Kemajuan desa diukur dari 3 dimensi: sosial, ekonomi, dan lingkungan.'; include BASE_PATH . '/views/partials/page_header.php';
function idm_kl(float $idm): array {
  if ($idm > 0.8155) return ['Mandiri', 'success', 'Desa mandiri, kualitas hidup terjaga dan kemampuan pengelolaan tinggi.'];
  if ($idm > 0.7072) return ['Maju', 'info', 'Desa maju, sudah mencapai standar dasar yang baik.'];
  if ($idm > 0.5989) return ['Berkembang', 'warning', 'Desa berkembang, sedang menuju kemandirian.'];
  if ($idm > 0.4907) return ['Tertinggal', 'danger', 'Desa masih tertinggal, butuh intervensi bertahap.'];
  return ['Sangat Tertinggal', 'secondary', 'Perlu perhatian khusus.'];
}
?>

<section class="section"><div class="container py-4">
  <?php if ($latest): [$kl, $col, $desc] = idm_kl((float)$latest['idm']); ?>
  <div class="card border-0 shadow-sm mb-4" data-testid="idm-latest-card">
    <div class="card-body text-center p-4">
      <div class="text-muted small mb-2">Skor IDM Tahun <?= (int)$latest['tahun'] ?></div>
      <div class="display-3 fw-bold text-<?= $col ?>"><?= number_format((float)$latest['idm'],4) ?></div>
      <span class="badge bg-<?= $col ?> fs-6 mt-2 px-3 py-2"><?= $kl ?></span>
      <p class="text-muted mt-3 mb-0"><?= e($desc) ?></p>
      <div class="row g-3 mt-4">
        <div class="col-md-4"><div class="p-3 rounded bg-light"><small class="text-muted d-block">IKS · Sosial</small><strong class="h4 text-primary"><?= number_format((float)$latest['iks'],4) ?></strong></div></div>
        <div class="col-md-4"><div class="p-3 rounded bg-light"><small class="text-muted d-block">IKE · Ekonomi</small><strong class="h4 text-success"><?= number_format((float)$latest['ike'],4) ?></strong></div></div>
        <div class="col-md-4"><div class="p-3 rounded bg-light"><small class="text-muted d-block">IKL · Lingkungan</small><strong class="h4 text-info"><?= number_format((float)$latest['ikl'],4) ?></strong></div></div>
      </div>
      <?php if ($latest['catatan']): ?><p class="text-muted small mt-4 mb-0"><i class="bi bi-info-circle me-1"></i><?= e($latest['catatan']) ?></p><?php endif; ?>
    </div>
  </div>
  <div class="d-flex justify-content-end mb-3"><a href="<?= url('transparansi/cetak?jenis=idm') ?>" target="_blank" class="btn btn-outline-primary" data-testid="idm-print-btn"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF</a></div>
  <?php endif; ?>

  <h5 class="mt-4 mb-3">Riwayat 10 Tahun Terakhir</h5>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Tahun</th><th class="text-end">IKS</th><th class="text-end">IKE</th><th class="text-end">IKL</th><th class="text-end">IDM</th><th>Klasifikasi</th></tr></thead>
    <tbody>
      <?php foreach ($rows as $r): [$kl, $col] = idm_kl((float)$r['idm']); ?>
      <tr><td><strong><?= (int)$r['tahun'] ?></strong></td><td class="text-end"><?= number_format((float)$r['iks'],4) ?></td><td class="text-end"><?= number_format((float)$r['ike'],4) ?></td><td class="text-end"><?= number_format((float)$r['ikl'],4) ?></td><td class="text-end"><strong><?= number_format((float)$r['idm'],4) ?></strong></td><td><span class="badge bg-<?= $col ?>-subtle text-<?= $col ?>-emphasis"><?= $kl ?></span></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Data IDM belum tersedia. Silakan hubungi pemerintah desa.</td></tr><?php endif; ?>
    </tbody></table>
  </div>
</div></section>
