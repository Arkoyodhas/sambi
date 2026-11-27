<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h5 class="mb-1">User Admin</h5>
    <p class="text-muted small mb-0">Kelola akun, role, status, dan pengecualian hak akses setiap user.</p>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary" href="<?= url('admin/roles') ?>" data-testid="manage-roles-btn"><i class="bi bi-shield-lock me-1"></i>Role & Hak Akses</a>
    <a class="btn btn-primary" href="<?= url('admin/users/create') ?>" data-testid="user-add-btn"><i class="bi bi-plus-lg me-1"></i>Tambah User</a>
  </div>
</div>

<div class="acard">
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Nama</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role & Akses</th>
            <th>Status</th>
            <th>Login Terakhir</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pg['data'] as $row): $isSelf = (int) $row['id'] === (int) auth_user()['id']; ?>
          <tr data-testid="user-row-<?= (int) $row['id'] ?>">
            <td>
              <strong><?= e($row['name']) ?></strong>
              <?php if ($isSelf): ?><span class="badge text-bg-info ms-1">Anda</span><?php endif; ?>
            </td>
            <td><code><?= e($row['username']) ?></code></td>
            <td class="small"><?= e($row['email']) ?></td>
            <td>
              <span class="badge <?= $row['role_slug'] === 'super-admin' ? 'text-bg-success' : 'text-bg-light border' ?>"><?= e($row['role_name']) ?></span>
              <?php if ((int) $row['override_count'] > 0): ?>
                <div class="small text-muted mt-1"><?= (int) $row['override_count'] ?> akses khusus</div>
              <?php endif; ?>
            </td>
            <td><span class="badge text-bg-<?= $row['is_active'] ? 'success' : 'secondary' ?>"><?= $row['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
            <td class="small text-muted"><?= $row['last_login_at'] ? tgl_id($row['last_login_at'], true) : 'Belum pernah' ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/users/edit/' . $row['id']) ?>" data-testid="user-edit-<?= (int) $row['id'] ?>" title="Edit user"><i class="bi bi-pencil"></i></a>
              <?php if (!$isSelf): ?>
                <form class="d-inline" method="post" action="<?= url('admin/users/delete/' . $row['id']) ?>" data-confirm="Yakin menghapus user <?= e($row['username']) ?>?">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" data-testid="user-delete-<?= (int) $row['id'] ?>" title="Hapus user"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada user.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="acard-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Total <?= (int) $pg['total'] ?> user</small>
    <?= pagination_links($pg) ?>
  </div>
</div>
