<?php
$footerMenus = cached_menus('footer');
$footerMenus = array_values(array_filter($footerMenus, fn(array $menu): bool => feature_public_path_enabled((string) ($menu['url'] ?? ''))));
?>
</main>

<?php if (!empty($faqs)): ?>
<section class="tl-section tl-faq" id="faq" data-testid="home-faq">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-4">
        <div class="tl-head">
          <span class="tl-eyebrow">FAQ</span>
          <h2 data-split>Pertanyaan yang sering diajukan</h2>
          <p>Belum menemukan jawaban? Hubungi kami lewat Live Chat, WhatsApp, atau kirim pengaduan.</p>
        </div>
        <div class="d-flex flex-wrap gap-2" data-reveal>
          <?php if (feature_enabled('complaints')): ?><a href="<?= url('pengaduan') ?>" class="btn tl-btn-ghost" data-testid="faq-pengaduan"><i class="bi bi-chat-square-text"></i> Kirim Pengaduan</a><?php endif; ?>
          <?php if (feature_enabled('contact')): ?><a href="<?= url('kontak') ?>" class="btn tl-btn-ghost" data-testid="faq-kontak"><i class="bi bi-telephone"></i> Kontak</a><?php endif; ?>
        </div>
      </div>
      <div class="col-lg-8">
        <div class="tl-accordion" data-reveal-group>
          <?php foreach ($faqs as $i => $fq): ?>
          <div class="tl-acc-item" data-testid="faq-item-<?= (int) $fq['id'] ?>">
            <button type="button" class="tl-acc-btn" aria-expanded="false" aria-controls="faqA<?= (int) $fq['id'] ?>" id="faqQ<?= (int) $fq['id'] ?>" data-testid="faq-toggle-<?= (int) $fq['id'] ?>">
              <span><?= e($fq['question']) ?></span><i class="tl-acc-icon" aria-hidden="true"></i>
            </button>
            <div class="tl-acc-panel" id="faqA<?= (int) $fq['id'] ?>" role="region" aria-labelledby="faqQ<?= (int) $fq['id'] ?>" hidden>
              <div class="tl-acc-inner"><?= nl2br(e($fq['answer'])) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<footer class="site-footer tl-footer" data-testid="site-footer">
  <div class="container">
    <?php if (feature_enabled('services')): ?>
    <div class="tl-footer-cta" data-reveal data-testid="footer-cta">
      <div class="tl-cta-copy">
        <span class="tl-badge-amber">Gratis · 1 Hari Kerja</span>
        <h3>Urus surat desa tanpa antre.</h3>
        <p>Ajukan dari rumah, pantau statusnya dengan nomor tiket.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="<?= url('layanan') ?>" class="btn tl-cta tl-cta-lg" data-testid="footer-ajukan-surat"><i class="bi bi-send-fill"></i> Ajukan Surat</a>
          <a href="<?= url('layanan/cek') ?>" class="btn tl-btn-light" data-testid="footer-cek-status"><i class="bi bi-search"></i> Cek Status</a>
        </div>
      </div>
      <div class="tl-cta-scene" aria-hidden="true" data-testid="footer-cta-scene">
        <svg viewBox="0 0 380 170" role="presentation">
          <g class="w-cloud w-cloud-1"><ellipse cx="60" cy="34" rx="26" ry="9"/><ellipse cx="78" cy="28" rx="16" ry="10"/></g>
          <g class="w-cloud w-cloud-2"><ellipse cx="230" cy="22" rx="22" ry="8"/><ellipse cx="244" cy="17" rx="13" ry="8"/></g>
          <path class="w-hill" d="M0 150 C 70 112, 130 120, 190 138 S 310 108, 380 128 L380 170 L0 170 Z"/>
          <g class="w-tree" transform="translate(28 112)"><rect x="-2" y="10" width="4" height="22" rx="2"/><circle cx="0" cy="4" r="13"/></g>
          <g class="w-tree" transform="translate(196 118)"><rect x="-2" y="8" width="4" height="18" rx="2"/><circle cx="0" cy="2" r="10"/></g>
          <g class="w-office" transform="translate(300 86)">
            <path class="w-roof" d="M-48 22 L-22 2 L22 2 L48 22 Z"/>
            <path class="w-roof-top" d="M-20 4 L-8 -12 L8 -12 L20 4 Z"/>
            <rect class="w-wall" x="-38" y="22" width="76" height="40" rx="3"/>
            <rect class="w-door" x="-9" y="34" width="18" height="28" rx="2"/>
            <rect class="w-win" x="-31" y="32" width="14" height="11" rx="2"/>
            <rect class="w-win" x="17" y="32" width="14" height="11" rx="2"/>
            <rect class="w-sign" x="-22" y="24" width="44" height="7" rx="2"/>
          </g>
          <line class="w-ground" x1="0" y1="149" x2="380" y2="149"/>
          <g class="w-walker-path">
            <g class="w-walker" transform="translate(40 148)">
              <g class="w-bob">
                <line class="w-leg w-leg-b" x1="0" y1="-26" x2="0" y2="-1"/>
                <line class="w-arm w-arm-b" x1="0" y1="-46" x2="0" y2="-28"/>
                <rect class="w-body" x="-7" y="-50" width="14" height="26" rx="6"/>
                <line class="w-leg w-leg-f" x1="0" y1="-26" x2="0" y2="-1"/>
                <circle class="w-head" cx="0" cy="-58" r="7"/>
                <rect class="w-peci" x="-6.5" y="-67" width="13" height="5" rx="1.5"/>
                <g class="w-arm-f-g">
                  <line class="w-arm w-arm-f" x1="0" y1="-46" x2="0" y2="-28"/>
                </g>
                <rect class="w-letter" x="6" y="-38" width="13" height="9" rx="1.5"/>
              </g>
            </g>
          </g>
          <g class="w-bubble" transform="translate(262 70)">
            <rect x="-26" y="-14" width="52" height="22" rx="11"/>
            <path d="M-10 -3 l6 6 l12 -12" class="w-check"/>
          </g>
        </svg>
      </div>
    </div>
    <?php endif; ?>
    <div class="row g-4 tl-footer-main">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting("logo"))) ?>" alt="Logo" style="height:44px" width="44" height="44" loading="lazy" decoding="async"><?php endif; ?>
          <div>
            <div class="h5 mb-0 footer-title"><?= e(setting('village_name')) ?></div>
            <small class="tl-footer-muted"><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></small>
          </div>
        </div>
        <p class="small tl-footer-muted"><?= e(setting('village_description')) ?></p>
        <div class="d-flex gap-2 social-links">
          <?php foreach (['facebook' => 'facebook', 'instagram' => 'instagram', 'youtube' => 'youtube', 'twitter' => 'twitter-x', 'tiktok' => 'tiktok'] as $key => $icon): if (setting($key)): ?>
          <a href="<?= e(setting($key)) ?>" target="_blank" rel="noopener" aria-label="<?= e($key) ?>" data-testid="footer-social-<?= e($key) ?>"><i class="bi bi-<?= e($icon) ?>"></i></a>
          <?php endif; endforeach; ?>
        </div>
      </div>
      <div class="col-lg-2 col-6">
        <div class="footer-heading">Tautan</div>
        <ul class="footer-links">
          <?php foreach ($footerMenus as $fm): ?>
          <li><a href="<?= e(str_starts_with($fm['url'], 'http') ? $fm['url'] : url($fm['url'])) ?>"><?= e($fm['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-3 col-6">
        <div class="footer-heading">Kontak</div>
        <ul class="footer-links footer-contact">
          <li><i class="bi bi-geo-alt"></i> <span><?= e(setting('address')) ?></span></li>
          <li><i class="bi bi-telephone"></i> <span><?= e(setting('phone')) ?></span></li>
          <li><i class="bi bi-envelope"></i> <span><?= e(setting('email')) ?></span></li>
          <li><i class="bi bi-clock"></i> <span><?= e(setting('jam_pelayanan')) ?></span></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <div class="footer-heading">Layanan Cepat</div>
        <div class="d-grid gap-2">
          <?php if (feature_enabled('services')): ?>
          <a href="<?= url('layanan') ?>" class="btn btn-sm btn-footer-ghost" data-testid="footer-ajukan-layanan"><i class="bi bi-file-earmark-text me-1"></i> Ajukan Layanan</a>
          <?php endif; ?>
          <?php if (feature_enabled('complaints')): ?>
          <a href="<?= url('pengaduan') ?>" class="btn btn-sm btn-footer-ghost" data-testid="footer-pengaduan"><i class="bi bi-chat-square-text me-1"></i> Kirim Pengaduan</a>
          <?php endif; ?>
          <?php if (feature_enabled('contact')): ?>
          <a href="<?= url('kontak') ?>" class="btn btn-sm btn-footer-ghost" data-testid="footer-kontak"><i class="bi bi-geo-alt me-1"></i> Lokasi & Kontak</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="footer-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
      <small>&copy; <?= date('Y') ?> Pemerintah <?= e(setting('village_name')) ?>. Hak cipta dilindungi.</small>
 <small class="tl-footer-muted">Website Resmi Sistem Informasi Desa | IT Dev <a href="https://instagram.com/arkoyodha">Arko</a>. </small>
    </div>
  </div>
</footer>

<?php
$chatFeatureOn = feature_enabled('live_chat');
$waNum = $chatFeatureOn ? preg_replace('/\D/', '', setting('whatsapp')) : '';
$lcOn = $chatFeatureOn && setting('livechat_enabled', '1') === '1';
if ($chatFeatureOn && ($waNum || $lcOn)):
?>
<div class="livechat" data-testid="livechat">
  <button class="lc-launcher" id="lcLauncher" aria-label="Bantuan &amp; Chat" aria-controls="lcPopup" aria-expanded="false" data-testid="livechat-launcher"><i class="bi bi-chat-dots-fill"></i><span class="lc-unread-badge" id="lcBadge" hidden>0</span></button>
  <div class="lc-popup" id="lcPopup" role="dialog" aria-modal="true" aria-labelledby="lcPopupTitle" hidden>
    <div class="lc-head">
      <div class="lc-head-id"><span class="lc-avatar" aria-hidden="true"><i class="bi bi-buildings"></i></span><div><strong id="lcPopupTitle">Butuh Bantuan?</strong><span>Admin desa biasanya membalas di jam kerja</span></div></div>
      <button class="lc-x" id="lcClose" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="lc-body" id="lcBody">
      <?php if ($lcOn): ?>
      <div class="lc-intro" id="lcIntro">
        <div class="lc-welcome">
          <!--<div class="lc-welcome-icon"><i class="bi bi-chat-heart-fill"></i></div>-->
          <!--<span class="lc-kicker">Layanan Live Chat</span>-->
          <h3>Halo, selamat datang.</h3>
          <p class="lc-p">Tuliskan nama Anda untuk memulai percakapan langsung dengan admin desa.</p>
          <form id="lcStart" novalidate>
            <label class="lc-name-label" for="lcName">Nama lengkap atau nama panggilan</label>
            <div class="lc-name-field">
              <i class="bi bi-person"></i>
              <input type="text" id="lcName" class="form-control" placeholder="Contoh: Budi" maxlength="60" minlength="2" required autocomplete="name" enterkeyhint="go" aria-describedby="lcStartHint lcStartError" data-testid="livechat-name">
            </div>
            <div class="lc-start-error" id="lcStartError" hidden role="alert"><i class="bi bi-exclamation-circle-fill"></i><span></span></div>
            <button class="btn btn-primary w-100 mt-3 lc-start-btn" id="lcStartBtn" type="submit" data-testid="livechat-start">
              <span class="lc-normal"><i class="bi bi-chat-text me-1"></i> Mulai Live Chat</span>
              <span class="lc-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Menghubungkan…</span>
            </button>
            <div class="lc-trust" id="lcStartHint"><i class="bi bi-shield-check"></i><span>Nama hanya digunakan untuk percakapan ini dan tidak ditampilkan kepada pengunjung lain.</span></div>
          </form>
        </div>
      </div>
      <div class="lc-room" id="lcRoom" hidden>
        <div class="lc-msgs" id="lcMsgs"></div>
        <form class="lc-input" id="lcSendForm">
          <input type="text" id="lcMsg" class="form-control" placeholder="Ketik pesan..." autocomplete="off" maxlength="1000" data-testid="livechat-msg">
          <button class="btn btn-primary" data-testid="livechat-send"><i class="bi bi-send"></i></button>
        </form>
      </div>
      <?php endif; ?>
      <?php if ($waNum): ?>
      <a class="lc-wa" href="https://wa.me/<?= e($waNum) ?>?text=<?= urlencode('Halo, saya warga ' . setting('village_name')) ?>" target="_blank" rel="noopener" data-testid="livechat-wa"><i class="bi bi-whatsapp"></i> Chat via WhatsApp</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
<button class="back-to-top" id="backToTop" aria-label="Kembali ke atas"><i class="bi bi-arrow-up"></i></button>

<?php
$fbShow = feedback_available();
if ($fbShow):
$fbQ = Cache::remember(
    'feedback-questions:v' . Cache::version('public'),
    300,
    static fn(): array => db_all("SELECT id, label, type, options, required FROM feedback_questions WHERE is_active=1 ORDER BY sort_order ASC, id ASC")
); ?>
<button type="button" class="fb-launcher" id="fbLauncher" aria-label="Beri Masukan" data-testid="feedback-launcher"><i class="bi bi-chat-square-text"></i></button>
<div class="fb-modal" id="fbModal" hidden data-testid="feedback-modal">
  <div class="fb-backdrop" id="fbBackdrop"></div>
  <div class="fb-dialog">
    <button type="button" class="fb-close" id="fbClose" aria-label="Tutup" data-testid="feedback-close">&times;</button>
    <div class="fb-header">
      <span class="fb-kicker">Survei Warga</span>
      <div class="h5 mb-1">Bantu kami menjadi lebih baik.</div>
      <p class="small mb-0 opacity-75"><?= e(setting('feedback_intro', 'Bantu kami memperbaiki layanan desa dengan mengisi survei singkat berikut. Terima kasih!')) ?></p>
    </div>
    <form id="fbForm" class="fb-body">
      <input type="text" id="fbName" class="form-control form-control-sm mb-2" placeholder="Nama (opsional)" maxlength="120" data-testid="feedback-name">
      <?php foreach ($fbQ as $q): ?>
      <div class="fb-q mb-3" data-testid="feedback-question">
        <label class="form-label small"><?= e($q['label']) ?><?= $q['required']?' <span class="text-danger">*</span>':'' ?></label>
        <?php if ($q['type'] === 'rating'): ?>
          <div class="fb-rating" data-q="<?= (int)$q['id'] ?>">
            <?php for ($i=1;$i<=5;$i++): ?><button type="button" data-v="<?= $i ?>" class="fb-star"><i class="bi bi-star"></i></button><?php endfor; ?>
          </div>
        <?php elseif ($q['type'] === 'choice'): $opts = json_decode($q['options'] ?: '[]', true) ?: []; ?>
          <select class="form-select form-select-sm" data-q="<?= (int)$q['id'] ?>" <?= $q['required']?'required':'' ?>>
            <option value="">-- pilih --</option>
            <?php foreach ($opts as $o): ?><option value="<?= e($o) ?>"><?= e($o) ?></option><?php endforeach; ?>
          </select>
        <?php elseif ($q['type'] === 'textarea'): ?>
          <textarea class="form-control form-control-sm" data-q="<?= (int)$q['id'] ?>" rows="3" maxlength="500" <?= $q['required']?'required':'' ?>></textarea>
        <?php else: ?>
          <input type="text" class="form-control form-control-sm" data-q="<?= (int)$q['id'] ?>" maxlength="200" <?= $q['required']?'required':'' ?>>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <button class="btn btn-primary w-100" type="submit" data-testid="feedback-submit"><i class="bi bi-send me-1"></i> Kirim Masukan</button>
      <div class="fb-ok" id="fbOk" hidden><i class="bi bi-check-circle-fill"></i> Terima kasih, masukan Anda sudah kami terima!</div>
    </form>
  </div>
</div>
<?php endif; ?>

<div id="music-player"></div>
<div id="effect-layer"></div>

<script>
window.DESA = {
  lazyWidgets: true,
  baseUrl: <?= json_encode(base_url()) ?>,
  page: <?= json_encode(($isHome ?? false) ? 'beranda' : 'lainnya') ?>,
  villageName: <?= json_encode(setting('village_name', 'Desa')) ?>,
  photoProtection: <?= json_encode(setting('photo_protection', '1') === '1') ?>,
  screenshotProtection: <?= json_encode(feature_enabled('visitor_audit') && setting('screenshot_protection') === '1') ?>,
  screenshotMode: <?= json_encode(setting('screenshot_mode', 'ketat')) ?>,
  feedback: {
    enabled: <?= json_encode(feature_enabled('feedback') && setting('feedback_enabled', '1') === '1') ?>,
    delay: <?= json_encode((int) setting('feedback_delay_seconds', '25')) ?>
  },
  liveChat: {
    enabled: <?= json_encode(feature_enabled('live_chat') && setting('livechat_enabled', '1') === '1') ?>,
    visitorTokenSeed: <?= json_encode(bin2hex(random_bytes(32))) ?>
  },
  entertainment: {
    enabled: <?= json_encode(feature_enabled('entertainment') && setting('entertainment_enabled', '1') === '1') ?>,
    page: <?= json_encode(!empty($entertainmentPage)) ?>,
    loggedIn: <?= json_encode(!empty($_SESSION['user_id'])) ?>
  },
  music: {
    enabled: <?= json_encode(feature_enabled('music_event') && setting('music_enabled', '1') === '1') ?>,
    autoplay: <?= json_encode(setting('music_autoplay', '1') === '1') ?>,
    buttonVisible: <?= json_encode(setting('music_button_visible', '1') === '1') ?>,
    url: <?= json_encode(setting('music_url', '')) ?>
  },
  webnotif: {
    enabled: <?= json_encode(feature_enabled('push_pwa') && setting('webnotif_enabled', '1') === '1') ?>,
    popup: <?= json_encode(setting('webnotif_popup', '1') === '1') ?>,
    popupImportant: <?= json_encode(setting('webnotif_popup_important', '1') === '1') ?>
  },
  pwaEnabled: <?= json_encode(feature_enabled('push_pwa')) ?>,
  assetVer: <?= json_encode(setting('asset_version', '1') ?: '1') ?>,
  bgfxDensity: <?= json_encode(setting('bgfx_density', 'sedang')) ?>,
  vapidKey: <?= json_encode(WebPush::publicKey()) ?>
};
</script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>" defer></script>
<script src="<?= asset('vendor/gsap/gsap.min.js') ?>" defer></script>
<script src="<?= asset('vendor/gsap/ScrollTrigger.min.js') ?>" defer></script>
<script src="<?= asset('vendor/gsap/Flip.min.js') ?>" defer></script>
<script src="<?= asset('vendor/lenis/lenis.min.js') ?>" defer></script>
<?php if (!empty($needsLightbox)): ?><script src="<?= asset('vendor/glightbox/glightbox.min.js') ?>" defer></script><?php endif; ?>
<?php if (!empty($needsChart)): ?>
<script src="<?= asset('vendor/chart/chart.umd.min.js') ?>" defer></script>
<?php endif; ?>
<?php if (!empty($needsMap)): ?>
<script>window.DESA_LEAFLET = <?= json_encode(['css' => asset('vendor/leaflet/leaflet.css'), 'js' => asset('vendor/leaflet/leaflet.js')]) ?>;</script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>" defer></script>
<script src="<?= asset('js/motion.js') ?>" defer></script>
<?php if (!empty($isHome)): ?><script src="<?= asset('js/smart-search.js') ?>" defer></script><?php endif; ?>
<?php if (!empty($isHome)): ?><script>window.DESA_DIORAMA = <?= json_encode(asset('vendor/three/three.lite.min.js')) ?>;</script><script src="<?= asset('js/diorama.js') ?>" defer></script><?php endif; ?>
<?php if (!empty($entertainmentPage) && feature_enabled('entertainment') && setting('entertainment_enabled', '1') === '1'): ?><script src="<?= asset('js/entertainment-tracker.js') ?>" defer></script><?php endif; ?>
<?php foreach (($extraJs ?? []) as $jsFile): ?><script src="<?= asset($jsFile) ?>" defer></script><?php endforeach; ?>
<?php if (isset($inlineScript)) echo $inlineScript; ?>
</body>
</html>