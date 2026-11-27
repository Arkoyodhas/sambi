<?php

/** Mode pemeliharaan: aktif via pengaturan Superadmin atau file storage/maintenance.flag. */
class Maintenance {

    public static function flagFile(): string {
        return BASE_PATH . '/storage/maintenance.flag';
    }

    public static function active(): bool {
        if (is_file(self::flagFile())) return true;
        try {
            return setting('maintenance_mode') === '1';
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function render(): void {
        http_response_code(503);
        header('Retry-After: 1800');
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        $name = 'Website Desa';
        $msg = 'Kami sedang melakukan pemeliharaan sistem agar layanan semakin baik. Silakan kembali beberapa saat lagi.';
        $until = '';
        $phone = '';
        try {
            $name = setting('village_name') ?: $name;
            $msg = setting('maintenance_message') ?: $msg;
            $until = setting('maintenance_until');
            $phone = setting('whatsapp') ?: setting('phone');
        } catch (Throwable $e) {
        }
        require BASE_PATH . '/views/maintenance.php';
    }
}
