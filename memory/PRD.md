# PRD — Website Desa (PHP Native + MySQL)

## Original request
Perbaiki fitur cloning website admin (gagal karena batas waktu request) dan pastikan paket hasil cloning benar-benar berjalan tanpa kesalahan untuk desa lain. Elemen tampilan (background, dll.) ikut tersalin; dokumen, berita, foto perangkat desa, dan konten lain yang tidak berkaitan dengan tampilan tidak tersalin.

## User choices
- Proses berjalan bertahap di latar belakang dengan progres.
- Isi awal: opsi contoh teks yang bisa diedit ATAU kosong (checkbox), desain tetap tersalin.

## Implemented (Nov 2026)
- controllers/admin/CloningController.php ditulis ulang: start → step (≤6 dtk / ≤12MB per request) → download; job di storage/clone_jobs (dilindungi .htaccess, auto-hapus 6 jam).
- SQL dibuat langsung dari struktur database live (SHOW CREATE TABLE, semua tabel) — tidak lagi bergantung pada file database/desa_db.sql yang tidak ada (penyebab gagal sebelumnya).
- Data yang disalin: site_settings (tanpa identitas/rahasia/token), menu (tanpa link eksternal desa sumber), peran & izin, kategori berita, jenis layanan surat, pertanyaan feedback, balasan cepat chat, efek event, musik latar; nama desa/wilayah sumber diganti otomatis.
- File tampilan disalin: background hero, video hero, layer hero, ikon verifikasi, musik latar.
- Tidak disalin: berita, pengumuman, agenda, dokumen, galeri, foto & data perangkat, penduduk, permohonan, pengaduan, log, akun, token WA/VAPID, logo.
- Opsi contoh teks: 1 berita, 1 pengumuman, sambutan, teks profil, FAQ, 4 perangkat tanpa foto.
- Diverifikasi: import SQL ke DB kosong, semua halaman publik & admin hasil clone 200 tanpa error.

## Backlog
- P1: Isi nomor WhatsApp admin baru di form cloning (saat ini diminta di login pertama).
- P2: Unggah logo desa baru langsung dari form cloning.

## Update (Nov 2026) — Request Timeout di hosting LiteSpeed
- Penyebab: ZipArchive membuka & menulis ulang seluruh arsip di setiap tahap (makin lama makin berat) + pemindaian folder .git/uploads/storage penuh.
- Perbaikan: penulis ZIP tambah-di-akhir (tiap tahap ≤4 dtk/≤8MB, terukur <0,2 dtk per tahap dengan video 30MB), pemindaian folder dipangkas, tahap SQL & ZIP aman diulang bila terputus (ftruncate ke posisi terakhir), file sementara dihapus setelah status selesai tersimpan.
- Ekstensi zip PHP tidak lagi wajib (hanya zlib).
- Komentar "Desa Dawung" di theme-teal.css, motion.js, diorama.js dan hint config/modules.php dibuat umum.
- Testing agent iteration_1: backend & frontend 100%.

## Update (Nov 2026) — "Terjadi kesalahan database" di hosting baru setelah ganti password
- Tidak bisa direproduksi di MariaDB 10.11 / MySQL 8 (strict, user terbatas). error_log hosting asal menunjukkan driver pdo_mysql hosting tidak stabil dengan prepared statement native (2014/2027/2000/HY093).
- core/Helpers.php db(): EMULATE_PREPARES=true, buffered query, closeCursor di semua helper, sql_mode tanpa ONLY_FULL_GROUP_BY/NO_ZERO_DATE, time_zone DB = zona PHP (berita "sekarang" langsung tampil).
- core/App.php: error database dicatat ke error_log dengan Kode; admin yang login melihat detail teknis (pengunjung tidak).
- MenuController: regex URL menu internal diperbaiki (sebelumnya semua link internal ditolak).
- Testing agent iteration_2 & 3: alur instal clone (MariaDB + MySQL 8) dan regresi tulis/baca situs sumber lulus.

## Update (Nov 2026) — Musik latar tidak berjalan untuk pengunjung
- sw.js: audio/video & Range request tidak lagi lewat cache Service Worker (penyebab gagal di iOS/Safari, seek/loop rusak); hanya status 200 yang di-cache; nama cache diganti agar cache lama terhapus.
- app.js: data musik dimuat saat halaman dibuka (bukan setelah scroll), tap/klik pertama langsung memutar musik (pointerup/touchend/click/keydown), posisi lagu dilanjutkan saat pindah halaman.
- MediaController::musik: lepas kunci sesi, matikan buffer/kompresi output, stream per 64KB dengan flush.
- Testing agent: backend 100%, frontend 100% (Chrome dengan autoplay policy asli, emulasi iPhone & Pixel).

## Update (Nov 2026) — Dashboard admin baru (gaya Apple)
- Statistik kunjungan dihapus dari dashboard (sudah ada menu Kunjungan).
- Hero sapaan + ringkasan "N hal dari warga menunggu", tombol aksi ke kategori paling mendesak.
- Kotak masuk warga: Surat, Pengaduan, Pesan, Live Chat, Komentar (kartu gelap = paling mendesak), Masukan pengunjung di samping.
- Feed gabungan "Terbaru dari warga" dengan filter, agenda gaya kalender, status musik/event, aktivitas tim, "Sekilas desa".
- File: controllers/admin/DashboardController.php, views/admin/dashboard.php, assets/css/dashboard.css (baru). Testing agent: 100%.
