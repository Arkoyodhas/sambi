<?php $phTitle = 'Cek Status Pengaduan'; $phCrumb = 'Pengaduan'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container" style="max-width:720px">
    <div class="glass-panel p-4 mb-4">
      <form method="get" action="<?= url('pengaduan/cek') ?>" class="d-flex gap-2">
        <input class="form-control" name="tiket" placeholder="Masukkan nomor tiket, contoh: ADU-260615-XXXXX" value="<?= e($ticket) ?>">
        <button class="btn btn-primary px-4"><i class="bi bi-search"></i></button>
      </form>
    </div>
    <?php if ($ticket !== '' && !$complaint): ?>
    <div class="alert alert-warning">Tiket <strong><?= e($ticket) ?></strong> tidak ditemukan.</div>
    <?php elseif ($complaint): ?>
    <div class="card-3d card-body" data-testid="complaint-result">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
          <span class="badge-cat"><?= e($complaint['ticket']) ?></span>
          <span class="badge-cat"><?= e($complaint['category']) ?></span>
        </div>
        <?= status_badge($complaint['status']) ?>
      </div>
      <p><?= nl2br(e($complaint['content'])) ?></p>
      <small class="text-muted">Dilaporkan oleh <?= e($complaint['is_anonymous'] ? '(dirahasiakan)' : $complaint['nama']) ?> · <?= tgl_id($complaint['created_at'], true) ?></small>
      <hr>
      <h6 class="mb-3">Tanggapan Petugas</h6>
      <?php if (!$responses): ?><p class="text-muted small">Belum ada tanggapan. Pengaduan Anda sedang diverifikasi.</p><?php endif; ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($responses as $resp): ?>
        <div class="announce-item">
          <p class="small mb-1"><?= nl2br(e($resp['response'])) ?></p>
          <small class="text-muted"><i class="bi bi-person-badge me-1"></i><?= e($resp['admin_name'] ?: 'Petugas Desa') ?> · <?= tgl_id($resp['created_at'], true) ?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>
