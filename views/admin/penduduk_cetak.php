<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Data Penduduk</title>
<style>
body{font-family:Arial,sans-serif;font-size:11px;margin:20px}
h3{margin:0}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{border:1px solid #444;padding:4px 6px;text-align:left}
th{background:#eee}
.no-print{margin:10px 0}
@media print{.no-print{display:none}}
</style>
</head>
<body>
<h3>DATA PENDUDUK — <?= e(strtoupper(setting('village_name'))) ?></h3>
<small><?= e(setting('kecamatan')) ?>, <?= e(setting('kabupaten')) ?> · Dicetak: <?= tgl_id(date('Y-m-d'), true) ?> · Total: <?= count($rows) ?> jiwa</small>
<div class="no-print"><button onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>
<table>
  <thead><tr><th>No</th><th>NIK</th><th>Nama</th><th>L/P</th><th>Tgl Lahir</th><th>Pendidikan</th><th>Pekerjaan</th><th>Dusun</th><th>RW/RT</th><th>Status</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $i => $r): ?>
    <tr>
      <td><?= $i + 1 ?></td><td><?= e($r['nik']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['jenis_kelamin']) ?></td>
      <td><?= e($r['tanggal_lahir']) ?></td><td><?= e($r['pendidikan']) ?></td><td><?= e($r['pekerjaan']) ?></td>
      <td><?= e($r['dusun_name']) ?></td><td><?= e($r['rw_name'] . '/' . $r['rt_name']) ?></td><td><?= e($r['status_penduduk']) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
