<?php
$hour = (int) date('G');
$greet = $hour < 4 ? 'Selamat malam' : ($hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam')));
$firstName = explode(' ', trim($userName))[0] ?: 'Admin';
$hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int) date('w')];
$actionable = array_values(array_filter($inbox, static fn($i) => empty($i['informational'])));
$infoTiles = array_values(array_filter($inbox, static fn($i) => !empty($i['informational'])));
$waiting = array_sum(array_map(static fn($i) => (int) $i['pending'], $actionable));
$hotKey = null; $hotMax = 0;
foreach ($actionable as $i) if ((int) $i['pending'] > $hotMax) { $hotMax = (int) $i['pending']; $hotKey = $i['key']; }
$hotItem = null; foreach ($actionable as $i) if ($i['key'] === $hotKey) $hotItem = $i;
$fmt = static fn($n) => number_format((int) $n, 0, ',', '.');
$bulanPendek = ['', 'JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
$feedTypes = [];
foreach ($feed as $f) $feedTypes[$f['type']] = $f['typeLabel'];
?>
<link href="<?= asset('css/dashboard.css') ?>" rel="stylesheet">

<div class="dx" data-testid="dashboard-root">

  <header class="dx-hero dx-rise">
    <div>
      <p class="dx-eyebrow" data-testid="dash-date"><?= e($hari . ', ' . tgl_id(date('Y-m-d'))) ?></p>
      <h1 class="dx-title" data-testid="dash-greeting"><?= e($greet) ?>, <?= e($firstName) ?>.</h1>
      <?php if ($actionable): ?>
        <?php if ($waiting > 0): ?>
        <p class="dx-lede" data-testid="dash-waiting-summary">Ada <strong><?= $fmt($waiting) ?> hal dari warga</strong> yang menunggu tanggapan Anda.</p>
        <?php else: ?>
        <p class="dx-lede" data-testid="dash-waiting-summary">Semua permintaan warga sudah ditanggapi. <span class="dx-calm">Kerja yang rapi.</span></p>
        <?php endif; ?>
      <?php elseif ($inbox): ?>
        <p class="dx-lede">Berikut ringkasan masukan pengunjung dan kegiatan desa hari ini.</p>
      <?php else: ?>
        <p class="dx-lede">Gunakan menu di samping untuk mengelola konten sesuai hak akses Anda.</p>
      <?php endif; ?>
    </div>
    <?php if ($hotItem): ?>
    <a class="dx-cta" href="<?= e($hotItem['url']) ?>" data-testid="dash-primary-action">
      Buka <?= e(mb_strtolower($hotItem['label'])) ?> <i class="bi bi-arrow-right"></i>
    </a>
    <?php endif; ?>
  </header>

  <?php if ($actionable): ?>
  <section class="dx-inbox" style="--n:<?= count($actionable) ?>" aria-label="Kotak masuk warga" data-testid="dash-inbox">
    <?php foreach ($actionable as $n => $i): $p = (int) $i['pending']; $isHot = $i['key'] === $hotKey; $clear = $p === 0 && empty($i['informational']); ?>
    <a href="<?= e($i['url']) ?>" class="dx-tile t-<?= e($i['key']) ?><?= $isHot ? ' is-hot' : '' ?><?= $clear ? ' is-clear' : '' ?> dx-rise" style="--d:<?= 60 + $n * 50 ?>ms" data-testid="dash-inbox-<?= e($i['key']) ?>">
      <div class="dx-tile-top">
        <span class="dx-chip"><i class="bi <?= e($i['icon']) ?>"></i></span>
        <span class="dx-tile-label"><?= e($i['label']) ?></span>
        <?php if ($isHot): ?><span class="dx-pulse" aria-hidden="true"></span><?php endif; ?>
        <i class="bi bi-chevron-right dx-tile-go"></i>
      </div>
      <div class="dx-tile-num" data-testid="dash-inbox-<?= e($i['key']) ?>-count"><?= $fmt($p) ?></div>
      <div class="dx-tile-cap"><?= $clear ? '<i class="bi bi-check2-circle"></i> Semua beres' : e($i['pendingLabel']) ?></div>
      <div class="dx-tile-foot">
        <?php if ($i['progress'] !== null): ?><span><b><?= $fmt($i['progress']) ?></b> <?= e($i['progressLabel']) ?></span><?php endif; ?>
        <?php if (!empty($i['latest']) && !$clear): ?><span class="dx-latest">Terbaru: <?= e(excerpt($i['latest'], 22)) ?></span><?php endif; ?>
      </div>
    </a>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <div class="dx-grid">
    <div class="dx-main">
    <?php if ($inbox): ?>
    <section class="dx-card dx-feed dx-rise" style="--d:260ms" data-testid="dash-feed">
      <div class="dx-card-head">
        <div>
          <h2 class="dx-h2">Terbaru dari warga</h2>
          <p class="dx-sub">Yang belum selesai, diurutkan dari yang paling baru.</p>
        </div>
        <?php if (count($feedTypes) > 1): ?>
        <div class="dx-seg" role="tablist" data-testid="dash-feed-filter">
          <button type="button" class="is-on" data-filter="all" data-testid="dash-feed-filter-all">Semua</button>
          <?php foreach ($feedTypes as $k => $lab): ?>
          <button type="button" data-filter="<?= e($k) ?>" data-testid="dash-feed-filter-<?= e($k) ?>"><?= e($lab) ?></button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <ul class="dx-list">
        <?php foreach ($feed as $n => $f): ?>
        <li class="dx-row t-<?= e($f['type']) ?>" data-type="<?= e($f['type']) ?>">
          <a href="<?= e($f['url']) ?>" data-testid="dash-feed-item-<?= $n ?>">
            <span class="dx-avatar"><?= e(mb_strtoupper(mb_substr(trim((string) $f['name']) ?: '?', 0, 1))) ?></span>
            <span class="dx-row-main">
              <span class="dx-row-top">
                <span class="dx-row-name"><?= e($f['name']) ?></span>
                <span class="dx-tag"><?= e($f['typeLabel']) ?></span>
              </span>
              <span class="dx-row-text"><?= e($f['text']) ?></span>
            </span>
            <span class="dx-row-meta">
              <span class="dx-time"><?= e($f['ago']) ?></span>
              <span class="dx-state s-<?= e(slugify((string) $f['status'])) ?>"><?= e(ucfirst((string) $f['status'])) ?></span>
            </span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <div class="dx-empty<?= $feed ? ' d-none' : '' ?>" data-testid="dash-feed-empty">
        <i class="bi bi-inbox"></i>
        <p>Kotak masuk kosong. Permintaan baru dari warga akan muncul di sini.</p>
      </div>
    </section>
    <?php endif; ?>
  <?php if ($stats): ?>
    <section class="dx-glance dx-rise" style="--d:480ms" data-testid="dash-glance">
      <h2 class="dx-h2">Sekilas desa</h2><p class="dx-sub">Data kependudukan dan konten saat ini.</p>
      <div class="dx-glance-row">
        <?php
        $glance = [];
        if (isset($stats['penduduk'])) {
            $glance[] = ['Penduduk', $stats['penduduk'], 'admin/penduduk'];
            $glance[] = ['Kepala keluarga', $stats['kk'], 'admin/keluarga'];
            $glance[] = ['Laki-laki', $stats['laki'], 'admin/penduduk?jk=L'];
            $glance[] = ['Perempuan', $stats['perempuan'], 'admin/penduduk?jk=P'];
        }
        if (isset($stats['berita'])) $glance[] = ['Berita terbit', $stats['berita'], 'admin/berita'];
        foreach ($glance as [$lab, $val, $link]): ?>
        <a class="dx-stat" href="<?= url($link) ?>" data-testid="dash-stat-<?= slugify($lab) ?>">
          <span class="dx-stat-v"><?= $fmt($val) ?></span>
          <span class="dx-stat-k"><?= e($lab) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php if ($dusunChart): $maxD = max(1, max(array_map('intval', array_column($dusunChart, 'total')))); ?>
      <div class="dx-bars" data-testid="dash-dusun-bars">
        <?php foreach ($dusunChart as $d): ?>
        <div class="dx-bar">
          <span class="dx-bar-k" title="<?= e($d['label']) ?>"><?= e($d['label']) ?></span>
          <span class="dx-bar-track"><span style="width:<?= max(2, round((int) $d['total'] / $maxD * 100)) ?>%"></span></span>
          <span class="dx-bar-v"><?= $fmt($d['total']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>
    </div>

    <aside class="dx-side">
      <?php foreach ($infoTiles as $i): ?>
      <a class="dx-card dx-mini t-<?= e($i['key']) ?> dx-rise" style="--d:300ms" href="<?= e($i['url']) ?>" data-testid="dash-inbox-<?= e($i['key']) ?>">
        <span class="dx-chip"><i class="bi <?= e($i['icon']) ?>"></i></span>
        <span class="dx-mini-main"><span class="dx-mini-k"><?= e($i['label']) ?> pengunjung</span><span class="dx-mini-v" data-testid="dash-inbox-<?= e($i['key']) ?>-count"><?= $fmt($i['pending']) ?> <small><?= e($i['pendingLabel']) ?></small></span></span>
        <i class="bi bi-chevron-right"></i>
      </a>
      <?php endforeach; ?>

      <?php if (!empty($access['news'])): ?>
      <section class="dx-card dx-rise" style="--d:320ms" data-testid="dash-agenda">
        <div class="dx-card-head"><h2 class="dx-h2">Agenda</h2><a class="dx-link" href="<?= url('admin/agenda') ?>">Semua</a></div>
        <?php foreach ($agendas as $a): $ts = strtotime($a['event_date']); ?>
        <div class="dx-agenda">
          <span class="dx-cal"><small><?= $bulanPendek[(int) date('n', $ts)] ?></small><b><?= date('j', $ts) ?></b></span>
          <span>
            <span class="dx-agenda-title"><?= e($a['title']) ?></span>
            <span class="dx-agenda-meta"><?= $a['start_time'] ? e(substr($a['start_time'], 0, 5)) . ' WIB' : 'Sepanjang hari' ?><?= $a['location'] ? ' · ' . e(excerpt($a['location'], 28)) : '' ?></span>
          </span>
        </div>
        <?php endforeach; ?>
        <?php if (!$agendas): ?><p class="dx-muted">Tidak ada agenda terdekat.</p><?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if (!empty($access['music']) || !empty($access['event'])): ?>
      <section class="dx-card dx-rise" style="--d:380ms" data-testid="dash-site-status">
        <div class="dx-card-head"><h2 class="dx-h2">Suasana website</h2></div>
        <?php if (!empty($access['music'])): $on = setting('music_enabled') === '1' && $activeMusic; ?>
        <a class="dx-status" href="<?= url('admin/musik') ?>" data-testid="dash-music-status">
          <span class="dx-dot<?= $on ? ' on' : '' ?>"></span>
          <span><span class="dx-status-k">Musik latar</span><span class="dx-status-v"><?= $on ? e($activeMusic['title']) : 'Tidak diputar' ?></span></span>
          <i class="bi bi-chevron-right"></i>
        </a>
        <?php endif; ?>
        <?php if (!empty($access['event'])): ?>
        <a class="dx-status" href="<?= url('admin/event') ?>" data-testid="dash-event-status">
          <span class="dx-dot<?= $activeEvent ? ' on' : '' ?>"></span>
          <span><span class="dx-status-k">Efek event</span><span class="dx-status-v"><?= $activeEvent ? e($activeEvent['event_name']) : 'Tidak ada yang aktif' ?></span></span>
          <i class="bi bi-chevron-right"></i>
        </a>
        <?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if (!empty($access['audit']) && $recentLogs): ?>
      <section class="dx-card dx-rise" style="--d:440ms" data-testid="dash-team-activity">
        <div class="dx-card-head"><h2 class="dx-h2">Aktivitas tim</h2><a class="dx-link" href="<?= url('admin/audit') ?>">Log</a></div>
        <?php foreach ($recentLogs as $l): ?>
        <div class="dx-log">
          <span class="dx-log-who"><?= e($l['user_name'] ?: 'Sistem') ?></span>
          <span class="dx-log-what"><?= e(excerpt($l['description'], 70)) ?></span>
          <span class="dx-log-when"><?= date('d/m H:i', strtotime($l['created_at'])) ?></span>
        </div>
        <?php endforeach; ?>
      </section>
      <?php endif; ?>
    </aside>
  </div>

</div>

<?php if (!empty($needFaceSetup)): ?>
<div id="faceSetupPrompt" class="face-setup-prompt" data-testid="face-setup-prompt" role="dialog" aria-modal="true" aria-labelledby="faceSetupTitle">
  <div class="face-setup-backdrop" data-face-dismiss></div>
  <div class="face-setup-card" role="document">
    <button type="button" class="face-setup-close" data-face-dismiss aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
    <div class="face-setup-icon" aria-hidden="true"><i class="bi bi-person-badge"></i><span class="face-setup-ring"></span></div>
    <h5 id="faceSetupTitle" class="face-setup-title">Aktifkan Login Wajah</h5>
    <p class="face-setup-desc">Akun Anda belum memiliki <strong>dataset wajah</strong>. Tambahkan sekarang untuk mengaktifkan login tanpa mengetik password.</p>
    <ul class="face-setup-bullets">
      <li><i class="bi bi-lightning-charge-fill"></i> Login cepat tanpa mengetik.</li>
      <li><i class="bi bi-shield-check"></i> Dataset hanya digunakan untuk autentikasi akun.</li>
      <li><i class="bi bi-arrow-repeat"></i> Bisa dihapus atau diatur ulang kapan saja.</li>
    </ul>
    <div class="face-setup-actions">
      <button type="button" class="btn btn-outline-secondary" data-face-dismiss>Nanti Saja</button>
      <a href="<?= url('admin/akun?face_setup=1') . '#faceDatasetCard' ?>" class="btn btn-primary"><i class="bi bi-camera-video-fill me-1"></i>Scan &amp; Tambah Dataset</a>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
$inlineScript = '<script>(function(){'
    . 'var p=document.getElementById("faceSetupPrompt");if(p){document.body.classList.add("face-setup-open");p.classList.add("is-visible");'
    . 'var c=function(){document.body.classList.remove("face-setup-open");p.classList.remove("is-visible");setTimeout(function(){if(p.parentNode)p.parentNode.removeChild(p);},200);};'
    . 'p.querySelectorAll("[data-face-dismiss]").forEach(function(el){el.addEventListener("click",c);});'
    . 'document.addEventListener("keydown",function(ev){if(ev.key==="Escape"&&p.isConnected)c();});}'
    . 'var seg=document.querySelector(".dx-seg");if(!seg)return;var rows=document.querySelectorAll(".dx-row"),empty=document.querySelector(".dx-empty");'
    . 'seg.addEventListener("click",function(ev){var b=ev.target.closest("button");if(!b)return;seg.querySelectorAll("button").forEach(function(x){x.classList.toggle("is-on",x===b);});'
    . 'var f=b.getAttribute("data-filter"),shown=0;rows.forEach(function(r){var ok=f==="all"||r.getAttribute("data-type")===f;r.hidden=!ok;if(ok)shown++;});if(empty)empty.classList.toggle("d-none",shown>0);});'
    . '})();</script>';
?>
