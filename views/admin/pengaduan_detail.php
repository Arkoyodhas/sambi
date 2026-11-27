<div class="row g-3">
  <div class="col-lg-7">
    <div class="acard mb-3">
      <div class="acard-head">
        <h6 class="mb-0"><?= e($c['ticket']) ?> <?php if ($c['priority']): ?><span class="badge text-bg-danger ms-1">Prioritas</span><?php endif; ?></h6>
        <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/pengaduan') ?>"><i class="bi bi-arrow-left"></i></a>
      </div>
      <div class="acard-body">
        <table class="table table-admin table-sm mb-3">
          <tr><td class="text-muted" style="width:140px">Status</td><td><?= status_badge($c['status']) ?></td></tr>
          <tr><td class="text-muted">Pelapor</td><td><?= e($c['nama']) ?> <?= $c['is_anonymous'] ? '<span class="badge text-bg-secondary">identitas disembunyikan dari publik</span>' : '' ?></td></tr>
          <tr><td class="text-muted">Kontak</td><td><?= e($c['kontak'] ?: '-') ?></td></tr>
          <tr><td class="text-muted">Kategori</td><td><?= e($c['category']) ?></td></tr>
          <tr><td class="text-muted">Tanggal</td><td><?= tgl_id($c['created_at'], true) ?></td></tr>
        </table>
        <p class="border rounded p-3 bg-light small"><?= nl2br(e($c['content'])) ?></p>
        <?php if ($c['attachment']): ?><a href="<?= e(upload_url($c['attachment'])) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-paperclip me-1"></i>Lihat Bukti Lampiran</a><?php endif; ?>
      </div>
    </div>
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Riwayat Tanggapan</h6></div>
      <div class="acard-body">
        <?php foreach ($responses as $resp): ?>
        <div class="border-start border-3 border-success ps-3 mb-3">
          <p class="small mb-1"><?= nl2br(e($resp['response'])) ?></p>
          <small class="text-muted"><?= e($resp['admin_name'] ?: 'Petugas') ?> · <?= tgl_id($resp['created_at'], true) ?></small>
        </div>
        <?php endforeach; ?>
        <?php if (!$responses): ?><p class="text-muted small mb-0">Belum ada tanggapan.</p><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard mb-3">
      <div class="acard-head"><h6 class="mb-0">Balas Pengaduan</h6></div>
      <div class="acard-body">
        <form method="post" action="<?= url('admin/pengaduan/balas/' . $c['id']) ?>">
          <?= csrf_field() ?>
          <div class="mb-3"><textarea class="form-control" name="response" rows="4" required placeholder="Tulis tanggapan (tampil di halaman cek tiket warga)..." data-testid="pengaduan-response"></textarea></div>
          <div class="mb-3">
            <label class="form-label">Sekaligus ubah status menjadi:</label>
            <select class="form-select" name="set_status">
              <option value="">(tidak diubah)</option>
              <?php foreach (['diproses', 'selesai', 'ditolak'] as $s): ?><option value="<?= $s ?>"><?= ucfirst($s) ?></option><?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary w-100" data-testid="pengaduan-reply-btn"><i class="bi bi-reply me-1"></i>Kirim Balasan</button>
        </form>
      </div>
    </div>
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Tindakan Cepat</h6></div>
      <div class="acard-body d-grid gap-2">
        <form method="post" action="<?= url('admin/pengaduan/status/' . $c['id']) ?>"><?= csrf_field() ?>
          <input type="hidden" name="toggle_priority" value="1">
          <button class="btn btn-outline-warning w-100"><i class="bi bi-flag me-1"></i><?= $c['priority'] ? 'Hapus Tanda Prioritas' : 'Tandai Prioritas' ?></button>
        </form>
        <form method="post" action="<?= url('admin/pengaduan/status/' . $c['id']) ?>"><?= csrf_field() ?>
          <input type="hidden" name="toggle_anonymous" value="1">
          <button class="btn btn-outline-secondary w-100"><i class="bi bi-incognito me-1"></i><?= $c['is_anonymous'] ? 'Tampilkan Identitas di Publik' : 'Sembunyikan Identitas Pelapor' ?></button>
        </form>
        <form method="post" action="<?= url('admin/pengaduan/delete/' . $c['id']) ?>" data-confirm="Tutup tiket ini?"><?= csrf_field() ?>
          <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Tutup Tiket</button>
        </form>
      </div>
    </div>
  </div>
</div>
