<div class="row g-4">
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-phone me-2 text-primary"></i>Identitas Aplikasi</h6></div>
      <div class="acard-body">
        <form method="post" enctype="multipart/form-data" action="<?= url('admin/pwa/simpan') ?>" data-testid="pwa-form">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <div class="mb-3">
            <label class="form-label small">Nama Aplikasi</label>
            <input type="text" name="pwa_name" class="form-control" value="<?= e(setting('pwa_name', '') ?: setting('village_name')) ?>" maxlength="60" data-testid="pwa-name">
            <div class="form-text small">Nama lengkap yang muncul saat instalasi (mis: "Desa Sukamaju").</div>
          </div>
          <div class="mb-3">
            <label class="form-label small">Nama Pendek (max 14 karakter)</label>
            <input type="text" name="pwa_short_name" class="form-control" value="<?= e(setting('pwa_short_name', '')) ?>" maxlength="14" data-testid="pwa-short">
            <div class="form-text small">Muncul di bawah ikon Home Screen. Kosongkan untuk pakai singkatan otomatis.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small">Deskripsi Singkat</label>
            <textarea name="pwa_description" class="form-control" rows="2" maxlength="200"><?= e(setting('pwa_description', '')) ?></textarea>
          </div>
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small">Warna Tema (address bar)</label>
              <input type="color" name="pwa_theme_color" value="<?= e(setting('pwa_theme_color', '') ?: setting('primary_color', '#0f6b4e')) ?>" class="form-control form-control-color">
            </div>
            <div class="col-md-6">
              <label class="form-label small">Warna Latar Splash</label>
              <input type="color" name="pwa_bg_color" value="<?= e(setting('pwa_bg_color', '#ffffff')) ?>" class="form-control form-control-color">
            </div>
          </div>
          <hr>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small"><strong>Ikon 192x192 px</strong></label>
              <?php if (setting('pwa_icon_192')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('pwa_icon_192'))) ?>" style="width:96px;height:96px;border-radius:20px;border:1px solid #e5e7eb"></div><?php endif; ?>
              <input type="file" name="pwa_icon_192" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp" data-testid="pwa-icon-192">
              <div class="form-text small">Untuk ikon Home Screen Android/iOS ukuran standar.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label small"><strong>Ikon 512x512 px</strong></label>
              <?php if (setting('pwa_icon_512')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('pwa_icon_512'))) ?>" style="width:96px;height:96px;border-radius:20px;border:1px solid #e5e7eb"></div><?php endif; ?>
              <input type="file" name="pwa_icon_512" class="form-control form-control-sm" accept="image/png,image/jpeg,image/webp" data-testid="pwa-icon-512">
              <div class="form-text small">Ikon splash screen (saat aplikasi baru dibuka).</div>
            </div>
          </div>
          <hr>
          <button class="btn btn-primary" data-testid="pwa-save"><i class="bi bi-check2 me-1"></i>Simpan Pengaturan PWA</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-info-circle me-2 text-primary"></i>Cara Pengunjung Memasang</h6></div>
      <div class="acard-body small">
        <p><strong>Android (Chrome):</strong> Buka website → Chrome otomatis menawarkan "Instal Aplikasi". Atau menu ⋮ → "Instal aplikasi".</p>
        <p><strong>iOS (Safari):</strong> Tap tombol <i class="bi bi-share"></i> Bagikan → "Tambahkan ke Layar Utama" → Tambah.</p>
        <p><strong>Desktop (Chrome/Edge):</strong> Klik ikon <i class="bi bi-download"></i> di address bar, atau menu → "Instal [nama desa]".</p>
        <div class="alert alert-info small mt-3 mb-0"><i class="bi bi-lightbulb me-1"></i>Tip: gunakan ikon dgn background solid + margin 10% supaya tidak terpotong oleh cropping OS.</div>
      </div>
    </div>
  </div>
</div>
