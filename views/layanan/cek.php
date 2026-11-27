<?php $phTitle = 'Cek Status Layanan'; $phCrumb = 'Layanan'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container" style="max-width:720px">
    <?php $flowType = 'cek'; include BASE_PATH . '/views/partials/flow_illustration.php'; ?>
    <div class="glass-panel p-4 mb-4" data-aos="fade-up">
      <form method="get" action="<?= url('layanan/cek') ?>" class="d-flex gap-2">
        <input class="form-control" name="tiket" placeholder="Masukkan nomor tiket, contoh: LYN-260615-XXXXX" value="<?= e($ticket) ?>" data-testid="cek-tiket-input">
        <button class="btn btn-primary px-4" data-testid="cek-tiket-btn"><i class="bi bi-search"></i></button>
      </form>
    </div>
    <?php if ($ticket !== '' && !$request): ?>
    <div class="alert alert-warning">Tiket <strong><?= e($ticket) ?></strong> tidak ditemukan. Periksa kembali nomor tiket Anda.</div>
    <?php elseif ($request): ?>
    <div class="card-3d card-body" data-aos="fade-up" data-testid="cek-hasil">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
          <h5 class="mb-1"><?= e($request['service_name']) ?></h5>
          <span class="badge-cat"><?= e($request['ticket']) ?></span>
        </div>
        <?= status_badge($request['status']) ?>
      </div>
      <table class="table table-modern table-sm">
        <tr><td class="text-muted" style="width:160px">Nama Pemohon</td><td><?= e($request['nama']) ?></td></tr>
        <tr><td class="text-muted">Tanggal Pengajuan</td><td><?= tgl_id($request['created_at'], true) ?></td></tr>
        <tr><td class="text-muted">Keperluan</td><td><?= e($request['keperluan']) ?></td></tr>
        <?php if ($request['letter_number']): ?><tr><td class="text-muted">Nomor Surat</td><td><?= e($request['letter_number']) ?></td></tr><?php endif; ?>
        <?php if ($request['admin_note']): ?><tr><td class="text-muted">Catatan Petugas</td><td><?= nl2br(e($request['admin_note'])) ?></td></tr><?php endif; ?>
      </table>
      <div class="timeline mt-3">
        <?php
        $steps = ['baru' => 'Permohonan diterima sistem', 'diproses' => 'Sedang diverifikasi & diproses petugas', 'selesai' => 'Selesai — surat dapat diambil di kantor desa'];
        $order = ['baru' => 1, 'diproses' => 2, 'selesai' => 3, 'ditolak' => 2];
        $cur = $order[$request['status']] ?? 1;
        $i = 1;
        foreach ($steps as $key => $label): ?>
        <div class="timeline-item" style="<?= $i > $cur ? 'opacity:.4' : '' ?>">
          <strong class="small"><?= ucfirst($key) ?></strong>
          <p class="small mb-0 text-muted"><?= e($label) ?></p>
        </div>
        <?php $i++; endforeach; ?>
        <?php if ($request['status'] === 'ditolak'): ?>
        <div class="timeline-item"><strong class="small text-danger">Ditolak</strong><p class="small mb-0 text-muted">Permohonan tidak dapat diproses. Lihat catatan petugas.</p></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
