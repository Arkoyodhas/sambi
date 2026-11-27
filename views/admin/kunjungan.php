<?php
$labels = []; $pvMap = []; $uvMap = [];
foreach ($daily as $row) { $pvMap[$row['d']] = (int) $row['pv']; $uvMap[$row['d']] = (int) $row['uv']; }
$pvData = []; $uvData = [];
for ($i = $days - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($d));
    $pvData[] = $pvMap[$d] ?? 0;
    $uvData[] = $uvMap[$d] ?? 0;
}
$maxPage = max(1, (int) ($topPages[0]['total'] ?? 1));
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <p class="text-muted small mb-0">Data kunjungan halaman publik beserta perkiraan lokasi pengunjung (berdasarkan alamat IP).</p>
  <div class="btn-group" data-testid="visit-range-filter">
    <?php foreach ([7 => '7 Hari', 30 => '30 Hari', 90 => '90 Hari'] as $d => $l): ?>
    <a href="<?= url('admin/kunjungan?hari=' . $d) ?>" class="btn btn-sm <?= $days === $d ? 'btn-primary' : 'btn-outline-primary' ?>"><?= $l ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-3 mb-4">
  <?php foreach ([
      ['bi-person-check', (int) $today['uv'], 'Pengunjung Hari Ini'],
      ['bi-eye', (int) $today['pv'], 'Tayangan Hari Ini'],
      ['bi-people', (int) $totals['uv'], "Pengunjung $days Hari"],
      ['bi-bar-chart', (int) $totals['pv'], "Tayangan $days Hari"],
  ] as $i => [$ic, $val, $lab]): ?>
  <div class="col-6 col-md-3">
    <div class="stat-tile" data-testid="visit-stat-<?= $i ?>">
      <div class="ic"><i class="bi <?= $ic ?>"></i></div>
      <div><div class="val"><?= number_format($val, 0, ',', '.') ?></div><div class="lab"><?= $lab ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Grafik Kunjungan <?= $days ?> Hari Terakhir</h6></div>
      <div class="acard-body"><canvas id="visitChart" height="120" data-testid="visit-chart"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-phone me-2 text-success"></i>Perangkat</h6></div>
      <div class="acard-body small">
        <?php $totDev = max(1, array_sum(array_column($devices, 'total'))); foreach ($devices as $dv): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between"><span class="text-capitalize"><i class="bi <?= $dv['device'] === 'hp' ? 'bi-phone' : ($dv['device'] === 'tablet' ? 'bi-tablet' : 'bi-laptop') ?> me-1"></i><?= e($dv['device']) ?></span><strong><?= number_format((int) $dv['total'], 0, ',', '.') ?></strong></div>
          <div class="progress" style="height:6px"><div class="progress-bar" style="width:<?= round($dv['total'] / $totDev * 100) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$devices): ?><span class="text-muted">Belum ada data.</span><?php endif; ?>
        <?php if ($osStats): ?>
        <hr class="my-2">
        <div class="fw-bold mb-1 text-muted" style="font-size:.72rem;letter-spacing:.05em">SISTEM OPERASI</div>
        <?php foreach ($osStats as $osr): ?>
        <div class="d-flex justify-content-between mb-1"><span><i class="bi bi-cpu me-1"></i><?= e($osr['os']) ?></span><strong><?= number_format((int) $osr['total'], 0, ',', '.') ?></strong></div>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-fire me-2 text-danger"></i>Halaman Terpopuler</h6></div>
      <div class="acard-body small" data-testid="popular-pages">
        <?php foreach ($topPages as $p): ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between"><code class="small"><?= e($p['path']) ?></code><strong><?= number_format((int) $p['total'], 0, ',', '.') ?></strong></div>
          <div class="progress" style="height:6px"><div class="progress-bar bg-danger" style="width:<?= round($p['total'] / $maxPage * 100) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
        <?php if (!$topPages): ?><span class="text-muted">Belum ada data kunjungan.</span><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="acard h-100">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-geo-alt me-2 text-warning"></i>Lokasi Pengunjung Teratas</h6></div>
      <div class="acard-body p-0" data-testid="top-locations">
        <div class="table-responsive"><table class="table table-admin table-hover mb-0"><thead><tr><th>Kota</th><th>Provinsi</th><th>Negara</th><th class="text-end">Kunjungan</th></tr></thead><tbody>
          <?php foreach ($topLocations as $l): ?>
          <tr><td><?= e($l['city']) ?></td><td class="small text-muted"><?= e($l['region_name']) ?></td><td class="small text-muted"><?= e($l['country']) ?></td><td class="text-end"><strong><?= number_format((int) $l['total'], 0, ',', '.') ?></strong></td></tr>
          <?php endforeach; ?>
          <?php if (!$topLocations): ?><tr><td colspan="4" class="text-muted small p-3">Belum ada data lokasi.</td></tr><?php endif; ?>
        </tbody></table></div>
      </div>
    </div>
  </div>
</div>

<div class="acard mb-3">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-globe-asia-australia me-2 text-info"></i>Peta Sebaran Pengunjung</h6></div>
  <div class="acard-body p-2">
    <?php if ($geoPoints): ?>
    <div id="visitorMap" style="height:380px;border-radius:12px;overflow:hidden" data-testid="visitor-map"></div>
    <div class="form-text mt-2 px-1">Lingkaran lebih besar = kunjungan lebih banyak dari kota tersebut. Sumber lokasi: geolokasi alamat IP (perkiraan tingkat kota).</div>
    <?php else: ?>
    <p class="text-muted small p-3 mb-0">Belum ada data lokasi pengunjung dengan koordinat (kunjungan lokal/privat tidak memiliki koordinat). Data akan terisi otomatis seiring kunjungan publik masuk.</p>
    <?php endif; ?>
  </div>
</div>

<?php $blockedIps = array_column($blocked ?? [], 'ip'); ?>
<div class="acard mb-3">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-shield-slash me-2 text-danger"></i>Perangkat Diblokir (<?= count($blocked ?? []) ?>)</h6></div>
  <div class="acard-body p-0" data-testid="blocked-ips">
    <div class="table-responsive"><table class="table table-admin mb-0">
      <thead><tr><th>Alamat IP</th><th>Alasan</th><th>Oleh</th><th>Waktu</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
        <?php foreach (($blocked ?? []) as $b): ?>
        <tr>
          <td class="small"><code><?= e($b['ip']) ?></code></td>
          <td class="small"><?= e($b['reason'] ?: '-') ?></td>
          <td class="small"><?= e($b['admin_name'] ?: '-') ?></td>
          <td class="small text-nowrap"><?= date('d/m/Y H:i', strtotime($b['created_at'])) ?></td>
          <td class="text-end"><form method="post" action="<?= url('admin/kunjungan/buka') ?>" class="d-inline" onsubmit="return confirm('Buka blokir IP ini?')"><?= csrf_field() ?><input type="hidden" name="ip" value="<?= e($b['ip']) ?>"><button class="btn btn-sm btn-outline-success" data-testid="unblock-btn"><i class="bi bi-unlock"></i> Buka</button></form></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($blocked)): ?><tr><td colspan="5" class="text-muted small p-3">Belum ada perangkat yang diblokir.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <div class="p-3 border-top">
      <form method="post" action="<?= url('admin/kunjungan/blokir') ?>" class="row g-2 align-items-end" data-testid="block-form">
        <?= csrf_field() ?>
        <div class="col-sm-5"><label class="form-label small mb-1">Blokir IP manual</label><input class="form-control form-control-sm" name="ip" placeholder="cth: 103.10.20.30" required></div>
        <div class="col-sm-5"><label class="form-label small mb-1">Alasan (opsional)</label><input class="form-control form-control-sm" name="reason" placeholder="Spam / penyalahgunaan"></div>
        <div class="col-sm-2"><button class="btn btn-sm btn-danger w-100"><i class="bi bi-shield-x"></i> Blokir</button></div>
      </form>
    </div>
  </div>
</div>

<div class="acard">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-clock-history me-2 text-secondary"></i>50 Kunjungan Terakhir</h6></div>
  <div class="acard-body p-0" data-testid="recent-visits">
    <div class="table-responsive"><table class="table table-admin table-hover mb-0">
      <thead><tr><th>Waktu</th><th>Halaman</th><th>Alamat IP</th><th>Lokasi</th><th>Perangkat</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $v): ?>
        <tr>
          <td class="small text-nowrap"><?= date('d/m H:i', strtotime($v['visited_at'])) ?><?= $v['is_unique'] ? ' <span class="badge text-bg-success" title="Pengunjung baru hari itu">baru</span>' : '' ?></td>
          <td><code class="small"><?= e($v['path']) ?></code></td>
          <td class="small"><?= e($v['ip_address']) ?></td>
          <td class="small"><?= e(trim(implode(', ', array_filter([$v['city'], $v['region_name'], $v['country']]))) ?: '-') ?></td>
          <td class="small text-capitalize"><?= e($v['device']) ?> · <?= e($v['browser']) ?><?= !empty($v['os']) ? ' · ' . e($v['os']) : '' ?><?= !empty($v['language']) ? ' · ' . e($v['language']) : '' ?></td>
          <td class="text-end">
            <?php if (in_array($v['ip_address'], $blockedIps, true)): ?>
              <span class="badge text-bg-danger">Diblokir</span>
            <?php else: ?>
              <form method="post" action="<?= url('admin/kunjungan/blokir') ?>" class="d-inline" onsubmit="return confirm('Blokir IP <?= e($v['ip_address']) ?>? Perangkat ini tidak bisa lagi membuka situs.')"><?= csrf_field() ?><input type="hidden" name="ip" value="<?= e($v['ip_address']) ?>"><button class="btn btn-sm btn-outline-danger py-0" title="Blokir perangkat ini" data-testid="block-visit-btn"><i class="bi bi-shield-x"></i></button></form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="text-muted small p-3">Belum ada kunjungan tercatat.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
</div>

<?php
$inlineScript = '<script src="' . asset('vendor/chart/chart.umd.min.js') . '"></script><script>
new Chart(document.getElementById("visitChart"), {type:"line",
  data:{labels:' . json_encode($labels) . ',datasets:[
    {label:"Tayangan",data:' . json_encode($pvData) . ',borderColor:"#0f6b4e",backgroundColor:"rgba(15,107,78,.12)",fill:true,tension:.35,pointRadius:2},
    {label:"Pengunjung",data:' . json_encode($uvData) . ',borderColor:"#e8b44c",backgroundColor:"rgba(232,180,76,.12)",fill:true,tension:.35,pointRadius:2}
  ]},
  options:{plugins:{legend:{position:"bottom"}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
</script>';
if ($geoPoints) {
    $inlineScript .= '<link rel="stylesheet" href="' . asset('vendor/leaflet/leaflet.css') . '"><script src="' . asset('vendor/leaflet/leaflet.js') . '"></script><script>
(function(){
  var pts = ' . json_encode(array_map(fn($g) => ['lat' => (float) $g['lat'], 'lon' => (float) $g['lon'], 'c' => $g['city'], 'r' => $g['region_name'], 't' => (int) $g['total']], $geoPoints)) . ';
  var map = L.map("visitorMap", {scrollWheelZoom:false}).setView([pts[0].lat, pts[0].lon], 5);
  L.tileLayer("https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png", {attribution:"&copy; OpenStreetMap &copy; CARTO", maxZoom:19}).addTo(map);
  var max = Math.max.apply(null, pts.map(function(p){return p.t;}));
  var group = [];
  pts.forEach(function(p){
    var radius = 7 + 18 * Math.sqrt(p.t / max);
    L.circleMarker([p.lat, p.lon], {radius: radius, color:"#0f6b4e", weight:1.5, fillColor:"#e8b44c", fillOpacity:.55})
      .bindPopup("<b>" + p.c + "</b>" + (p.r ? ", " + p.r : "") + "<br>" + p.t + " kunjungan").addTo(map);
    group.push([p.lat, p.lon]);
  });
  if (group.length > 1) map.fitBounds(group, {padding:[30,30], maxZoom:8});
})();
</script>';
}
?>
