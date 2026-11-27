<?php
$isNew = $u === null;
$old = $_SESSION['old_user_form'] ?? [];
unset($_SESSION['old_user_form']);

$field = static function (string $key, $default = '') use ($old, $u) {
    return $old[$key] ?? $u[$key] ?? $default;
};
$selectedRoleId = (int) $field('role_id', 8);
$selectedEffects = $old['permission_effect'] ?? $userOverrides;
$byRole = [];
foreach ($rolePerms as $rp) {
    $byRole[(int) $rp['role_id']][] = (int) $rp['permission_id'];
}
$permissionNames = array_column($permissions, 'name', 'id');
?>

<form method="post" action="<?= url('admin/users/' . ($isNew ? 'store' : 'update/' . $u['id'])) ?>" data-testid="user-form">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-xl-7">
      <div class="acard h-100">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-vcard me-2"></i>Data Akun</h6></div>
        <div class="acard-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="userName">Nama Lengkap *</label>
              <input class="form-control" id="userName" name="name" value="<?= e($field('name')) ?>" minlength="2" maxlength="150" required data-testid="user-name">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="userUsername">Username *</label>
              <input class="form-control" id="userUsername" name="username" value="<?= e($field('username')) ?>" required pattern="[a-zA-Z0-9_.]{3,60}" maxlength="60" autocomplete="username" data-testid="user-username">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="userEmail">Email *</label>
              <input class="form-control" id="userEmail" type="email" name="email" value="<?= e($field('email')) ?>" maxlength="150" required data-testid="user-email">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="userPhone">No. HP</label>
              <input class="form-control" id="userPhone" name="phone" value="<?= e($field('phone')) ?>" maxlength="30">
            </div>
            <div class="col-md-6">
              <label class="form-label" for="userPassword"><?= $isNew ? 'Password *' : 'Password Baru' ?></label>
              <input class="form-control" id="userPassword" type="password" name="password" minlength="8" <?= $isNew ? 'required' : '' ?> autocomplete="new-password" data-testid="user-password">
              <div class="form-text"><?= $isNew ? 'Minimal 8 karakter.' : 'Kosongkan untuk mempertahankan password saat ini.' ?></div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="roleSelect">Role *</label>
              <select class="form-select" name="role_id" id="roleSelect" required data-testid="user-role">
                <?php foreach ($roles as $role): ?>
                  <option value="<?= (int) $role['id'] ?>" data-super="<?= $role['slug'] === 'super-admin' ? '1' : '0' ?>" <?= $selectedRoleId === (int) $role['id'] ? 'selected' : '' ?>>
                    <?= e($role['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Role memberikan hak akses dasar. Akses khusus dapat diatur di panel sebelah.</div>
            </div>
          </div>

          <hr class="my-4">

          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="userActive" <?= (int) $field('is_active', 1) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="userActive">Akun aktif</label>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="must_change_password" id="mustChangePassword" <?= (int) $field('must_change_password', 0) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="mustChangePassword">Wajib ganti password saat login berikutnya</label>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-5">
      <div class="acard mb-3">
        <div class="acard-head">
          <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Hak Akses Role</h6>
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/roles') ?>"><i class="bi bi-gear me-1"></i>Kelola Role</a>
        </div>
        <div class="acard-body">
          <?php foreach ($roles as $role): $rolePermissionIds = $byRole[(int) $role['id']] ?? []; ?>
            <div class="role-perms" data-role="<?= (int) $role['id'] ?>" <?= $selectedRoleId !== (int) $role['id'] ? 'hidden' : '' ?>>
              <strong class="d-block mb-2"><?= e($role['name']) ?></strong>
              <?php if ($role['slug'] === 'super-admin'): ?>
                <div class="alert alert-success py-2 small mb-0"><i class="bi bi-shield-fill-check me-1"></i>Akses penuh seluruh sistem.</div>
              <?php elseif (!$rolePermissionIds): ?>
                <div class="alert alert-secondary py-2 small mb-0">Role ini belum memiliki izin dasar.</div>
              <?php else: ?>
                <div class="d-flex flex-wrap gap-1">
                  <?php foreach ($rolePermissionIds as $permissionId): ?>
                    <span class="badge text-bg-light border"><?= e($permissionNames[$permissionId] ?? ('Izin #' . $permissionId)) ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="acard">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-toggles me-2"></i>Akses Khusus User</h6></div>
        <div class="acard-body">
          <div class="alert alert-info py-2 small" id="overrideHelp">
            Pilih <strong>Ikuti role</strong> untuk memakai izin dasar, <strong>Izinkan</strong> untuk menambah akses, atau <strong>Tolak</strong> untuk mencabut akses tertentu.
          </div>
          <div id="superAdminOverrideNotice" class="alert alert-success py-2 small" hidden>
            Super Admin selalu memiliki akses penuh; pengaturan khusus tidak diperlukan.
          </div>

          <div id="userPermissionOverrides" class="accordion accordion-flush">
            <?php $groupIndex = 0; foreach ($permissionGroups as $group => $groupPermissions): $groupIndex++; ?>
              <div class="accordion-item">
                <h2 class="accordion-header" id="permissionHeading<?= $groupIndex ?>">
                  <button class="accordion-button <?= $groupIndex > 1 ? 'collapsed' : '' ?> px-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#permissionGroup<?= $groupIndex ?>" aria-expanded="<?= $groupIndex === 1 ? 'true' : 'false' ?>">
                    <?= e($group) ?>
                  </button>
                </h2>
                <div id="permissionGroup<?= $groupIndex ?>" class="accordion-collapse collapse <?= $groupIndex === 1 ? 'show' : '' ?>" aria-labelledby="permissionHeading<?= $groupIndex ?>">
                  <div class="accordion-body px-0 pt-1 pb-2">
                    <?php foreach ($groupPermissions as $permission):
                      $effect = $selectedEffects[(int) $permission['id']] ?? 'inherit';
                    ?>
                      <div class="border-bottom py-2">
                        <label class="form-label small fw-semibold mb-1" for="permissionEffect<?= (int) $permission['id'] ?>"><?= e($permission['name']) ?></label>
                        <select class="form-select form-select-sm permission-effect" id="permissionEffect<?= (int) $permission['id'] ?>" name="permission_effect[<?= (int) $permission['id'] ?>]">
                          <option value="inherit" <?= $effect === 'inherit' ? 'selected' : '' ?>>Ikuti role</option>
                          <option value="allow" <?= $effect === 'allow' ? 'selected' : '' ?>>Izinkan khusus user</option>
                          <option value="deny" <?= $effect === 'deny' ? 'selected' : '' ?>>Tolak khusus user</option>
                        </select>
                        <small class="text-muted d-block mt-1"><?= e($permission['description']) ?></small>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 mt-3">
    <button class="btn btn-primary" type="submit" data-testid="user-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan User</button>
    <a class="btn btn-outline-secondary" href="<?= url('admin/users') ?>">Batal</a>
  </div>
</form>

<?php if (!$isNew): ?>
<div class="acard mt-4" id="faceDatasetCard">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>Dataset Login Wajah</h6></div>
  <div class="acard-body">
    <?php if ($canEnrollFace): ?>
      <p class="small text-muted">Simpan descriptor numerik wajah untuk akun ini. Foto atau video kamera tidak diunggah ke server.</p>
      <div class="face-enroll-box mb-3" id="faceEnrollBox" data-stage="idle">
        <div class="face-camera-shell face-enroll-camera">
          <video id="faceEnrollVideo" autoplay muted playsinline></video>
          <div class="face-camera-shade"></div>
          <div class="face-guide" aria-hidden="true"><span></span><span></span><span></span><span></span></div>
          <div class="face-camera-badge" id="faceEnrollBadge"><i class="bi bi-camera-fill"></i><span>Kamera belum aktif</span></div>
        </div>
        <div class="face-scan-status-wrap">
          <span class="face-status-dot" aria-hidden="true"></span>
          <div id="faceEnrollStatus" class="face-scan-status text-muted" aria-live="polite">Tekan tombol scan, lalu izinkan kamera ketika browser meminta akses.</div>
        </div>
        <div class="face-permission-help" id="faceEnrollPermissionHelp" hidden>
          <i class="bi bi-info-circle-fill"></i>
          <span>Permintaan izin belum terlihat? Periksa ikon kamera atau izin situs pada browser.</span>
        </div>
        <label class="form-label mt-3" for="faceDatasetLabel">Nama dataset</label>
        <input class="form-control mb-2" id="faceDatasetLabel" value="Wajah utama" maxlength="120">
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-primary flex-grow-1" id="faceEnrollBtn">
            <i class="bi bi-camera me-1"></i><span>Scan dan Tambah Dataset</span>
          </button>
          <button type="button" class="btn btn-outline-secondary" id="faceEnrollCancel" hidden>
            <i class="bi bi-x-lg me-1"></i>Batalkan
          </button>
        </div>
        <div class="face-privacy"><i class="bi bi-shield-lock-fill"></i>Kamera berhenti otomatis setelah scan selesai atau dibatalkan.</div>
      </div>
    <?php else: ?>
      <div class="alert alert-info small">
        <i class="bi bi-shield-lock me-1"></i>Dataset baru harus dipindai sendiri oleh pemilik akun melalui halaman <strong>Akun Saya</strong>. Super Admin hanya dapat menonaktifkan atau menghapus dataset bermasalah.
      </div>
    <?php endif; ?>

    <div id="faceProfileList">
      <?php if (empty($faceProfiles)): ?>
        <div class="small text-muted face-empty">Belum ada dataset wajah.</div>
      <?php else: foreach ($faceProfiles as $profile): ?>
        <div class="border rounded p-2 mb-2 d-flex align-items-center gap-2 face-profile-row">
          <i class="bi bi-person-check fs-5"></i>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold small text-truncate"><?= e($profile['label']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= (int) $profile['sample_count'] ?> sampel · <?= e(tgl_id($profile['created_at'], true)) ?></div>
          </div>
          <button type="button" class="btn btn-sm <?= $profile['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?> face-toggle" data-id="<?= (int) $profile['id'] ?>" title="Aktif/nonaktif"><i class="bi bi-power"></i></button>
          <button type="button" class="btn btn-sm btn-outline-danger face-delete" data-id="<?= (int) $profile['id'] ?>" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<?php if ($canEnrollFace): ?>
<script>
window.FACE_AUTH_CONFIG = {
  localHumanUrl: <?= json_encode(asset('vendor/human/human.esm.js')) ?>,
  localModelPath: <?= json_encode(url('assets/vendor/human/models/')) ?>,
  cdnHumanUrl: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/dist/human.esm.js',
  cdnModelPath: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/models/'
};
</script>
<script src="<?= asset('js/face-auth.js') ?>?v=20260727-face-easy-2"></script>
<?php endif; ?>
<script>
(function () {
  var csrf = <?= json_encode(csrf_token()) ?>;

  async function post(urlValue, data) {
    var form = new FormData();
    form.append('csrf_token', csrf);
    Object.keys(data || {}).forEach(function (key) { form.append(key, data[key]); });
    var response = await fetch(urlValue, {
      method: 'POST',
      body: form,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    var payload = await response.json().catch(function () { return {}; });
    if (!response.ok || !payload.ok) throw new Error(payload.error || 'Permintaan gagal.');
    return payload;
  }

  document.querySelectorAll('.face-delete').forEach(function (button) {
    button.addEventListener('click', async function () {
      if (!confirm('Hapus dataset wajah ini?')) return;
      try {
        await post(<?= json_encode(url('admin/users/face-delete/')) ?> + button.dataset.id, {});
        var row = button.closest('.face-profile-row');
        if (row) row.remove();
      } catch (error) {
        alert(error.message);
      }
    });
  });

  document.querySelectorAll('.face-toggle').forEach(function (button) {
    button.addEventListener('click', async function () {
      try {
        var result = await post(<?= json_encode(url('admin/users/face-toggle/')) ?> + button.dataset.id, {});
        button.className = 'btn btn-sm ' + (result.active ? 'btn-success' : 'btn-outline-secondary') + ' face-toggle';
      } catch (error) {
        alert(error.message);
      }
    });
  });

  var scanButton = document.getElementById('faceEnrollBtn');
  var video = document.getElementById('faceEnrollVideo');
  var status = document.getElementById('faceEnrollStatus');
  var cancelButton = document.getElementById('faceEnrollCancel');
  var box = document.getElementById('faceEnrollBox');
  var badge = document.getElementById('faceEnrollBadge');
  var permissionHelp = document.getElementById('faceEnrollPermissionHelp');
  var scanRun = 0;
  var scanning = false;

  if (!scanButton || !window.FaceScanner) return;
  window.setTimeout(function () { FaceScanner.preload(); }, 0);

  function setEnrollStage(stage, detail) {
    if (box) box.dataset.stage = stage;
    if (permissionHelp) permissionHelp.hidden = stage !== 'permission-wait';
    var label = 'Kamera belum aktif';
    var icon = 'bi-camera-fill';
    if (stage === 'permission' || stage === 'permission-wait') label = 'Menunggu izin kamera';
    else if (stage === 'camera-ready' || stage === 'scanning') { label = 'Kamera aktif'; icon = 'bi-record-circle-fill'; }
    else if (stage === 'sample') { label = 'Sampel ' + detail.current + '/' + detail.total; icon = 'bi-record-circle-fill'; }
    else if (stage === 'complete') { label = 'Scan selesai'; icon = 'bi-check-circle-fill'; }
    else if (stage === 'error') { label = 'Scan perlu diulang'; icon = 'bi-exclamation-triangle-fill'; }
    if (badge) badge.innerHTML = '<i class="bi ' + icon + '"></i><span>' + label + '</span>';
  }

  function setScanning(active) {
    scanning = active;
    scanButton.disabled = active;
    var label = scanButton.querySelector('span');
    if (label) label.textContent = active ? 'Sedang Memindai…' : 'Scan dan Tambah Dataset';
    if (cancelButton) cancelButton.hidden = !active;
  }

  scanButton.addEventListener('click', async function () {
    if (scanning) return;
    var runId = ++scanRun;
    setScanning(true);
    setEnrollStage('permission', {});
    try {
      var result = await FaceScanner.scan({
        video: video,
        status: status,
        mode: 'enroll',
        samples: 5,
        timeout: 42000,
        permissionTimeout: 22000,
        onStage: setEnrollStage,
        isCancelled: function () { return runId !== scanRun; }
      });
      await post(<?= json_encode(url('admin/users/face-store/' . $u['id'])) ?>, {
        descriptor: JSON.stringify(result.descriptor),
        sample_count: result.sampleCount,
        templates: JSON.stringify(result.templates || []),
        label: document.getElementById('faceDatasetLabel').value
      });
      FaceScanner.setStatus(status, 'Dataset tersimpan. Memuat ulang daftar…', 'success');
      setEnrollStage('complete', {});
      location.reload();
    } catch (error) {
      if (runId !== scanRun) return;
      setEnrollStage('error', {});
      FaceScanner.setStatus(status, error.message || 'Pemindaian gagal.', 'danger');
    } finally {
      if (runId === scanRun) setScanning(false);
    }
  });

  if (cancelButton) {
    cancelButton.addEventListener('click', function () {
      scanRun += 1;
      FaceScanner.stop(video);
      setScanning(false);
      setEnrollStage('idle', {});
      FaceScanner.setStatus(status, 'Pemindaian dibatalkan. Tekan scan untuk mencoba kembali.', 'muted');
    });
  }
})();
</script>
<?php endif; ?>

<script>
(function () {
  var roleSelect = document.getElementById('roleSelect');
  var overridePanel = document.getElementById('userPermissionOverrides');
  var overrideHelp = document.getElementById('overrideHelp');
  var superNotice = document.getElementById('superAdminOverrideNotice');
  var effectSelects = Array.prototype.slice.call(document.querySelectorAll('.permission-effect'));
  if (!roleSelect) return;

  function refreshRole() {
    document.querySelectorAll('.role-perms').forEach(function (panel) {
      panel.hidden = panel.dataset.role !== roleSelect.value;
    });
    var selected = roleSelect.options[roleSelect.selectedIndex];
    var isSuper = selected && selected.dataset.super === '1';
    if (overridePanel) overridePanel.hidden = isSuper;
    if (overrideHelp) overrideHelp.hidden = isSuper;
    if (superNotice) superNotice.hidden = !isSuper;
    effectSelects.forEach(function (select) { select.disabled = isSuper; });
  }

  roleSelect.addEventListener('change', refreshRole);
  refreshRole();
})();
</script>
