<?php $entertainmentEnabled = Entertainment::enabled(); ?>

<section class="ent-page-hero">
  <div class="container">
    <nav class="ent-section-nav" aria-label="Navigasi Zona Hiburan">
      <a href="#entOverview" class="active"><i class="bi bi-command"></i><span>Dashboard</span></a>
      <a href="#entInteractive"><i class="bi bi-lightning-charge"></i><span>Aktivitas</span></a>
      <a href="#entGameArena"><i class="bi bi-controller"></i><span>Game Arena</span></a>
      <a href="#leaderboardTitle"><i class="bi bi-trophy"></i><span>Peringkat</span></a>
    </nav>

    <article class="ent-overview-card" id="entOverview" aria-label="Dashboard Zona Bermain">
      <header class="ent-overview-topbar">
        <div class="ent-overview-title">
          <span class="ent-eyebrow"><i class="bi bi-controller"></i> Dashboard pemain</span>
          <h1>Zona Bermain</h1>
        </div>
        <div class="ent-profile-topline">
          <span><i class="bi bi-person-badge"></i> Player profile</span>
          <span class="ent-profile-status"><i></i> Online</span>
        </div>
      </header>

      <?php if ($entertainmentEnabled): ?>
        <div class="ent-profile-loading" id="entProfileLoading">
          <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
          <span>Menyiapkan profil…</span>
        </div>

        <div class="ent-overview-content" id="entProfileContent" hidden>
          <div class="ent-overview-profile-row">
            <div class="ent-player-row">
              <div class="ent-avatar ent-frame-simple" id="entProfileAvatar" aria-hidden="true"><i class="bi bi-tree-fill"></i></div>
              <div class="ent-player-name">
                <small>Nama pemain</small>
                <strong id="entDisplayName">Pemain</strong>
                <span id="entFeaturedBadgePill" hidden></span>
              </div>
              <button class="ent-icon-button ms-auto" id="entRenameBtn" type="button" aria-label="Atur profil">
                <i class="bi bi-pencil-square"></i>
              </button>
            </div>
          </div>

          <div class="ent-overview-stats" aria-label="Ringkasan Zona Bermain">
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-controller"></i></span>
              <strong>4</strong>
              <small>Game aktif</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-grid"></i></span>
              <strong>3</strong>
              <small>Aktivitas</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-calendar2-check"></i></span>
              <strong>Harian</strong>
              <small>Reset progres</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-stars"></i></span>
              <strong id="entPoints">0</strong>
              <small>Total poin</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-fire"></i></span>
              <strong id="entStreak">0</strong>
              <small id="entStreakLabel">Main 1 game/hari</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-trophy"></i></span>
              <strong id="entRank">-</strong>
              <small>Peringkat</small>
            </div>
            <div class="ent-overview-stat">
              <span class="ent-overview-stat-icon"><i class="bi bi-award"></i></span>
              <strong id="entBadgeCount">0</strong>
              <small>Badge</small>
            </div>
          </div>
        </div>

        <div class="ent-profile-error" id="entProfileError" hidden></div>
      <?php else: ?>
        <div class="ent-profile-loading">
          <i class="bi bi-pause-circle"></i>
          <span>Zona Hiburan sedang tidak tersedia.</span>
        </div>
      <?php endif; ?>
    </article>
  </div>
</section>

<div class="ent-main" data-enabled="<?= $entertainmentEnabled ? '1' : '0' ?>">
  <div class="container">
    <?php if (!$entertainmentEnabled): ?>
      <div class="ent-disabled-state">
        <i class="bi bi-info-circle"></i>
        <div>
          <strong>Zona Hiburan sedang dinonaktifkan</strong>
          <p>Admin belum membuka akses permainan dan aktivitas untuk saat ini.</p>
        </div>
      </div>
    <?php else: ?>

      <header class="ent-workspace-head">
        <div>
          <span class="ent-eyebrow">Ruang bermain</span>
          <h2>Dashboard Pemain</h2>
          <p>Pantau misi, klaim hadiah, dan lanjutkan aktivitas harianmu.</p>
        </div>
        <div class="ent-workspace-badge">
          <i class="bi bi-shield-check"></i>
          <span>Progres tersimpan otomatis</span>
        </div>
      </header>

      <div class="ent-comfort-bar" aria-label="Pengaturan kenyamanan">
        <div>
          <i class="bi bi-heart-pulse"></i>
          <span><strong>Kontrol kenyamanan</strong><small>Kurangi efek visual atau matikan suara permainan.</small></span>
        </div>
        <div class="ent-comfort-actions">
          <button type="button" id="entComfortToggle" aria-pressed="false">
            <i class="bi bi-eye"></i><span>Mode nyaman</span>
          </button>
          <button type="button" id="entSoundToggle" aria-pressed="true">
            <i class="bi bi-volume-up"></i><span>Suara aktif</span>
          </button>
        </div>
      </div>

      <section class="ent-dashboard-grid" aria-labelledby="missionTitle">
        <article class="ent-card ent-mission-card">
          <div class="ent-card-head">
            <div class="ent-card-title-group">
              <span class="ent-card-kicker"><i class="bi bi-calendar2-check"></i> Target hari ini</span>
              <h2 id="missionTitle">Misi Harian</h2>
              <p>Selesaikan seluruh target untuk membuka bonus progres.</p>
            </div>
            <div class="ent-mission-summary">
              <strong id="entMissionCount">0/5</strong>
              <span>Bonus +20 poin</span>
            </div>
          </div>

          <div class="ent-progress-row">
            <span>Progres misi</span>
            <span>Reset setiap hari</span>
          </div>
          <div class="ent-progress-track" aria-hidden="true"><span id="entMissionBar"></span></div>

          <div class="ent-mission-list" id="entMissionList">
            <div class="ent-skeleton"></div>
            <div class="ent-skeleton"></div>
            <div class="ent-skeleton"></div>
            <div class="ent-skeleton"></div>
            <div class="ent-skeleton"></div>
          </div>
        </article>

        <article class="ent-card ent-box-card" id="entDailyBoxCard">
          <div class="ent-box-visual" aria-hidden="true">
            <span class="ent-box-ring"></span>
            <div class="ent-box-icon"><i class="bi bi-gift-fill"></i></div>
          </div>
          <span class="ent-card-kicker"><i class="bi bi-stars"></i> Hadiah harian</span>
          <h2>Kotak Kejutan</h2>
          <p id="entDailyBoxText">Buka satu kotak untuk melihat hadiah hari ini.</p>
          <button class="btn ent-btn-primary" id="entDailyBoxBtn" type="button">
            <i class="bi bi-box2-heart"></i>
            <span>Buka kotak</span>
          </button>
        </article>
      </section>

      <section class="ent-section" id="entInteractive" aria-labelledby="interactiveTitle">
        <div class="ent-section-head">
          <div>
            <span class="ent-eyebrow">Aktivitas singkat</span>
            <h2 id="interactiveTitle">Aktivitas Interaktif</h2>
            <p>Selesaikan aktivitas ringan tanpa harus membuka permainan penuh.</p>
          </div>
          <span class="ent-section-chip"><i class="bi bi-stopwatch"></i> 1–3 menit</span>
        </div>

        <div class="ent-interactive-grid">
          <article class="ent-card ent-interactive-card ent-poll-card" id="entPollCard">
            <div class="ent-card-toolbar">
              <div class="ent-card-icon is-cyan"><i class="bi bi-bar-chart-fill"></i></div>
              <span class="ent-card-number">01</span>
            </div>
            <span class="ent-card-kicker">Polling cepat</span>
            <h3 id="entPollQuestion">Memuat pertanyaan…</h3>
            <div id="entPollContent" class="ent-poll-content">
              <div class="ent-skeleton"></div>
              <div class="ent-skeleton"></div>
            </div>
          </article>

          <article class="ent-card ent-interactive-card ent-story-card" id="entStoryCard">
            <div class="ent-card-toolbar">
              <div class="ent-card-icon is-violet"><i class="bi bi-signpost-split-fill"></i></div>
              <span class="ent-card-number">02</span>
            </div>
            <span class="ent-card-kicker">Cerita interaktif</span>
            <h3 id="entStoryTitle">Memuat cerita…</h3>
            <div id="entStoryContent">
              <div class="ent-skeleton"></div>
              <div class="ent-skeleton"></div>
            </div>
          </article>

          <article class="ent-card ent-interactive-card ent-fact-card" id="entFactCard">
            <div class="ent-card-toolbar">
              <div class="ent-card-icon is-amber"><i class="bi bi-lightbulb-fill"></i></div>
              <span class="ent-card-number">03</span>
            </div>
            <span class="ent-card-kicker" id="entFactCategory">Fakta pilihan</span>
            <h3>Tahukah kamu?</h3>
            <p id="entFactText">Menyiapkan fakta untukmu…</p>
            <button class="btn ent-btn-quiet" id="entNextFactBtn" type="button">
              <span>Fakta berikutnya</span>
              <i class="bi bi-arrow-right"></i>
            </button>
          </article>
        </div>
      </section>

      <section class="ent-section ent-games" id="entGameArena" aria-labelledby="gameTitle">
        <div class="ent-section-head">
          <div>
            <span class="ent-eyebrow">Koleksi permainan</span>
            <h2 id="gameTitle">Game Arena</h2>
            <p>Pilih permainan, pecahkan skor terbaik, lalu kumpulkan poin.</p>
          </div>
          <span class="ent-section-chip" id="entChallengeChip"><i class="bi bi-stars"></i> Tantangan harian</span>
        </div>

        <div class="ent-game-grid">
          <article class="ent-game-card" data-game-card="quiz">
            <div class="ent-game-cover cover-quiz">
              <span class="ent-game-type">Pengetahuan</span>
              <div class="ent-game-icon"><i class="bi bi-lightbulb-fill"></i></div>
              <span class="ent-game-index">01</span>
            </div>
            <div class="ent-game-copy">
              <h3>Kuis Harian</h3>
              <p>Jawab satu pertanyaan pilihan ganda dan uji wawasanmu.</p>
              <div class="ent-game-status" data-game-status="quiz"><i class="bi bi-circle"></i> Belum dimainkan</div>
            </div>
            <button class="btn ent-play-btn" type="button" data-open-game="quiz">
              <span>Mainkan</span><i class="bi bi-arrow-up-right"></i>
            </button>
          </article>

          <article class="ent-game-card" data-game-card="memory">
            <div class="ent-game-cover cover-memory">
              <span class="ent-game-type">Ingatan</span>
              <div class="ent-game-icon"><i class="bi bi-grid-3x3-gap-fill"></i></div>
              <span class="ent-game-index">02</span>
            </div>
            <div class="ent-game-copy">
              <h3>Memory Card</h3>
              <p id="entMemoryDescription">Temukan enam pasang kartu dengan langkah sesedikit mungkin.</p>
              <div class="ent-game-status" data-game-status="memory"><i class="bi bi-circle"></i> Belum dimainkan</div>
            </div>
            <button class="btn ent-play-btn" type="button" data-open-game="memory">
              <span>Mainkan</span><i class="bi bi-arrow-up-right"></i>
            </button>
          </article>

          <article class="ent-game-card" data-game-card="puzzle">
            <div class="ent-game-cover cover-puzzle">
              <span class="ent-game-type">Ketelitian</span>
              <div class="ent-game-icon"><i class="bi bi-puzzle-fill"></i></div>
              <span class="ent-game-index">03</span>
            </div>
            <div class="ent-game-copy">
              <h3>Puzzle Foto</h3>
              <p id="entPuzzleDescription">Susun sembilan potongan hingga menjadi gambar yang lengkap.</p>
              <div class="ent-game-status" data-game-status="puzzle"><i class="bi bi-circle"></i> Belum dimainkan</div>
            </div>
            <button class="btn ent-play-btn" type="button" data-open-game="puzzle">
              <span>Mainkan</span><i class="bi bi-arrow-up-right"></i>
            </button>
          </article>

          <article class="ent-game-card" data-game-card="wheel">
            <div class="ent-game-cover cover-wheel">
              <span class="ent-game-type">Bonus</span>
              <div class="ent-game-icon"><i class="bi bi-disc-fill"></i></div>
              <span class="ent-game-index">04</span>
            </div>
            <div class="ent-game-copy">
              <h3>Roda Kejutan</h3>
              <p>Putar sekali setiap hari untuk memperoleh bonus poin.</p>
              <div class="ent-game-status" data-game-status="wheel"><i class="bi bi-circle"></i> Belum diputar</div>
            </div>
            <button class="btn ent-play-btn" type="button" data-open-game="wheel">
              <span>Putar</span><i class="bi bi-arrow-up-right"></i>
            </button>
          </article>
        </div>
      </section>

      <section class="ent-stage-wrap" id="entStageWrap" hidden aria-labelledby="entStageTitle">
        <div class="ent-stage-head">
          <div>
            <span class="ent-eyebrow" id="entStageEyebrow">Permainan</span>
            <h2 id="entStageTitle">Mulai bermain</h2>
          </div>
          <button class="ent-icon-button ent-close-stage" type="button" id="entCloseStage" aria-label="Tutup permainan">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="ent-stage" id="entStage" data-puzzle-image="<?= e($puzzleImage) ?>"></div>
      </section>

      <section class="ent-bottom-grid">
        <article class="ent-card ent-leader-panel" aria-labelledby="leaderboardTitle">
          <div class="ent-card-head">
            <div class="ent-card-title-group">
              <span class="ent-card-kicker"><i class="bi bi-trophy"></i> Peringkat komunitas</span>
              <h2 id="leaderboardTitle">Papan Peringkat</h2>
              <p>Pemain dengan akumulasi poin tertinggi.</p>
            </div>
            <button class="ent-icon-button" type="button" id="entRefreshLeaderboard" aria-label="Muat ulang peringkat">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </div>
          <div class="ent-leaderboard" id="entLeaderboard">
            <div class="ent-list-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Menyiapkan peringkat…</div>
          </div>
        </article>

        <article class="ent-card ent-badge-panel" aria-labelledby="badgeTitle">
          <div class="ent-card-head">
            <div class="ent-card-title-group">
              <span class="ent-card-kicker"><i class="bi bi-award"></i> Koleksi pencapaian</span>
              <h2 id="badgeTitle">Koleksi Badge</h2>
              <p>Pencapaian yang sudah berhasil dibuka.</p>
            </div>
            <button class="ent-icon-button" type="button" data-open-profile aria-label="Atur badge unggulan">
              <i class="bi bi-person-gear"></i>
            </button>
          </div>
          <div class="ent-badge-grid" id="entBadgeGrid">
            <div class="ent-list-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Menyiapkan badge…</div>
          </div>
        </article>
      </section>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="entRenameModal" tabindex="-1" aria-labelledby="entRenameTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content ent-profile-modal">
      <form id="entRenameForm">
        <div class="modal-header">
          <div>
            <span class="ent-eyebrow">Pengaturan pemain</span>
            <h5 class="modal-title" id="entRenameTitle">Atur Profil</h5>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <label class="form-label" for="entNickname">Nama yang ditampilkan</label>
          <input class="form-control" id="entNickname" maxlength="32" minlength="3" autocomplete="nickname" placeholder="Contoh: Bintang Ceria">

          <fieldset class="mt-4">
            <legend class="form-label">Pilih avatar</legend>
            <div class="ent-choice-grid" id="entAvatarOptions"></div>
          </fieldset>

          <fieldset class="mt-4">
            <legend class="form-label">Pilih bingkai</legend>
            <div class="ent-frame-grid" id="entFrameOptions"></div>
          </fieldset>

          <label class="form-label mt-4" for="entFeaturedBadgeSelect">Badge unggulan</label>
          <select class="form-select" id="entFeaturedBadgeSelect">
            <option value="">Tidak ditampilkan</option>
          </select>

          <div class="ent-form-error" id="entRenameError" hidden></div>
        </div>

        <div class="modal-footer">
          <button class="btn ent-btn-primary w-100" type="submit">
            <i class="bi bi-check2-circle"></i>
            <span>Simpan profil</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="ent-toast" id="entToast" role="status" aria-live="polite" hidden>
  <i class="bi bi-check-circle-fill"></i><span></span>
</div>
