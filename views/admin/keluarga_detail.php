<?php
$head = null;
foreach ($members as $m) { if (($m['hubungan_keluarga'] ?? '') === 'Kepala Keluarga') { $head = $m; break; } }
$hasAddress = trim((string) ($f['alamat'] ?? '')) !== '' || !empty($f['dusun_id']);
$defaultRel = $memberOld['hubungan'] ?? ($head ? 'Anak' : 'Kepala Keluarga');
$quickRels = ['Kepala Keluarga', 'Istri', 'Suami', 'Anak', 'Orang Tua', 'Cucu', 'Menantu', 'Mertua', 'Famili Lain', 'Lainnya'];
$age = static function (?string $d): string {
    if (!$d) return '';
    try { return (new DateTime($d))->diff(new DateTime())->y . ' th'; } catch (Throwable $e) { return ''; }
};
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $s = '';
    foreach (array_slice($parts, 0, 2) as $p) $s .= mb_strtoupper(mb_substr($p, 0, 1));
    return $s ?: '?';
};
$relGroup = static function (?string $r): string {
    return match ($r) {
        'Kepala Keluarga' => 'head', 'Istri', 'Suami' => 'spouse', 'Anak', 'Cucu' => 'child', default => 'other',
    };
};
?>
<div class="fam-wrap" data-testid="family-detail">
  <!-- Stepper alur -->
  <ol class="fam-steps" aria-label="Alur pengaturan keluarga">
    <li class="is-done"><span class="fam-step-ic"><i class="bi bi-check-lg"></i></span><div><strong>Data KK</strong><small>No. <?= e($f['no_kk']) ?></small></div></li>
    <li class="<?= $head ? 'is-done' : 'is-current' ?>" data-testid="step-head"><span class="fam-step-ic"><?= $head ? '<i class="bi bi-check-lg"></i>' : '2' ?></span><div><strong>Kepala Keluarga</strong><small><?= $head ? e($head['nama']) : 'Belum ditentukan' ?></small></div></li>
    <li class="<?= count($members) > 1 ? 'is-done' : ($head ? 'is-current' : '') ?>" data-testid="step-members"><span class="fam-step-ic"><?= count($members) > 1 ? '<i class="bi bi-check-lg"></i>' : '3' ?></span><div><strong>Anggota & Hubungan</strong><small><?= count($members) ?> orang terdaftar</small></div></li>
  </ol>

  <div class="row g-3">
    <div class="col-xl-8">
      <div class="acard fam-card">
        <div class="fam-hero">
          <div class="fam-hero-main">
            <span class="fam-hero-ic"><i class="bi bi-house-heart"></i></span>
            <div class="min-w-0">
              <small class="text-uppercase fw-semibold text-muted" style="letter-spacing:.08em;font-size:.68rem">Kartu Keluarga</small>
              <h5 class="mb-0 font-monospace" data-testid="family-nokk"><?= e($f['no_kk']) ?></h5>
              <div class="small text-muted text-truncate"><?= e($f['kepala_keluarga'] ?: 'Kepala keluarga belum ditentukan') ?></div>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/keluarga/edit/' . $f['id']) ?>" data-testid="family-edit-btn"><i class="bi bi-pencil me-1"></i>Edit KK</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/keluarga') ?>" data-testid="family-back-btn"><i class="bi bi-arrow-left me-1"></i>Daftar</a>
          </div>
        </div>
        <div class="fam-chips">
          <span><i class="bi bi-geo-alt"></i><?= e($f['alamat'] ?: 'Alamat belum diisi') ?></span>
          <span><i class="bi bi-signpost-2"></i>Dusun <?= e($f['dusun_name'] ?: '-') ?></span>
          <span><i class="bi bi-grid-3x3-gap"></i>RW <?= e($f['rw_name'] ?: '-') ?> / RT <?= e($f['rt_name'] ?: '-') ?></span>
          <span><?= status_badge($f['status']) ?></span>
        </div>
        <?php if (!$hasAddress): ?>
        <div class="fam-tip"><i class="bi bi-lightbulb"></i> Lengkapi alamat & wilayah lewat <a href="<?= url('admin/keluarga/edit/' . $f['id']) ?>">Edit KK</a> agar anggota baru otomatis mengikuti alamat keluarga.</div>
        <?php endif; ?>

        <div class="acard-body pt-2">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Anggota Keluarga <span class="badge rounded-pill text-bg-light border" data-testid="member-count"><?= count($members) ?></span></h6>
            <small class="text-muted d-none d-md-inline">Ubah hubungan langsung dari pilihan — tersimpan otomatis.</small>
          </div>

          <?php if (!$members): ?>
          <div class="fam-empty" data-testid="member-empty">
            <i class="bi bi-people"></i>
            <strong>Belum ada anggota</strong>
            <p class="mb-0 small text-muted">Mulai dengan menambahkan kepala keluarga di panel <b>Tambah Anggota</b>.</p>
          </div>
          <?php endif; ?>

          <div class="fam-members">
            <?php foreach ($members as $member): $grp = $relGroup($member['hubungan_keluarga'] ?? null); $isHead = $grp === 'head'; ?>
            <div class="fam-member fam-<?= $grp ?>" data-testid="member-row-<?= (int) $member['id'] ?>">
              <span class="fam-avatar <?= ($member['jenis_kelamin'] ?? 'L') === 'P' ? 'is-p' : 'is-l' ?>"><?= e($initials((string) $member['nama'])) ?></span>
              <div class="fam-member-info">
                <a class="fw-semibold text-decoration-none text-body" href="<?= url('admin/penduduk/detail/' . $member['id']) ?>"><?= e($member['nama']) ?></a>
                <?php if ($isHead): ?><span class="badge text-bg-primary ms-1"><i class="bi bi-star-fill me-1"></i>Kepala</span><?php endif; ?>
                <div class="fam-meta">
                  <span class="font-monospace"><?= e($member['nik']) ?></span>
                  <span><?= ($member['jenis_kelamin'] ?? 'L') === 'P' ? 'Perempuan' : 'Laki-laki' ?></span>
                  <?php if ($a = $age($member['tanggal_lahir'] ?? null)): ?><span><?= e($a) ?></span><?php endif; ?>
                </div>
              </div>
              <form class="fam-rel" method="post" action="<?= url('admin/keluarga/anggota-update/' . $member['id']) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="family_id" value="<?= (int) $f['id'] ?>">
                <select class="form-select form-select-sm" name="hubungan" data-autosubmit aria-label="Hubungan keluarga <?= e($member['nama']) ?>" data-testid="member-rel-<?= (int) $member['id'] ?>">
                  <?php foreach ($hubunganList as $relationship): ?>
                    <option value="<?= e($relationship) ?>" <?= ($member['hubungan_keluarga'] ?? '') === $relationship ? 'selected' : '' ?>><?= e($relationship) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
              <div class="fam-actions">
                <?php if (!$isHead): ?>
                <form method="post" action="<?= url('admin/keluarga/anggota-update/' . $member['id']) ?>" data-confirm="Jadikan <?= e($member['nama']) ?> sebagai Kepala Keluarga? Kepala lama akan menjadi Famili Lain.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="family_id" value="<?= (int) $f['id'] ?>">
                  <input type="hidden" name="hubungan" value="Kepala Keluarga">
                  <button class="btn btn-sm btn-light border" title="Jadikan Kepala Keluarga" data-testid="member-make-head-<?= (int) $member['id'] ?>"><i class="bi bi-star"></i></button>
                </form>
                <?php endif; ?>
                <a class="btn btn-sm btn-light border" href="<?= url('admin/penduduk/edit/' . $member['id']) ?>" title="Edit data lengkap" data-testid="member-edit-<?= (int) $member['id'] ?>"><i class="bi bi-pencil"></i></a>
                <form method="post" action="<?= url('admin/keluarga/anggota-hapus/' . $member['id']) ?>" data-confirm="Keluarkan <?= e($member['nama']) ?> dari KK ini? Data penduduknya tetap tersimpan.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="family_id" value="<?= (int) $f['id'] ?>">
                  <button class="btn btn-sm btn-light border text-danger" title="Keluarkan dari KK" data-testid="member-remove-<?= (int) $member['id'] ?>"><i class="bi bi-person-dash"></i></button>
                </form>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-xl-4">
      <div class="acard fam-add sticky-lg-top" style="top:84px" id="tambah-anggota" data-testid="member-add-panel">
        <div class="acard-head">
          <div>
            <h6 class="mb-1"><i class="bi bi-person-plus me-1"></i>Tambah Anggota</h6>
            <small class="text-muted"><?= $head ? 'Pilih orang, tentukan hubungan, selesai.' : 'Langkah berikutnya: tentukan kepala keluarga.' ?></small>
          </div>
        </div>
        <div class="acard-body">
          <div class="fam-seg" role="tablist">
            <button type="button" class="<?= $memberOld ? '' : 'active' ?>" data-seg="pick" data-testid="seg-pick"><i class="bi bi-search me-1"></i>Sudah terdata</button>
            <button type="button" class="<?= $memberOld ? 'active' : '' ?>" data-seg="new" data-testid="seg-new"><i class="bi bi-person-add me-1"></i>Penduduk baru</button>
          </div>

          <form method="post" action="" id="memberForm" data-pick="<?= url('admin/keluarga/anggota-tambah') ?>" data-new="<?= url('admin/keluarga/anggota-baru') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="family_id" value="<?= (int) $f['id'] ?>">

            <div data-pane="pick" <?= $memberOld ? 'hidden' : '' ?>>
              <input type="hidden" name="resident_id" id="memberResidentId">
              <label class="form-label small fw-semibold">1. Cari nama, NIK, atau No. KK</label>
              <div class="population-search-wrap">
                <input class="form-control" id="memberResidentSearch" placeholder="Ketik minimal 2 karakter..." autocomplete="off" data-testid="member-search-input">
                <div class="population-search-results shadow-sm" id="memberResidentResults" hidden></div>
              </div>
              <div class="selected-resident mt-2" id="memberSelectedResident" hidden data-testid="member-selected">
                <span class="min-w-0"><i class="bi bi-person-check-fill me-1"></i><strong id="memberSelectedLabel"></strong><small class="d-block text-muted" id="memberSelectedNote"></small></span>
                <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none" id="memberClearResident">Ganti</button>
              </div>
            </div>

            <div data-pane="new" <?= $memberOld ? '' : 'hidden' ?>>
              <label class="form-label small fw-semibold">1. Data singkat penduduk</label>
              <div class="row g-2">
                <div class="col-12"><input class="form-control digit-16" name="nik" placeholder="NIK (16 digit)" inputmode="numeric" maxlength="16" value="<?= e($memberOld['nik'] ?? '') ?>" data-testid="member-new-nik"></div>
                <div class="col-12"><input class="form-control" name="nama" placeholder="Nama lengkap" maxlength="150" value="<?= e($memberOld['nama'] ?? '') ?>" data-testid="member-new-nama"></div>
                <div class="col-6">
                  <select class="form-select" name="jenis_kelamin" data-testid="member-new-jk">
                    <option value="L" <?= ($memberOld['jenis_kelamin'] ?? 'L') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="P" <?= ($memberOld['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                  </select>
                </div>
                <div class="col-6"><input class="form-control" type="date" name="tanggal_lahir" max="<?= date('Y-m-d') ?>" value="<?= e($memberOld['tanggal_lahir'] ?? '') ?>" aria-label="Tanggal lahir" data-testid="member-new-tgl"></div>
                <div class="col-12"><input class="form-control" name="tempat_lahir" placeholder="Tempat lahir (opsional)" maxlength="100" value="<?= e($memberOld['tempat_lahir'] ?? '') ?>"></div>
              </div>
              <small class="text-muted d-block mt-1"><i class="bi bi-magic me-1"></i>Alamat, dusun, RW/RT & No. KK diisi otomatis dari keluarga ini.</small>
            </div>

            <label class="form-label small fw-semibold mt-3">2. Hubungan dalam keluarga</label>
            <div class="fam-relchips" data-testid="member-rel-chips">
              <?php foreach ($quickRels as $rel): ?>
              <label class="fam-relchip">
                <input type="radio" name="hubungan" value="<?= e($rel) ?>" <?= $rel === $defaultRel ? 'checked' : '' ?> data-testid="rel-chip-<?= e(slugify($rel)) ?>">
                <span><?= e($rel) ?></span>
              </label>
              <?php endforeach; ?>
            </div>

            <button class="btn btn-primary w-100 mt-3" data-testid="member-submit"><i class="bi bi-check2-circle me-1"></i><span id="memberSubmitText">Masukkan ke KK Ini</span></button>
            <small class="text-muted d-block mt-2" id="memberHint">Jika orang tersebut ada di KK lain, ia dipindahkan otomatis dan KK lamanya diperbarui.</small>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
$searchUrl = url('admin/keluarga/resident-search');
$inlineScript = '<script>window.POPULATION_RESIDENT_SEARCH_URL = ' . json_encode($searchUrl) . ';window.FAMILY_NO_KK = ' . json_encode((string) $f['no_kk']) . ';</script>' . <<<'HTML'
<script>
(function () {
  'use strict';
  const form = document.getElementById('memberForm');
  const search = document.getElementById('memberResidentSearch');
  const results = document.getElementById('memberResidentResults');
  const selectedId = document.getElementById('memberResidentId');
  const selectedBox = document.getElementById('memberSelectedResident');
  const selectedLabel = document.getElementById('memberSelectedLabel');
  const selectedNote = document.getElementById('memberSelectedNote');
  let mode = document.querySelector('.fam-seg .active')?.dataset.seg || 'pick';
  let timer = 0, controller = null;

  document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      sel.disabled = false; sel.classList.add('is-saving');
      sel.form.submit();
    });
  });
  document.querySelectorAll('.digit-16').forEach(function (input) {
    input.addEventListener('input', function () { input.value = input.value.replace(/\D/g, '').slice(0, 16); });
  });

  function setMode(m) {
    mode = m;
    document.querySelectorAll('.fam-seg button').forEach(function (b) { b.classList.toggle('active', b.dataset.seg === m); });
    document.querySelectorAll('[data-pane]').forEach(function (p) {
      const on = p.dataset.pane === m;
      p.hidden = !on;
      p.querySelectorAll('input,select').forEach(function (i) { i.disabled = !on; });
    });
    document.getElementById('memberSubmitText').textContent = m === 'new' ? 'Simpan & Masukkan ke KK' : 'Masukkan ke KK Ini';
    document.getElementById('memberHint').textContent = m === 'new'
      ? 'Data lengkap (agama, pekerjaan, dll.) bisa dilengkapi nanti lewat tombol edit.'
      : 'Jika orang tersebut ada di KK lain, ia dipindahkan otomatis dan KK lamanya diperbarui.';
  }
  document.querySelectorAll('.fam-seg button').forEach(function (b) { b.addEventListener('click', function () { setMode(b.dataset.seg); }); });
  setMode(mode);

  function esc(v) { const s = document.createElement('span'); s.textContent = v == null ? '' : String(v); return s.innerHTML; }
  function hideResults() { results.hidden = true; results.innerHTML = ''; }

  search?.addEventListener('input', function () {
    clearTimeout(timer);
    search.classList.remove('is-invalid');
    const q = search.value.trim();
    if (q.length < 2) { hideResults(); return; }
    timer = setTimeout(async function () {
      controller?.abort(); controller = new AbortController();
      results.hidden = false;
      results.innerHTML = '<div class="population-search-state"><span class="spinner-border spinner-border-sm me-2"></span>Mencari...</div>';
      try {
        const r = await fetch(window.POPULATION_RESIDENT_SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' }, signal: controller.signal });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const rows = ((await r.json()).data) || [];
        if (!rows.length) {
          results.innerHTML = '<div class="population-search-state text-muted">Tidak ditemukan. <button type="button" class="btn btn-link btn-sm p-0" data-go-new>Buat penduduk baru</button></div>';
          results.querySelector('[data-go-new]').addEventListener('click', function () { hideResults(); setMode('new'); });
          return;
        }
        results.innerHTML = rows.map(function (row) {
          const same = row.no_kk && row.no_kk === window.FAMILY_NO_KK;
          const kk = same ? '<span class="text-success">Sudah di KK ini</span>' : (row.no_kk ? 'KK ' + esc(row.no_kk) + ' · akan dipindahkan' : 'Belum punya KK');
          return '<button type="button" class="population-search-item" data-id="' + Number(row.id) + '"' + (same ? ' disabled' : '') + '>' +
            '<strong>' + esc(row.nama) + '</strong><small>' + esc(row.nik) + ' · ' + kk + '</small></button>';
        }).join('');
        results.querySelectorAll('.population-search-item').forEach(function (btn) {
          btn.addEventListener('click', function () {
            const row = rows.find(function (x) { return Number(x.id) === Number(btn.dataset.id); });
            if (!row) return;
            selectedId.value = row.id;
            selectedLabel.textContent = row.nama + ' (' + row.nik + ')';
            selectedNote.textContent = row.no_kk ? 'Pindah dari KK ' + row.no_kk : 'Belum memiliki KK';
            selectedBox.hidden = false; search.value = ''; search.closest('.population-search-wrap').hidden = true;
            hideResults();
          });
        });
      } catch (e) {
        if (e.name !== 'AbortError') results.innerHTML = '<div class="population-search-state text-danger">Pencarian gagal. Coba kembali.</div>';
      }
    }, 260);
  });

  document.getElementById('memberClearResident')?.addEventListener('click', function () {
    selectedId.value = ''; selectedBox.hidden = true;
    search.closest('.population-search-wrap').hidden = false; search.focus();
  });
  document.addEventListener('click', function (e) { if (results && !results.contains(e.target) && e.target !== search) hideResults(); });

  form?.addEventListener('submit', function (e) {
    form.action = form.dataset[mode];
    if (mode === 'pick' && !selectedId.value) { e.preventDefault(); search.classList.add('is-invalid'); search.focus(); return; }
    if (mode === 'new') {
      const nik = form.querySelector('[name=nik]'), nama = form.querySelector('[name=nama]');
      let bad = false;
      [[nik, /^\d{16}$/.test(nik.value)], [nama, nama.value.trim().length >= 2]].forEach(function (p) { p[0].classList.toggle('is-invalid', !p[1]); if (!p[1]) bad = true; });
      if (bad) { e.preventDefault(); return; }
    }
    const btn = e.submitter;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...'; }
  });
})();
</script>
HTML;
?>
