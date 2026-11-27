<div class="row g-4">
  <div class="col-lg-6">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-qr-code me-2 text-primary"></i>Pengaturan QR Code</h6></div>
      <div class="acard-body">
        <form method="get" action="<?= url('admin/qr') ?>" data-testid="qr-form">
          <div class="mb-3">
            <label class="form-label small">Teks / URL</label>
            <textarea name="text" class="form-control" rows="3" data-testid="qr-text" required><?= e($text) ?></textarea>
            <div class="form-text small">Bisa berupa URL website, link WhatsApp, teks bebas, dsb. Default: <code><?= e($baseUrl) ?></code></div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-4">
              <label class="form-label small">Ukuran (px)</label>
              <input type="number" name="size" min="120" max="1024" value="<?= (int)$size ?>" class="form-control form-control-sm" data-testid="qr-size">
            </div>
            <div class="col-md-4">
              <label class="form-label small">Warna QR</label>
              <input type="color" name="color" value="#<?= e($color) ?>" class="form-control form-control-sm form-control-color" data-testid="qr-color">
            </div>
            <div class="col-md-4">
              <label class="form-label small">Warna Latar</label>
              <input type="color" name="bg" value="#<?= e($bg) ?>" class="form-control form-control-sm form-control-color" data-testid="qr-bg">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <?php foreach ([
              ['Website', $baseUrl],
              ['Halaman Berita', $baseUrl . 'berita'],
              ['Layanan Online', $baseUrl . 'layanan'],
              ['Transparansi', $baseUrl . 'transparansi'],
              ['IDM', $baseUrl . 'idm'],
              ['Bansos', $baseUrl . 'bansos'],
            ] as [$lab, $u]): ?>
            <div class="col-6 col-md-4"><button type="button" onclick="document.querySelector('[data-testid=\'qr-text\']').value='<?= e($u) ?>';this.form.submit()" class="btn btn-sm btn-outline-secondary w-100"><?= e($lab) ?></button></div>
            <?php endforeach; ?>
          </div>
          <button class="btn btn-primary w-100" data-testid="qr-generate"><i class="bi bi-arrow-clockwise me-1"></i>Generate QR</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="acard">
      <div class="acard-head d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-image me-2 text-primary"></i>Hasil QR Code</h6>
        <div class="d-flex gap-2">
          <a href="<?= e($qrUrl) ?>" download="qrcode.png" class="btn btn-sm btn-outline-primary" data-testid="qr-download"><i class="bi bi-download me-1"></i>Unduh PNG</a>
          <button type="button" onclick="window.open('<?= e($qrUrl) ?>','_blank')" class="btn btn-sm btn-outline-secondary" data-testid="qr-print"><i class="bi bi-printer me-1"></i>Cetak</button>
        </div>
      </div>
      <div class="acard-body text-center py-4">
        <div style="position:relative;display:inline-block" data-testid="qr-wrap">
          <img src="<?= e($qrUrl) ?>" alt="QR Code" id="qrImg" class="img-fluid" style="max-width:320px;border:1px solid #e5e7eb;border-radius:12px;padding:12px;background:#fff" data-testid="qr-image">
          <?php $logoSrc = setting('logo_path') ? upload_url(setting('logo_path')) : (setting('pwa_icon_192') ? upload_url(setting('pwa_icon_192')) : ''); ?>
          <?php if ($logoSrc): ?>
          <img src="<?= e($logoSrc) ?>" alt="Logo Desa" id="qrLogo" style="position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);width:22%;height:22%;object-fit:contain;background:#fff;padding:5px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.12)" data-testid="qr-logo">
          <?php endif; ?>
        </div>
        <p class="text-muted small mt-3 mb-0"><strong>Scan untuk membuka:</strong><br><code class="text-break"><?= e($text) ?></code></p>
        <p class="small mt-3 mb-0">
          <span class="badge bg-info-subtle text-info-emphasis">Tips</span>
          Gunakan QR ini untuk tempel di kantor desa, brosur, papan pengumuman, kartu nama perangkat, atau kaos acara — supaya warga bisa akses website tanpa mengetik URL.
        </p>
      </div>
    </div>
  </div>
</div>
