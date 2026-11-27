<?php
$blockedSet = array_flip(array_column($blocked, 'ip'));
$totalAll = (int) ($totals['total'] ?? 0);
$totalIps = (int) ($totals['ips'] ?? 0);
$chatEnabled = setting('livechat_enabled', '1') === '1' && can('kelola_tampilan');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <p class="text-muted small mb-0">Daftar indikasi upaya screenshot yang tertangkap dari sisi pengunjung. Sinyal browser seperti PrintScreen, Print, Cmd+Shift+3/4/5, Windows key, serta perubahan fokus/visibilitas tertentu dicatat lengkap dengan IP, perangkat, halaman, dan waktunya.</p>
  <form method="post" action="<?= url('admin/screenshot-log/clear') ?>" onsubmit="return confirm('Hapus log yang lebih tua dari 60 hari?')">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <button class="btn btn-sm btn-outline-secondary" data-testid="ss-clear-old"><i class="bi bi-eraser"></i> Bersihkan &gt; 60 hari</button>
  </form>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['bi-shield-exclamation', $today, 'Upaya Hari Ini', 'warning'],
      ['bi-camera', $totalAll, 'Total Upaya', 'danger'],
      ['bi-fingerprint', $totalIps, 'IP Berbeda', 'primary'],
  ] as $i => [$ic, $val, $lab, $col]): ?>
  <div class="col-md-4 col-6">
    <div class="stat-tile" data-testid="ss-stat-<?= $i ?>">
      <div class="ic text-<?= $col ?>"><i class="bi <?= $ic ?>"></i></div>
      <div><div class="val"><?= number_format((int) $val, 0, ',', '.') ?></div><div class="lab"><?= $lab ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-7">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-fingerprint me-2 text-primary"></i>15 IP Paling Sering Mencoba</h6></div>
      <div class="acard-body p-0">
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="small text-muted"><tr><th class="ps-3">IP</th><th>Lokasi</th><th>Perangkat</th><th class="text-end">Upaya</th><th class="text-end">Terakhir</th><th class="text-end pe-3">Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($topIps as $r): $isBlocked = isset($blockedSet[$r['ip_address']]); ?>
              <tr data-testid="ss-top-ip-row">
                <td class="ps-3"><code><?= e($r['ip_address']) ?></code><?php if ($isBlocked): ?> <span class="badge bg-danger">Diblokir</span><?php endif; ?></td>
                <td class="small"><?= e(trim(($r['city'] ?? '') . ' ' . ($r['country'] ?? '')) ?: '-') ?></td>
                <td class="small"><?= e(($r['os'] ?? '-') . ' · ' . ($r['browser'] ?? '-')) ?></td>
                <td class="text-end"><strong><?= number_format((int) $r['total'], 0, ',', '.') ?></strong></td>
                <td class="text-end small text-muted"><?= e(date('d/m H:i', strtotime($r['last_at']))) ?></td>
                <td class="text-end pe-3 text-nowrap">
                  <?php if ($chatEnabled): ?>
                    <button type="button" class="btn btn-sm btn-outline-primary js-ss-chat"
                            data-bs-toggle="modal" data-bs-target="#ssChatModal"
                            data-attempt-id="<?= (int) ($r['last_id'] ?? 0) ?>"
                            data-ip="<?= e($r['ip_address']) ?>"
                            data-method="Log terbaru dari IP ini"
                            title="Kirim pesan Live Chat" aria-label="Kirim pesan Live Chat"
                            data-testid="ss-chat-ip-btn"><i class="bi bi-chat-dots"></i></button>
                  <?php endif; ?>
                  <?php if ($isBlocked): ?>
                    <form method="post" action="<?= url('admin/screenshot-log/buka') ?>" class="d-inline">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="ip" value="<?= e($r['ip_address']) ?>">
                      <button class="btn btn-sm btn-outline-success" data-testid="ss-unban-btn"><i class="bi bi-unlock"></i></button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= url('admin/screenshot-log/blokir') ?>" class="d-inline" onsubmit="return confirm('Blokir IP <?= e($r['ip_address']) ?>? Perangkat ini tidak bisa lagi mengakses situs.')">
                      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                      <input type="hidden" name="ip" value="<?= e($r['ip_address']) ?>">
                      <button class="btn btn-sm btn-outline-danger" data-testid="ss-ban-btn" title="Blokir IP"><i class="bi bi-slash-circle"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$topIps): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada upaya screenshot tercatat.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-lightning me-2 text-warning"></i>Metode Terpopuler</h6></div>
      <div class="acard-body small">
        <?php $mxMethod = max(1, (int) ($topMethods[0]['total'] ?? 1)); foreach ($topMethods as $m): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between"><code class="text-truncate" style="max-width:180px"><?= e($m['method']) ?></code><strong><?= number_format((int) $m['total'], 0, ',', '.') ?></strong></div>
          <div class="progress" style="height:6px"><div class="progress-bar bg-warning" style="width:<?= round((int) $m['total'] / $mxMethod * 100) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$topMethods): ?><span class="text-muted">Belum ada data.</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="acard mb-4">
  <div class="acard-head d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>200 Upaya Terbaru</h6>
    <form method="get" action="<?= url('admin/screenshot-log') ?>" class="d-flex gap-2">
      <input type="text" name="ip" value="<?= e($ipFilter) ?>" class="form-control form-control-sm" placeholder="Filter IP..." style="width:150px" data-testid="ss-filter-ip">
      <input type="text" name="method" value="<?= e($method) ?>" class="form-control form-control-sm" placeholder="Filter metode..." style="width:150px" data-testid="ss-filter-method">
      <button class="btn btn-sm btn-primary" data-testid="ss-filter-apply"><i class="bi bi-funnel"></i> Filter</button>
      <?php if ($ipFilter || $method): ?><a href="<?= url('admin/screenshot-log') ?>" class="btn btn-sm btn-outline-secondary">Reset</a><?php endif; ?>
    </form>
  </div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="small text-muted"><tr><th class="ps-3">Waktu</th><th>IP</th><th>Metode</th><th>Halaman</th><th>Perangkat</th><th>Lokasi</th><th class="text-end pe-3">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $r): ?>
          <tr data-testid="ss-log-row">
            <td class="ps-3 small text-muted"><?= e(date('d/m/Y H:i:s', strtotime($r['created_at']))) ?></td>
            <td><code><?= e($r['ip_address']) ?></code></td>
            <td><span class="badge bg-warning-subtle text-warning-emphasis"><?= e($r['method']) ?></span></td>
            <td class="small text-truncate" style="max-width:220px"><?= e($r['path'] ?: '-') ?></td>
            <td class="small"><?= e(($r['os'] ?? '-') . ' · ' . ($r['browser'] ?? '-') . ' · ' . ($r['device'] ?? '-')) ?></td>
            <td class="small"><?= e(trim(($r['city'] ?? '') . ' ' . ($r['country'] ?? '')) ?: '-') ?></td>
            <td class="text-end pe-3 text-nowrap">
              <?php if ($chatEnabled): ?>
                <button type="button" class="btn btn-sm btn-link text-primary js-ss-chat"
                        data-bs-toggle="modal" data-bs-target="#ssChatModal"
                        data-attempt-id="<?= (int) $r['id'] ?>"
                        data-ip="<?= e($r['ip_address']) ?>"
                        data-method="<?= e($r['method']) ?>"
                        title="Kirim pesan Live Chat" aria-label="Kirim pesan Live Chat"
                        data-testid="ss-chat-log-btn"><i class="bi bi-chat-dots"></i></button>
              <?php endif; ?>
              <form method="post" action="<?= url('admin/screenshot-log/hapus') ?>" class="d-inline" onsubmit="return confirm('Hapus log ini?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-link text-danger" data-testid="ss-delete-log"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$recent): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada upaya screenshot tercatat<?= ($ipFilter || $method) ? ' untuk filter ini' : '' ?>.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($chatEnabled): ?>
<div class="modal fade" id="ssChatModal" tabindex="-1" aria-labelledby="ssChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= url('admin/screenshot-log/chat') ?>">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="ssChatModalLabel"><i class="bi bi-chat-dots me-2 text-primary"></i>Kirim Pesan Live Chat</h5>
            <div class="small text-muted mt-1" id="ssChatTarget">Target belum dipilih.</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="attempt_id" id="ssChatAttemptId" value="">
          <label class="form-label" for="ssChatBody">Pesan untuk pengunjung</label>
          <textarea class="form-control" name="body" id="ssChatBody" rows="5" maxlength="1000" required data-testid="ss-chat-message">Kami mendeteksi indikasi aktivitas tangkapan layar pada halaman yang Anda buka. Apakah ada yang bisa kami bantu?</textarea>
          <div class="form-text">
            Pesan dikirim ke sesi/perangkat yang menghasilkan log. IP hanya digunakan sebagai metadata verifikasi tambahan.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" data-testid="ss-chat-send">
            <i class="bi bi-send me-1"></i> Kirim Pesan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var attemptInput = document.getElementById('ssChatAttemptId');
  var targetText = document.getElementById('ssChatTarget');
  var bodyInput = document.getElementById('ssChatBody');

  document.querySelectorAll('.js-ss-chat').forEach(function (button) {
    button.addEventListener('click', function () {
      if (attemptInput) attemptInput.value = button.getAttribute('data-attempt-id') || '';
      if (targetText) {
        var ip = button.getAttribute('data-ip') || '-';
        var method = button.getAttribute('data-method') || '-';
        targetText.textContent = 'IP ' + ip + ' · ' + method;
      }
      window.setTimeout(function () {
        if (bodyInput) {
          bodyInput.focus();
          bodyInput.setSelectionRange(bodyInput.value.length, bodyInput.value.length);
        }
      }, 350);
    });
  });
})();
</script>
<?php endif; ?>

<div class="alert alert-info small mb-0">
  <i class="bi bi-info-circle me-1"></i>
  <strong>Catatan:</strong> Sistem mendeteksi upaya keyboard/shortcut (PrintScreen, Ctrl/Cmd+P, Cmd+Shift+3/4/5, Windows key, Snipping Tool), aksi print, serta blur/visibility yang sangat cepat (indikasi Snipping/Control Center). Screenshot dengan tombol fisik HP (Power+Volume) <em>tidak bisa dideteksi</em> — tapi konten yang tertangkap akan tetap buram karena veil proteksi.
</div>
