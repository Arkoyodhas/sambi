<div class="acard">
  <div class="acard-head">
    <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-up me-2"></i>Riwayat Import Data Penduduk</h6>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/audit') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke Audit Log</a>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th>Waktu</th><th>User</th><th>File</th><th>Total Baris</th><th>Valid</th><th>Gagal</th><th>Mode</th><th>Catatan</th></tr></thead>
        <tbody>
          <?php foreach ($pg['data'] as $row): ?>
          <tr>
            <td class="small text-nowrap"><?= tgl_id($row['created_at'], true) ?></td>
            <td><?= e($row['user_name'] ?? '-') ?></td>
            <td class="small"><code><?= e($row['filename']) ?></code></td>
            <td><?= (int) $row['total_rows'] ?></td>
            <td><span class="badge text-bg-success"><?= (int) $row['valid_rows'] ?></span></td>
            <td><span class="badge text-bg-<?= $row['invalid_rows'] > 0 ? 'danger' : 'secondary' ?>"><?= (int) $row['invalid_rows'] ?></span></td>
            <td><span class="badge text-bg-light border text-uppercase"><?= e($row['mode']) ?></span></td>
            <td class="small text-muted"><?= e(excerpt($row['note'] ?? '', 80)) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="8" class="text-center text-muted py-4">Belum ada riwayat import.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Total <?= (int) $pg['total'] ?> import</small>
    <?= pagination_links($pg) ?>
  </div>
</div>
