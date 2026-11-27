<form method="post" action="<?= url('admin/profil-desa/update/' . $s['id']) ?>" enctype="multipart/form-data" data-testid="profil-form">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="acard">
        <div class="acard-body">
          <div class="mb-3"><label class="form-label">Judul Bagian *</label><input class="form-control" name="title" value="<?= e($s['title']) ?>" required data-testid="profil-title"></div>
          <div class="mb-1"><label class="form-label">Isi Konten</label>
            <textarea class="form-control editor-init" name="content" rows="14" data-testid="profil-content"><?= e($s['content']) ?></textarea>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0">Pengaturan</h6></div>
        <div class="acard-body">
          <div class="mb-3"><label class="form-label">Kunci Bagian</label><input class="form-control" value="<?= e($s['section_key']) ?>" disabled></div>
          <div class="mb-3"><label class="form-label">Urutan Tampil</label><input class="form-control" type="number" name="sort_order" value="<?= (int) $s['sort_order'] ?>"></div>
          <div>
            <label class="form-label">Gambar Ilustrasi</label>
            <?php if ($s['image']): ?><img src="<?= e(upload_url($s['image'])) ?>" class="w-100 rounded mb-2" alt=""><?php endif; ?>
            <input class="form-control" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
          </div>
        </div>
      </div>
      <div class="d-grid gap-2">
        <button class="btn btn-primary" data-testid="profil-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan Konten</button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/profil-desa') ?>">Batal</a>
      </div>
    </div>
  </div>
</form>
