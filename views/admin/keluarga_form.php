<?php
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);

$value = static fn(string $name, $default = ''): string => e($old[$name] ?? $f[$name] ?? $default);
$selected = static fn(string $name, $option): string =>
    ((string) ($old[$name] ?? $f[$name] ?? '') === (string) $option) ? 'selected' : '';
$isNew = !$f;
$headId = (int) ($old['head_resident_id'] ?? $currentHead['id'] ?? 0);
$headLabel = $currentHead
    ? (($currentHead['nama'] ?? '') . ' (' . ($currentHead['nik'] ?? '') . ')')
    : '';
$createHead = !empty($old['create_head']);
?>
<div class="acard population-form-card" style="max-width:940px">
  <div class="acard-head">
    <div>
      <h6 class="mb-1"><?= e($pageTitle) ?></h6>
      <small class="text-muted">Satu formulir untuk data KK, kepala keluarga, alamat, dan relasi anggota.</small>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/keluarga') ?>">
      <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
  </div>

  <div class="acard-body">
    <form method="post" action="<?= url('admin/keluarga/' . ($f ? 'update/' . $f['id'] : 'store')) ?>" id="familyForm">
      <?= csrf_field() ?>

      <?php if ($isNew): ?>
      <ol class="fam-steps" aria-label="Alur pengaturan keluarga" data-testid="family-form-steps">
        <li class="is-current"><span class="fam-step-ic">1</span><div><strong>Data KK</strong><small>Isi nomor KK & alamat</small></div></li>
        <li><span class="fam-step-ic">2</span><div><strong>Kepala Keluarga</strong><small>Pilih / buat (boleh nanti)</small></div></li>
        <li><span class="fam-step-ic">3</span><div><strong>Anggota</strong><small>Ditambahkan setelah simpan</small></div></li>
      </ol>
      <?php endif; ?>
      <div class="alert alert-info border-0 population-helper">
        <div class="d-flex gap-2">
          <i class="bi bi-magic"></i>
          <div>
            <strong>Cukup isi Nomor KK dan alamat, lalu simpan.</strong>
            <div class="small">Setelah tersimpan Anda langsung diarahkan ke halaman keluarga untuk menambahkan anggota (istri, anak, dll.) satu per satu dengan sekali klik.</div>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-house-door"></i></span>
          <div><strong>Identitas Keluarga</strong><small>Nomor KK harus unik</small></div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nomor KK (16 digit) *</label>
            <input class="form-control digit-16" id="familyNoKk" name="no_kk" value="<?= $value('no_kk') ?>" required inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" data-testid="kk-nokk">
            <div class="form-text">Spasi atau tanda baca akan dibersihkan otomatis.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Kepala Keluarga</label>
            <input class="form-control" id="familyHeadName" name="kepala_keluarga" value="<?= $value('kepala_keluarga') ?>" maxlength="150" data-testid="kk-kepala">
            <div class="form-text">Terisi otomatis saat memilih atau membuat kepala keluarga.</div>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-person-check"></i></span>
          <div><strong>Kepala Keluarga</strong><small>Bagian ini opsional ketika hanya ingin membuat data KK</small></div>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Cari Penduduk yang Sudah Ada</label>
            <div class="population-search-wrap">
              <input class="form-control" id="residentSearch" placeholder="Ketik minimal 2 huruf nama, NIK, atau Nomor KK..." autocomplete="off">
              <div class="population-search-results shadow-sm" id="residentResults" hidden></div>
            </div>
            <input type="hidden" name="head_resident_id" id="headResidentId" value="<?= $headId ?>">
            <div class="selected-resident mt-2" id="selectedResident" <?= $headId ? '' : 'hidden' ?>>
              <span><i class="bi bi-person-check-fill me-1"></i><strong id="selectedResidentLabel"><?= e($headLabel) ?></strong></span>
              <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none" id="clearResident">Hapus pilihan</button>
            </div>
            <div class="form-text">Penduduk dari keluarga lain dapat dipindahkan ke KK ini; sistem akan memperbarui relasinya secara otomatis.</div>
          </div>

          <div class="col-12">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="create_head" value="1" id="createHeadToggle" <?= $createHead ? 'checked' : '' ?>>
              <label class="form-check-label fw-semibold" for="createHeadToggle">Buat data penduduk kepala keluarga baru sekaligus</label>
            </div>
          </div>

          <div class="col-12" id="newHeadPanel" <?= $createHead ? '' : 'hidden' ?>>
            <div class="rounded-3 border bg-light p-3">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">NIK Kepala Keluarga *</label>
                  <input class="form-control digit-16" name="head_nik" value="<?= e($old['head_nik'] ?? '') ?>" inputmode="numeric" pattern="[0-9]{16}" maxlength="16">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nama Lengkap *</label>
                  <input class="form-control" name="head_nama" id="newHeadName" value="<?= e($old['head_nama'] ?? '') ?>" maxlength="150">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Jenis Kelamin</label>
                  <select class="form-select" name="head_jenis_kelamin">
                    <option value="L" <?= (($old['head_jenis_kelamin'] ?? 'L') === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= (($old['head_jenis_kelamin'] ?? '') === 'P') ? 'selected' : '' ?>>Perempuan</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tanggal Lahir</label>
                  <input class="form-control" type="date" name="head_tanggal_lahir" value="<?= e($old['head_tanggal_lahir'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
                </div>
              </div>
              <small class="text-muted d-block mt-2">Alamat, wilayah, Nomor KK, dan hubungan “Kepala Keluarga” akan mengikuti data keluarga ini.</small>
            </div>
          </div>
        </div>
      </div>

      <div class="population-section">
        <div class="population-section-title">
          <span class="population-section-icon"><i class="bi bi-geo-alt"></i></span>
          <div><strong>Alamat dan Wilayah</strong><small>Digunakan sebagai referensi anggota keluarga</small></div>
        </div>

        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Alamat</label>
            <input class="form-control" id="familyAddress" name="alamat" value="<?= $value('alamat') ?>" maxlength="255">
          </div>
          <div class="col-md-4">
            <label class="form-label">Dusun</label>
            <select class="form-select" name="dusun_id" id="familyDusun">
              <option value="">—</option>
              <?php foreach ($dusunList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('dusun_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">RW</label>
            <select class="form-select" name="rw_id" id="familyRw">
              <option value="">—</option>
              <?php foreach ($rwList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('rw_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">RT</label>
            <select class="form-select" name="rt_id" id="familyRt">
              <option value="">—</option>
              <?php foreach ($rtList as $region): ?>
                <option value="<?= (int) $region['id'] ?>" <?= $selected('rt_id', $region['id']) ?>><?= e($region['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status Keluarga</label>
            <select class="form-select" name="status">
              <option value="aktif" <?= $selected('status', 'aktif') ?>>Aktif</option>
              <option value="pindah" <?= $selected('status', 'pindah') ?>>Pindah</option>
              <option value="tidak_aktif" <?= $selected('status', 'tidak_aktif') ?>>Tidak Aktif</option>
            </select>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 flex-wrap sticky-form-actions">
        <button class="btn btn-primary px-4" data-testid="kk-save">
          <i class="bi bi-check-lg me-1"></i><?= $isNew ? 'Simpan & Lanjut Tambah Anggota' : 'Simpan dan Sinkronkan' ?>
        </button>
        <a class="btn btn-outline-secondary" href="<?= url('admin/keluarga') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php
$searchUrl = url('admin/keluarga/resident-search');
$inlineScript = '<script>window.POPULATION_RESIDENT_SEARCH_URL = ' . json_encode($searchUrl) . ';</script>' . <<<'HTML'
<script>
(function () {
  'use strict';

  const search = document.getElementById('residentSearch');
  const results = document.getElementById('residentResults');
  const selectedBox = document.getElementById('selectedResident');
  const selectedLabel = document.getElementById('selectedResidentLabel');
  const selectedId = document.getElementById('headResidentId');
  const clearButton = document.getElementById('clearResident');
  const createToggle = document.getElementById('createHeadToggle');
  const newHeadPanel = document.getElementById('newHeadPanel');
  const newHeadName = document.getElementById('newHeadName');
  const headName = document.getElementById('familyHeadName');
  const noKk = document.getElementById('familyNoKk');
  const address = document.getElementById('familyAddress');
  const dusun = document.getElementById('familyDusun');
  const rw = document.getElementById('familyRw');
  const rt = document.getElementById('familyRt');
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
    results.hidden = true;
    results.innerHTML = '';
  }

  function chooseResident(row) {
    selectedId.value = row.id;
    selectedLabel.textContent = row.nama + ' (' + row.nik + ')';
    selectedBox.hidden = false;
    search.value = '';
    hideResults();

    createToggle.checked = false;
    newHeadPanel.hidden = true;
    headName.value = row.nama || '';
    if (!noKk.value && row.no_kk) noKk.value = row.no_kk;
    if (!address.value && row.alamat) address.value = row.alamat;
    if (!dusun.value && row.dusun_id) dusun.value = row.dusun_id;
    if (!rw.value && row.rw_id) rw.value = row.rw_id;
    if (!rt.value && row.rt_id) rt.value = row.rt_id;
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
      results.innerHTML = '<div class="population-search-state"><span class="spinner-border spinner-border-sm me-2"></span>Mencari...</div>';

      try {
        const response = await fetch(window.POPULATION_RESIDENT_SEARCH_URL + '?q=' + encodeURIComponent(query), {
          headers: { 'Accept': 'application/json' },
          signal: requestController.signal
        });
        if (!response.ok) throw new Error('HTTP ' + response.status);
        const payload = await response.json();
        const rows = Array.isArray(payload.data) ? payload.data : [];

        if (!rows.length) {
          results.innerHTML = '<div class="population-search-state text-muted">Penduduk tidak ditemukan.</div>';
          return;
        }

        results.innerHTML = rows.map(function (row) {
          const kk = row.no_kk ? ' · KK ' + escapeHtml(row.no_kk) : ' · Belum memiliki KK';
          return '<button type="button" class="population-search-item" data-id="' + Number(row.id) + '">' +
            '<strong>' + escapeHtml(row.nama) + '</strong>' +
            '<small>' + escapeHtml(row.nik) + kk + '</small>' +
          '</button>';
        }).join('');

        results.querySelectorAll('.population-search-item').forEach(function (button) {
          button.addEventListener('click', function () {
            const row = rows.find(function (item) { return Number(item.id) === Number(button.dataset.id); });
            if (row) chooseResident(row);
          });
        });
      } catch (error) {
        if (error.name !== 'AbortError') {
          results.innerHTML = '<div class="population-search-state text-danger">Pencarian gagal. Coba kembali.</div>';
        }
      }
    }, 280);
  });

  clearButton?.addEventListener('click', function () {
    selectedId.value = '';
    selectedLabel.textContent = '';
    selectedBox.hidden = true;
  });

  createToggle?.addEventListener('change', function () {
    newHeadPanel.hidden = !createToggle.checked;
    if (createToggle.checked) {
      selectedId.value = '';
      selectedBox.hidden = true;
      newHeadName?.focus();
    }
  });

  newHeadName?.addEventListener('input', function () {
    if (createToggle.checked) headName.value = newHeadName.value;
  });

  document.addEventListener('click', function (event) {
    if (!results.contains(event.target) && event.target !== search) hideResults();
  });

  document.getElementById('familyForm')?.addEventListener('submit', function (event) {
    if (createToggle.checked && newHeadName.value.trim()) headName.value = newHeadName.value.trim();
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
