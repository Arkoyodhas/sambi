<?php
if (!function_exists('admin_guide_item')) {
    function admin_guide_item(string $icon, string $title, string $body, string $keywords = ''): void {
        static $number = 0;
        $number++;
        $id = 'guide-' . $number;
        $searchable = function_exists('mb_strtolower')
            ? mb_strtolower($title . ' ' . $keywords, 'UTF-8')
            : strtolower($title . ' ' . $keywords);
        echo '<div class="accordion-item guide-item" data-guide-title="' . e($searchable) . '">
          <h2 class="accordion-header" id="' . $id . '-head">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#' . $id . '"
              aria-expanded="false" aria-controls="' . $id . '">
              <i class="bi ' . e($icon) . ' me-2 text-primary"></i><span>' . e($title) . '</span>
            </button>
          </h2>
          <div id="' . $id . '" class="accordion-collapse collapse" aria-labelledby="' . $id . '-head">
            <div class="accordion-body small guide-body">' . $body . '</div>
          </div>
        </div>';
    }
}
if (!function_exists('admin_guide_label')) {
    function admin_guide_label(string $text, string $icon = 'bi-folder2-open'): void {
        echo '<h6 class="text-uppercase text-muted small fw-bold mt-4 mb-2 guide-label"><i class="bi ' . e($icon) . ' me-1"></i>' . e($text) . '</h6>';
    }
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
  <div>
    <h4 class="mb-1"><i class="bi bi-book me-2"></i>Panduan Penggunaan Admin</h4>
    <p class="text-muted small mb-0">Petunjuk fungsi dan langkah penggunaan seluruh fitur yang tersedia sesuai hak akses akun Anda.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-sm btn-outline-secondary" id="guideExpandAll"><i class="bi bi-arrows-expand me-1"></i>Buka Semua</button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-8">
    <div class="input-group">
      <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
      <input type="search" id="guideSearch" class="form-control" maxlength="80"
        placeholder="Cari panduan, misalnya: halaman statis, keluarga, berita, PWA…" data-testid="guide-search">
      <button type="button" class="btn btn-outline-secondary" id="guideClear" aria-label="Hapus pencarian"><i class="bi bi-x-lg"></i></button>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="alert alert-info py-2 px-3 mb-0 small h-100">
      <i class="bi bi-shield-check me-1"></i>Menu dan panduan otomatis menyesuaikan hak akses akun.
    </div>
  </div>
</div>

<div class="row g-3 mb-4 guide-summary">
  <div class="col-sm-6 col-xl-3"><div class="acard h-100"><div class="acard-body"><i class="bi bi-check2-circle text-success fs-4"></i><h6 class="mt-2 mb-1">Alur aman</h6><small class="text-muted">Isi data → periksa → simpan → lihat hasil publik.</small></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="acard h-100"><div class="acard-body"><i class="bi bi-trash3 text-warning fs-4"></i><h6 class="mt-2 mb-1">Sampah data</h6><small class="text-muted">Modul dengan soft delete dapat dipulihkan dari menu Sampah.</small></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="acard h-100"><div class="acard-body"><i class="bi bi-arrow-repeat text-primary fs-4"></i><h6 class="mt-2 mb-1">Data terintegrasi</h6><small class="text-muted">Penduduk, keluarga, statistik, dan beranda diperbarui otomatis.</small></div></div></div>
  <div class="col-sm-6 col-xl-3"><div class="acard h-100"><div class="acard-body"><i class="bi bi-life-preserver text-danger fs-4"></i><h6 class="mt-2 mb-1">Saat terjadi error</h6><small class="text-muted">Salin pesan error, jangan mengulang klik, dan cek Audit Log.</small></div></div></div>
</div>

<div class="accordion" id="guideAccordion" data-testid="guide-accordion">

<?php admin_guide_label('Mulai Menggunakan', 'bi-rocket-takeoff'); ?>
<?php admin_guide_item('bi-speedometer2', 'Dashboard dan rutinitas harian', '
<ol>
  <li>Login melalui <code>/admin</code>, lalu buka <b>Dashboard</b>.</li>
  <li>Periksa kartu ringkasan penduduk, keluarga, permohonan, pengaduan, pesan, berita, dokumen, dan perangkat.</li>
  <li>Buka daftar permohonan/pengaduan terbaru dari kartu atau menu sidebar.</li>
  <li>Periksa agenda mendatang, musik/event aktif, dan grafik kunjungan bila tersedia.</li>
  <li>Gunakan tombol <b>Lihat Website</b> untuk memastikan perubahan tampil dengan benar.</li>
</ol>
<div class="alert alert-light border py-2 mb-0"><b>Rutinitas:</b> cek layanan dan pengaduan setiap hari, perbarui konten sesuai kegiatan, lalu lakukan backup berkala.</div>', 'dashboard ringkasan rutinitas'); ?>

<?php admin_guide_item('bi-person-circle', 'Akun Saya, password, dan login wajah', '
<ul>
  <li>Buka menu pengguna di kanan atas → <b>Akun Saya</b>.</li>
  <li>Ubah nama tampilan dan nomor telepon, lalu simpan.</li>
  <li>Untuk mengganti password, isi password lama, password baru minimal 8 karakter, dan konfirmasi.</li>
  <li>Bila fitur login wajah tersedia, tambahkan sampel wajah dari perangkat berkamera. Gunakan pencahayaan cukup dan beberapa sudut wajah.</li>
  <li>Nonaktifkan atau hapus dataset wajah milik sendiri bila perangkat berubah atau autentikasi tidak akurat.</li>
</ul>
<div class="alert alert-warning py-2 mb-0">Jangan berbagi akun. Setiap petugas sebaiknya menggunakan akun masing-masing agar audit aktivitas jelas.</div>', 'akun password face login wajah'); ?>

<?php if (can('kelola_penduduk')): ?>
<?php admin_guide_label('Kependudukan', 'bi-people'); ?>
<?php admin_guide_item('bi-person-plus', 'Data Penduduk: tambah, edit, filter wilayah, hapus, dan pulihkan', '
<ol>
  <li>Buka <b>Data Penduduk</b>.</li>
  <li>Pada dropdown <b>Dusun dan RT yang tersedia</b>, setiap nama dusun langsung menampilkan daftar singkat RT serta jumlah penduduknya.</li>
  <li>Pilih dusun. Dropdown <b>RT</b> otomatis hanya menampilkan RT pada dusun tersebut.</li>
  <li>Pilih RT bila perlu, lalu tekan <b>Tampilkan</b>. Biarkan <b>Semua RT</b> untuk melihat seluruh penduduk dalam dusun terpilih.</li>
  <li>Gunakan pencarian untuk nama, NIK, atau Nomor KK. Buka <b>Filter lanjutan</b> hanya bila diperlukan.</li>
  <li>Ringkasan hasil, Excel, dan PDF mengikuti filter aktif. Tekan tombol panah melingkar untuk menghapus filter.</li>
  <li>Jika lokasi menampilkan <b>Belum Terpetakan</b>, buka data tersebut dan lengkapi Dusun/RT.</li>
  <li>Untuk menambah data, klik <b>Tambah</b>, isi NIK 16 digit dan identitas, lalu pilih KK. Alamat serta wilayah dapat mengikuti data keluarga.</li>
  <li>Bila Nomor KK belum terdaftar, sistem dapat membuat atau menghubungkan keluarga secara otomatis.</li>
  <li>Data yang dihapus masuk ke <b>Sampah</b> dan dapat dipulihkan.</li>
</ol>
<div class="alert alert-light border py-2 mb-0"><b>Tampilan responsif:</b> pada ponsel setiap dropdown tersusun vertikal agar label dan isi tidak bertabrakan. <b>Dampak otomatis:</b> statistik publik dan dashboard mengikuti data penduduk aktif.</div>', 'nik warga dusun rw rt filter wilayah detail restore excel pdf'); ?>

<?php admin_guide_item('bi-house-heart', 'Data Keluarga: membuat KK dan mengelola anggota', '
<ol>
  <li>Buka <b>Data Keluarga</b> → <b>Tambah Keluarga</b>.</li>
  <li>Pilih salah satu cara: hubungkan penduduk yang sudah ada sebagai kepala keluarga, atau buat kepala keluarga baru sekaligus.</li>
  <li>Isi Nomor KK 16 digit, alamat, dusun/RW/RT, dan informasi keluarga.</li>
  <li>Setelah disimpan, buka <b>Detail Keluarga</b> untuk menambah anggota, mengubah hubungan keluarga, atau memindahkan anggota.</li>
  <li>Sistem menjaga satu kepala keluarga utama dan memperbarui KK asal/tujuan ketika anggota dipindahkan.</li>
  <li>Keluarga yang masih mempunyai anggota tidak dapat dihapus agar tidak meninggalkan data tanpa hubungan.</li>
</ol>
<div class="alert alert-info py-2 mb-0"><b>Perbaiki Otomatis:</b> gunakan tombol sinkronisasi bila indikator relasi menunjukkan masalah. Sistem membangun ulang hubungan penduduk–keluarga tanpa menghapus data utama.</div>', 'kk kepala anggota sinkron keluarga'); ?>

<?php admin_guide_item('bi-geo-alt', 'Data Wilayah: Dusun, RW, dan RT', '
<ol>
  <li>Tambahkan Dusun terlebih dahulu.</li>
  <li>Tambahkan RW dan pilih Dusun sebagai wilayah induk.</li>
  <li>Tambahkan RT dan pilih RW sebagai wilayah induk.</li>
  <li>Isi nama kepala wilayah/ketua bila diperlukan.</li>
  <li>Gunakan penamaan konsisten, misalnya <code>RW 01</code> dan <code>RT 003</code>.</li>
</ol>
Data wilayah dipakai oleh formulir penduduk, keluarga, filter, statistik, bansos, dan stunting.', 'dusun rw rt wilayah induk'); ?>

<?php if (can('import_penduduk')): ?>
<?php admin_guide_item('bi-file-earmark-arrow-up', 'Import dan export Excel penduduk', '
<ol>
  <li>Buka <b>Import Excel</b> dan unduh template terbaru.</li>
  <li>Isi data tanpa mengubah nama/urutan kolom. Simpan sebagai <code>.xlsx</code>.</li>
  <li>Upload file, lalu periksa preview: baris valid, data yang akan diperbarui, dan kesalahan.</li>
  <li>Perbaiki semua baris bermasalah sebelum menekan <b>Simpan Import</b>.</li>
  <li>Import menggunakan transaksi; kegagalan tidak meninggalkan data setengah tersimpan.</li>
  <li>Riwayat import dapat diperiksa pada Audit → Import.</li>
</ol>
<b>Export:</b> gunakan tombol Export pada Data Penduduk. File mengikuti filter aktif.', 'xlsx template preview import export'); ?>
<?php endif; ?>
<?php endif; ?>

<?php if (can('kelola_berita') || can('kelola_galeri')): ?>
<?php admin_guide_label('Konten dan Informasi Publik', 'bi-newspaper'); ?>
<?php endif; ?>

<?php if (can('kelola_berita')): ?>
<?php admin_guide_item('bi-newspaper', 'Berita: menulis, menerbitkan, tag, dan komentar', '
<ol>
  <li>Buka <b>Berita</b> → <b>Tambah Berita</b>.</li>
  <li>Isi judul, kategori, isi berita, ringkasan, thumbnail, tag, dan metadata SEO.</li>
  <li>Slug dibuat otomatis dari judul, tetapi dapat disesuaikan sebelum terbit.</li>
  <li>Pilih status <b>Draft</b> untuk menyimpan tanpa menayangkan atau <b>Published</b> untuk menerbitkan.</li>
  <li>Atur tanggal terbit dan pilihan komentar.</li>
  <li>Lihat halaman publik untuk memeriksa gambar, paragraf, tautan, dan tampilan ponsel.</li>
</ol>
Berita yang dihapus masuk ke Sampah dan dapat dipulihkan.', 'artikel draft published tag thumbnail seo'); ?>

<?php admin_guide_item('bi-tags', 'Kategori Berita dan moderasi Komentar', '
<ul>
  <li><b>Kategori:</b> buat kategori yang konsisten agar berita mudah difilter, misalnya Pemerintahan, Pembangunan, Kesehatan, dan Kegiatan.</li>
  <li><b>Komentar:</b> aktif/nonaktifkan komentar melalui saklar. Setujui komentar yang layak dan hapus spam.</li>
  <li>Jika moderasi aktif di Pengaturan, komentar baru tidak langsung tampil sebelum disetujui.</li>
</ul>', 'kategori komentar moderasi spam'); ?>

<?php admin_guide_item('bi-megaphone', 'Pengumuman', '
<ol>
  <li>Klik Tambah, isi judul dan isi pengumuman.</li>
  <li>Tentukan tanggal mulai dan tanggal selesai. Kosongkan tanggal selesai bila tanpa batas.</li>
  <li>Centang <b>Penting</b> untuk menaikkan prioritas dan menampilkan penanda khusus.</li>
  <li>Tambahkan lampiran bila diperlukan.</li>
  <li>Aktifkan pengumuman dan periksa halaman publik.</li>
</ol>
Pengumuman kedaluwarsa otomatis tidak ditampilkan meskipun datanya tetap tersimpan.', 'announcement penting periode lampiran'); ?>

<?php admin_guide_item('bi-calendar-event', 'Agenda Kegiatan', '
<ol>
  <li>Isi nama kegiatan, deskripsi, tanggal, jam mulai/selesai, lokasi, dan penanggung jawab.</li>
  <li>Pilih status: akan datang, berlangsung, selesai, atau dibatalkan.</li>
  <li>Setelah kegiatan selesai, tambahkan foto dokumentasi dan ubah status menjadi selesai.</li>
  <li>Agenda aktif tampil pada halaman agenda dan dapat muncul di beranda.</li>
</ol>', 'jadwal kegiatan tanggal lokasi pic'); ?>
<?php endif; ?>

<?php if (can('kelola_galeri')): ?>
<?php admin_guide_item('bi-images', 'Album dan Galeri foto/video', '
<ol>
  <li>Buat <b>Album</b> terlebih dahulu, isi nama, kategori, tahun, deskripsi, dan sampul.</li>
  <li>Buka <b>Galeri</b>, pilih jenis Foto atau Video YouTube.</li>
  <li>Untuk foto, unggah JPG/PNG/WEBP. Anda dapat memilih banyak foto sekaligus.</li>
  <li>Untuk video, tempel URL YouTube lengkap.</li>
  <li>Pilih album, isi judul/kategori/tahun, lalu atur proteksi dan watermark bila diperlukan.</li>
  <li>Periksa lightbox di halaman publik setelah menyimpan.</li>
</ol>', 'album foto video youtube upload massal watermark'); ?>
<?php endif; ?>

<?php if (can('kelola_layanan') || can('kelola_pengaduan')): ?>
<?php admin_guide_label('Pelayanan Warga', 'bi-inbox'); ?>
<?php endif; ?>

<?php if (can('kelola_layanan')): ?>
<?php admin_guide_item('bi-inbox', 'Permohonan Masuk dan penerbitan surat', '
<ol>
  <li>Buka <b>Permohonan Masuk</b> dan pilih pengajuan berstatus Baru.</li>
  <li>Periksa identitas, jenis layanan, persyaratan, lampiran, dan keperluan.</li>
  <li>Ubah status menjadi Diproses ketika mulai ditangani.</li>
  <li>Isi catatan admin atau alasan penolakan secara jelas.</li>
  <li>Ketika selesai, sistem dapat membentuk nomor surat dan halaman cetak berdasarkan template layanan.</li>
  <li>Cetak/unduh surat, lalu ubah status menjadi Selesai.</li>
</ol>
Warga dapat melacak status menggunakan nomor registrasi. Notifikasi WhatsApp dikirim bila integrasi diaktifkan.', 'surat request status nomor cetak'); ?>

<?php admin_guide_item('bi-file-earmark-text', 'Jenis Layanan dan template surat', '
<ol>
  <li>Tambah nama layanan, slug, deskripsi, persyaratan, alur, durasi, dan biaya.</li>
  <li>Isi satu persyaratan atau satu langkah per baris.</li>
  <li>Pada template surat, gunakan placeholder yang tersedia seperti <code>{nama}</code>, <code>{nik}</code>, <code>{alamat}</code>, <code>{keperluan}</code>, <code>{nomor_surat}</code>, dan <code>{tanggal}</code>.</li>
  <li>Nonaktifkan layanan sementara tanpa menghapusnya.</li>
  <li>Lakukan pengajuan uji dari halaman publik sebelum layanan diumumkan.</li>
</ol>', 'service template persyaratan biaya surat'); ?>
<?php endif; ?>

<?php if (can('kelola_pengaduan')): ?>
<?php admin_guide_item('bi-chat-square-text', 'Pengaduan Warga', '
<ol>
  <li>Buka aduan baru dan periksa isi, kategori, prioritas, identitas, serta lampiran.</li>
  <li>Ubah status Diterima → Diproses → Selesai atau Ditolak.</li>
  <li>Tambahkan tanggapan. Tanggapan tampil pada halaman pelacakan warga.</li>
  <li>Gunakan prioritas untuk aduan mendesak dan jaga kerahasiaan aduan anonim.</li>
  <li>Export data bila diperlukan untuk laporan internal.</li>
</ol>', 'aduan tiket tanggapan prioritas anonim'); ?>

<?php admin_guide_item('bi-envelope', 'Pesan Kontak', '
<ul>
  <li>Pesan berasal dari formulir Kontak pada website publik.</li>
  <li>Baca nama, email/telepon, subjek, dan isi pesan.</li>
  <li>Gunakan saklar untuk menandai sudah dibaca.</li>
  <li>Hubungi pengirim melalui kanal resmi dan jangan menyalin data pribadi ke halaman publik.</li>
</ul>', 'contact message pesan masuk'); ?>
<?php endif; ?>

<?php if (can('kelola_transparansi') || can('kelola_dokumen')): ?>
<?php admin_guide_label('Transparansi dan Dokumen', 'bi-cash-stack'); ?>
<?php endif; ?>

<?php if (can('kelola_transparansi')): ?>
<?php admin_guide_item('bi-cash-stack', 'APBDes / Anggaran', '
<ol>
  <li>Tambah item berdasarkan tahun anggaran.</li>
  <li>Pilih jenis Pendapatan, Belanja, atau Pembiayaan.</li>
  <li>Isi kategori/bidang, uraian, nilai anggaran, realisasi, dan sumber dana.</li>
  <li>Gunakan angka tanpa simbol mata uang; tampilan Rupiah dibuat otomatis.</li>
  <li>Perbarui realisasi secara berkala dan cocokkan total dengan dokumen resmi.</li>
</ol>', 'apbdes budget realisasi sumber dana'); ?>

<?php admin_guide_item('bi-cone-striped', 'Pembangunan Desa', '
<ol>
  <li>Isi nama proyek, lokasi, tahun, sumber dana, pagu, dan realisasi.</li>
  <li>Atur status serta progres 0–100%.</li>
  <li>Isi tanggal mulai/selesai dan unggah foto progres.</li>
  <li>Tambahkan dokumen pendukung bila tersedia.</li>
  <li>Perbarui progres dan foto sesuai perkembangan lapangan.</li>
</ol>', 'proyek progress pagu pembangunan'); ?>
<?php endif; ?>

<?php if (can('kelola_dokumen')): ?>
<?php admin_guide_item('bi-folder2-open', 'Dokumen Publik', '
<ol>
  <li>Isi nama dokumen, kategori, tahun, dan deskripsi.</li>
  <li>Upload PDF, DOC, DOCX, XLS, atau XLSX.</li>
  <li>Aktifkan <b>Publik</b> agar warga dapat mengunduh dan <b>Aktif</b> agar ditampilkan.</li>
  <li>Periksa file melalui halaman publik. Jumlah unduhan tercatat otomatis.</li>
</ol>', 'perdes sk laporan pdf download'); ?>
<?php endif; ?>

<?php if (can('kelola_profil')): ?>
<?php admin_guide_label('Profil dan Potensi Desa', 'bi-buildings'); ?>
<?php admin_guide_item('bi-info-circle', 'Profil Desa', '
<ul>
  <li>Kelola bagian Sejarah, Visi Misi, Geografis, Demografi, dan bagian profil lain.</li>
  <li>Edit judul, isi, gambar, ikon, dan urutan tampil.</li>
  <li>Aktifkan hanya bagian yang sudah lengkap.</li>
  <li>Periksa hasil pada halaman <code>/profil</code>.</li>
</ul>', 'sejarah visi misi geografis demografi'); ?>

<?php admin_guide_item('bi-person-badge', 'Perangkat Desa', '
<ol>
  <li>Isi nama, jabatan, kategori lembaga, pendidikan, masa jabatan, kontak resmi, dan tugas.</li>
  <li>Upload foto yang jelas. Foto disimpan melalui jalur terproteksi.</li>
  <li>Atur urutan dengan drag-and-drop dan pilih apakah ditampilkan di beranda.</li>
  <li>Nonaktifkan perangkat lama daripada menghapus riwayat bila masih diperlukan.</li>
</ol>', 'pejabat aparat foto urutan'); ?>

<?php admin_guide_item('bi-diagram-3', 'Struktur Organisasi (SOTK)', '
<ul>
  <li>Tambahkan nama, jabatan, foto, tingkat, dan urutan.</li>
  <li>Tingkat 1 untuk Kepala Desa, 2 untuk Sekretariat, 3 untuk Kaur/Kasi, dan 4 untuk kewilayahan.</li>
  <li>Gunakan urutan untuk mengatur posisi kiri–kanan pada tingkat yang sama.</li>
  <li>Periksa bagan publik setelah perubahan.</li>
</ul>', 'bagan struktur level'); ?>

<?php admin_guide_item('bi-globe-asia-australia', 'SDGs Desa', '
<ul>
  <li>Kelola tujuan SDGs 1–18.</li>
  <li>Isi skor 0–100, deskripsi, ikon, dan warna.</li>
  <li>Aktifkan tujuan yang ingin ditampilkan.</li>
  <li>Skor rata-rata dihitung otomatis pada halaman publik.</li>
</ul>', 'sdgs skor tujuan'); ?>

<?php admin_guide_item('bi-shop', 'UMKM', '
<ol>
  <li>Isi nama produk/usaha, pemilik, kategori, deskripsi, harga, lokasi, dan nomor WhatsApp.</li>
  <li>Gunakan format WhatsApp <code>62xxxxxxxxxx</code>.</li>
  <li>Upload foto produk yang terang dan tidak terlalu besar.</li>
  <li>Tambahkan URL Google Maps lengkap bila tersedia.</li>
  <li>Aktifkan setelah informasi diperiksa pemilik usaha.</li>
</ol>', 'usaha produk whatsapp maps'); ?>

<?php admin_guide_item('bi-tree', 'Potensi Desa', '
<ul>
  <li>Pilih kategori wisata, pertanian, peternakan, perikanan, budaya, kerajinan, atau lainnya.</li>
  <li>Isi judul, deskripsi yang informatif, dan foto.</li>
  <li>Aktifkan data agar tampil pada halaman Potensi.</li>
</ul>', 'wisata pertanian budaya kerajinan'); ?>
<?php endif; ?>

<?php if (can('kelola_konten')): ?>
<?php admin_guide_label('Data Publik', 'bi-bar-chart'); ?>
<?php admin_guide_item('bi-graph-up', 'IDM / Indeks Desa Membangun', '
<ul>
  <li>Masukkan tahun, IKS, IKE, IKL, dan catatan.</li>
  <li>Data pada tahun yang sama diperbarui, bukan digandakan.</li>
  <li>Gunakan tombol cetak untuk laporan dan periksa tampilan publik.</li>
</ul>', 'indeks iks ike ikl'); ?>

<?php admin_guide_item('bi-people-fill', 'Data Bansos', '
<ol>
  <li>Buat program bansos berdasarkan nama, tahun, sumber dana, nilai per penerima, dan deskripsi.</li>
  <li>Pilih program, lalu tambah penerima beserta NIK, alamat, RT/RW, dan keterangan.</li>
  <li>Gunakan filter tahun/program dan cetak laporan.</li>
  <li>Periksa NIK agar penerima tidak tercatat ganda.</li>
</ol>', 'bantuan sosial penerima program'); ?>

<?php admin_guide_item('bi-heart-pulse', 'Data Stunting', '
<ol>
  <li>Masukkan inisial anak, tanggal lahir, jenis kelamin, RT/RW, tinggi, berat, status, tindakan, dan tanggal pemeriksaan.</li>
  <li>Gunakan inisial, bukan nama lengkap, untuk menjaga privasi.</li>
  <li>Filter berdasarkan tahun dan cetak rekap.</li>
  <li>Perbarui hasil setiap pemeriksaan berikutnya.</li>
</ol>', 'balita kesehatan tinggi berat'); ?>
<?php endif; ?>

<?php if (can('kelola_musik') || can('kelola_event')): ?>
<?php admin_guide_label('Pengalaman Website', 'bi-stars'); ?>
<?php endif; ?>

<?php if (can('kelola_musik')): ?>
<?php admin_guide_item('bi-music-note-beamed', 'Musik Website', '
<ol>
  <li>Upload MP3, WAV, atau OGG.</li>
  <li>Atur volume 0–100, autoplay, loop, posisi player, dan halaman target.</li>
  <li>Isi jadwal tanggal/jam bila musik hanya untuk periode tertentu.</li>
  <li>Aktifkan musik dan saklar fitur musik pada Pengaturan.</li>
  <li>Uji pada ponsel; browser dapat meminta interaksi pengguna sebelum memutar audio.</li>
</ol>', 'audio autoplay loop jadwal'); ?>
<?php endif; ?>

<?php if (can('kelola_event')): ?>
<?php admin_guide_item('bi-stars', 'Event Experience', '
<ol>
  <li>Isi nama/jenis event, efek, pesan, banner, warna, intensitas, dan durasi.</li>
  <li>Tentukan tanggal serta jam mulai–selesai.</li>
  <li>Pilih halaman target, prioritas, frekuensi, dan opsi tampil sekali.</li>
  <li>Pilih musik event bila diperlukan.</li>
  <li>Gunakan Preview sebelum mengaktifkan agar efek tidak terlalu berat.</li>
</ol>', 'confetti fireworks snow balloon sparkle'); ?>
<?php endif; ?>

<?php if (can('kelola_tampilan')): ?>
<?php admin_guide_label('Pengaturan dan Interaksi Website', 'bi-sliders'); ?>
<?php admin_guide_item('bi-sliders', 'Pengaturan Website: seluruh tab', '
<ul>
  <li><b>Informasi Umum:</b> identitas desa, alamat, kontak, peta, kepala desa, sambutan, dan SEO.</li>
  <li><b>Tampilan & Tema:</b> logo, favicon, hero, video hero, warna, font, gaya komponen, susunan beranda, dan footer.</li>
  <li><b>Media Sosial:</b> tautan akun resmi desa.</li>
  <li><b>Fitur Website:</b> komentar, musik, proteksi foto/screenshot, animasi, dan fitur publik lain.</li>
  <li><b>Notifikasi Web:</b> lonceng, popup, sumber konten, dan jumlah item.</li>
  <li><b>Notifikasi WhatsApp:</b> Fonnte, nomor admin, serta jenis notifikasi.</li>
</ul>
Simpan setiap tab secara terpisah. Setelah mengubah tampilan, gunakan <b>Reset Cache</b> dan lakukan hard refresh.', 'setting tema logo sosial notifikasi cache'); ?>

<?php admin_guide_item('bi-list-nested', 'Menu Navigasi', '
<ol>
  <li>Pilih lokasi Main atau Footer.</li>
  <li>Isi judul menu, URL, ikon, urutan, dan status aktif.</li>
  <li>Untuk dropdown, buat menu induk lalu pilih sebagai Parent pada submenu.</li>
  <li>Gunakan URL internal tanpa domain, misalnya <code>berita</code>, <code>layanan</code>, atau <code>halaman/prosedur-nikah</code>.</li>
  <li>Geser urutan menu bila fitur reorder tersedia, lalu periksa desktop dan ponsel.</li>
</ol>', 'navbar footer dropdown parent'); ?>

<?php admin_guide_item('bi-file-earmark-richtext', 'Halaman Statis: langkah lengkap dan pemecahan error', '
<ol>
  <li>Buka <b>Halaman Statis</b> → <b>Tambah</b>.</li>
  <li>Isi <b>Judul</b> dan <b>Isi Halaman</b>. Keduanya wajib.</li>
  <li>Biarkan Slug kosong agar dibuat otomatis. Contoh judul “Prosedur Nikah” menghasilkan <code>/halaman/prosedur-nikah</code>.</li>
  <li>Tambahkan gambar utama dan lampiran bila diperlukan.</li>
  <li>Isi SEO Meta Title/Description, pilih template, atur urutan, lalu aktifkan <b>Tampilkan</b>.</li>
  <li>Klik Simpan. Pada daftar, klik ikon <b>Lihat</b> untuk membuka halaman publik.</li>
  <li>Agar muncul di navbar/footer, buka <b>Menu Navigasi</b> dan gunakan URL <code>halaman/slug-halaman</code>.</li>
  <li>Data yang dihapus masuk ke Sampah dan dapat dipulihkan.</li>
</ol>
<b>Jika gagal disimpan:</b>
<ul>
  <li>Pastikan judul dan isi tidak kosong.</li>
  <li>Gunakan gambar JPG/PNG/WEBP dan dokumen PDF/DOC/DOCX/XLS/XLSX.</li>
  <li>Pastikan folder <code>uploads</code> dapat ditulis oleh PHP.</li>
  <li>Jika muncul pesan struktur database, jalankan migrasi terbaru.</li>
  <li>Jika halaman tersimpan tetapi tidak tampil, pastikan status Tampilkan aktif dan buka URL melalui tombol Lihat.</li>
</ul>
<div class="alert alert-success py-2 mb-0">Versi ini menyediakan fallback UTF-8 tanpa mbstring, transaksi database, perlindungan file lama, dan pesan error yang lebih jelas.</div>', 'static page slug editor attachment error'); ?>

<?php admin_guide_item('bi-bell', 'Notifikasi Push', '
<ol>
  <li>Pastikan website menggunakan HTTPS dan pelanggan sudah memberi izin notifikasi.</li>
  <li>Isi judul, isi pesan, ikon/tautan tujuan bila tersedia.</li>
  <li>Kirim ke semua perangkat dan periksa jumlah berhasil/gagal.</li>
  <li>Gunakan seperlunya untuk pengumuman penting agar warga tidak menonaktifkan notifikasi.</li>
</ol>', 'web push notification'); ?>

<?php admin_guide_item('bi-controller', 'Zona Hiburan', '
<ul>
  <li>Aktif/nonaktifkan Zona Hiburan dan masing-masing game.</li>
  <li>Kelola soal Kuis Harian secara manual: pertanyaan, empat pilihan, jawaban benar, penjelasan, urutan, dan status aktif.</li>
  <li>Memory, Puzzle, dan Roda menggunakan variasi lokal harian tanpa API AI.</li>
  <li>Periksa statistik pemain, streak, skor, aktivitas, dan export sesuai kebutuhan.</li>
  <li>Soal yang sudah memiliki riwayat jawaban akan dinonaktifkan, bukan dihapus permanen.</li>
</ul>', 'game quiz streak leaderboard'); ?>

<?php admin_guide_item('bi-chat-dots', 'Live Chat', '
<ol>
  <li>Buka Live Chat dan pilih percakapan aktif atau perangkat online.</li>
  <li>Kirim balasan, gunakan quick reply, dan perhatikan indikator mengetik.</li>
  <li>Mute percakapan untuk menghentikan notifikasi sementara atau tutup setelah selesai.</li>
  <li>Blokir IP hanya untuk spam/penyalahgunaan; percakapan dapat dibuka kembali dari arsip.</li>
  <li>Atur jam/status layanan dan quick reply pada pengaturan chat.</li>
</ol>', 'chat operator visitor mute ban quick reply'); ?>
<?php endif; ?>

<?php if (can('lihat_audit')): ?>
<?php admin_guide_label('Pemantauan dan Evaluasi', 'bi-clipboard-data'); ?>
<?php admin_guide_item('bi-graph-up-arrow', 'Statistik Kunjungan', '
<ul>
  <li>Pilih rentang 7, 30, atau 90 hari.</li>
  <li>Periksa tayangan, pengunjung unik, halaman populer, perangkat, OS, dan lokasi perkiraan.</li>
  <li>Blokir IP hanya bila terbukti bot/spam; IP dapat dibuka kembali.</li>
  <li>Lokasi berbasis IP bersifat perkiraan, bukan lokasi GPS presisi.</li>
</ul>', 'visitor analytics ip location'); ?>

<?php admin_guide_item('bi-shield-exclamation', 'Upaya Screenshot', '
<ul>
  <li>Periksa metode, halaman, IP, perangkat, dan waktu kejadian.</li>
  <li>Buka chat terkait bila tersedia untuk konteks tambahan.</li>
  <li>Blokir IP hanya setelah verifikasi; hapus log lama sesuai kebijakan retensi.</li>
  <li>Catatan ini adalah indikator teknis dan tidak selalu membuktikan pelanggaran.</li>
</ul>', 'screenshot log privacy block'); ?>

<?php admin_guide_item('bi-clipboard-data', 'Audit Log dan riwayat Import', '
<ul>
  <li>Audit merekam login, tambah/edit/hapus, perubahan status, import, pengiriman notifikasi, dan aktivitas penting lainnya.</li>
  <li>Gunakan filter user, aksi, modul, dan tanggal untuk penelusuran.</li>
  <li>Audit Import menampilkan file, jumlah berhasil/gagal, dan petugas.</li>
  <li>Jangan mengubah log secara manual karena dipakai untuk pemeriksaan aktivitas.</li>
</ul>', 'activity history log'); ?>

<?php admin_guide_item('bi-chat-square-heart', 'Feedback Pengunjung', '
<ol>
  <li>Atur judul/pengantar formulir dan status aktif.</li>
  <li>Buat pertanyaan teks, pilihan, rating, atau jenis yang tersedia.</li>
  <li>Atur wajib, urutan, opsi, dan status aktif.</li>
  <li>Periksa respons, cetak/export bila diperlukan, dan hapus respons uji/spam.</li>
</ol>', 'survey question response rating'); ?>
<?php endif; ?>

<?php if (can('kelola_konten')): ?>
<?php admin_guide_label('Aplikasi dan Distribusi', 'bi-phone'); ?>
<?php admin_guide_item('bi-phone', 'Pengaturan PWA', '
<ul>
  <li>Isi nama aplikasi, nama pendek, deskripsi, warna tema/latar, ikon, dan status fitur.</li>
  <li>Nama pendek sebaiknya maksimal 14 karakter.</li>
  <li>Simpan lalu lakukan hard refresh atau hapus instalasi lama saat menguji ikon baru.</li>
  <li>PWA dan push memerlukan HTTPS pada server produksi.</li>
</ul>', 'manifest install app'); ?>

<?php admin_guide_item('bi-qr-code', 'Generator QR Code', '
<ol>
  <li>Pilih atau masukkan URL tujuan yang aman.</li>
  <li>Atur ukuran/label bila tersedia.</li>
  <li>Generate QR, unduh gambar, lalu uji menggunakan kamera ponsel sebelum dicetak.</li>
  <li>Gunakan URL HTTPS dan hindari memasukkan data pribadi langsung ke QR.</li>
</ol>', 'qrcode link download'); ?>
<?php endif; ?>

<?php admin_guide_label('Keamanan, Backup, dan Troubleshooting', 'bi-shield-check'); ?>
<?php admin_guide_item('bi-database-check', 'Backup dan pemulihan', '
<ol>
  <li>Sebelum update/import besar, backup file website melalui File Manager.</li>
  <li>Backup database melalui phpMyAdmin → Export → SQL.</li>
  <li>Pastikan folder <code>uploads</code> ikut dibackup karena menyimpan gambar/dokumen.</li>
  <li>Simpan backup di lokasi terpisah dari hosting.</li>
  <li>Uji pemulihan pada staging bila memungkinkan.</li>
</ol>', 'backup database uploads restore'); ?>

<?php admin_guide_item('bi-tools', 'Cara menangani error admin', '
<ol>
  <li>Jangan menekan Simpan berkali-kali. Tunggu respons pertama.</li>
  <li>Salin pesan error dan catat menu, waktu, serta data yang sedang diproses.</li>
  <li>Periksa format file, ukuran upload, kolom wajib, tanggal, dan angka.</li>
  <li>Lakukan hard refresh <kbd>Ctrl</kbd> + <kbd>F5</kbd> setelah update kode.</li>
  <li>Periksa permission folder <code>uploads</code>, ekstensi PHP, dan migrasi database.</li>
  <li>Periksa Audit Log serta <code>error_log</code> hosting tanpa membagikan password atau token.</li>
</ol>
<div class="alert alert-warning py-2 mb-0">Jangan mengedit tabel database langsung untuk “memperbaiki” data sebelum membuat backup.</div>', '500 error troubleshooting permission migration'); ?>

</div>

<div id="guideNoResult" class="text-center text-muted py-5 d-none" data-testid="guide-no-result">
  <i class="bi bi-search fs-1 d-block mb-2"></i>
  Tidak ada panduan yang cocok. Gunakan kata yang lebih singkat.
</div>

<script>
(function () {
  'use strict';
  var input = document.getElementById('guideSearch');
  var clear = document.getElementById('guideClear');
  var expand = document.getElementById('guideExpandAll');
  var items = Array.prototype.slice.call(document.querySelectorAll('.guide-item'));
  var labels = Array.prototype.slice.call(document.querySelectorAll('.guide-label'));
  var empty = document.getElementById('guideNoResult');
  var allOpen = false;

  function normalize(value) {
    return (value || '').toLowerCase().normalize ? value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') : (value || '').toLowerCase();
  }

  function applySearch() {
    var query = normalize(input.value.trim());
    var shown = 0;

    items.forEach(function (item) {
      var haystack = normalize((item.dataset.guideTitle || '') + ' ' + item.textContent);
      var hit = !query || haystack.indexOf(query) !== -1;
      item.classList.toggle('d-none', !hit);
      if (hit) shown++;

      var panel = item.querySelector('.accordion-collapse');
      if (query && hit && panel && !panel.classList.contains('show')) {
        bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).show();
      } else if (!query && panel && panel.classList.contains('show') && !allOpen) {
        bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false }).hide();
      }
    });

    labels.forEach(function (label) { label.classList.toggle('d-none', !!query); });
    empty.classList.toggle('d-none', shown > 0);
  }

  input.addEventListener('input', applySearch);
  clear.addEventListener('click', function () {
    input.value = '';
    input.focus();
    applySearch();
  });

  expand.addEventListener('click', function () {
    allOpen = !allOpen;
    items.filter(function (item) { return !item.classList.contains('d-none'); }).forEach(function (item) {
      var panel = item.querySelector('.accordion-collapse');
      if (!panel) return;
      var instance = bootstrap.Collapse.getOrCreateInstance(panel, { toggle: false });
      allOpen ? instance.show() : instance.hide();
    });
    expand.innerHTML = allOpen
      ? '<i class="bi bi-arrows-collapse me-1"></i>Tutup Semua'
      : '<i class="bi bi-arrows-expand me-1"></i>Buka Semua';
  });
})();
</script>

<style>
@media print {
  .admin-sidebar, .admin-topbar, #guideSearch, #guideClear, #guideExpandAll, .guide-summary, .btn { display:none!important; }
  .admin-main, .admin-content { margin:0!important; padding:0!important; }
  .accordion-collapse { display:block!important; height:auto!important; }
  .accordion-button::after { display:none!important; }
  .accordion-item { break-inside:avoid; border:1px solid #ddd!important; margin-bottom:8px; }
}
.editor-area.is-invalid { border-color:#dc3545!important; box-shadow:0 0 0 .2rem rgba(220,53,69,.15); }
</style>
