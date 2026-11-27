<?php
$selectedDusun = trim((string) ($_GET['dusun'] ?? ''));
if ($selectedDusun !== '' && ctype_digit($selectedDusun)) {
    foreach ($dusunList as $legacyDusun) {
        if ((string) $legacyDusun['id'] === $selectedDusun) {
            $selectedDusun = (string) $legacyDusun['name'];
            break;
        }
    }
}
$selectedRt = trim((string) ($_GET['rt'] ?? ''));
$hasAdvancedFilter = !empty($_GET['jk'])
    || !empty($_GET['status'])
    || !empty($_GET['pendidikan'])
    || !empty($_GET['pekerjaan'])
    || (isset($_GET['umur_min']) && $_GET['umur_min'] !== '')
    || (isset($_GET['umur_max']) && $_GET['umur_max'] !== '');
$hasAnyFilter = trim((string) ($_GET['q'] ?? '')) !== ''
    || $selectedDusun !== ''
    || $selectedRt !== ''
    || $hasAdvancedFilter;

$dusunRtMap = [];
foreach ((array) $filterRtList as $rtItem) {
    $dusunKey = (string) ($rtItem['dusun'] ?? '');
    $rtName = trim((string) ($rtItem['name'] ?? ''));
    if ($dusunKey === '' || $rtName === '') {
        continue;
    }
    $dusunRtMap[$dusunKey][$rtName] = true;
}
foreach ($dusunRtMap as $dusunKey => $rtNames) {
    $rtNames = array_keys($rtNames);
    natcasesort($rtNames);
    $dusunRtMap[$dusunKey] = array_values($rtNames);
}

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<style>
.resident-filter-panel{
  border:1px solid var(--bs-border-color);
  border-radius:14px;
  padding:1rem;
  background:var(--bs-tertiary-bg);
  overflow:hidden;
}
.resident-filter-grid{
  display:grid;
  grid-template-columns:minmax(220px,1.6fr) minmax(240px,1.35fr) minmax(180px,.9fr) auto;
  gap:.85rem;
  align-items:end;
}
.resident-filter-field{min-width:0}
.resident-filter-field label{
  display:block;
  min-height:1.25rem;
  margin:0 0 .38rem;
  font-size:.78rem;
  line-height:1.25;
  font-weight:700;
  color:var(--bs-secondary-color);
  white-space:normal;
}
.resident-filter-field .form-control,
.resident-filter-field .form-select{
  width:100%;
  min-width:0;
}
.resident-filter-field .form-control,
.resident-filter-field .form-select,
.resident-filter-actions .btn{
  min-height:40px;
}
.resident-filter-field .form-select{
  padding-right:2.35rem;
  background-position:right .75rem center;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}
.resident-search-control{
  position:relative;
  width:100%;
  min-width:0;
}
.resident-search-control>i{
  position:absolute;
  top:50%;
  left:.85rem;
  z-index:2;
  width:1rem;
  height:1rem;
  display:flex;
  align-items:center;
  justify-content:center;
  color:var(--bs-secondary-color);
  line-height:1;
  pointer-events:none;
  transform:translateY(-50%);
}
.resident-search-control .form-control{
  padding-left:2.55rem;
}
.resident-search-control:focus-within>i{
  color:var(--bs-primary);
}
.resident-filter-actions{
  display:flex;
  gap:.5rem;
  align-items:stretch;
}
.resident-filter-actions .btn{display:inline-flex;align-items:center;justify-content:center;white-space:nowrap}
.resident-filter-actions .btn-primary{min-width:104px}
.resident-filter-help{
  margin-top:.7rem;
  padding:.55rem .7rem;
  border-radius:10px;
  background:var(--bs-body-bg);
  color:var(--bs-secondary-color);
  font-size:.78rem;
  line-height:1.45;
  overflow-wrap:anywhere;
}
.resident-filter-details{margin-top:.85rem}
.resident-filter-details summary{
  width:max-content;
  max-width:100%;
  cursor:pointer;
  font-size:.82rem;
  font-weight:700;
  color:var(--bs-primary);
}
.resident-advanced-grid{
  display:grid;
  grid-template-columns:repeat(6,minmax(0,1fr));
  gap:.8rem;
  margin-top:.75rem;
}
.resident-stat-grid{
  display:grid;
  grid-template-columns:repeat(4,minmax(110px,1fr));
  gap:.6rem;
}
.resident-stat{
  border:1px solid var(--bs-border-color);
  border-radius:12px;
  padding:.72rem .8rem;
  background:var(--bs-body-bg);
  min-width:0;
}
.resident-stat strong{display:block;font-size:1.1rem;line-height:1.1}
.resident-stat small{display:block;margin-top:.2rem;color:var(--bs-secondary-color)}
.resident-location{min-width:145px}
.resident-location strong{display:block;font-size:.82rem;line-height:1.3}
.resident-location small{display:block;margin-top:.2rem;color:var(--bs-secondary-color);line-height:1.35}
@media(max-width:1199.98px){
  .resident-filter-grid{grid-template-columns:minmax(220px,1.4fr) minmax(220px,1.2fr) minmax(170px,.8fr)}
  .resident-filter-actions{grid-column:1/-1;justify-content:flex-end}
  .resident-advanced-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media(max-width:767.98px){
  .resident-filter-panel{padding:.85rem}
  .resident-filter-grid{grid-template-columns:1fr 1fr;gap:.75rem}
  .resident-filter-field.is-search{grid-column:1/-1}
  .resident-filter-actions{grid-column:1/-1}
  .resident-filter-actions .btn-primary{flex:1}
  .resident-advanced-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .resident-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:575.98px){
  .resident-filter-grid{grid-template-columns:1fr}
  .resident-filter-field.is-search,
  .resident-filter-actions{grid-column:auto}
  .resident-filter-actions{display:grid;grid-template-columns:1fr 46px;width:100%}
  .resident-filter-actions .btn{width:100%;min-width:0}
  .resident-advanced-grid{grid-template-columns:1fr}
  .resident-stat-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .resident-filter-field label{min-height:0}
}
</style>

<div class="acard">
  <div class="acard-head">
    <div>
      <h6 class="mb-1"><i class="bi bi-people me-2 text-primary"></i><?= $trashed ? 'Sampah Data Penduduk' : 'Data Penduduk' ?></h6>
      <small class="text-muted">Pilih dusun untuk menampilkan RT yang berada di dusun tersebut.</small>
    </div>
  </div>

  <div class="acard-body border-bottom">
    <form id="residentFilterForm" method="get" class="resident-filter-panel" autocomplete="off">
      <div class="resident-filter-grid">
        <div class="resident-filter-field is-search">
          <label for="residentSearch">Cari penduduk</label>
          <div class="resident-search-control">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input id="residentSearch" class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Nama, NIK, atau No. KK" data-testid="penduduk-search">
          </div>
        </div>

        <div class="resident-filter-field">
          <label for="filterDusun">Dusun dan RT yang tersedia</label>
          <select id="filterDusun" class="form-select" name="dusun">
            <option value="">Semua Dusun</option>
            <?php foreach ($filterDusunList as $d): ?>
              <?php
                $dusunKey = (string) $d['key'];
                $rtNames = $dusunRtMap[$dusunKey] ?? [];
                $rtPreview = array_slice($rtNames, 0, 4);
                $remainingRt = max(0, count($rtNames) - count($rtPreview));
                $optionLabel = (string) $d['name'];
                if ($rtPreview) {
                    $optionLabel .= ' — ' . implode(', ', $rtPreview);
                    if ($remainingRt > 0) {
                        $optionLabel .= ' +' . $remainingRt . ' RT';
                    }
                } else {
                    $optionLabel .= ' — belum ada RT';
                }
                $optionLabel .= ' (' . (int) $d['jumlah'] . ' penduduk)';
              ?>
              <option
                value="<?= e($dusunKey) ?>"
                data-rt-list="<?= e($rtNames ? implode(', ', $rtNames) : 'Belum ada RT') ?>"
                <?= $selectedDusun === $dusunKey ? 'selected' : '' ?>
              ><?= e($optionLabel) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="resident-filter-field">
          <label for="filterRt">RT</label>
          <select id="filterRt" class="form-select" name="rt" data-selected="<?= e($selectedRt) ?>">
            <option value="">Semua RT</option>
          </select>
        </div>

        <div class="resident-filter-actions">
          <button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
          <a class="btn btn-outline-secondary" href="<?= url($trashed ? 'admin/penduduk/trash' : 'admin/penduduk') ?>" title="Reset filter" aria-label="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
      </div>

      <div id="regionFilterHelp" class="resident-filter-help" aria-live="polite">
        Pilih dusun. Daftar RT akan disesuaikan otomatis.
      </div>

      <details class="resident-filter-details" <?= $hasAdvancedFilter ? 'open' : '' ?>>
        <summary>Filter lanjutan</summary>
        <div class="resident-advanced-grid">
          <div class="resident-filter-field">
            <label for="filterJk">Jenis kelamin</label>
            <select id="filterJk" class="form-select" name="jk">
              <option value="">Semua</option>
              <option value="L" <?= ($_GET['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="P" <?= ($_GET['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
          </div>
          <div class="resident-filter-field">
            <label for="filterStatus">Status penduduk</label>
            <select id="filterStatus" class="form-select" name="status">
              <option value="">Semua Status</option>
              <?php foreach (['hidup', 'meninggal', 'pindah', 'tidak_aktif'] as $statusOption): ?>
                <option value="<?= $statusOption ?>" <?= ($_GET['status'] ?? '') === $statusOption ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $statusOption))) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="resident-filter-field">
            <label for="filterPendidikan">Pendidikan</label>
            <select id="filterPendidikan" class="form-select" name="pendidikan">
              <option value="">Semua Pendidikan</option>
              <?php foreach (PendudukController::PENDIDIKAN as $pendidikan): ?>
                <option value="<?= e($pendidikan) ?>" <?= ($_GET['pendidikan'] ?? '') === $pendidikan ? 'selected' : '' ?>><?= e($pendidikan) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="resident-filter-field">
            <label for="filterPekerjaan">Pekerjaan</label>
            <input id="filterPekerjaan" class="form-control" name="pekerjaan" value="<?= e($_GET['pekerjaan'] ?? '') ?>" placeholder="Contoh: Petani">
          </div>
          <div class="resident-filter-field">
            <label for="filterUmurMin">Umur minimal</label>
            <input id="filterUmurMin" class="form-control" type="number" min="0" max="150" name="umur_min" value="<?= e($_GET['umur_min'] ?? '') ?>">
          </div>
          <div class="resident-filter-field">
            <label for="filterUmurMax">Umur maksimal</label>
            <input id="filterUmurMax" class="form-control" type="number" min="0" max="150" name="umur_max" value="<?= e($_GET['umur_max'] ?? '') ?>">
          </div>
          <div class="resident-filter-field">
            <label for="filterPerPage">Baris per halaman</label>
            <select id="filterPerPage" class="form-select" name="per_page">
              <?php foreach ([20, 50, 100] as $size): ?>
                <option value="<?= $size ?>" <?= (int) ($_GET['per_page'] ?? 20) === $size ? 'selected' : '' ?>><?= $size ?> data</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </details>
    </form>
  </div>

  <div class="acard-body border-bottom">
    <div class="resident-stat-grid">
      <div class="resident-stat"><strong><?= number_format((int) $filterStats['total'], 0, ',', '.') ?></strong><small>Hasil penduduk</small></div>
      <div class="resident-stat"><strong><?= number_format((int) $filterStats['laki_laki'], 0, ',', '.') ?></strong><small>Laki-laki</small></div>
      <div class="resident-stat"><strong><?= number_format((int) $filterStats['perempuan'], 0, ',', '.') ?></strong><small>Perempuan</small></div>
      <div class="resident-stat"><strong><?= number_format((int) $filterStats['keluarga'], 0, ',', '.') ?></strong><small>Keluarga/KK</small></div>
    </div>
    <?php if ($hasAnyFilter): ?>
      <div class="mt-2 small text-muted"><i class="bi bi-info-circle me-1"></i>Ringkasan mengikuti filter yang sedang aktif.</div>
    <?php endif; ?>
  </div>

  <div class="acard-head py-2">
    <div>
      <strong class="small"><?= $trashed ? 'Data di Sampah' : 'Daftar Penduduk' ?></strong>
      <small class="text-muted ms-1">Halaman <?= (int) $pg['page'] ?> dari <?= (int) $pg['pages'] ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if (!$trashed): ?>
        <?php if (can('export_penduduk')): ?>
          <a class="btn btn-sm btn-outline-success" href="<?= url('admin/penduduk/export?' . http_build_query($_GET)) ?>" data-testid="penduduk-export"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
          <a class="btn btn-sm btn-outline-danger" href="<?= url('admin/penduduk/cetak?' . http_build_query($_GET)) ?>" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <?php endif; ?>
        <?php if (can('import_penduduk')): ?><a class="btn btn-sm btn-outline-primary" href="<?= url('admin/penduduk/import') ?>"><i class="bi bi-upload me-1"></i>Import</a><?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/penduduk/trash') ?>" title="Sampah"><i class="bi bi-trash3"></i></a>
        <a class="btn btn-sm btn-primary" href="<?= url('admin/penduduk/create') ?>" data-testid="penduduk-add"><i class="bi bi-plus-lg me-1"></i>Tambah</a>
      <?php else: ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/penduduk') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>NIK / No. KK</th>
            <th>Nama</th>
            <th>L/P · Umur</th>
            <th>Dusun / RT</th>
            <th>Pekerjaan</th>
            <th>Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pg['data'] as $r): ?>
            <tr>
              <td>
                <span class="font-monospace small"><?= e($r['nik']) ?></span><br>
                <small class="text-muted">KK: <?= e($r['no_kk'] ?: '-') ?></small>
              </td>
              <td>
                <strong class="small"><?= e($r['nama']) ?></strong>
                <?php if (!empty($r['hubungan_keluarga'])): ?><br><small class="text-muted"><?= e($r['hubungan_keluarga']) ?></small><?php endif; ?>
              </td>
              <td class="small">
                <?= e($r['jenis_kelamin']) ?>
                <span class="text-muted">·</span>
                <?= $r['tanggal_lahir'] ? umur($r['tanggal_lahir']) . ' th' : '-' ?>
              </td>
              <td>
                <div class="resident-location">
                  <strong><i class="bi bi-geo-alt-fill text-primary me-1"></i><?= e($r['dusun_name'] ?: 'Belum Terpetakan') ?></strong>
                  <small>
                    <?php if (!empty($r['rw_name'])): ?><?= e($r['rw_name']) ?> · <?php endif; ?>
                    <?= e($r['rt_name'] ?: 'RT belum diisi') ?>
                  </small>
                </div>
              </td>
              <td class="small"><?= e($r['pekerjaan'] ?: '-') ?></td>
              <td><?= status_badge($r['status_penduduk']) ?></td>
              <td class="text-end text-nowrap">
                <?php if ($trashed): ?>
                  <form class="d-inline" method="post" action="<?= url('admin/penduduk/restore/' . $r['id']) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i></button></form>
                <?php else: ?>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/penduduk/detail/' . $r['id']) ?>" title="Detail"><i class="bi bi-eye"></i></a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/penduduk/edit/' . $r['id']) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                  <form class="d-inline" method="post" action="<?= url('admin/penduduk/delete/' . $r['id']) ?>" data-confirm="Pindahkan data penduduk ini ke sampah?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?>
            <tr>
              <td colspan="7" class="text-center py-5">
                <i class="bi bi-search fs-3 text-muted d-block mb-2"></i>
                <strong>Data tidak ditemukan</strong>
                <div class="text-muted small mt-1">Ubah pilihan Dusun atau RT, lalu coba kembali.</div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="acard-body py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
    <small class="text-muted">Menampilkan maksimal <?= (int) $pg['per_page'] ?> data per halaman dari total <?= number_format((int) $pg['total'], 0, ',', '.') ?> hasil.</small>
    <?= pagination_links($pg) ?>
  </div>
</div>

<script>
(() => {
  'use strict';

  const rtData = <?= json_encode(array_values($filterRtList), $jsonFlags) ?>;
  const dusunSelect = document.getElementById('filterDusun');
  const rtSelect = document.getElementById('filterRt');
  const help = document.getElementById('regionFilterHelp');

  if (!dusunSelect || !rtSelect || !help) return;

  const initialRt = rtSelect.dataset.selected || '';
  const same = (left, right) => String(left || '') === String(right || '');

  function createOption(value, label) {
    const node = document.createElement('option');
    node.value = String(value);
    node.textContent = label;
    return node;
  }

  function renderRt(preferredValue = '') {
    const dusun = dusunSelect.value;
    const rows = rtData.filter((item) => !dusun || same(item.dusun, dusun));

    rtSelect.replaceChildren(createOption('', rows.length ? 'Semua RT' : 'RT belum tersedia'));
    rows.forEach((item) => {
      const dusunLabel = item.dusun === '__unmapped__' ? 'Belum Terpetakan' : item.dusun;
      const parentLabel = dusun ? '' : ` — ${dusunLabel}`;
      const count = Number(item.jumlah || 0) > 0 ? ` (${item.jumlah} penduduk)` : '';
      rtSelect.appendChild(createOption(item.id, `${item.name}${parentLabel}${count}`));
    });

    const exists = rows.some((item) => same(item.id, preferredValue));
    rtSelect.value = exists ? String(preferredValue) : '';
    rtSelect.disabled = rows.length === 0;
  }

  function updateHelp() {
    const selected = dusunSelect.options[dusunSelect.selectedIndex];
    if (!dusunSelect.value || !selected) {
      const totalRt = Math.max(0, rtSelect.options.length - 1);
      help.textContent = `Semua dusun ditampilkan. Tersedia ${totalRt} pilihan RT. Pilih dusun agar daftar RT lebih singkat.`;
      return;
    }

    const rtList = selected.dataset.rtList || 'Belum ada RT';
    const cleanLabel = selected.textContent.split(' — ')[0].trim();
    help.textContent = `${cleanLabel}: ${rtList}.`;
  }

  dusunSelect.addEventListener('change', () => {
    renderRt('');
    updateHelp();
  });

  renderRt(initialRt);
  updateHelp();
})();
</script>
