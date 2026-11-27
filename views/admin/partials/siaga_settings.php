<?php $siagaColor = setting_color('btn_siaga_color', '#DC2626'); ?>
<div class="acard mb-3" data-testid="siaga-settings-card">
  <div class="acard-head"><h3 class="h6 mb-0"><i class="bi bi-broadcast-pin me-1"></i> Tombol Siaga di Beranda</h3></div>
  <div class="acard-body">
    <form method="post" action="<?= url('admin/kontak-darurat/tombol') ?>" class="row g-3 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-3 col-6">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="siagaEnabled" name="siaga_enabled" value="1" <?= setting('siaga_enabled', '1') === '1' ? 'checked' : '' ?> data-testid="siaga-enabled-switch">
          <label class="form-check-label" for="siagaEnabled">Tampilkan tombol</label>
        </div>
      </div>
      <div class="col-md-4 col-6">
        <label class="form-label small mb-1" for="siagaLabel">Teks tombol</label>
        <input class="form-control" id="siagaLabel" name="siaga_label" maxlength="30" value="<?= e(setting('siaga_label', 'Siaga Darurat')) ?>" data-testid="siaga-label-input">
      </div>
      <div class="col-md-2 col-6">
        <label class="form-label small mb-1" for="siagaColor">Warna tombol</label>
        <input class="form-control form-control-color w-100" type="color" id="siagaColor" name="btn_siaga_color" value="<?= e($siagaColor) ?>" data-testid="siaga-color-input">
      </div>
      <div class="col-md-3 col-6 d-flex align-items-center gap-2">
        <span class="siaga-preview" id="siagaPreview" style="--c-btn-siaga:<?= e($siagaColor) ?>" aria-hidden="true"><i class="bi bi-broadcast-pin"></i></span>
        <button class="btn btn-primary" type="submit" data-testid="siaga-settings-save"><i class="bi bi-check2"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>
<style>.siaga-preview{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;color:#fff;background:var(--c-btn-siaga);box-shadow:0 0 0 6px color-mix(in srgb,var(--c-btn-siaga) 22%,transparent);flex:0 0 auto}</style>
<script>document.getElementById('siagaColor').addEventListener('input',function(e){document.getElementById('siagaPreview').style.setProperty('--c-btn-siaga',e.target.value);});</script>
