<div class="acard">
  <div class="acard-head">
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari tiket / isi...">
      <select class="form-select form-select-sm" name="status" style="width:140px">
        <option value="">Semua Status</option>
        <?php foreach (['diterima', 'diproses', 'selesai', 'ditolak'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm" name="prioritas" style="width:130px">
        <option value="">Prioritas: Semua</option>
        <option value="1" <?= ($_GET['prioritas'] ?? '') === '1' ? 'selected' : '' ?>>Prioritas</option>
        <option value="0" <?= ($_GET['prioritas'] ?? '') === '0' ? 'selected' : '' ?>>Normal</option>
      </select>
      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <a class="btn btn-sm btn-outline-success" href="<?= url('admin/pengaduan/export') ?>"><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th>Tiket</th><th>Pelapor</th><th>Kategori</th><th>Isi</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($pg['data'] as $c): ?>
          <tr class="<?= $c['priority'] ? 'table-warning' : '' ?>">
            <td><a href="<?= url('admin/pengaduan/detail/' . $c['id']) ?>" class="fw-semibold small"><?= e($c['ticket']) ?></a>
              <?php if ($c['priority']): ?><br><span class="badge text-bg-danger">Prioritas</span><?php endif; ?></td>
            <td class="small"><?= $c['is_anonymous'] ? '<em class="text-muted">(dirahasiakan)</em>' : e($c['nama']) ?></td>
            <td class="small"><?= e($c['category']) ?></td>
            <td class="small"><?= e(excerpt($c['content'], 50)) ?></td>
            <td><?= status_badge($c['status']) ?></td>
            <td class="small"><?= tgl_id($c['created_at']) ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/pengaduan/detail/' . $c['id']) ?>" data-testid="pengaduan-detail-<?= $c['id'] ?>"><i class="bi bi-eye"></i></a>
              <form class="d-inline" method="post" action="<?= url('admin/pengaduan/delete/' . $c['id']) ?>" data-confirm="Tutup/hapus tiket pengaduan ini?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i></button></form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada pengaduan.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-end"><?= pagination_links($pg) ?></div>
</div>
