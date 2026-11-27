<div class="acard">
  <div class="acard-head">
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input class="form-control form-control-sm" style="width:200px" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari aktivitas..." data-testid="audit-search">
      <select class="form-select form-select-sm" style="width:170px" name="user">
        <option value="">Semua User</option>
        <?php foreach ($users as $us): ?>
        <option value="<?= $us['id'] ?>" <?= ($_GET['user'] ?? '') == $us['id'] ? 'selected' : '' ?>><?= e($us['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input class="form-control form-control-sm" style="width:160px" type="date" name="tanggal" value="<?= e($_GET['tanggal'] ?? '') ?>">
      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/audit/import') ?>" data-testid="audit-import-log-btn"><i class="bi bi-file-earmark-arrow-up me-1"></i>Log Import</a>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Modul</th><th>Keterangan</th><th>IP</th></tr></thead>
        <tbody>
          <?php $badges = ['login' => 'success', 'logout' => 'secondary', 'tambah' => 'primary', 'edit' => 'warning', 'hapus' => 'danger', 'import' => 'info', 'export' => 'info']; ?>
          <?php foreach ($pg['data'] as $row): ?>
          <tr>
            <td class="small text-nowrap"><?= tgl_id($row['created_at'], true) ?></td>
            <td><?= e($row['user_name'] ?? 'Sistem') ?></td>
            <td><span class="badge text-bg-<?= $badges[$row['action']] ?? 'light border' ?>"><?= e($row['action']) ?></span></td>
            <td><span class="badge text-bg-light border"><?= e($row['module'] ?? '-') ?></span></td>
            <td class="small"><?= e($row['description']) ?></td>
            <td class="small text-muted"><?= e($row['ip_address']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Total <?= (int) $pg['total'] ?> aktivitas</small>
    <?= pagination_links($pg) ?>
  </div>
</div>
