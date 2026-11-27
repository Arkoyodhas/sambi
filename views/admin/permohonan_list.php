<div class="acard">
  <div class="acard-head">
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari tiket / nama / NIK...">
      <select class="form-select form-select-sm" name="status" style="width:140px">
        <option value="">Semua Status</option>
        <?php foreach (['baru', 'diproses', 'selesai', 'ditolak'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <a class="btn btn-sm btn-outline-success" href="<?= url('admin/permohonan/export') ?>"><i class="bi bi-file-earmark-excel me-1"></i>Export Laporan</a>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th>Tiket</th><th>Layanan</th><th>Pemohon</th><th>Keperluan</th><th>Status</th><th>Tanggal</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($pg['data'] as $r): ?>
          <tr>
            <td><a href="<?= url('admin/permohonan/detail/' . $r['id']) ?>" class="fw-semibold small"><?= e($r['ticket']) ?></a></td>
            <td class="small"><?= e($r['service_name']) ?></td>
            <td class="small"><?= e($r['nama']) ?><br><small class="text-muted"><?= e($r['no_hp'] ?: '-') ?></small></td>
            <td class="small"><?= e(excerpt($r['keperluan'], 40)) ?></td>
            <td><?= status_badge($r['status']) ?></td>
            <td class="small"><?= tgl_id($r['created_at']) ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/permohonan/detail/' . $r['id']) ?>" data-testid="permohonan-detail-<?= $r['id'] ?>"><i class="bi bi-eye"></i></a>
              <form class="d-inline" method="post" action="<?= url('admin/permohonan/delete/' . $r['id']) ?>" data-confirm="Hapus permohonan ini?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada permohonan.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-end"><?= pagination_links($pg) ?></div>
</div>
