<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h5 class="mb-1">Role & Hak Akses</h5>
    <p class="text-muted small mb-0">Atur izin dasar setiap role. Izin khusus per user dapat diatur dari halaman User Admin.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary" href="<?= url('admin/users') ?>"><i class="bi bi-people me-1"></i>User Admin</a>
    <a class="btn btn-primary" href="<?= url('admin/roles/create') ?>" data-testid="role-add-btn"><i class="bi bi-plus-lg me-1"></i>Tambah Role</a>
  </div>
</div>

<div class="acard">
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Role</th>
            <th>Slug</th>
            <th>Hak Akses</th>
            <th>User</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($roles as $role): $protected = $role['slug'] === 'super-admin'; ?>
          <tr data-testid="role-row-<?= (int) $role['id'] ?>">
            <td>
              <strong><?= e($role['name']) ?></strong>
              <?php if ($protected): ?><span class="badge text-bg-success ms-1">Inti</span><?php endif; ?>
              <?php if (!empty($role['description'])): ?><div class="small text-muted mt-1"><?= e($role['description']) ?></div><?php endif; ?>
            </td>
            <td><code><?= e($role['slug']) ?></code></td>
            <td>
              <?php if ($protected): ?>
                <span class="badge text-bg-success">Semua fitur</span>
              <?php elseif ((int) $role['permission_count'] > 0): ?>
                <span class="badge text-bg-light border"><?= (int) $role['permission_count'] ?> izin</span>
              <?php else: ?>
                <span class="badge text-bg-secondary">Tanpa izin</span>
              <?php endif; ?>
            </td>
            <td><span class="badge text-bg-light border"><?= (int) $role['user_count'] ?> user</span></td>
            <td class="text-end text-nowrap">
              <?php if ($protected): ?>
                <button class="btn btn-sm btn-outline-secondary" type="button" disabled title="Role inti tidak dapat diubah"><i class="bi bi-lock"></i></button>
              <?php else: ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/roles/edit/' . $role['id']) ?>" title="Edit role"><i class="bi bi-pencil"></i></a>
                <form class="d-inline" method="post" action="<?= url('admin/roles/delete/' . $role['id']) ?>" data-confirm="Hapus role <?= e($role['name']) ?>?">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" <?= (int) $role['user_count'] > 0 ? 'disabled title="Role masih digunakan user"' : 'title="Hapus role"' ?>><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$roles): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada role.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="alert alert-info mt-3 mb-0">
  <i class="bi bi-info-circle me-2"></i>
  Super Admin selalu memiliki akses penuh. Pengaturan role dan user hanya tersedia untuk Super Admin.
</div>
