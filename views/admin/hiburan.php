<?php
$gameLabels = [
  'quiz' => 'Kuis',
  'memory' => 'Memory Card',
  'puzzle' => 'Puzzle Foto',
  'wheel' => 'Roda Kejutan',
  'read_news' => 'Membaca berita',
  'open_gallery' => 'Membuka galeri',
  'read_info' => 'Membaca informasi',
  'survey' => 'Mengisi survei',
  'poll_vote' => 'Polling cepat',
  'story_complete' => 'Cerita interaktif',
  'fact_read' => 'Kartu fakta',
  'daily_box' => 'Kotak harian',
  'profile_update' => 'Profil pemain',
  'lainnya' => 'Aktivitas lain',
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h1 class="h3 mb-1"><i class="bi bi-controller me-2 text-primary"></i>Zona Hiburan</h1>
    <p class="text-muted mb-0">Kelola permainan, aktivitas interaktif, profil pemain, statistik, dan konten kuis.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a href="<?= url('hiburan') ?>" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i> Lihat Zona Hiburan</a>
    <div class="dropdown">
      <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download me-1"></i> Export CSV</button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= url('admin/hiburan/export?type=devices') ?>">Data perangkat</a></li>
        <li><a class="dropdown-item" href="<?= url('admin/hiburan/export?type=events') ?>">Riwayat aktivitas</a></li>
        <li><a class="dropdown-item" href="<?= url('admin/hiburan/export?type=ips') ?>">Riwayat IP</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="alert alert-info d-flex gap-2 align-items-start">
  <i class="bi bi-database-check fs-5"></i>
  <div>
    <strong>Retensi tanpa penghapusan otomatis aktif.</strong>
    Data tidak dihapus oleh aplikasi, tetapi tetap dapat hilang karena kerusakan server, penghapusan database, atau kegagalan hosting. Gunakan tombol Export CSV dan backup database hosting secara rutin.
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
    ['bi-phone', number_format((int)$stats['total_devices'],0,',','.'), 'Perangkat tersimpan'],
    ['bi-broadcast', number_format((int)$stats['active_today'],0,',','.'), 'Aktif hari ini'],
    ['bi-controller', number_format((int)$stats['games_today'],0,',','.'), 'Permainan hari ini'],
    ['bi-cursor-fill', number_format((int)($stats['interactions_today'] ?? 0),0,',','.'), 'Interaksi hari ini'],
    ['bi-stars', number_format((int)$stats['points_total'],0,',','.'), 'Total poin'],
  ] as [$icon,$number,$label]): ?>
  <div class="col-6 col-xl">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <span class="admin-ent-stat-icon"><i class="bi <?= e($icon) ?>"></i></span>
        <div><div class="h4 mb-0 fw-bold"><?= e($number) ?></div><small class="text-muted"><?= e($label) ?></small></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <div class="col-xl-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0"><i class="bi bi-sliders me-2"></i>Pengaturan Fitur</h2></div>
      <div class="card-body">
        <form method="post" action="<?= url('admin/hiburan/simpan') ?>">
          <?= csrf_field() ?>
          <?php foreach ([
            'entertainment_enabled' => ['Zona Hiburan aktif','Tampilkan tombol dan izinkan akses publik',$settings['enabled']],
            'entertainment_quiz_enabled' => ['Kuis Harian','Satu soal per perangkat setiap hari',$settings['quiz']],
            'entertainment_memory_enabled' => ['Memory Card','Game mencocokkan kartu',$settings['memory']],
            'entertainment_puzzle_enabled' => ['Puzzle Foto','Game menyusun gambar website',$settings['puzzle']],
            'entertainment_wheel_enabled' => ['Roda Kejutan','Hadiah poin virtual satu kali sehari',$settings['wheel']],
            'entertainment_leaderboard_enabled' => ['Papan Peringkat','Skor, streak, badge, dan kontribusi pemain',$settings['leaderboard']],
            'entertainment_activity_points_enabled' => ['Poin Aktivitas Website','Baca berita, buka galeri, informasi, login, dan survei',$settings['activity_points']],
            'entertainment_poll_enabled' => ['Polling Cepat','Pertanyaan singkat dengan hasil langsung',$settings['poll']],
            'entertainment_story_enabled' => ['Cerita Interaktif','Cerita bercabang berdasarkan pilihan pengguna',$settings['story']],
            'entertainment_facts_enabled' => ['Kartu Fakta','Fakta, tips, dan pengetahuan acak',$settings['facts']],
            'entertainment_profile_enabled' => ['Avatar & Profil','Nama, avatar, bingkai, dan badge unggulan',$settings['profile']],
            'entertainment_daily_box_enabled' => ['Kotak Kejutan Harian','Bonus poin satu kali setiap hari',$settings['daily_box']],
          ] as $key => [$title,$desc,$checked]): ?>
          <label class="admin-ent-toggle">
            <span><strong><?= e($title) ?></strong><small><?= e($desc) ?></small></span>
            <input class="form-check-input" type="checkbox" name="<?= e($key) ?>" value="1" <?= $checked?'checked':'' ?>>
          </label>
          <?php endforeach; ?>
          <button class="btn btn-primary w-100 mt-3" type="submit"><i class="bi bi-save me-1"></i> Simpan Pengaturan</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-xl-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0"><i class="bi bi-bar-chart me-2"></i>Aktivitas 30 Hari</h2></div>
      <div class="card-body">
        <?php if (!$stats['game_totals']): ?>
          <div class="text-muted text-center py-5">Belum ada aktivitas permainan.</div>
        <?php else: ?>
          <div class="admin-ent-game-totals">
          <?php $maxTotal=max(array_map(fn($r)=>(int)$r['total'],$stats['game_totals'])); foreach ($stats['game_totals'] as $g): ?>
            <div class="admin-ent-game-row">
              <div class="d-flex justify-content-between gap-2 mb-1">
                <strong><?= e($gameLabels[$g['game_type']] ?? ucfirst($g['game_type'])) ?></strong>
                <span><?= number_format((int)$g['total'],0,',','.') ?> aktivitas · <?= number_format((int)$g['points'],0,',','.') ?> poin</span>
              </div>
              <div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= $maxTotal?round(((int)$g['total']/$maxTotal)*100):0 ?>%"></div></div>
            </div>
          <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <h2 class="h6 mb-0"><i class="bi bi-trophy me-2"></i>Perangkat & Peringkat</h2>
    <span class="badge text-bg-light"><?= count($stats['top']) ?> data ditampilkan</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>#</th><th>Perangkat</th><th>IP terakhir</th><th>Device</th><th class="text-end">Poin</th><th>Streak</th><th>Aktivitas</th></tr></thead>
      <tbody>
      <?php if (!$stats['top']): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada perangkat Zona Hiburan.</td></tr>
      <?php else: foreach ($stats['top'] as $i=>$d): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>
            <strong><?= e($d['nickname'] ?: 'Pemain #'.substr($d['token_hint'],0,6)) ?></strong>
            <small class="d-block text-muted">Token hash: <?= e(substr($d['token_hint'],0,8)) ?>…</small>
          </td>
          <td><code><?= e($d['ip_address']) ?></code></td>
          <td><?= e($d['device']) ?> · <?= e($d['browser']) ?> · <?= e($d['os']) ?></td>
          <td class="text-end fw-bold"><?= number_format((int)$d['total_points'],0,',','.') ?></td>
          <td>🔥 <?= (int)$d['current_streak'] ?> <small class="text-muted">(maks. <?= (int)$d['longest_streak'] ?>)</small></td>
          <td><small><?= tgl_id($d['last_seen_at'],true) ?></small></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card border-0 shadow-sm mb-4" id="quizQuestions">
  <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h2 class="h6 mb-1"><i class="bi bi-lightbulb me-2"></i>Soal Kuis Harian</h2>
      <small class="text-muted">Perangkat menerima satu soal aktif per hari secara bergiliran.</small>
    </div>
    <button class="btn btn-primary btn-sm" type="button" id="addQuizQuestion"><i class="bi bi-plus-lg me-1"></i> Tambah Soal</button>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th style="min-width:280px">Pertanyaan</th><th>Jawaban</th><th>Urutan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($questions as $q): ?>
        <tr>
          <td><strong><?= e($q['question']) ?></strong><small class="d-block text-muted"><?= e($q['explanation'] ?: 'Tanpa penjelasan') ?></small></td>
          <td><span class="badge text-bg-success"><?= e($q['correct_option']) ?></span> <?= e($q['option_'.strtolower($q['correct_option'])]) ?></td>
          <td><?= (int)$q['sort_order'] ?></td>
          <td><?= $q['is_active']?'<span class="badge text-bg-success">Aktif</span>':'<span class="badge text-bg-secondary">Nonaktif</span>' ?></td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-primary js-edit-question" type="button"
              data-question="<?= e($q['question']) ?>"
              data-id="<?= (int)$q['id'] ?>"
              data-a="<?= e($q['option_a']) ?>"
              data-b="<?= e($q['option_b']) ?>"
              data-c="<?= e($q['option_c']) ?>"
              data-d="<?= e($q['option_d']) ?>"
              data-correct="<?= e($q['correct_option']) ?>"
              data-explanation="<?= e($q['explanation']) ?>"
              data-order="<?= (int)$q['sort_order'] ?>"
              data-active="<?= (int)$q['is_active'] ?>"><i class="bi bi-pencil"></i></button>
            <form class="d-inline" method="post" action="<?= url('admin/hiburan/question-delete') ?>" onsubmit="return confirm('Hapus atau nonaktifkan soal ini?')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$q['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-4">
  <div class="col-xl-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0"><i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru</h2></div>
      <div class="table-responsive admin-ent-scroll-table">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Perangkat</th><th>Aktivitas</th><th>Skor/Poin</th><th>IP</th><th>Waktu</th></tr></thead>
          <tbody>
          <?php if (!$stats['recent']): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada aktivitas.</td></tr>
          <?php else: foreach ($stats['recent'] as $r): ?>
            <tr>
              <td><?= e($r['nickname'] ?: 'Pemain #'.substr($r['token_hint'],0,6)) ?></td>
              <td><?= e($r['event_type']) ?><?= $r['game_type']?' · '.e($gameLabels[$r['game_type']]??$r['game_type']):'' ?></td>
              <td><?= (int)$r['score'] ?> / <strong>+<?= (int)$r['points'] ?></strong></td>
              <td><code><?= e($r['ip_address']) ?></code></td>
              <td><small><?= tgl_id($r['created_at'],true) ?></small></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-transparent py-3"><h2 class="h6 mb-0"><i class="bi bi-router me-2"></i>Riwayat IP Perangkat</h2></div>
      <div class="table-responsive admin-ent-scroll-table">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Perangkat</th><th>IP</th><th>Kunjungan</th><th>Terakhir</th></tr></thead>
          <tbody>
          <?php if (!$stats['ip_logs']): ?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada riwayat IP.</td></tr>
          <?php else: foreach ($stats['ip_logs'] as $r): ?>
            <tr>
              <td><?= e($r['nickname'] ?: 'Pemain #'.substr($r['token_hint'],0,6)) ?></td>
              <td><code><?= e($r['ip_address']) ?></code></td>
              <td><?= number_format((int)$r['visit_count'],0,',','.') ?></td>
              <td><small><?= tgl_id($r['last_seen_at'],true) ?></small></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="quizQuestionModal" tabindex="-1" aria-labelledby="quizQuestionTitle" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= url('admin/hiburan/question-save') ?>" id="quizQuestionForm">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="qqId">
        <div class="modal-header">
          <h5 class="modal-title" id="quizQuestionTitle">Tambah Soal Kuis</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <label class="form-label" for="qqQuestion">Pertanyaan</label>
          <textarea class="form-control" name="question" id="qqQuestion" rows="2" maxlength="500" required></textarea>
          <div class="row g-3 mt-1">
            <?php foreach (['A','B','C','D'] as $opt): ?>
            <div class="col-md-6">
              <label class="form-label" for="qq<?= $opt ?>">Pilihan <?= $opt ?></label>
              <input class="form-control" name="option_<?= strtolower($opt) ?>" id="qq<?= $opt ?>" maxlength="255" required>
            </div>
            <?php endforeach; ?>
            <div class="col-md-4">
              <label class="form-label" for="qqCorrect">Jawaban benar</label>
              <select class="form-select" name="correct_option" id="qqCorrect">
                <?php foreach (['A','B','C','D'] as $opt): ?><option value="<?= $opt ?>"><?= $opt ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="qqOrder">Urutan</label>
              <input class="form-control" type="number" name="sort_order" id="qqOrder" min="0" max="9999" value="0">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_active" id="qqActive" value="1" checked> <span class="form-check-label">Soal aktif</span></label>
            </div>
          </div>
          <label class="form-label mt-3" for="qqExplanation">Penjelasan jawaban</label>
          <textarea class="form-control" name="explanation" id="qqExplanation" rows="2" maxlength="500"></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i> Simpan Soal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.admin-ent-stat-icon{display:grid;width:46px;height:46px;place-items:center;flex:0 0 46px;border-radius:14px;background:rgba(15,107,78,.1);color:var(--c-primary);font-size:1.25rem}
.admin-ent-toggle{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.8rem 0;border-bottom:1px solid rgba(0,0,0,.07);cursor:pointer}
.admin-ent-toggle:last-of-type{border-bottom:0}.admin-ent-toggle strong,.admin-ent-toggle small{display:block}.admin-ent-toggle small{color:#6c757d;font-size:.75rem;margin-top:.15rem}
.admin-ent-game-totals{display:grid;gap:1rem}.admin-ent-game-row span{color:#6c757d;font-size:.75rem}.admin-ent-scroll-table{max-height:520px}
@media(max-width:575.98px){.admin-ent-toggle{align-items:flex-start}.admin-ent-scroll-table{max-height:420px}}
</style>

<?php
$inlineScript = '<script>
(function(){
  "use strict";
  var modalEl=document.getElementById("quizQuestionModal"),modal=bootstrap.Modal.getOrCreateInstance(modalEl);
  function resetForm(){
    document.getElementById("quizQuestionForm").reset();
    document.getElementById("qqId").value="";
    document.getElementById("qqActive").checked=true;
    document.getElementById("quizQuestionTitle").textContent="Tambah Soal Kuis";
  }
  document.getElementById("addQuizQuestion").addEventListener("click",function(){resetForm();modal.show();});
  Array.prototype.forEach.call(document.querySelectorAll(".js-edit-question"),function(btn){
    btn.addEventListener("click",function(){
      resetForm();
      document.getElementById("quizQuestionTitle").textContent="Edit Soal Kuis";
      document.getElementById("qqId").value=btn.dataset.id||"";
      document.getElementById("qqQuestion").value=btn.dataset.question||"";
      document.getElementById("qqA").value=btn.dataset.a||"";
      document.getElementById("qqB").value=btn.dataset.b||"";
      document.getElementById("qqC").value=btn.dataset.c||"";
      document.getElementById("qqD").value=btn.dataset.d||"";
      document.getElementById("qqCorrect").value=btn.dataset.correct||"A";
      document.getElementById("qqExplanation").value=btn.dataset.explanation||"";
      document.getElementById("qqOrder").value=btn.dataset.order||"0";
      document.getElementById("qqActive").checked=btn.dataset.active==="1";
      modal.show();
    });
  });
}());
</script>';
?>
