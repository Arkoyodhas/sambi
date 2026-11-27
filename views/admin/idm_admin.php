<?php
function idm_klasifikasi(float $idm): array {
  if ($idm > 0.8155) return ['Mandiri', 'success'];
  if ($idm > 0.7072) return ['Maju', 'info'];
  if ($idm > 0.5989) return ['Berkembang', 'warning'];
  if ($idm > 0.4907) return ['Tertinggal', 'danger'];
  return ['Sangat Tertinggal', 'secondary'];
}
$tahunNow = (int) date('Y');
$existing = []; foreach ($rows as $r) $existing[$r['tahun']] = $r;
$edit = $existing[$tahunNow] ?? ['tahun'=>$tahunNow,'iks'=>0,'ike'=>0,'ikl'=>0,'catatan'=>''];
?>
<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Input Skor IDM</h6></div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/idm/simpan') ?>" data-testid="idm-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="mb-2"><label class="form-label small">Tahun</label><input type="number" name="tahun" class="form-control form-control-sm" value="<?= (int)$edit['tahun'] ?>" min="2000" max="2100" required></div>
          <div class="mb-2"><label class="form-label small">IKS · Indeks Ketahanan Sosial (0-1)</label><input type="number" name="iks" step="0.0001" min="0" max="1" class="form-control form-control-sm" value="<?= number_format((float)$edit['iks'],4,'.','') ?>" required data-testid="idm-iks"></div>
          <div class="mb-2"><label class="form-label small">IKE · Indeks Ketahanan Ekonomi (0-1)</label><input type="number" name="ike" step="0.0001" min="0" max="1" class="form-control form-control-sm" value="<?= number_format((float)$edit['ike'],4,'.','') ?>" required data-testid="idm-ike"></div>
          <div class="mb-2"><label class="form-label small">IKL · Indeks Ketahanan Lingkungan (0-1)</label><input type="number" name="ikl" step="0.0001" min="0" max="1" class="form-control form-control-sm" value="<?= number_format((float)$edit['ikl'],4,'.','') ?>" required data-testid="idm-ikl"></div>
          <div class="mb-3"><label class="form-label small">Catatan</label><textarea name="catatan" class="form-control form-control-sm" rows="3"><?= e($edit['catatan'] ?? '') ?></textarea></div>
          <button class="btn btn-primary w-100" data-testid="idm-save"><i class="bi bi-check2 me-1"></i>Simpan Data IDM</button>
        </form>
        <p class="small text-muted mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Skor IDM dihitung otomatis: <code>(IKS + IKE + IKL) ÷ 3</code>. Klasifikasi: Sangat Tertinggal ≤ 0,4907 · Tertinggal ≤ 0,5989 · Berkembang ≤ 0,7072 · Maju ≤ 0,8155 · Mandiri &gt; 0,8155.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="acard h-100">
      <div class="acard-head d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat IDM</h6>
        <a href="<?= url('transparansi/cetak?jenis=idm') ?>" class="btn btn-sm btn-outline-primary" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF</a>
      </div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr><th class="ps-3">Tahun</th><th class="text-end">IKS</th><th class="text-end">IKE</th><th class="text-end">IKL</th><th class="text-end">IDM</th><th>Klasifikasi</th><th class="text-end pe-3">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): [$kl, $col] = idm_klasifikasi((float)$r['idm']); ?>
              <tr data-testid="idm-row">
                <td class="ps-3"><strong><?= (int)$r['tahun'] ?></strong></td>
                <td class="text-end"><?= number_format((float)$r['iks'],4) ?></td>
                <td class="text-end"><?= number_format((float)$r['ike'],4) ?></td>
                <td class="text-end"><?= number_format((float)$r['ikl'],4) ?></td>
                <td class="text-end"><strong><?= number_format((float)$r['idm'],4) ?></strong></td>
                <td><span class="badge bg-<?= $col ?>-subtle text-<?= $col ?>-emphasis"><?= $kl ?></span></td>
                <td class="text-end pe-3">
                  <form method="post" action="<?= url('admin/idm/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus data tahun <?= (int)$r['tahun'] ?>?')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada data IDM.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
