<?php $selectedProgram = null; foreach ($programs as $p) if ((int)$p['id'] === $programId) $selectedProgram = $p; ?>
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
  <form method="get" class="d-flex gap-2 align-items-center">
    <label class="small text-muted mb-0">Tahun:</label>
    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()" data-testid="bansos-year">
      <?php $years = array_unique(array_merge([date('Y')-1, date('Y'), date('Y')+1], array_column($tahunList,'tahun'))); rsort($years); foreach ($years as $y): ?>
      <option value="<?= (int)$y ?>" <?= (int)$tahun===(int)$y?'selected':'' ?>><?= (int)$y ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <a href="<?= url('admin/bansos/cetak?tahun='.$tahun) ?>" target="_blank" class="btn btn-sm btn-outline-primary ms-auto"><i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF <?= (int)$tahun ?></a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-collection me-2 text-primary"></i>Program Bansos <?= (int)$tahun ?></h6></div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/bansos/simpan_program') ?>" class="row g-2 mb-3" data-testid="bansos-program-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="tahun" value="<?= (int)$tahun ?>">
          <div class="col-12"><input name="nama" placeholder="Nama program (PKH / BPNT / BLT-DD / ...)" class="form-control form-control-sm" required></div>
          <div class="col-7"><input name="sumber_dana" placeholder="Sumber dana (APBN/APBD/DD)" class="form-control form-control-sm"></div>
          <div class="col-5"><input name="nilai_per_penerima" placeholder="Nilai (Rp)" class="form-control form-control-sm" inputmode="numeric"></div>
          <div class="col-12"><textarea name="deskripsi" placeholder="Deskripsi singkat" class="form-control form-control-sm" rows="2"></textarea></div>
          <div class="col-12 form-check ms-2"><input type="checkbox" class="form-check-input" name="is_active" checked id="ba"> <label for="ba" class="form-check-label small">Program aktif</label></div>
          <div class="col-12"><button class="btn btn-primary btn-sm w-100" data-testid="bansos-add-program"><i class="bi bi-plus-lg me-1"></i>Tambah Program</button></div>
        </form>
        <?php foreach ($programs as $p): ?>
        <div class="d-flex justify-content-between align-items-start p-2 border-bottom" data-testid="bansos-program-item">
          <a href="<?= url('admin/bansos?tahun='.$tahun.'&program='.$p['id']) ?>" class="text-decoration-none flex-grow-1">
            <strong><?= e($p['nama']) ?></strong> <span class="badge bg-primary-subtle text-primary-emphasis"><?= (int)$p['jml'] ?> penerima</span>
            <div class="small text-muted"><?= e($p['sumber_dana'] ?: '-') ?> · Rp <?= number_format((int)$p['nilai_per_penerima'],0,',','.') ?></div>
          </a>
          <form method="post" action="<?= url('admin/bansos/hapus_program') ?>" onsubmit="return confirm('Hapus program dan semua penerimanya?')">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
            <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
          </form>
        </div>
        <?php endforeach; ?>
        <?php if (!$programs): ?><p class="text-muted small text-center py-3">Belum ada program tahun <?= (int)$tahun ?>.</p><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Penerima <?= $selectedProgram ? '· ' . e($selectedProgram['nama']) : '' ?></h6></div>
      <div class="acard-body">
        <?php if ($selectedProgram): ?>
        <form method="post" action="<?= url('admin/bansos/simpan_penerima') ?>" class="row g-2 mb-3" data-testid="bansos-recipient-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="program_id" value="<?= (int)$selectedProgram['id'] ?>">
          <div class="col-md-6"><input name="nama" placeholder="Nama lengkap" class="form-control form-control-sm" required></div>
          <div class="col-md-6"><input name="nik" placeholder="NIK (16 digit)" class="form-control form-control-sm" maxlength="16"></div>
          <div class="col-md-8"><input name="alamat" placeholder="Alamat" class="form-control form-control-sm"></div>
          <div class="col-md-2"><input name="rt" placeholder="RT" class="form-control form-control-sm" maxlength="5"></div>
          <div class="col-md-2"><input name="rw" placeholder="RW" class="form-control form-control-sm" maxlength="5"></div>
          <div class="col-12"><input name="keterangan" placeholder="Keterangan" class="form-control form-control-sm"></div>
          <div class="col-12"><button class="btn btn-primary btn-sm w-100" data-testid="bansos-add-recipient"><i class="bi bi-plus-lg me-1"></i>Tambah Penerima</button></div>
        </form>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr><th>Nama</th><th>NIK</th><th>Alamat</th><th>RT/RW</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($recipients as $r): ?>
              <tr>
                <td><strong><?= e($r['nama']) ?></strong></td>
                <td><code class="small"><?= e($r['nik'] ?: '-') ?></code></td>
                <td class="small"><?= e($r['alamat'] ?: '-') ?></td>
                <td class="small"><?= e(($r['rt']??'').'/'.($r['rw']??'')) ?></td>
                <td class="text-end">
                  <form method="post" action="<?= url('admin/bansos/hapus_penerima') ?>" onsubmit="return confirm('Hapus penerima?')">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="program_id" value="<?= (int)$selectedProgram['id'] ?>">
                    <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$recipients): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada penerima.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p class="text-muted text-center py-4">← Pilih program di sebelah kiri untuk mengelola penerima.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
