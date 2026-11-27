<?php
$hasIssues = !$trashed && $health && (
    (int) $health['missing_families'] > 0
    || (int) $health['missing_links'] > 0
    || (int) $health['multiple_heads'] > 0
);
?>
<?php if (!$trashed && $health): ?>
<div class="row g-3 mb-3 population-health">
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="ic"><i class="bi bi-house-door"></i></div>
      <div><div class="val"><?= (int) $health['families'] ?></div><div class="lab">Keluarga aktif</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile">
      <div class="ic"><i class="bi bi-people"></i></div>
      <div><div class="val"><?= (int) $health['residents'] ?></div><div class="lab">Penduduk tercatat</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile <?= (int) $health['missing_links'] > 0 ? 'population-stat-warning' : '' ?>">
      <div class="ic"><i class="bi bi-link-45deg"></i></div>
      <div><div class="val"><?= (int) $health['missing_links'] ?></div><div class="lab">Relasi perlu sinkron</div></div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-tile <?= (int) $health['empty_families'] > 0 ? 'population-stat-muted' : '' ?>">
      <div class="ic"><i class="bi bi-house-dash"></i></div>
      <div><div class="val"><?= (int) $health['empty_families'] ?></div><div class="lab">Keluarga tanpa anggota</div></div>
    </div>
  </div>
</div>

<?php if ($hasIssues): ?>
<div class="alert alert-warning d-flex flex-wrap gap-3 align-items-center justify-content-between">
  <div>
    <strong><i class="bi bi-exclamation-triangle me-1"></i>Ditemukan data yang belum konsisten.</strong>
    <div class="small">
      KK belum dibuat: <?= (int) $health['missing_families'] ?>,
      relasi anggota belum terhubung: <?= (int) $health['missing_links'] ?>,
      kepala keluarga ganda: <?= (int) $health['multiple_heads'] ?>.
    </div>
  </div>
  <form method="post" action="<?= url('admin/keluarga/sinkronkan') ?>" data-confirm="Sinkronkan seluruh data penduduk dan keluarga sekarang? Proses menggunakan transaksi dan dapat membangun ulang relasi anggota.">
    <?= csrf_field() ?>
    <button class="btn btn-warning">
      <i class="bi bi-arrow-repeat me-1"></i>Perbaiki Otomatis
    </button>
  </form>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="acard">
  <div class="acard-head">
    <form class="row g-2 flex-grow-1" method="get">
      <div class="col-12 col-lg-5">
        <input class="form-control form-control-sm" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Cari No. KK, kepala keluarga, atau alamat...">
      </div>
      <div class="col-6 col-lg-3">
        <select class="form-select form-select-sm" name="dusun">
          <option value="">Semua Dusun</option>
          <?php foreach ($dusunList as $region): ?>
            <option value="<?= (int) $region['id'] ?>" <?= ($_GET['dusun'] ?? '') == $region['id'] ? 'selected' : '' ?>><?= e($region['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-4 col-lg-2">
        <select class="form-select form-select-sm" name="status">
          <option value="">Semua Status</option>
          <?php foreach (['aktif' => 'Aktif', 'pindah' => 'Pindah', 'tidak_aktif' => 'Tidak Aktif'] as $statusValue => $statusLabel): ?>
            <option value="<?= e($statusValue) ?>" <?= ($_GET['status'] ?? '') === $statusValue ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-2 col-lg-1">
        <button class="btn btn-sm btn-outline-primary w-100" title="Cari"><i class="bi bi-search"></i></button>
      </div>
      <div class="col-12 col-lg-1">
        <a class="btn btn-sm btn-outline-secondary w-100" href="<?= url($trashed ? 'admin/keluarga/trash' : 'admin/keluarga') ?>" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
      </div>
    </form>
  </div>

  <div class="acard-head py-2">
    <div>
      <strong class="small"><?= $trashed ? 'Data keluarga di sampah' : 'Keluarga terbaru tampil paling atas' ?></strong>
      <small class="text-muted d-block">Total <?= (int) $pg['total'] ?> KK</small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <?php if ($trashed): ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/keluarga') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
      <?php else: ?>
        <?php if (!$hasIssues): ?>
          <form method="post" action="<?= url('admin/keluarga/sinkronkan') ?>" data-confirm="Periksa dan sinkronkan seluruh relasi penduduk-keluarga?">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-repeat me-1"></i>Sinkronkan</button>
          </form>
        <?php endif; ?>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/keluarga/trash') ?>" title="Sampah data keluarga"><i class="bi bi-trash3"></i></a>
        <a class="btn btn-sm btn-primary" href="<?= url('admin/keluarga/create') ?>" data-testid="keluarga-add"><i class="bi bi-plus-lg me-1"></i>Tambah KK</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-admin table-hover mb-0">
        <thead>
          <tr>
            <th>No. KK</th>
            <th>Kepala Keluarga</th>
            <th>Alamat</th>
            <th>Wilayah</th>
            <th>Status</th>
            <th>Anggota</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pg['data'] as $family): ?>
          <tr>
            <td class="font-monospace small"><?= e($family['no_kk']) ?></td>
            <td>
              <strong class="small"><?= e($family['kepala_keluarga'] ?: 'Belum ditentukan') ?></strong>
              <small class="text-muted d-block">Diperbarui <?= e(tgl_id($family['updated_at'], true)) ?></small>
            </td>
            <td class="small"><?= e($family['alamat'] ?: '-') ?></td>
            <td class="small"><?= e($family['dusun_name'] ?: '-') ?> <?= e($family['rw_name'] ?: '') ?> <?= e($family['rt_name'] ?: '') ?></td>
            <td><?= status_badge($family['status']) ?></td>
            <td>
              <span class="badge <?= (int) $family['jumlah_anggota'] > 0 ? 'text-bg-light border' : 'text-bg-warning' ?>">
                <?= (int) $family['jumlah_anggota'] ?> jiwa
              </span>
            </td>
            <td class="text-end text-nowrap">
              <?php if ($trashed): ?>
                <form class="d-inline" method="post" action="<?= url('admin/keluarga/restore/' . $family['id']) ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-success" title="Pulihkan"><i class="bi bi-arrow-counterclockwise"></i></button>
                </form>
              <?php else: ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/keluarga/detail/' . $family['id']) ?>" title="Kelola anggota"><i class="bi bi-people"></i></a>
                <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/keluarga/edit/' . $family['id']) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                <?php if ((int) $family['jumlah_anggota'] === 0): ?>
                  <form class="d-inline" method="post" action="<?= url('admin/keluarga/delete/' . $family['id']) ?>" data-confirm="Pindahkan keluarga kosong ini ke sampah?">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" title="Pindahkan ke sampah"><i class="bi bi-trash"></i></button>
                  </form>
                <?php else: ?>
                  <button class="btn btn-sm btn-outline-danger" disabled title="Keluarkan atau pindahkan semua anggota sebelum menghapus keluarga"><i class="bi bi-trash"></i></button>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$pg['data']): ?>
            <tr><td colspan="7" class="text-center text-muted py-4"><?= $trashed ? 'Sampah keluarga kosong.' : 'Belum ada data keluarga.' ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="acard-body py-2 d-flex justify-content-between align-items-center">
    <small class="text-muted">Halaman <?= (int) $pg['page'] ?> dari <?= (int) $pg['pages'] ?></small>
    <?= pagination_links($pg) ?>
  </div>
</div>
