<?php
// Daftar CRUD generik: $module, $cfg, $pg, $trashed.
$cols = $cfg['list'] ?? ['id'];
$fieldLabels = [];
foreach ((array) ($cfg['fields'] ?? []) as $fieldConfig) {
    $fieldLabels[(string) $fieldConfig['name']] = (string) ($fieldConfig['label'] ?? $fieldConfig['name']);
}

if (!function_exists('admin_crud_cell')) {
    function admin_crud_cell(array $row, string $spec, string $module, bool $trashed): string {
        [$field, $type] = array_pad(explode(':', $spec, 2), 2, 'text');
        $value = $row[$field] ?? null;

        switch ($type) {
            case 'image':
                if (!$value) return '<span class="text-muted">—</span>';
                $full = BASE_PATH . '/uploads/' . ltrim((string) $value, '/');
                if (!is_file($full)) return '<span class="badge text-bg-warning">File hilang</span>';
                return '<img class="thumb" src="' . e(upload_url((string) $value)) . '" alt="" loading="lazy">';

            case 'pimage':
                return '<img class="thumb" src="' . e(url('media/foto/' . (int) $row['id'])) . '" alt="" loading="lazy">';

            case 'audio':
                if (!$value) return '—';
                $audioFile = BASE_PATH . '/uploads/' . ltrim((string) $value, '/');
                if (!is_file($audioFile) || !is_readable($audioFile)) {
                    return '<span class="badge text-bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>File musik hilang</span>';
                }
                return '<audio class="audio-preview" controls preload="metadata" src="' . e(upload_url((string) $value)) . '" style="height:32px;max-width:220px"></audio>';

            case 'bool':
                if ($trashed) {
                    return '<span class="badge text-bg-' . ($value ? 'success' : 'secondary') . '">' . ($value ? 'Ya' : 'Tidak') . '</span>';
                }
                return '<form method="post" action="' . e(url("admin/$module/toggle/" . (int) $row['id'])) . '" class="d-inline">'
                    . csrf_field()
                    . '<input type="hidden" name="field" value="' . e($field) . '">'
                    . '<button type="submit" class="badge-toggle badge text-bg-' . ($value ? 'success' : 'secondary')
                    . '" title="Klik untuk mengubah status">' . ($value ? 'Ya' : 'Tidak') . '</button></form>';

            case 'badge':
                return status_badge((string) $value);

            case 'date':
                return $value ? e(tgl_id((string) $value)) : '—';

            case 'money':
                return e(rupiah($value));

            case 'upper':
                return '<span class="badge text-bg-light border text-uppercase">' . e((string) $value) . '</span>';

            case 'progress':
                $progress = max(0, min(100, (int) $value));
                return '<div class="progress" style="height:6px;min-width:70px"><div class="progress-bar bg-success" style="width:'
                    . $progress . '%"></div></div><small class="text-muted">' . $progress . '%</small>';

            default:
                return e(excerpt((string) $value, 60));
        }
    }
}
?>
<?php if (!empty($cfg['list_partial']) && !$trashed && preg_match('#^[a-z_/]+$#', $cfg['list_partial'])) include BASE_PATH . '/views/admin/' . $cfg['list_partial'] . '.php'; ?>
<div class="acard">
  <div class="acard-head">
    <form class="d-flex gap-2" method="get" role="search">
      <label class="visually-hidden" for="crudSearch">Cari data</label>
      <input class="form-control form-control-sm" id="crudSearch" style="width:min(260px,60vw)" name="q"
        value="<?= e($_GET['q'] ?? '') ?>" maxlength="100" placeholder="Cari…" data-testid="crud-search">
      <button class="btn btn-sm btn-outline-primary" type="submit" aria-label="Cari"><i class="bi bi-search"></i></button>
      <?php if (trim((string) ($_GET['q'] ?? '')) !== ''): ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(url("admin/$module" . ($trashed ? '/trash' : ''))) ?>" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>

    <div class="d-flex gap-2 flex-wrap">
      <?php if (!empty($cfg['soft_delete'])): ?>
        <?php if ($trashed): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url("admin/$module")) ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        <?php else: ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url("admin/$module/trash")) ?>"><i class="bi bi-trash3 me-1"></i>Sampah</a>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (!$trashed): ?>
      <a class="btn btn-sm btn-primary" href="<?= e(url("admin/$module/create")) ?>" data-testid="crud-add-btn">
        <i class="bi bi-plus-lg me-1"></i>Tambah
      </a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($trashed): ?>
  <div class="alert alert-light border rounded-0 border-start-0 border-end-0 mb-0 small">
    Data di sampah tidak tampil di website. Gunakan tombol pulihkan untuk mengaktifkannya kembali.
  </div>
  <?php endif; ?>

  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0"
        <?= !empty($cfg['reorder']) && !$trashed ? 'data-reorder-url="' . e(url("admin/$module/reorder")) . '"' : '' ?>>
        <thead>
          <tr>
            <?php if (!empty($cfg['reorder']) && !$trashed): ?><th style="width:30px"><i class="bi bi-arrows-move" title="Geser untuk mengurutkan"></i></th><?php endif; ?>
            <?php foreach ($cols as $spec): [$field] = explode(':', $spec, 2); ?>
            <th><?= e($fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field))) ?></th>
            <?php endforeach; ?>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ((array) $pg['data'] as $row): ?>
          <tr data-id="<?= (int) $row['id'] ?>">
            <?php if (!empty($cfg['reorder']) && !$trashed): ?><td class="text-muted"><i class="bi bi-grip-vertical"></i></td><?php endif; ?>
            <?php foreach ($cols as $spec): ?>
            <td><?= admin_crud_cell($row, (string) $spec, $module, (bool) $trashed) ?></td>
            <?php endforeach; ?>
            <td class="text-end text-nowrap">
              <?php if ($trashed): ?>
              <form class="d-inline" method="post" action="<?= e(url("admin/$module/restore/" . (int) $row['id'])) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i></button>
              </form>
              <?php else: ?>
                <?php if (!empty($cfg['public_preview'])):
                  $previewField = (string) ($cfg['public_preview']['field'] ?? 'slug');
                  $previewValue = trim((string) ($row[$previewField] ?? ''));
                  if ($previewValue !== ''):
                    $previewUrl = url(trim((string) $cfg['public_preview']['route'], '/') . '/' . rawurlencode($previewValue));
                ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($previewUrl) ?>" target="_blank" rel="noopener" title="Lihat di website">
                  <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <?php endif; endif; ?>

              <a class="btn btn-sm btn-outline-primary" href="<?= e(url("admin/$module/edit/" . (int) $row['id'])) ?>"
                title="Edit" data-testid="crud-edit-<?= (int) $row['id'] ?>"><i class="bi bi-pencil"></i></a>

              <form class="d-inline" method="post" action="<?= e(url("admin/$module/delete/" . (int) $row['id'])) ?>"
                data-confirm="<?= !empty($cfg['soft_delete']) ? 'Pindahkan data ini ke sampah?' : 'Hapus data ini secara permanen?' ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger"
                  title="<?= !empty($cfg['soft_delete']) ? 'Pindahkan ke sampah' : 'Hapus' ?>"
                  data-testid="crud-delete-<?= (int) $row['id'] ?>"><i class="bi bi-trash"></i></button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php if (empty($pg['data'])): ?>
          <tr><td colspan="20" class="text-center text-muted py-5">
            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
            <?= trim((string) ($_GET['q'] ?? '')) !== '' ? 'Tidak ada data yang cocok dengan pencarian.' : 'Belum ada data.' ?>
          </td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="acard-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <small class="text-muted">Total <?= (int) $pg['total'] ?> data</small>
    <?= pagination_links($pg) ?>
  </div>
</div>
