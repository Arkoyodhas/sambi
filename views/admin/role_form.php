<?php
$isNew = $role === null;
$old = $_SESSION['old_role_form'] ?? [];
unset($_SESSION['old_role_form']);
$value = static fn(string $key, string $default = ''): string => e($old[$key] ?? $role[$key] ?? $default);
$checkedIds = array_map('intval', $old['permission_ids'] ?? $selectedPermissions);
?>
<form method="post" action="<?= url('admin/roles/' . ($isNew ? 'store' : 'update/' . $role['id'])) ?>" data-testid="role-form">
  <?= csrf_field() ?>

  <?php if ($isProtected): ?>
    <div class="alert alert-warning">
      <i class="bi bi-lock-fill me-2"></i>Role Super Admin adalah role inti dengan akses penuh dan tidak dapat diubah.
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-xl-4">
      <div class="acard h-100">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Informasi Role</h6></div>
        <div class="acard-body">
          <div class="mb-3">
            <label class="form-label" for="roleName">Nama Role *</label>
            <input class="form-control" id="roleName" name="name" value="<?= $value('name') ?>" minlength="3" maxlength="100" required <?= $isProtected ? 'disabled' : '' ?>>
          </div>
          <div class="mb-3">
            <label class="form-label" for="roleSlug">Slug</label>
            <input class="form-control" id="roleSlug" name="slug" value="<?= $value('slug') ?>" maxlength="100" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="otomatis-dari-nama" <?= $isProtected ? 'disabled' : '' ?>>
            <div class="form-text">Huruf kecil, angka, dan tanda hubung. Kosongkan agar dibuat otomatis.</div>
          </div>
          <div class="mb-0">
            <label class="form-label" for="roleDescription">Deskripsi</label>
            <textarea class="form-control" id="roleDescription" name="description" rows="4" maxlength="255" <?= $isProtected ? 'disabled' : '' ?>><?= $value('description') ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-8">
      <div class="acard">
        <div class="acard-head flex-wrap gap-2">
          <div>
            <h6 class="mb-1"><i class="bi bi-shield-check me-2"></i>Hak Akses Role</h6>
            <small class="text-muted">Pilih seluruh fitur yang boleh dikelola oleh role ini.</small>
          </div>
          <?php if (!$isProtected): ?>
          <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" id="selectAllPermissions" type="button">Pilih semua</button>
            <button class="btn btn-sm btn-outline-secondary" id="clearAllPermissions" type="button">Kosongkan</button>
          </div>
          <?php endif; ?>
        </div>
        <div class="acard-body">
          <?php if ($isProtected): ?>
            <div class="p-4 text-center">
              <i class="bi bi-shield-fill-check display-5 text-success"></i>
              <h6 class="mt-3">Akses penuh seluruh sistem</h6>
              <p class="text-muted small mb-0">Super Admin tidak memerlukan daftar izin individual.</p>
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($permissionGroups as $group => $permissions): ?>
              <div class="col-lg-6">
                <fieldset class="border rounded-3 p-3 h-100">
                  <legend class="float-none w-auto px-2 fs-6 fw-semibold mb-1"><?= e($group) ?></legend>
                  <?php foreach ($permissions as $permission): ?>
                  <label class="d-flex gap-2 align-items-start py-2 border-bottom permission-option">
                    <input class="form-check-input mt-1 permission-checkbox" type="checkbox" name="permission_ids[]" value="<?= (int) $permission['id'] ?>" <?= in_array((int) $permission['id'], $checkedIds, true) ? 'checked' : '' ?>>
                    <span>
                      <strong class="d-block small"><?= e($permission['name']) ?></strong>
                      <small class="text-muted d-block"><?= e($permission['description']) ?></small>
                    </span>
                  </label>
                  <?php endforeach; ?>
                </fieldset>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mt-3">
    <?php if (!$isProtected): ?>
      <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg me-1"></i>Simpan Role</button>
    <?php endif; ?>
    <a class="btn btn-outline-secondary" href="<?= url('admin/roles') ?>">Kembali</a>
  </div>
</form>

<?php if (!$isProtected): ?>
<script>
(function () {
  var boxes = Array.prototype.slice.call(document.querySelectorAll('.permission-checkbox'));
  var all = document.getElementById('selectAllPermissions');
  var clear = document.getElementById('clearAllPermissions');
  if (all) all.addEventListener('click', function () { boxes.forEach(function (box) { box.checked = true; }); });
  if (clear) clear.addEventListener('click', function () { boxes.forEach(function (box) { box.checked = false; }); });
})();
</script>
<?php endif; ?>
