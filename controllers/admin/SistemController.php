<?php

/** Superadmin: mode maintenance + backup / rollback database. */
class SistemController extends Controller {

    public array $actions = ['index', 'maintenance', 'backup', 'download', 'restore', 'hapus'];
    private const KEEP = 20;

    public function __construct() { Auth::requireSuperAdmin(); }

    private function dir(): string {
        $d = BASE_PATH . '/storage/backups';
        if (!is_dir($d)) @mkdir($d, 0750, true);
        if (!is_file($d . '/.htaccess')) @file_put_contents($d . '/.htaccess', "Require all denied\nDeny from all\n");
        if (!is_file($d . '/index.html')) @file_put_contents($d . '/index.html', '');
        return $d;
    }

    /** Nama file aman (anti path traversal): hanya pola backup-YYYYmmdd-His[-label].sql.gz */
    private function safeFile(string $name): ?string {
        $name = basename($name);
        if (!preg_match('/^backup-\d{8}-\d{6}(-[a-z0-9-]{1,30})?\.sql\.gz$/', $name)) return null;
        $path = $this->dir() . '/' . $name;
        return is_file($path) ? $path : null;
    }

    private function backups(): array {
        $out = [];
        foreach (glob($this->dir() . '/backup-*.sql.gz') ?: [] as $f) {
            $out[] = ['name' => basename($f), 'size' => filesize($f), 'time' => filemtime($f)];
        }
        usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
        return $out;
    }

    public function index(): void {
        $this->adminView('sistem', [
            'pageTitle' => 'Maintenance & Backup',
            'active' => Maintenance::active(),
            'flagActive' => is_file(Maintenance::flagFile()),
            'backups' => $this->backups(),
        ]);
    }

    public function maintenance(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/sistem');
        verify_csrf();
        $on = ($_POST['maintenance_mode'] ?? '') === '1';
        set_setting('maintenance_mode', $on ? '1' : '0');
        set_setting('maintenance_message', mb_substr(strip_tags(trim((string) ($_POST['maintenance_message'] ?? ''))), 0, 500));
        set_setting('maintenance_until', mb_substr(strip_tags(trim((string) ($_POST['maintenance_until'] ?? ''))), 0, 60));
        if (!$on && is_file(Maintenance::flagFile()) && isset($_POST['clear_flag'])) @unlink(Maintenance::flagFile());
        Cache::bumpVersion('public');
        log_activity('edit', 'sistem', 'Mode maintenance ' . ($on ? 'DIAKTIFKAN' : 'dimatikan'));
        flash('success', 'Mode maintenance ' . ($on ? 'aktif. Pengunjung publik melihat halaman pemeliharaan; admin tetap bisa login.' : 'dimatikan. Website kembali normal.'));
        redirect('admin/sistem');
    }

    public function backup(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/sistem');
        verify_csrf();
        try {
            $name = $this->createBackup('manual');
            log_activity('create', 'sistem', 'Backup database dibuat: ' . $name);
            flash('success', 'Backup database berhasil dibuat: ' . $name);
        } catch (Throwable $e) {
            error_log('Backup gagal: ' . $e->getMessage());
            flash('danger', 'Backup gagal dibuat. Periksa izin tulis folder storage/backups.');
        }
        redirect('admin/sistem');
    }

    public function download(): void {
        $path = $this->safeFile((string) ($_GET['file'] ?? ''));
        if (!$path) { flash('danger', 'File backup tidak ditemukan.'); redirect('admin/sistem'); }
        log_activity('export', 'sistem', 'Unduh backup ' . basename($path));
        header('Content-Type: application/gzip');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    public function hapus(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/sistem');
        verify_csrf();
        $path = $this->safeFile((string) ($_POST['file'] ?? ''));
        if ($path) { @unlink($path); log_activity('delete', 'sistem', 'Hapus backup ' . basename($path)); flash('success', 'Backup dihapus.'); }
        redirect('admin/sistem');
    }

    public function restore(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('admin/sistem');
        verify_csrf();
        $u = Auth::user();
        $path = $this->safeFile((string) ($_POST['file'] ?? ''));
        if (!$path) { flash('danger', 'File backup tidak valid.'); redirect('admin/sistem'); }
        if (trim((string) ($_POST['confirm'] ?? '')) !== 'PULIHKAN' || !password_verify((string) ($_POST['password'] ?? ''), (string) $u['password'])) {
            flash('danger', 'Konfirmasi gagal. Ketik PULIHKAN dan masukkan password Anda dengan benar.');
            redirect('admin/sistem');
        }
        @set_time_limit(600);
        try {
            $safety = $this->createBackup('sebelum-rollback');
            try {
                $this->runSqlGz($path);
            } catch (Throwable $inner) {
                // Gagal di tengah jalan: kembalikan otomatis ke kondisi sebelum rollback
                $this->runSqlGz($this->dir() . '/' . $safety);
                throw new RuntimeException('Backup tidak dapat dipulihkan (' . $inner->getMessage() . '). Data dikembalikan ke kondisi sebelumnya.');
            }
            Cache::bumpVersion('public');
            log_activity('restore', 'sistem', 'Rollback database ke ' . basename($path) . ' (cadangan otomatis: ' . $safety . ')');
            flash('success', 'Database berhasil dipulihkan ke ' . basename($path) . '. Cadangan kondisi sebelumnya disimpan sebagai ' . $safety . '.');
        } catch (Throwable $e) {
            error_log('Restore gagal: ' . $e->getMessage());
            flash('danger', 'Pemulihan gagal: ' . e($e->getMessage()));
        }
        redirect('admin/sistem');
    }

    private function createBackup(string $label): string {
        $pdo = db();
        $name = 'backup-' . date('Ymd-His') . '-' . preg_replace('/[^a-z0-9-]/', '', strtolower($label)) . '.sql.gz';
        $gz = gzopen($this->dir() . '/' . $name, 'wb6');
        if (!$gz) throw new RuntimeException('Tidak bisa menulis file backup.');
        $end = "\n--#END#\n";
        gzwrite($gz, "-- Backup " . setting('village_name') . " " . date('c') . "\nSET NAMES utf8mb4;" . $end . "SET FOREIGN_KEY_CHECKS=0;" . $end);
        foreach ($pdo->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_NUM) as [$table]) {
            $q = '`' . str_replace('`', '``', $table) . '`';
            $create = $pdo->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1];
            gzwrite($gz, "DROP TABLE IF EXISTS $q;" . $end . $create . ";" . $end);
            $cols = [];
            foreach ($pdo->query("SHOW COLUMNS FROM $q")->fetchAll(PDO::FETCH_ASSOC) as $c) {
                if (stripos((string) $c['Extra'], 'GENERATED') === false) $cols[] = '`' . str_replace('`', '``', $c['Field']) . '`';
            }
            if (!$cols) continue;
            $colList = implode(',', $cols);
            $stmt = $pdo->query("SELECT $colList FROM $q", PDO::FETCH_NUM);
            $batch = [];
            while ($row = $stmt->fetch()) {
                $batch[] = '(' . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), $row)) . ')';
                if (count($batch) >= 200) { gzwrite($gz, "INSERT INTO $q ($colList) VALUES " . implode(",\n", $batch) . ";" . $end); $batch = []; }
            }
            if ($batch) gzwrite($gz, "INSERT INTO $q ($colList) VALUES " . implode(",\n", $batch) . ";" . $end);
        }
        gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;" . $end);
        gzclose($gz);
        $all = $this->backups();
        foreach (array_slice($all, self::KEEP) as $old) @unlink($this->dir() . '/' . $old['name']);
        return $name;
    }

    /** Jalankan dump buatan createBackup (setiap pernyataan dipisah baris penanda --#END#). */
    private function runSqlGz(string $path): void {
        $pdo = db();
        $gz = gzopen($path, 'rb');
        if (!$gz) throw new RuntimeException('File backup tidak bisa dibaca.');
        $buf = '';
        $pdo->exec("SET SESSION sql_mode = ''");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        while (!gzeof($gz)) {
            $line = gzgets($gz, 1 << 20);
            if ($line === false) break;
            if ($buf === '' && (str_starts_with($line, '-- ') || trim($line) === '')) continue;
            if (rtrim($line, "\r\n") === '--#END#') {
                $sql = trim($buf);
                $buf = '';
                if ($sql !== '') $pdo->exec($sql);
                continue;
            }
            $buf .= $line;
        }
        gzclose($gz);
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}
