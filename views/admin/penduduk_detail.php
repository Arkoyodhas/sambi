<div class="row g-3">
  <div class="col-lg-7">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0">Detail Penduduk</h6>
        <div class="d-flex gap-2 flex-wrap">
          <?php if ($familyId): ?>
          <a class="btn btn-sm btn-outline-success" href="<?= url('admin/keluarga/detail/' . $familyId) ?>"><i class="bi bi-people me-1"></i>Lihat Keluarga</a>
          <?php endif; ?>
          <a class="btn btn-sm btn-outline-primary" href="<?= url('admin/penduduk/edit/' . $r['id']) ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
          <a class="btn btn-sm btn-outline-secondary" href="<?= url('admin/penduduk') ?>"><i class="bi bi-arrow-left"></i></a>
        </div>
      </div>
      <div class="acard-body">
        <table class="table table-admin table-sm mb-0">
          <?php foreach ([
              'NIK' => $r['nik'], 'Nomor KK' => $r['no_kk'], 'Nama' => $r['nama'],
              'Tempat, Tgl Lahir' => trim(($r['tempat_lahir'] ?: '') . ', ' . tgl_id($r['tanggal_lahir'])) . ' (' . umur($r['tanggal_lahir']) . ' th)',
              'Jenis Kelamin' => $r['jenis_kelamin'] === 'P' ? 'Perempuan' : 'Laki-laki',
              'Agama' => $r['agama'], 'Pendidikan' => $r['pendidikan'], 'Pekerjaan' => $r['pekerjaan'],
              'Status Perkawinan' => $r['status_perkawinan'], 'Hubungan Keluarga' => $r['hubungan_keluarga'],
              'Alamat' => $r['alamat'], 'Wilayah' => trim(($r['dusun_name'] ?: '') . ' ' . ($r['rw_name'] ?: '') . ' ' . ($r['rt_name'] ?: '')),
              'No. HP' => $r['no_hp'], 'Email' => $r['email'], 'Keterangan' => $r['keterangan'],
          ] as $lab => $v): ?>
          <tr><td class="text-muted" style="width:180px"><?= $lab ?></td><td><strong class="small"><?= e($v ?: '-') ?></strong></td></tr>
          <?php endforeach; ?>
          <tr><td class="text-muted">Status</td><td><?= status_badge($r['status_penduduk']) ?></td></tr>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="acard">
      <div class="acard-head"><h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Riwayat Perubahan</h6></div>
      <div class="acard-body p-0">
        <table class="table table-admin mb-0"><tbody>
          <?php foreach ($riwayat as $l): ?>
          <tr><td><span class="badge text-bg-light border"><?= e($l['action']) ?></span></td>
            <td class="small"><?= e($l['user_name'] ?: 'Sistem') ?><br><small class="text-muted"><?= tgl_id($l['created_at'], true) ?></small></td></tr>
          <?php endforeach; ?>
          <?php if (!$riwayat): ?><tr><td class="text-muted small p-3">Belum ada riwayat tercatat.</td></tr><?php endif; ?>
        </tbody></table>
      </div>
    </div>
  </div>
</div>
