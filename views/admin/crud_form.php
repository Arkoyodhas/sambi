<?php
// Form CRUD generik: $module, $cfg, $row (null = tambah).
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);
$hasOld = array_key_exists('_form_submitted', $old);
$isNew = $row === null;
$rawValue = static function (string $name, $default = '') use ($old, $row, $hasOld) {
    if ($hasOld && array_key_exists($name, $old)) return $old[$name];
    return $row[$name] ?? $default;
};
$value = static fn(string $name, $default = '') => e($rawValue($name, $default));
$action = url("admin/$module/" . ($isNew ? 'store' : 'update/' . (int) $row['id']));
$inputAttrs = static function (array $field): string {
    $attrs = [];
    if (!empty($field['required'])) $attrs[] = 'required';
    if (isset($field['maxlength'])) $attrs[] = 'maxlength="' . (int) $field['maxlength'] . '"';
    if (isset($field['min'])) $attrs[] = 'min="' . e((string) $field['min']) . '"';
    if (isset($field['max'])) $attrs[] = 'max="' . e((string) $field['max']) . '"';
    return implode(' ', $attrs);
};
?>
<div class="acard" style="max-width:920px">
  <div class="acard-head">
    <div>
      <h6 class="mb-0"><?= e($pageTitle) ?></h6>
      <?php if (!$isNew && !empty($cfg['public_preview'])):
        $previewField = (string) ($cfg['public_preview']['field'] ?? 'slug');
        $previewValue = (string) ($row[$previewField] ?? '');
        if ($previewValue !== ''):
      ?>
      <a class="small text-decoration-none" target="_blank" rel="noopener" href="<?= e(url(trim((string) $cfg['public_preview']['route'], '/') . '/' . rawurlencode($previewValue))) ?>">
        <i class="bi bi-box-arrow-up-right me-1"></i>Lihat halaman publik
      </a>
      <?php endif; endif; ?>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url("admin/$module") ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
  </div>

  <div class="acard-body">
    <?php if (!empty($cfg['form_note'])): ?>
    <div class="alert alert-info small py-2"><i class="bi bi-info-circle me-1"></i><?= e($cfg['form_note']) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate data-crud-form>
      <?= csrf_field() ?>
      <input type="hidden" name="_form_submitted" value="1">
      <div class="row g-3">
        <?php foreach ((array) $cfg['fields'] as $f):
            $name = (string) $f['name'];
            $type = (string) ($f['type'] ?? 'text');
            $col = in_array($type, ['editor', 'textarea'], true) ? 'col-12' : 'col-md-6';
            $required = !empty($f['required']);
            $requiredIf = !empty($f['required_if']) ? ' data-required-if=\'' . e(json_encode($f['required_if'], JSON_UNESCAPED_UNICODE)) . '\'' : '';
        ?>
        <div class="<?= $col ?>">
          <?php if ($type === 'checkbox'):
            $checked = $hasOld
              ? isset($old[$name])
              : (bool) ($row[$name] ?? ($f['default'] ?? ($isNew ? 1 : 0)));
          ?>
          <div class="form-check form-switch mt-4">
            <input class="form-check-input" type="checkbox" name="<?= e($name) ?>" id="f_<?= e($name) ?>" value="1"
              <?= $checked ? 'checked' : '' ?> data-testid="field-<?= e($name) ?>">
            <label class="form-check-label" for="f_<?= e($name) ?>"><?= e($f['label']) ?></label>
          </div>
          <?php else: ?>
          <label class="form-label" for="f_<?= e($name) ?>"><?= e($f['label']) ?><?= $required ? ' *' : '' ?></label>

          <?php switch ($type):
              case 'textarea': ?>
          <textarea class="form-control" id="f_<?= e($name) ?>" name="<?= e($name) ?>" rows="4"
            <?= $inputAttrs($f) ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>"><?= $value($name) ?></textarea>

              <?php break; case 'editor': ?>
          <textarea class="form-control editor-init" id="f_<?= e($name) ?>" name="<?= e($name) ?>" rows="10"
            data-editor-required="<?= $required ? '1' : '0' ?>" <?= isset($f['maxlength']) ? 'maxlength="' . (int) $f['maxlength'] . '"' : '' ?>
            <?= $requiredIf ?> data-testid="field-<?= e($name) ?>"><?= $value($name) ?></textarea>

              <?php break; case 'select':
                $current = $rawValue($name, $f['default'] ?? '');
          ?>
          <select class="form-select" id="f_<?= e($name) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">
            <?php if (!empty($f['nullable'])): ?><option value="">— Tidak ada —</option><?php endif; ?>
            <?php foreach ((array) $f['options'] as $optionValue => $optionLabel): ?>
            <option value="<?= e($optionValue) ?>" <?= (string) $current === (string) $optionValue ? 'selected' : '' ?>><?= e($optionLabel) ?></option>
            <?php endforeach; ?>
          </select>

              <?php break; case 'select_db':
                $current = $rawValue($name, '');
                $options = [];
                try {
                    $options = db_all((string) $f['options_sql']);
                } catch (Throwable $e) {
                    $options = [];
                }
          ?>
          <select class="form-select" id="f_<?= e($name) ?>" name="<?= e($name) ?>"
            <?= $required && empty($f['nullable']) ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">
            <?php if (!empty($f['nullable'])): ?><option value="">— Tidak ada —</option><?php endif; ?>
            <?php foreach ($options as $option): ?>
            <option value="<?= (int) $option['id'] ?>" <?= (string) $current === (string) $option['id'] ? 'selected' : '' ?>>
              <?= e($option['label'] ?? $option['name'] ?? $option['id']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <?php if (!$options): ?><div class="form-text text-warning">Belum ada data pilihan. Tambahkan data referensi terlebih dahulu.</div><?php endif; ?>

              <?php break; case 'number': ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="number" name="<?= e($name) ?>"
            value="<?= $value($name, (string) ($f['default'] ?? '')) ?>" <?= $inputAttrs($f) ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'decimal': ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="number" step="<?= e((string) ($f['step'] ?? '0.01')) ?>" name="<?= e($name) ?>"
            value="<?= $value($name) ?>" <?= $inputAttrs($f) ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'date': ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="date" name="<?= e($name) ?>"
            value="<?= $value($name) ?>" <?= $required ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'time': ?>
          <?php $timeValue = (string) $rawValue($name, (string) ($f['default'] ?? '')); ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="time" name="<?= e($name) ?>"
            value="<?= e(substr($timeValue, 0, 5)) ?>" <?= $required ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'color': ?>
          <input class="form-control form-control-color" id="f_<?= e($name) ?>" type="color" name="<?= e($name) ?>"
            value="<?= $value($name, (string) ($f['default'] ?? '#0f6b4e')) ?>" data-testid="field-<?= e($name) ?>">

              <?php break; case 'image': case 'image_protected': ?>
          <?php if (!$isNew && !empty($row[$name])):
            $imageSrc = str_contains((string) ($f['dir'] ?? ''), 'protected')
              ? url('media/foto/' . (int) $row['id'])
              : upload_url((string) $row[$name]);
          ?>
          <div class="mb-2">
            <img src="<?= e($imageSrc) ?>" style="height:88px;max-width:180px;object-fit:cover;border-radius:10px" alt="Gambar saat ini">
            <div class="small text-muted mt-1">Pilih file baru hanya jika ingin mengganti gambar.</div>
          </div>
          <?php endif; ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="file" name="<?= e($name) ?>" accept=".jpg,.jpeg,.png,.webp"
            <?= $required && $isNew ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'file': ?>
          <?php if (!$isNew && !empty($row[$name])): ?>
          <div class="mb-2">
            <a href="<?= e(upload_url((string) $row[$name])) ?>" target="_blank" rel="noopener" class="small text-decoration-none">
              <i class="bi bi-paperclip me-1"></i><?= e(basename((string) $row[$name])) ?>
            </a>
            <div class="small text-muted">Biarkan kosong untuk mempertahankan file saat ini.</div>
          </div>
          <?php endif; ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="file" name="<?= e($name) ?>" accept=".pdf,.doc,.docx,.xls,.xlsx"
            <?= $required && $isNew ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; case 'audio':
                $audioExists = !$isNew && !empty($row[$name])
                  && is_file(BASE_PATH . '/uploads/' . ltrim((string) $row[$name], '/'))
                  && is_readable(BASE_PATH . '/uploads/' . ltrim((string) $row[$name], '/'));
          ?>
          <?php if ($audioExists): ?>
          <audio controls preload="metadata" class="d-block mb-2 audio-preview" style="height:34px;max-width:100%" src="<?= e(upload_url((string) $row[$name])) ?>"></audio>
          <div class="small text-muted mb-1">Biarkan kosong untuk mempertahankan musik saat ini.</div>
          <?php elseif (!$isNew && !empty($row[$name])): ?>
          <div class="alert alert-warning py-2 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>File musik lama tidak ditemukan. Unggah ulang file.</div>
          <?php endif; ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="file" name="<?= e($name) ?>" accept=".mp3,.wav,.ogg"
            <?= $required && $isNew ? 'required' : '' ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">

              <?php break; default:
                $htmlType = ($f['format'] ?? '') === 'url' ? 'url' : 'text';
          ?>
          <input class="form-control" id="f_<?= e($name) ?>" type="<?= $htmlType ?>" name="<?= e($name) ?>"
            value="<?= $value($name, (string) ($f['default'] ?? '')) ?>" <?= $inputAttrs($f) ?><?= $requiredIf ?> data-testid="field-<?= e($name) ?>">
          <?php endswitch; ?>

          <?php if (!empty($f['hint'])): ?><div class="form-text"><?= e($f['hint']) ?></div><?php endif; ?>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if (!empty($cfg['multi_image']) && $isNew): ?>
        <div class="col-12">
          <label class="form-label" for="f_multi_files"><i class="bi bi-images me-1"></i>Upload Banyak Foto Sekaligus</label>
          <input class="form-control" id="f_multi_files" type="file" name="multi_files[]" accept=".jpg,.jpeg,.png,.webp" multiple data-testid="field-multi-files">
          <div class="form-text">Setiap file disimpan sebagai item galeri terpisah. Maksimal ukuran mengikuti pengaturan upload gambar.</div>
        </div>
        <?php endif; ?>

        <?php if (!empty($cfg['form_extra'])):
          $extraPath = BASE_PATH . '/views/admin/' . basename((string) $cfg['form_extra']) . '.php';
          if (is_file($extraPath)) include $extraPath;
        endif; ?>

        <?php if (!empty($cfg['push'])): ?>
        <div class="col-12">
          <div class="border rounded-3 p-3 bg-light">
            <label class="form-label mb-1" for="f_send_push"><i class="bi bi-bell me-1"></i>Kirim notifikasi push setelah disimpan?</label>
            <select class="form-select" id="f_send_push" name="send_push" data-testid="field-send-push">
              <option value="0" selected>Tidak</option>
              <option value="1">Ya, kirim ke semua perangkat pelanggan</option>
            </select>
            <div class="form-text">Pilih Ya hanya untuk informasi yang memang perlu diberitahukan kepada warga.</div>
          </div>
        </div>
        <?php endif; ?>

        <div class="col-12 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary px-4" type="submit" data-testid="crud-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan</button>
          <a class="btn btn-outline-secondary" href="<?= url("admin/$module") ?>">Batal</a>
        </div>
      </div>
    </form>
  </div>
</div>
