<div class="row g-3">
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head">
        <h6 class="mb-0"><?= e($r['ticket']) ?> — <?= e($r['service_name']) ?></h6>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/permohonan') ?>"><i class="bi bi-arrow-left"></i></a>
      </div>
      <div class="acard-body">
        <table class="table table-admin table-sm">
          <tr><td class="text-muted" style="width:150px">Status</td><td><?= status_badge($r['status']) ?></td></tr>
          <tr><td class="text-muted">NIK</td><td class="font-monospace"><?= e($r['nik']) ?></td></tr>
          <tr><td class="text-muted">Nama</td><td><strong><?= e($r['nama']) ?></strong></td></tr>
          <tr><td class="text-muted">Kontak</td><td><?= e($r['no_hp'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Alamat</td><td><?= e($r['alamat'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Keperluan</td><td><?= nl2br(e($r['keperluan'])) ?></td></tr>
          <tr><td class="text-muted">Diajukan</td><td><?= tgl_id($r['created_at'], true) ?></td></tr>
          <?php if ($r['attachment']): ?><tr><td class="text-muted">Lampiran</td><td><a href="<?= e(upload_url($r['attachment'])) ?>" target="_blank"><i class="bi bi-paperclip"></i> Lihat lampiran</a></td></tr><?php endif; ?>
        </table>
        <?php if ($r['status'] === 'selesai'): ?>
        <a class="btn btn-success" href="<?= url('admin/permohonan/cetak/' . $r['id']) ?>" target="_blank" data-testid="cetak-surat-btn"><i class="bi bi-printer me-1"></i>Cetak Surat (PDF)</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Proses Permohonan</h6></div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/permohonan/status/' . $r['id']) ?>">
          <?= csrf_field() ?>
          <div class="mb-3"><label class="form-label">Ubah Status</label>
            <select class="form-select" name="status" data-testid="permohonan-status">
              <?php foreach (['baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai', 'ditolak' => 'Ditolak'] as $sv => $sl): ?>
              <option value="<?= $sv ?>" <?= $r['status'] === $sv ? 'selected' : '' ?>><?= $sl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3"><label class="form-label">Nomor Surat</label>
            <input class="form-control" name="letter_number" value="<?= e($r['letter_number']) ?>" placeholder="otomatis saat status Selesai jika kosong"></div>
          <div class="mb-3"><label class="form-label">Catatan untuk Pemohon</label>
            <textarea class="form-control" name="admin_note" rows="3" placeholder="Catatan tampil saat warga cek status tiket"><?= e($r['admin_note']) ?></textarea></div>
          <button class="btn btn-primary w-100" data-testid="permohonan-save"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
        </form>
      </div>
    </div>
  </div>
</div>
