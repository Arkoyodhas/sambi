<?php /* Form Cloning Website — khusus Super Admin */ ?>
<div class="alert alert-info small d-flex gap-2" data-testid="cloning-info">
  <i class="bi bi-copy fs-5"></i>
  <div>
    <strong>Cloning Website</strong> menghasilkan paket ZIP siap instal untuk desa lain. Proses berjalan bertahap di latar belakang
    dengan indikator progres sehingga tidak terkena batas waktu server.
    <div class="mt-1"><strong>Ikut tersalin:</strong> seluruh kode program, struktur database terbaru, tema/warna/font/tata letak,
    background & video hero, menu navigasi, efek event, musik latar, jenis layanan surat, serta peran & hak akses.</div>
    <div><strong>Tidak tersalin:</strong> berita, pengumuman, agenda, dokumen, galeri, foto & data perangkat desa, data penduduk,
    permohonan, pengaduan, log, akun admin, dan token/kunci rahasia desa sumber.</div>
  </div>
</div>

<form method="post" action="<?= url('admin/cloning/start') ?>" data-testid="cloning-form" id="cloningForm">
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
          <label class="form-check mb-2" data-testid="clone-keep-colors-label">
            <input class="form-check-input" type="checkbox" name="keep_colors" value="1" id="keepColors" checked data-testid="clone-keep-colors">
            <span class="form-check-label small">Gunakan warna tema website ini (sama persis dengan tampilan sekarang)</span>
          </label>
          <div class="row g-2" id="customColors">
            <div class="col-4"><label class="form-label">Utama</label><input class="form-control form-control-color w-100" type="color" name="primary_color" value="<?= e(setting('primary_color', '#0f6b4e')) ?>"></div>
            <div class="col-4"><label class="form-label">Sekunder</label><input class="form-control form-control-color w-100" type="color" name="secondary_color" value="<?= e(setting('secondary_color', '#0b3d2e')) ?>"></div>
            <div class="col-4"><label class="form-label">Aksen</label><input class="form-control form-control-color w-100" type="color" name="accent_color" value="<?= e(setting('accent_color', '#e8b44c')) ?>"></div>
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
        <div class="acard-head"><h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Isi Awal Website Baru</h6></div>
        <div class="acard-body">
          <label class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="include_sample" value="1" checked data-testid="clone-include-sample">
            <span class="form-check-label small"><strong>Isi contoh teks yang bisa diedit</strong> — 1 berita sambutan, 1 pengumuman, sambutan kepala desa, teks profil desa, FAQ, dan nama perangkat desa (tanpa foto).</span>
          </label>
          <div class="form-text">Jika tidak dicentang, website baru benar-benar kosong dan siap diisi admin desa baru. Tampilan & desain tetap tersalin.</div>
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
        <i class="bi bi-box-seam me-2"></i>Buat Paket Cloning
      </button>

      <div class="acard mt-3 d-none" id="cloneProgressCard" data-testid="clone-progress-card">
        <div class="acard-body">
          <div class="d-flex justify-content-between small mb-1">
            <span id="cloneProgressText" data-testid="clone-progress-text">Memulai...</span>
            <strong id="cloneProgressPct" data-testid="clone-progress-pct">0%</strong>
          </div>
          <div class="progress" style="height:12px"><div class="progress-bar progress-bar-striped progress-bar-animated" id="cloneProgressBar" style="width:0%"></div></div>
          <div class="alert alert-danger small mt-3 mb-0 d-none" id="cloneError" data-testid="clone-error"></div>
          <div class="mt-3 d-none" id="cloneDone" data-testid="clone-done">
            <div class="alert alert-success small mb-2"><i class="bi bi-check-circle me-1"></i>Paket selesai dibuat (<span id="cloneSize"></span>). Unduhan dimulai otomatis.</div>
            <a class="btn btn-success w-100" id="cloneDownloadLink" data-testid="clone-download-link" href="#"><i class="bi bi-download me-2"></i>Unduh Paket ZIP</a>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2 d-none" id="cloneRetry" data-testid="clone-retry-btn">Coba lanjutkan lagi</button>
        </div>
      </div>
    </div>
  </div>
</form>

<script>
(function(){
  var form = document.getElementById('cloningForm');
  var button = document.getElementById('cloneGenerateButton');
  var clear = document.getElementById('clearDisabledFeatures');
  var keep = document.getElementById('keepColors');
  var custom = document.getElementById('customColors');
  var card = document.getElementById('cloneProgressCard');
  var bar = document.getElementById('cloneProgressBar');
  var pct = document.getElementById('cloneProgressPct');
  var text = document.getElementById('cloneProgressText');
  var err = document.getElementById('cloneError');
  var done = document.getElementById('cloneDone');
  var retry = document.getElementById('cloneRetry');
  var stepUrl = <?= json_encode(url('admin/cloning/step')) ?>;
  var token = form.querySelector('[name=csrf_token]').value;
  var job = null, failures = 0;

  function syncColors(){ custom.style.opacity = keep.checked ? '.45' : '1'; custom.querySelectorAll('input').forEach(function(i){ i.disabled = keep.checked; }); }
  keep.addEventListener('change', syncColors); syncColors();

  if (clear) clear.addEventListener('click', function(){
    document.querySelectorAll('.clone-feature-check').forEach(function(input){ input.checked = false; });
  });

  function setProgress(p, msg){ bar.style.width = p + '%'; pct.textContent = p + '%'; if (msg) text.textContent = msg; }
  function showError(msg){
    err.textContent = msg; err.classList.remove('d-none'); bar.classList.remove('progress-bar-animated');
    button.disabled = false; button.innerHTML = '<i class="bi bi-box-seam me-2"></i>Buat Paket Cloning';
    retry.classList.toggle('d-none', !job);
  }
  function post(url, data){
    return fetch(url, {method: 'POST', body: data, credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
      .then(function(r){ return r.text().then(function(t){ var j; try { j = JSON.parse(t); } catch(e) { throw new Error('Respon server tidak valid (HTTP ' + r.status + '). ' + t.replace(/<[^>]+>/g, ' ').trim().slice(0, 200)); } if (!j.ok) { var e2 = new Error(j.message || 'Gagal'); e2.fatal = true; throw e2; } return j; }); });
  }
  function fmt(b){ return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.round(b / 1024) + ' KB'; }
  function next(){
    var fd = new FormData(); fd.append('csrf_token', token); fd.append('job', job);
    post(stepUrl, fd).then(function(j){
      failures = 0; setProgress(j.progress, j.message);
      if (j.done) {
        bar.classList.remove('progress-bar-animated'); bar.classList.add('bg-success');
        document.getElementById('cloneSize').textContent = fmt(j.size || 0);
        var link = document.getElementById('cloneDownloadLink'); link.href = j.download;
        done.classList.remove('d-none');
        button.disabled = false; button.innerHTML = '<i class="bi bi-box-seam me-2"></i>Buat Paket Cloning Lagi';
        window.location.href = j.download;
        return;
      }
      next();
    }).catch(function(e){
      if (!e.fatal && failures < 5) { failures++; text.textContent = 'Koneksi terputus, mencoba lagi (' + failures + '/5)...'; setTimeout(next, 2000 * failures); return; }
      showError(e.message);
    });
  }
  retry.addEventListener('click', function(){ retry.classList.add('d-none'); err.classList.add('d-none'); bar.classList.add('progress-bar-animated'); failures = 0; next(); });

  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    if (!form.reportValidity()) return;
    job = null; failures = 0;
    card.classList.remove('d-none'); err.classList.add('d-none'); done.classList.add('d-none'); retry.classList.add('d-none');
    bar.classList.remove('bg-success'); bar.classList.add('progress-bar-animated'); setProgress(0, 'Memulai...');
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Membuat paket...';
    post(form.action, new FormData(form)).then(function(j){ job = j.job; setProgress(j.progress, j.message); next(); })
      .catch(function(e){ showError(e.message); });
    card.scrollIntoView({behavior: 'smooth', block: 'center'});
  });
})();
</script>
