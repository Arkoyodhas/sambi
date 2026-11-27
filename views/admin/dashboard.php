<?php
$statCards = [];
if (!empty($access['population'])) {
    $statCards[] = ['bi-people-fill', $stats['penduduk'] ?? 0, 'Penduduk', 'admin/penduduk'];
    $statCards[] = ['bi-house-heart-fill', $stats['kk'] ?? 0, 'Kepala Keluarga', 'admin/keluarga'];
    $statCards[] = ['bi-gender-male', $stats['laki'] ?? 0, 'Laki-laki', 'admin/penduduk?jk=L'];
    $statCards[] = ['bi-gender-female', $stats['perempuan'] ?? 0, 'Perempuan', 'admin/penduduk?jk=P'];
}
if (!empty($access['news'])) $statCards[] = ['bi-newspaper', $stats['berita'] ?? 0, 'Berita', 'admin/berita'];
if (!empty($access['services'])) $statCards[] = ['bi-inbox', $stats['layanan_baru'] ?? 0, 'Permohonan Baru', 'admin/permohonan?status=baru'];
if (!empty($access['complaints'])) {
    $statCards[] = ['bi-chat-square-text', $stats['pengaduan_baru'] ?? 0, 'Pengaduan Baru', 'admin/pengaduan?status=diterima'];
    $statCards[] = ['bi-envelope', $stats['pesan'] ?? 0, 'Pesan Belum Dibaca', 'admin/pesan'];
}
?>

<?php if ($statCards): ?>
<div class="row g-3 mb-4">
  <?php foreach ($statCards as [$ic, $val, $lab, $link]): ?>
  <div class="col-6 col-md-3">
    <a href="<?= url($link) ?>" class="text-decoration-none text-reset">
      <div class="stat-tile" data-testid="dash-stat-<?= slugify($lab) ?>">
        <div class="ic"><i class="bi <?= $ic ?>"></i></div>
        <div><div class="val"><?= number_format((int) $val, 0, ',', '.') ?></div><div class="lab"><?= e($lab) ?></div></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($access['audit'])): ?>
<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="acard h-100">
      <div class="acard-head">
        <h6 class="mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Kunjungan Website 30 Hari</h6>
        <span class="small text-muted">Hari ini: <b><?= number_format((int) ($visitToday['uv'] ?? 0), 0, ',', '.') ?></b> pengunjung · <b><?= number_format((int) ($visitToday['pv'] ?? 0), 0, ',', '.') ?></b> tayangan · <a href="<?= url('admin/kunjungan') ?>">Detail</a></span>
      </div>
      <div class="acard-body"><canvas id="visitDashChart" height="105"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-fire me-2 text-danger"></i>Halaman Terpopuler</h6></div>
      <div class="acard-body small">
        <?php $maxTop = max(1, (int) ($visitTop[0]['total'] ?? 1)); foreach ($visitTop as $tp): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between"><code class="small"><?= e($tp['path']) ?></code><strong><?= number_format((int) $tp['total'], 0, ',', '.') ?></strong></div>
          <div class="progress" style="height:5px"><div class="progress-bar bg-danger" style="width:<?= round($tp['total'] / $maxTop * 100) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$visitTop): ?><span class="text-muted">Belum ada data kunjungan.</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($access['music']) || !empty($access['event']) || !empty($access['news'])): ?>
<div class="row g-3 mb-4">
  <?php if (!empty($access['music']) || !empty($access['event'])): ?>
  <div class="<?= !empty($access['news']) ? 'col-md-6' : 'col-12' ?>">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-music-note-beamed me-2 text-success"></i>Status Musik & Event</h6></div>
      <div class="acard-body small">
        <?php if (!empty($access['music'])): ?>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Musik website</span>
          <?php if (setting('music_enabled') === '1' && $activeMusic): ?>
          <span class="badge text-bg-success"><i class="bi bi-play-fill"></i> <?= e($activeMusic['title']) ?></span>
          <?php else: ?><span class="badge text-bg-secondary">Nonaktif</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($access['event'])): ?>
        <div class="d-flex justify-content-between py-1">
          <span class="text-muted">Event effect aktif</span>
          <?php if ($activeEvent): ?><span class="badge text-bg-warning text-dark"><?= e($activeEvent['event_name']) ?></span>
          <?php else: ?><span class="badge text-bg-secondary">Tidak ada</span><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="mt-2 d-flex gap-2">
          <?php if (!empty($access['music'])): ?><a href="<?= url('admin/musik') ?>" class="btn btn-sm btn-outline-primary">Kelola Musik</a><?php endif; ?>
          <?php if (!empty($access['event'])): ?><a href="<?= url('admin/event') ?>" class="btn btn-sm btn-outline-primary">Event Experience</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($access['news'])): ?>
  <div class="<?= (!empty($access['music']) || !empty($access['event'])) ? 'col-md-6' : 'col-12' ?>">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-calendar-event me-2 text-primary"></i>Agenda Terdekat</h6></div>
      <div class="acard-body small">
        <?php foreach ($agendas as $a): ?>
        <div class="d-flex justify-content-between py-1 border-bottom"><span><?= e($a['title']) ?></span><span class="text-muted text-nowrap ms-2"><?= tgl_id($a['event_date']) ?></span></div>
        <?php endforeach; ?>
        <?php if (!$agendas): ?><span class="text-muted">Tidak ada agenda terdekat.</span><?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($access['population']) || !empty($access['audit'])): ?>
<div class="row g-3 mb-4">
  <?php if (!empty($access['population'])): ?>
  <div class="<?= !empty($access['audit']) ? 'col-lg-5' : 'col-12' ?>">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0">Penduduk per Dusun</h6></div>
      <div class="acard-body"><canvas id="dashChart" height="210"></canvas></div>
    </div>
  </div>
  <?php endif; ?>
  <?php if (!empty($access['audit'])): ?>
  <div class="<?= !empty($access['population']) ? 'col-lg-7' : 'col-12' ?>">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0">Aktivitas Admin Terbaru</h6><a class="small" href="<?= url('admin/audit') ?>">Semua log</a></div>
      <div class="acard-body p-0"><div class="table-responsive"><table class="table table-admin table-hover mb-0"><tbody>
        <?php foreach ($recentLogs as $l): ?>
        <tr><td><span class="badge text-bg-light border"><?= e($l['action']) ?></span></td><td class="small"><?= e(excerpt($l['description'], 60)) ?></td><td class="small text-muted text-nowrap"><?= e($l['user_name'] ?: 'Sistem') ?><br><?= date('d/m H:i', strtotime($l['created_at'])) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$recentLogs): ?><tr><td class="text-muted small p-3">Belum ada aktivitas.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($access['services']) || !empty($access['complaints'])): ?>
<div class="row g-3">
  <?php if (!empty($access['services'])): ?>
  <div class="<?= !empty($access['complaints']) ? 'col-lg-6' : 'col-12' ?>">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Permohonan Layanan Terbaru</h6><a class="small" href="<?= url('admin/permohonan') ?>">Semua</a></div>
      <div class="acard-body p-0"><div class="table-responsive"><table class="table table-admin mb-0"><tbody>
        <?php foreach ($recentRequests as $r): ?><tr><td><a href="<?= url('admin/permohonan/detail/' . $r['id']) ?>"><?= e($r['ticket']) ?></a><br><small class="text-muted"><?= e($r['service_name']) ?></small></td><td class="small"><?= e($r['nama']) ?></td><td><?= status_badge($r['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$recentRequests): ?><tr><td class="text-muted small p-3">Belum ada permohonan.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!empty($access['complaints'])): ?>
  <div class="<?= !empty($access['services']) ? 'col-lg-6' : 'col-12' ?>">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Pengaduan Terbaru</h6><a class="small" href="<?= url('admin/pengaduan') ?>">Semua</a></div>
      <div class="acard-body p-0"><div class="table-responsive"><table class="table table-admin mb-0"><tbody>
        <?php foreach ($recentComplaints as $c): ?><tr><td><a href="<?= url('admin/pengaduan/detail/' . $c['id']) ?>"><?= e($c['ticket']) ?></a><br><small class="text-muted"><?= e($c['category']) ?></small></td><td class="small"><?= e(excerpt($c['content'], 40)) ?></td><td><?= status_badge($c['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$recentComplaints): ?><tr><td class="text-muted small p-3">Belum ada pengaduan.</td></tr><?php endif; ?>
      </tbody></table></div></div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$statCards && empty($access['audit']) && empty($access['music']) && empty($access['event']) && empty($access['news']) && empty($access['population']) && empty($access['services']) && empty($access['complaints'])): ?>
<div class="acard">
  <div class="acard-head"><h6 class="mb-0">Selamat Bekerja</h6></div>
  <div class="acard-body"><p class="text-muted mb-0">Gunakan menu di samping untuk mengelola konten sesuai hak akses Anda. Data yang tidak termasuk izin akun ini tidak dimuat ke dashboard.</p></div>
</div>
<?php endif; ?>

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
<script>
(function(){
  var prompt = document.getElementById('faceSetupPrompt'); if (!prompt) return;
  document.body.classList.add('face-setup-open'); prompt.classList.add('is-visible');
  function closePrompt(){ document.body.classList.remove('face-setup-open'); prompt.classList.remove('is-visible'); setTimeout(function(){ if (prompt.parentNode) prompt.parentNode.removeChild(prompt); }, 200); }
  prompt.querySelectorAll('[data-face-dismiss]').forEach(function(el){ el.addEventListener('click', closePrompt); });
  document.addEventListener('keydown', function(ev){ if (ev.key === 'Escape' && prompt.isConnected) closePrompt(); });
})();
</script>
<?php endif; ?>

<?php
$charts = '';
if (!empty($access['population']) || !empty($access['audit'])) {
    $charts .= '<script src="' . e(asset('vendor/chart/chart.umd.min.js')) . '"></script><script>';
    if (!empty($access['population'])) {
        $charts .= 'var dc=document.getElementById("dashChart");if(dc)new Chart(dc,{type:"doughnut",data:{labels:' . json_encode(array_column($dusunChart, 'label')) . ',datasets:[{data:' . json_encode(array_map('intval', array_column($dusunChart, 'total'))) . '}]},options:{plugins:{legend:{position:"bottom"}}}});';
    }
    if (!empty($access['audit'])) {
        $vLabels=[];$vPv=[];$vUv=[];$pvMap=[];$uvMap=[];
        foreach ($visitDaily as $vd) { $pvMap[$vd['d']] = (int)$vd['pv']; $uvMap[$vd['d']] = (int)$vd['uv']; }
        for ($i=29;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$vLabels[]=date('d/m',strtotime($d));$vPv[]=$pvMap[$d]??0;$vUv[]=$uvMap[$d]??0;}
        $charts .= 'var vc=document.getElementById("visitDashChart");if(vc)new Chart(vc,{type:"line",data:{labels:' . json_encode($vLabels) . ',datasets:[{label:"Tayangan",data:' . json_encode($vPv) . ',fill:true,tension:.35,pointRadius:2},{label:"Pengunjung",data:' . json_encode($vUv) . ',fill:true,tension:.35,pointRadius:2}]},options:{plugins:{legend:{position:"bottom"}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});';
    }
    $charts .= '</script>';
}
$inlineScript = $charts;
?>
