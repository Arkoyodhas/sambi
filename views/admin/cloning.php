<?php /* Form Cloning Website — khusus Super Admin */ ?>
<div class="alert alert-info small d-flex gap-2" data-testid="cloning-info">
  <i class="bi bi-copy fs-5"></i>
  <div>
    <strong>Cloning Website</strong> menghasilkan paket ZIP siap instal tanpa data operasional website sumber.
    Fitur cloning tidak ikut ke paket hasil. Modul yang tidak diperlukan dapat dinonaktifkan melalui pilihan di bawah;
    file dan tabel tetap dipertahankan agar modul dapat diaktifkan kembali dengan aman.
  </div>
</div>

<form method="post" action="<?= url('admin/cloning/generate') ?>" data-testid="cloning-form" id="cloningForm">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-buildings me-2"></i>Identitas Desa Baru</h6></div>
        <div class="acard-body">
          <div class="mb-2"><label class="form-label">Nama Desa *</label><input class="form-control" name="village_name" required maxlength="150" placeholder="Contoh: Desa Mekar Jaya"></div>
          <div class="mb-2"><label class="form-label">Tagline</label><input class="form-control" name="village_tagline"></div>
          <div class="mb-2"><label class="form-label">Deskripsi Singkat</label><textarea class="form-control" name="village_description" rows="2"></textarea></div>
          <div class="row g-2">
            <div class="col-6 mb-2"><label class="form-label">Kecamatan</label><input class="form-control" name="kecamatan"></div>
            <div class="col-6 mb-2"><label class="form-label">Kabupaten</label><input class="form-control" name="kabupaten"></div>
            <div class="col-6 mb-2"><label class="form-label">Provinsi</label><input class="form-control" name="provinsi"></div>
            <div class="col-6 mb-2"><label class="form-label">Kode Pos</label><input class="form-control" name="kode_pos"></div>
          </div>
          <div class="mb-2"><label class="form-label">Alamat Kantor Desa</label><input class="form-control" name="address"></div>
          <div class="row g-2">
            <div class="col-6 mb-2"><label class="form-label">Telepon</label><input class="form-control" name="phone"></div>
            <div class="col-6 mb-2"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp"></div>
          </div>
          <div class="mb-2"><label class="form-label">Email Desa</label><input class="form-control" type="email" name="email"></div>
          <div class="mb-2"><label class="form-label">Nama Kepala Desa</label><input class="form-control" name="kepala_desa_nama"></div>
        </div>
      </div>

      <div class="acard mb-3">
        <div class="acard-head d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-toggles me-2"></i>Pilih Fitur yang Tidak Dimuat</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary" id="clearDisabledFeatures">Aktifkan Semua</button>
        </div>
        <div class="acard-body">
          <p class="small text-muted">Centang modul yang tidak diperlukan pada website hasil cloning.</p>
          <div class="row g-2">
            <?php foreach ($featureOptions as $key => [$label, $description]): ?>
            <div class="col-md-6">
              <label class="border rounded-3 p-2 d-flex gap-2 h-100 w-100">
                <input class="form-check-input mt-1 clone-feature-check" type="checkbox" name="disabled_features[]" value="<?= e($key) ?>">
                <span><strong class="d-block small"><?= e($label) ?></strong><small class="text-muted"><?= e($description) ?></small></span>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="alert alert-warning py-2 small mt-3 mb-0">
            Modul yang dicentang disembunyikan dari menu, dashboard, beranda, widget, dan rute terkait. Data inti serta struktur database tidak dihapus.
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-palette me-2"></i>Warna Tema</h6></div>
        <div class="acard-body">
          <div class="row g-2">
            <div class="col-4"><label class="form-label">Utama</label><input class="form-control form-control-color w-100" type="color" name="primary_color" value="#0f6b4e"></div>
            <div class="col-4"><label class="form-label">Sekunder</label><input class="form-control form-control-color w-100" type="color" name="secondary_color" value="#0b3d2e"></div>
            <div class="col-4"><label class="form-label">Aksen</label><input class="form-control form-control-color w-100" type="color" name="accent_color" value="#e8b44c"></div>
          </div>
        </div>
      </div>

      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Koordinat Peta</h6></div>
        <div class="acard-body">
          <div class="row g-2">
            <div class="col-6"><label class="form-label">Latitude</label><input class="form-control" name="map_lat" placeholder="-6.914744"></div>
            <div class="col-6"><label class="form-label">Longitude</label><input class="form-control" name="map_lng" placeholder="107.60981"></div>
          </div>
        </div>
      </div>

      <div class="acard mb-3">
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-person-lock me-2"></i>Akun Super Admin Baru</h6></div>
        <div class="acard-body">
          <div class="mb-2"><label class="form-label">Username Admin *</label><input class="form-control" name="admin_username" required value="admin" pattern="[a-zA-Z0-9_.]{3,30}"></div>
          <div class="mb-2"><label class="form-label">Password Admin *</label><input class="form-control" type="password" name="admin_password" required minlength="8" autocomplete="new-password"></div>
          <div class="mb-2"><label class="form-label">Email Admin *</label><input class="form-control" type="email" name="admin_email" value="admin@desa.id" required></div>
          <div class="form-text">Paket tidak menyertakan akun admin, password hash, token, log, maupun data warga dari website sumber.</div>
        </div>
      </div>

      <button class="btn btn-primary btn-lg w-100" data-testid="clone-generate-btn" id="cloneGenerateButton">
        <i class="bi bi-box-seam me-2"></i>Buat & Unduh Paket Cloning
      </button>
    </div>
  </div>
</form>

<script>
(function(){
  var form = document.getElementById('cloningForm');
  var button = document.getElementById('cloneGenerateButton');
  var clear = document.getElementById('clearDisabledFeatures');

  if (clear) clear.addEventListener('click', function(){
    document.querySelectorAll('.clone-feature-check').forEach(function(input){ input.checked = false; });
  });

  if (form && button) form.addEventListener('submit', function(){
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Membuat paket...';
  });
})();
</script>
