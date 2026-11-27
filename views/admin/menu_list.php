<?php
// $mainMenus, $subMenus, $footerMenus
$children = [];
foreach ($subMenus as $sm) $children[$sm['parent_id']][] = $sm;
function menu_row(array $m, bool $isChild = false): void { ?>
  <tr>
    <td><?= $isChild ? '<span class="text-muted ms-3"><i class="bi bi-arrow-return-right me-1"></i></span>' : '' ?><?php if ($m['icon']): ?><i class="bi <?= e($m['icon']) ?> me-1"></i><?php endif; ?><strong><?= e($m['title']) ?></strong></td>
    <td><code class="small"><?= e($m['url']) ?></code></td>
    <td><?= (int) $m['sort_order'] ?></td>
    <td><span class="badge text-bg-<?= $m['is_active'] ? 'success' : 'secondary' ?>"><?= $m['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td>
    <td class="text-end text-nowrap">
      <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/menu/edit/' . $m['id']) ?>" data-testid="menu-edit-<?= $m['id'] ?>"><i class="bi bi-pencil"></i></a>
      <form class="d-inline" method="post" action="<?= url('admin/menu/delete/' . $m['id']) ?>" data-confirm="Hapus menu ini? Submenu akan menjadi menu utama."><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" data-testid="menu-delete-<?= $m['id'] ?>"><i class="bi bi-trash"></i></button></form>
    </td>
  </tr>
<?php } ?>

<div class="d-flex justify-content-end mb-3">
  <a class="btn btn-primary btn-sm" href="<?= url('admin/menu/create') ?>" data-testid="menu-add-btn"><i class="bi bi-plus-lg me-1"></i>Tambah Menu</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-list-nested me-2"></i>Menu Navigasi Utama</h6></div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-admin table-hover mb-0">
            <thead><tr><th>Judul</th><th>URL</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($mainMenus as $m): menu_row($m);
                foreach ($children[$m['id']] ?? [] as $c) menu_row($c, true);
              endforeach; ?>
              <?php if (!$mainMenus): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada menu.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-layout-text-window-reverse me-2"></i>Menu Footer</h6></div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-admin table-hover mb-0">
            <thead><tr><th>Judul</th><th>URL</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($footerMenus as $m) menu_row($m); ?>
              <?php if (!$footerMenus): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada menu footer.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="alert alert-light border small mt-3"><i class="bi bi-info-circle me-1"></i>Gunakan URL relatif seperti <code>berita</code> atau <code>profil</code>, atau URL lengkap <code>https://...</code> untuk tautan eksternal.</div>
  </div>
</div>
