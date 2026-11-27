<?php $phTitle = 'Agenda Kegiatan Desa'; include BASE_PATH . '/views/partials/page_header.php';
$bulanNama = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>
<section class="section">
  <div class="container">
    <form class="row g-2 mb-4" method="get" action="<?= url('agenda') ?>">
      <div class="col-6 col-md-3">
        <select class="form-select" name="bulan">
          <option value="">Semua Bulan</option>
          <?php for ($b = 1; $b <= 12; $b++): ?><option value="<?= $b ?>" <?= $bulan === $b ? 'selected' : '' ?>><?= $bulanNama[$b] ?></option><?php endfor; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select class="form-select" name="tahun">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunList as $t): ?><option value="<?= (int) $t['y'] ?>" <?= $tahun === (int) $t['y'] ? 'selected' : '' ?>><?= (int) $t['y'] ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
    <div class="row g-4">
      <?php foreach ($pg['data'] as $ag): ?>
      <div class="col-md-6 col-lg-4" data-aos="fade-up">
        <div class="card-3d card-body h-100">
          <div class="d-flex gap-3">
            <div class="agenda-date">
              <div class="d"><?= date('d', strtotime($ag['event_date'])) ?></div>
              <div class="m"><?= substr($bulanNama[(int) date('n', strtotime($ag['event_date']))], 0, 3) ?> <?= date('y', strtotime($ag['event_date'])) ?></div>
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <h6 class="mb-1"><?= e($ag['title']) ?></h6>
                <?= status_badge($ag['status']) ?>
              </div>
              <div class="small text-muted d-flex flex-column gap-1">
                <?php if ($ag['start_time']): ?><span><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($ag['start_time'])) ?><?= $ag['end_time'] ? ' - ' . date('H:i', strtotime($ag['end_time'])) : '' ?> WIB</span><?php endif; ?>
                <?php if ($ag['location']): ?><span><i class="bi bi-geo-alt me-1"></i><?= e($ag['location']) ?></span><?php endif; ?>
                <?php if ($ag['pic']): ?><span><i class="bi bi-person me-1"></i><?= e($ag['pic']) ?></span><?php endif; ?>
              </div>
              <?php if ($ag['description']): ?><p class="small text-muted mt-2 mb-0"><?= e(excerpt($ag['description'], 100)) ?></p><?php endif; ?>
            </div>
          </div>
          <?php if ($ag['documentation']): ?>
          <a href="<?= e(upload_url($ag['documentation'])) ?>" class="glightbox d-block mt-3" data-gallery="agenda">
            <img src="<?= e(thumbnail_url($ag['documentation'], 640, 280)) ?>" decoding="async" class="w-100 rounded-3" style="height:140px;object-fit:cover" alt="Dokumentasi" loading="lazy">
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Tidak ada agenda untuk filter ini.</p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination_links($pg) ?></div>
  </div>
</section>
