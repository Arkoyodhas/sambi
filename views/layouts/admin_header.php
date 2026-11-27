<?php $authUser = auth_user(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Admin') ?> · Admin <?= e(setting('village_name')) ?></title>
<meta name="robots" content="noindex, nofollow">
<?php $fav = setting('favicon') ?: setting('logo'); if ($fav): ?><link rel="icon" href="<?= e(upload_url($fav)) ?>"><?php endif; ?>
<link href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>" rel="stylesheet">
<link href="<?= asset('vendor/icons/bootstrap-icons.min.css') ?>" rel="stylesheet">
<link href="<?= asset('css/admin.css') ?>" rel="stylesheet">
<style>:root{--c-primary:<?= e(setting_color('primary_color', '#0f6b4e')) ?>;--c-secondary:<?= e(setting_color('secondary_color', '#0b3d2e')) ?>;--c-accent:<?= e(setting_color('accent_color', '#e8b44c')) ?>;--c-button-floating:<?= e(setting_color('button_floating_color', setting_color('primary_color', '#0f6b4e'))) ?>;--c-verify:<?= e(setting_color('verify_color', '#34d399')) ?>;<?php if (setting('verify_logo')): ?>--verify-logo-image:url("<?= e(upload_url(setting('verify_logo'))) ?>");<?php endif; ?>}</style>
<link href="<?= asset('css/admin-teal.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-wrap">
  <aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
      <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" alt="Logo"><?php else: ?><i class="bi bi-buildings"></i><?php endif; ?>
      <div><strong><?= e(setting('village_name')) ?></strong><small>Panel Admin</small></div>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= url('admin/dashboard') ?>" class="<?= active_menu('admin') && str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : '' ?>" data-testid="menu-dashboard"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a href="<?= url('admin/panduan') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'], 'panduan') ? 'active' : '' ?>" data-testid="menu-panduan"><i class="bi bi-book"></i> Panduan Penggunaan</a>
      <?php if (feature_enabled('population') && can('kelola_penduduk')): ?>
      <span class="nav-label">Kependudukan</span>
      <a href="<?= url('admin/penduduk') ?>" data-testid="menu-penduduk"><i class="bi bi-people"></i> Data Penduduk</a>
      <a href="<?= url('admin/keluarga') ?>"><i class="bi bi-house-heart"></i> Data Keluarga</a>
      <a href="<?= url('admin/wilayah') ?>"><i class="bi bi-geo-alt"></i> Data Wilayah</a>
      <?php if (can('import_penduduk')): ?><a href="<?= url('admin/penduduk/import') ?>" data-testid="menu-import"><i class="bi bi-file-earmark-arrow-up"></i> Import Excel</a><?php endif; ?>
      <?php endif; ?>
      <?php if (feature_enabled('news') && can('kelola_berita')): ?>
      <span class="nav-label">Konten</span>
      <a href="<?= url('admin/berita') ?>" data-testid="menu-berita"><i class="bi bi-newspaper"></i> Berita</a>
      <a href="<?= url('admin/kategori-berita') ?>"><i class="bi bi-tags"></i> Kategori Berita</a>
      <a href="<?= url('admin/komentar') ?>"><i class="bi bi-chat-dots"></i> Komentar</a>
      <a href="<?= url('admin/pengumuman') ?>"><i class="bi bi-megaphone"></i> Pengumuman</a>
      <a href="<?= url('admin/agenda') ?>"><i class="bi bi-calendar-event"></i> Agenda</a>
      <?php endif; ?>
      <?php if (feature_enabled('gallery') && can('kelola_galeri')): ?>
      <a href="<?= url('admin/galeri') ?>"><i class="bi bi-images"></i> Galeri</a>
      <a href="<?= url('admin/album') ?>"><i class="bi bi-collection"></i> Album</a>
      <?php endif; ?>
      <?php if ((feature_enabled('services') && can('kelola_layanan')) || (feature_enabled('complaints') && can('kelola_pengaduan'))): ?>
      <span class="nav-label">Pelayanan</span>
      <?php endif; ?>
      <?php if (feature_enabled('services') && can('kelola_layanan')): ?>
      <a href="<?= url('admin/permohonan') ?>" data-testid="menu-permohonan"><i class="bi bi-inbox"></i> Permohonan Masuk</a>
      <a href="<?= url('admin/layanan') ?>"><i class="bi bi-file-earmark-text"></i> Jenis Layanan</a>
      <?php endif; ?>
      <?php if (feature_enabled('complaints') && can('kelola_pengaduan')): ?>
      <a href="<?= url('admin/pengaduan') ?>" data-testid="menu-pengaduan"><i class="bi bi-chat-square-text"></i> Pengaduan</a>
      <a href="<?= url('admin/pesan') ?>"><i class="bi bi-envelope"></i> Pesan Kontak</a>
      <?php endif; ?>
      <?php if ((feature_enabled('transparency') && can('kelola_transparansi')) || (feature_enabled('documents') && can('kelola_dokumen'))): ?>
      <span class="nav-label">Transparansi</span>
      <?php endif; ?>
      <?php if (feature_enabled('transparency') && can('kelola_transparansi')): ?>
      <a href="<?= url('admin/transparansi') ?>"><i class="bi bi-cash-stack"></i> APBDes</a>
      <a href="<?= url('admin/pembangunan') ?>"><i class="bi bi-cone-striped"></i> Pembangunan</a>
      <?php endif; ?>
      <?php if (feature_enabled('documents') && can('kelola_dokumen')): ?>
      <a href="<?= url('admin/dokumen') ?>"><i class="bi bi-folder2-open"></i> Dokumen Publik</a>
      <?php endif; ?>
      <?php if (feature_enabled('village_profile') && can('kelola_profil')): ?>
      <span class="nav-label">Profil Desa</span>
      <a href="<?= url('admin/profil-desa') ?>"><i class="bi bi-info-circle"></i> Profil Desa</a>
      <a href="<?= url('admin/perangkat') ?>" data-testid="menu-perangkat"><i class="bi bi-person-badge"></i> Perangkat Desa</a>
      <a href="<?= url('admin/sotk') ?>" data-testid="menu-sotk"><i class="bi bi-diagram-3"></i> Struktur (SOTK)</a>
      <a href="<?= url('admin/sdgs') ?>" data-testid="menu-sdgs"><i class="bi bi-globe-asia-australia"></i> SDGs Desa</a>
      <?php endif; ?>
      <?php if (feature_enabled('economy') && can('kelola_profil')): ?>
      <span class="nav-label">Ekonomi Desa</span>
      <a href="<?= url('admin/umkm') ?>"><i class="bi bi-shop"></i> UMKM</a>
      <a href="<?= url('admin/potensi') ?>"><i class="bi bi-tree"></i> Potensi Desa</a>
      <?php endif; ?>
      <?php if (feature_enabled('public_data') && can('kelola_konten')): ?>
      <span class="nav-label">Data Publik</span>
      <a href="<?= url('admin/idm') ?>" data-testid="menu-idm"><i class="bi bi-graph-up"></i> IDM (Indeks Desa)</a>
      <a href="<?= url('admin/bansos') ?>" data-testid="menu-bansos"><i class="bi bi-people-fill"></i> Bansos</a>
      <a href="<?= url('admin/stunting') ?>" data-testid="menu-stunting"><i class="bi bi-heart-pulse"></i> Data Stunting</a>
      <?php endif; ?>
      <?php if (feature_enabled('music_event') && (can('kelola_musik') || can('kelola_event'))): ?>
      <span class="nav-label">Pengalaman Website</span>
      <?php endif; ?>
      <?php if (feature_enabled('music_event') && can('kelola_musik')): ?><a href="<?= url('admin/musik') ?>" data-testid="menu-musik"><i class="bi bi-music-note-beamed"></i> Musik Website</a><?php endif; ?>
      <?php if (feature_enabled('music_event') && can('kelola_event')): ?><a href="<?= url('admin/event') ?>" data-testid="menu-event"><i class="bi bi-stars"></i> Event Experience</a><?php endif; ?>
      <?php if (can('kelola_tampilan') || Auth::isSuperAdmin() || can('lihat_audit') || can('kelola_konten') || can('balas_chat') || can('lihat_feedback') || can('lihat_kunjungan') || can('kelola_hiburan') || can('kelola_kontak_darurat')): ?>
      <span class="nav-label">Sistem</span>
      <?php endif; ?>
      <?php if (can('kelola_tampilan')): ?>
      <a href="<?= url('admin/pengaturan') ?>" data-testid="menu-pengaturan"><i class="bi bi-sliders"></i> Pengaturan Website</a>
      <a href="<?= url('admin/menu') ?>"><i class="bi bi-list-nested"></i> Menu Navigasi</a>
      <a href="<?= url('admin/faq') ?>" data-testid="menu-faq"><i class="bi bi-question-circle"></i> FAQ Beranda</a>
      <?php if (feature_enabled('static_pages')): ?><a href="<?= url('admin/halaman') ?>"><i class="bi bi-file-earmark-richtext"></i> Halaman Statis</a><?php endif; ?>
      <?php if (feature_enabled('push_pwa')): ?><a href="<?= url('admin/push') ?>" data-testid="menu-push"><i class="bi bi-bell"></i> Notifikasi Push</a><?php endif; ?>
      <?php endif; ?>
      <?php if (can('kelola_kontak_darurat')): ?><a href="<?= url('admin/kontak-darurat') ?>" data-testid="menu-kontak-darurat"><i class="bi bi-telephone-plus"></i> Kontak Darurat</a><?php endif; ?>
      <?php if (feature_enabled('entertainment') && can('kelola_hiburan')): ?><a href="<?= url('admin/hiburan') ?>" data-testid="menu-hiburan-admin"><i class="bi bi-controller"></i> Zona Hiburan</a><?php endif; ?>
      <?php if (feature_enabled('live_chat') && can('balas_chat')): ?><a href="<?= url('admin/chat') ?>" data-testid="menu-chat"><i class="bi bi-chat-dots"></i> Live Chat <span class="chat-badge" id="chatSidebarBadge" hidden></span></a><?php endif; ?>
      <?php if (Auth::isSuperAdmin()): ?>
      <a href="<?= url('admin/users') ?>" data-testid="menu-users"><i class="bi bi-person-gear"></i> User Admin</a>
      <a href="<?= url('admin/roles') ?>" data-testid="menu-roles"><i class="bi bi-shield-lock"></i> Role & Hak Akses</a>
      <a href="<?= url('admin/sistem') ?>" data-testid="menu-sistem"><i class="bi bi-cone-striped"></i> Maintenance & Backup</a>
      <?php endif; ?>
      <?php if (feature_enabled('visitor_audit') && can('lihat_kunjungan')): ?>
      <a href="<?= url('admin/kunjungan') ?>" data-testid="menu-kunjungan"><i class="bi bi-graph-up-arrow"></i> Statistik Kunjungan</a>
      <a href="<?= url('admin/screenshot-log') ?>" data-testid="menu-screenshot-log"><i class="bi bi-shield-exclamation"></i> Upaya Screenshot</a>
      <?php endif; ?>
      <?php if (feature_enabled('visitor_audit') && can('lihat_audit')): ?>
      <a href="<?= url('admin/audit') ?>"><i class="bi bi-clipboard-data"></i> Audit Log</a>
      <?php endif; ?>
      <?php if (feature_enabled('feedback') && can('lihat_feedback')): ?>
      <a href="<?= url('admin/feedback') ?>" data-testid="menu-feedback"><i class="bi bi-chat-square-text"></i> Feedback Pengunjung</a>
      <?php endif; ?>
      <?php if (can('kelola_konten')): ?>
      <?php if (feature_enabled('push_pwa')): ?><a href="<?= url('admin/pwa') ?>" data-testid="menu-pwa"><i class="bi bi-phone"></i> Pengaturan PWA</a><?php endif; ?>
      <a href="<?= url('admin/qr') ?>" data-testid="menu-qr"><i class="bi bi-qr-code"></i> Generator QR Code</a>
      <?php endif; ?>
      <?php if (Auth::isSuperAdmin() && is_file(BASE_PATH . '/controllers/admin/CloningController.php')): ?>
      <a href="<?= url('admin/cloning') ?>" data-testid="menu-cloning"><i class="bi bi-copy"></i> Cloning Website</a>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <div class="admin-main">
    <header class="admin-topbar">
      <button class="btn btn-light d-lg-none" id="sidebarToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
      <h6 class="mb-0 flex-grow-1 text-truncate"><?= e($pageTitle ?? '') ?></h6>
      <a href="<?= url('') ?>" target="_blank" class="btn btn-sm btn-outline-secondary" data-testid="view-site-btn"><i class="bi bi-box-arrow-up-right me-1"></i><span class="d-none d-md-inline">Lihat Website</span></a>
      <div class="dropdown">
        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" data-testid="user-dropdown">
          <span class="user-avatar"><?= strtoupper(substr($authUser['name'] ?? 'A', 0, 1)) ?></span>
          <span class="d-none d-md-inline"><?= e($authUser['name'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
          <li><span class="dropdown-item-text small text-muted"><?= e($authUser['role_name'] ?? '') ?></span></li>
          <li><a class="dropdown-item" href="<?= url('admin/akun') ?>"><i class="bi bi-person me-2"></i>Akun Saya</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item text-danger" href="<?= url('admin/logout') ?>" data-testid="logout-btn"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
        </ul>
      </div>
    </header>
    <main class="admin-content">
      <?php $adminFlash = flash(); if ($adminFlash): ?>
      <div class="alert alert-<?= e($adminFlash['type']) ?> alert-dismissible fade show" role="alert" data-testid="admin-flash"><?= $adminFlash['msg'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['form_errors'])): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0"><?php foreach ($_SESSION['form_errors'] as $err): ?><li><?= e($err) ?></li><?php endforeach; unset($_SESSION['form_errors']); ?></ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
      <?php endif; ?>
