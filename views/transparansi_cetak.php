<?php
// Halaman print-friendly. window.print() otomatis dipanggil; user pilih "Save as PDF" di dialog browser.
$printCss = '<style>@page{margin:15mm}body{font-family:Arial,sans-serif;color:#000;font-size:12pt;line-height:1.4}h1{font-size:22pt;margin:0 0 6pt 0}h2{font-size:16pt;margin:16pt 0 6pt 0;border-bottom:1px solid #666;padding-bottom:4pt}table{width:100%;border-collapse:collapse;margin:8pt 0;font-size:10pt}th,td{border:1px solid #666;padding:5pt;text-align:left;vertical-align:top}th{background:#eee;font-weight:bold}.text-end{text-align:right}.hd{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #000;padding-bottom:8pt;margin-bottom:12pt}.hd .r{font-size:9pt;text-align:right}.sig{margin-top:40pt;display:flex;justify-content:space-between}.sig .s{width:32%;text-align:center}.sig .s .line{margin-top:60pt;border-top:1px solid #000;padding-top:4pt}.no-print{margin-bottom:12pt}@media print{.no-print{display:none!important}}</style>';
$year = (int)($tahun ?? date('Y'));
?>
<!doctype html>
<html lang="id"><head><meta charset="utf-8"><title><?= e($title) ?> - <?= e($siteName) ?></title>
<?= $printCss ?>
</head><body onload="setTimeout(function(){window.print();},400)" data-testid="print-page">
<div class="no-print" style="text-align:right;padding:10px;background:#f0f0f0"><button onclick="window.print()" style="padding:8px 20px;background:#0d6efd;color:#fff;border:none;border-radius:4px;cursor:pointer">🖨️ Cetak / Simpan sebagai PDF</button> <button onclick="window.close()" style="padding:8px 20px;background:#666;color:#fff;border:none;border-radius:4px;cursor:pointer">Tutup</button></div>

<div class="hd">
  <div><h1><?= e($title) ?></h1><div style="font-size:11pt"><?= e($siteName) ?></div></div>
  <div class="r"><strong>Tahun: <?= $year ?></strong><br>Dicetak: <?= date('d/m/Y H:i') ?></div>
</div>

<?php if ($kind === 'idm'): ?>
<h2>Data Indeks Desa Membangun</h2>
<table><thead><tr><th>Tahun</th><th class="text-end">IKS</th><th class="text-end">IKE</th><th class="text-end">IKL</th><th class="text-end">IDM</th><th>Catatan</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?>
<tr><td><?= (int)$r['tahun'] ?></td><td class="text-end"><?= number_format((float)$r['iks'],4) ?></td><td class="text-end"><?= number_format((float)$r['ike'],4) ?></td><td class="text-end"><?= number_format((float)$r['ikl'],4) ?></td><td class="text-end"><strong><?= number_format((float)$r['idm'],4) ?></strong></td><td><?= e($r['catatan'] ?? '-') ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<?php elseif ($kind === 'bansos'): ?>
<h2>Program Bantuan Sosial <?= $year ?></h2>
<table><thead><tr><th>Program</th><th>Sumber</th><th class="text-end">Nilai/Penerima</th><th class="text-end">Jml Penerima</th></tr></thead><tbody>
<?php foreach ($programs as $p): ?>
<tr><td><strong><?= e($p['nama']) ?></strong></td><td><?= e($p['sumber_dana'] ?: '-') ?></td><td class="text-end">Rp <?= number_format((int)$p['nilai_per_penerima'],0,',','.') ?></td><td class="text-end"><?= (int)$p['jml'] ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<h2>Daftar Penerima</h2>
<table><thead><tr><th>#</th><th>Program</th><th>Nama</th><th>NIK</th><th>Alamat</th><th>RT/RW</th></tr></thead><tbody>
<?php foreach ($recipients as $i => $r): ?>
<tr><td><?= $i+1 ?></td><td><?= e($r['program']) ?></td><td><?= e($r['nama']) ?></td><td><?= e($r['nik'] ? substr(preg_replace('/\D/','',$r['nik']),0,4).'********'.substr(preg_replace('/\D/','',$r['nik']),-4) : '-') ?></td><td><?= e($r['alamat'] ?: '-') ?></td><td><?= e(($r['rt']??'-').'/'.($r['rw']??'-')) ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<?php elseif ($kind === 'stunting'):
$statusLabel = ['normal'=>'Normal','pendek'=>'Pendek','sangat_pendek'=>'Sangat Pendek','beresiko'=>'Beresiko']; ?>
<h2>Ringkasan Stunting <?= $year ?></h2>
<table><thead><tr><th>Status</th><th class="text-end">Jumlah</th></tr></thead><tbody>
<?php foreach ($byStatus as $s): ?>
<tr><td><?= e($statusLabel[$s['status']] ?? $s['status']) ?></td><td class="text-end"><?= (int)$s['total'] ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<h2>Sebaran per RT/RW</h2>
<table><thead><tr><th>RT</th><th>RW</th><th class="text-end">Total Balita</th><th class="text-end">Stunting/Beresiko</th><th class="text-end">Persentase</th></tr></thead><tbody>
<?php foreach ($byRt as $r): $pct = $r['total']>0 ? round($r['stunting']/$r['total']*100,1) : 0; ?>
<tr><td><?= e($r['rt'] ?: '-') ?></td><td><?= e($r['rw'] ?: '-') ?></td><td class="text-end"><?= (int)$r['total'] ?></td><td class="text-end"><?= (int)$r['stunting'] ?></td><td class="text-end"><?= $pct ?>%</td></tr>
<?php endforeach; ?>
</tbody></table>

<?php else: ?>
<h2>Ringkasan APBDes Tahun <?= $year ?></h2>
<table><thead><tr><th>Jenis</th><th>Kategori</th><th class="text-end">Anggaran (Rp)</th><th class="text-end">Realisasi (Rp)</th><th>Sumber</th></tr></thead><tbody>
<?php foreach (($budgets ?? []) as $b): ?>
<tr><td><?= e($b['type']) ?></td><td><?= e($b['category'] ?? '-') ?></td><td class="text-end"><?= number_format((float)$b['budget_amount'],0,',','.') ?></td><td class="text-end"><?= number_format((float)$b['realization_amount'],0,',','.') ?></td><td><?= e($b['source'] ?? '-') ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<h2>Proyek Pembangunan Tahun <?= $year ?></h2>
<table><thead><tr><th>Nama Proyek</th><th class="text-end">Anggaran</th><th>Status</th><th>Sumber</th></tr></thead><tbody>
<?php foreach (($projects ?? []) as $p): ?>
<tr><td><?= e($p['project_name'] ?? $p['title'] ?? '-') ?></td><td class="text-end"><?= number_format((float)($p['budget'] ?? $p['budget_amount'] ?? 0),0,',','.') ?></td><td><?= e($p['status'] ?? '-') ?></td><td><?= e($p['source'] ?? '-') ?></td></tr>
<?php endforeach; ?>
</tbody></table>
<?php endif; ?>

<div class="sig"><div class="s"><small>Mengetahui,</small><div class="line">Kepala Desa</div></div><div class="s"><small>Diperiksa,</small><div class="line">Sekretaris Desa</div></div><div class="s"><small>Dicetak,</small><div class="line">Operator</div></div></div>
</body></html>
