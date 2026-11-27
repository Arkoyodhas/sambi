<?php
$isNew = $m === null;
$formOld = (array) ($_SESSION['form_old'] ?? []);
$raw = static function (string $key, $default = '') use ($formOld, $m) {
    return array_key_exists($key, $formOld) ? $formOld[$key] : ($m[$key] ?? $default);
};
$val = static fn(string $key, $default = '') => e($raw($key, $default));
?>
<div class="acard" style="max-width:640px">
  <div class="acard-body">
    <form method="post" action="<?= url('admin/menu/' . ($isNew ? 'store' : 'update/' . $m['id'])) ?>" data-testid="menu-form">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Judul Menu *</label><input class="form-control" name="title" value="<?= $val('title') ?>" required data-testid="menu-title"></div>
      <div class="mb-3">
        <label class="form-label">URL *</label>
        <input class="form-control" name="url" value="<?= $val('url', '#') ?>" required data-testid="menu-url">
        <div class="form-text">Contoh: <code>berita</code>, <code>profil</code>, <code>halaman/sejarah</code>, atau <code>https://situs-lain.id</code></div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label">Ikon (Bootstrap Icons)</label><input class="form-control" name="icon" value="<?= $val('icon') ?>" placeholder="bi-house"></div>
        <div class="col-md-6"><label class="form-label">Target</label>
          <select class="form-select" name="target">
            <option value="_self" <?= (string) $raw('target', '_self') === '_self' ? 'selected' : '' ?>>Tab yang sama</option>
            <option value="_blank" <?= (string) $raw('target', '') === '_blank' ? 'selected' : '' ?>>Tab baru</option>
          </select>
        </div>
      </div>
      <div class="row g-3 mb-3">
        <div class="col-md-4"><label class="form-label">Lokasi</label>
          <select class="form-select" name="location" data-testid="menu-location">
            <option value="main" <?= (string) $raw('location', 'main') === 'main' ? 'selected' : '' ?>>Navigasi Utama</option>
            <option value="footer" <?= (string) $raw('location', '') === 'footer' ? 'selected' : '' ?>>Footer</option>
          </select>
        </div>
        <div class="col-md-4"><label class="form-label">Induk Menu (submenu)</label>
          <select class="form-select" name="parent_id">
            <option value="0">— Tanpa Induk —</option>
            <?php foreach ($parents as $p): if (!$isNew && (int) $p['id'] === (int) $m['id']) continue; ?>
            <option value="<?= $p['id'] ?>" <?= (string) $raw('parent_id', '') === (string) $p['id'] ? 'selected' : '' ?>><?= e($p['title']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4"><label class="form-label">Urutan</label><input class="form-control" type="number" name="sort_order" value="<?= $val('sort_order', '99') ?>"></div>
      </div>
      <div class="form-check form-switch mb-4">
        <input class="form-check-input" type="checkbox" name="is_active" id="act" <?= (int) $raw('is_active', $isNew ? 1 : 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="act">Menu aktif (tampil di website)</label>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-primary" data-testid="menu-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan Menu</button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/menu') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
