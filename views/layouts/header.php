<?php
$menus = cached_menus('main');
$menus = array_values(array_filter($menus, fn(array $menu): bool => feature_public_path_enabled((string) ($menu['url'] ?? ''))));
$menuTree = [];
foreach ($menus as $m) { if (!$m['parent_id']) { $m['children'] = []; $menuTree[$m['id']] = $m; } }
foreach ($menus as $m) { if ($m['parent_id'] && isset($menuTree[$m['parent_id']])) $menuTree[$m['parent_id']]['children'][] = $m; }
$logo = setting('logo');
$fontHeading = setting('font_heading', 'Bricolage Grotesque');
$fontBody = setting('font_body', 'Plus Jakarta Sans');
$isHome = $isHome ?? false;
$hasFeedback = feedback_available();
$hasEntertainmentMenu = false;
foreach ($menus as $menuCheck) {
  if (trim((string) ($menuCheck['url'] ?? ''), '/') === 'hiburan') { $hasEntertainmentMenu = true; break; }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? setting('village_name')) ?></title>
<meta name="description" content="<?= e($metaDescription ?? setting('meta_description', setting('village_description'))) ?>">
<meta name="robots" content="<?= e($metaRobots ?? 'index, follow') ?>">
<meta property="og:title" content="<?= e($pageTitle ?? setting('village_name')) ?>">
<meta property="og:description" content="<?= e($metaDescription ?? setting('meta_description')) ?>">
<meta property="og:type" content="website">
<?php $og = $ogImage ?? (setting('og_image') ? upload_url(setting('og_image')) : ''); if ($og): ?>
<meta property="og:image" content="<?= e($og) ?>">
<?php endif; ?>
<?php $fav = setting('favicon') ?: setting('logo'); if ($fav): ?><link rel="icon" href="<?= e(upload_url($fav)) ?>"><link rel="apple-touch-icon" href="<?= e(upload_url($fav)) ?>"><?php else: ?><link rel="apple-touch-icon" href="<?= asset('img/pwa-192.png') ?>"><?php endif; ?>
<?php if (feature_enabled('push_pwa')): ?><link rel="manifest" href="<?= url('manifest.webmanifest') ?>"><?php endif; ?>
<meta name="theme-color" content="#00B589">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?= e(setting('village_name')) ?>">
<!-- Font ikon lokal: preload agar ikon langsung tampil tanpa "kotak-kotak" -->
<link rel="preload" href="<?= asset('vendor/icons/fonts/bootstrap-icons.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
<link rel="preload" href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>" as="style">
<link href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet" media="print" onload="this.media='all'">
<link rel="preload" href="<?= asset('vendor/fonts/pjs-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
<?php foreach (($extraCss ?? []) as $cssFile): ?><link href="<?= asset($cssFile) ?>" rel="stylesheet"><?php endforeach; ?>
<!-- CSS non-kritis dimuat asinkron agar tidak menghambat render pertama -->
<?php if (!empty($needsLightbox)): ?><link href="<?= asset('vendor/glightbox/glightbox.min.css') ?>" rel="stylesheet" media="print" onload="this.media='all'"><?php endif; ?>
<noscript>
<link href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
<?php if (!empty($needsLightbox)): ?><link href="<?= asset('vendor/glightbox/glightbox.min.css') ?>" rel="stylesheet"><?php endif; ?>
</noscript>
<style>
:root{
  --c-primary:<?= e(setting_color('primary_color', '#0f6b4e')) ?>;
  --c-secondary:<?= e(setting_color('secondary_color', '#0b3d2e')) ?>;
  --c-accent:<?= e(setting_color('accent_color', '#e8b44c')) ?>;
  --c-button-floating:<?= e(setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e'))) ?>;
  --c-btn-hero-primary:<?= e(setting_color('btn_hero_primary_color', setting_color('primary_color', '#0f6b4e'))) ?>;
  --c-btn-section-cta:<?= e(setting_color('btn_section_cta_color', setting_color('primary_color', '#0f6b4e'))) ?>;
  --c-btn-livechat:<?= e(setting_color('btn_livechat_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-btn-feedback:<?= e(setting_color('btn_feedback_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-btn-backtotop:<?= e(setting_color('btn_backtotop_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-btn-siaga:<?= e(setting_color('btn_siaga_color', '#DC2626')) ?>;
  --c-btn-bell:<?= e(setting_color('btn_bell_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-btn-notif-toast:<?= e(setting_color('btn_notif_toast_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-btn-install:<?= e(setting_color('btn_install_color', setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e')))) ?>;
  --c-verify:<?= e(setting_color('verify_color', '#34d399')) ?>;
  <?php if (setting('verify_logo')): ?>--verify-logo-image:url("<?= e(upload_url(setting('verify_logo'))) ?>");<?php endif; ?>
  --radius:<?= (int) setting('card_radius', '18') ?>px;
  --font-heading:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Arial,sans-serif;
  --font-body:ui-sans-serif,system-ui,-apple-system,"Segoe UI",Arial,sans-serif;
}
</style>
<style><?= @file_get_contents(BASE_PATH . '/assets/vendor/lenis/lenis.css') ?: '' ?></style>
<link href="<?= asset('css/theme-teal.css') ?>" rel="stylesheet">
<?php if ($isHome): ?>
<link rel="preload" as="image" href="<?= e(hero_layer_url('hero_diorama_image', 'diorama.webp')) ?>" fetchpriority="high">
<link href="<?= asset('css/home-banner.css') ?>" rel="stylesheet" media="print" onload="this.media='all'">
<link href="<?= asset('css/smart-search.css') ?>" rel="stylesheet">
<noscript><link href="<?= asset('css/home-banner.css') ?>" rel="stylesheet"></noscript>
<?php endif; ?>
<script>
document.documentElement.classList.add('js');
setTimeout(function(){if(!document.documentElement.classList.contains('motion-ready'))document.documentElement.classList.add('motion-timeout');},5000);
document.documentElement.setAttribute('data-theme','light');
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"GovernmentOrganization","name":<?= json_encode(setting('village_name')) ?>,"address":<?= json_encode(setting('address') . ', ' . setting('kecamatan') . ', ' . setting('kabupaten')) ?>,"telephone":<?= json_encode(setting('phone')) ?>,"email":<?= json_encode(setting('email')) ?>,"url":<?= json_encode(base_url()) ?>}
</script>
</head>
<body class="<?= $isHome ? 'is-home ' : '' ?><?= $hasFeedback ? 'has-feedback' : 'no-feedback' ?> btn-style-<?= e(setting('button_style', 'pill')) ?>">
<!-- Skeleton loading: tampil saat halaman dimuat & saat berpindah halaman, hilang otomatis -->
<div id="pageSkeleton" aria-hidden="true" data-testid="page-skeleton">
  <div class="skel-nav">
    <span class="skel skel-logo"></span>
    <span class="skel skel-line" style="width:110px"></span>
    <span class="skel-navlinks d-none d-lg-flex">
      <span class="skel skel-line" style="width:64px"></span><span class="skel skel-line" style="width:80px"></span><span class="skel skel-line" style="width:70px"></span><span class="skel skel-line" style="width:88px"></span><span class="skel skel-line" style="width:60px"></span>
    </span>
  </div>
  <div class="skel-body container">
    <div class="skel skel-hero"></div>
    <div class="skel-grid">
      <div class="skel skel-card"></div>
      <div class="skel skel-card"></div>
      <div class="skel skel-card d-none d-md-block"></div>
      <div class="skel skel-card d-none d-lg-block"></div>
    </div>
    <div class="skel skel-line mx-auto" style="width:180px;height:12px"></div>
  </div>
</div>
<noscript><style>#pageSkeleton{display:none!important}</style></noscript>
<script>
(function(){var r=function(){document.documentElement.classList.add('page-ready')};
document.addEventListener('DOMContentLoaded',function(){requestAnimationFrame(r)});
window.addEventListener('pageshow',r);setTimeout(r,6000);})();
</script>
<div id="event-banner"></div>

<?php
$navHref = fn(array $m): string => str_starts_with((string) $m['url'], 'http') ? $m['url'] : url($m['url']);
$navTop = []; $navMore = [];
foreach (array_values($menuTree) as $idx => $m) {
  if ($idx < 6) $navTop[] = $m; else $navMore[] = $m;
}
$showEntLink = feature_enabled('entertainment') && !$hasEntertainmentMenu;
?>
<nav class="navbar navbar-expand-lg site-navbar tl-nav <?= !empty($entertainmentPage) ? 'entertainment-navbar' : '' ?>" id="siteNavbar" data-testid="site-navbar">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('') ?>" data-testid="nav-brand">
      <?php if ($logo): ?>
        <img src="<?= e(upload_url($logo)) ?>" alt="Logo <?= e(setting("village_name")) ?>" class="nav-logo" width="46" height="46" decoding="async" fetchpriority="high">
      <?php else: ?>
        <span class="nav-logo-fallback"><i class="bi bi-buildings"></i></span>
      <?php endif; ?>
      <span class="brand-text">
        <strong><?= e(setting('village_name')) ?></strong>
      <small><?= e(setting('kecamatan')) ?> &middot; <?= e(setting('kabupaten')) ?></small>
      </span>
    </a>
    <div class="navbar-actions d-flex align-items-center gap-2 order-lg-3">
      <button class="btn btn-icon notif-bell d-none" id="notifBell" type="button" aria-label="Notifikasi desa" data-testid="notif-bell">
        <i class="bi bi-bell"></i>
        <span class="notif-badge" id="notifBadge" hidden>0</span>
      </button>
      <button class="btn btn-icon theme-toggle d-none" id="themeToggle" aria-label="Ganti mode gelap/terang" data-testid="theme-toggle"><i class="bi bi-moon-stars"></i></button>
      <?php if (feature_enabled('services')): ?>
      <a href="<?= url('layanan') ?>" class="btn tl-cta d-none d-sm-inline-flex" data-testid="nav-ajukan-surat"><i class="bi bi-send-fill"></i> Ajukan Surat</a>
      <?php endif; ?>
      <button class="navbar-toggler tl-burger" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainNav" aria-controls="mainNav" aria-label="Menu" data-testid="nav-toggle">
        <span></span><span></span><span></span>
      </button>
    </div>
    <div class="offcanvas-lg offcanvas-end tl-offcanvas order-lg-2" tabindex="-1" id="mainNav" role="navigation" aria-labelledby="mainNavLabel">
      <div class="offcanvas-header d-lg-none">
        <strong id="mainNavLabel" class="tl-off-title"><?= e(setting('village_name')) ?></strong>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#mainNav" aria-label="Tutup" data-testid="nav-close"></button>
      </div>
      <div class="offcanvas-body">
      <ul class="navbar-nav ms-auto align-items-lg-center">
        <?php foreach ($navTop as $m): ?>
          <?php if ($m['children']): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-testid="nav-<?= e(slugify($m['title'])) ?>"><?= e($m['title']) ?></a>
            <ul class="dropdown-menu dropdown-menu-modern tl-dropdown">
              <?php foreach ($m['children'] as $c): ?>
              <li><a class="dropdown-item" href="<?= e($navHref($c)) ?>" target="<?= e($c['target']) ?>"><?php if ($c['icon']): ?><i class="bi <?= e($c['icon']) ?>"></i><?php else: ?><i class="bi bi-dot"></i><?php endif; ?><?= e($c['title']) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </li>
          <?php else: ?>
          <?php $isEntertainmentLink = trim((string) $m['url'], '/') === 'hiburan'; ?>
          <li class="nav-item"><a class="nav-link <?= $isEntertainmentLink ? 'ent-nav-link ' : '' ?><?= active_menu($m['url']) ?>" href="<?= e($navHref($m)) ?>" target="<?= e($m['target']) ?>" data-testid="nav-<?= e(slugify($m['title'])) ?>"><?= e($m['title']) ?></a></li>
          <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($navMore || $showEntLink): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" data-testid="nav-lainnya">Lainnya</a>
            <ul class="dropdown-menu dropdown-menu-modern dropdown-menu-lg-end tl-dropdown">
              <?php foreach ($navMore as $m): ?>
                <?php if ($m['children']): ?>
                  <li><span class="dropdown-header"><?= e($m['title']) ?></span></li>
                  <?php foreach ($m['children'] as $c): ?>
                  <li><a class="dropdown-item" href="<?= e($navHref($c)) ?>" target="<?= e($c['target']) ?>"><i class="bi <?= e($c['icon'] ?: (str_starts_with((string) $c['url'], 'http') ? 'bi-box-arrow-up-right' : 'bi-dot')) ?>"></i><?= e($c['title']) ?></a></li>
                  <?php endforeach; ?>
                <?php else: $isEntertainmentLink = trim((string) $m['url'], '/') === 'hiburan'; ?>
                  <li><a class="dropdown-item <?= $isEntertainmentLink ? 'ent-nav-link ' : '' ?><?= active_menu($m['url']) ?>" href="<?= e($navHref($m)) ?>" target="<?= e($m['target']) ?>" data-testid="nav-<?= e(slugify($m['title'])) ?>"><i class="bi <?= e($m['icon'] ?: ($isEntertainmentLink ? 'bi-controller' : 'bi-dot')) ?>"></i><?= e($m['title']) ?></a></li>
                <?php endif; ?>
              <?php endforeach; ?>
              <?php if ($showEntLink): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item ent-nav-link <?= active_menu('hiburan') ?>" href="<?= url('hiburan') ?>" data-testid="menu-hiburan"><i class="bi bi-controller"></i> Zona Hiburan</a></li>
              <?php endif; ?>
            </ul>
          </li>
        <?php endif; ?>
      </ul>
      <?php if (feature_enabled('services')): ?>
      <a href="<?= url('layanan') ?>" class="btn tl-cta w-100 mt-3 d-lg-none" data-testid="nav-ajukan-surat-mobile"><i class="bi bi-send-fill"></i> Ajukan Surat</a>
      <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<?php $publicFlash = flash(); if ($publicFlash): ?>
<div class="flash-wrap container mt-3">
  <div class="alert alert-<?= e($publicFlash['type']) ?> alert-dismissible fade show shadow-sm" role="alert" data-testid="flash-message">
    <?= $publicFlash['msg'] ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<main>