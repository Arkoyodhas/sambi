# Model face-auth lokal

Folder ini dipakai oleh `assets/js/face-auth.js` terlebih dahulu. Jalankan dari
terminal hosting:

```bash
php tools/install-face-models.php
```

Perintah tersebut mengunduh versi yang dikunci (`@vladmandic/human 3.3.6`),
memverifikasi bahwa file bukan HTML/error page, lalu menyimpan modul dan model
di folder ini. Bila file lokal belum tersedia, login wajah memakai CDN yang
sama sebagai fallback agar fitur tidak mati.
