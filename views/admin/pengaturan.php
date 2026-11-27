<?php
// $tab: umum | tampilan | sosial | fitur
$tabs = ['umum' => 'Informasi Umum', 'tampilan' => 'Tampilan & Tema', 'sosial' => 'Media Sosial', 'fitur' => 'Fitur Website', 'notifweb' => 'Notifikasi Web', 'notifikasi' => 'Notifikasi WhatsApp'];
$s = fn($k, $d = '') => e(setting($k, $d));
?>
<ul class="nav nav-pills mb-3 settings-tabs" data-testid="settings-tabs">
  <?php foreach ($tabs as $tk => $tl): ?>
  <li class="nav-item"><a class="nav-link <?= $tab === $tk ? 'active' : '' ?>" href="<?= url('admin/pengaturan?tab=' . $tk) ?>" data-testid="settings-tab-<?= $tk ?>"><?= $tl ?></a></li>
  <?php endforeach; ?>
</ul>

<form method="post" action="<?= url('admin/pengaturan/simpan') ?>" enctype="multipart/form-data" class="settings-form" data-testid="settings-form">
<?= csrf_field() ?>
<input type="hidden" name="group" value="<?= e($tab) ?>">

<?php if ($tab === 'umum'): ?>
<div class="row g-3">
  <div class="col-lg-8">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Identitas Desa</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-6"><label class="form-label">Nama Desa *</label><input class="form-control" name="village_name" value="<?= $s('village_name') ?>" required data-testid="setting-village-name"></div>
        <div class="col-md-6"><label class="form-label">Tagline / Slogan</label><input class="form-control" name="village_tagline" value="<?= $s('village_tagline') ?>"></div>
        <div class="col-12"><label class="form-label">Deskripsi Singkat</label><textarea class="form-control" name="village_description" rows="2"><?= $s('village_description') ?></textarea></div>
        <div class="col-12"><label class="form-label">Alamat Kantor Desa</label><input class="form-control" name="address" value="<?= $s('address') ?>"></div>
        <div class="col-md-4"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan" value="<?= $s('kecamatan') ?>"></div>
        <div class="col-md-4"><label class="form-label">Kabupaten</label><input class="form-control" name="kabupaten" value="<?= $s('kabupaten') ?>"></div>
        <div class="col-md-4"><label class="form-label">Provinsi</label><input class="form-control" name="provinsi" value="<?= $s('provinsi') ?>"></div>
        <div class="col-md-4"><label class="form-label">Kode Pos</label><input class="form-control" name="kode_pos" value="<?= $s('kode_pos') ?>"></div>
        <div class="col-md-4"><label class="form-label">Luas Wilayah</label><input class="form-control" name="luas_wilayah" value="<?= $s('luas_wilayah') ?>"></div>
        <div class="col-md-4"><label class="form-label">Jam Pelayanan</label><input class="form-control" name="jam_pelayanan" value="<?= $s('jam_pelayanan') ?>"></div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-telephone me-2"></i>Kontak</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-4"><label class="form-label">Telepon</label><input class="form-control" name="phone" value="<?= $s('phone') ?>"></div>
        <div class="col-md-4"><label class="form-label">WhatsApp (62xxx)</label><input class="form-control" name="whatsapp" value="<?= $s('whatsapp') ?>"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="<?= $s('email') ?>"></div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Lokasi Peta</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-4"><label class="form-label">Latitude</label><input class="form-control" name="map_lat" value="<?= $s('map_lat') ?>"></div>
        <div class="col-md-4"><label class="form-label">Longitude</label><input class="form-control" name="map_lng" value="<?= $s('map_lng') ?>"></div>
        <div class="col-md-4"><label class="form-label">Zoom (1-20)</label><input class="form-control" type="number" name="map_zoom" min="1" max="20" value="<?= $s('map_zoom', '14') ?>"></div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-search me-2"></i>SEO</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-6"><label class="form-label">Meta Title</label><input class="form-control" name="meta_title" value="<?= $s('meta_title') ?>"></div>
        <div class="col-md-6"><label class="form-label">Meta Description</label><input class="form-control" name="meta_description" value="<?= $s('meta_description') ?>"></div>
        <div class="col-md-6">
          <label class="form-label">Gambar OG (share media sosial)</label>
          <?php if (setting('og_image')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('og_image'))) ?>" class="rounded" style="max-height:70px" alt=""></div><?php endif; ?>
          <input class="form-control" type="file" name="og_image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Kepala Desa</h6></div>
      <div class="acard-body">
        <div class="mb-3"><label class="form-label">Nama Kepala Desa</label><input class="form-control" name="kepala_desa_nama" value="<?= $s('kepala_desa_nama') ?>"></div>
        <div class="mb-3">
          <label class="form-label">Foto Kepala Desa</label>
          <?php if (setting('kepala_desa_foto')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('kepala_desa_foto'))) ?>" class="rounded" style="max-height:120px" alt=""></div><?php endif; ?>
          <input class="form-control" type="file" name="kepala_desa_foto" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <div><label class="form-label">Kata Sambutan</label><textarea class="form-control" name="sambutan" rows="8"><?= $s('sambutan') ?></textarea></div>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'tampilan'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-image me-2"></i>Branding</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Logo Desa</label>
          <?php if (setting('logo')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('logo'))) ?>" style="max-height:60px" alt="Logo"></div><?php endif; ?>
          <input class="form-control" type="file" name="logo" accept=".jpg,.jpeg,.png,.webp,.svg" data-testid="setting-logo">
          <div class="form-text">PNG/SVG transparan disarankan.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Favicon</label>
          <?php if (setting('favicon')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('favicon'))) ?>" style="max-height:32px" alt=""></div><?php endif; ?>
          <input class="form-control" type="file" name="favicon" accept=".png,.ico,.svg">
        </div>
        <div class="col-12">
          <label class="form-label">Gambar Hero Beranda</label>
          <?php if (setting('hero_image')): ?><div class="mb-2"><img src="<?= e(upload_url(setting('hero_image'))) ?>" class="rounded w-100" style="max-height:120px;object-fit:cover" alt=""></div><?php endif; ?>
          <input class="form-control" type="file" name="hero_image" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <div class="col-12" data-testid="hero-scenery-settings">
          <label class="form-label fw-semibold"><i class="bi bi-image-alt me-1"></i>Pemandangan Hero (lapisan parallax)</label>
          <p class="small text-muted mb-2">Lapisan bergerak dengan kecepatan berbeda saat halaman digulir. Gunakan PNG/WEBP transparan untuk lapisan bukit & desa. Kosongkan untuk memakai gambar bawaan. Jika video hero aktif, video menggantikan lapisan langit.</p>
          <div class="row g-3">
            <?php foreach (['hero_layer_sky' => ['Langit', 'sky.webp'], 'hero_layer_hills' => ['Bukit / Gunung Lawu', 'hills.webp'], 'hero_layer_village' => ['Siluet Desa', 'village.webp'], 'hero_diorama_image' => ['Diorama (tampilan ponsel)', 'diorama.webp']] as $lk => [$llabel, $ldef]): ?>
            <div class="col-md-6">
              <div class="border rounded-3 p-2 h-100">
                <div class="small fw-semibold mb-1"><?= e($llabel) ?></div>
                <img src="<?= e(hero_layer_url($lk, $ldef)) ?>" class="rounded w-100 mb-2" style="height:80px;object-fit:contain;background:#eef7f4" alt="">
                <input class="form-control form-control-sm" type="file" name="<?= e($lk) ?>" accept=".png,.webp,.jpg,.jpeg" data-testid="setting-<?= e($lk) ?>">
                <?php if (setting($lk)): ?><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_<?= e($lk) ?>" id="rm_<?= e($lk) ?>"><label class="form-check-label small" for="rm_<?= e($lk) ?>">Kembalikan ke bawaan</label></div><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label"><i class="bi bi-camera-video me-1"></i>Video Background Hero (MP4/WEBM, maks 25 MB)</label>
          <?php if (setting('hero_video')): ?>
          <div class="mb-2"><video src="<?= e(upload_url(setting('hero_video'))) ?>" class="rounded w-100" style="max-height:150px" controls muted preload="metadata"></video></div>
          <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="remove_hero_video" id="rmv" data-testid="setting-remove-hero-video"><label class="form-check-label" for="rmv">Hapus video ini</label></div>
          <?php endif; ?>
          <input class="form-control" type="file" name="hero_video" accept=".mp4,.webm" data-testid="setting-hero-video">
          <div class="form-check form-switch mt-2">
            <input class="form-check-input" type="checkbox" name="hero_video_enabled" id="hve" <?= setting('hero_video_enabled') === '1' ? 'checked' : '' ?> data-testid="setting-hero-video-enabled">
            <label class="form-check-label" for="hve">Gunakan video sebagai background hero (menggantikan animasi langit)</label>
          </div>
          <div class="form-text">Gunakan video pendek (10-30 detik) beresolusi 1280-1920px agar ringan. Video otomatis diputar tanpa suara & berulang.</div>
        </div>
        <div class="col-12">
          <hr>
          <label class="form-label"><i class="bi bi-magic me-1"></i>Model Tampilan Hero</label>
          <?php $hs = setting('hero_style', 'classic'); ?>
          <select class="form-select mb-2" name="hero_style" data-testid="setting-hero-style">
            <option value="classic" <?= $hs==='classic'?'selected':'' ?>>Klasik — langit, awan, burung, bukit, kunang-kunang</option>
            <option value="aurora" <?= $hs==='aurora'?'selected':'' ?>>Aurora — 3 blob warna bergerak lambat (modern & tenang)</option>
            <option value="gradient" <?= $hs==='gradient'?'selected':'' ?>>Gradient Modern — warna latar bergeser 4-warna</option>
            <option value="particle" <?= $hs==='particle'?'selected':'' ?>>Particle — 30 titik cahaya naik pelan (cinematic)</option>
            <option value="minimal" <?= $hs==='minimal'?'selected':'' ?>>Minimal — polos gelap, fokus judul saja</option>
          </select>
          <label class="form-label small">Elemen yang tampil (centang):</label>
          <?php $eSet = array_flip(array_filter(array_map('trim', explode(',', setting('hero_elements', 'stars,clouds,birds,hills,fireflies,badge,tagline,buttons'))))); ?>
          <div class="row g-2 mb-2">
            <?php foreach (['stars'=>'Bintang','clouds'=>'Awan','birds'=>'Burung','hills'=>'Bukit','fireflies'=>'Kunang-kunang','badge'=>'Badge','tagline'=>'Tagline','buttons'=>'Tombol Aksi'] as $k=>$lab): ?>
            <div class="col-6 col-md-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="hero_elements[]" value="<?= $k ?>" id="he_<?= $k ?>" <?= isset($eSet[$k])?'checked':'' ?> data-testid="hero-el-<?= $k ?>"><label class="form-check-label small" for="he_<?= $k ?>"><?= $lab ?></label></div></div>
            <?php endforeach; ?>
          </div>
          <label class="form-label small">Tombol yang tampil (pisahkan koma):</label>
          <input class="form-control form-control-sm" name="hero_buttons" value="<?= e(setting('hero_buttons', 'profil,layanan,berita,pengaduan,statistik')) ?>" data-testid="hero-buttons">
          <div class="form-text small">Pilihan: profil, layanan, berita, pengaduan, statistik, idm, bansos, transparansi. Tombol pertama berwarna aksen; sisanya glass.</div>
        </div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-palette me-2"></i>Warna Tema Website</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-3 col-6"><label class="form-label">Warna Utama</label><input class="form-control form-control-color w-100" type="color" name="primary_color" value="<?= e(setting_color('primary_color', '#0f6b4e')) ?>" data-testid="setting-primary-color"></div>
        <div class="col-md-3 col-6"><label class="form-label">Warna Sekunder</label><input class="form-control form-control-color w-100" type="color" name="secondary_color" value="<?= e(setting_color('secondary_color', '#0b3d2e')) ?>"></div>
        <div class="col-md-3 col-6"><label class="form-label">Warna Aksen</label><input class="form-control form-control-color w-100" type="color" name="accent_color" value="<?= e(setting_color('accent_color', '#e8b44c')) ?>"></div>
        <div class="col-md-3 col-6">
          <label class="form-label">Warna Ikon Verify</label>
          <input class="form-control form-control-color w-100" type="color" name="verify_color" id="settingVerifyColor" data-testid="setting-verify-color" value="<?= e(setting_color('verify_color', '#34d399')) ?>">
        </div>
        <div class="col-md-4 col-12">
          <label class="form-label">Bentuk Ikon Verify</label>
          <?php $verifyIcon = verify_icon_class(); ?>
          <select class="form-select" name="verify_icon" id="settingVerifyIcon" data-testid="setting-verify-icon">
            <?php foreach ([
              'bi-patch-check-fill' => 'Patch Check Penuh',
              'bi-patch-check' => 'Patch Check Garis',
              'bi-check-circle-fill' => 'Centang Lingkaran',
              'bi-shield-check' => 'Perisai Centang',
              'bi-award-fill' => 'Lencana Resmi',
            ] as $iconValue => $iconLabel): ?>
            <option value="<?= e($iconValue) ?>" <?= $verifyIcon === $iconValue ? 'selected' : '' ?>><?= e($iconLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-8 col-12">
          <label class="form-label">Logo Verify Khusus <span class="text-muted fw-normal">(opsional)</span></label>
          <input class="form-control" type="file" name="verify_logo" id="settingVerifyLogo" accept=".svg,.png,.jpg,.jpeg,.webp" data-testid="setting-verify-logo">
          <div class="form-text">Gunakan ikon transparan. Logo akan mengikuti warna ikon verify.</div>
          <?php if (setting('verify_logo')): ?>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="remove_verify_logo" id="removeVerifyLogo" value="1">
            <label class="form-check-label small" for="removeVerifyLogo">Hapus logo khusus dan gunakan bentuk ikon</label>
          </div>
          <?php endif; ?>
        </div>
        <div class="col-12">
          <span class="setting-verify-preview" id="settingVerifyBadge">
            <span id="settingVerifyPreviewWrap"><?= verify_mark_html('setting-verify-mark') ?></span>
            Website Resmi Pemerintah Desa
          </span>
        </div>
      </div>
    </div>

    <div class="acard mb-3">
      <div class="acard-head">
        <div>
          <h6 class="mb-1"><i class="bi bi-ui-checks me-2"></i>Warna Tombol Kustom (per-tombol)</h6>
          <small class="text-muted">Atur warna setiap tombol yang tampil di sisi pengunjung secara individual. Tombol lain (Simpan, Kirim, Batal, dll.) tetap mengikuti <strong>Warna Utama</strong>. Tombol pendukung yang dikosongkan akan mengikuti <strong>Warna Tombol Pendukung (Global)</strong>.</small>
        </div>
      </div>
      <div class="acard-body">
        <div class="row g-3">
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-broadcast-pin"></i> Global Pendukung</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="button_floating_color" id="settingButtonFloatingColor" data-testid="setting-button-floating-color" data-var="--c-button-floating" value="<?= e(setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e'))) ?>">
            <div class="form-text small">Fallback semua tombol pendukung.</div>
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-info-circle"></i> Hero (Profil Desa dsb)</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_hero_primary_color" id="settingBtnHeroPrimary" data-testid="setting-btn-hero-primary-color" data-var="--c-btn-hero-primary" value="<?= e(setting_color('btn_hero_primary_color', setting_color('primary_color', '#0f6b4e'))) ?>">
            <div class="form-text small">Tombol utama di hero halaman depan.</div>
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-arrow-right-circle"></i> CTA Section Publik</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_section_cta_color" id="settingBtnSectionCta" data-testid="setting-btn-section-cta-color" data-var="--c-btn-section-cta" value="<?= e(setting_color('btn_section_cta_color', setting_color('primary_color', '#0f6b4e'))) ?>">
            <div class="form-text small">Lihat Semua Berita, Layanan, Agenda, dll.</div>
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-chat-dots"></i> Live Chat</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_livechat_color" id="settingBtnLivechat" data-testid="setting-btn-livechat-color" data-var="--c-btn-livechat" value="<?= e(setting_color('btn_livechat_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-chat-square-heart"></i> Feedback</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_feedback_color" id="settingBtnFeedback" data-testid="setting-btn-feedback-color" data-var="--c-btn-feedback" value="<?= e(setting_color('btn_feedback_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-arrow-up-square"></i> Back-to-Top</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_backtotop_color" id="settingBtnBackToTop" data-testid="setting-btn-backtotop-color" data-var="--c-btn-backtotop" value="<?= e(setting_color('btn_backtotop_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-bell"></i> Lonceng Notif</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_bell_color" id="settingBtnBell" data-testid="setting-btn-bell-color" data-var="--c-btn-bell" value="<?= e(setting_color('btn_bell_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-megaphone"></i> Notif Toast</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_notif_toast_color" id="settingBtnNotifToast" data-testid="setting-btn-notif-toast-color" data-var="--c-btn-notif-toast" value="<?= e(setting_color('btn_notif_toast_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
          <div class="col-md-4 col-6">
            <label class="form-label small mb-1"><i class="bi bi-phone"></i> Install App (PWA)</label>
            <input class="form-control form-control-color w-100 setting-btn-color" type="color" name="btn_install_color" id="settingBtnInstall" data-testid="setting-btn-install-color" data-var="--c-btn-install" value="<?= e(setting_color('btn_install_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>">
          </div>
        </div>
        <div class="setting-button-preview mt-3" aria-label="Preview warna tombol">
          <span class="text-muted small">Preview:</span>
          <button type="button" class="setting-preview-floating" id="settingButtonFloatingPreview" aria-label="Preview" style="background:var(--c-btn-livechat)"><i class="bi bi-chat-dots-fill"></i></button>
          <button type="button" class="setting-preview-floating" aria-label="Preview" style="background:var(--c-btn-feedback)"><i class="bi bi-chat-square-heart-fill"></i></button>
          <button type="button" class="setting-preview-floating" aria-label="Preview" style="background:var(--c-btn-backtotop)"><i class="bi bi-arrow-up"></i></button>
          <button type="button" class="setting-preview-floating" aria-label="Preview" style="background:var(--c-btn-bell)"><i class="bi bi-bell-fill"></i></button>
          <button type="button" class="setting-preview-floating" aria-label="Preview" style="background:var(--c-btn-install)"><i class="bi bi-phone"></i></button>
        </div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-fonts me-2"></i>Font</h6></div>
      <div class="acard-body row g-3">
        <?php $fonts = ['Bricolage Grotesque', 'Plus Jakarta Sans', 'Poppins', 'Montserrat', 'Merriweather', 'Source Sans 3', 'Nunito', 'Lora']; ?>
        <div class="col-md-6"><label class="form-label">Font Judul</label>
          <select class="form-select" name="font_heading"><?php foreach ($fonts as $f): ?><option <?= setting('font_heading') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-6"><label class="form-label">Font Isi</label>
          <select class="form-select" name="font_body"><?php foreach ($fonts as $f): ?><option <?= setting('font_body') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-layout-text-window me-2"></i>Gaya Layout</h6></div>
      <div class="acard-body row g-3">
        <div class="col-md-6"><label class="form-label">Gaya Tampilan</label>
          <select class="form-select" name="layout_style" data-testid="setting-layout-style">
            <?php foreach (['3d-premium' => '3D Premium (Modern)', 'flat' => 'Flat Minimalis', 'classic' => 'Klasik Pemerintahan'] as $lv => $ll): ?>
            <option value="<?= $lv ?>" <?= setting('layout_style') === $lv ? 'selected' : '' ?>><?= $ll ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Radius Kartu (px)</label><input class="form-control" type="number" name="card_radius" min="0" max="40" value="<?= $s('card_radius', '18') ?>"></div>
        <div class="col-md-6"><label class="form-label">Gaya Tombol</label>
          <select class="form-select" name="button_style">
            <?php foreach (['pill' => 'Pill (Bulat)', 'rounded' => 'Rounded', 'sharp' => 'Kotak Tajam'] as $bv => $bl): ?>
            <option value="<?= $bv ?>" <?= setting('button_style') === $bv ? 'selected' : '' ?>><?= $bl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Gaya Navbar</label>
          <select class="form-select" name="navbar_style">
            <?php foreach (['glass' => 'Glass (Transparan Blur)', 'solid' => 'Solid', 'gradient' => 'Gradien'] as $nv => $nl): ?>
            <option value="<?= $nv ?>" <?= setting('navbar_style') === $nv ? 'selected' : '' ?>><?= $nl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Gaya Footer</label>
          <select class="form-select" name="footer_style">
            <?php foreach (['dark' => 'Gelap', 'light' => 'Terang', 'gradient' => 'Gradien'] as $fv => $fl): ?>
            <option value="<?= $fv ?>" <?= setting('footer_style') === $fv ? 'selected' : '' ?>><?= $fl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label">Tema Default</label>
          <select class="form-select" name="default_theme">
            <option value="light" <?= setting('default_theme') === 'light' ? 'selected' : '' ?>>Terang</option>
            <option value="dark" <?= setting('default_theme') === 'dark' ? 'selected' : '' ?>>Gelap</option>
          </select>
        </div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-house me-2"></i>Urutan Section Beranda</h6></div>
      <div class="acard-body">
<?php
        $secLabels = [
            'stats' => ['bi-bar-chart', 'Statistik Ringkas'], 'banner' => ['bi-film', 'Banner Slider Galeri'],
            'transparansi' => ['bi-shield-check', 'Transparansi'], 'sambutan' => ['bi-person-badge', 'Sambutan Kepala Desa'],
            'layanan' => ['bi-file-earmark-text', 'Layanan Surat'], 'berita' => ['bi-newspaper', 'Berita Terbaru'],
            'pengumuman' => ['bi-megaphone', 'Pengumuman & Agenda'],
            'perangkat' => ['bi-people', 'Perangkat Desa'], 'galeri' => ['bi-images', 'Galeri Foto'],
            'umkm' => ['bi-shop', 'UMKM & Potensi Desa'],
            'social' => ['bi-share', 'Media Sosial'], 'peta' => ['bi-geo-alt', 'Peta Lokasi'],
        ];
        // Section gabungan: agenda ikut pengumuman, potensi ikut UMKM (tampil satu blok di beranda).
        $curSecs = array_map(static fn($k) => ['agenda' => 'pengumuman', 'potensi' => 'umkm'][$k] ?? $k, array_filter(array_map('trim', explode(',', setting('home_sections', 'stats,transparansi,sambutan,layanan,berita,pengumuman,agenda,perangkat,galeri,potensi,umkm,social,peta')))));
        $curSecs = array_values(array_unique($curSecs));
        $bannerOn = setting('home_banner_enabled', '1') === '1';
        if (!in_array('banner', $curSecs, true)) { $si = array_search('stats', $curSecs, true); array_splice($curSecs, $si === false ? 0 : $si + 1, 0, ['banner']); }
        $allSecs = array_merge($curSecs, array_values(array_diff(array_keys($secLabels), $curSecs)));
        ?>
        <label class="form-label">Seret (atau pakai tombol panah) untuk mengatur urutan. Matikan saklar untuk menyembunyikan section.</label>
        <input type="hidden" name="home_sections" id="homeSectionsInput" value="<?= $s('home_sections') ?>" data-testid="setting-home-sections-input">
        <ol class="sec-sort" id="secSort" data-testid="home-sections-sorter">
          <?php foreach ($allSecs as $sk): [$sic, $slab] = $secLabels[$sk] ?? ['bi-square', ucfirst($sk)]; $on = $sk === 'banner' ? $bannerOn : in_array($sk, $curSecs, true); ?>
          <li class="sec-item<?= $sk === 'banner' ? ' is-banner' : '' ?><?= $on ? '' : ' is-off' ?>" draggable="true" data-key="<?= e($sk) ?>" data-testid="home-section-<?= e($sk) ?>">
            <span class="sec-grip" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
            <span class="sec-no"></span>
            <i class="bi <?= $sic ?> sec-ic"></i>
            <span class="sec-name"><?= e($slab) ?><?php if ($sk === 'banner'): ?> <small>· foto dipilih di menu <a href="<?= url('admin/galeri') ?>">Galeri</a></small><?php endif; ?></span>
            <button type="button" class="btn btn-sm btn-light sec-up" aria-label="Naikkan" data-testid="home-section-up-<?= e($sk) ?>"><i class="bi bi-arrow-up"></i></button>
            <button type="button" class="btn btn-sm btn-light sec-down" aria-label="Turunkan" data-testid="home-section-down-<?= e($sk) ?>"><i class="bi bi-arrow-down"></i></button>
            <div class="form-check form-switch m-0">
              <input class="form-check-input sec-on" type="checkbox" <?= $sk === 'banner' ? 'name="home_banner_enabled" id="hbe"' : '' ?> <?= $on ? 'checked' : '' ?> aria-label="Tampilkan <?= e($slab) ?>" data-testid="<?= $sk === 'banner' ? 'setting-home-banner-enabled' : 'home-section-toggle-' . e($sk) ?>">
            </div>
          </li>
          <?php endforeach; ?>
        </ol>
        <div class="form-text">Posisi <b>Banner Slider Galeri</b> bisa diletakkan di mana saja (paling atas, di antara section, atau paling bawah). Hero selalu tampil paling atas.</div>
        <style>
          .sec-sort{list-style:none;padding:0;margin:.5rem 0 0;display:flex;flex-direction:column;gap:.45rem}
          .sec-item{display:flex;align-items:center;gap:.6rem;padding:.55rem .7rem;border:1px solid var(--bs-border-color,#e3efeb);border-radius:12px;background:var(--bs-body-bg,#fff);cursor:grab;transition:box-shadow .2s,opacity .2s,transform .2s,background-color .2s}
          .sec-item:hover{box-shadow:0 6px 18px -10px rgba(15,64,52,.35)}
          .sec-item.is-drag{opacity:.45;transform:scale(.98)}
          .sec-item.is-over{box-shadow:0 0 0 2px #00B589}
          .sec-item.is-off{opacity:.55}
          .sec-item.is-off .sec-name{text-decoration:line-through}
          .sec-item.is-banner{background:linear-gradient(90deg,rgba(0,181,137,.08),transparent)}
          .sec-grip{color:#94a3b8;font-size:1.1rem}
          .sec-no{min-width:1.6rem;font-weight:800;font-size:.78rem;color:#00805F}
          .sec-ic{color:#00B589}
          .sec-name{flex:1;font-weight:600;font-size:.92rem}
          .sec-name small{font-weight:500;color:#64748b}
        </style>
        <script>
        (function () {
          var list = document.getElementById('secSort'), input = document.getElementById('homeSectionsInput');
          if (!list) return;
          function sync() {
            var keys = [];
            Array.prototype.forEach.call(list.children, function (li, i) {
              var on = li.querySelector('.sec-on').checked;
              li.querySelector('.sec-no').textContent = String(i + 1).padStart(2, '0');
              li.classList.toggle('is-off', !on);
              // Banner selalu disimpan di urutan agar posisinya tetap; tampil/sembunyi diatur saklarnya.
              if (on || li.dataset.key === 'banner') {
                keys.push(li.dataset.key);
                if (on && li.dataset.key === 'pengumuman') keys.push('agenda');
                if (on && li.dataset.key === 'umkm') keys.push('potensi');
              }
            });
            input.value = keys.join(',');
          }
          var dragEl = null;
          list.addEventListener('dragstart', function (e) { dragEl = e.target.closest('.sec-item'); if (!dragEl) return; dragEl.classList.add('is-drag'); e.dataTransfer.effectAllowed = 'move'; });
          list.addEventListener('dragend', function () { if (dragEl) dragEl.classList.remove('is-drag'); dragEl = null; list.querySelectorAll('.is-over').forEach(function (x) { x.classList.remove('is-over'); }); sync(); });
          list.addEventListener('dragover', function (e) {
            e.preventDefault();
            var over = e.target.closest('.sec-item');
            if (!over || !dragEl || over === dragEl) return;
            var r = over.getBoundingClientRect();
            list.insertBefore(dragEl, (e.clientY - r.top) > r.height / 2 ? over.nextSibling : over);
          });
          list.addEventListener('click', function (e) {
            var li = e.target.closest('.sec-item'); if (!li) return;
            if (e.target.closest('.sec-up') && li.previousElementSibling) list.insertBefore(li, li.previousElementSibling);
            else if (e.target.closest('.sec-down') && li.nextElementSibling) list.insertBefore(li.nextElementSibling, li);
            else return;
            sync();
          });
          list.addEventListener('change', sync);
          sync();
        })();
        </script>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'sosial'): ?>
<?php $socialPreviewItems = social_embed_items(); ?>
<div class="settings-social-layout">
  <section class="acard settings-panel">
    <div class="acard-head">
      <div>
        <h6 class="mb-1"><i class="bi bi-share me-2"></i>Akun Media Sosial</h6>
        <p class="small text-muted mb-0">Tautan ini digunakan untuk ikon sosial di bagian website.</p>
      </div>
    </div>
    <div class="acard-body">
      <div class="row g-3">
        <?php foreach (['facebook' => 'bi-facebook', 'instagram' => 'bi-instagram', 'youtube' => 'bi-youtube', 'twitter' => 'bi-twitter-x', 'tiktok' => 'bi-tiktok'] as $sk => $icon): ?>
        <div class="col-md-6">
          <label class="form-label text-capitalize" for="social-<?= $sk ?>"><i class="bi <?= $icon ?> me-1"></i><?= $sk ?></label>
          <input class="form-control" id="social-<?= $sk ?>" type="text" inputmode="url" name="<?= $sk ?>" value="<?= $s($sk) ?>" placeholder="https://...">
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="acard settings-panel">
    <div class="acard-head">
      <div>
        <h6 class="mb-1"><i class="bi bi-play-btn me-2"></i>Konten Sosial di Beranda</h6>
        <p class="small text-muted mb-0">Video dan post tampil langsung di website, tanpa membuka halaman luar.</p>
      </div>
      <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" name="home_social_enabled" id="homeSocialEnabled" <?= setting('home_social_enabled', '1') === '1' ? 'checked' : '' ?>>
        <label class="form-check-label small fw-semibold" for="homeSocialEnabled">Tampilkan</label>
      </div>
    </div>
    <div class="acard-body">
      <div class="mb-3">
        <label class="form-label" for="homeSocialTitle">Judul bagian</label>
        <input class="form-control" id="homeSocialTitle" name="home_social_title" value="<?= $s('home_social_title', 'Media Sosial Desa') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label" for="homeSocialItems">URL video atau post</label>
        <textarea
          class="form-control social-content-input"
          id="homeSocialItems"
          name="home_social_items"
          rows="7"
          placeholder="Tempel satu URL per baris&#10;https://youtu.be/VIDEO_ID&#10;https://www.instagram.com/reel/KODE/&#10;https://www.tiktok.com/@akun/video/ID"
        ><?= e(setting('home_social_items')) ?></textarea>
        <div class="social-input-help">
          <span><i class="bi bi-youtube"></i> Video, Shorts, playlist, atau channel ID YouTube</span>
          <span><i class="bi bi-instagram"></i> Post atau Reel Instagram</span>
          <span><i class="bi bi-tiktok"></i> Video TikTok</span>
        </div>
        <div class="form-text">URL profil tetap dipakai untuk ikon sosial, tetapi tidak dapat ditanam sebagai feed. Untuk beranda, gunakan URL konten tertentu agar gambar atau videonya tampil langsung.</div>
      </div>

      <div class="social-admin-summary">
        <span class="status-dot <?= $socialPreviewItems ? 'is-ready' : '' ?>"></span>
        <strong><?= count($socialPreviewItems) ?> konten siap tampil</strong>
      </div>

      <?php if ($socialPreviewItems): ?>
      <div class="social-admin-preview" aria-label="Preview konten sosial">
        <?php foreach (array_slice($socialPreviewItems, 0, 6) as $item):
          $icon = ['youtube'=>'bi-youtube','instagram'=>'bi-instagram','tiktok'=>'bi-tiktok'][$item['platform']] ?? 'bi-play-circle';
          $ratioClass = ($item['ratio'] ?? '') === 'wide' ? 'is-wide' : 'is-portrait';
        ?>
        <div class="social-admin-item <?= e($ratioClass) ?>">
          <div class="social-admin-frame">
            <iframe
              src="<?= e($item['embed_url']) ?>"
              title="<?= e($item['title']) ?>"
              loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen"
              referrerpolicy="strict-origin-when-cross-origin"
              allowfullscreen
            ></iframe>
          </div>
          <div class="social-admin-meta"><i class="bi <?= e($icon) ?>"></i><span><?= e($item['title']) ?></span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="social-admin-empty">
        <i class="bi bi-play-btn"></i>
        <div><strong>Belum ada konten yang dapat ditampilkan.</strong><br><span>Tempel URL video atau post pada kolom di atas, lalu simpan.</span></div>
      </div>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php elseif ($tab === 'fitur'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>Login Admin dengan Wajah</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="face_login_enabled" id="faceLoginEnabled" <?= setting('face_login_enabled', '1') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="faceLoginEnabled">Aktifkan opsi scan wajah di halaman login admin</label>
        </div>
        <div class="alert alert-info py-2 small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Dataset wajah ditambahkan dari menu <strong>User Admin → Edit User</strong>. Kamera browser hanya bekerja pada HTTPS atau localhost. Password tetap tersedia sebagai metode pemulihan.</div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-shield-check me-2"></i>Proteksi Foto Galeri</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="photo_protection" id="pp" <?= setting('photo_protection') === '1' ? 'checked' : '' ?> data-testid="setting-photo-protection">
          <label class="form-check-label" for="pp">Aktifkan proteksi foto (blokir klik kanan & drag pada galeri)</label>
        </div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="photo_watermark" id="pw" <?= setting('photo_watermark') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="pw">Tambahkan watermark otomatis pada foto galeri</label>
        </div>
        <label class="form-label">Teks Watermark</label>
        <input class="form-control" name="watermark_text" value="<?= $s('watermark_text') ?>">
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-music-note-beamed me-2"></i>Musik Latar</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="music_enabled" id="me" <?= setting('music_enabled') === '1' ? 'checked' : '' ?> data-testid="setting-music-enabled">
          <label class="form-check-label" for="me">Aktifkan pemutar musik latar di website publik</label>
        </div>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="music_autoplay" id="ma" <?= setting('music_autoplay', '1') === '1' ? 'checked' : '' ?> data-testid="setting-music-autoplay">
          <label class="form-check-label" for="ma">Autoplay otomatis (mulai putar musik saat pengunjung interaksi pertama — tap/scroll/klik)</label>
        </div>
        <div class="form-check form-switch mt-2">
          <input class="form-check-input" type="checkbox" name="music_button_visible" id="mbv" <?= setting('music_button_visible', '1') === '1' ? 'checked' : '' ?> data-testid="setting-music-button-visible">
          <label class="form-check-label" for="mbv">Tampilkan tombol/kontrol musik ke pengunjung (jika dimatikan, musik tetap play namun tombol tersembunyi)</label>
        </div>
        <div class="form-text mt-2">Kelola daftar lagu melalui menu <a href="<?= url('admin/musik') ?>">Musik Website</a>.</div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Komentar Berita</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="comments_enabled" id="ce" <?= setting('comments_enabled') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="ce">Izinkan pengunjung berkomentar di berita</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="comment_moderation" id="cm" <?= setting('comment_moderation') === '1' ? 'checked' : '' ?>>
          <label class="form-check-label" for="cm">Moderasi komentar (harus disetujui admin sebelum tampil)</label>
        </div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-stars me-2"></i>Efek Event</h6></div>
      <div class="acard-body">
        <div class="form-text">Efek confetti/salju/kembang api untuk hari besar dikelola melalui menu <a href="<?= url('admin/event') ?>">Event Experience</a>.</div>
      </div>
    </div>
    <div class="acard mb-3 d-none" aria-hidden="true">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-magic me-2"></i>Animasi Background Interaktif</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="bgfx_enabled" id="bgfx" <?= setting('bgfx_enabled', '1') === '1' ? 'checked' : '' ?> data-testid="setting-bgfx-enabled">
          <label class="form-check-label" for="bgfx">Aktifkan animasi partikel interaktif di background website (mengikuti kursor pengunjung)</label>
        </div>
        <label class="form-label">Kepadatan Partikel</label>
        <select class="form-select" name="bgfx_density" data-testid="setting-bgfx-density">
          <?php foreach (['rendah' => 'Rendah (paling ringan)', 'sedang' => 'Sedang (disarankan)', 'tinggi' => 'Tinggi'] as $bv => $bl): ?>
          <option value="<?= $bv ?>" <?= setting('bgfx_density', 'sedang') === $bv ? 'selected' : '' ?>><?= $bl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-camera-fill me-2"></i>Proteksi Screenshot</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="screenshot_protection" id="ssp" <?= setting('screenshot_protection') === '1' ? 'checked' : '' ?> data-testid="setting-screenshot-protection">
          <label class="form-check-label" for="ssp">Aktifkan proteksi screenshot di seluruh halaman publik</label>
        </div>
        <label class="form-label">Tingkat Proteksi</label>
        <select class="form-select mb-2" name="screenshot_mode" data-testid="setting-screenshot-mode">
          <option value="ketat" <?= setting('screenshot_mode', 'ketat') === 'ketat' ? 'selected' : '' ?>>Ketat — konten diburamkan, hanya area sekitar kursor/sentuhan yang terlihat</option>
          <option value="normal" <?= setting('screenshot_mode', 'ketat') === 'normal' ? 'selected' : '' ?>>Normal — website tetap tampil biasa, screenshot diperbolehkan namun upaya tetap dicatat & notifikasi WA tetap terkirim</option>
          <option value="standar" <?= setting('screenshot_mode', 'ketat') === 'standar' ? 'selected' : '' ?>>Standar — blokir tombol screenshot & buramkan saat pindah aplikasi</option>
        </select>
        <div class="form-text">Mode <strong>Ketat</strong>: seluruh halaman diburamkan permanen dan hanya jelas di sekitar kursor (di HP: hanya saat layar disentuh), sehingga hasil screenshot berupa halaman buram. Juga memblokir PrintScreen, tombol Windows (Snipping Tool), Ctrl+P, klik kanan, seleksi teks, serta menutup layar saat berpindah aplikasi. Catatan jujur: tidak ada teknologi web yang bisa memblokir 100% (mis. memotret layar dengan kamera lain) — mode Ketat adalah proteksi maksimal yang dimungkinkan browser.</div>
        <hr>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="screenshot_wa_notify" id="sswa" <?= setting('screenshot_wa_notify', '1') === '1' ? 'checked' : '' ?> data-testid="setting-ss-wa-notify">
          <label class="form-check-label" for="sswa">Kirim notifikasi WhatsApp ke admin saat ada upaya screenshot</label>
        </div>
        <label class="form-label small">Kirim WA setelah upaya ke- (per IP, per jam)</label>
        <input type="number" name="screenshot_wa_threshold" min="1" max="50" value="<?= (int) setting('screenshot_wa_threshold', '1') ?>" class="form-control form-control-sm" data-testid="setting-ss-wa-threshold" style="max-width:120px">
        <div class="form-text small">Contoh: nilai 1 = kirim WA saat upaya pertama dari IP tsb. Nilai 3 = kirim baru saat sudah 3 kali upaya. Nomor WA & token Fonnte diatur di tab <a href="?tab=fonnte">Fonnte / WhatsApp</a>.</div>
      </div>
    </div>
  </div>
</div>

<?php elseif ($tab === 'notifweb'): ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-bell-fill me-2"></i>Notifikasi di Website (Lonceng & Popup)</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_enabled" id="nwe" <?= setting('webnotif_enabled', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-enabled">
          <label class="form-check-label" for="nwe"><strong>Aktifkan notifikasi web untuk pengunjung</strong></label>
        </div>
        <div class="form-text mb-3">Menampilkan ikon lonceng di menu atas website dengan jumlah info baru, popup kecil yang muncul otomatis untuk konten terbaru, serta popup tengah layar untuk pengumuman penting.</div>
        <label class="form-label">Jumlah maksimal item di panel lonceng</label>
        <input class="form-control" type="number" min="3" max="15" name="webnotif_limit" value="<?= e(setting('webnotif_limit', '6')) ?>" data-testid="setting-webnotif-limit">
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-window-stack me-2"></i>Popup Otomatis</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_popup" id="nwp" <?= setting('webnotif_popup', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-popup">
          <label class="form-check-label" for="nwp">Tampilkan <strong>popup kecil</strong> (pojok bawah) otomatis saat ada info baru yang belum dibaca pengunjung</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_popup_important" id="nwpi" <?= setting('webnotif_popup_important', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-popup-important">
          <label class="form-check-label" for="nwpi">Tampilkan <strong>popup tengah layar</strong> untuk pengumuman yang ditandai <em>Penting</em></label>
        </div>
        <div class="form-text">Setiap popup hanya muncul sekali per pengunjung (tersimpan di perangkat pengunjung), sehingga tidak mengganggu.</div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-collection me-2"></i>Sumber Konten Notifikasi</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_berita" id="nwb" <?= setting('webnotif_berita', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-berita">
          <label class="form-check-label" for="nwb"><i class="bi bi-newspaper me-1"></i>Berita terbaru (terbit)</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_pengumuman" id="nwpg" <?= setting('webnotif_pengumuman', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-pengumuman">
          <label class="form-check-label" for="nwpg"><i class="bi bi-megaphone me-1"></i>Pengumuman aktif (yang ditandai Penting tampil paling atas)</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="webnotif_agenda" id="nwa" <?= setting('webnotif_agenda', '1') === '1' ? 'checked' : '' ?> data-testid="setting-webnotif-agenda">
          <label class="form-check-label" for="nwa"><i class="bi bi-calendar-event me-1"></i>Agenda kegiatan mendatang</label>
        </div>
        <div class="alert alert-light border small mt-3 mb-0"><i class="bi bi-lightbulb me-1"></i>Cukup terbitkan berita, pengumuman, atau agenda seperti biasa — notifikasi muncul otomatis di website tanpa langkah tambahan. Untuk notifikasi yang sampai ke HP warga meski sedang tidak membuka website, gunakan menu <a href="<?= url('admin/push') ?>">Notifikasi Push</a>.</div>
      </div>
    </div>
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Live Chat</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="livechat_enabled" id="lce" <?= setting('livechat_enabled', '1') === '1' ? 'checked' : '' ?> data-testid="setting-livechat-enabled">
          <label class="form-check-label" for="lce"><strong>Aktifkan Live Chat</strong> di tombol mengambang halaman publik</label>
        </div>
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="livechat_wa_notify" id="lcwa" <?= setting('livechat_wa_notify', '1') === '1' ? 'checked' : '' ?> data-testid="setting-livechat-wa">
          <label class="form-check-label" for="lcwa">Kirim notifikasi WhatsApp ke admin saat ada chat baru masuk (butuh Fonnte aktif)</label>
        </div>
        <div class="form-text">Kelola percakapan di menu <a href="<?= url('admin/chat') ?>">Live Chat</a>. Notifikasi suara berbunyi di halaman tersebut saat ada pesan baru.</div>
      </div>
    </div>
  </div>
</div>

<?php else: /* notifikasi */ ?>
<div class="row g-3">
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-whatsapp me-2"></i>Koneksi WhatsApp (Fonnte)</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="fonnte_enabled" id="fe" <?= setting('fonnte_enabled') === '1' ? 'checked' : '' ?> data-testid="setting-fonnte-enabled">
          <label class="form-check-label" for="fe"><strong>Aktifkan notifikasi WhatsApp</strong></label>
        </div>
        <div class="mb-3">
          <label class="form-label">Token Device Fonnte *</label>
          <input class="form-control" type="password" name="fonnte_token" value="<?= e(setting('fonnte_token')) ?>" autocomplete="off" placeholder="Tempel token device dari dashboard Fonnte" data-testid="setting-fonnte-token" onfocus="this.type='text'" onblur="this.type='password'">
          <div class="form-text">Dapatkan di <a href="https://md.fonnte.com" target="_blank" rel="noopener">md.fonnte.com</a> → menu Device → salin <strong>Token</strong> device (bukan token akun). Pastikan device berstatus <em>Connected</em> (scan QR WhatsApp).</div>
        </div>
        <div class="mb-2">
          <label class="form-label">Nomor WA Admin Penerima Notifikasi *</label>
          <input class="form-control" name="wa_admin_number" value="<?= e(setting('wa_admin_number')) ?>" placeholder="08xxxxxxxxxx atau 628xxxxxxxxxx" data-testid="setting-wa-admin-number">
          <div class="form-text">Nomor default bila jenis notifikasi di bawah belum diatur. Format 08... otomatis diubah ke 62...</div>
        </div>
        <div class="mt-3 p-3 rounded-3" style="background:#F4FBF9" data-testid="wa-routing-settings">
          <div class="fw-semibold mb-1"><i class="bi bi-signpost-split me-1"></i>Routing Nomor per Jenis Notifikasi</div>
          <div class="small text-muted mb-2">Isi nomor admin tujuan untuk tiap jenis notifikasi. Boleh lebih dari satu nomor, pisahkan dengan koma. Kosongkan untuk memakai nomor default.</div>
          <?php foreach (Fonnte::ROUTES as $rk => $rl): ?>
          <div class="mb-2">
            <label class="form-label small mb-1"><?= e($rl) ?></label>
            <input class="form-control form-control-sm" name="wa_route_<?= e($rk) ?>" value="<?= e(setting('wa_route_' . $rk)) ?>" placeholder="mis. 0812xxxx, 0857xxxx" maxlength="200" data-testid="setting-wa-route-<?= e($rk) ?>">
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-bell me-2"></i>Jenis Notifikasi</h6></div>
      <div class="acard-body">
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="wa_notify_applicant" id="wna" <?= setting('wa_notify_applicant', '1') === '1' ? 'checked' : '' ?> data-testid="setting-wa-notify-applicant">
          <label class="form-check-label" for="wna">Kirim WA ke <strong>pemohon</strong> saat permohonan surat diterima & saat status berubah</label>
        </div>
        <div class="form-check form-switch mb-2">
          <input class="form-check-input" type="checkbox" name="wa_notify_admin" id="wnadm" <?= setting('wa_notify_admin', '1') === '1' ? 'checked' : '' ?> data-testid="setting-wa-notify-admin">
          <label class="form-check-label" for="wnadm">Kirim WA ke <strong>nomor admin</strong> setiap ada permohonan surat baru</label>
        </div>
        <div class="alert alert-light border small mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Notifikasi hanya terkirim jika pemohon mengisi nomor HP pada formulir layanan. Pengiriman berjalan di latar belakang dan tidak menghambat proses permohonan warga.</div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="mt-2">
  <button class="btn btn-primary px-4" data-testid="settings-save-btn"><i class="bi bi-check-lg me-1"></i>Simpan Pengaturan</button>
</div>
</form>

<?php if ($tab === 'tampilan'): ?>
<style>
.setting-verify-preview{display:inline-flex;align-items:center;gap:7px;font-size:.78rem;font-weight:750;padding:7px 11px;border-radius:999px;background:#1f2937;color:#fff}
.setting-verify-preview .verify-mark{font-size:1rem;color:var(--c-verify,#34d399)}
.setting-verify-preview .verify-mark-image{width:1rem;height:1rem}
</style>
<script>
(function(){
  // Semua input warna dengan atribut data-var akan menerapkan CSS variable secara realtime
  var colorInputs = document.querySelectorAll('.setting-btn-color[data-var]');
  var verifyColor=document.getElementById('settingVerifyColor');
  var verifyIcon=document.getElementById('settingVerifyIcon');
  var verifyLogo=document.getElementById('settingVerifyLogo');
  var removeVerifyLogo=document.getElementById('removeVerifyLogo');
  var verifyPreviewWrap=document.getElementById('settingVerifyPreviewWrap');
  var objectUrl='';
  function useIconPreview(){
    if(!verifyPreviewWrap||!verifyIcon) return;
    verifyPreviewWrap.innerHTML='';
    var icon=document.createElement('i');
    icon.className='bi '+verifyIcon.value+' verify-mark setting-verify-mark';
    icon.setAttribute('aria-hidden','true');
    verifyPreviewWrap.appendChild(icon);
  }
  function useLogoPreview(file){
    if(!verifyPreviewWrap||!file) return;
    if(objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl=URL.createObjectURL(file);
    document.documentElement.style.setProperty('--verify-logo-image','url("'+objectUrl.replace(/"/g,'%22')+'")');
    verifyPreviewWrap.innerHTML='<span class="verify-mark setting-verify-mark verify-mark-image" aria-hidden="true"></span>';
  }
  function updatePreview(){
    colorInputs.forEach(function(inp){
      if(inp && inp.dataset.var){
        document.documentElement.style.setProperty(inp.dataset.var, inp.value);
      }
    });
    if(verifyColor) document.documentElement.style.setProperty('--c-verify',verifyColor.value);
    if(removeVerifyLogo&&removeVerifyLogo.checked) useIconPreview();
    else if(verifyLogo&&verifyLogo.files&&verifyLogo.files[0]) useLogoPreview(verifyLogo.files[0]);
    else if(verifyPreviewWrap&&!verifyPreviewWrap.querySelector('.verify-mark-image')) useIconPreview();
  }
  colorInputs.forEach(function(el){
    el.addEventListener('input',updatePreview);
    el.addEventListener('change',updatePreview);
  });
  [verifyColor,verifyIcon,removeVerifyLogo].forEach(function(el){
    if(!el) return;
    el.addEventListener('input',updatePreview);
    el.addEventListener('change',updatePreview);
  });
  if(verifyLogo) verifyLogo.addEventListener('change',updatePreview);
  window.addEventListener('pagehide',function(){if(objectUrl) URL.revokeObjectURL(objectUrl);});
  updatePreview();
})();
</script>
<?php endif; ?>

<?php if ($tab === 'sosial'): ?>
<script>
(function(){
  var toggle=document.getElementById('homeSocialEnabled');
  if(!toggle) return;
  ['youtube','instagram','tiktok','home_social_items'].forEach(function(name){
    var field=document.querySelector('[name="'+name+'"]');
    if(!field) return;
    field.addEventListener('input',function(){
      if(String(field.value||'').trim()!=='') toggle.checked=true;
    });
  });
})();
</script>
<?php endif; ?>

<?php if ($tab === 'fitur'): ?>
<form method="post" action="<?= url('admin/pengaturan/resetcache') ?>" class="mt-3" data-testid="resetcache-form" onsubmit="return confirm('Naikkan versi kode website? Semua pengunjung akan otomatis memuat ulang file terbaru & cache lama mereka dibersihkan.');">
  <?= csrf_field() ?>
  <div class="acard" style="max-width:640px">
    <div class="acard-head"><h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Cache & Pembaruan Kode Pengunjung</h6></div>
    <div class="acard-body">
      <p class="small mb-2">Versi kode saat ini: <span class="badge text-bg-secondary" data-testid="asset-version-badge"><?= e(setting('asset_version', '1')) ?></span></p>
      <p class="form-text mb-3">Gunakan tombol ini <b>setelah website diperbarui</b> (upload file baru) atau jika ada pengunjung yang tampilannya kacau/error karena masih memakai file lama. Sekali klik: browser semua pengunjung otomatis mengunduh CSS/JS versi terbaru, cache aplikasi (PWA/service worker) dibersihkan, dan penyimpanan lokal lama di perangkat pengunjung di-reset — mencegah kode lama dan baru bertabrakan.</p>
      <button class="btn btn-warning" data-testid="resetcache-btn"><i class="bi bi-arrow-repeat me-1"></i>Reset Cache Pengunjung Sekarang</button>
    </div>
  </div>
</form>
<?php endif; ?>

<?php if ($tab === 'notifikasi'): ?>
<form method="post" action="<?= url('admin/pengaturan/testwa') ?>" class="mt-3" data-testid="testwa-form">
  <?= csrf_field() ?>
  <div class="acard" style="max-width:640px">
    <div class="acard-head"><h6 class="mb-0"><i class="bi bi-send-check me-2"></i>Tes Koneksi WhatsApp</h6></div>
    <div class="acard-body">
      <label class="form-label">Kirim pesan uji ke nomor (kosongkan = pakai Nomor WA Admin)</label>
      <div class="d-flex gap-2 flex-wrap">
        <input class="form-control" style="max-width:280px" name="test_number" placeholder="08xxxxxxxxxx" data-testid="testwa-number">
        <button class="btn btn-outline-primary" data-testid="testwa-btn"><i class="bi bi-whatsapp me-1"></i>Kirim Pesan Uji</button>
      </div>
      <div class="form-text">Simpan pengaturan (token & nomor) terlebih dahulu sebelum melakukan tes.</div>
    </div>
  </div>
</form>
<?php endif; ?>
