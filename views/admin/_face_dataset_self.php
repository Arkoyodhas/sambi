<div class="acard mt-3" id="faceDatasetCard">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>Dataset Login Wajah</h6></div>
  <div class="acard-body">
    <p class="small text-muted">Simpan descriptor numerik wajah untuk akun Anda. Foto/video kamera tidak diunggah ke server.</p>
    <div class="face-enroll-box" id="faceEnrollBox" data-stage="idle">
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
      <div class="face-privacy"><i class="bi bi-shield-lock-fill"></i> Kamera berhenti otomatis setelah scan selesai atau dibatalkan.</div>
    </div>
    <hr>
    <div id="faceProfileList">
      <?php if (empty($faceProfiles)): ?>
        <div class="small text-muted face-empty">Belum ada dataset wajah.</div>
      <?php else: foreach ($faceProfiles as $fp): ?>
        <div class="border rounded p-2 mb-2 d-flex align-items-center gap-2 face-profile-row">
          <i class="bi bi-person-check fs-5"></i>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold small text-truncate"><?= e($fp['label']) ?></div>
            <div class="text-muted" style="font-size:.72rem"><?= (int) $fp['sample_count'] ?> sampel · <?= e(tgl_id($fp['created_at'], true)) ?></div>
          </div>
          <button type="button" class="btn btn-sm <?= $fp['is_active'] ? 'btn-success' : 'btn-outline-secondary' ?> face-toggle" data-id="<?= (int) $fp['id'] ?>" title="Aktif/nonaktif"><i class="bi bi-power"></i></button>
          <button type="button" class="btn btn-sm btn-outline-danger face-delete" data-id="<?= (int) $fp['id'] ?>" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<script>
window.FACE_AUTH_CONFIG = {
  localHumanUrl: <?= json_encode(asset('vendor/human/human.esm.js')) ?>,
  localModelPath: <?= json_encode(url('assets/vendor/human/models/')) ?>,
  cdnHumanUrl: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/dist/human.esm.js',
  cdnModelPath: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.3.6/models/'
};
</script>
<script src="<?= asset('js/face-auth.js') ?>?v=20260727-face-easy-2"></script>
<script>
(function () {
  var button = document.getElementById('faceEnrollBtn');
  var video = document.getElementById('faceEnrollVideo');
  var status = document.getElementById('faceEnrollStatus');
  var cancelButton = document.getElementById('faceEnrollCancel');
  var box = document.getElementById('faceEnrollBox');
  var badge = document.getElementById('faceEnrollBadge');
  var permissionHelp = document.getElementById('faceEnrollPermissionHelp');
  var csrf = <?= json_encode(csrf_token()) ?>;
  var scanRun = 0;
  var scanning = false;
  if (!button || !window.FaceScanner) return;
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
    if (badge) badge.innerHTML = '<i class="bi '+icon+'"></i><span>'+label+'</span>';
  }

  function setScanning(active) {
    scanning = active;
    button.disabled = active;
    button.querySelector('span').textContent = active ? 'Sedang Memindai…' : 'Scan dan Tambah Dataset';
    if (cancelButton) cancelButton.hidden = !active;
  }

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

  button.addEventListener('click', async function () {
    if (scanning) return;
    var runId = ++scanRun;
    setScanning(true);
    setEnrollStage('permission');
    if (box && typeof box.scrollIntoView === 'function') {
      box.scrollIntoView({ block: 'start', inline: 'nearest' });
    }
    try {
      var result = await FaceScanner.scan({
        video: video,
        status: status,
        mode: 'enroll',
        samples: 5,
        timeout: 38000,
        permissionTimeout: 18000,
        onStage: setEnrollStage,
        isCancelled: function () { return runId !== scanRun; }
      });
      await post(<?= json_encode(url('admin/akun/face-store')) ?>, {
        descriptor: JSON.stringify(result.descriptor),
        sample_count: result.sampleCount,
        templates: JSON.stringify(result.templates || []),
        label: document.getElementById('faceDatasetLabel').value
      });
      FaceScanner.setStatus(status, 'Dataset tersimpan. Memuat ulang daftar…', 'success');
      setEnrollStage('complete');
      location.reload();
    } catch (error) {
      if (runId !== scanRun) return;
      setEnrollStage('error');
      FaceScanner.setStatus(status, error.message || 'Pemindaian gagal.', 'danger');
    } finally {
      if (runId === scanRun) setScanning(false);
    }
  });

  if (cancelButton) cancelButton.addEventListener('click', function () {
    scanRun += 1;
    FaceScanner.stop(video);
    setScanning(false);
    setEnrollStage('idle');
    FaceScanner.setStatus(status, 'Pemindaian dibatalkan. Tekan scan untuk mencoba kembali.', 'muted');
  });

  document.querySelectorAll('#faceDatasetCard .face-delete').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      if (!confirm('Hapus dataset wajah ini?')) return;
      try {
        await post(<?= json_encode(url('admin/akun/face-delete/')) ?> + btn.dataset.id, {});
        btn.closest('.face-profile-row').remove();
      } catch (error) { alert(error.message); }
    });
  });

  document.querySelectorAll('#faceDatasetCard .face-toggle').forEach(function (btn) {
    btn.addEventListener('click', async function () {
      try {
        var data = await post(<?= json_encode(url('admin/akun/face-toggle/')) ?> + btn.dataset.id, {});
        btn.className = 'btn btn-sm ' + (data.active ? 'btn-success' : 'btn-outline-secondary') + ' face-toggle';
      } catch (error) { alert(error.message); }
    });
  });

  window.addEventListener('pagehide', function () { FaceScanner.stop(video); });

  // Akses dari popup dashboard: fokuskan kartu dan mulai scan secara langsung.
  var params = new URLSearchParams(window.location.search);
  if (params.get('face_setup') === '1') {
    if (box && typeof box.scrollIntoView === 'function') {
      box.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
    }
    window.setTimeout(function () {
      if (!scanning && button && !button.disabled) button.click();
      try {
        params.delete('face_setup');
        var cleanQuery = params.toString();
        var cleanUrl = window.location.pathname + (cleanQuery ? '?' + cleanQuery : '') + '#faceDatasetCard';
        window.history.replaceState({}, document.title, cleanUrl);
      } catch (error) {}
    }, 350);
  }
})();
</script>
