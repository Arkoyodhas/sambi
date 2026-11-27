<?php
$heroVideo = setting('hero_video_enabled') === '1' && setting('hero_video');
$heroEl = array_filter(array_map('trim', explode(',', setting('hero_elements', 'stars,clouds,birds,hills,fireflies,badge,tagline,buttons'))));
$showEl = fn($k) => in_array($k, $heroEl, true);
$kadesName = setting('kepala_desa_nama');
$kadesDisplay = str_contains($kadesName, ',') ? $kadesName : title_case_id($kadesName);
$serviceIcon = static function (string $slug): string {
    foreach (['domisili' => 'bi-house-door', 'usaha' => 'bi-shop', 'ktp' => 'bi-person-vcard', '-kk' => 'bi-people', 'tidak-mampu' => 'bi-heart', 'kelahiran' => 'bi-balloon-heart', 'kematian' => 'bi-flower1', 'pindah' => 'bi-truck', 'menikah' => 'bi-person-check', 'nikah' => 'bi-gem', 'waris' => 'bi-diagram-3', 'kehilangan' => 'bi-search'] as $k => $ic) {
        if (str_contains($slug, $k)) return $ic;
    }
    return 'bi-file-earmark-text';
};
$mergedDone = false;
?>
<!-- HERO -->
<section class="hx-hero" data-testid="hero">
  <div class="hx-scene" aria-hidden="true">
    <?php if ($heroVideo): ?>
    <video class="hx-layer hx-sky hx-video" data-hx="sky" autoplay muted loop playsinline webkit-playsinline disablepictureinpicture disableremoteplayback preload="auto" tabindex="-1" aria-hidden="true" data-testid="hero-video">
      <source src="<?= e(upload_url(setting('hero_video'))) ?>" type="video/<?= str_ends_with(setting('hero_video'), '.webm') ? 'webm' : 'mp4' ?>">
    </video>
    <?php else: ?>
    <div class="hx-layer hx-sky" data-hx="sky" style="background-image:url('<?= e(hero_layer_url('hero_layer_sky', 'sky.webp')) ?>')"></div>
    <?php endif; ?>
    <div class="hx-sun" data-hx="sun"></div>
    <svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false"><defs>
      <linearGradient id="cloudShade" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#FFFFFF"/><stop offset=".55" stop-color="#FFFFFF"/><stop offset="1" stop-color="#D9E6EC"/></linearGradient>
      <filter id="cloudSoft" x="-15%" y="-25%" width="130%" height="150%"><feGaussianBlur stdDeviation="1.6"/></filter>
      <symbol id="cloudA" viewBox="0 0 320 150"><g filter="url(#cloudSoft)" fill="url(#cloudShade)"><circle cx="92" cy="92" r="40"/><circle cx="140" cy="70" r="52"/><circle cx="196" cy="80" r="44"/><circle cx="240" cy="98" r="32"/><circle cx="60" cy="108" r="26"/><rect x="40" y="96" width="232" height="38" rx="19"/></g></symbol>
      <symbol id="cloudB" viewBox="0 0 320 150"><g filter="url(#cloudSoft)" fill="url(#cloudShade)"><circle cx="110" cy="88" r="34"/><circle cx="160" cy="76" r="44"/><circle cx="212" cy="92" r="30"/><rect x="70" y="92" width="178" height="32" rx="16"/></g></symbol>
    </defs></svg>
    <div class="hx-clouds hx-clouds-far" data-hx="cloudsFar"><svg class="hx-cloud" viewBox="0 0 320 150" style="--top:4%;--w:200px;--dur:170s;--delay:-20s;--o:0.8"><use href="#cloudB"/></svg><svg class="hx-cloud" viewBox="0 0 320 150" style="--top:14%;--w:260px;--dur:210s;--delay:-120s;--o:0.75"><use href="#cloudA"/></svg><svg class="hx-cloud" viewBox="0 0 320 150" style="--top:2%;--w:150px;--dur:150s;--delay:-85s;--o:0.7"><use href="#cloudB"/></svg></div>
    <img class="hx-layer hx-hills" data-hx="hills" src="<?= e(hero_layer_url('hero_layer_hills', 'hills.webp')) ?>" alt="" decoding="async" fetchpriority="high">
    <div class="hx-clouds hx-clouds-near" data-hx="cloudsNear"><svg class="hx-cloud" viewBox="0 0 320 150" style="--top:10%;--w:380px;--dur:120s;--delay:-30s;--o:0.95"><use href="#cloudA"/></svg><svg class="hx-cloud" viewBox="0 0 320 150" style="--top:34%;--w:300px;--dur:140s;--delay:-95s;--o:0.9"><use href="#cloudB"/></svg></div>
    <img class="hx-layer hx-village" data-hx="village" src="<?= e(hero_layer_url('hero_layer_village', 'village.webp')) ?>" alt="" decoding="async">
  </div>

  <div class="container hx-inner">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 hx-copy" data-hx="copy">
        <?php if ($showEl('badge')): ?><span class="tl-trust" data-testid="hero-badge"><?= verify_mark_html('hero-verify-mark') ?> Website Resmi Pemerintah Desa</span><?php endif; ?>
        <h1 class="hx-title" data-split data-testid="hero-title"><?= e(setting('village_name')) ?></h1>
        <p class="hx-place"><?= e(setting('kecamatan')) ?> · <?= e(setting('kabupaten')) ?></p>
        <?php if ($showEl('tagline')): ?><p class="hx-tagline" data-reveal><?= e(setting('village_tagline')) ?></p><?php endif; ?>
        <div class="ss-hero" data-reveal>
          <form class="ss-box" role="search" data-ss-trigger action="<?= url('berita') ?>" method="get" data-testid="smart-search-hero" data-api="<?= e(url('api/search')) ?>">
            <span class="ss-glow" aria-hidden="true"></span>
            <i class="bi bi-stars ss-box-ic" aria-hidden="true"></i>
            <input class="ss-box-input" type="search" name="q" autocomplete="off" aria-label="Cari apa saja di website desa" placeholder="Cari apa saja…" data-ss-hero-input data-testid="smart-search-hero-input">
            <span class="ss-typed" aria-hidden="true" data-ss-typed></span>
            <kbd class="ss-kbd" aria-hidden="true">Ctrl K</kbd>
            <button type="submit" class="ss-box-btn" aria-label="Cari" data-testid="smart-search-hero-submit"><i class="bi bi-arrow-right"></i></button>
          </form>
          <div class="ss-hero-chips" data-testid="smart-search-hero-chips">
            <?php foreach (['Surat domisili', 'Jumlah penduduk', 'Jam pelayanan', 'Bansos', 'UMKM'] as $ci => $chip): ?>
            <button type="button" class="ss-hchip" data-ss-chip="<?= e($chip) ?>" data-testid="smart-search-hero-chip-<?= $ci ?>"><?= e($chip) ?></button>
            <?php endforeach; ?>
          </div>
        </div>
        <?php if ($showEl('buttons')): ?>
        <div class="hx-actions" data-reveal>
          <?php if (feature_enabled('services')): ?>
          <a href="<?= url('layanan') ?>" class="btn tl-cta tl-cta-lg" data-testid="hero-ajukan-surat"><i class="bi bi-send-fill"></i> Ajukan Surat</a>
          <?php endif; ?>
          <?php $buttons = array_filter(array_map('trim', explode(',', setting('hero_buttons', 'profil,layanan,berita,pengaduan,statistik'))));
          $btnMap = ['profil'=>['bi-info-circle','Profil Desa','profil'],'layanan'=>['bi-file-earmark-text','Layanan','layanan'],'berita'=>['bi-newspaper','Berita','berita'],'pengaduan'=>['bi-chat-square-text','Pengaduan','pengaduan'],'statistik'=>['bi-bar-chart','Statistik','statistik'],'idm'=>['bi-graph-up','IDM','idm'],'bansos'=>['bi-people','Bansos','bansos'],'transparansi'=>['bi-shield-check','Transparansi','transparansi'],'hiburan'=>['bi-controller','Zona Hiburan','hiburan']];
          foreach ($buttons as $bk): if (!isset($btnMap[$bk]) || ($bk === 'layanan' && feature_enabled('services'))) continue; [$ic,$lab,$route] = $btnMap[$bk]; ?>
            <a href="<?= url($route) ?>" class="btn tl-chip" data-testid="hero-btn-<?= e($bk) ?>"><i class="bi <?= $ic ?>"></i> <?= e($lab) ?></a>
          <?php endforeach; ?>
          <?php include BASE_PATH . '/views/partials/siaga.php'; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="col-lg-6">
        <div class="hx-diorama" data-hx="diorama" data-testid="hero-diorama">
          <img class="hx-diorama-still" src="<?= e(hero_layer_url('hero_diorama_image', 'diorama.webp')) ?>" alt="Ilustrasi diorama <?= e(setting('village_name')) ?>: pendopo, pepohonan dan kolam ikan" decoding="async" data-testid="hero-diorama-still">
          <canvas class="hx-diorama-canvas" id="dioramaCanvas" aria-hidden="true"></canvas>
          <?php
          $heroVillageName = trim((string) setting('village_name'));
          $heroKecamatan   = trim((string) setting('kecamatan'));
          $heroKabupaten   = trim((string) setting('kabupaten', ''));
          $heroMapsUrl     = trim((string) (setting('google_maps_url') ?: setting('maps_url') ?: ''));
          if ($heroMapsUrl === '') {
              $heroLat = trim((string) (setting('map_lat') ?: setting('latitude') ?: ''));
              $heroLng = trim((string) (setting('map_lng') ?: setting('longitude') ?: ''));
              if ($heroLat !== '' && $heroLng !== '') {
                  $heroMapsUrl = 'https://www.google.com/maps?q=' . rawurlencode($heroLat . ',' . $heroLng);
              } else {
                  $heroQueryParts = array_filter([$heroVillageName, $heroKecamatan, $heroKabupaten]);
                  $heroMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(implode(', ', $heroQueryParts));
              }
          }
          ?>
          <a href="<?= e($heroMapsUrl) ?>"
             target="_blank"
             rel="noopener noreferrer"
             class="hx-diorama-tag"
             title="Buka lokasi <?= e($heroVillageName) ?> di Google Maps"
             aria-label="Buka lokasi <?= e($heroVillageName) ?>, <?= e($heroKecamatan) ?> di Google Maps">
            <i class="bi bi-geo-alt-fill hx-tag-pin" aria-hidden="true"></i>
            <span class="hx-tag-name"><?= e($heroVillageName) ?></span>
            <?php if ($heroKecamatan !== ''): ?>
            <span class="hx-tag-sep" aria-hidden="true"></span>
            <span class="hx-tag-area"><?= e($heroKecamatan) ?></span>
            <?php endif; ?>
            <i class="bi bi-arrow-up-right hx-tag-go" aria-hidden="true"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
  <a href="#konten" class="hx-scroll" aria-label="Gulir ke konten" data-testid="hero-scroll"><span></span></a>
</section>

<div id="konten"></div>
<?php foreach ($sections as $sec): ?>
<?php if ($sec === 'transparansi'): $idmVal = $transparansiIdm ? (float)$transparansiIdm['idm'] : 0; $idmKl = $idmVal > 0.8155 ? ['Mandiri','success'] : ($idmVal > 0.7072 ? ['Maju','info'] : ($idmVal > 0.5989 ? ['Berkembang','warning'] : ($idmVal > 0.4907 ? ['Tertinggal','danger'] : ($idmVal > 0 ? ['Sangat Tertinggal','secondary'] : ['—','secondary'])))); ?>
<!-- TRANSPARANSI -->
<section class="tl-section" data-testid="home-transparansi">
  <div class="container">
    <div class="tl-head text-center mx-auto">
      <span class="tl-eyebrow">Transparansi</span>
      <h2 data-split>Indikator utama desa</h2>
      <p class="mx-auto">Data ringkas yang bisa dicek siapa saja. Klik untuk detail lengkap.</p>
    </div>
    <div class="row g-4" data-reveal-group>
      <div class="col-md-4">
        <a href="<?= url('idm') ?>" class="tl-card tl-metric" data-testid="widget-idm">
          <span class="tl-metric-icon"><i class="bi bi-graph-up"></i></span>
          <span class="tl-metric-lab">Indeks Desa Membangun</span>
          <span class="tl-metric-val"><?= $transparansiIdm ? number_format($idmVal, 4) : '—' ?></span>
          <span class="badge bg-<?= $idmKl[1] ?>-subtle text-<?= $idmKl[1] ?>-emphasis align-self-start"><?= $idmKl[0] ?></span>
          <span class="tl-link">Lihat detail <i class="bi bi-arrow-right"></i></span>
        </a>
      </div>
      <div class="col-md-4">
        <a href="<?= url('bansos') ?>" class="tl-card tl-metric" data-testid="widget-bansos">
          <span class="tl-metric-icon"><i class="bi bi-people-fill"></i></span>
          <span class="tl-metric-lab">Bantuan Sosial <?= (int)date('Y') ?></span>
          <span class="tl-metric-val"><?= (int)($transparansiBansos['recipients'] ?? 0) ?></span>
          <span class="tl-metric-sub"><?= (int)($transparansiBansos['programs'] ?? 0) ?> program aktif</span>
          <span class="tl-link">Lihat penerima <i class="bi bi-arrow-right"></i></span>
        </a>
      </div>
      <div class="col-md-4">
        <a href="<?= url('stunting') ?>" class="tl-card tl-metric" data-testid="widget-stunting">
          <span class="tl-metric-icon"><i class="bi bi-heart-pulse"></i></span>
          <span class="tl-metric-lab">Kasus Stunting <?= (int)date('Y') ?></span>
          <span class="tl-metric-val"><?= (int)($transparansiStunting['stunting'] ?? 0) ?></span>
          <span class="tl-metric-sub">dari <?= (int)($transparansiStunting['total'] ?? 0) ?> balita diperiksa</span>
          <span class="tl-link">Lihat sebaran <i class="bi bi-arrow-right"></i></span>
        </a>
      </div>
    </div>
  </div>
</section>

<?php elseif ($sec === 'stats'): ?>
<!-- STATISTIK -->
<section class="tl-stats-wrap" data-testid="home-stats">
  <div class="container">
    <div class="tl-stats" data-reveal>
      <?php
      $statItems = [
          ['bi-people-fill', $stats['penduduk'], 'Penduduk'],
          ['bi-house-heart-fill', $stats['kk'], 'Kepala Keluarga'],
          ['bi-map-fill', $stats['dusun'], 'Dusun'],
          ['bi-grid-3x3-gap-fill', $stats['rt'] + $stats['rw'], 'RT / RW'],
      ];
      if (feature_enabled('village_profile')) $statItems[] = ['bi-person-badge-fill', $stats['perangkat'], 'Perangkat'];
      if (feature_enabled('news')) $statItems[] = ['bi-newspaper', $stats['berita'], 'Berita'];
      ?>
      <?php foreach ($statItems as [$icon, $num, $label]): ?>
      <div class="tl-stat" data-testid="stat-<?= slugify($label) ?>">
        <span class="tl-stat-icon"><i class="bi <?= $icon ?>"></i></span>
        <div>
          <div class="tl-stat-num odo" data-value="<?= (int) $num ?>" aria-label="<?= number_format((int) $num, 0, ',', '.') ?>" data-testid="stat-value-<?= slugify($label) ?>">
            <span class="odo-static"><?= number_format((int) $num, 0, ',', '.') ?></span>
            <span class="odo-skel" aria-hidden="true"></span>
          </div>
          <div class="tl-stat-label"><?= e($label) ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($sec === 'sambutan' && setting('sambutan')): ?>
<!-- SAMBUTAN -->
<section class="tl-section" data-testid="home-sambutan" data-sketch="joglo wayang">
  <div class="container">
    <article class="tl-editorial" data-clip-reveal>
      <div class="tl-editorial-photo protected-img">
        <?php if (setting('kepala_desa_foto')): ?>
        <div class="photo-bg" data-parallax-img style="background-image:url('<?= e(upload_url(setting('kepala_desa_foto'))) ?>')"></div>
        <?php else: ?>
        <div class="photo-bg avatar-fallback"><i class="bi bi-person"></i></div>
        <?php endif; ?>
        <div class="photo-shield"></div>
        <span class="tl-editorial-role">Kepala Desa</span>
      </div>
      <div class="tl-editorial-body">
        <span class="tl-eyebrow">Sambutan</span>
        <h2 data-split>Sambutan Kepala Desa</h2>
        <svg class="tl-quote" viewBox="0 0 48 36" aria-hidden="true"><path d="M0 36V22C0 9 7 2 19 0l2 5c-7 2-10 6-10 12h9v19H0zm27 0V22C27 9 34 2 46 0l2 5c-7 2-10 6-10 12h9v19H27z"/></svg>
        <div class="tl-editorial-text" data-no-scrub data-testid="sambutan-text"><?= nl2br(e(setting('sambutan'))) ?></div>
        <div class="tl-sign">
          <div>
            <strong data-testid="kades-name">Bapak <?= e($kadesDisplay) ?></strong>
            <span>Kepala <?= e(setting('village_name')) ?></span>
          </div>
          <?php if (feature_enabled('village_profile')): ?><a href="<?= url('profil') ?>" class="btn tl-btn-ghost" data-testid="sambutan-profil">Profil Desa <i class="bi bi-arrow-right"></i></a><?php endif; ?>
        </div>
      </div>
    </article>
  </div>
</section>

<?php elseif ($sec === 'layanan' && $services): ?>
<!-- LAYANAN -->
<section class="tl-section tl-mint" data-testid="home-layanan" data-sketch="satelit drone" data-sketch-pos="top">
  <div class="container">
    <div class="tl-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="tl-eyebrow">Layanan Publik</span>
        <h2 data-split>Layanan Administrasi Desa</h2>
        <p>Ajukan surat keterangan dan administrasi kependudukan secara online, pantau statusnya dengan nomor tiket.</p>
      </div>
      <a href="<?= url('layanan') ?>" class="btn tl-btn-ghost" data-testid="layanan-semua">Semua Layanan <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4" data-reveal-group>
      <?php foreach ($services as $s): ?>
      <div class="col-6 col-lg-3">
        <a href="<?= url('layanan/' . $s['slug']) ?>" class="tl-card tl-service" data-testid="service-card-<?= e($s['slug']) ?>">
          <span class="tl-doc" aria-hidden="true">
            <span class="tl-doc-sheet tl-doc-back"></span>
            <span class="tl-doc-sheet tl-doc-front"><i></i><i></i><i></i><b class="bi <?= $serviceIcon($s['slug']) ?>"></b></span>
            <span class="tl-doc-stamp"><i class="bi bi-check-lg"></i></span>
          </span>
          <h3 class="tl-service-name"><?= e(preg_replace('/^Surat (Keterangan|Pengantar)\s+/i', '', $s['name'])) ?></h3>
          <span class="tl-service-type"><?= e(stripos($s['name'], 'pengantar') !== false ? 'Surat Pengantar' : 'Surat Keterangan') ?></span>
          <span class="tl-badges">
            <span class="tl-badge-teal"><i class="bi bi-clock"></i> <?= e(title_case_id($s['duration'] ?: '1 hari kerja')) ?></span>
            <span class="tl-badge-amber"><?= e($s['fee'] ?: 'Gratis') ?></span>
          </span>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="tl-flow" data-flow data-testid="alur-pengajuan">
      <div class="tl-flow-head">
        <span class="tl-eyebrow">Alur Pengajuan Surat</span>
        <h3>Lima langkah, tanpa antre</h3>
      </div>
      <div class="tl-flow-track">
        <svg class="tl-flow-line" viewBox="0 0 1000 40" preserveAspectRatio="none" aria-hidden="true">
          <path class="tl-flow-base" d="M10 20 C 200 20, 300 20, 500 20 S 800 20, 990 20"/>
          <path class="tl-flow-draw" pathLength="1" d="M10 20 C 200 20, 300 20, 500 20 S 800 20, 990 20"/>
        </svg>
        <span class="tl-flow-traveler" aria-hidden="true" data-testid="alur-traveler"><i class="bi bi-envelope-paper-fill"></i></span>
        <svg class="tl-flow-line-v" viewBox="0 0 4 1000" preserveAspectRatio="none" aria-hidden="true">
          <path class="tl-flow-base" d="M2 0 L2 1000"/>
          <path class="tl-flow-draw" pathLength="1" d="M2 0 L2 1000"/>
        </svg>
        <?php foreach ([['bi-ui-checks-grid', 'Pilih Layanan', 'Tentukan jenis surat yang dibutuhkan.'], ['bi-pencil-square', 'Isi Formulir', 'Lengkapi data diri sesuai KTP/KK.'], ['bi-cloud-arrow-up', 'Unggah Berkas', 'Lampirkan dokumen pendukung.'], ['bi-person-check', 'Verifikasi Petugas', 'Perangkat desa memeriksa data Anda.'], ['bi-patch-check', 'Surat Selesai', 'Ambil surat atau pantau via nomor tiket.']] as $si => [$sic, $st, $sd]): ?>
        <div class="tl-step" data-step data-testid="alur-step-<?= $si + 1 ?>">
          <span class="tl-step-dot"><i class="bi <?= $sic ?>"></i></span>
          <span class="tl-step-num">Langkah <?= str_pad((string) ($si + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <strong><?= e($st) ?></strong>
          <small><?= e($sd) ?></small>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="text-center mt-4"><a href="<?= url('layanan') ?>" class="btn tl-cta tl-cta-lg" data-testid="alur-ajukan">Mulai Pengajuan <i class="bi bi-arrow-right"></i></a></div>
    </div>
  </div>
</section>

<?php elseif ($sec === 'berita' && $latestNews): ?>
<!-- BERITA -->
<section class="tl-section" data-testid="home-berita" data-sketch="kereta angklung" data-sketch-pos="left">
  <div class="container">
    <div class="tl-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="tl-eyebrow">Kabar Desa</span>
        <h2 data-split>Berita Terbaru</h2>
      </div>
      <a href="<?= url('berita') ?>" class="btn tl-btn-ghost" data-testid="berita-semua">Semua Berita <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4" data-reveal-group>
      <?php foreach (array_slice($latestNews, 0, 6) as $n): ?>
      <div class="col-md-6 col-lg-4">
        <article class="tl-card tl-news" data-testid="news-card">
          <a href="<?= url('berita/' . $n['slug']) ?>" class="tl-frame">
            <?php if ($n['thumbnail']): ?>
            <img src="<?= e(thumbnail_url($n['thumbnail'], 720, 440)) ?>" alt="<?= e($n['title']) ?>" loading="lazy" data-parallax-img>
            <?php else: ?>
            <span class="tl-frame-fallback"><i class="bi bi-newspaper"></i></span>
            <?php endif; ?>
            <span class="tl-tag-amber tl-frame-tag"><?= e($n['category'] ?: 'Umum') ?></span>
          </a>
          <div class="tl-news-body">
            <small class="tl-meta"><i class="bi bi-calendar3"></i> <?= tgl_id($n['published_at']) ?></small>
            <h3 class="tl-news-title"><a href="<?= url('berita/' . $n['slug']) ?>"><?= e($n['title']) ?></a></h3>
            <p class="tl-news-excerpt" data-testid="news-excerpt"><?= e(excerpt($n['excerpt'] ?: $n['content'], 110)) ?></p>
            <a href="<?= url('berita/' . $n['slug']) ?>" class="tl-link">Baca selengkapnya <i class="bi bi-arrow-right"></i></a>
          </div>
        </article>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($sec === 'pengumuman' && ($announcements || $agendas)): ?>
<!-- PENGUMUMAN + AGENDA -->
<section class="tl-section tl-mint" data-testid="home-pengumuman" data-sketch="gamelan energi">
  <div class="container">
    <div class="row g-5">
      <div class="col-lg-7">
        <div class="tl-head mb-4">
          <span class="tl-eyebrow">Informasi</span>
          <h2 data-split>Pengumuman</h2>
        </div>
        <div class="d-flex flex-column gap-3" data-reveal-group>
          <?php foreach ($announcements as $a): ?>
          <a class="tl-card tl-announce <?= $a['is_important'] ? 'important' : '' ?>" href="<?= url('pengumuman/' . $a['id']) ?>" data-testid="announce-item">
            <span class="tl-announce-icon"><i class="bi <?= $a['is_important'] ? 'bi-megaphone-fill' : 'bi-megaphone' ?>"></i></span>
            <span class="flex-grow-1">
              <strong><?= e($a['title']) ?></strong>
              <small><i class="bi bi-calendar3 me-1"></i><?= tgl_id($a['start_date']) ?></small>
            </span>
            <?php if ($a['is_important']): ?><span class="tl-tag-amber">Penting</span><?php endif; ?>
          </a>
          <?php endforeach; ?>
          <?php if (!$announcements): ?><p class="tl-muted">Belum ada pengumuman aktif.</p><?php endif; ?>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="tl-head mb-4">
          <span class="tl-eyebrow">Jadwal</span>
          <h2 data-split>Agenda Terdekat</h2>
        </div>
        <div class="d-flex flex-column gap-3" data-reveal-group>
          <?php foreach ($agendas as $ag): ?>
          <div class="tl-card tl-agenda" data-testid="agenda-item">
            <div class="agenda-date">
              <div class="d"><?= date('d', strtotime($ag['event_date'])) ?></div>
              <div class="m"><?= ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'][(int) date('n', strtotime($ag['event_date']))] ?></div>
            </div>
            <div>
              <strong><?= e($ag['title']) ?></strong>
              <small><i class="bi bi-geo-alt me-1"></i><?= e($ag['location'] ?: '-') ?> · <?= $ag['start_time'] ? date('H:i', strtotime($ag['start_time'])) . ' WIB' : '' ?></small>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (!$agendas): ?><p class="tl-muted">Belum ada agenda terdekat.</p><?php endif; ?>
          <a href="<?= url('agenda') ?>" class="btn tl-btn-ghost align-self-start" data-testid="agenda-semua">Semua Agenda <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php elseif ($sec === 'perangkat' && $officials): ?>
<!-- PERANGKAT -->
<section class="tl-section tl-mint" data-testid="home-perangkat" data-sketch="borobudur prambanan" data-sketch-pos="left">
  <div class="container">
    <div class="tl-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="tl-eyebrow">Pemerintahan</span>
        <h2 data-split>Perangkat Desa</h2>
        <p>Wajah-wajah yang melayani warga setiap hari kerja.</p>
      </div>
      <a href="<?= url('pemerintahan') ?>" class="btn tl-btn-ghost" data-testid="perangkat-lengkap">Struktur Lengkap <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="row g-4 justify-content-center" data-reveal-group>
      <?php foreach ($officials as $o): ?>
      <div class="col-6 col-md-3">
        <div class="tl-card tl-official" data-tilt="6" data-testid="official-card">
          <div class="official-photo protected-img">
            <div class="photo-bg" style="background-image:url('<?= url('media/foto/' . $o['id']) ?>')"></div>
            <div class="photo-shield"></div>
          </div>
          <div class="tl-official-body">
            <div class="official-name"><?= e($o['nama']) ?></div>
            <div class="official-role"><?= e($o['jabatan']) ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($sec === 'banner' && !empty($banners)): include BASE_PATH . '/views/partials/home_banner.php'; ?>

<?php elseif ($sec === 'galeri' && $galleries): ?>
<!-- GALERI -->
<section class="tl-section" data-testid="home-galeri" data-sketch="jembatan pinisi" data-sketch-pos="top">
  <div class="container">
    <div class="tl-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div><span class="tl-eyebrow">Dokumentasi</span><h2 data-split>Galeri Kegiatan</h2></div>
      <a href="<?= url('galeri') ?>" class="btn tl-btn-ghost" data-testid="galeri-buka">Buka Galeri <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="tl-gallery" data-reveal-group>
      <?php foreach ($galleries as $g): ?>
      <a href="<?= e(gallery_src($g)) ?>" class="tl-gallery-item glightbox <?= $g['protected'] ? 'protected-img' : '' ?>" data-gallery="home" data-testid="gallery-item">
        <img src="<?= e(gallery_thumb($g, 800, 800)) ?>" alt="<?= e($g['title'] ?: 'Galeri desa') ?>" loading="lazy" decoding="async" data-parallax-img>
        <?php if ($g['title']): ?><span class="tl-gallery-cap"><?= e($g['title']) ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif (($sec === 'potensi' || $sec === 'umkm') && !$mergedDone && ($potentials || $umkm)): $mergedDone = true;
$digital = [];
foreach (cached_menus('main') as $dm) {
    if (str_starts_with((string) $dm['url'], 'http') && !in_array($dm['url'], array_column($digital, 'url'), true)) $digital[] = $dm;
}
?>
<!-- UMKM & POTENSI -->
<section class="tl-section tl-mint" data-testid="home-umkm-potensi" data-sketch="pesawat angklung">
  <div class="container">
    <div class="tl-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="tl-eyebrow">Ekonomi & Unggulan</span>
        <h2 data-split>UMKM & Potensi Desa</h2>
        <p>Produk warga, wisata alam dan budidaya, serta layanan digital desa dalam satu tempat.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="<?= url('umkm') ?>" class="btn tl-btn-ghost" data-testid="umkm-semua">Semua UMKM <i class="bi bi-arrow-right"></i></a>
        <a href="<?= url('potensi') ?>" class="btn tl-btn-ghost d-none d-md-inline-flex" data-testid="potensi-semua">Potensi <i class="bi bi-arrow-right"></i></a>
      </div>
    </div>
    <div class="tl-filters" role="tablist" aria-label="Filter UMKM dan potensi" data-filter-bar>
      <?php foreach (['all' => 'Semua', 'umkm' => 'Produk UMKM', 'wisata' => 'Wisata & Budidaya', 'digital' => 'E-Commerce & E-Perpus'] as $fk => $fl): ?>
      <button type="button" class="tl-pill <?= $fk === 'all' ? 'active' : '' ?>" data-filter="<?= $fk ?>" role="tab" aria-selected="<?= $fk === 'all' ? 'true' : 'false' ?>" data-testid="filter-<?= $fk ?>"><?= e($fl) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="tl-grid" data-filter-grid data-testid="umkm-potensi-grid">
      <?php foreach ($umkm as $u): ?>
      <a href="<?= url('umkm' . ($u['category'] ? '?kategori=' . urlencode($u['category']) : '')) ?>" class="tl-card tl-item" data-cat="umkm" data-tilt="5" data-testid="item-umkm-<?= (int) $u['id'] ?>">
        <span class="tl-frame">
          <?php if ($u['photo']): ?><img src="<?= e(thumbnail_url($u['photo'], 640, 420)) ?>" alt="<?= e(title_case_id($u['name'])) ?>" loading="lazy"><?php else: ?><span class="tl-frame-fallback"><i class="bi bi-shop"></i></span><?php endif; ?>
          <span class="tl-tag-amber tl-frame-tag"><?= e(title_case_id($u['category'] ?: 'Produk UMKM')) ?></span>
        </span>
        <span class="tl-item-body">
          <strong class="tl-item-title" data-testid="umkm-title"><?= e(title_case_id($u['name'])) ?></strong>
          <small><?= e($u['owner'] ?: 'UMKM ' . setting('village_name')) ?></small>
          <?php if ($u['price']): ?><span class="tl-price"><?= e($u['price']) ?></span><?php endif; ?>
        </span>
      </a>
      <?php endforeach; ?>
      <?php foreach ($potentials as $p):
        $catIcon = ['wisata' => 'bi-tree', 'pertanian' => 'bi-flower1', 'peternakan' => 'bi-egg-fried', 'perikanan' => 'bi-water', 'budaya' => 'bi-music-note-list', 'kerajinan' => 'bi-palette'][$p['category']] ?? 'bi-star'; ?>
      <a href="<?= url('potensi') ?>" class="tl-card tl-item" data-cat="wisata" data-tilt="5" data-testid="potensi-card">
        <span class="tl-frame">
          <?php if (!empty($p['photo'])): ?><img src="<?= e(thumbnail_url($p['photo'], 640, 420)) ?>" alt="<?= e($p['title']) ?>" loading="lazy"><?php else: ?><span class="tl-frame-fallback"><i class="bi <?= e($catIcon) ?>"></i></span><?php endif; ?>
          <span class="tl-tag-teal tl-frame-tag"><i class="bi <?= e($catIcon) ?>"></i> <?= e(ucfirst($p['category'])) ?></span>
        </span>
        <span class="tl-item-body">
          <strong class="tl-item-title"><?= e(title_case_id($p['title'])) ?></strong>
          <small><?= e(excerpt($p['description'], 80)) ?></small>
        </span>
      </a>
      <?php endforeach; ?>
      <?php foreach ($digital as $dg): $isPerpus = stripos($dg['title'], 'perpus') !== false; ?>
      <a href="<?= e($dg['url']) ?>" target="_blank" rel="noopener" class="tl-card tl-item tl-item-digital" data-cat="digital" data-tilt="5" data-testid="item-digital-<?= e(slugify($dg['title'])) ?>">
        <span class="tl-frame tl-frame-digital">
          <span class="tl-digital-icon"><i class="bi <?= $isPerpus ? 'bi-book-half' : 'bi-bag-heart' ?>"></i></span>
          <span class="tl-tag-teal tl-frame-tag"><?= $isPerpus ? 'E-Perpus' : 'E-Commerce' ?></span>
        </span>
        <span class="tl-item-body">
          <strong class="tl-item-title"><?= e($dg['title']) ?></strong>
          <small><?= $isPerpus ? 'Pinjam dan baca koleksi buku digital desa.' : 'Belanja produk warga & BUMDes secara online.' ?></small>
          <span class="tl-link">Kunjungi <i class="bi bi-box-arrow-up-right"></i></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($sec === 'social' && setting('home_social_enabled', '1') === '1' && !empty($socialEmbeds)): ?>
<!-- MEDIA SOSIAL -->
<section class="tl-section social-showcase" data-testid="home-social-slider" data-sketch="ponsel kamera">
  <div class="soc-float" aria-hidden="true">
    <i class="bi bi-heart"></i><i class="bi bi-play-circle"></i><i class="bi bi-chat-dots"></i><i class="bi bi-hand-thumbs-up"></i><i class="bi bi-bell"></i><i class="bi bi-share"></i><i class="bi bi-music-note-beamed"></i><i class="bi bi-camera"></i>
  </div>
  <div class="container">
    <div class="tl-head social-section-head d-flex flex-wrap justify-content-between align-items-end gap-3">
      <div>
        <span class="tl-eyebrow">Media Resmi Desa</span>
        <h2 data-split><?= e(setting('home_social_title', 'Media Sosial Desa')) ?></h2>
        <p class="mb-0">Lihat video dan kegiatan terbaru langsung dari website desa.</p>
      </div>
      <?php if (count($socialEmbeds) > 1): ?>
      <div class="social-slider-controls" aria-label="Navigasi konten sosial">
        <button type="button" class="social-nav-btn" data-social-slide="-1" aria-label="Konten sebelumnya"><i class="bi bi-chevron-left"></i></button>
        <button type="button" class="social-nav-btn" data-social-slide="1" aria-label="Konten berikutnya"><i class="bi bi-chevron-right"></i></button>
      </div>
      <?php endif; ?>
    </div>

    <div class="social-slider" id="homeSocialSlider">
      <?php foreach ($socialEmbeds as $item):
        $icon = ['youtube'=>'bi-youtube','instagram'=>'bi-instagram','tiktok'=>'bi-tiktok'][$item['platform']] ?? 'bi-play-circle';
        $ratioClass = ($item['ratio'] ?? '') === 'wide' ? 'is-wide' : 'is-portrait';
      ?>
      <article class="social-media-card social-<?= e($item['platform']) ?> <?= e($ratioClass) ?>" data-social-platform="<?= e($item['platform']) ?>">
        <div class="social-media-frame">
          <span class="social-frame-mark" aria-hidden="true"><i class="bi <?= e($icon) ?>"></i></span>
          <?php if (!empty($item['poster_url'])): ?>
          <button type="button" class="social-media-poster" data-social-embed="<?= e($item['embed_url']) ?>" data-social-title="<?= e($item['title']) ?>" aria-label="Putar <?= e($item['title']) ?>">
            <img src="<?= e($item['poster_url']) ?>" alt="<?= e($item['title']) ?>" loading="lazy" onload="this.closest('.social-media-card').classList.add('loaded')">
            <span class="social-play"><i class="bi bi-play-fill"></i></span>
          </button>
          <?php else: ?>
          <iframe src="<?= e($item['embed_url']) ?>" title="<?= e($item['title']) ?>" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen onload="this.closest('.social-media-card').classList.add('loaded')"></iframe>
          <?php endif; ?>
        </div>
        <div class="social-media-meta">
          <span class="social-platform-label"><i class="bi <?= e($icon) ?>"></i><?= e(ucfirst($item['platform'])) ?></span>
          <h3><?= e($item['title']) ?></h3>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php elseif ($sec === 'peta'): ?>
<!-- PETA -->
<section class="tl-section tl-mint" data-testid="home-peta" data-sketch="pinisi energi" data-sketch-pos="left">
  <div class="container">
    <div class="tl-map-card" data-reveal>
      <div class="tl-map-info">
        <span class="tl-eyebrow">Lokasi</span>
        <h2 data-split>Kantor <?= e(setting('village_name')) ?></h2>
        <ul class="tl-map-list">
          <li><i class="bi bi-geo-alt-fill"></i><span><?= e(setting('address')) ?>, <?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?></span></li>
          <?php if (setting('jam_pelayanan')): ?><li><i class="bi bi-clock-fill"></i><span><?= e(setting('jam_pelayanan')) ?></span></li><?php endif; ?>
          <?php if (setting('phone')): ?><li><i class="bi bi-telephone-fill"></i><span><?= e(setting('phone')) ?></span></li><?php endif; ?>
        </ul>
        <a class="btn tl-cta" target="_blank" rel="noopener" href="https://www.google.com/maps?q=<?= e((string) (float) setting('map_lat', '-6.914744')) ?>,<?= e((string) (float) setting('map_lng', '107.60981')) ?>" data-testid="map-directions"><i class="bi bi-sign-turn-right-fill"></i> Petunjuk Arah</a>
      </div>
      <div id="villageMap" class="tl-map" data-testid="village-map"></div>
    </div>
  </div>
</section>
<?php $needsMap = true; endif; ?>
<?php endforeach; ?>

<?php if (!empty($needsMap)):
$inlineScript = '<script>
document.addEventListener("DOMContentLoaded", function(){
  window.desaMap && desaMap({lat: ' . json_encode((float) setting('map_lat', '-6.914744')) . ', lng: ' . json_encode((float) setting('map_lng', '107.60981')) . ', zoom: ' . max(18, min(19, (int) setting('map_zoom', '14'))) . ', title: ' . json_encode('Kantor ' . setting('village_name')) . ', address: ' . json_encode(setting('address')) . ', satellite: true, lockInteraction: true, hideMarker: true, hideRadius: true});
});
</script>';
endif; ?>