<?php $phTitle = 'Data Bantuan Sosial'; $phDesc = 'Transparansi penerima bantuan sosial. Data pribadi (NIK) ditampilkan sebagian untuk melindungi privasi warga.'; include BASE_PATH . '/views/partials/page_header.php';
function mask_nik(?string $nik): string { if (!$nik) return '-'; $n = preg_replace('/\D/', '', $nik); if (strlen($n) < 8) return '****'; return substr($n,0,4) . '********' . substr($n,-4); }
?>
<section class="section"><div class="container py-4">
  <form class="row g-2 mb-4" method="get">
    <div class="col-6 col-md-3"><select class="form-select" name="tahun" onchange="this.form.submit()" data-testid="bansos-tahun">
      <?php $years = array_unique(array_merge([date('Y')], array_column($tahunList,'tahun'))); rsort($years); foreach ($years as $y): ?>
      <option value="<?= (int)$y ?>" <?= (int)$tahun===(int)$y?'selected':'' ?>>Tahun <?= (int)$y ?></option>
      <?php endforeach; ?>
    </select></div>
    <div class="col-md-9 text-md-end"><a href="<?= url('transparansi/cetak?jenis=bansos&tahun='.$tahun) ?>" target="_blank" class="btn btn-outline-primary" data-testid="bansos-print-btn"><i class="bi bi-file-earmark-pdf me-1"></i>Unduh PDF</a></div>
  </form>

  <div class="row g-3 mb-4">
    <?php foreach ($programs as $p): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100" data-testid="bansos-program-card">
        <div class="card-body">
          <h5 class="card-title mb-1"><?= e($p['nama']) ?></h5>
          <div class="small text-muted mb-2"><?= e($p['sumber_dana'] ?: 'Sumber dana: -') ?></div>
          <p class="small mb-2"><?= e(mb_substr($p['deskripsi'] ?: '', 0, 140)) ?></p>
          <div class="d-flex justify-content-between align-items-center">
            <span class="badge bg-primary"><?= (int)$p['jml_penerima'] ?> penerima</span>
            <strong>Rp <?= number_format((int)$p['nilai_per_penerima'],0,',','.') ?></strong>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$programs): ?><div class="col-12 text-center text-muted py-5">Belum ada program bansos tahun <?= (int)$tahun ?>.</div><?php endif; ?>
  </div>

  <?php if ($recipients): ?>
  <h5 class="mt-4 mb-3">Daftar Penerima</h5>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Program</th><th>Nama</th><th>NIK</th><th>Alamat</th><th>RT/RW</th></tr></thead>
    <tbody>
      <?php foreach ($recipients as $r): ?>
      <tr data-testid="bansos-recipient-row"><td><span class="badge bg-info-subtle text-info-emphasis"><?= e($r['program']) ?></span></td><td><strong><?= e($r['nama']) ?></strong></td><td><code class="small"><?= e(mask_nik($r['nik'])) ?></code></td><td class="small"><?= e($r['alamat'] ?: '-') ?></td><td class="small">RT <?= e($r['rt']??'-') ?> / RW <?= e($r['rw']??'-') ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>
  <?= pagination_links($recipientPg) ?>
  <?php endif; ?>
</div></section>
