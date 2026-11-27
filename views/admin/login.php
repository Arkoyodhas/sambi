<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(setting('village_name')) ?></title>
<meta name="robots" content="noindex, nofollow">
<?php $humanLocalReady = is_file(BASE_PATH . '/assets/vendor/human/human.esm.js'); ?>
<?php if (!$humanLocalReady): ?><link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin><?php endif; ?>
<link href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= asset('css/admin.css') ?>" rel="stylesheet">
<style>:root{--c-primary:<?= e(setting_color('primary_color', '#0f6b4e')) ?>;--c-secondary:<?= e(setting_color('secondary_color', '#0b3d2e')) ?>;--c-accent:<?= e(setting_color('accent_color', '#e8b44c')) ?>;--c-button-floating:<?= e(setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e'))) ?>}body{font-family:system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif}</style>
<link href="<?= asset('css/admin-teal.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-brand text-center mb-4">
      <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" alt="Logo" class="login-brand-logo mb-2"><?php else: ?><i class="bi bi-buildings" style="font-size:3rem;color:var(--c-primary)"></i><?php endif; ?>
      <h1 class="login-title mb-1"><?= e(setting('village_name')) ?></h1>
      <p class="login-subtitle mb-0">Panel Administrasi Desa</p>
    </div>
    <?php $f = flash(); if ($f): ?>
    <div class="alert alert-<?= e($f['type']) ?> py-2 small" data-testid="login-flash"><?= $f['msg'] ?></div>
    <?php endif; ?>

    <?php if (($mode ?? 'login') === 'login'): ?>
    <?php $faceEnabled = feature_enabled('face_auth') && setting('face_login_enabled', '1') === '1'; ?>

    <?php if ($faceEnabled): ?>
    <div class="face-login-primary mb-3" id="faceLoginCard">
      <button type="button" class="btn btn-primary w-100 face-login-open" id="faceLoginOpen" data-testid="face-login-open">
        <i class="bi bi-person-badge"></i>
        <span>Masuk dengan Wajah</span>
      </button>

      <div id="faceLoginPanel" class="face-scan-panel face-scan-premium" hidden data-stage="accounts">
        <button type="button" class="face-scan-close" id="faceLoginCancel" aria-label="Tutup pemindai"><i class="bi bi-x-lg"></i></button>

        <div class="face-scan-header">
          <h5 id="facePanelTitle">Pilih Akun Admin</h5>
          <p id="facePanelSubtitle">Pilih akun terlebih dahulu agar pencocokan lebih cepat dan tepat.</p>
        </div>

        <div id="faceAccountPicker" class="face-account-picker">
          <div id="faceAccountLoading" class="face-account-loading">
            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
            <span>Memuat akun yang memiliki dataset wajah…</span>
          </div>
          <div id="faceAccountList" class="face-account-list" role="list"></div>
          <div id="faceAccountEmpty" class="alert alert-warning small mb-0" hidden>
            Belum ada akun aktif yang memiliki dataset wajah siap pakai. Gunakan login manual lalu daftarkan wajah dari menu Akun Saya.
          </div>
        </div>

        <div id="faceScannerArea" hidden>
          <button type="button" class="btn btn-sm btn-link px-0 mb-2" id="faceBackAccounts">
            <i class="bi bi-arrow-left me-1"></i>Pilih akun lain
          </button>
          <div class="face-selected-account" id="faceSelectedAccount"></div>

          <div class="face-camera-ring" id="faceCameraRing">
            <div class="face-camera-orbit" aria-hidden="true"></div>
            <div class="face-camera-mask">
              <video id="faceLoginVideo" autoplay muted playsinline></video>
              <div class="face-camera-shade"></div>
            </div>
            <div class="face-camera-badge" id="faceCameraBadge"><i class="bi bi-camera-fill"></i></div>
          </div>

          <div class="face-scan-status-wrap">
            <span class="face-status-dot" aria-hidden="true"></span>
            <div id="faceLoginStatus" class="face-scan-status" aria-live="polite">Menyiapkan tantangan keamanan…</div>
          </div>

          <div class="face-permission-help" id="facePermissionHelp" hidden>
            <i class="bi bi-info-circle-fill"></i>
            <span>Belum muncul? Periksa ikon kamera atau izin situs di bagian atas browser.</span>
          </div>

          <div class="face-scan-actions">
            <button type="button" class="btn btn-primary" id="faceLoginRetry" hidden>
              <i class="bi bi-arrow-clockwise me-1"></i>Scan Ulang
            </button>
            <button type="button" class="btn btn-link text-muted" id="faceLoginCancelSecondary">Batalkan</button>
          </div>
          <div class="face-privacy"><i class="bi bi-shield-lock-fill"></i> Wajah hanya dibandingkan dengan akun yang dipilih. Foto/video tidak disimpan.</div>
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center gap-2 my-3">
      <hr class="flex-grow-1 m-0"><span class="small text-muted">atau</span><hr class="flex-grow-1 m-0">
    </div>
    <details class="manual-login-details">
      <summary class="btn btn-outline-secondary w-100 py-2 mb-3">Gunakan Login Manual</summary>
    <?php endif; ?>

    <form method="post" action="<?= url('admin/login') ?>" class="<?= $faceEnabled ? 'pt-1' : '' ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Username atau Email</label>
        <input class="form-control" name="login" id="loginIdentity" required <?= $faceEnabled ? '' : 'autofocus' ?> data-testid="login-username" autocomplete="username">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" type="password" name="password" required data-testid="login-password" autocomplete="current-password">
      </div>
      <div class="mb-3">
        <label class="form-label">Captcha: <strong><?= e($captcha) ?></strong></label>
        <input class="form-control" name="captcha" required placeholder="Jawaban" inputmode="numeric" data-testid="login-captcha">
      </div>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="remember" id="remember">
          <label class="form-check-label small" for="remember">Ingat saya</label>
        </div>
        <a class="small" href="<?= url('admin/lupa-password') ?>">Lupa password?</a>
      </div>
      <button class="btn btn-primary w-100 py-2 fw-semibold" data-testid="login-submit"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk Manual</button>
    </form>

    <?php if ($faceEnabled): ?>
    </details>
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
      'use strict';
      var openButton = document.getElementById('faceLoginOpen');
      var panel = document.getElementById('faceLoginPanel');
      var picker = document.getElementById('faceAccountPicker');
      var list = document.getElementById('faceAccountList');
      var loading = document.getElementById('faceAccountLoading');
      var empty = document.getElementById('faceAccountEmpty');
      var scannerArea = document.getElementById('faceScannerArea');
      var selectedBox = document.getElementById('faceSelectedAccount');
      var backButton = document.getElementById('faceBackAccounts');
      var retryButton = document.getElementById('faceLoginRetry');
      var cancelButton = document.getElementById('faceLoginCancel');
      var cancelButtonSecondary = document.getElementById('faceLoginCancelSecondary');
      var video = document.getElementById('faceLoginVideo');
      var status = document.getElementById('faceLoginStatus');
      var permissionHelp = document.getElementById('facePermissionHelp');
      var cameraBadge = document.getElementById('faceCameraBadge');
      var title = document.getElementById('facePanelTitle');
      var subtitle = document.getElementById('facePanelSubtitle');
      var csrf = <?= json_encode(csrf_token()) ?>;
      var selectedAccount = null;
      var scanning = false;
      var scanRun = 0;
      var accountsLoaded = false;
      if (!openButton || !panel || !window.FaceScanner) return;

      async function postJson(urlValue, payload) {
        var response = await fetch(urlValue, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify(Object.assign({ csrf_token: csrf }, payload || {}))
        });
        var data = await response.json().catch(function () { return {}; });
        if (!response.ok || !data.ok) throw new Error(data.error || 'Permintaan gagal.');
        return data;
      }

      function setStage(stage, detail) {
        panel.dataset.stage = stage;
        if (permissionHelp) permissionHelp.hidden = stage !== 'permission-wait';
        var badgeText = 'Menyiapkan kamera';
        var icon = 'bi-camera-fill';
        if (stage === 'permission' || stage === 'permission-wait') badgeText = 'Menunggu izin kamera';
        else if (stage === 'camera-ready' || stage === 'scanning') { badgeText = 'Kamera aktif'; icon = 'bi-record-circle-fill'; }
        else if (stage === 'challenge') { badgeText = 'Gerakan ' + detail.current + '/' + detail.total; icon = 'bi-person-check-fill'; }
        else if (stage === 'sample') { badgeText = 'Sampel ' + detail.current + '/' + detail.total; icon = 'bi-record-circle-fill'; }
        else if (stage === 'matching') { badgeText = 'Memverifikasi akun'; icon = 'bi-stars'; }
        else if (stage === 'success') { badgeText = 'Wajah sesuai'; icon = 'bi-check-circle-fill'; }
        else if (stage === 'error') { badgeText = 'Scan perlu diulang'; icon = 'bi-exclamation-triangle-fill'; }
        if (cameraBadge) cameraBadge.innerHTML = '<i class="bi ' + icon + '"></i><span>' + badgeText + '</span>';
      }

      function openPanel() {
        panel.hidden = false;
        openButton.hidden = true;
        document.body.classList.add('face-panel-open');
        window.requestAnimationFrame(function () { panel.classList.add('is-visible'); });
      }

      function showPicker() {
        scanRun += 1;
        scanning = false;
        selectedAccount = null;
        FaceScanner.stop(video);
        picker.hidden = false;
        scannerArea.hidden = true;
        title.textContent = 'Pilih Akun Admin';
        subtitle.textContent = 'Pilih akun terlebih dahulu agar pencocokan lebih cepat dan tepat.';
        retryButton.hidden = true;
        setStage('accounts');
      }

      function closePanel() {
        scanRun += 1;
        scanning = false;
        FaceScanner.stop(video);
        panel.classList.remove('is-visible');
        document.body.classList.remove('face-panel-open');
        window.setTimeout(function () {
          panel.hidden = true;
          openButton.hidden = false;
          showPicker();
        }, 180);
      }

      function accountButton(account) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'face-account-item';
        button.setAttribute('role', 'listitem');

        var avatar = document.createElement('span');
        avatar.className = 'face-account-avatar';
        avatar.textContent = account.initials || 'A';

        var text = document.createElement('span');
        text.className = 'face-account-copy';
        var name = document.createElement('strong');
        name.textContent = account.name;
        var role = document.createElement('small');
        role.textContent = account.role || 'Admin';
        text.appendChild(name);
        text.appendChild(role);

        var arrow = document.createElement('i');
        arrow.className = 'bi bi-chevron-right';
        button.appendChild(avatar);
        button.appendChild(text);
        button.appendChild(arrow);
        button.addEventListener('click', function () {
          selectedAccount = account;
          runScan();
        });
        return button;
      }

      async function loadAccounts(force) {
        if (accountsLoaded && !force) return;
        loading.hidden = false;
        empty.hidden = true;
        list.replaceChildren();
        try {
          var payload = await postJson(<?= json_encode(url('admin/face-auth/accounts')) ?>, {});
          (payload.accounts || []).forEach(function (account) {
            list.appendChild(accountButton(account));
          });
          empty.hidden = (payload.accounts || []).length > 0;
          accountsLoaded = true;
        } catch (error) {
          empty.hidden = false;
          empty.textContent = error.message || 'Daftar akun gagal dimuat.';
        } finally {
          loading.hidden = true;
        }
      }

      async function runScan() {
        if (!selectedAccount || scanning) return;
        scanning = true;
        var runId = ++scanRun;
        picker.hidden = true;
        scannerArea.hidden = false;
        retryButton.hidden = true;
        title.textContent = 'Verifikasi Wajah';
        subtitle.textContent = 'Kedipkan mata atau gerakkan wajah sedikit, lalu lihat ke kamera.';
        selectedBox.textContent = selectedAccount.name + ' · ' + selectedAccount.role;
        setStage('permission');

        try {
          var challengePayload = await postJson(<?= json_encode(url('admin/face-auth/challenge')) ?>, {
            account_token: selectedAccount.token
          });
          if (runId !== scanRun) return;

          var result = await FaceScanner.scan({
            video: video,
            status: status,
            mode: 'login',
            samples: 2,
            timeout: 32000,
            permissionTimeout: 22000,
            challenge: challengePayload.challenge,
            onStage: setStage,
            isCancelled: function () { return runId !== scanRun; }
          });
          if (runId !== scanRun) return;

          setStage('matching');
          FaceScanner.setStatus(status, 'Mencocokkan wajah dengan akun yang dipilih…', 'primary');
          var payload = await postJson(<?= json_encode(url('admin/face-auth/login')) ?>, {
            account_token: selectedAccount.token,
            challenge_nonce: challengePayload.challenge.nonce,
            descriptor: result.descriptor,
            evidence: result.evidence
          });

          setStage('success');
          FaceScanner.setStatus(status, 'Wajah sesuai. Membuka dashboard…', 'success');
          cancelButton.disabled = true;
          cancelButtonSecondary.disabled = true;
          location.href = payload.redirect;
        } catch (error) {
          FaceScanner.stop(video);
          if (runId !== scanRun) return;
          setStage('error');
          FaceScanner.setStatus(status, error.message || 'Login wajah gagal.', 'danger');
          retryButton.hidden = false;
        } finally {
          if (runId === scanRun) scanning = false;
        }
      }

      // Muat model di waktu idle tanpa membuka kamera. Saat admin menekan
      // tombol login wajah, model biasanya sudah siap dan scan dapat dimulai cepat.
      var warmModels = function () { FaceScanner.preload(); };
      if ('requestIdleCallback' in window) {
        window.requestIdleCallback(warmModels, { timeout: 1800 });
      } else {
        window.setTimeout(warmModels, 700);
      }

      openButton.addEventListener('click', function () {
        openPanel();
        showPicker();
        FaceScanner.preload();
        loadAccounts(false);
      });
      retryButton.addEventListener('click', runScan);
      backButton.addEventListener('click', showPicker);
      cancelButton.addEventListener('click', closePanel);
      cancelButtonSecondary.addEventListener('click', closePanel);
      window.addEventListener('pagehide', function () { FaceScanner.stop(video); });
    })();
    </script>
    <?php endif; ?>

    <?php elseif ($mode === 'forgot'): ?>
    <form method="post" action="<?= url('admin/lupa-password') ?>">
      <?= csrf_field() ?>
      <p class="small text-muted">Masukkan username atau nomor WhatsApp akun Anda. Kode OTP 6 digit akan dikirim ke WhatsApp yang terdaftar. Alternatif: minta Super Admin mereset password Anda dari menu User Admin.</p>
      <div class="mb-3"><label class="form-label">Username / Nomor WhatsApp</label><input class="form-control" name="identity" required maxlength="100" autocomplete="username" data-testid="forgot-identity"></div>
      <button class="btn btn-primary w-100 py-2" data-testid="forgot-submit"><i class="bi bi-whatsapp me-1"></i>Kirim Kode OTP via WhatsApp</button>
      <a class="btn btn-link w-100 small" href="<?= url('admin/login') ?>">Kembali ke login</a>
    </form>

    <?php else: /* reset (link lama) atau otp */ ?>
    <form method="post" action="<?= url('admin/lupa-password') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="uid" value="<?= (int) $uid ?>">
      <?php if ($mode === 'otp'): ?>
      <p class="small text-muted">Kode OTP telah dikirim ke WhatsApp <?= e($maskedPhone ?? '') ?>. Kode berlaku 10 menit.</p>
      <div class="mb-3">
        <label class="form-label">Kode OTP (6 digit)</label>
        <input class="form-control text-center fw-bold" name="token" required pattern="\d{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="letter-spacing:.5em;font-size:1.3rem" data-testid="reset-otp">
      </div>
      <?php else: ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">Password Baru (min. 8 karakter)</label>
        <input class="form-control" type="password" name="password_baru" minlength="8" required autocomplete="new-password" data-testid="reset-password">
      </div>
      <div class="mb-3">
        <label class="form-label">Konfirmasi Password Baru</label>
        <input class="form-control" type="password" name="password_konfirmasi" minlength="8" required autocomplete="new-password" data-testid="reset-password-confirm">
      </div>
      <button class="btn btn-primary w-100 py-2" data-testid="reset-submit">Simpan Password Baru</button>
      <?php if ($mode === 'otp'): ?><a class="btn btn-link w-100 small" href="<?= url('admin/lupa-password') ?>">Kirim ulang kode</a><?php endif; ?>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
