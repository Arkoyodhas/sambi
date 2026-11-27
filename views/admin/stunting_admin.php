<?php
$statusLabel = ['normal'=>'Normal','pendek'=>'Pendek','sangat_pendek'=>'Sangat Pendek','beresiko'=>'Beresiko'];
$statusColor = ['normal'=>'success','pendek'=>'warning','sangat_pendek'=>'danger','beresiko'=>'info'];
$totalYear = array_sum(array_column($byStatus,'total'));
$totalStunted = 0; foreach ($byStatus as $s) if ($s['status'] !== 'normal') $totalStunted += (int) $s['total'];
?>
<div class="row g-3 mb-3">
  <?php foreach ([
    ['bi-people', $totalYear, 'Total Diperiksa '.$tahun, 'primary'],
    ['bi-exclamation-triangle', $totalStunted, 'Kasus Stunting/Beresiko', 'danger'],
    ['bi-map', count($byRt), 'RT dgn Data', 'success'],
  ] as [$ic, $val, $lab, $col]): ?>
  <div class="col-md-4 col-6"><div class="stat-tile"><div class="ic text-<?= $col ?>"><i class="bi <?= $ic ?>"></i></div><div><div class="val"><?= number_format((int)$val,0,',','.') ?></div><div class="lab"><?= $lab ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <form method="get" class="d-flex gap-2 align-items-center">
    <label class="small text-muted mb-0">Tahun:</label>
    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
      <?php $years = array_unique(array_merge([date('Y')-1, date('Y')], array_column($tahunList,'tahun'))); rsort($years); foreach ($years as $y): ?>
      <option value="<?= (int)$y ?>" <?= (int)$tahun===(int)$y?'selected':'' ?>><?= (int)$y ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <a href="<?= url('admin/stunting/cetak?tahun='.$tahun) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-auto"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF <?= (int)$tahun ?></a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-pencil-square me-2 text-primary"></i>Input Data Pemeriksaan</h6></div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/stunting/simpan') ?>" class="row g-2" data-testid="stunting-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="col-md-8"><input name="nama_inisial" placeholder="Inisial (mis: A.R.)" class="form-control form-control-sm" required maxlength="20"></div>
          <div class="col-md-4">
            <select name="jenis_kelamin" class="form-select form-select-sm"><option value="L">Laki-laki</option><option value="P">Perempuan</option></select>
          </div>
          <div class="col-md-6"><label class="small text-muted mb-0">Tanggal Lahir</label><input type="date" name="tanggal_lahir" class="form-control form-control-sm" required></div>
          <div class="col-md-6"><label class="small text-muted mb-0">Tanggal Periksa</label><input type="date" name="tanggal_pemeriksaan" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
          <div class="col-md-3"><input name="rt" placeholder="RT" class="form-control form-control-sm"></div>
          <div class="col-md-3"><input name="rw" placeholder="RW" class="form-control form-control-sm"></div>
          <div class="col-md-3"><input type="number" step="0.1" name="tinggi_cm" placeholder="Tinggi (cm)" class="form-control form-control-sm"></div>
          <div class="col-md-3"><input type="number" step="0.1" name="berat_kg" placeholder="Berat (kg)" class="form-control form-control-sm"></div>
          <div class="col-12">
            <select name="status" class="form-select form-select-sm" data-testid="stunting-status">
              <?php foreach ($statusLabel as $sv => $sl): ?><option value="<?= $sv ?>"><?= $sl ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-12"><input name="tindakan" placeholder="Tindakan / rekomendasi" class="form-control form-control-sm"></div>
          <div class="col-12"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-check2 me-1"></i>Simpan Pemeriksaan</button></div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-map me-2 text-primary"></i>Ringkasan per RT (<?= (int)$tahun ?>)</h6></div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr><th class="ps-3">RT/RW</th><th class="text-end">Total Balita</th><th class="text-end">Stunting/Beresiko</th><th>Persentase</th></tr></thead>
            <tbody>
              <?php foreach ($byRt as $r): $pct = $r['total']>0 ? round($r['stunting']/$r['total']*100,1) : 0; ?>
              <tr>
                <td class="ps-3">RT <?= e($r['rt'] ?: '-') ?> / RW <?= e($r['rw'] ?: '-') ?></td>
                <td class="text-end"><?= (int)$r['total'] ?></td>
                <td class="text-end"><strong class="text-danger"><?= (int)$r['stunting'] ?></strong></td>
                <td><div class="progress" style="height:8px"><div class="progress-bar bg-<?= $pct>15?'danger':($pct>5?'warning':'success') ?>" style="width:<?= $pct ?>%"></div></div><span class="small"><?= $pct ?>%</span></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$byRt): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada data.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="acard mt-3">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>Daftar Pemeriksaan <?= (int)$tahun ?></h6></div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="small text-muted"><tr><th class="ps-3">Tgl Periksa</th><th>Inisial</th><th>Umur (bln)</th><th>JK</th><th>RT/RW</th><th class="text-end">TB/BB</th><th>Status</th><th>Tindakan</th><th class="text-end pe-3">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($rows as $r):
            $umur = round((strtotime($r['tanggal_pemeriksaan']) - strtotime($r['tanggal_lahir'])) / (30.44 * 86400)); ?>
          <tr data-testid="stunting-row">
            <td class="ps-3 small"><?= e(date('d/m/Y', strtotime($r['tanggal_pemeriksaan']))) ?></td>
            <td><strong><?= e($r['nama_inisial']) ?></strong></td>
            <td><?= $umur ?></td>
            <td><?= e($r['jenis_kelamin']) ?></td>
            <td class="small"><?= e(($r['rt']??'').'/'.($r['rw']??'')) ?></td>
            <td class="text-end small"><?= e($r['tinggi_cm']) ?>cm / <?= e($r['berat_kg']) ?>kg</td>
            <td><span class="badge bg-<?= $statusColor[$r['status']] ?>-subtle text-<?= $statusColor[$r['status']] ?>-emphasis"><?= $statusLabel[$r['status']] ?></span></td>
            <td class="small text-truncate" style="max-width:200px"><?= e($r['tindakan'] ?: '-') ?></td>
            <td class="text-end pe-3">
              <form method="post" action="<?= url('admin/stunting/hapus') ?>" onsubmit="return confirm('Hapus data ini?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-3">Belum ada data pemeriksaan tahun <?= (int)$tahun ?>.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
