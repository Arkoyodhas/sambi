<?php $phTitle = 'Statistik Desa'; $phDesc = 'Data agregat kependudukan yang diperbarui otomatis dari basis data desa.'; include BASE_PATH . '/views/partials/page_header.php'; ?>
<section class="stat-strip" style="margin-top:-40px">
  <div class="container">
    <div class="row g-3">
      <?php foreach ([['bi-people-fill', $total, 'Total Penduduk'], ['bi-house-heart-fill', $kk, 'Kepala Keluarga'], ['bi-gender-male', $laki, 'Laki-laki'], ['bi-gender-female', $perempuan, 'Perempuan']] as $i => [$ic, $num, $lab]): ?>
      <div class="col-6 col-lg-3" data-aos="fade-up" data-aos-delay="<?= $i * 80 ?>">
        <div class="stat-card">
          <div class="icon"><i class="bi <?= $ic ?>"></i></div>
          <div class="num counter" data-count="<?= (int) $num ?>"><span class="num-skel" aria-hidden="true"></span><noscript><?= number_format((int) $num, 0, ',', '.') ?></noscript></div>
          <div class="label"><?= $lab ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<section class="section pt-4">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Kelompok Umur</h6><canvas id="chartUmur" height="220"></canvas></div></div>
      <div class="col-lg-6" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Jenis Kelamin</h6><canvas id="chartJK" height="220"></canvas></div></div>
      <div class="col-lg-6" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Pendidikan</h6><canvas id="chartPendidikan" height="240"></canvas></div></div>
      <div class="col-lg-6" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Pekerjaan (10 Teratas)</h6><canvas id="chartPekerjaan" height="240"></canvas></div></div>
      <div class="col-lg-4" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Agama</h6><canvas id="chartAgama" height="240"></canvas></div></div>
      <div class="col-lg-4" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Status Perkawinan</h6><canvas id="chartKawin" height="240"></canvas></div></div>
      <div class="col-lg-4" data-aos="fade-up"><div class="card-3d card-body"><h6 class="mb-3">Penduduk per Dusun</h6><canvas id="chartDusun" height="240"></canvas></div></div>
    </div>
    <p class="text-muted small mt-4"><i class="bi bi-shield-check me-1"></i>Demi perlindungan data pribadi, halaman ini hanya menampilkan data agregat. NIK, nomor KK, dan data pribadi penduduk tidak dipublikasikan.</p>
  </div>
</section>
<?php
$needsChart = true;
$chartData = [
    'umur' => ['labels' => array_keys($umur), 'data' => array_values($umur)],
    'jk' => [(int) $laki, (int) $perempuan],
    'pendidikan' => ['labels' => array_column($pendidikan, 'label'), 'data' => array_map('intval', array_column($pendidikan, 'total'))],
    'pekerjaan' => ['labels' => array_column($pekerjaan, 'label'), 'data' => array_map('intval', array_column($pekerjaan, 'total'))],
    'agama' => ['labels' => array_column($agama, 'label'), 'data' => array_map('intval', array_column($agama, 'total'))],
    'kawin' => ['labels' => array_column($kawin, 'label'), 'data' => array_map('intval', array_column($kawin, 'total'))],
    'dusun' => ['labels' => array_column($dusun, 'label'), 'data' => array_map('intval', array_column($dusun, 'total'))],
];
$inlineScript = '<script>
document.addEventListener("DOMContentLoaded", function(){
  var D = ' . json_encode($chartData) . ';
  var css = getComputedStyle(document.documentElement);
  var P = css.getPropertyValue("--c-primary").trim() || "#0f6b4e";
  var A = css.getPropertyValue("--c-accent").trim() || "#e8b44c";
  var palette = [P, A, "#2a9d8f", "#e76f51", "#457b9d", "#8338ec", "#ff006e", "#6a994e", "#bc6c25", "#118ab2"];
  Chart.defaults.color = css.getPropertyValue("--text").trim() || "#333";
  Chart.defaults.font.family = css.getPropertyValue("--font-body") || "sans-serif";
  function bar(id, labels, data, horizontal){
    new Chart(document.getElementById(id), {type:"bar", data:{labels:labels, datasets:[{data:data, backgroundColor:P, borderRadius:8}]},
      options:{indexAxis: horizontal?"y":"x", plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}}, y:{grid:{color:"rgba(128,128,128,.12)"}}}}});
  }
  function pie(id, labels, data, doughnut){
    new Chart(document.getElementById(id), {type: doughnut?"doughnut":"pie", data:{labels:labels, datasets:[{data:data, backgroundColor:palette}]},
      options:{plugins:{legend:{position:"bottom", labels:{boxWidth:12, font:{size:11}}}}}});
  }
  bar("chartUmur", D.umur.labels, D.umur.data);
  pie("chartJK", ["Laki-laki","Perempuan"], D.jk, true);
  bar("chartPendidikan", D.pendidikan.labels, D.pendidikan.data, true);
  bar("chartPekerjaan", D.pekerjaan.labels, D.pekerjaan.data, true);
  pie("chartAgama", D.agama.labels, D.agama.data);
  pie("chartKawin", D.kawin.labels, D.kawin.data, true);
  bar("chartDusun", D.dusun.labels, D.dusun.data);
});
</script>';
?>
