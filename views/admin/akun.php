<?php if (!empty($u['must_change_password'])): ?>
<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><strong>Demi keamanan, Anda wajib mengganti password sebelum melanjutkan.</strong></div>
<?php endif; ?>

<form method="post" action="<?= url('admin/akun/simpan') ?>" data-testid="akun-form">
<?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person me-2"></i>Profil Saya</h6></div>
      <div class="acard-body">
        <div class="row g-3 mb-3">
          <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?= e($u['username']) ?>" disabled></div>
          <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" value="<?= e($u['email']) ?>" disabled></div>
        </div>
        <div class="mb-3"><label class="form-label">Nama Lengkap *</label><input class="form-control" name="name" value="<?= e($u['name']) ?>" required data-testid="akun-name"></div>
        <?php if (trim((string) ($u['phone'] ?? '')) === ''): ?><div class="alert alert-warning py-2 small" data-testid="phone-required-alert"><i class="bi bi-whatsapp me-1"></i>Isi nomor WhatsApp terlebih dahulu untuk dapat mengakses fitur admin lainnya.</div><?php endif; ?>
        <div class="mb-2"><label class="form-label">No. WhatsApp *</label><input class="form-control" name="phone" value="<?= e($u['phone'] ?? '') ?>" required inputmode="tel" maxlength="20" placeholder="0812xxxxxxxx" data-testid="akun-phone"></div>
        <div class="form-text">Role: <strong><?= e($u['role_name'] ?? '') ?></strong> · Login terakhir: <?= $u['last_login_at'] ? tgl_id($u['last_login_at'], true) : '-' ?></div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-key me-2"></i>Ganti Password</h6></div>
      <div class="acard-body">
        <div class="mb-3"><label class="form-label">Password Lama</label><input class="form-control" type="password" name="password_lama" autocomplete="current-password" data-testid="akun-old-password"></div>
        <div class="mb-3"><label class="form-label">Password Baru</label><input class="form-control" type="password" name="password_baru" minlength="8" autocomplete="new-password" data-testid="akun-new-password"><div class="form-text">Minimal 8 karakter. Kosongkan jika tidak ingin mengganti.</div></div>
        <div class="mb-2"><label class="form-label">Konfirmasi Password Baru</label><input class="form-control" type="password" name="password_konfirmasi" autocomplete="new-password" data-testid="akun-confirm-password"></div>
      </div>
    </div>
  </div>
</div>
<button class="btn btn-primary px-4" data-testid="akun-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
</form>

<div class="row g-3">
  <div class="col-lg-6">
    <?php if (feature_enabled('face_auth')) require BASE_PATH . '/views/admin/_face_dataset_self.php'; ?>
  </div>
</div>
