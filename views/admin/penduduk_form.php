<?php
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$val = static fn(string $name, $default = ''): string => e($old[$name] ?? $r[$name] ?? $default);
$selected = static fn(string $name, $value): string =>
    ((string) ($old[$name] ?? $r[$name] ?? '') === (string) $value) ? 'selected' : '';
$isNew = $r === null;
$hasOldInput = !empty($old);
$familyIdValue = (int) ($old['family_id'] ?? $selectedFamily['id'] ?? 0);
$inheritFamily = $hasOldInput ? !empty($old['inherit_family']) : true;
$familyLabel = $selectedFamily
    ? (($selectedFamily['kepala_keluarga'] ?: 'Tanpa nama kepala') . ' — ' . $selectedFamily['no_kk'])
    : '';
?>
<div class="acard population-form-card" style="max-width:1040px">
  <div class="acard-head">
    <div>
      <h6 class="mb-1"><?= e($pageTitle) ?></h6>
      <small class="text-muted">Penduduk, keluarga, dan anggota KK akan disinkronkan otomatis saat disimpan.</small>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/penduduk') ?>">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>

  <div class="acard-body">
    <form method="post" action="<?= url('admin/penduduk/' . ($isNew ? 'store' : 'update/' . $r['id'])) ?>" id="residentForm">
      <?= csrf_field() ?>

      <div class="alert alert-info border-0 population-helper">
        <div class="d-flex gap-2">
          <i class="bi bi-stars"></i>
          <div>
            <strong>Integrasi otomatis aktif.</strong>
            <div class="small">Pilih keluarga yang sudah ada atau isi Nomor KK baru. Jika KK belum tersedia, sistem akan membuat data keluarga dan hubungan anggotanya secara otomatis.</div>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-person-vcard"></i></span>
          <div><strong>Identitas Penduduk</strong><small>Kolom bertanda * wajib diisi</small></div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">NIK (16 digit) *</label>
            <input class="form-control digit-16" name="nik" value="<?= $val('nik') ?>" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" data-testid="p-nik">
            <div class="form-text">Spasi atau tanda baca akan dihapus otomatis.</div>
          </div>
          <div class="col-md-8">
            <label class="form-label">Nama Lengkap *</label>
            <input class="form-control" name="nama" value="<?= $val('nama') ?>" required maxlength="150" autocomplete="name" data-testid="p-nama">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tempat Lahir</label>
            <input class="form-control" name="tempat_lahir" value="<?= $val('tempat_lahir') ?>" maxlength="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tanggal Lahir</label>
            <input class="form-control" type="date" name="tanggal_lahir" value="<?= $val('tanggal_lahir') ?>" max="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Jenis Kelamin</label>
            <select class="form-select" name="jenis_kelamin">
              <option value="L" <?= $selected('jenis_kelamin', 'L') ?>>Laki-laki</option>
              <option value="P" <?= $selected('jenis_kelamin', 'P') ?>>Perempuan</option>
            </select>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-people"></i></span>
          <div><strong>Keluarga dan Nomor KK</strong><small>Pilih keluarga agar alamat dan wilayah dapat diisi otomatis</small></div>
        </div>
        <div class="row g-3">
          <div class="col-lg-7">
            <label class="form-label">Cari Keluarga Terdaftar</label>
            <div class="population-search-wrap">
              <input class="form-control" id="familySearch" placeholder="Ketik nama kepala keluarga, Nomor KK, atau alamat..." autocomplete="off">
              <div class="population-search-results shadow-sm" id="familyResults" hidden></div>
            </div>
            <input type="hidden" name="family_id" id="familyId" value="<?= $familyIdValue ?>">
            <div
              class="selected-resident mt-2"
              id="selectedFamily"
              <?= $selectedFamily ? '' : 'hidden' ?>
              data-no-kk="<?= e($selectedFamily['no_kk'] ?? '') ?>"
              data-address="<?= e($selectedFamily['alamat'] ?? '') ?>"
              data-dusun="<?= (int) ($selectedFamily['dusun_id'] ?? 0) ?>"
              data-rw="<?= (int) ($selectedFamily['rw_id'] ?? 0) ?>"
              data-rt="<?= (int) ($selectedFamily['rt_id'] ?? 0) ?>"
            >
              <span><i class="bi bi-house-check-fill me-1"></i><strong id="selectedFamilyLabel"><?= e($familyLabel) ?></strong></span>
              <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none" id="clearFamily">Ganti</button>
            </div>
            <div class="form-text">Pencarian cepat tidak memuat ribuan data KK sekaligus.</div>
          </div>
          <div class="col-lg-5">
            <label class="form-label">Nomor KK</label>
            <input class="form-control digit-16" id="noKkInput" name="no_kk" value="<?= $val('no_kk') ?>" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" data-testid="p-nokk" <?= $selectedFamily ? 'readonly' : '' ?>>
            <div class="form-text">Boleh dikosongkan bila belum terhubung ke keluarga.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Hubungan dalam Keluarga</label>
            <select class="form-select" name="hubungan_keluarga" id="relationshipSelect">
              <option value="">— Tentukan otomatis —</option>
              <?php foreach (PendudukController::HUBUNGAN as $relationship): ?>
                <option value="<?= e($relationship) ?>" <?= $selected('hubungan_keluarga', $relationship) ?>><?= e($relationship) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Jika kosong, anggota pertama pada KK baru akan dijadikan Kepala Keluarga.</div>
          </div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" name="inherit_family" value="1" id="inheritFamily" <?= $inheritFamily ? 'checked' : '' ?>>
              <label class="form-check-label" for="inheritFamily">Gunakan alamat dan wilayah keluarga bila kolom masih kosong</label>
            </div>
          </div>
          <div class="col-12">
            <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/keluarga/create') ?>" target="_blank">
              <i class="bi bi-house-add me-1"></i>Buat keluarga terlebih dahulu
            </a>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-journal-text"></i></span>
          <div><strong>Data Sosial</strong><small>Lengkapi sesuai dokumen administrasi</small></div>
        </div>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Agama</label>
            <select class="form-select" name="agama">
              <option value="">—</option>
              <?php foreach (PendudukController::AGAMA as $religion): ?>
                <option value="<?= e($religion) ?>" <?= $selected('agama', $religion) ?>><?= e($religion) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Pendidikan</label>
            <select class="form-select" name="pendidikan">
              <option value="">—</option>
              <?php foreach (PendudukController::PENDIDIKAN as $education): ?>
                <option value="<?= e($education) ?>" <?= $selected('pendidikan', $education) ?>><?= e($education) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Pekerjaan</label>
            <input class="form-control" name="pekerjaan" value="<?= $val('pekerjaan') ?>" maxlength="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Status Perkawinan</label>
            <select class="form-select" name="status_perkawinan">
              <option value="">—</option>
              <?php foreach (PendudukController::KAWIN as $marital): ?>
                <option value="<?= e($marital) ?>" <?= $selected('status_perkawinan', $marital) ?>><?= e($marital) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Status Penduduk</label>
            <select class="form-select" name="status_penduduk">
              <?php foreach (['hidup', 'meninggal', 'pindah', 'tidak_aktif'] as $status): ?>
                <option value="<?= e($status) ?>" <?= $selected('status_penduduk', $status) ?>><?= e(ucwords(str_replace('_', ' ', $status))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-geo-alt"></i></span>
          <div><strong>Alamat dan Wilayah</strong><small>Dapat mengikuti keluarga yang dipilih</small></div>
        </div>
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Alamat</label>
            <input class="form-control" id="addressInput" name="alamat" value="<?= $val('alamat') ?>" maxlength="255">
          </div>
          <div class="col-md-4">
            <label class="form-label">Dusun</label>
            <select class="form-select" name="dusun_id" id="dusunSelect">
              <option value="">—</option>
              <?php foreach ($dusunList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('dusun_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">RW</label>
            <select class="form-select" name="rw_id" id="rwSelect">
              <option value="">—</option>
              <?php foreach ($rwList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('rw_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">RT</label>
            <select class="form-select" name="rt_id" id="rtSelect">
              <option value="">—</option>
              <?php foreach ($rtList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('rt_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-telephone"></i></span>
          <div><strong>Kontak dan Keterangan</strong><small>Opsional</small></div>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input class="form-control" name="no_hp" value="<?= $val('no_hp') ?>" inputmode="tel" maxlength="20" autocomplete="tel">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= $val('email') ?>" maxlength="150" autocomplete="email">
          </div>
          <div class="col-12">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan" rows="3" maxlength="3000"><?= $val('keterangan') ?></textarea>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap sticky-form-actions">
        <button class="btn btn-primary px-4" data-testid="p-save">
          <i class="bi bi-check-lg me-1"></i>Simpan dan Sinkronkan
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/penduduk') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php
$familySearchUrl = url('admin/penduduk/family-search');
$inlineScript = '<script>window.POPULATION_FAMILY_SEARCH_URL = ' . json_encode($familySearchUrl) . ';</script>' . <<<'HTML'
<script>
(function () {
  'use strict';

  const search = document.getElementById('familySearch');
  const results = document.getElementById('familyResults');
  const familyId = document.getElementById('familyId');
  const selectedBox = document.getElementById('selectedFamily');
  const selectedLabel = document.getElementById('selectedFamilyLabel');
  const clearButton = document.getElementById('clearFamily');
  const noKk = document.getElementById('noKkInput');
  const inherit = document.getElementById('inheritFamily');
  const address = document.getElementById('addressInput');
  const dusun = document.getElementById('dusunSelect');
  const rw = document.getElementById('rwSelect');
  const rt = document.getElementById('rtSelect');
  let timer = 0;
  let requestController = null;

  document.querySelectorAll('.digit-16').forEach(function (input) {
    input.addEventListener('input', function () {
      input.value = input.value.replace(/\D/g, '').slice(0, 16);
    });
  });

  function escapeHtml(value) {
    const span = document.createElement('span');
    span.textContent = value == null ? '' : String(value);
    return span.innerHTML;
  }

  function hideResults() {
    if (!results) return;
    results.hidden = true;
    results.innerHTML = '';
  }

  function setFamilyData(row, force) {
    if (!row) return;
    noKk.value = row.no_kk || '';
    noKk.readOnly = true;

    selectedBox.dataset.noKk = row.no_kk || '';
    selectedBox.dataset.address = row.alamat || '';
    selectedBox.dataset.dusun = row.dusun_id || '';
    selectedBox.dataset.rw = row.rw_id || '';
    selectedBox.dataset.rt = row.rt_id || '';

    if (!inherit.checked) return;
    if (force || !address.value.trim()) address.value = row.alamat || '';
    if (force || !dusun.value) dusun.value = row.dusun_id || '';
    if (force || !rw.value) rw.value = row.rw_id || '';
    if (force || !rt.value) rt.value = row.rt_id || '';
  }

  function chooseFamily(row) {
    familyId.value = row.id;
    selectedLabel.textContent = (row.kepala_keluarga || 'Tanpa nama kepala') + ' — ' + row.no_kk;
    selectedBox.hidden = false;
    search.value = '';
    hideResults();
    setFamilyData(row, true);
  }

  function currentFamilyData() {
    if (!familyId.value || selectedBox.hidden) return null;
    return {
      id: Number(familyId.value),
      no_kk: selectedBox.dataset.noKk || '',
      alamat: selectedBox.dataset.address || '',
      dusun_id: selectedBox.dataset.dusun || '',
      rw_id: selectedBox.dataset.rw || '',
      rt_id: selectedBox.dataset.rt || ''
    };
  }

  search?.addEventListener('input', function () {
    clearTimeout(timer);
    const query = search.value.trim();
    if (query.length < 2) {
      hideResults();
      return;
    }

    timer = window.setTimeout(async function () {
      requestController?.abort();
      requestController = new AbortController();
      results.hidden = false;
      results.innerHTML = '<div class="population-search-state"><span class="spinner-border spinner-border-sm me-2"></span>Mencari keluarga...</div>';

      try {
        const response = await fetch(window.POPULATION_FAMILY_SEARCH_URL + '?q=' + encodeURIComponent(query), {
          headers: { 'Accept': 'application/json' },
          signal: requestController.signal
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const payload = await response.json();
        const rows = Array.isArray(payload.data) ? payload.data : [];

        if (!rows.length) {
          results.innerHTML = '<div class="population-search-state text-muted">Keluarga tidak ditemukan. Anda dapat mengisi Nomor KK baru secara manual.</div>';
          return;
        }

        results.innerHTML = rows.map(function (row) {
          const head = row.kepala_keluarga || 'Tanpa nama kepala';
          const members = Number(row.jumlah_anggota || 0) + ' anggota';
          return '<button type="button" class="population-search-item" data-id="' + Number(row.id) + '">' +
            '<strong>' + escapeHtml(head) + '</strong>' +
            '<small>KK ' + escapeHtml(row.no_kk) + ' · ' + escapeHtml(members) + '</small>' +
          '</button>';
        }).join('');

        results.querySelectorAll('.population-search-item').forEach(function (button) {
          button.addEventListener('click', function () {
            const row = rows.find(function (item) { return Number(item.id) === Number(button.dataset.id); });
            if (row) chooseFamily(row);
          });
        });
      } catch (error) {
        if (error.name !== 'AbortError') {
          results.innerHTML = '<div class="population-search-state text-danger">Pencarian gagal. Isi Nomor KK secara manual atau coba kembali.</div>';
        }
      }
    }, 280);
  });

  clearButton?.addEventListener('click', function () {
    familyId.value = '';
    selectedLabel.textContent = '';
    selectedBox.hidden = true;
    noKk.readOnly = false;
    noKk.value = '';
    search?.focus();
  });

  inherit?.addEventListener('change', function () {
    if (inherit.checked) setFamilyData(currentFamilyData(), false);
  });

  document.addEventListener('click', function (event) {
    if (results && !results.contains(event.target) && event.target !== search) hideResults();
  });

  const initialFamily = currentFamilyData();
  if (initialFamily) setFamilyData(initialFamily, false);

  document.getElementById('residentForm')?.addEventListener('submit', function (event) {
    const current = currentFamilyData();
    if (current) setFamilyData(current, false);
    const button = event.submitter;
    if (button) {
      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Menyimpan...';
    }
  });
})();
</script>
HTML;
?>
