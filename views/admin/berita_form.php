<?php
$isNew = $n === null;
$formOld = (array) ($_SESSION['form_old'] ?? []);
$raw = static function (string $key, $default = '') use ($formOld, $n) {
    return array_key_exists($key, $formOld) ? $formOld[$key] : ($n[$key] ?? $default);
};
$val = static fn(string $key, $default = '') => e($raw($key, $default));
$publishedRaw = (string) $raw('published_at', '');
$publishedValue = $publishedRaw !== '' && strtotime($publishedRaw) !== false
    ? date('Y-m-d\TH:i', strtotime($publishedRaw))
    : '';
?>
<form method="post" action="<?= url('admin/berita/' . ($isNew ? 'store' : 'update/' . $n['id'])) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="acard mb-3">
        <div class="acard-body">
          <div class="mb-3"><label class="form-label">Judul Berita *</label><input class="form-control" name="title" value="<?= $val('title') ?>" required data-testid="berita-title"></div>
          <div class="mb-3"><label class="form-label">Slug URL</label><input class="form-control" name="slug" value="<?= $val('slug') ?>" placeholder="otomatis dari judul jika dikosongkan"></div>
          <div class="mb-3"><label class="form-label">Ringkasan (excerpt)</label><textarea class="form-control" name="excerpt" rows="2"><?= $val('excerpt') ?></textarea></div>
          <div class="mb-1"><label class="form-label">Isi Berita *</label>
            <textarea class="form-control editor-init" name="content" rows="12" required data-testid="berita-content"><?= $val('content') ?></textarea>
            <div class="form-text">Gunakan toolbar untuk format teks & menyisipkan gambar (via URL gambar galeri).</div>
          </div>
        </div>
      </div>
      <div class="acard">
        <div class="acard-head"><h6 class="mb-0">SEO</h6></div>
        <div class="acard-body row g-3">
          <div class="col-md-6"><label class="form-label">Meta Title</label><input class="form-control" name="meta_title" value="<?= $val('meta_title') ?>"></div>
          <div class="col-md-6"><label class="form-label">Meta Description</label><input class="form-control" name="meta_description" value="<?= $val('meta_description') ?>"></div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0">Publikasi</h6></div>
        <div class="acard-body">
          <div class="mb-3"><label class="form-label">Status</label>
            <select class="form-select" name="status" data-testid="berita-status">
              <?php foreach (['draft' => 'Draft', 'published' => 'Publish Sekarang', 'scheduled' => 'Terjadwal'] as $sv => $sl): ?>
              <option value="<?= $sv ?>" <?= (string) $raw('status', 'published') === $sv ? 'selected' : '' ?>><?= $sl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Tanggal & Jam Terbit</label>
            <input class="form-control" type="datetime-local" name="published_at" value="<?= e($publishedValue) ?>">
            <div class="form-text">Wajib diisi untuk status Terjadwal.</div>
          </div>
          <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_featured" id="feat" <?= (int) $raw('is_featured', 0) === 1 ? 'checked' : '' ?>><label class="form-check-label small" for="feat">Berita Utama (featured)</label></div>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="allow_comments" id="cmt" <?= (int) $raw('allow_comments', $isNew ? 1 : 0) === 1 ? 'checked' : '' ?>><label class="form-check-label small" for="cmt">Izinkan komentar</label></div>
          <hr>
          <label class="form-label" for="sendPush">Kirim notifikasi push?</label>
          <select class="form-select" name="send_push" id="sendPush">
            <option value="0" <?= (string) $raw('send_push', '0') === '0' ? 'selected' : '' ?>>Tidak, simpan saja</option>
            <option value="1" <?= (string) $raw('send_push', '0') === '1' ? 'selected' : '' ?>>Ya, kirim setelah disimpan</option>
          </select>
          <div class="form-text">Push hanya dikirim bila status berita sudah dipublikasikan.</div>
        </div>
      </div>
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0">Kategori & Tag</h6></div>
        <div class="acard-body">
          <div class="mb-3"><label class="form-label">Kategori</label>
            <select class="form-select" name="category_id">
              <option value="">—</option>
              <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (string) $raw('category_id', '') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div><label class="form-label">Tag (pisahkan koma)</label><input class="form-control" name="tags" value="<?= e((string) ($formOld['tags'] ?? $tags)) ?>" placeholder="musdes, dana desa"></div>
        </div>
      </div>
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0">Thumbnail</h6></div>
        <div class="acard-body">
          <?php if ($n && $n['thumbnail']): ?><img src="<?= e(upload_url($n['thumbnail'])) ?>" class="w-100 rounded mb-2" alt=""><?php endif; ?>
          <input class="form-control" type="file" name="thumbnail" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
      <div class="d-grid gap-2">
        <button class="btn btn-primary" data-testid="berita-save"><i class="bi bi-check-lg me-1"></i>Simpan Berita</button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/berita') ?>">Batal</a>
      </div>
    </div>
  </div>
</form>
