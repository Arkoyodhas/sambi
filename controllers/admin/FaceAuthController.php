<?php

class FaceAuthController extends Controller {

    public array $actions = ['accounts', 'challenge', 'login'];

    public function accounts(): void {
        $this->noStore();
        $data = $this->jsonRequest('face-accounts', 20, 60, 8192);
        $this->verifyCsrfJson($data);

        if (setting('face_login_enabled', '1') !== '1') {
            http_response_code(403);
            $this->json(['ok' => false, 'error' => 'Login wajah dinonaktifkan.']);
        }

        $this->cleanupSessionTokens();
        $items = [];
        foreach (FaceAuth::loginAccounts() as $account) {
            $token = bin2hex(random_bytes(32));
            $_SESSION['face_account_tokens'][hash('sha256', $token)] = [
                'user_id' => (int) $account['id'],
                 'expires' => time() + 600,
            ];
            $name = trim((string) $account['name']);
            $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $initials = '';
            foreach (array_slice($parts, 0, 2) as $part) $initials .= mb_substr($part, 0, 1);
            $items[] = [
                'token' => $token,
                'name' => $name,
                'role' => (string) ($account['role_name'] ?? 'Admin'),
                'initials' => strtoupper($initials ?: 'A'),
            ];
        }

        $this->json(['ok' => true, 'accounts' => $items]);
    }

    public function challenge(): void {
        $this->noStore();
        $data = $this->jsonRequest('face-challenge', 12, 300, 8192);
        $this->verifyCsrfJson($data);
        $this->cleanupSessionTokens();

        $selectionToken = strtolower(trim((string) ($data['account_token'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $selectionToken)) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Pilihan akun tidak valid.']);
        }

        $account = $_SESSION['face_account_tokens'][hash('sha256', $selectionToken)] ?? null;
        if (!$account || ($account['expires'] ?? 0) < time()) {
            http_response_code(419);
            $this->json(['ok' => false, 'error' => 'Pilihan akun kedaluwarsa. Pilih akun kembali.']);
        }

        $userId = (int) $account['user_id'];
        if (!FaceAuth::hasUsableProfile($userId)) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Dataset wajah akun ini belum siap digunakan.']);
        }

        // Satu challenge adaptif lebih ramah kamera ponsel: pengguna dapat
        // berkedip atau menggerakkan wajah ke samping. Nonce tetap sekali pakai.
        $steps = ['motion'];
        $nonce = bin2hex(random_bytes(32));
        $_SESSION['face_challenges'][hash('sha256', $nonce)] = [
            'user_id' => $userId,
            'account_hash' => hash('sha256', $selectionToken),
            'steps' => $steps,
            'issued_at' => microtime(true),
            'expires' => time() + 180,
        ];

        $this->json([
            'ok' => true,
            'challenge' => [
                'nonce' => $nonce,
                'steps' => $steps,
                'expires_in' => 180,
            ],
        ]);
    }

    public function login(): void {
        $this->noStore();
        $data = $this->jsonRequest('face-login', 8, 300, 262144);
        $this->verifyCsrfJson($data);
        $this->cleanupSessionTokens();

        $nonce = strtolower(trim((string) ($data['challenge_nonce'] ?? '')));
        $selectionToken = strtolower(trim((string) ($data['account_token'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $nonce) || !preg_match('/^[a-f0-9]{64}$/', $selectionToken)) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Tantangan login tidak valid. Ulangi scan.']);
        }

        $challengeKey = hash('sha256', $nonce);
        $challenge = $_SESSION['face_challenges'][$challengeKey] ?? null;
        // Sekali pakai: hapus sebelum verifikasi agar request yang sama tidak dapat diputar ulang.
        unset($_SESSION['face_challenges'][$challengeKey]);

        if (!$challenge || ($challenge['expires'] ?? 0) < time()) {
            http_response_code(419);
            $this->json(['ok' => false, 'error' => 'Tantangan login kedaluwarsa. Ulangi scan.']);
        }
        if (!hash_equals((string) ($challenge['account_hash'] ?? ''), hash('sha256', $selectionToken))) {
            http_response_code(403);
            $this->json(['ok' => false, 'error' => 'Akun dan tantangan tidak cocok.']);
        }

        $userId = (int) ($challenge['user_id'] ?? 0);
        if (!Security::rateLimitPersistent('face-login-user', 6, 600, (string) $userId)) {
            http_response_code(429);
            $this->json(['ok' => false, 'error' => 'Terlalu banyak percobaan untuk akun ini. Coba kembali beberapa menit lagi.']);
        }

        $descriptor = FaceAuth::normalizeDescriptor($data['descriptor'] ?? null);
        if (!$descriptor || !$this->validEvidence($challenge, $data['evidence'] ?? null)) {
            http_response_code(422);
            $this->json(['ok' => false, 'error' => 'Bukti pemindaian tidak lengkap. Ikuti instruksi gerakan dari kamera.']);
        }

        $user = db_row(
            "SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1",
            [$userId]
        );
        if (!$user || (!empty($user['locked_until']) && strtotime($user['locked_until']) > time())) {
            http_response_code(401);
            $this->json(['ok' => false, 'error' => 'Login wajah tidak dapat digunakan untuk akun ini.']);
        }

        $result = FaceAuth::verify($userId, $descriptor);
        if (empty($result['ok'])) {
            log_activity(
                'login_gagal',
                'auth',
                'Login wajah tertutup ditolak: user=' . $userId
                . ' similarity=' . round((float) ($result['similarity'] ?? -1), 4)
                . ' threshold=' . round((float) ($result['threshold'] ?? FaceAuth::MIN_LOGIN_THRESHOLD), 4)
                . ' votes=' . (int) ($result['votes'] ?? 0)
                . '/' . (int) ($result['required_votes'] ?? 1)
            );
            http_response_code(401);
            $this->json(['ok' => false, 'error' => 'Wajah tidak sesuai dengan dataset akun yang dipilih.']);
        }

        $login = Auth::loginUser($user, false, 'face');
        if (empty($login['ok'])) {
            http_response_code(401);
            $this->json(['ok' => false, 'error' => 'Login wajah tidak dapat diselesaikan untuk akun ini.']);
        }

        unset($_SESSION['face_account_tokens'], $_SESSION['face_challenges']);
        $this->json([
            'ok' => true,
            'redirect' => url('admin/dashboard'),
        ]);
    }

    private function validEvidence(array $challenge, $input): bool {
        if (!is_array($input)) return false;
        $events = $input['events'] ?? null;
        if (!is_array($events)) return false;

        $expected = array_values($challenge['steps'] ?? []);
        if (count($events) !== count($expected)) return false;

        $lastAt = -1;
        $actual = [];
        foreach ($events as $event) {
            if (!is_array($event)) return false;
            $step = (string) ($event['step'] ?? '');
            $at = (int) ($event['at'] ?? -1);
            if (!in_array($step, ['motion', 'blink', 'turn_left', 'turn_right'], true) || $at < 0 || $at <= $lastAt || $at > 60000) return false;
            $actual[] = $step;
            $lastAt = $at;
        }

        if ($actual !== $expected) return false;
        $elapsed = (int) ($input['elapsed_ms'] ?? -1);
        $serverElapsed = (microtime(true) - (float) ($challenge['issued_at'] ?? microtime(true))) * 1000;
        return $elapsed >= 350
            && $elapsed <= 60000
            && $lastAt <= $elapsed
            && $serverElapsed >= 300
            && $serverElapsed <= 190000;
    }

    private function jsonRequest(string $endpoint, int $max, int $window, int $maxBytes): array {
        try {
            return Security::publicJsonRequest($endpoint, $max, $window, $maxBytes);
        } catch (RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function verifyCsrfJson(array $data): void {
        $csrf = (string) ($data['csrf_token'] ?? '');
        if ($csrf === '' || !hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $csrf)) {
            http_response_code(419);
            $this->json(['ok' => false, 'error' => 'Sesi login kedaluwarsa. Muat ulang halaman.']);
        }
    }

    private function cleanupSessionTokens(): void {
        foreach (['face_account_tokens', 'face_challenges'] as $bucket) {
            if (!isset($_SESSION[$bucket]) || !is_array($_SESSION[$bucket])) $_SESSION[$bucket] = [];
            foreach ($_SESSION[$bucket] as $key => $item) {
                if (($item['expires'] ?? 0) < time()) unset($_SESSION[$bucket][$key]);
            }
            if (count($_SESSION[$bucket]) > 100) $_SESSION[$bucket] = [];
        }
    }

    private function noStore(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }
}
