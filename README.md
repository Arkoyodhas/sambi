# Website Company Profile & Sistem Informasi Desa

Website resmi desa berbasis **PHP Native (MVC ringan) + MySQL/MariaDB** — modern, dinamis, aman, responsif, dan siap dihosting di **cPanel** tanpa dependensi tambahan (tanpa Composer, tanpa Node.js).

---

## 1. Fitur Utama

### Tampilan Publik
| Fitur | Keterangan |
|---|---|
| Beranda dinamis | Hero, statistik, sambutan kepala desa, urutan section bisa diatur admin |
| Profil Desa | Sejarah, Visi & Misi, Kondisi Geografis, Demografi |
| Pemerintahan | Struktur organisasi & perangkat desa |
| Berita | Kategori, tag, pencarian, komentar (dengan moderasi), berita utama |
| Pengumuman & Agenda | Informasi resmi dan jadwal kegiatan desa |
| Galeri | Album foto & video, proteksi foto + watermark otomatis |
| Layanan Online | Permohonan surat online + cek status via nomor tiket |
| Statistik Penduduk | Grafik demografi otomatis dari data penduduk |
| Transparansi | APBDes per tahun & progres pembangunan |
| Pengaduan Warga | Kirim aduan + lacak status via nomor tiket |
| UMKM & Potensi Desa | Direktori usaha warga dan potensi desa |
| Dokumen Publik | Unduh dokumen resmi (Perdes, laporan, dll.) |
| Efek Event | Confetti/efek khusus hari besar (diatur admin) |
| Musik Latar | Pemutar musik website (diatur admin) |
| Video Hero | Background hero bisa berupa video MP4/WEBM (upload via admin) |
| Animasi Background | Partikel interaktif mengikuti kursor di seluruh halaman (on/off & kepadatan via admin) |
| Notifikasi WhatsApp | Terhubung Fonnte: pemohon surat & admin dapat notifikasi WA otomatis |
| Proteksi Screenshot | Blokir PrintScreen/cetak + blur saat pindah aplikasi (on/off via admin) |

### Panel Admin (Role-Based)
- Dashboard statistik ringkas
- CRUD Data Penduduk & Keluarga + **Import/Export Excel**
- Kelola semua konten publik (berita, galeri, layanan, transparansi, dll.)
- Pengaturan website: identitas desa, logo, warna tema, font, layout, SEO, media sosial
- Manajemen menu navigasi, halaman statis, user admin per-role, dan audit log
- Keamanan: CSRF protection, captcha login, anti brute-force (kunci akun), session timeout, wajib ganti password pertama

---

## 2. Kebutuhan Server (Hosting cPanel)

- PHP **8.0 atau lebih baru** (disarankan 8.1/8.2) dengan ekstensi: `pdo_mysql`, `mbstring`, `gd`, `zip` (umumnya sudah aktif default)
- MySQL 5.7+ / MariaDB 10.3+
- Apache dengan `mod_rewrite` (standar di semua cPanel)

---

## 3. Panduan Instalasi di cPanel (Langkah demi Langkah)

### Langkah 1 — Upload Source Code
1. Login ke **cPanel** hosting Anda.
2. Buka **File Manager** → masuk ke folder `public_html` (atau subdomain/subfolder yang diinginkan).
3. Upload file ZIP source code, lalu klik kanan → **Extract**.
4. Pastikan file `index.php` dan `.htaccess` berada langsung di dalam `public_html`
   (bukan di dalam `public_html/desa-website/`). Jika masih di dalam subfolder, pindahkan semua isinya ke `public_html`.

### Langkah 2 — Buat Database
1. Di cPanel, buka **MySQL® Databases**.
2. Buat database baru, contoh: `namacpanel_desa`.
3. Buat user database baru beserta password yang kuat, contoh: `namacpanel_desauser`.
4. Tambahkan user tersebut ke database dan centang **ALL PRIVILEGES**.

### Langkah 3 — Import File SQL
1. Buka **phpMyAdmin** dari cPanel.
2. Pilih database yang baru dibuat di panel kiri.
3. Klik tab **Import** → pilih file `database/dawungsr_dbdesa.sql` → klik **Go/Kirim**.
4. Tunggu hingga muncul pesan sukses.

> **Disarankan:** gunakan file **`database/desa_dawung_FULL_LENGKAP.sql`** — satu file berisi seluruh struktur (84 tabel) + semua migrasi + data awal. Tidak perlu import file migrasi lain.

### Langkah 4 — Konfigurasi Koneksi Database
Edit file `config/config.php` melalui File Manager (klik kanan → Edit):

```php
'db_host' => 'localhost',
'db_name' => 'namacpanel_desa',        // sesuaikan
'db_user' => 'namacpanel_desauser',    // sesuaikan
'db_pass' => 'password-anda',          // sesuaikan

// WAJIB: ganti dengan string acak panjang (min. 32 karakter)
'app_key' => 'tulis-string-acak-anda-di-sini-minimal-32-karakter',
```

> Biarkan `'debug' => false` untuk produksi.

### Langkah 5 — Set Permission Folder Upload
Melalui File Manager, pastikan folder berikut memiliki permission **755** (atau 775 jika diperlukan):
- `uploads/` beserta seluruh subfoldernya

### Langkah 6 — Selesai! Login Admin
1. Buka website Anda: `https://domain-desa.id`
2. Buka panel admin: `https://domain-desa.id/admin/login`

| | |
|---|---|
| **Username** | `admin` |
| **Password** | `admin123` |

> ⚠️ **PENTING:** Saat login pertama Anda akan **dipaksa mengganti password**. Ini fitur keamanan bawaan — jangan dilewati.

---

## 4. Update dari Versi Sebelumnya (Website Sudah Terpasang)

Jika website Anda SUDAH berjalan di cPanel dan hanya ingin memperbarui ke versi dengan fitur **Statistik Kunjungan** & **Notifikasi Push**:

1. Upload/timpa semua file source code terbaru ke folder website (JANGAN timpa `config/config.php` dan folder `uploads/`).
2. Buka **phpMyAdmin** → pilih database desa → tab **Import** → import file `database/update-statistik-push.sql` (hanya menambah 4 tabel baru, data lama aman, boleh dijalankan ulang).
3. Selesai. Kunci VAPID untuk push dibuat otomatis saat halaman publik pertama kali dibuka.
4. Menu baru di admin: **Statistik Kunjungan** (grup Sistem) dan **Notifikasi Push** (grup Sistem).

Untuk fitur **Notifikasi Web (lonceng & popup)** dan **Proteksi Screenshot Mode Ketat**:

1. Upload/timpa file terbaru seperti di atas.
2. (Opsional tapi disarankan) Import `database/update-notifikasi-web.sql` — hanya menambah baris pengaturan default, data lama aman. Tanpa file ini pun fitur tetap berjalan dengan nilai bawaan.
3. Atur di admin: **Pengaturan → tab Notifikasi Web** (lonceng, popup otomatis, sumber konten) dan **Pengaturan → tab Fitur → Proteksi Screenshot** (pilih tingkat *Ketat* untuk memburamkan halaman kecuali area sekitar kursor/sentuhan + watermark otomatis).

## Update v10 — Banner Sinematik, Posisi Banner & Pencarian Pintar

- **Banner Slider Galeri sinematik**: zoom halus (Ken Burns), transisi reveal, judul muncul per kata, efek parallax mengikuti kursor, cincin progres pada tombol jeda.
- **Foto lebih cepat di internet lambat**: placeholder buram super kecil (±1 KB) tampil seketika, foto otomatis diperkecil (480/768/1440 px WEBP, watermark tetap ada), slide berikutnya dimuat di latar, autoplay menunggu foto siap, dan mode hemat data (2G/3G/Data Saver) hanya memuat versi kecil. Foto terproteksi kini boleh di-cache browser sehingga tidak diunduh ulang.
- **Posisi banner diatur admin**: menu **Pengaturan → Tampilan & Tema → Urutan Section Beranda**. Seret (atau tombol panah) untuk mengurutkan, saklar untuk tampil/sembunyi. Banner bisa diletakkan di posisi mana pun di antara section lain.
- **Pencarian pintar di beranda**: kotak cari di hero (juga `Ctrl + K` atau tombol `/`). Mendukung koreksi salah ketik, sinonim bahasa sehari-hari (mis. "kades", "lapor", "jualan"), **jawaban cepat** (jumlah penduduk, KK, wilayah, kepala desa, jam pelayanan, alamat, kontak), aksi cepat ke halaman, hasil dari layanan, berita, pengumuman, agenda, UMKM, potensi, dokumen, galeri, perangkat, halaman & FAQ, riwayat pencarian, navigasi keyboard, dan pencarian suara (bila browser mendukung). Tanpa AI eksternal / tanpa biaya API.
- Endpoint baru: `GET /api/search?q=...` dan `GET /media/galeri/{id}/{480|768|1440}`.
- Tidak ada perubahan struktur database yang wajib untuk update ini.

## 5. Setelah Instalasi (Checklist Admin)

1. **Ganti password admin** (otomatis diminta saat login pertama).
2. Buka **Pengaturan Website** → tab *Informasi Umum*: isi nama desa, alamat, kontak, kepala desa, dan koordinat peta.
3. Tab *Tampilan & Tema*: upload logo & favicon, atur warna dan gaya tampilan.
4. Hapus/ubah **data contoh (dummy)**: berita, penduduk, UMKM, galeri, dsb. sudah diisi data contoh agar website langsung terlihat hidup — ganti dengan data asli desa Anda.
5. Kelola **Data Penduduk**: gunakan menu *Import Excel* → unduh template → isi data → upload. Sistem memvalidasi NIK/KK dan menampilkan preview sebelum disimpan.
6. Tambahkan user admin lain melalui menu **User Admin** dengan role sesuai tugasnya (Admin Desa, Operator Kependudukan, Redaktur Berita, dll.).

### Mengaktifkan Notifikasi WhatsApp (Fonnte)
1. Daftar akun di [fonnte.com](https://fonnte.com) → tambah **Device** → scan QR dengan WhatsApp nomor desa.
2. Salin **Token device** (menu Device di dashboard Fonnte).
3. Di admin website: **Pengaturan Website → tab Notifikasi WhatsApp** → aktifkan, tempel token, isi Nomor WA Admin → **Simpan**.
4. Klik **Kirim Pesan Uji** untuk memastikan koneksi berhasil.
5. Selesai — setiap permohonan surat baru otomatis mengirim WA ke pemohon dan ke nomor admin; perubahan status (diproses/selesai/ditolak) juga diberitahukan ke pemohon.

---

## 5. Struktur Folder

```
public_html/
├── assets/          → CSS, JavaScript, gambar tema
├── config/          → config.php (koneksi DB), modules.php
├── controllers/     → Logika aplikasi (publik & admin)
├── core/            → Inti framework (routing, auth, security, database)
├── database/        → desa_db.sql (file import phpMyAdmin)
├── uploads/         → File upload (foto, dokumen, musik)
│   └── protected/   → Dokumen privat (tidak bisa diakses langsung)
├── views/           → Template tampilan (publik & admin)
├── .htaccess        → Rewrite URL + proteksi folder sistem
└── index.php        → Front controller (semua request masuk ke sini)
```

Folder `config/`, `core/`, `controllers/`, `views/`, `database/`, dan `uploads/protected/` **diblokir dari akses langsung** oleh `.htaccess`.

---

## 6. Instalasi di Subfolder / Subdomain

Aplikasi otomatis mendeteksi base URL, jadi bisa dipasang di:
- Domain utama: `https://desa.id`
- Subdomain: `https://web.desa.id`
- Subfolder: `https://domain.id/desa` — tanpa mengubah konfigurasi apa pun.

Jika ingin memaksa URL tertentu, isi `'base_url'` di `config/config.php`.

---

## 7. Pemecahan Masalah (Troubleshooting)

| Masalah | Solusi |
|---|---|
| Halaman putih / error 500 | Cek versi PHP di cPanel (**MultiPHP Manager**) — harus 8.0+. Cek juga kredensial DB di `config/config.php`. |
| Muncul "Terjadi kesalahan database" | Database belum diimport, atau `db_name`/`db_user`/`db_pass` salah. |
| Halaman selain beranda 404 | `mod_rewrite` belum aktif atau file `.htaccess` tidak ikut ter-upload (file diawali titik = tersembunyi; aktifkan *Show Hidden Files* di File Manager). |
| Upload gambar gagal | Permission folder `uploads/` bukan 755/775, atau ukuran file melebihi batas (gambar 3 MB, dokumen 10 MB, musik 12 MB — bisa diubah di `config/config.php`). |
| Import Excel gagal dibaca | Pastikan file berformat `.xlsx` dan mengikuti template yang diunduh dari menu Import. |
| Akun terkunci setelah salah password | Tunggu 15 menit (default), atau ubah `lockout_minutes` di config. |

---

## 8. Keamanan Bawaan

- Password di-hash dengan **bcrypt**
- Proteksi **CSRF** di semua form
- **Captcha** matematika + **anti brute-force** (kunci akun setelah 5 kali gagal)
- Session timeout otomatis + opsi wajib ganti password
- Semua output di-escape (anti XSS), query memakai **prepared statements** (anti SQL Injection)
- Header keamanan: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`
- File `.sql`, `.md`, `.log`, `.env` diblokir dari akses publik

---

*Dibangun dengan PHP Native — ringan, cepat, dan mudah dirawat. Selamat membangun desa digital! 🇮🇩*

## Versi tanpa modul IMEI dan AI Zona Hiburan

Versi ini tidak memiliki menu, rute, controller, konektor API, atau tampilan Registrasi IMEI. Zona Hiburan juga tidak memakai layanan AI; soal dikelola manual oleh admin dan variasi game berotasi dari konfigurasi lokal. Lihat `PANDUAN-PENGHAPUSAN-IMEI-DAN-AI-ZONA-HIBURAN.md` untuk langkah deployment dan pembersihan data lama.

