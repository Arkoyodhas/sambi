<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Surat - <?= e($r['ticket']) ?></title>
<style>
body{font-family:'Times New Roman',serif;font-size:13pt;max-width:800px;margin:30px auto;padding:0 30px;color:#000}
.kop{display:flex;align-items:center;gap:16px;border-bottom:4px double #000;padding-bottom:10px;margin-bottom:24px;text-align:center}
.kop img{height:80px}
.kop .teks{flex:1}
.kop h4,.kop h3{margin:0;text-transform:uppercase}
.kop small{font-size:10pt}
.isi{white-space:pre-wrap;line-height:1.7;text-align:justify}
.ttd{margin-top:50px;width:280px;margin-left:auto;text-align:center}
.ttd .nama{margin-top:80px;font-weight:bold;text-decoration:underline}
.no-print{margin:16px 0}
@media print{.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button></div>
<div class="kop">
  <?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" alt="Logo"><?php endif; ?>
  <div class="teks">
    <h4>PEMERINTAH <?= e(strtoupper(setting('kabupaten'))) ?></h4>
    <h4><?= e(strtoupper(setting('kecamatan'))) ?></h4>
    <h3><?= e(strtoupper(setting('village_name'))) ?></h3>
    <small><?= e(setting('address')) ?> · Telp: <?= e(setting('phone')) ?> · Email: <?= e(setting('email')) ?></small>
  </div>
</div>
<div class="isi"><?= nl2br(e($isi)) ?></div>
<div class="ttd">
  Kepala <?= e(setting('village_name')) ?>,
  <div class="nama"><?= e(setting('kepala_desa_nama')) ?></div>
</div>
</body>
</html>
