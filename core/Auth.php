<?php

class Auth {

    public static function user(): ?array {
        static $user = false;
        if ($user !== false) return $user;
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            // session timeout
            $timeout = (int) cfg('session_timeout', 3600);
            if (!empty($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > $timeout) {
                self::logout(false);
                return null;
            }
            $_SESSION['last_activity'] = time();
            $user = db_row("SELECT u.*, r.slug AS role_slug, r.name AS role_name
                            FROM users u JOIN roles r ON r.id = u.role_id
                            WHERE u.id = ? AND u.is_active = 1", [$_SESSION['user_id']]);
        } elseif (!empty($_COOKIE['desa_remember'])) {
            $user = self::loginFromRemember($_COOKIE['desa_remember']);
        }
        return $user;
    }

    public static function attempt(string $login, string $password, bool $remember = false): array {
        $u = db_row("SELECT * FROM users WHERE (username = ? OR email = ?) LIMIT 1", [$login, $login]);
        if (!$u) return ['ok' => false, 'msg' => 'Username atau password salah.'];
        if (!$u['is_active']) return ['ok' => false, 'msg' => 'Akun dinonaktifkan. Hubungi Super Admin.'];
        if ($u['locked_until'] && strtotime($u['locked_until']) > time()) {
            return ['ok' => false, 'msg' => 'Akun terkunci sementara. Coba lagi setelah ' . date('H:i', strtotime($u['locked_until'])) . '.'];
        }
        if (!password_verify($password, $u['password'])) {
            $attempts = (int) $u['failed_attempts'] + 1;
            $data = ['failed_attempts' => $attempts];
            if ($attempts >= (int) cfg('max_login_attempts', 5)) {
                $data['locked_until'] = date('Y-m-d H:i:s', time() + 60 * (int) cfg('lockout_minutes', 15));
                $data['failed_attempts'] = 0;
            }
            db_update('users', $data, 'id = ?', [$u['id']]);
            log_activity('login_gagal', 'auth', 'Gagal login: ' . $login);
            return ['ok' => false, 'msg' => 'Username atau password salah.'];
        }
        return self::loginUser($u, $remember, 'password');
    }

    /** Selesaikan login setelah faktor autentikasi tervalidasi. */
    public static function loginUser(array $u, bool $remember = false, string $method = 'password'): array {
        if (empty($u['id']) || empty($u['is_active'])) return ['ok' => false, 'msg' => 'Akun tidak aktif.'];
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $u['id'];
        $_SESSION['last_activity'] = time();
        $_SESSION['auth_method'] = $method;

        $upd = ['failed_attempts' => 0, 'locked_until' => null, 'last_login_at' => date('Y-m-d H:i:s')];
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $upd['remember_token'] = hash('sha256', $token);
            setcookie('desa_remember', $u['id'] . ':' . $token, [
                'expires' => time() + 86400 * 30,
                'path' => '/',
                'httponly' => true,
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'samesite' => 'Lax',
            ]);
        }
        db_update('users', $upd, 'id = ?', [(int) $u['id']]);
        log_activity('login', 'auth', 'Login berhasil via ' . $method . ': ' . ($u['username'] ?? $u['id']));
        return ['ok' => true, 'user' => $u];
    }

    private static function loginFromRemember(string $cookie): ?array {
        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) return null;
        [$id, $token] = $parts;
        $u = db_row("SELECT u.*, r.slug AS role_slug, r.name AS role_name FROM users u
                     JOIN roles r ON r.id = u.role_id
                     WHERE u.id = ? AND u.is_active = 1", [(int) $id]);
        if ($u && $u['remember_token'] && hash_equals($u['remember_token'], hash('sha256', $token))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['last_activity'] = time();
            return $u;
        }
        setcookie('desa_remember', '', time() - 3600, '/');
        return null;
    }

    public static function logout(bool $log = true): void {
        if ($log && !empty($_SESSION['user_id'])) {
            db_update('users', ['remember_token' => null], 'id = ?', [$_SESSION['user_id']]);
            log_activity('logout', 'auth', 'Logout');
        }
        $_SESSION = [];
        setcookie('desa_remember', '', time() - 3600, '/');
        session_regenerate_id(true);
    }

    public static function permissions(): array {
        static $perms = null;
        if ($perms !== null) return $perms;

        $u = self::user();
        if (!$u) return $perms = [];
        if (($u['role_slug'] ?? '') === 'super-admin') return $perms = ['*'];

        $rows = db_all(
            "SELECT p.slug
             FROM role_permissions rp
             JOIN permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ?",
            [(int) $u['role_id']]
        );
        $resolved = array_fill_keys(array_column($rows, 'slug'), true);

        // Override pengguna bersifat fail-safe. Instalasi lama yang belum
        // mengimpor migrasi tetap memakai izin role tanpa menyebabkan login error.
        try {
            $overrides = db_all(
                "SELECT p.slug, up.effect
                 FROM user_permissions up
                 JOIN permissions p ON p.id = up.permission_id
                 WHERE up.user_id = ?",
                [(int) $u['id']]
            );
            foreach ($overrides as $override) {
                $slug = (string) $override['slug'];
                if ($override['effect'] === 'deny') {
                    unset($resolved[$slug]);
                } else {
                    $resolved[$slug] = true;
                }
            }
        } catch (Throwable $e) {
            // Tabel user_permissions belum tersedia pada database lama.
        }

        return $perms = array_keys($resolved);
    }

    public static function can(string $perm): bool {
        $perms = self::permissions();
        return in_array('*', $perms, true) || in_array($perm, $perms, true);
    }

    public static function isSuperAdmin(): bool {
        $user = self::user();
        return ($user['role_slug'] ?? '') === 'super-admin';
    }

    public static function requireLogin(): array {
        $u = self::user();
        if (!$u) redirect('admin/login');
        $path = '/' . trim(rawurldecode((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/')), '/');
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) $path = '/' . ltrim(substr($path, strlen($basePath)), '/');
        $onAkun = (bool) preg_match('#^/admin/akun(/|$)#', $path);
        if ($u['must_change_password'] && !$onAkun) {
            flash('warning', 'Demi keamanan, silakan ganti password default Anda terlebih dahulu.');
            redirect('admin/akun');
        }
        if (trim((string) ($u['phone'] ?? '')) === '' && !$onAkun && !preg_match('#^/admin/logout(/|$)#', $path)) {
            flash('warning', 'Nomor WhatsApp akun Anda belum diisi. Wajib diisi sebelum memakai fitur lain (dipakai untuk notifikasi & reset password).');
            redirect('admin/akun');
        }
        return $u;
    }

    public static function requirePermission(string $perm): void {
        if (Cache::get('schema:permissions:v2') !== true) {
            try { AccessControl::ensureSchema(); Cache::put('schema:permissions:v2', true, 86400); } catch (Throwable $e) { error_log('perm schema: ' . $e->getMessage()); }
        }
        self::requireLogin();
        if (!self::can($perm)) {
            http_response_code(403);
            exit('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>403</h2><p>Anda tidak memiliki izin untuk mengakses modul ini.</p><a href="' . url('admin/dashboard') . '">Kembali ke Dashboard</a></div>');
        }
    }

    public static function requireSuperAdmin(): array {
        $user = self::requireLogin();
        if (($user['role_slug'] ?? '') !== 'super-admin') {
            http_response_code(403);
            exit('<div style="font-family:sans-serif;padding:40px;text-align:center"><h2>403</h2><p>Pengaturan user dan role hanya dapat diakses oleh Super Admin.</p><a href="' . url('admin/dashboard') . '">Kembali ke Dashboard</a></div>');
        }
        return $user;
    }
}
