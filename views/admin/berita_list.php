<div class="acard">
  <div class="acard-head">
    <form class="d-flex gap-2" method="get">
      <input class="form-control form-control-sm" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari judul...">
      <select class="form-select form-select-sm" name="status" style="width:140px">
        <option value="">Semua Status</option>
        <?php foreach (['draft', 'published', 'scheduled'] as $s): ?><option value="<?= $s ?>" <?= ($_GET['status'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <div class="d-flex gap-2">
      <?php if ($trashed): ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/berita') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
      <?php else: ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/berita/trash') ?>"><i class="bi bi-trash3"></i></a>
      <a class="btn btn-sm btn-primary" href="<?= url('admin/berita/create') ?>" data-testid="berita-add"><i class="bi bi-plus-lg me-1"></i>Tulis Berita</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th></th><th>Judul</th><th>Kategori</th><th>Status</th><th>Dibaca</th><th>Terbit</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($pg['data'] as $n): ?>
          <tr>
            <td><?php if ($n['thumbnail']): ?><img class="thumb" src="<?= e(upload_url($n['thumbnail'])) ?>" alt=""><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
            <td><strong class="small"><?= e(excerpt($n['title'], 60)) ?></strong>
              <?php if ($n['is_featured']): ?><span class="badge text-bg-warning text-dark ms-1">Utama</span><?php endif; ?>
              <br><small class="text-muted">/berita/<?= e($n['slug']) ?></small></td>
            <td class="small"><?= e($n['category'] ?: '-') ?></td>
            <td><?= status_badge($n['status']) ?></td>
            <td class="small"><?= (int) $n['views'] ?></td>
            <td class="small"><?= $n['published_at'] ? tgl_id($n['published_at']) : '-' ?></td>
            <td class="text-end text-nowrap">
              <?php if ($trashed): ?>
              <form class="d-inline" method="post" action="<?= url('admin/berita/restore/' . $n['id']) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i></button></form>
              <?php else: ?>
              <a class="btn btn-sm btn-outline-secondary" href="<?= url('berita/' . $n['slug']) ?>" target="_blank" title="Lihat"><i class="bi bi-eye"></i></a>
              <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/berita/edit/' . $n['id']) ?>"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= url('admin/berita/delete/' . $n['id']) ?>" data-confirm="Pindahkan berita ke sampah?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada berita.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-end"><?= pagination_links($pg) ?></div>
</div>
