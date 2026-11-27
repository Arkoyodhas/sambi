<?php $phTitle = 'Dokumen Publik'; $phDesc = 'Unduh peraturan desa, formulir layanan, dan laporan publik.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="section">
  <div class="container">
    <form class="row g-2 mb-4" method="get" action="<?= url('dokumen') ?>">
      <div class="col-md-4"><input class="form-control" name="q" placeholder="Cari dokumen..." value="<?= e($q) ?>"></div>
      <div class="col-6 col-md-3">
        <select class="form-select" name="kategori">
          <option value="">Semua Kategori</option>
          <?php foreach ($kategoriList as $k): ?><option value="<?= e($k['category']) ?>" <?= $kategori === $k['category'] ? 'selected' : '' ?>><?= e($k['category']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select class="form-select" name="tahun">
          <option value="">Semua Tahun</option>
          <?php foreach ($tahunList as $t): ?><option value="<?= (int) $t['year'] ?>" <?= $tahun === (int) $t['year'] ? 'selected' : '' ?>><?= (int) $t['year'] ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Cari</button></div>
    </form>
    <div class="d-flex flex-column gap-3">
      <?php $iconMap = ['pdf' => ['bi-file-earmark-pdf', '#dc3545'], 'doc' => ['bi-file-earmark-word', '#2b579a'], 'docx' => ['bi-file-earmark-word', '#2b579a'], 'xls' => ['bi-file-earmark-excel', '#217346'], 'xlsx' => ['bi-file-earmark-excel', '#217346']]; ?>
      <?php foreach ($pg['data'] as $d): [$ic, $col] = $iconMap[strtolower((string) $d['file_type'])] ?? ['bi-file-earmark', '#6c757d']; ?>
      <div class="card-3d card-body d-flex align-items-center gap-3 py-3" data-aos="fade-up">
        <div class="doc-icon" style="background:<?= $col ?>1a;color:<?= $col ?>"><i class="bi <?= $ic ?>"></i></div>
        <div class="flex-grow-1">
          <h6 class="mb-1"><?= e($d['title']) ?></h6>
          <small class="text-muted"><?= e($d['category']) ?> · <?= (int) $d['year'] ?: '-' ?> · <?= strtoupper((string) $d['file_type']) ?> · <?= $d['file_size'] ? round($d['file_size'] / 1024) . ' KB' : '' ?> · <i class="bi bi-download"></i> <?= (int) $d['downloads'] ?>x</small>
          <?php if ($d['description']): ?><p class="small text-muted mb-0 mt-1"><?= e(excerpt($d['description'], 100)) ?></p><?php endif; ?>
        </div>
        <a href="<?= url('dokumen/download/' . $d['id']) ?>" class="btn btn-primary btn-sm" data-testid="doc-download-<?= $d['id'] ?>"><i class="bi bi-download me-1"></i><span class="d-none d-md-inline">Unduh</span></a>
      </div>
      <?php endforeach; ?>
      <?php if (!$pg['data']): ?><p class="text-muted text-center py-5">Belum ada dokumen publik yang tersedia.</p><?php endif; ?>
    </div>
    <div class="mt-4"><?= pagination_links($pg) ?></div>
  </div>
</section>
