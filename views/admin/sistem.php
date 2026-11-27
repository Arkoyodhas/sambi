<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h4 class="fw-bold mb-0">Maintenance & Backup</h4>
    <div class="text-muted small">Khusus Super Admin. Semua aksi tercatat di Audit Log.</div>
  </div>
  <a href="<?= url('') ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Lihat Website</a>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="acard mb-3" data-testid="maintenance-card">
      <div class="acard-head d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-cone-striped me-2"></i>Mode Maintenance</h6>
        <span class="badge <?= $active ? 'text-bg-warning' : 'bg-success' ?>" data-testid="maintenance-status"><?= $active ? 'AKTIF' : 'Normal' ?></span>
      </div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/sistem/maintenance') ?>">
          <?= csrf_field() ?>
          <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch" name="maintenance_mode" value="1" id="mtSwitch" <?= setting('maintenance_mode') === '1' ? 'checked' : '' ?> data-testid="maintenance-toggle">
            <label class="form-check-label fw-semibold" for="mtSwitch">Aktifkan halaman pemeliharaan untuk pengunjung</label>
          </div>
          <div class="mb-3">
            <label class="form-label">Pesan untuk pengunjung</label>
            <textarea class="form-control" name="maintenance_message" rows="3" maxlength="500" placeholder="Kami sedang melakukan pemeliharaan sistem..." data-testid="maintenance-message"><?= e(setting('maintenance_message')) ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Perkiraan selesai (opsional)</label>
            <input class="form-control" name="maintenance_until" maxlength="60" value="<?= e(setting('maintenance_until')) ?>" placeholder="mis. Senin, 10.00 WIB" data-testid="maintenance-until">
          </div>
          <?php if ($flagActive): ?>
          <div class="alert alert-warning small py-2">File <code>storage/maintenance.flag</code> terdeteksi (pemeliharaan file inti). Situs tetap dalam mode maintenance sampai file dihapus.
            <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="clear_flag" id="clearFlag"><label class="form-check-label" for="clearFlag">Hapus file penanda saat mematikan maintenance</label></div>
          </div>
          <?php endif; ?>
          <button class="btn btn-primary" data-testid="maintenance-save"><i class="bi bi-save me-1"></i>Simpan</button>
        </form>
        <p class="small text-muted mt-3 mb-0">Saat aktif, pengunjung publik melihat halaman pemeliharaan (HTTP 503). Admin yang sudah login tetap dapat melihat website dan panel admin; halaman <code>/admin/login</code> tetap bisa dibuka.</p>
      </div>
    </div>

    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>Panduan Rollback File</h6></div>
      <div class="acard-body small">
        <ol class="ps-3 mb-0">
          <li class="mb-2">Sebelum memperbarui file inti, buat file kosong <code>storage/maintenance.flag</code> (lewat File Manager hosting). Website otomatis masuk mode maintenance.</li>
          <li class="mb-2">Buat backup database di panel ini, lalu unduh salinannya.</li>
          <li class="mb-2">Cadangkan folder website (File Manager → Compress → zip) atau gunakan fitur Backup/JetBackup di cPanel.</li>
          <li class="mb-2">Jika pembaruan bermasalah: ekstrak kembali zip versi sebelumnya ke folder website, lalu pulihkan database ke tanggal yang sama melalui tombol <strong>Pulihkan</strong>.</li>
          <li>Hapus <code>storage/maintenance.flag</code> dan matikan mode maintenance.</li>
        </ol>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="acard" data-testid="backup-card">
      <div class="acard-head d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-database-down me-2"></i>Backup & Rollback Database</h6>
        <form method="post" action="<?= url('admin/sistem/backup') ?>" class="m-0"><?= csrf_field() ?><button class="btn btn-sm btn-primary" data-testid="backup-create"><i class="bi bi-plus-lg me-1"></i>Buat Backup Sekarang</button></form>
      </div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-admin align-middle mb-0">
            <thead><tr><th>File</th><th>Tanggal</th><th>Ukuran</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php foreach ($backups as $b): ?>
              <tr data-testid="backup-row">
                <td class="small"><code><?= e($b['name']) ?></code></td>
                <td class="small"><?= e(tgl_id(date('Y-m-d H:i:s', $b['time']), true)) ?></td>
                <td class="small"><?= number_format($b['size'] / 1024, 0, ',', '.') ?> KB</td>
                <td class="text-end text-nowrap">
                  <a class="btn btn-sm btn-light" href="<?= url('admin/sistem/download?file=' . rawurlencode($b['name'])) ?>" title="Unduh" data-testid="backup-download"><i class="bi bi-download"></i></a>
                  <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#rs<?= md5($b['name']) ?>" data-testid="backup-restore-open"><i class="bi bi-arrow-counterclockwise"></i> Pulihkan</button>
                  <form method="post" action="<?= url('admin/sistem/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus backup ini?')"><?= csrf_field() ?><input type="hidden" name="file" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-light text-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>
                </td>
              </tr>
              <tr class="collapse" id="rs<?= md5($b['name']) ?>">
                <td colspan="4" class="bg-light">
                  <form method="post" action="<?= url('admin/sistem/restore') ?>" class="row g-2 align-items-end">
                    <?= csrf_field() ?><input type="hidden" name="file" value="<?= e($b['name']) ?>">
                    <div class="col-12 small text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Seluruh data saat ini akan diganti dengan isi backup ini. Cadangan otomatis kondisi sekarang dibuat terlebih dahulu.</div>
                    <div class="col-md-4"><label class="form-label small">Ketik PULIHKAN</label><input class="form-control form-control-sm" name="confirm" required autocomplete="off" data-testid="restore-confirm"></div>
                    <div class="col-md-5"><label class="form-label small">Password Anda</label><input class="form-control form-control-sm" type="password" name="password" required autocomplete="current-password" data-testid="restore-password"></div>
                    <div class="col-md-3"><button class="btn btn-sm btn-warning w-100" data-testid="restore-submit">Pulihkan</button></div>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$backups): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada backup. Klik "Buat Backup Sekarang".</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <p class="small text-muted mt-2">Backup disimpan di <code>storage/backups</code> (tidak bisa diakses publik). Maksimal 20 file terbaru disimpan.</p>
  </div>
</div>
