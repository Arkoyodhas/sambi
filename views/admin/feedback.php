<?php
$totalActive = 0; foreach ($questions as $q) if ((int)$q['is_active']) $totalActive++;
?>
<div class="row g-3 mb-4">
  <?php foreach ([
    ['bi-chat-square-text', $total, 'Total Respons', 'primary'],
    ['bi-calendar-check', $today, 'Respons Hari Ini', 'success'],
    ['bi-list-check', $totalActive, 'Pertanyaan Aktif', 'warning'],
  ] as $i => [$ic, $val, $lab, $col]): ?>
  <div class="col-md-4 col-6"><div class="stat-tile"><div class="ic text-<?= $col ?>"><i class="bi <?= $ic ?>"></i></div><div><div class="val"><?= number_format((int)$val,0,',','.') ?></div><div class="lab"><?= $lab ?></div></div></div></div>
  <?php endforeach; ?>
</div>

<div class="acard mb-4">
  <div class="acard-head d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h6 class="mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Pengaturan & Pertanyaan Popup</h6>
    <a href="<?= url('admin/feedback/cetak') ?>" class="btn btn-sm btn-outline-primary" target="_blank" data-testid="feedback-export-pdf"><i class="bi bi-file-earmark-pdf me-1"></i>Ekspor PDF Semua Respons</a>
  </div>
  <div class="acard-body">
    <form method="post" action="<?= url('admin/feedback/simpan') ?>" data-testid="feedback-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
          <label class="form-label small">Popup Aktif</label>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="enabled" value="1" <?= $enabled ? 'checked' : '' ?> data-testid="feedback-enabled"> <label class="form-check-label small">Tampilkan ke pengunjung</label></div>
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">Muncul Setelah (detik)</label>
          <input type="number" class="form-control form-control-sm" name="delay" value="<?= (int)$delay ?>" min="3" max="120" data-testid="feedback-delay">
        </div>
        <div class="col-md-6">
          <label class="form-label small">Kalimat Pembuka Popup</label>
          <input type="text" class="form-control form-control-sm" name="intro" value="<?= e($intro) ?>" maxlength="400">
        </div>
      </div>
      <table class="table table-sm align-middle">
        <thead class="small text-muted"><tr><th>#</th><th>Label Pertanyaan</th><th>Tipe</th><th style="width:200px">Pilihan (baris per opsi)</th><th class="text-center">Wajib</th><th class="text-center">Aktif</th><th class="text-end">Aksi</th></tr></thead>
        <tbody id="qBody">
          <?php foreach ($questions as $i => $q): ?>
          <tr>
            <td><input type="hidden" name="id[<?= $i ?>]" value="<?= (int)$q['id'] ?>"><input type="number" name="order[<?= $i ?>]" value="<?= (int)$q['sort_order'] ?>" class="form-control form-control-sm" style="width:60px"></td>
            <td><input type="text" name="label[<?= $i ?>]" class="form-control form-control-sm" value="<?= e($q['label']) ?>" required></td>
            <td>
              <select name="type[<?= $i ?>]" class="form-select form-select-sm">
                <?php foreach (['rating'=>'Rating 1-5','choice'=>'Pilihan','text'=>'Teks pendek','textarea'=>'Teks panjang'] as $tv=>$tl): ?>
                <option value="<?= $tv ?>" <?= $q['type']===$tv?'selected':'' ?>><?= $tl ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><textarea name="options[<?= $i ?>]" class="form-control form-control-sm" rows="2" placeholder="Baris per opsi"><?= e(implode("\n", json_decode($q['options']??'[]', true) ?: [])) ?></textarea></td>
            <td class="text-center"><input type="checkbox" name="required[]" value="<?= $i ?>" <?= (int)$q['required']?'checked':'' ?>></td>
            <td class="text-center"><input type="checkbox" name="active[]" value="<?= $i ?>" <?= (int)$q['is_active']?'checked':'' ?>></td>
            <td class="text-end">
              <button type="submit" formaction="<?= url('admin/feedback/hapus_pertanyaan') ?>" formnovalidate name="id" value="<?= (int)$q['id'] ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('Hapus pertanyaan ini?')"><i class="bi bi-trash"></i></button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-primary" onclick="addQ()" data-testid="feedback-add-question"><i class="bi bi-plus-lg"></i> Tambah Pertanyaan</button>
      <button class="btn btn-sm btn-primary float-end" data-testid="feedback-save"><i class="bi bi-check2 me-1"></i>Simpan Semua</button>
    </form>
  </div>
</div>

<div class="acard">
  <div class="acard-head"><h6 class="mb-0"><i class="bi bi-inbox me-2 text-primary"></i>100 Respons Terbaru</h6></div>
  <div class="acard-body p-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="small text-muted"><tr><th class="ps-3">Waktu</th><th>Nama/Email</th><th>Jawaban Ringkas</th><th>IP</th><th class="text-end pe-3">Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($responses as $r): $ans = json_decode($r['answers'], true) ?: []; ?>
          <tr data-testid="feedback-row">
            <td class="ps-3 small text-muted"><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td>
            <td class="small"><?= e($r['respondent_name'] ?: '-') ?></td>
            <td class="small" style="max-width:400px"><?php foreach ($ans as $k=>$v): ?><div class="text-truncate"><span class="text-muted">Q<?= (int)$k ?>:</span> <?= e(is_array($v)?implode(', ',$v):(string)$v) ?></div><?php endforeach; ?></td>
            <td><code class="small"><?= e($r['ip_address']) ?></code></td>
            <td class="text-end pe-3">
              <form method="post" action="<?= url('admin/feedback/hapus_respons') ?>" class="d-inline" onsubmit="return confirm('Hapus respons ini?')">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-link text-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$responses): ?><tr><td colspan="5" class="text-center text-muted py-4">Belum ada respons feedback.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){
  var body = document.getElementById('qBody');
  window.addQ = function(){
    var idx = body.querySelectorAll('tr').length;
    var row = '<tr><td><input type="hidden" name="id['+idx+']" value="0"><input type="number" name="order['+idx+']" value="'+idx+'" class="form-control form-control-sm" style="width:60px"></td><td><input type="text" name="label['+idx+']" class="form-control form-control-sm" placeholder="Tulis pertanyaan..." required></td><td><select name="type['+idx+']" class="form-select form-select-sm"><option value="rating">Rating 1-5</option><option value="choice">Pilihan</option><option value="text">Teks pendek</option><option value="textarea">Teks panjang</option></select></td><td><textarea name="options['+idx+']" class="form-control form-control-sm" rows="2" placeholder="Baris per opsi"></textarea></td><td class="text-center"><input type="checkbox" name="required[]" value="'+idx+'" checked></td><td class="text-center"><input type="checkbox" name="active[]" value="'+idx+'" checked></td><td></td></tr>';
    body.insertAdjacentHTML('beforeend', row);
  };
})();
</script>
