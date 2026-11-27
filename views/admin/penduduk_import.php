<div class="row g-3">
  <div class="col-lg-8">
    <?php if (!$preview): ?>
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Data Penduduk dari Excel</h6></div>
      <div class="acard-body">
        <div class="alert alert-info small">
          <strong>Langkah import:</strong>
          <ol class="mb-0">
            <li>Unduh template Excel di samping.</li>
            <li>Isi data sesuai kolom (NIK & No KK 16 digit, tanggal format <code>YYYY-MM-DD</code> atau <code>DD/MM/YYYY</code>).</li>
            <li>Kolom DUSUN/RW/RT diisi sesuai nama wilayah yang ada di menu Data Wilayah.</li>
            <li>Upload file, periksa preview, lalu simpan.</li>
          </ol>
        </div>
        <form method="post" action="<?= url('admin/penduduk/preview') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">File Excel (.xlsx) *</label>
            <input class="form-control" type="file" name="file" accept=".xlsx" required data-testid="import-file">
          </div>
          <button class="btn btn-primary" data-testid="import-preview-btn"><i class="bi bi-eye me-1"></i>Upload & Preview</button>
        </form>
      </div>
    </div>
    <?php else: ?>
    <div class="acard">
      <div class="acard-head">
        <h6 class="mb-0">Preview Import</h6>
        <div>
          <span class="badge text-bg-success">Valid: <?= count($preview['valid']) ?></span>
          <span class="badge text-bg-danger">Tidak valid: <?= count($preview['invalid']) ?></span>
        </div>
      </div>
      <div class="acard-body">
        <?php if ($preview['invalid']): ?>
        <h6 class="small text-danger">Baris bermasalah (tidak akan diimport):</h6>
        <div class="table-responsive mb-3" style="max-height:220px">
          <table class="table table-admin table-sm">
            <thead><tr><th>Baris</th><th>NIK</th><th>Nama</th><th>Masalah</th></tr></thead>
            <tbody>
              <?php foreach ($preview['invalid'] as $iv): ?>
              <tr class="table-danger"><td><?= (int) $iv['line'] ?></td><td><?= e($iv['nik']) ?></td><td><?= e($iv['nama']) ?></td><td class="small"><?= e(implode(', ', $iv['errors'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
        <h6 class="small text-success">Data valid (maks 50 baris pertama ditampilkan):</h6>
        <div class="table-responsive mb-3" style="max-height:300px">
          <table class="table table-admin table-sm">
            <thead><tr><th>NIK</th><th>Nama</th><th>L/P</th><th>Tgl Lahir</th><th>Wilayah</th><th>Status di DB</th></tr></thead>
            <tbody>
              <?php foreach (array_slice($preview['valid'], 0, 50) as $v): ?>
              <tr>
                <td class="font-monospace small"><?= e($v['nik']) ?></td><td><?= e($v['nama']) ?></td>
                <td><?= e($v['jenis_kelamin']) ?></td><td><?= e($v['tanggal_lahir'] ?: '-') ?></td>
                <td class="small"><?= e($v['dusun']) ?> <?= e($v['rw']) ?> <?= e($v['rt']) ?></td>
                <td><?= $v['exists'] ? '<span class="badge text-bg-warning text-dark">NIK sudah ada</span>' : '<span class="badge text-bg-success">Baru</span>' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <form method="post" action="<?= url('admin/penduduk/import-simpan') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($preview['token']) ?>">
          <div class="mb-3">
            <label class="form-label">Jika NIK sudah ada di database:</label>
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="skip" id="mSkip" checked><label class="form-check-label small" for="mSkip">Lewati (jangan ubah data lama)</label></div>
            <div class="form-check"><input class="form-check-input" type="radio" name="mode" value="update" id="mUpd"><label class="form-check-label small" for="mUpd">Update data lama dengan data dari Excel</label></div>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-success" <?= !$preview['valid'] ? 'disabled' : '' ?> data-testid="import-save-btn"><i class="bi bi-database-add me-1"></i>Simpan <?= count($preview['valid']) ?> Data</button>
            <a class="btn btn-outline-secondary" href="<?= url('admin/penduduk/import') ?>">Batal / Upload Ulang</a>
          </div>
          <small class="text-muted d-block mt-2"><i class="bi bi-shield-check me-1"></i>Import berjalan dalam transaksi — jika terjadi kesalahan, seluruh perubahan dibatalkan otomatis (rollback).</small>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-download me-1"></i>Template</h6></div>
      <div class="acard-body">
        <p class="small text-muted">Template berisi header kolom + 1 baris contoh data.</p>
        <a class="btn btn-outline-success w-100" href="<?= url('admin/penduduk/template') ?>" data-testid="import-template-btn"><i class="bi bi-file-earmark-excel me-1"></i>Unduh Template Excel</a>
      </div>
    </div>
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Riwayat Import</h6></div>
      <div class="acard-body p-0">
        <table class="table table-admin mb-0"><tbody>
          <?php foreach ($logs as $l): ?>
          <tr><td class="small"><?= e($l['filename']) ?><br><small class="text-muted"><?= e($l['note']) ?> · <?= e($l['user_name']) ?> · <?= tgl_id($l['created_at'], true) ?></small></td></tr>
          <?php endforeach; ?>
          <?php if (!$logs): ?><tr><td class="text-muted small p-3">Belum ada import.</td></tr><?php endif; ?>
        </tbody></table>
      </div>
    </div>
  </div>
</div>
