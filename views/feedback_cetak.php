<?php
$printCss = '<style>@page{margin:15mm}body{font-family:Arial,sans-serif;color:#000;font-size:11pt;line-height:1.4}h1{font-size:20pt;margin:0 0 6pt 0}h2{font-size:14pt;margin:14pt 0 6pt 0;border-bottom:1px solid #666;padding-bottom:4pt}table{width:100%;border-collapse:collapse;margin:6pt 0;font-size:10pt}th,td{border:1px solid #666;padding:4pt;text-align:left;vertical-align:top}th{background:#eee;font-weight:bold}.hd{display:flex;justify-content:space-between;border-bottom:2px solid #000;padding-bottom:8pt;margin-bottom:12pt}.no-print{margin-bottom:12pt}@media print{.no-print{display:none!important}}</style>';
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Laporan Feedback</title><?= $printCss ?></head>
<body onload="setTimeout(function(){window.print();},400)" data-testid="feedback-print">
<div class="no-print" style="text-align:right;padding:10px;background:#f0f0f0"><button onclick="window.print()" style="padding:8px 20px;background:#0d6efd;color:#fff;border:none;border-radius:4px;cursor:pointer">🖨️ Cetak / Simpan sebagai PDF</button></div>
<div class="hd"><div><h1>Laporan Feedback Pengunjung</h1><div><?= e($siteName) ?></div></div><div style="text-align:right;font-size:9pt"><strong><?= count($rows) ?> respons</strong><br>Dicetak: <?= date('d/m/Y H:i') ?></div></div>

<h2>Pertanyaan Aktif</h2>
<table><thead><tr><th>#</th><th>Pertanyaan</th><th>Tipe</th></tr></thead><tbody>
<?php foreach ($questions as $i => $q): ?>
<tr><td><?= $i+1 ?></td><td><?= e($q['label']) ?></td><td><?= e($q['type']) ?></td></tr>
<?php endforeach; ?>
</tbody></table>

<h2>Daftar Respons</h2>
<table><thead><tr><th>#</th><th>Waktu</th><th>Nama/Email</th><th>Jawaban</th></tr></thead><tbody>
<?php foreach ($rows as $i => $r): $ans = json_decode($r['answers'], true) ?: []; ?>
<tr>
  <td><?= $i+1 ?></td>
  <td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
  <td><?= e($r['respondent_name'] ?: '-') ?></td>
  <td>
    <?php foreach ($ans as $qid => $val): $q = $qMap[$qid] ?? null; ?>
      <div><strong><?= e($q['label'] ?? ('Q'.$qid)) ?>:</strong> <?= e(is_array($val)?implode(', ',$val):(string)$val) ?></div>
    <?php endforeach; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="4" style="text-align:center">Belum ada respons.</td></tr><?php endif; ?>
</tbody></table>
</body></html>
