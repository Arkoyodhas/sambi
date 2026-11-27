<?php

/**
 * Penyimpanan dan pencocokan descriptor wajah admin.
 *
 * Foto/video kamera tidak disimpan. Descriptor dienkripsi sebelum masuk DB.
 * Login memakai verifikasi tertutup terhadap akun yang dipilih pengguna.
 */
class FaceAuth {

    public const MODEL_VERSION = 'human-3.3.6-faceres';
    public const EXPECTED_DESCRIPTOR_SIZE = 1024;
    public const DEFAULT_THRESHOLD = 0.815;
    public const MIN_LOGIN_THRESHOLD = 0.805;
    public const ENROLLMENT_CONFLICT_THRESHOLD = 0.840;
    public const OWN_DUPLICATE_THRESHOLD = 0.985;
    public const MAX_PROFILES_PER_USER = 5;
    public const MIN_ENROLLMENT_SAMPLES = 5;
    public const MIN_USABLE_SAMPLES = 3;
    public const MIN_TEMPLATE_AGREEMENT = 2;

    private static ?bool $schemaReady = null;

    public static function ensureSchema(): bool {
        if (self::$schemaReady !== null) return self::$schemaReady;

        try {
            db_exec("CREATE TABLE IF NOT EXISTS admin_face_profiles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id INT UNSIGNED NOT NULL,
                label VARCHAR(120) NOT NULL DEFAULT 'Dataset wajah',
                descriptor LONGTEXT NOT NULL,
                sample_count SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                threshold DECIMAL(4,3) NOT NULL DEFAULT 0.815,
                model_version VARCHAR(80) NOT NULL DEFAULT 'human-3.3.6-faceres',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_by INT UNSIGNED DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_face_user_active (user_id, is_active),
                KEY idx_face_model_active (model_version, is_active),
                CONSTRAINT fk_face_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_face_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('FaceAuth schema: ' . $e->getMessage());
            self::$schemaReady = false;
        }

        return self::$schemaReady;
    }

    public static function normalizeDescriptor($input): ?array {
        if (is_string($input)) $input = json_decode($input, true);
        if (!is_array($input) || count($input) !== self::EXPECTED_DESCRIPTOR_SIZE) return null;

        $out = [];
        $norm = 0.0;
        foreach ($input as $value) {
            if (!is_numeric($value)) return null;
            $float = (float) $value;
            if (!is_finite($float) || abs($float) > 100) return null;
            $out[] = $float;
            $norm += $float * $float;
        }

        $norm = sqrt($norm);
        if ($norm < 0.000001) return null;
        foreach ($out as &$value) $value /= $norm;
        unset($value);
        return $out;
    }

    public static function normalizeTemplates($input, int $max = 7): array {
        if (is_string($input)) $input = json_decode($input, true);
        if (!is_array($input)) return [];

        $out = [];
        foreach (array_slice($input, 0, max(1, $max)) as $template) {
            $normalized = self::normalizeDescriptor($template);
            if ($normalized) $out[] = $normalized;
        }
        return $out;
    }

    public static function cosine(array $a, array $b): float {
        if (count($a) !== self::EXPECTED_DESCRIPTOR_SIZE || count($b) !== self::EXPECTED_DESCRIPTOR_SIZE) return -1.0;
        $dot = 0.0;
        foreach ($a as $index => $value) $dot += $value * $b[$index];
        return max(-1.0, min(1.0, $dot));
    }

    private static function effectiveThreshold($stored): float {
        $value = (float) $stored;
        // Nilai 0.840 adalah default versi sebelumnya yang terlalu ketat pada
        // kamera ponsel. Kalibrasikan ke default baru tanpa melemahkan profil
        // yang sengaja memakai ambang khusus di luar rentang legacy.
        if ($value >= 0.839 && $value <= 0.841) $value = self::DEFAULT_THRESHOLD;
        return max(self::MIN_LOGIN_THRESHOLD, min(0.900, $value));
    }

    public static function hasUsableProfile(int $userId): bool {
        if (!self::ensureSchema()) return false;
        return (bool) db_val(
            "SELECT 1 FROM admin_face_profiles
             WHERE user_id = ?
               AND is_active = 1
               AND sample_count >= ?
               AND created_by = user_id
               AND model_version = ?
             LIMIT 1",
            [$userId, self::MIN_USABLE_SAMPLES, self::MODEL_VERSION]
        );
    }

    /** Akun yang aman ditampilkan pada pemilih login wajah. */
    public static function loginAccounts(): array {
        if (!self::ensureSchema()) return [];
        return db_all(
            "SELECT u.id, u.name, u.photo, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.is_active = 1
               AND (u.locked_until IS NULL OR u.locked_until <= NOW())
               AND EXISTS (
                    SELECT 1 FROM admin_face_profiles p
                    WHERE p.user_id = u.id
                      AND p.is_active = 1
                      AND p.sample_count >= ?
                      AND p.created_by = p.user_id
                      AND p.model_version = ?
               )
             ORDER BY u.name ASC, u.id ASC",
            [self::MIN_USABLE_SAMPLES, self::MODEL_VERSION]
        );
    }

    public static function profilesForUser(int $userId): array {
        if (!self::ensureSchema()) return [];
        return db_all(
            "SELECT id, user_id, label, sample_count, threshold, model_version,
                    CASE WHEN is_active = 1 AND created_by = user_id AND model_version = ?
                         THEN 1 ELSE 0 END AS is_active,
                    created_at, updated_at
             FROM admin_face_profiles
             WHERE user_id = ? AND created_by = user_id AND model_version = ?
             ORDER BY id DESC",
            [self::MODEL_VERSION, $userId, self::MODEL_VERSION]
        );
    }

    public static function activeDescriptors(int $userId): array {
        if (!self::ensureSchema()) return [];
        return db_all(
            "SELECT id, descriptor, threshold, sample_count
             FROM admin_face_profiles
             WHERE user_id = ?
               AND is_active = 1
               AND sample_count >= ?
               AND created_by = user_id
               AND model_version = ?
             ORDER BY id DESC",
            [$userId, self::MIN_USABLE_SAMPLES, self::MODEL_VERSION]
        );
    }

    /**
     * Validasi pendaftaran:
     * - hanya dataset pemilik sendiri;
     * - centroid wajah tidak boleh bentrok dengan akun lain;
     * - dataset identik milik sendiri ditolak;
     * - jumlah profil dibatasi.
     */
    public static function validateEnrollment(int $userId, array $descriptor): array {
        if (!self::ensureSchema()) return ['ok' => false, 'reason' => 'schema'];

        $trustedOwnCount = (int) db_val(
            "SELECT COUNT(*) FROM admin_face_profiles
             WHERE user_id = ? AND created_by = user_id AND model_version = ?",
            [$userId, self::MODEL_VERSION]
        );
        if ($trustedOwnCount >= self::MAX_PROFILES_PER_USER) return ['ok' => false, 'reason' => 'limit'];

        $rows = db_all(
            "SELECT p.id, p.user_id, p.descriptor
             FROM admin_face_profiles p
             JOIN users u ON u.id = p.user_id
             WHERE p.is_active = 1
               AND p.sample_count >= ?
               AND p.created_by = p.user_id
               AND p.model_version = ?
               AND u.is_active = 1",
            [self::MIN_USABLE_SAMPLES, self::MODEL_VERSION]
        );

        $bestOwn = -1.0;
        $bestOther = -1.0;
        $otherUserId = null;
        foreach ($rows as $row) {
            $payload = self::decodePayload((string) $row['descriptor']);
            $known = $payload['centroid'] ?? null;
            if (!$known) continue;
            $similarity = self::cosine($known, $descriptor);
            if ((int) $row['user_id'] === $userId) {
                $bestOwn = max($bestOwn, $similarity);
            } elseif ($similarity > $bestOther) {
                $bestOther = $similarity;
                $otherUserId = (int) $row['user_id'];
            }
        }

        if ($bestOther >= self::ENROLLMENT_CONFLICT_THRESHOLD) {
            return ['ok' => false, 'reason' => 'other_user_conflict', 'similarity' => $bestOther, 'other_user_id' => $otherUserId];
        }
        if ($bestOwn >= self::OWN_DUPLICATE_THRESHOLD) {
            return ['ok' => false, 'reason' => 'own_duplicate', 'similarity' => $bestOwn];
        }
        return ['ok' => true, 'reason' => 'accepted'];
    }

    public static function add(
        int $userId,
        string $label,
        array $descriptor,
        ?int $creatorId,
        array $templates = [],
        int $sampleCount = 1
    ): int {
        if (!self::ensureSchema()) throw new RuntimeException('Penyimpanan dataset wajah belum tersedia.');
        if ($creatorId === null || $creatorId !== $userId) {
            throw new InvalidArgumentException('Dataset wajah harus dibuat sendiri oleh pemilik akun.');
        }

        $centroid = self::normalizeDescriptor($descriptor);
        if (!$centroid) throw new InvalidArgumentException('Dimensi descriptor wajah tidak sesuai.');

        $templates = self::normalizeTemplates($templates);
        if (!$templates) $templates = [$centroid];
        $sampleCount = max(count($templates), min(20, $sampleCount));

        $payload = [
            'version' => 2,
            'model' => self::MODEL_VERSION,
            'centroid' => array_map(static fn($v) => round((float) $v, 8), $centroid),
            'templates' => array_map(
                static fn(array $template): array => array_map(static fn($v) => round((float) $v, 8), $template),
                $templates
            ),
        ];

        return db_insert('admin_face_profiles', [
            'user_id' => $userId,
            'label' => mb_substr(trim($label) ?: 'Dataset wajah ' . date('d/m/Y H:i'), 0, 120),
            'descriptor' => self::encryptPayload($payload),
            'sample_count' => $sampleCount,
            'threshold' => self::DEFAULT_THRESHOLD,
            'model_version' => self::MODEL_VERSION,
            'is_active' => 1,
            'created_by' => $creatorId,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Verifikasi tertutup ke satu akun.
     *
     * Semua template tepercaya dinilai. Jika tersedia minimal tiga template,
     * sedikitnya dua harus melewati ambang. Ini mencegah satu template kebetulan
     * memberi nilai tinggi menjadi satu-satunya dasar login.
     */
    public static function verify(int $userId, array $probe): array {
        $probe = self::normalizeDescriptor($probe);
        if (!$probe) {
            return ['ok' => false, 'reason' => 'invalid_descriptor', 'similarity' => -1.0, 'threshold' => self::MIN_LOGIN_THRESHOLD, 'profile_id' => null, 'votes' => 0, 'required_votes' => 1];
        }

        $scores = [];
        $profileBest = [];
        $thresholds = [];
        foreach (self::activeDescriptors($userId) as $row) {
            $payload = self::decodePayload((string) $row['descriptor']);
            $templates = $payload['templates'] ?? [];
            $centroid = $payload['centroid'] ?? null;
            if ($centroid) {
                array_unshift($templates, $centroid);
            }
            if (!$templates && $centroid) $templates = [$centroid];
            $threshold = self::effectiveThreshold($row['threshold']);
            $thresholds[] = $threshold;

            foreach ($templates as $template) {
                $similarity = self::cosine($template, $probe);
                $scores[] = [
                    'profile_id' => (int) $row['id'],
                    'similarity' => $similarity,
                    'threshold' => $threshold,
                    'pass' => $similarity >= $threshold,
                ];
                if (!isset($profileBest[$row['id']]) || $similarity > $profileBest[$row['id']]['similarity']) {
                    $profileBest[$row['id']] = end($scores);
                }
            }
        }

        if (!$scores) {
            return ['ok' => false, 'reason' => 'no_profiles', 'similarity' => -1.0, 'threshold' => self::MIN_LOGIN_THRESHOLD, 'profile_id' => null, 'votes' => 0, 'required_votes' => 1];
        }

        usort($scores, static fn(array $a, array $b): int => $b['similarity'] <=> $a['similarity']);
        $best = $scores[0];
        $votes = count(array_filter($scores, static fn(array $score): bool => $score['pass']));
        $templateCount = count($scores);
        $requiredVotes = $templateCount >= 3 ? self::MIN_TEMPLATE_AGREEMENT : 1;

        // Bila ada beberapa profil terpisah, minimal satu profil harus melewati
        // ambangnya; votes tetap dihitung per-template untuk consensus.
        $profilePasses = count(array_filter($profileBest, static fn(array $score): bool => $score['pass']));
        $matched = $votes >= $requiredVotes && $profilePasses >= 1;

        return [
            'ok' => $matched,
            'reason' => $matched ? 'matched' : 'not_matched',
            'similarity' => (float) $best['similarity'],
            'threshold' => (float) $best['threshold'],
            'profile_id' => $matched ? (int) $best['profile_id'] : null,
            'votes' => $votes,
            'required_votes' => $requiredVotes,
            'template_count' => $templateCount,
            'profile_passes' => $profilePasses,
        ];
    }

    /**
     * Kompatibilitas identifikasi lama. Login baru tidak memakai metode ini.
     * Metode tetap fail-closed dan memanggil verify per-user.
     */
    public static function identify(array $probe): array {
        $accounts = self::loginAccounts();
        $matches = [];
        foreach ($accounts as $account) {
            $result = self::verify((int) $account['id'], $probe);
            $matches[] = ['user_id' => (int) $account['id']] + $result;
        }
        if (!$matches) return ['ok' => false, 'reason' => 'no_profiles', 'similarity' => -1.0, 'threshold' => self::MIN_LOGIN_THRESHOLD];

        usort($matches, static fn(array $a, array $b): int => $b['similarity'] <=> $a['similarity']);
        $best = $matches[0];
        $second = $matches[1] ?? null;
        $separation = $second ? $best['similarity'] - $second['similarity'] : 1.0;
        $ok = !empty($best['ok']) && (!$second || !$second['ok'] || $separation >= 0.10);

        return $best + [
            'ok' => $ok,
            'reason' => $ok ? 'matched' : 'ambiguous',
            'user_id' => $ok ? $best['user_id'] : null,
            'separation' => $separation,
        ];
    }

    /** Baca payload terenkripsi serta kompatibilitas JSON lama. */
    private static function decodePayload(string $stored): array {
        $plain = self::decryptPayload($stored);
        if ($plain === null) $plain = $stored;

        $decoded = json_decode($plain, true);
        if (!is_array($decoded)) return [];

        // Format lama berupa satu vector JSON.
        $legacy = self::normalizeDescriptor($decoded);
        if ($legacy) return ['version' => 1, 'centroid' => $legacy, 'templates' => [$legacy]];

        $centroid = self::normalizeDescriptor($decoded['centroid'] ?? null);
        $templates = self::normalizeTemplates($decoded['templates'] ?? []);
        if (!$centroid && $templates) $centroid = self::average($templates);
        if (!$centroid) return [];
        if (!$templates) $templates = [$centroid];

        return ['version' => (int) ($decoded['version'] ?? 2), 'centroid' => $centroid, 'templates' => $templates];
    }

    private static function average(array $templates): ?array {
        if (!$templates) return null;
        $out = array_fill(0, self::EXPECTED_DESCRIPTOR_SIZE, 0.0);
        foreach ($templates as $template) {
            foreach ($template as $index => $value) $out[$index] += $value;
        }
        foreach ($out as &$value) $value /= count($templates);
        unset($value);
        return self::normalizeDescriptor($out);
    }

    private static function encryptionKey(): string {
        $source = (string) cfg('face_encryption_key', '');
        if ($source === '') $source = (string) cfg('app_key', 'desa-face-fallback-key');
        if (function_exists('sodium_crypto_generichash')) {
            return sodium_crypto_generichash('desa-face-v2|' . $source, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }
        return hash('sha256', 'desa-face-v2|' . $source, true);
    }

    private static function encryptPayload(array $payload): string {
        $plain = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($plain)) throw new RuntimeException('Descriptor wajah gagal diserialisasi.');
        $key = self::encryptionKey();

        if (function_exists('sodium_crypto_secretbox')) {
            $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = sodium_crypto_secretbox($plain, $nonce, $key);
            return 'enc:sodium:v1:' . base64_encode($nonce . $cipher);
        }

        if (function_exists('openssl_encrypt')) {
            $iv = random_bytes(12);
            $tag = '';
            $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'desa-face-v2');
            if ($cipher === false) throw new RuntimeException('Enkripsi descriptor wajah gagal.');
            return 'enc:aesgcm:v1:' . base64_encode($iv . $tag . $cipher);
        }

        throw new RuntimeException('Server memerlukan libsodium atau OpenSSL untuk menyimpan dataset wajah.');
    }

    private static function decryptPayload(string $stored): ?string {
        $key = self::encryptionKey();

        if (str_starts_with($stored, 'enc:sodium:v1:')) {
            if (!function_exists('sodium_crypto_secretbox_open')) return null;
            $raw = base64_decode(substr($stored, strlen('enc:sodium:v1:')), true);
            if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) return null;
            $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $cipher = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
            $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
            return $plain === false ? null : $plain;
        }

        if (str_starts_with($stored, 'enc:aesgcm:v1:')) {
            if (!function_exists('openssl_decrypt')) return null;
            $raw = base64_decode(substr($stored, strlen('enc:aesgcm:v1:')), true);
            if ($raw === false || strlen($raw) <= 28) return null;
            $iv = substr($raw, 0, 12);
            $tag = substr($raw, 12, 16);
            $cipher = substr($raw, 28);
            $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'desa-face-v2');
            return $plain === false ? null : $plain;
        }

        return null;
    }
}
