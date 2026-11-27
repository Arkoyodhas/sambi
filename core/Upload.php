<?php

class Upload {

    public const IMAGE_MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    public const DOC_MIMES = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];
    public const AUDIO_MIMES = ['audio/mpeg' => 'mp3', 'audio/mp3' => 'mp3', 'audio/wav' => 'wav', 'audio/x-wav' => 'wav', 'audio/ogg' => 'ogg'];
    public const VIDEO_MIMES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    private static function ensureDir(string $dir): string {
        $clean = trim(str_replace('\\', '/', $dir), '/');
        if ($clean === '' || str_contains($clean, '..')) {
            throw new RuntimeException('Folder upload tidak valid.');
        }

        $path = BASE_PATH . '/uploads/' . $clean;
        if (!is_dir($path) && !@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat. Periksa permission folder uploads.');
        }
        if (!is_writable($path)) {
            throw new RuntimeException('Folder upload tidak dapat ditulis. Periksa permission folder uploads.');
        }
        return $path;
    }

    private static function mime(string $tmp): string {
        if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) return '';

        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi !== false) {
                $mime = @finfo_file($fi, $tmp);
                @finfo_close($fi);
                if (is_string($mime) && $mime !== '') return strtolower(trim($mime));
            }
        }

        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmp);
            if (is_string($mime)) return strtolower(trim($mime));
        }
        return '';
    }

    /**
     * Deteksi kode server (PHP) tersembunyi. Hanya penanda panjang yang dicek
     * agar byte acak di JPG/PDF/DOCX terkompresi tidak memicu salah deteksi.
     */
    public static function looksMalicious(string $tmp): bool {
        if ($tmp === '' || !is_file($tmp)) return false;
        $fh = @fopen($tmp, 'rb');
        if (!$fh) return true;
        $head = (string) fread($fh, 262144);
        fseek($fh, max(0, filesize($tmp) - 65536));
        $tail = (string) fread($fh, 65536);
        fclose($fh);
        $data = $head . $tail;
        if (preg_match('/<\?php[\s\/]|__halt_compiler\s*\(/i', $data)) return true;
        // Teks polos (bukan biner) yang berisi kode eksekusi PHP.
        $printable = preg_match_all('/[\x09\x0A\x0D\x20-\x7E]/', substr($data, 0, 4096));
        $isText = strlen($data) > 0 && $printable / max(1, min(4096, strlen($data))) > 0.95;
        return $isText && (bool) preg_match('/<\?=|\b(?:shell_exec|passthru|proc_open)\s*\(/i', $data);
    }

    /** Office/PDF sering terbaca MIME generik di hosting; validasi via signature + ekstensi. */
    private static function documentMime(string $tmp, string $originalName): string {
        $mime = self::mime($tmp);
        if (isset(self::DOC_MIMES[$mime])) return $mime;
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $sig = (string) @file_get_contents($tmp, false, null, 0, 8);
        if ($ext === 'pdf' && str_starts_with($sig, '%PDF')) return 'application/pdf';
        if (in_array($ext, ['docx', 'xlsx'], true) && str_starts_with($sig, "PK\x03\x04")) {
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($tmp) !== true) return '';
                $ok = $zip->locateName('[Content_Types].xml') !== false;
                for ($i = 0; $ok && $i < $zip->numFiles; $i++) {
                    if (preg_match('/\.(?:php\d?|phtml|phar|exe|sh|js|vbs|bat)$/i', (string) $zip->getNameIndex($i))) $ok = false;
                }
                $zip->close();
                if (!$ok) return '';
            }
            return array_search($ext, self::DOC_MIMES, true) ?: '';
        }
        if (in_array($ext, ['doc', 'xls'], true) && $sig === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            return array_search($ext, self::DOC_MIMES, true) ?: '';
        }
        return '';
    }

    /** Pilih penanganan gambar atau dokumen berdasarkan isi file. */
    public static function attachment(array $file, string $dir) {
        $mime = self::mime((string) ($file['tmp_name'] ?? ''));
        return isset(self::IMAGE_MIMES[$mime]) ? self::image($file, $dir) : self::document($file, $dir);
    }

    private static function randomName(string $ext): string {
        return date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    }

    private static function uploadError(array $file, string $label): ?string {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        return match ($error) {
            UPLOAD_ERR_OK => null,
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Ukuran $label melebihi batas server.",
            UPLOAD_ERR_PARTIAL => "$label hanya terunggah sebagian. Silakan ulangi.",
            UPLOAD_ERR_NO_FILE => "$label belum dipilih.",
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara server tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi server.',
            default => "$label tidak valid.",
        };
    }

    private static function moveUploaded(string $tmp, string $dest): bool {
        if (@move_uploaded_file($tmp, $dest)) return true;
        // Fallback hanya untuk pengujian CLI/lokal; pada request web file harus
        // tetap berasal dari direktori upload PHP.
        if (PHP_SAPI === 'cli' && is_file($tmp)) return @copy($tmp, $dest);
        return false;
    }

    /**
     * Terapkan orientasi EXIF JPEG sebelum gambar disimpan ulang.
     */
    private static function normalizeJpegOrientation($img, string $source) {
        if (!function_exists('exif_read_data') || !function_exists('imagerotate')) return $img;

        $exif = @exif_read_data($source, 'IFD0', false);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
        if ($orientation < 2 || $orientation > 8) return $img;

        $rotate = static function ($image, int $degrees) {
            $rotated = @imagerotate($image, $degrees, 0);
            if ($rotated !== false) {
                imagedestroy($image);
                return $rotated;
            }
            return $image;
        };

        switch ($orientation) {
            case 2: if (function_exists('imageflip')) imageflip($img, IMG_FLIP_HORIZONTAL); break;
            case 3: $img = $rotate($img, 180); break;
            case 4: if (function_exists('imageflip')) imageflip($img, IMG_FLIP_VERTICAL); break;
            case 5:
                $img = $rotate($img, 90);
                if (function_exists('imageflip')) imageflip($img, IMG_FLIP_HORIZONTAL);
                break;
            case 6: $img = $rotate($img, -90); break;
            case 7:
                $img = $rotate($img, -90);
                if (function_exists('imageflip')) imageflip($img, IMG_FLIP_HORIZONTAL);
                break;
            case 8: $img = $rotate($img, 90); break;
        }
        return $img;
    }

    /**
     * Upload gambar JPG/PNG/WEBP. Bila ekstensi GD tidak tersedia, file yang
     * MIME-nya sudah tervalidasi tetap disimpan tanpa resize agar form admin
     * tidak berakhir fatal.
     */
    public static function image(array $file, string $dir = 'images', int $maxWidth = 1600) {
        if (!empty($file['tmp_name']) && self::looksMalicious((string) $file['tmp_name'])) return ['error' => 'File ditolak: terdeteksi konten berbahaya.'];
        if ($msg = self::uploadError($file, 'File gambar')) return ['error' => $msg];
        if ((int) ($file['size'] ?? 0) > (int) cfg('max_upload_image')) {
            return ['error' => 'Ukuran gambar maksimal ' . round((int) cfg('max_upload_image') / 1048576, 1) . ' MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::mime($tmp);
        if (!isset(self::IMAGE_MIMES[$mime])) return ['error' => 'Format gambar harus JPG, PNG, atau WEBP.'];

        $ext = self::IMAGE_MIMES[$mime];
        $name = self::randomName($ext);
        try {
            $destDir = self::ensureDir($dir);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $dest = $destDir . '/' . $name;

        $createFn = match ($ext) {
            'jpg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng',
            'webp' => 'imagecreatefromwebp',
        };
        $saveFn = match ($ext) {
            'jpg' => 'imagejpeg',
            'png' => 'imagepng',
            'webp' => 'imagewebp',
        };

        if (!function_exists($createFn) || !function_exists($saveFn) || !function_exists('imagesx')) {
            if (!self::moveUploaded($tmp, $dest)) return ['error' => 'Gagal menyimpan gambar. Periksa permission folder uploads.'];
            @chmod($dest, 0644);
            $relative = trim($dir, '/') . '/' . $name;
            self::thumbnailWebp($relative, 640, 420);
            return $relative;
        }

        $img = @$createFn($tmp);
        if (!$img) return ['error' => 'Gambar rusak atau tidak dapat diproses.'];
        if ($ext === 'jpg') $img = self::normalizeJpegOrientation($img, $tmp);

        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1) {
            imagedestroy($img);
            return ['error' => 'Dimensi gambar tidak valid.'];
        }

        $maxWidth = max(320, min(4000, $maxWidth));
        if ($w > $maxWidth) {
            $nh = max(1, (int) round($h * $maxWidth / $w));
            $resized = imagecreatetruecolor($maxWidth, $nh);
            if ($ext === 'png' || $ext === 'webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }
            if (!imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxWidth, $nh, $w, $h)) {
                imagedestroy($resized);
                imagedestroy($img);
                return ['error' => 'Gambar gagal diperkecil.'];
            }
            imagedestroy($img);
            $img = $resized;
        }

        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
        $ok = match ($ext) {
            'jpg' => @imagejpeg($img, $dest, 82),
            'png' => @imagepng($img, $dest, 7),
            'webp' => @imagewebp($img, $dest, 82),
        };
        imagedestroy($img);

        if (!$ok || !is_file($dest)) {
            @unlink($dest);
            return ['error' => 'Gagal menyimpan gambar. Periksa permission folder uploads.'];
        }
        @chmod($dest, 0644);
        $relative = trim($dir, '/') . '/' . $name;
        self::thumbnailWebp($relative, 640, 420);
        return $relative;
    }

    public static function logo(array $file, string $dir = 'branding') {
        if (!empty($file['tmp_name']) && self::looksMalicious((string) $file['tmp_name'])) return ['error' => 'File ditolak: terdeteksi konten berbahaya.'];
        if ($msg = self::uploadError($file, 'File logo')) return ['error' => $msg];
        if ((int) ($file['size'] ?? 0) > (int) cfg('max_upload_image')) {
            return ['error' => 'Ukuran logo maksimal ' . round((int) cfg('max_upload_image') / 1048576, 1) . ' MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::mime($tmp);
        if (in_array($mime, ['image/vnd.microsoft.icon', 'image/x-icon'], true)) {
            try {
                $destDir = self::ensureDir($dir);
            } catch (Throwable $e) {
                return ['error' => $e->getMessage()];
            }
            $name = self::randomName('ico');
            if (!self::moveUploaded($tmp, $destDir . '/' . $name)) return ['error' => 'Ikon gagal disimpan.'];
            return trim($dir, '/') . '/' . $name;
        }
        if (in_array($mime, ['image/svg+xml', 'text/xml', 'text/plain'], true)) {
            $content = @file_get_contents($tmp);
            $clean = is_string($content) ? Security::sanitizeSvg($content) : null;
            if ($clean === null) return ['error' => 'File SVG tidak valid atau mengandung konten berbahaya.'];
            try {
                $destDir = self::ensureDir($dir);
            } catch (Throwable $e) {
                return ['error' => $e->getMessage()];
            }
            $name = self::randomName('svg');
            $dest = $destDir . '/' . $name;
            if (@file_put_contents($dest, $clean, LOCK_EX) === false) return ['error' => 'Logo gagal disimpan.'];
            @chmod($dest, 0644);
            $relative = trim($dir, '/') . '/' . $name;
            self::thumbnailWebp($relative, 640, 420);
            return $relative;
        }
        return self::image($file, $dir, 800);
    }

    public static function document(array $file, string $dir = 'documents') {
        if (!empty($file['tmp_name']) && self::looksMalicious((string) $file['tmp_name'])) return ['error' => 'File ditolak: terdeteksi konten berbahaya.'];
        if ($msg = self::uploadError($file, 'File dokumen')) return ['error' => $msg];
        if ((int) ($file['size'] ?? 0) > (int) cfg('max_upload_doc')) {
            return ['error' => 'Ukuran dokumen maksimal ' . round((int) cfg('max_upload_doc') / 1048576) . ' MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::documentMime($tmp, (string) ($file['name'] ?? ''));
        if (!isset(self::DOC_MIMES[$mime])) return ['error' => 'Format file harus JPG, PNG, WEBP, PDF, DOC, DOCX, XLS, atau XLSX.'];

        try {
            $destDir = self::ensureDir($dir);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $name = self::randomName(self::DOC_MIMES[$mime]);
        $dest = $destDir . '/' . $name;
        if (!self::moveUploaded($tmp, $dest)) return ['error' => 'Gagal menyimpan dokumen. Periksa permission folder uploads.'];
        @chmod($dest, 0644);
        return trim($dir, '/') . '/' . $name;
    }

    public static function video(array $file, string $dir = 'video') {
        if (!empty($file['tmp_name']) && self::looksMalicious((string) $file['tmp_name'])) return ['error' => 'File ditolak: terdeteksi konten berbahaya.'];
        if ($msg = self::uploadError($file, 'File video')) return ['error' => $msg];
        if ((int) ($file['size'] ?? 0) > (int) cfg('max_upload_video', 26214400)) {
            return ['error' => 'Ukuran video maksimal ' . round((int) cfg('max_upload_video', 26214400) / 1048576) . ' MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::mime($tmp);
        if (!isset(self::VIDEO_MIMES[$mime])) return ['error' => 'Format video harus MP4 atau WEBM.'];

        try {
            $destDir = self::ensureDir($dir);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $name = self::randomName(self::VIDEO_MIMES[$mime]);
        $dest = $destDir . '/' . $name;
        if (!self::moveUploaded($tmp, $dest)) return ['error' => 'Gagal menyimpan video. Periksa permission folder uploads.'];
        @chmod($dest, 0644);
        return trim($dir, '/') . '/' . $name;
    }

    public static function audio(array $file, string $dir = 'music') {
        if (!empty($file['tmp_name']) && self::looksMalicious((string) $file['tmp_name'])) return ['error' => 'File ditolak: terdeteksi konten berbahaya.'];
        if ($msg = self::uploadError($file, 'File musik')) return ['error' => $msg];
        if ((int) ($file['size'] ?? 0) > (int) cfg('max_upload_music')) {
            return ['error' => 'Ukuran musik maksimal ' . round((int) cfg('max_upload_music') / 1048576) . ' MB.'];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::mime($tmp);
        if (!isset(self::AUDIO_MIMES[$mime])) return ['error' => 'Format musik harus MP3, WAV, atau OGG.'];

        try {
            $destDir = self::ensureDir($dir);
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $name = self::randomName(self::AUDIO_MIMES[$mime]);
        $dest = $destDir . '/' . $name;
        if (!self::moveUploaded($tmp, $dest)) return ['error' => 'Gagal menyimpan musik. Periksa permission folder uploads.'];
        @chmod($dest, 0644);
        return trim($dir, '/') . '/' . $name;
    }

    public static function excelTemp(array $file) {
        if ($msg = self::uploadError($file, 'File Excel')) return ['error' => $msg];

        $tmp = (string) ($file['tmp_name'] ?? '');
        $mime = self::mime($tmp);
        $okMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream'
        ];
        $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext !== 'xlsx' || !in_array($mime, $okMimes, true)) {
            return ['error' => 'Gunakan file .xlsx (Excel 2007+). Simpan ulang file lama sebagai .xlsx.'];
        }

        try {
            $destDir = self::ensureDir('tmp');
        } catch (Throwable $e) {
            return ['error' => $e->getMessage()];
        }
        $dest = $destDir . '/' . self::randomName('xlsx');
        if (!self::moveUploaded($tmp, $dest)) return ['error' => 'Gagal menyimpan file Excel sementara.'];
        @chmod($dest, 0600);
        return $dest;
    }


    /**
     * Membuat thumbnail WEBP ter-cache untuk gambar publik. Jika GD/WEBP tidak
     * tersedia, path asli dikembalikan agar tampilan tetap berfungsi.
     */
    public static function thumbnailWebp(string $relPath, int $width = 640, int $height = 420): string {
        $relPath = ltrim(str_replace('\\', '/', trim($relPath)), '/');
        if ($relPath === '' || str_contains($relPath, '..')) return $relPath;

        $uploadRoot = realpath(BASE_PATH . '/uploads');
        $source = realpath(BASE_PATH . '/uploads/' . $relPath);
        if ($uploadRoot === false || $source === false || !is_file($source)
            || !str_starts_with($source, $uploadRoot . DIRECTORY_SEPARATOR)) {
            return $relPath;
        }

        $width = max(120, min(1600, $width));
        $height = max(90, min(1200, $height));
        if (!function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) return $relPath;

        $mime = self::mime($source);
        $createFn = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };
        if (!$createFn || !function_exists($createFn)) return $relPath;

        $dir = dirname($relPath);
        $base = pathinfo($relPath, PATHINFO_FILENAME);
        $hash = substr(hash('sha256', $relPath . '|' . (string) @filemtime($source)), 0, 12);
        $thumbRelDir = ($dir === '.' ? '' : $dir . '/') . 'thumbs';
        try {
            $thumbDir = self::ensureDir($thumbRelDir);
        } catch (Throwable $e) {
            return $relPath;
        }
        $thumbName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $base) . "-{$width}x{$height}-{$hash}.webp";
        $thumb = $thumbDir . '/' . $thumbName;
        if (is_file($thumb) && filesize($thumb) > 0) return $thumbRelDir . '/' . $thumbName;

        $src = @$createFn($source);
        if (!$src) return $relPath;
        $sw = imagesx($src); $sh = imagesy($src);
        if ($sw < 1 || $sh < 1) { imagedestroy($src); return $relPath; }

        // Center-crop agar semua kartu memiliki rasio stabil tanpa distorsi.
        $targetRatio = $width / $height;
        $sourceRatio = $sw / $sh;
        if ($sourceRatio > $targetRatio) {
            $cropH = $sh;
            $cropW = (int) round($sh * $targetRatio);
            $srcX = (int) floor(($sw - $cropW) / 2);
            $srcY = 0;
        } else {
            $cropW = $sw;
            $cropH = (int) round($sw / $targetRatio);
            $srcX = 0;
            $srcY = (int) floor(($sh - $cropH) / 2);
        }

        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefill($dst, 0, 0, $transparent);
        $ok = imagecopyresampled($dst, $src, 0, 0, $srcX, $srcY, $width, $height, $cropW, $cropH)
            && @imagewebp($dst, $thumb, 80);
        imagedestroy($src);
        imagedestroy($dst);

        if (!$ok || !is_file($thumb)) {
            @unlink($thumb);
            return $relPath;
        }
        @chmod($thumb, 0644);
        return $thumbRelDir . '/' . $thumbName;
    }

    public static function delete(?string $relPath): void {
        if (!$relPath) return;
        $uploadRoot = realpath(BASE_PATH . '/uploads');
        if ($uploadRoot === false) return;

        $full = BASE_PATH . '/uploads/' . ltrim(str_replace('\\', '/', $relPath), '/');
        $real = realpath($full);
        if ($real && str_starts_with($real, $uploadRoot . DIRECTORY_SEPARATOR) && is_file($real)) {
            @unlink($real);
        }
    }
}
