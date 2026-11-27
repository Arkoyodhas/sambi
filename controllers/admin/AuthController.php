<?php

class AuthController extends Controller {

    public function login(): void {
        if (auth_user()) redirect('admin/dashboard');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (!Security::rateLimit('login', 10, 300)) { flash('danger', 'Terlalu banyak percobaan. Tunggu beberapa menit.'); redirect('admin/login'); }
            if (!Security::verifyCaptcha($_POST['captcha'] ?? '')) { flash('danger', 'Jawaban captcha salah.'); redirect('admin/login'); }
            $res = Auth::attempt(trim($_POST['login'] ?? ''), $_POST['password'] ?? '', isset($_POST['remember']));
            if ($res['ok']) redirect('admin/dashboard');
            flash('danger', $res['msg']);
            redirect('admin/login');
        }
        $this->raw('admin/login', ['pageTitle' => 'Login Admin', 'captcha' => Security::captchaQuestion(), 'mode' => 'login']);
    }

    public function logout(): void {
        Auth::logout();
        flash('success', 'Anda telah keluar dengan aman.');
        redirect('admin/login');
    }

    public function forgot(): void {
        $token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
        $uid = (int) ($_POST['uid'] ?? $_GET['uid'] ?? 0);
        $isResetSubmit = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_baru']);

        if ($isResetSubmit) {
            verify_csrf();
            $isOtp = preg_match('/^\d{6}$/', $token) === 1;
            if ($isOtp) {
                $uid = (int) ($_SESSION['reset_otp_uid'] ?? 0);
                if ($uid > 0 && !Security::rateLimitPersistent('reset-otp-' . $uid, 5, 600)) {
                    db_update('users', ['reset_token' => null, 'reset_expires' => null], 'id = ?', [$uid]);
                    unset($_SESSION['reset_otp_uid'], $_SESSION['reset_otp_mask']);
                    flash('danger', 'Terlalu banyak percobaan kode OTP. Kode dibatalkan, silakan minta kode baru.');
                    redirect('admin/lupa-password');
                }
            }
            if (!Security::rateLimit('reset-password-' . $uid, 5, 600)) {
                flash('danger', 'Terlalu banyak percobaan reset. Silakan coba lagi beberapa menit.');
                redirect('admin/lupa-password?token=' . rawurlencode($token) . '&uid=' . $uid);
            }

            $u = $this->validResetUser($uid, $token);
            if (!$u && $isOtp && $uid > 0) {
                $fails = (int) Cache::get('otp-fail-' . $uid, 0) + 1;
                Cache::put('otp-fail-' . $uid, $fails, 3600);
                if ($fails % 5 === 0) {
                    db_update('users', ['reset_token' => null, 'reset_expires' => null], 'id = ?', [$uid]);
                    unset($_SESSION['reset_otp_uid'], $_SESSION['reset_otp_mask']);
                    flash('danger', 'Terlalu banyak percobaan kode OTP. Kode dibatalkan, silakan minta kode baru.');
                    redirect('admin/lupa-password');
                }
            }
            if (!$u) {
                flash('danger', 'Kode OTP / token reset tidak valid atau kedaluwarsa.');
                redirect(isset($_SESSION['reset_otp_uid']) ? 'admin/lupa-password?step=otp' : 'admin/lupa-password');
            }

            $pass = (string) ($_POST['password_baru'] ?? '');
            $confirm = (string) ($_POST['password_konfirmasi'] ?? '');
            if (strlen($pass) < 8) {
                flash('danger', 'Password minimal 8 karakter.');
                redirect(isset($_SESSION['reset_otp_uid']) ? 'admin/lupa-password?step=otp' : 'admin/lupa-password?token=' . rawurlencode($token) . '&uid=' . $uid);
            }
            if ($pass !== $confirm) {
                flash('danger', 'Konfirmasi password tidak cocok.');
                redirect(isset($_SESSION['reset_otp_uid']) ? 'admin/lupa-password?step=otp' : 'admin/lupa-password?token=' . rawurlencode($token) . '&uid=' . $uid);
            }

            db_update('users', [
                'password' => password_hash($pass, PASSWORD_BCRYPT),
                'reset_token' => null,
                'reset_expires' => null,
                'remember_token' => null,
                'must_change_password' => 0,
                'failed_attempts' => 0,
                'locked_until' => null,
            ], 'id = ?', [$uid]);

            unset($_SESSION['reset_otp_uid'], $_SESSION['reset_otp_mask']);
            Cache::put('otp-fail-' . $uid, 0, 60);
            Cache::put('otp-req-' . $uid, 0, 60);
            log_activity('reset_password', 'auth', 'Password akun #' . $uid . ' berhasil direset melalui OTP WhatsApp.');
            flash('success', 'Password berhasil diganti. Silakan login dengan password baru.');
            redirect('admin/login');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if (!Security::rateLimit('forgot', 3, 600) || !Security::rateLimitPersistent('forgot-ip', 6, 900)) {
                flash('danger', 'Terlalu banyak permintaan. Silakan coba lagi beberapa menit.');
                redirect('admin/lupa-password');
            }

            $identity = trim((string) ($_POST['identity'] ?? ''));
            $phone = normalize_phone_id($identity);
            $u = null;
            if ($identity !== '' && mb_strlen($identity) <= 100) {
                $u = db_row("SELECT id, name, phone FROM users WHERE is_active = 1 AND username = ? LIMIT 1", [$identity]);
                if (!$u && $phone !== '') {
                    foreach (db_all("SELECT id, name, phone FROM users WHERE is_active = 1 AND phone IS NOT NULL AND phone <> ''") as $cand) {
                        if (Fonnte::normalize((string) $cand['phone']) === Fonnte::normalize($phone)) { $u = $cand; break; }
                    }
                }
            }
            if (!Fonnte::enabled()) {
                flash('danger', 'Layanan WhatsApp (Fonnte) belum aktif. Hubungi Super Admin untuk reset password.');
                redirect('admin/lupa-password');
            }
            $reqCount = $u ? (int) Cache::get('otp-req-' . $u['id'], 0) : 0;
            if ($u) Cache::put('otp-req-' . $u['id'], $reqCount + 1, 3600);
            if ($u && trim((string) $u['phone']) !== '' && $reqCount < 3 && (int) Cache::get('otp-fail-' . $u['id'], 0) < 10) {
                $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                db_update('users', [
                    'reset_token' => hash('sha256', $otp),
                    'reset_expires' => date('Y-m-d H:i:s', time() + 600),
                ], 'id = ?', [$u['id']]);
                $r = Fonnte::send((string) $u['phone'], "🔐 *Kode Reset Password*\n\nKode OTP Anda: *{$otp}*\nBerlaku 10 menit. Jangan bagikan kode ini kepada siapa pun, termasuk petugas.\n\n- Admin " . setting('village_name'));
                log_activity('forgot_password', 'auth', 'OTP reset password via WA untuk akun #' . $u['id'] . ': ' . ($r['ok'] ? 'terkirim' : 'gagal - ' . $r['error']));
                $_SESSION['reset_otp_uid'] = (int) $u['id'];
                $_SESSION['reset_otp_mask'] = substr(Fonnte::normalize((string) $u['phone']), 0, 4) . '••••' . substr(Fonnte::normalize((string) $u['phone']), -3);
            } else {
                $_SESSION['reset_otp_uid'] = 0;
                $_SESSION['reset_otp_mask'] = 'yang terdaftar';
            }
            flash('success', 'Jika akun terdaftar dan memiliki nomor WhatsApp, kode OTP telah dikirim.');
            redirect('admin/lupa-password?step=otp');
        }

        if (($_GET['step'] ?? '') === 'otp' && isset($_SESSION['reset_otp_uid'])) {
            $this->raw('admin/login', [
                'pageTitle' => 'Verifikasi OTP',
                'mode' => 'otp',
                'token' => '',
                'uid' => 0,
                'maskedPhone' => (string) ($_SESSION['reset_otp_mask'] ?? ''),
                'captcha' => '',
            ]);
            return;
        }

        if ($token !== '' || $uid > 0) {
            $u = $this->validResetUser($uid, $token);
            if (!$u) {
                flash('danger', 'Token reset tidak valid atau kedaluwarsa.');
                redirect('admin/lupa-password');
            }

            $this->raw('admin/login', [
                'pageTitle' => 'Reset Password',
                'mode' => 'reset',
                'token' => $token,
                'uid' => $uid,
                'captcha' => '',
            ]);
            return;
        }

        $this->raw('admin/login', ['pageTitle' => 'Lupa Password', 'mode' => 'forgot', 'captcha' => '']);
    }


    private function resetBaseUrl(): string {
        $configured = rtrim((string) cfg('base_url', ''), '/');
        if ($configured !== '' && filter_var($configured, FILTER_VALIDATE_URL)
            && in_array(strtolower((string) parse_url($configured, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return $configured;
        }

        $serverName = strtolower(trim((string) ($_SERVER['SERVER_NAME'] ?? 'localhost')));
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?|localhost)$/', $serverName)) {
            $serverName = 'localhost';
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $port = (int) ($_SERVER['SERVER_PORT'] ?? ($https ? 443 : 80));
        $portPart = ($https && $port !== 443) || (!$https && $port !== 80) ? ':' . $port : '';
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');

        return $scheme . '://' . $serverName . $portPart . $dir;
    }

    private function validResetUser(int $uid, string $token): ?array {
        if ($uid < 1 || !preg_match('/^([a-f0-9]{64}|\d{6})$/i', $token)) return null;

        $u = db_row(
            "SELECT id, reset_token, reset_expires
             FROM users
             WHERE id = ? AND is_active = 1 AND reset_expires IS NOT NULL AND reset_expires > NOW()
             LIMIT 1",
            [$uid]
        );
        if (!$u || empty($u['reset_token'])) return null;

        return hash_equals((string) $u['reset_token'], hash('sha256', $token)) ? $u : null;
    }
}
