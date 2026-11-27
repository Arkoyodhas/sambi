<?php $shareUrl = url('berita/' . $news['slug']); ?>
<header class="page-header">
  <div class="container">
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-2">
      <li class="breadcrumb-item"><a href="<?= url('') ?>">Beranda</a></li>
      <li class="breadcrumb-item"><a href="<?= url('berita') ?>">Berita</a></li>
      <li class="breadcrumb-item active"><?= e(excerpt($news['title'], 40)) ?></li>
    </ol></nav>
    <span class="badge-cat"><?= e($news['category'] ?: 'Umum') ?></span>
    <h1 class="mt-2"><?= e($news['title']) ?></h1>
    <div class="d-flex flex-wrap gap-3 opacity-75 small">
      <span><i class="bi bi-person me-1"></i><?= e($news['author'] ?: 'Admin Desa') ?></span>
      <span><i class="bi bi-calendar3 me-1"></i><?= tgl_id($news['published_at'], true) ?></span>
      <span><i class="bi bi-eye me-1"></i><?= (int) $news['views'] + 1 ?> dibaca</span>
    </div>
  </div>
</header>
<section class="section pt-4">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-8">
        <?php if ($news['thumbnail']): ?>
        <img src="<?= e(thumbnail_url($news['thumbnail'], 1200, 675)) ?>" class="w-100 rounded-4 mb-4" style="max-height:440px;object-fit:cover" alt="<?= e($news['title']) ?>">
        <?php endif; ?>
        <div class="content-html mb-4" data-testid="news-content"><?= $news['content'] ?></div>

        <?php if ($tags): ?>
        <div class="d-flex flex-wrap gap-2 mb-4">
          <?php foreach ($tags as $t): ?>
          <a href="<?= url('berita?tag=' . $t['slug']) ?>" class="badge-cat text-decoration-none">#<?= e($t['name']) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="glass-panel p-3 d-flex align-items-center gap-3 flex-wrap mb-5">
          <strong class="small">Bagikan:</strong>
          <div class="share-btns d-flex gap-2">
            <a href="https://wa.me/?text=<?= urlencode($news['title'] . ' ' . $shareUrl) ?>" target="_blank" rel="noopener" style="background:#25d366" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>" target="_blank" rel="noopener" style="background:#1877f2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($news['title']) ?>" target="_blank" rel="noopener" style="background:#111" aria-label="X"><i class="bi bi-twitter-x"></i></a>
            <a href="https://t.me/share/url?url=<?= urlencode($shareUrl) ?>&text=<?= urlencode($news['title']) ?>" target="_blank" rel="noopener" style="background:#229ed9" aria-label="Telegram"><i class="bi bi-telegram"></i></a>
            <button type="button" class="btn-copy-link" data-url="<?= e($shareUrl) ?>" style="background:#5b6470" aria-label="Salin tautan" title="Salin tautan" data-testid="share-copy-btn"><i class="bi bi-link-45deg"></i></button>
            <button type="button" class="btn-native-share d-none" data-url="<?= e($shareUrl) ?>" data-title="<?= e($news['title']) ?>" style="background:var(--c-primary)" aria-label="Bagikan" title="Bagikan lainnya" data-testid="share-native-btn"><i class="bi bi-share"></i></button>
          </div>
        </div>

        <?php if ($news['allow_comments'] && setting('comments_enabled', '1') === '1'): ?>
        <div class="mb-4">
          <h4 class="mb-3">Komentar (<?= count($comments) ?>)</h4>
          <?php foreach ($comments as $c): ?>
          <div class="card-3d card-body mb-3 py-3">
            <div class="d-flex justify-content-between">
              <strong class="small"><?= e($c['name']) ?></strong>
              <small class="text-muted"><?= tgl_id($c['created_at']) ?></small>
            </div>
            <p class="mb-0 small mt-1"><?= nl2br(e($c['content'])) ?></p>
          </div>
          <?php endforeach; ?>
          <div class="glass-panel p-4 mt-3">
            <h6 class="mb-3">Tulis Komentar</h6>
            <form method="post" action="<?= url('berita/komentar') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="news_id" value="<?= (int) $news['id'] ?>">
              <div class="row g-3">
                <div class="col-md-6"><input class="form-control" name="name" value="<?= e(old('name')) ?>" maxlength="100" autocomplete="name" placeholder="Nama Anda *" required data-testid="comment-name"></div>
                <div class="col-md-6"><input class="form-control" type="email" name="email" value="<?= e(old('email')) ?>" maxlength="150" autocomplete="email" placeholder="Email (opsional)"></div>
                <div class="col-12"><textarea class="form-control" name="content" rows="3" maxlength="1000" placeholder="Komentar Anda *" required data-testid="comment-content"><?= e(old('content')) ?></textarea></div>
                <div class="col-md-6">
                  <input type="hidden" name="captcha_id" value="<?= e($captcha['id']) ?>">
                  <label class="form-label small">Captcha: <?= e($captcha['question']) ?></label>
                  <input class="form-control" name="captcha" inputmode="numeric" autocomplete="off" required placeholder="Jawaban">
                </div>
                <div class="col-12"><button class="btn btn-primary" data-testid="comment-submit">Kirim Komentar</button></div>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-4">
        <div class="sticky-sidebar glass-panel p-4">
          <h6 class="mb-3">Berita Terkait</h6>
          <div class="d-flex flex-column gap-3">
            <?php foreach ($related as $r): ?>
            <a class="d-flex gap-3 text-decoration-none align-items-center" href="<?= url('berita/' . $r['slug']) ?>">
              <?php if ($r['thumbnail']): ?><img src="<?= e(thumbnail_url($r['thumbnail'], 280, 180)) ?>" style="width:70px;height:56px;object-fit:cover;border-radius:10px" alt="" loading="lazy">
              <?php else: ?><div class="avatar-fallback" style="width:70px;height:56px;border-radius:10px"><i class="bi bi-newspaper"></i></div><?php endif; ?>
              <div>
                <small class="fw-semibold d-block lh-sm" style="color:var(--text)"><?= e(excerpt($r['title'], 64)) ?></small>
                <small class="text-muted"><?= tgl_id($r['published_at']) ?></small>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
