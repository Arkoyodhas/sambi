<div class="row g-3">
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-send me-2 text-primary"></i>Kirim Notifikasi Baru</h6></div>
      <div class="acard-body">
        <?php if (!$vapidReady): ?>
        <div class="alert alert-warning small">Kunci VAPID belum dapat dibuat (periksa ekstensi OpenSSL PHP). Notifikasi push belum bisa dikirim.</div>
        <?php endif; ?>
        <div class="alert alert-info small py-2">
          <i class="bi bi-people me-1"></i> Perangkat berlangganan saat ini: <b data-testid="push-sub-count"><?= number_format($subCount, 0, ',', '.') ?></b>
        </div>
        <form method="post" action="<?= url('admin/push/kirim') ?>" data-testid="push-form">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Judul Notifikasi *</label>
            <input class="form-control" name="title" maxlength="150" required placeholder="contoh: Pengumuman Penting" data-testid="push-title-input">
          </div>
          <div class="mb-3">
            <label class="form-label">Isi Pesan *</label>
            <textarea class="form-control" name="body" rows="3" maxlength="300" required placeholder="contoh: Posyandu balita besok pukul 08.00 di Balai Desa." data-testid="push-body-input"></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Tautan Tujuan (opsional)</label>
            <input class="form-control" name="url" placeholder="contoh: pengumuman  atau  https://...">
            <small class="text-muted">Halaman yang dibuka saat notifikasi diketuk. Kosongkan untuk beranda.</small>
          </div>
          <button class="btn btn-primary" <?= $vapidReady ? '' : 'disabled' ?> data-testid="push-send-btn"><i class="bi bi-send me-1"></i> Kirim ke Semua Perangkat</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Notifikasi</h6></div>
      <div class="acard-body p-0" data-testid="push-history">
        <div class="table-responsive"><table class="table table-admin table-hover mb-0">
          <thead><tr><th>Waktu</th><th>Notifikasi</th><th class="text-center">Terkirim</th><th>Oleh</th></tr></thead>
          <tbody>
            <?php foreach ($history as $h): ?>
            <tr>
              <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
              <td><strong class="small"><?= e($h['title']) ?></strong><br><small class="text-muted"><?= e(excerpt($h['body'], 70)) ?></small></td>
              <td class="text-center"><span class="badge text-bg-success"><?= (int) $h['sent_count'] ?></span><?php if ($h['fail_count']): ?> <span class="badge text-bg-danger"><?= (int) $h['fail_count'] ?></span><?php endif; ?></td>
              <td class="small text-muted"><?= e($h['user_name'] ?: '-') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$history): ?><tr><td colspan="4" class="text-muted small p-3">Belum ada notifikasi terkirim.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
    <div class="acard mt-3">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-info-circle me-2 text-info"></i>Cara Kerja</h6></div>
      <div class="acard-body small text-muted">
        <ul class="mb-0">
          <li>Pengunjung website melihat tombol <b>"Aktifkan Notifikasi"</b> dan memberi izin di browser/HP mereka.</li>
          <li>Notifikasi yang Anda kirim di sini langsung muncul di perangkat semua pelanggan — termasuk warga yang memasang aplikasi (PWA).</li>
          <li>Push membutuhkan website online dengan <b>HTTPS</b>. Di iPhone, notifikasi hanya diterima oleh yang memasang aplikasi ke layar utama (ketentuan Apple).</li>
          <li>Perangkat yang berhenti berlangganan otomatis dihapus dari daftar saat pengiriman berikutnya.</li>
        </ul>
      </div>
    </div>
  </div>
</div>
