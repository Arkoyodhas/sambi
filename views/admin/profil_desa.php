<div class="acard">
  <div class="acard-head">
    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Konten Profil Desa</h6>
    <small class="text-muted">Sejarah, Visi Misi, Geografis, Demografi, dan Struktur</small>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead><tr><th style="width:60px">Urutan</th><th>Bagian</th><th>Ringkasan Konten</th><th>Gambar</th><th>Terakhir Diubah</th><th class="text-end">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($sections as $sec): ?>
          <tr data-testid="profil-row-<?= $sec['id'] ?>">
            <td><span class="badge text-bg-light border"><?= (int) $sec['sort_order'] ?></span></td>
            <td><strong><?= e($sec['title']) ?></strong><br><code class="small text-muted"><?= e($sec['section_key']) ?></code></td>
            <td class="small"><?= e(excerpt($sec['content'], 90)) ?></td>
            <td><?= $sec['image'] ? '<img class="thumb" src="' . e(upload_url($sec['image'])) . '" alt="">' : '<span class="text-muted">—</span>' ?></td>
            <td class="small text-muted"><?= tgl_id($sec['updated_at'], true) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/profil-desa/edit/' . $sec['id']) ?>" data-testid="profil-edit-<?= $sec['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</a></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$sections): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada konten profil.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
