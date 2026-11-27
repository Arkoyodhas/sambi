<?php

/**
 * Layanan Zona Hiburan.
 *
 * Identitas pemain dipetakan melalui token perangkat acak. Token mentah tetap
 * berada di browser; server hanya menyimpan HMAC agar token tidak dapat dipakai
 * kembali apabila database terbaca pihak lain.
 */
class Entertainment {

    private const SCHEMA_VERSION = '6';
    private static ?bool $schemaReady = null;

    public static function enabled(): bool {
        return setting('entertainment_enabled', '1') === '1';
    }

    /**
     * Variasi game harian lokal. Tidak melakukan koneksi jaringan atau
     * menggunakan layanan eksternal, sehingga permainan tetap cepat dan stabil.
     */
    public static function dailyChallenge(?string $date = null): array {
        $date = $date ?: date('Y-m-d');
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            $date = date('Y-m-d');
            $timestamp = strtotime($date);
        }

        $themes = [
            [
                'title' => 'Nuansa Desa',
                'description' => 'Cocokkan simbol kehidupan desa dengan tenang dan teliti.',
                'symbols' => ['🌾', '🏡', '🌳', '🐟', '🚜', '🌻', '🐓', '💧'],
                'puzzle' => 'Susun Panorama Hari Ini',
                'tip' => 'Mulai dari potongan sudut, lalu cocokkan garis dan warna.',
                'wheel' => ['Semangat', 'Ceria', 'Kejutan', 'Bintang', 'Istimewa'],
            ],
            [
                'title' => 'Sahabat Alam',
                'description' => 'Temukan pasangan simbol alam dan lingkungan.',
                'symbols' => ['🌿', '🌤️', '🦋', '🌊', '🍃', '🐝', '🌈', '🌱'],
                'puzzle' => 'Tantangan Warna Alam',
                'tip' => 'Perhatikan bagian gambar yang memiliki pola paling khas.',
                'wheel' => ['Tumbuh', 'Segar', 'Berani', 'Hebat', 'Langka'],
            ],
            [
                'title' => 'Ruang Kreatif',
                'description' => 'Latih fokus dengan pasangan simbol kreatif yang berbeda.',
                'symbols' => ['🎨', '📚', '🎵', '⭐', '🧩', '✏️', '💡', '🎭'],
                'puzzle' => 'Puzzle Fokus Harian',
                'tip' => 'Susun satu baris terlebih dahulu agar langkah lebih terarah.',
                'wheel' => ['Inspirasi', 'Fokus', 'Karya', 'Juara', 'Bonus'],
            ],
        ];

        $index = ((int) date('z', $timestamp)) % count($themes);
        $theme = $themes[$index];

        return [
            'date' => $date,
            'game_config' => [
                'memory' => [
                    'title' => $theme['title'],
                    'description' => $theme['description'],
                    'symbols' => $theme['symbols'],
                ],
                'puzzle' => [
                    'title' => $theme['puzzle'],
                    'tip' => $theme['tip'],
                    'grid' => 3,
                ],
                'wheel' => [
                    'title' => 'Roda ' . $theme['title'],
                    'labels' => $theme['wheel'],
                ],
            ],
        ];
    }

    public static function ensureSchema(): bool {
        if (self::$schemaReady !== null) return self::$schemaReady;
        if (setting('entertainment_schema_version', '0') === self::SCHEMA_VERSION) {
            return self::$schemaReady = true;
        }

        try {
            db_exec("CREATE TABLE IF NOT EXISTS entertainment_devices (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                token_hash CHAR(64) NOT NULL,
                token_hint CHAR(12) NOT NULL,
                nickname VARCHAR(32) DEFAULT NULL,
                avatar_key VARCHAR(30) NOT NULL DEFAULT 'leaf',
                frame_key VARCHAR(30) NOT NULL DEFAULT 'simple',
                featured_badge VARCHAR(40) DEFAULT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                device VARCHAR(20) DEFAULT NULL,
                browser VARCHAR(30) DEFAULT NULL,
                os VARCHAR(30) DEFAULT NULL,
                total_points INT NOT NULL DEFAULT 0,
                current_streak INT NOT NULL DEFAULT 0,
                longest_streak INT NOT NULL DEFAULT 0,
                last_visit_date DATE DEFAULT NULL,
                first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_device_token (token_hash),
                KEY idx_ent_points (total_points),
                KEY idx_ent_last_seen (last_seen_at),
                KEY idx_ent_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            self::ensureColumn('entertainment_devices', 'avatar_key', "VARCHAR(30) NOT NULL DEFAULT 'leaf' AFTER nickname");
            self::ensureColumn('entertainment_devices', 'frame_key', "VARCHAR(30) NOT NULL DEFAULT 'simple' AFTER avatar_key");
            self::ensureColumn('entertainment_devices', 'featured_badge', "VARCHAR(40) DEFAULT NULL AFTER frame_key");
            self::ensureColumn('entertainment_devices', 'last_play_date', "DATE DEFAULT NULL AFTER last_visit_date");
            db_exec("UPDATE entertainment_devices
                     SET last_play_date = last_visit_date
                     WHERE last_play_date IS NULL AND current_streak > 0 AND last_visit_date IS NOT NULL");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_device_ips (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                visit_count INT UNSIGNED NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_device_ip (device_id, ip_address),
                KEY idx_ent_ip_address (ip_address),
                KEY idx_ent_ip_last_seen (last_seen_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_events (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                event_type VARCHAR(40) NOT NULL,
                game_type VARCHAR(30) DEFAULT NULL,
                score INT NOT NULL DEFAULT 0,
                points INT NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NOT NULL,
                page_path VARCHAR(190) DEFAULT NULL,
                meta_json LONGTEXT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ent_event_device (device_id, created_at),
                KEY idx_ent_event_type (event_type, game_type, created_at),
                KEY idx_ent_event_ip (ip_address)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_daily_games (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                play_date DATE NOT NULL,
                game_type VARCHAR(30) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                best_score INT NOT NULL DEFAULT 0,
                points_awarded INT NOT NULL DEFAULT 0,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_daily_game (device_id, play_date, game_type),
                KEY idx_ent_daily_date (play_date, game_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_game_sessions (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                game_type VARCHAR(30) NOT NULL,
                nonce_hash CHAR(64) NOT NULL,
                started_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                client_seconds INT UNSIGNED DEFAULT NULL,
                client_moves INT UNSIGNED DEFAULT NULL,
                config_value TINYINT UNSIGNED NOT NULL DEFAULT 0,
                created_ip VARCHAR(45) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_game_nonce (nonce_hash),
                KEY idx_ent_game_device (device_id, game_type, started_at),
                KEY idx_ent_game_expiry (expires_at, used_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            self::ensureColumn('entertainment_game_sessions', 'config_value', "TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER client_moves");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_quiz_questions (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                question VARCHAR(500) NOT NULL,
                option_a VARCHAR(255) NOT NULL,
                option_b VARCHAR(255) NOT NULL,
                option_c VARCHAR(255) NOT NULL,
                option_d VARCHAR(255) NOT NULL,
                correct_option CHAR(1) NOT NULL,
                explanation VARCHAR(500) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ent_quiz_active (is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            self::ensureColumn('entertainment_quiz_questions', 'source', "VARCHAR(20) NOT NULL DEFAULT 'manual' AFTER sort_order");
            self::ensureColumn('entertainment_quiz_questions', 'category', "VARCHAR(60) DEFAULT NULL AFTER source");
            self::ensureColumn('entertainment_quiz_questions', 'difficulty', "VARCHAR(20) DEFAULT NULL AFTER category");
            self::ensureColumn('entertainment_quiz_questions', 'question_hash', "CHAR(64) DEFAULT NULL AFTER difficulty");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_quiz_assignments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                assignment_date DATE NOT NULL,
                question_id INT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_quiz_assignment (device_id, assignment_date),
                KEY idx_ent_quiz_assignment_question (question_id),
                KEY idx_ent_quiz_assignment_date (assignment_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_quiz_answers (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                question_id INT UNSIGNED NOT NULL,
                answer_option CHAR(1) NOT NULL,
                is_correct TINYINT(1) NOT NULL DEFAULT 0,
                points INT NOT NULL DEFAULT 0,
                answer_date DATE NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_quiz_daily (device_id, answer_date),
                KEY idx_ent_quiz_question (question_id),
                KEY idx_ent_quiz_date (answer_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_wheel_spins (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                spin_date DATE NOT NULL,
                prize_label VARCHAR(80) NOT NULL,
                prize_points INT NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_wheel_daily (device_id, spin_date),
                KEY idx_ent_wheel_date (spin_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_device_badges (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                badge_key VARCHAR(40) NOT NULL,
                awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_device_badge (device_id, badge_key),
                KEY idx_ent_badge_key (badge_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_daily_bonuses (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                bonus_date DATE NOT NULL,
                bonus_key VARCHAR(40) NOT NULL,
                points INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_daily_bonus (device_id, bonus_date, bonus_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_activity_rewards (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                reward_date DATE NOT NULL,
                activity_key VARCHAR(40) NOT NULL,
                source_key VARCHAR(190) DEFAULT NULL,
                points INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_activity_daily (device_id, reward_date, activity_key),
                KEY idx_ent_activity_date (reward_date, activity_key),
                KEY idx_ent_activity_device (device_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_polls (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                question VARCHAR(500) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                starts_at DATETIME DEFAULT NULL,
                ends_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ent_poll_active (is_active, starts_at, ends_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_poll_options (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                poll_id INT UNSIGNED NOT NULL,
                label VARCHAR(255) NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                PRIMARY KEY (id),
                KEY idx_ent_poll_option (poll_id, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_poll_votes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                poll_id INT UNSIGNED NOT NULL,
                option_id INT UNSIGNED NOT NULL,
                device_id BIGINT UNSIGNED NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_poll_device (poll_id, device_id),
                KEY idx_ent_poll_vote (poll_id, option_id),
                KEY idx_ent_poll_vote_device (device_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_story_progress (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                story_key VARCHAR(40) NOT NULL,
                node_key VARCHAR(40) NOT NULL,
                path_json LONGTEXT DEFAULT NULL,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_story_device (device_id, story_key),
                KEY idx_ent_story_updated (updated_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_facts (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                category VARCHAR(60) NOT NULL,
                content VARCHAR(700) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_ent_fact_active (is_active, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            db_exec("CREATE TABLE IF NOT EXISTS entertainment_daily_boxes (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                device_id BIGINT UNSIGNED NOT NULL,
                claim_date DATE NOT NULL,
                reward_label VARCHAR(100) NOT NULL,
                reward_points INT NOT NULL DEFAULT 0,
                ip_address VARCHAR(45) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_ent_box_daily (device_id, claim_date),
                KEY idx_ent_box_date (claim_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            foreach ([
                'entertainment_enabled' => '1',
                'entertainment_quiz_enabled' => '1',
                'entertainment_memory_enabled' => '1',
                'entertainment_puzzle_enabled' => '1',
                'entertainment_wheel_enabled' => '1',
                'entertainment_leaderboard_enabled' => '1',
                'entertainment_activity_points_enabled' => '1',
                'entertainment_poll_enabled' => '1',
                'entertainment_story_enabled' => '1',
                'entertainment_facts_enabled' => '1',
                'entertainment_profile_enabled' => '1',
                'entertainment_daily_box_enabled' => '1',
                'entertainment_retention' => 'permanent',
            ] as $key => $value) {
                db_exec("INSERT INTO site_settings (setting_key, setting_value)
                         VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_key = setting_key", [$key, $value]);
            }

            // Nonaktifkan konten lama dari generator yang telah dihapus.
            db_exec("UPDATE entertainment_quiz_questions SET is_active = 0, updated_at = NOW() WHERE source = 'ai'");

            if (!(bool) db_val("SELECT 1 FROM entertainment_quiz_questions WHERE COALESCE(source, 'manual') <> 'ai' LIMIT 1")) self::seedQuestions();
            if (!(bool) db_val("SELECT 1 FROM entertainment_facts LIMIT 1")) self::seedFacts();
            if (!(bool) db_val("SELECT 1 FROM entertainment_polls LIMIT 1")) self::seedPoll();

            set_setting('entertainment_schema_version', self::SCHEMA_VERSION);
            set_setting('asset_version', '20260727-entertainment-local-white');
            return self::$schemaReady = true;
        } catch (Throwable $e) {
            error_log('Entertainment schema: ' . $e->getMessage());
            return self::$schemaReady = false;
        }
    }

    private static function ensureColumn(string $table, string $column, string $definition): void {
        $exists = (bool) db_val(
            "SELECT 1 FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1",
            [$table, $column]
        );
        if (!$exists) db_exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }

    private static function seedQuestions(): void {
        $questions = [
            ['Bhinneka Tunggal Ika memiliki arti…', 'Berbeda-beda tetapi tetap satu', 'Bersatu kita teguh', 'Maju bersama bangsa', 'Gotong royong selamanya', 'A', 'Semboyan Bhinneka Tunggal Ika berarti berbeda-beda tetapi tetap satu.'],
            ['Planet terbesar dalam tata surya adalah…', 'Bumi', 'Mars', 'Jupiter', 'Saturnus', 'C', 'Jupiter merupakan planet terbesar dalam tata surya.'],
            ['Air membeku pada suhu berapa derajat Celsius?', '0°C', '10°C', '32°C', '100°C', 'A', 'Pada tekanan normal, air membeku pada 0°C.'],
            ['Hewan yang mengalami metamorfosis sempurna adalah…', 'Ayam', 'Kupu-kupu', 'Kucing', 'Ikan', 'B', 'Kupu-kupu melalui tahap telur, larva, pupa, dan dewasa.'],
            ['Pulau terluas di Indonesia adalah…', 'Jawa', 'Bali', 'Madura', 'Kalimantan', 'D', 'Bagian Indonesia di Pulau Kalimantan merupakan wilayah pulau terluas di Indonesia.'],
            ['Perangkat untuk mengukur suhu disebut…', 'Barometer', 'Termometer', 'Higrometer', 'Speedometer', 'B', 'Termometer digunakan untuk mengukur suhu.'],
            ['Sumber energi utama bagi bumi adalah…', 'Bulan', 'Angin', 'Matahari', 'Air', 'C', 'Sebagian besar energi di bumi berasal dari Matahari.'],
            ['Lawan kata “optimis” adalah…', 'Pesimis', 'Aktif', 'Kreatif', 'Objektif', 'A', 'Optimis berlawanan makna dengan pesimis.'],
            ['Hasil dari 12 × 8 adalah…', '84', '88', '92', '96', 'D', '12 dikalikan 8 sama dengan 96.'],
            ['Organ tubuh yang memompa darah adalah…', 'Paru-paru', 'Ginjal', 'Jantung', 'Hati', 'C', 'Jantung memompa darah ke seluruh tubuh.'],
            ['Bahasa pemrograman yang berjalan langsung di browser adalah…', 'JavaScript', 'SQL', 'PHP', 'C', 'A', 'JavaScript merupakan bahasa utama yang dijalankan langsung oleh browser.'],
            ['Satu jam terdiri dari berapa menit?', '30', '45', '60', '90', 'C', 'Satu jam terdiri dari 60 menit.'],
        ];
        foreach ($questions as $i => $q) {
            db_insert('entertainment_quiz_questions', [
                'question' => $q[0], 'option_a' => $q[1], 'option_b' => $q[2],
                'option_c' => $q[3], 'option_d' => $q[4], 'correct_option' => $q[5],
                'explanation' => $q[6], 'is_active' => 1, 'sort_order' => ($i + 1) * 10,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private static function seedFacts(): void {
        $facts = [
            ['Kesehatan', 'Minum air secara teratur membantu tubuh menjaga suhu, konsentrasi, dan fungsi organ.'],
            ['Keamanan Digital', 'Gunakan kata sandi yang berbeda untuk setiap akun dan aktifkan verifikasi dua langkah bila tersedia.'],
            ['Teknologi', 'QR Code dapat menyimpan teks, tautan, kontak, atau informasi verifikasi dalam pola dua dimensi.'],
            ['Lingkungan', 'Memisahkan sampah organik dan anorganik membuat proses pengolahan dan daur ulang lebih efektif.'],
            ['Pengetahuan', 'Lebah berkomunikasi tentang arah sumber makanan melalui gerakan yang dikenal sebagai tarian waggle.'],
            ['Kesehatan', 'Berjalan kaki ringan secara rutin dapat membantu menjaga kebugaran jantung dan kualitas tidur.'],
            ['Keamanan Digital', 'Jangan pernah membagikan kode OTP, PIN, atau kata sandi kepada siapa pun.'],
            ['Budaya', 'Gotong royong merupakan nilai sosial yang menekankan kerja bersama untuk kepentingan bersama.'],
            ['Teknologi', 'Cache browser membantu halaman yang pernah dikunjungi dimuat lebih cepat.'],
            ['Lingkungan', 'Menanam tanaman lokal dapat membantu menyediakan habitat bagi serangga penyerbuk.'],
            ['Pengetahuan', 'Satu hari di Bumi berlangsung sekitar 23 jam 56 menit bila dihitung terhadap bintang-bintang.'],
            ['Kesehatan', 'Istirahat singkat setelah menatap layar dapat membantu mengurangi ketegangan mata.'],
        ];
        foreach ($facts as $i => $fact) {
            db_insert('entertainment_facts', [
                'category' => $fact[0],
                'content' => $fact[1],
                'is_active' => 1,
                'sort_order' => ($i + 1) * 10,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private static function seedPoll(): void {
        $pollId = db_insert('entertainment_polls', [
            'question' => 'Konten apa yang paling ingin Anda lihat lebih sering di website?',
            'is_active' => 1,
            'starts_at' => null,
            'ends_at' => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach (['Informasi layanan', 'Berita dan kegiatan', 'Tips bermanfaat', 'Konten hiburan'] as $i => $label) {
            db_insert('entertainment_poll_options', [
                'poll_id' => $pollId,
                'label' => $label,
                'sort_order' => ($i + 1) * 10,
            ]);
        }
    }

    public static function normalizeToken($value): ?string {
        $token = strtolower(trim((string) $value));
        return preg_match('/^[a-f0-9]{64}$/', $token) ? $token : null;
    }

    private static function tokenHash(string $token): string {
        return hash_hmac('sha256', $token, (string) cfg('app_key', 'desa-app'));
    }

    public static function identify($tokenValue, bool $awardVisit = true): ?array {
        if (!self::ensureSchema()) return null;
        $token = self::normalizeToken($tokenValue);
        if ($token === null) return null;

        $hash = self::tokenHash($token);
        $ip = Visitor::ip();
        $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        [$device, $browser] = Visitor::parseUa($ua);
        $os = Visitor::parseOs($ua);
        $today = date('Y-m-d');

        db()->beginTransaction();
        try {
            db_exec("INSERT INTO entertainment_devices
                        (token_hash, token_hint, ip_address, user_agent, device, browser, os,
                         total_points, current_streak, longest_streak, first_seen_at, last_seen_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0, NOW(), NOW(), NOW())
                     ON DUPLICATE KEY UPDATE
                        ip_address = VALUES(ip_address),
                        user_agent = VALUES(user_agent),
                        device = VALUES(device),
                        browser = VALUES(browser),
                        os = VALUES(os),
                        last_seen_at = NOW(),
                        updated_at = NOW()", [
                $hash, strtoupper(substr($hash, 0, 12)), $ip, $ua, $device, $browser, $os,
            ]);

            $row = db_row("SELECT * FROM entertainment_devices WHERE token_hash = ? FOR UPDATE", [$hash]);
            if (!$row) throw new RuntimeException('Perangkat tidak ditemukan.');

            db_exec("INSERT INTO entertainment_device_ips
                        (device_id, ip_address, first_seen_at, last_seen_at, visit_count)
                     VALUES (?, ?, NOW(), NOW(), 1)
                     ON DUPLICATE KEY UPDATE
                        visit_count = visit_count + IF(last_seen_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE), 1, 0),
                        last_seen_at = NOW()", [$row['id'], $ip]);

            if ($awardVisit && ($row['last_visit_date'] ?? null) !== $today) {
                db_exec("UPDATE entertainment_devices
                         SET last_visit_date = ?, total_points = total_points + 2
                         WHERE id = ?", [$today, $row['id']]);
                self::insertEvent((int) $row['id'], 'daily_visit', null, 0, 2);
            }

            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            error_log('Entertainment identify: ' . $e->getMessage());
            return null;
        }

        self::awardBadges((int) $row['id']);
        return db_row("SELECT * FROM entertainment_devices WHERE id = ?", [$row['id']]);
    }

    private static function insertEvent(int $deviceId, string $eventType, ?string $gameType, int $score, int $points, array $meta = []): void {
        db_insert('entertainment_events', [
            'device_id' => $deviceId,
            'event_type' => mb_substr($eventType, 0, 40),
            'game_type' => $gameType ? mb_substr($gameType, 0, 30) : null,
            'score' => $score,
            'points' => $points,
            'ip_address' => Visitor::ip(),
            'page_path' => mb_substr(parse_url($_SERVER['REQUEST_URI'] ?? '/hiburan', PHP_URL_PATH) ?: '/hiburan', 0, 190),
            'meta_json' => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function addPoints(int $deviceId, int $points): void {
        if ($points === 0) return;
        db_exec("UPDATE entertainment_devices
                 SET total_points = GREATEST(0, total_points + ?), updated_at = NOW()
                 WHERE id = ?", [$points, $deviceId]);
    }

    /**
     * Streak hanya berubah setelah permainan berhasil diselesaikan. Satu
     * perangkat hanya dapat menaikkan streak satu kali per tanggal server.
     * Method dipanggil di dalam transaksi game sehingga race request tetap aman.
     */
    private static function recordPlayStreak(int $deviceId, string $gameType): array {
        $row = db_row(
            "SELECT current_streak, longest_streak, last_play_date
             FROM entertainment_devices
             WHERE id = ?
             FOR UPDATE",
            [$deviceId]
        );
        if (!$row) return ['streak' => 0, 'longest_streak' => 0, 'increased' => false];

        $today = date('Y-m-d');
        if (($row['last_play_date'] ?? null) === $today) {
            return [
                'streak' => (int) $row['current_streak'],
                'longest_streak' => (int) $row['longest_streak'],
                'increased' => false,
            ];
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $streak = ($row['last_play_date'] ?? null) === $yesterday
            ? max(1, (int) $row['current_streak'] + 1)
            : 1;
        $longest = max((int) $row['longest_streak'], $streak);

        db_exec(
            "UPDATE entertainment_devices
             SET last_play_date = ?, current_streak = ?, longest_streak = ?, updated_at = NOW()
             WHERE id = ?",
            [$today, $streak, $longest, $deviceId]
        );
        self::insertEvent($deviceId, 'streak_update', $gameType, 0, 0, [
            'streak' => $streak,
            'longest_streak' => $longest,
        ]);

        return ['streak' => $streak, 'longest_streak' => $longest, 'increased' => true];
    }

    private static function rewardOnce(int $deviceId, string $activity, int $points, string $source = '', array $meta = []): int {
        $activity = mb_substr(preg_replace('/[^a-z0-9_]/', '', strtolower($activity)) ?? '', 0, 40);
        if ($activity === '') return 0;
        $inserted = db_exec("INSERT IGNORE INTO entertainment_activity_rewards
                            (device_id, reward_date, activity_key, source_key, points, created_at)
                            VALUES (?, CURDATE(), ?, ?, ?, NOW())", [
            $deviceId, $activity, mb_substr($source, 0, 190) ?: null, $points,
        ]);
        if ($inserted > 0) {
            self::addPoints($deviceId, $points);
            self::insertEvent($deviceId, 'activity_' . $activity, null, 0, $points, array_merge($meta, ['source' => $source]));
            self::awardDailyMaster($deviceId);
            self::awardBadges($deviceId);
            return $points;
        }
        return 0;
    }

    public static function profile($tokenValue): ?array {
        $device = self::identify($tokenValue, true);
        if (!$device) return null;
        $id = (int) $device['id'];

        self::awardDailyMaster($id);
        self::awardBadges($id);
        $device = db_row("SELECT * FROM entertainment_devices WHERE id = ?", [$id]);
        if (!$device) return null;

        $rank = 1 + (int) db_val("SELECT COUNT(*) FROM entertainment_devices WHERE total_points > ?", [$device['total_points']]);
        $dailyRows = db_all("SELECT game_type, attempts, best_score, points_awarded, completed
                             FROM entertainment_daily_games WHERE device_id = ? AND play_date = CURDATE()", [$id]);
        $daily = [];
        foreach ($dailyRows as $r) $daily[$r['game_type']] = $r;

        $badges = [];
        $definitions = self::badgeDefinitions();
        foreach (db_all("SELECT badge_key, awarded_at FROM entertainment_device_badges WHERE device_id = ? ORDER BY awarded_at", [$id]) as $b) {
            $def = $definitions[$b['badge_key']] ?? null;
            if ($def) $badges[] = ['key' => $b['badge_key'], 'name' => $def[0], 'icon' => $def[1], 'awarded_at' => $b['awarded_at']];
        }

        $todayQuiz = db_row("SELECT qa.*, q.question, q.explanation
                             FROM entertainment_quiz_answers qa
                             JOIN entertainment_quiz_questions q ON q.id = qa.question_id
                             WHERE qa.device_id = ? AND qa.answer_date = CURDATE() LIMIT 1", [$id]);
        $spin = db_row("SELECT prize_label, prize_points FROM entertainment_wheel_spins WHERE device_id = ? AND spin_date = CURDATE() LIMIT 1", [$id]);
        $activityRows = db_all("SELECT DISTINCT event_type FROM entertainment_events WHERE device_id = ? AND created_at >= CURDATE()", [$id]);
        $activities = array_fill_keys(array_column($activityRows, 'event_type'), true);
        $poll = setting('entertainment_poll_enabled', '1') === '1' ? self::pollState($id) : null;
        $dailyBox = db_row("SELECT reward_label, reward_points, created_at
                            FROM entertainment_daily_boxes WHERE device_id = ? AND claim_date = CURDATE() LIMIT 1", [$id]);

        $enabledGames = [
            'quiz' => setting('entertainment_quiz_enabled', '1') === '1',
            'memory' => setting('entertainment_memory_enabled', '1') === '1',
            'puzzle' => setting('entertainment_puzzle_enabled', '1') === '1',
            'wheel' => setting('entertainment_wheel_enabled', '1') === '1',
        ];

        $dailyChallenge = self::dailyChallenge();
        $gameConfig = is_array($dailyChallenge['game_config'] ?? null)
            ? $dailyChallenge['game_config']
            : [];
        if (isset($gameConfig['memory']) && is_array($gameConfig['memory'])) {
            $gameConfig['memory']['pair_count'] = (int) ($daily['memory']['best_score'] ?? 0) >= 85 ? 8 : 6;
        }
        if (isset($gameConfig['puzzle']) && is_array($gameConfig['puzzle'])) {
            $gameConfig['puzzle']['grid'] = (int) ($daily['puzzle']['best_score'] ?? 0) >= 85 ? 4 : 3;
        }

        return [
            'id' => $id,
            'hint' => $device['token_hint'],
            'display_name' => $device['nickname'] ?: 'Pemain #' . substr($device['token_hint'], 0, 6),
            'nickname' => $device['nickname'] ?: '',
            'avatar' => $device['avatar_key'] ?: 'leaf',
            'frame' => $device['frame_key'] ?: 'simple',
            'featured_badge' => $device['featured_badge'] ?: '',
            'points' => (int) $device['total_points'],
            'streak' => (int) $device['current_streak'],
            'longest_streak' => (int) $device['longest_streak'],
            'streak_active_today' => ($device['last_play_date'] ?? null) === date('Y-m-d'),
            'rank' => $rank,
            'badges' => $badges,
            'missions' => self::missions($daily, $activities, (bool) ($poll['voted'] ?? false)),
            'poll' => $poll,
            'story' => setting('entertainment_story_enabled', '1') === '1' ? self::storyState($id) : null,
            'fact' => setting('entertainment_facts_enabled', '1') === '1' ? self::factForDevice($id) : null,
            'daily_box' => [
                'enabled' => setting('entertainment_daily_box_enabled', '1') === '1',
                'claimed' => (bool) $dailyBox,
                'reward' => $dailyBox ? [
                    'label' => $dailyBox['reward_label'],
                    'points' => (int) $dailyBox['reward_points'],
                ] : null,
            ],
            'profile_options' => self::profileOptions($badges),
            'daily_challenge' => [
                'date' => $dailyChallenge['date'] ?? date('Y-m-d'),
                'game_config' => $gameConfig,
            ],
            'games' => [
                'quiz' => [
                    'enabled' => $enabledGames['quiz'],
                    'completed' => (bool) $todayQuiz,
                    'correct' => $todayQuiz ? (bool) $todayQuiz['is_correct'] : null,
                    'points' => $todayQuiz ? (int) $todayQuiz['points'] : 0,
                    'explanation' => $todayQuiz['explanation'] ?? null,
                ],
                'memory' => [
                    'enabled' => $enabledGames['memory'],
                    'completed' => !empty($daily['memory']['completed']),
                    'best_score' => (int) ($daily['memory']['best_score'] ?? 0),
                    'attempts' => (int) ($daily['memory']['attempts'] ?? 0),
                ],
                'puzzle' => [
                    'enabled' => $enabledGames['puzzle'],
                    'completed' => !empty($daily['puzzle']['completed']),
                    'best_score' => (int) ($daily['puzzle']['best_score'] ?? 0),
                    'attempts' => (int) ($daily['puzzle']['attempts'] ?? 0),
                ],
                'wheel' => [
                    'enabled' => $enabledGames['wheel'],
                    'completed' => (bool) $spin,
                    'prize' => $spin,
                ],
            ],
        ];
    }

    public static function updateNickname($tokenValue, $nicknameValue): array {
        return self::updateProfile($tokenValue, [
            'nickname' => $nicknameValue,
        ]);
    }

    public static function updateProfile($tokenValue, array $input): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];
        if (setting('entertainment_profile_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Pengaturan profil sedang dinonaktifkan.'];

        $nickname = trim(strip_tags((string) ($input['nickname'] ?? $device['nickname'] ?? '')));
        $nickname = preg_replace('/[^\p{L}\p{N} ._-]/u', '', $nickname) ?? '';
        $nickname = preg_replace('/\s+/u', ' ', $nickname) ?? '';
        $nickname = mb_substr(trim($nickname), 0, 32);
        if ($nickname !== '' && mb_strlen($nickname) < 3) return ['ok' => false, 'error' => 'Nama pemain minimal 3 karakter.'];

        $options = self::profileOptions([]);
        $avatarKeys = array_column($options['avatars'], 'key');
        $frameKeys = array_column($options['frames'], 'key');
        $avatar = strtolower(trim((string) ($input['avatar'] ?? $device['avatar_key'] ?? 'leaf')));
        $frame = strtolower(trim((string) ($input['frame'] ?? $device['frame_key'] ?? 'simple')));
        if (!in_array($avatar, $avatarKeys, true)) $avatar = 'leaf';
        if (!in_array($frame, $frameKeys, true)) $frame = 'simple';

        $featured = strtolower(trim((string) ($input['featured_badge'] ?? $device['featured_badge'] ?? '')));
        if ($featured !== '') {
            $owned = (bool) db_val("SELECT 1 FROM entertainment_device_badges WHERE device_id = ? AND badge_key = ? LIMIT 1", [$device['id'], $featured]);
            if (!$owned) $featured = '';
        }

        db_update('entertainment_devices', [
            'nickname' => $nickname ?: null,
            'avatar_key' => $avatar,
            'frame_key' => $frame,
            'featured_badge' => $featured ?: null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$device['id']]);
        self::insertEvent((int) $device['id'], 'profile_update', null, 0, 0, [
            'avatar' => $avatar, 'frame' => $frame, 'featured_badge' => $featured,
        ]);

        return [
            'ok' => true,
            'display_name' => $nickname ?: 'Pemain #' . substr($device['token_hint'], 0, 6),
            'avatar' => $avatar,
            'frame' => $frame,
            'featured_badge' => $featured,
        ];
    }

    private static function profileOptions(array $badges): array {
        return [
            'avatars' => [
                ['key' => 'leaf', 'label' => 'Daun', 'icon' => 'bi-tree-fill'],
                ['key' => 'sun', 'label' => 'Matahari', 'icon' => 'bi-sun-fill'],
                ['key' => 'smile', 'label' => 'Senyum', 'icon' => 'bi-emoji-smile-fill'],
                ['key' => 'star', 'label' => 'Bintang', 'icon' => 'bi-star-fill'],
                ['key' => 'book', 'label' => 'Buku', 'icon' => 'bi-book-fill'],
                ['key' => 'camera', 'label' => 'Kamera', 'icon' => 'bi-camera-fill'],
            ],
            'frames' => [
                ['key' => 'simple', 'label' => 'Sederhana'],
                ['key' => 'green', 'label' => 'Hijau'],
                ['key' => 'blue', 'label' => 'Biru'],
                ['key' => 'gold', 'label' => 'Emas'],
            ],
            'badges' => array_map(static fn(array $b): array => [
                'key' => $b['key'], 'label' => $b['name'],
            ], $badges),
        ];
    }

    public static function trackActivity($tokenValue, $activityValue, $sourceValue = ''): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Perangkat belum dapat dikenali.'];
        if (setting('entertainment_activity_points_enabled', '1') !== '1') return ['ok' => true, 'points' => 0];

        $activity = strtolower(trim((string) $activityValue));
        $source = mb_substr(trim(strip_tags((string) $sourceValue)), 0, 190);
        $map = [
            'login' => 3,
            'read_news' => 3,
            'open_gallery' => 2,
            'read_info' => 2,
            'survey' => 5,
        ];
        if (!isset($map[$activity])) return ['ok' => false, 'error' => 'Aktivitas tidak valid.'];
        if ($activity === 'login' && empty($_SESSION['user_id'])) return ['ok' => false, 'error' => 'Sesi login tidak ditemukan.'];
        if (!self::validActivitySource($activity, $source)) return ['ok' => false, 'error' => 'Sumber aktivitas tidak valid.'];

        $points = self::rewardOnce((int) $device['id'], $activity, $map[$activity], $source);
        return ['ok' => true, 'points' => $points, 'awarded' => $points > 0];
    }

    private static function validActivitySource(string $activity, string $source): bool {
        if ($activity === 'login' || $activity === 'survey') return true;
        $path = strtolower((string) parse_url($source ?: '/', PHP_URL_PATH));
        if ($activity === 'read_news') return str_contains($path, '/berita/');
        if ($activity === 'open_gallery') return str_contains($path, '/galeri');
        if ($activity === 'read_info') {
            foreach (['/pengumuman', '/agenda', '/layanan', '/profil', '/transparansi', '/dokumen', '/umkm', '/potensi'] as $allowed) {
                if (str_contains($path, $allowed)) return true;
            }
            return false;
        }
        return false;
    }

    public static function dailyQuestion($tokenValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];
        if (setting('entertainment_quiz_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Kuis sedang dinonaktifkan.'];

        $answered = db_row("SELECT qa.*, q.explanation FROM entertainment_quiz_answers qa
                            JOIN entertainment_quiz_questions q ON q.id = qa.question_id
                            WHERE qa.device_id = ? AND qa.answer_date = CURDATE() LIMIT 1", [$device['id']]);
        if ($answered) {
            return [
                'ok' => true, 'completed' => true, 'correct' => (bool) $answered['is_correct'],
                'points' => (int) $answered['points'], 'explanation' => $answered['explanation'],
            ];
        }

        $question = self::questionForDevice($device);
        if (!$question) return ['ok' => false, 'error' => 'Belum ada soal aktif.'];
        return [
            'ok' => true,
            'completed' => false,
            'question' => [
                'id' => (int) $question['id'],
                'text' => $question['question'],
                'options' => [
                    'A' => $question['option_a'], 'B' => $question['option_b'],
                    'C' => $question['option_c'], 'D' => $question['option_d'],
                ],
                'category' => $question['category'] ?? 'Pengetahuan',
                'difficulty' => $question['difficulty'] ?? 'mudah',
            ],
        ];
    }

    private static function questionForDevice(array $device): ?array {
        $assigned = db_row(
            "SELECT q.*
             FROM entertainment_quiz_assignments a
             JOIN entertainment_quiz_questions q ON q.id = a.question_id
             WHERE a.device_id = ? AND a.assignment_date = CURDATE()
             LIMIT 1",
            [$device['id']]
        );
        if ($assigned) return $assigned;

        $questions = db_all(
            "SELECT * FROM entertainment_quiz_questions
             WHERE is_active = 1
               AND COALESCE(source, 'manual') <> 'ai'
             ORDER BY sort_order, id"
        );
        if (!$questions) return null;

        $seed = hexdec(substr((string) $device['token_hash'], 0, 7)) + (int) date('z');
        $selected = $questions[$seed % count($questions)];

        db_exec(
            "INSERT IGNORE INTO entertainment_quiz_assignments
                (device_id, assignment_date, question_id, created_at)
             VALUES (?, CURDATE(), ?, NOW())",
            [$device['id'], $selected['id']]
        );

        return db_row(
            "SELECT q.*
             FROM entertainment_quiz_assignments a
             JOIN entertainment_quiz_questions q ON q.id = a.question_id
             WHERE a.device_id = ? AND a.assignment_date = CURDATE()
             LIMIT 1",
            [$device['id']]
        ) ?: $selected;
    }

    public static function answerQuiz($tokenValue, $questionIdValue, $answerValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];
        if (setting('entertainment_quiz_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Kuis sedang dinonaktifkan.'];

        $existing = db_row("SELECT * FROM entertainment_quiz_answers WHERE device_id = ? AND answer_date = CURDATE() LIMIT 1", [$device['id']]);
        if ($existing) return ['ok' => false, 'error' => 'Kuis hari ini sudah dijawab.', 'already_done' => true];

        $question = self::questionForDevice($device);
        $questionId = (int) $questionIdValue;
        $answer = strtoupper(trim((string) $answerValue));
        if (!$question || (int) $question['id'] !== $questionId || !in_array($answer, ['A','B','C','D'], true)) {
            return ['ok' => false, 'error' => 'Soal atau jawaban tidak valid.'];
        }

        $correct = hash_equals((string) $question['correct_option'], $answer);
        $points = $correct ? 15 : 2;
        $streakUpdate = ['streak' => (int) $device['current_streak'], 'longest_streak' => (int) $device['longest_streak'], 'increased' => false];

        db()->beginTransaction();
        try {
            db_insert('entertainment_quiz_answers', [
                'device_id' => $device['id'], 'question_id' => $questionId,
                'answer_option' => $answer, 'is_correct' => $correct ? 1 : 0,
                'points' => $points, 'answer_date' => date('Y-m-d'),
                'ip_address' => Visitor::ip(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            db_exec("INSERT INTO entertainment_daily_games
                        (device_id, play_date, game_type, attempts, best_score, points_awarded, completed, updated_at)
                     VALUES (?, CURDATE(), 'quiz', 1, ?, ?, 1, NOW())
                     ON DUPLICATE KEY UPDATE attempts = attempts + 1,
                        best_score = GREATEST(best_score, VALUES(best_score)),
                        points_awarded = GREATEST(points_awarded, VALUES(points_awarded)),
                        completed = 1, updated_at = NOW()", [
                $device['id'], $correct ? 100 : 20, $points,
            ]);
            self::addPoints((int) $device['id'], $points);
            self::insertEvent((int) $device['id'], 'game_complete', 'quiz', $correct ? 100 : 20, $points, [
                'question_id' => $questionId, 'answer' => $answer, 'correct' => $correct,
            ]);
            $streakUpdate = self::recordPlayStreak((int) $device['id'], 'quiz');
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            return ['ok' => false, 'error' => 'Jawaban belum dapat disimpan.'];
        }

        self::awardDailyMaster((int) $device['id']);
        self::awardBadges((int) $device['id']);
        return [
            'ok' => true, 'correct' => $correct, 'points' => $points,
            'correct_option' => $question['correct_option'],
            'explanation' => $question['explanation'],
            'streak' => $streakUpdate['streak'],
            'streak_increased' => $streakUpdate['increased'],
        ];
    }

    /**
     * Membuat sesi permainan sekali pakai. Hasil game tanpa nonce ini ditolak,
     * sehingga skor tidak dapat dikirim langsung tanpa memulai permainan.
     */
    public static function startGame($tokenValue, $gameValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];

        $game = strtolower(trim((string) $gameValue));
        if (!in_array($game, ['memory', 'puzzle'], true)) return ['ok' => false, 'error' => 'Game tidak valid.'];
        if (setting('entertainment_' . $game . '_enabled', '1') !== '1') {
            return ['ok' => false, 'error' => 'Game sedang dinonaktifkan.'];
        }

        self::ensureSchema();
        $challenge = self::dailyChallenge();
        $gameConfig = (array) ($challenge['game_config'][$game] ?? []);
        $configValue = $game === 'memory'
            ? max(4, min(8, (int) ($gameConfig['pair_count'] ?? 6)))
            : ((int) ($gameConfig['grid'] ?? 3) === 4 ? 4 : 3);

        $nonce = bin2hex(random_bytes(32));
        $hash = hash('sha256', $nonce);
        db_exec("DELETE FROM entertainment_game_sessions
                 WHERE expires_at < NOW() OR (used_at IS NOT NULL AND used_at < NOW() - INTERVAL 1 DAY)");
        db_exec("INSERT INTO entertainment_game_sessions
                    (device_id, game_type, nonce_hash, started_at, expires_at, config_value, created_ip)
                 VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), ?, ?)", [
            (int) $device['id'], $game, $hash, $configValue, Visitor::ip(),
        ]);

        return [
            'ok' => true,
            'session_token' => $nonce,
            'expires_in' => 1800,
            'minimum_seconds' => $game === 'memory' ? 2 : 1,
            'config_value' => $configValue,
        ];
    }

    public static function submitGame($tokenValue, $gameValue, $scoreValue, array $meta = []): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];

        $game = strtolower(trim((string) $gameValue));
        if (!in_array($game, ['memory', 'puzzle'], true)) return ['ok' => false, 'error' => 'Game tidak valid.'];
        if (setting('entertainment_' . $game . '_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Game sedang dinonaktifkan.'];

        $sessionToken = strtolower(trim((string) ($meta['session_token'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $sessionToken)) {
            return ['ok' => false, 'error' => 'Sesi permainan tidak valid. Mulai permainan kembali.'];
        }
        $sessionHash = hash('sha256', $sessionToken);
        $moves = max(0, min(9999, (int) ($meta['moves'] ?? 0)));
        $seconds = max(0, min(3600, (int) ($meta['seconds'] ?? 0)));
        if (($game === 'memory' && $moves < 4) || ($game === 'puzzle' && $moves < 1) || $seconds < 1) {
            return ['ok' => false, 'error' => 'Hasil permainan tidak masuk akal dan tidak dapat disimpan.'];
        }

        $reportedScore = max(1, min(100, (int) $scoreValue));
        $score = 0;
        $desiredPoints = 0;
        $streakUpdate = ['streak' => (int) $device['current_streak'], 'longest_streak' => (int) $device['longest_streak'], 'increased' => false];

        db()->beginTransaction();
        try {
            $gameSession = db_row("SELECT *, TIMESTAMPDIFF(SECOND, started_at, NOW()) AS elapsed
                FROM entertainment_game_sessions
                WHERE nonce_hash = ? AND device_id = ? AND game_type = ?
                FOR UPDATE", [$sessionHash, $device['id'], $game]);
            $minimum = $game === 'memory' ? 2 : 1;
            if (!$gameSession || $gameSession['used_at'] !== null
                || strtotime((string) $gameSession['expires_at']) < time()
                || (int) ($gameSession['elapsed'] ?? 0) < $minimum
                || abs((int) ($gameSession['elapsed'] ?? 0) - $seconds) > 180) {
                throw new RuntimeException('Sesi permainan kedaluwarsa atau hasil tidak konsisten.');
            }

            $configValue = (int) ($gameSession['config_value'] ?? 0);
            $serverElapsed = max(1, (int) ($gameSession['elapsed'] ?? 0));
            $effectiveSeconds = max($seconds, $serverElapsed);
            if ($game === 'memory') {
                $pairCount = max(4, min(8, $configValue ?: 6));
                if ($moves < $pairCount) throw new RuntimeException('Jumlah langkah memory di bawah batas teoritis.');
                $timeWeight = $pairCount > 6 ? 0.35 : 0.55;
                $score = (int) round(100 - max(0, $moves - $pairCount) * 7 - $effectiveSeconds * $timeWeight);
            } else {
                $grid = $configValue === 4 ? 4 : 3;
                if ($moves < 1) throw new RuntimeException('Jumlah langkah puzzle tidak valid.');
                $moveWeight = $grid === 4 ? 1.35 : 3.0;
                $timeWeight = $grid === 4 ? 0.28 : 0.7;
                $score = (int) round(110 - $moves * $moveWeight - $effectiveSeconds * $timeWeight);
            }
            $score = max(10, min(100, $score));
            if (abs($reportedScore - $score) > 3) {
                throw new RuntimeException('Skor client tidak sesuai perhitungan server.');
            }
            $desiredPoints = max(2, min(10, (int) ceil($score / 10)));

            db_exec("UPDATE entertainment_game_sessions
                     SET used_at = NOW(), client_seconds = ?, client_moves = ?
                     WHERE id = ? AND used_at IS NULL", [$seconds, $moves, $gameSession['id']]);

            $row = db_row("SELECT * FROM entertainment_daily_games
                           WHERE device_id = ? AND play_date = CURDATE() AND game_type = ? FOR UPDATE", [$device['id'], $game]);
            $already = (int) ($row['points_awarded'] ?? 0);
            $reward = max(0, $desiredPoints - $already);
            db_exec("INSERT INTO entertainment_daily_games
                        (device_id, play_date, game_type, attempts, best_score, points_awarded, completed, updated_at)
                     VALUES (?, CURDATE(), ?, 1, ?, ?, 1, NOW())
                     ON DUPLICATE KEY UPDATE
                        attempts = attempts + 1,
                        best_score = GREATEST(best_score, VALUES(best_score)),
                        points_awarded = GREATEST(points_awarded, VALUES(points_awarded)),
                        completed = 1,
                        updated_at = NOW()", [$device['id'], $game, $score, $desiredPoints]);
            self::addPoints((int) $device['id'], $reward);
            self::insertEvent((int) $device['id'], 'game_complete', $game, $score, $reward, [
                'moves' => $moves,
                'seconds' => $effectiveSeconds,
                'reported_seconds' => $seconds,
            ]);
            $streakUpdate = self::recordPlayStreak((int) $device['id'], $game);
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            error_log('Entertainment game validation: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Sesi atau hasil permainan tidak valid. Mulai permainan kembali.'];
        }

        self::awardBadges((int) $device['id']);
        return [
            'ok' => true, 'score' => $score, 'points' => $reward,
            'message' => $reward > 0 ? "+{$reward} poin tersimpan." : 'Skor tersimpan. Poin harian game ini sudah maksimal.',
            'streak' => $streakUpdate['streak'],
            'streak_increased' => $streakUpdate['increased'],
        ];
    }

    public static function spinWheel($tokenValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan. Muat ulang halaman.'];
        if (setting('entertainment_wheel_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Roda hadiah sedang dinonaktifkan.'];

        $existing = db_row("SELECT prize_label, prize_points FROM entertainment_wheel_spins WHERE device_id = ? AND spin_date = CURDATE() LIMIT 1", [$device['id']]);
        if ($existing) return ['ok' => false, 'error' => 'Roda hadiah hanya dapat diputar satu kali per hari.', 'already_done' => true, 'prize' => $existing];

        $challenge = self::dailyChallenge();
        $labels = $challenge['game_config']['wheel']['labels'] ?? [];
        if (!is_array($labels) || count($labels) !== 5) {
            $labels = ['Semangat Hari Ini', 'Bonus Ceria', 'Poin Kejutan', 'Bintang Harian', 'Jackpot Virtual'];
        }
        $pool = [
            [(string) $labels[0], 2, 30],
            [(string) $labels[1], 5, 28],
            [(string) $labels[2], 8, 20],
            [(string) $labels[3], 10, 14],
            [(string) $labels[4], 15, 8],
        ];
        $roll = random_int(1, 100);
        $acc = 0; $picked = $pool[0]; $index = 0;
        foreach ($pool as $i => $p) {
            $acc += $p[2];
            if ($roll <= $acc) { $picked = $p; $index = $i; break; }
        }

        $streakUpdate = ['streak' => (int) $device['current_streak'], 'longest_streak' => (int) $device['longest_streak'], 'increased' => false];

        db()->beginTransaction();
        try {
            db_insert('entertainment_wheel_spins', [
                'device_id' => $device['id'], 'spin_date' => date('Y-m-d'),
                'prize_label' => $picked[0], 'prize_points' => $picked[1],
                'ip_address' => Visitor::ip(), 'created_at' => date('Y-m-d H:i:s'),
            ]);
            db_exec("INSERT INTO entertainment_daily_games
                        (device_id, play_date, game_type, attempts, best_score, points_awarded, completed, updated_at)
                     VALUES (?, CURDATE(), 'wheel', 1, ?, ?, 1, NOW())
                     ON DUPLICATE KEY UPDATE attempts = attempts + 1, completed = 1, updated_at = NOW()", [
                $device['id'], $picked[1] * 5, $picked[1],
            ]);
            self::addPoints((int) $device['id'], (int) $picked[1]);
            self::insertEvent((int) $device['id'], 'wheel_spin', 'wheel', $picked[1] * 5, $picked[1], ['label' => $picked[0]]);
            $streakUpdate = self::recordPlayStreak((int) $device['id'], 'wheel');
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) db()->rollBack();
            return ['ok' => false, 'error' => 'Putaran belum dapat disimpan.'];
        }

        self::awardBadges((int) $device['id']);
        return [
            'ok' => true,
            'label' => $picked[0],
            'points' => (int) $picked[1],
            'segment' => $index,
            'streak' => $streakUpdate['streak'],
            'streak_increased' => $streakUpdate['increased'],
        ];
    }

    public static function votePoll($tokenValue, $pollIdValue, $optionIdValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan.'];
        if (setting('entertainment_poll_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Polling sedang dinonaktifkan.'];

        $poll = self::activePoll();
        $pollId = (int) $pollIdValue;
        $optionId = (int) $optionIdValue;
        if (!$poll || (int) $poll['id'] !== $pollId) return ['ok' => false, 'error' => 'Polling tidak tersedia.'];
        $validOption = (bool) db_val("SELECT 1 FROM entertainment_poll_options WHERE id = ? AND poll_id = ? LIMIT 1", [$optionId, $pollId]);
        if (!$validOption) return ['ok' => false, 'error' => 'Pilihan polling tidak valid.'];

        $existing = db_row("SELECT id FROM entertainment_poll_votes WHERE poll_id = ? AND device_id = ? LIMIT 1", [$pollId, $device['id']]);
        if ($existing) return ['ok' => true, 'already_voted' => true, 'poll' => self::pollState((int) $device['id'])];

        try {
            db_insert('entertainment_poll_votes', [
                'poll_id' => $pollId,
                'option_id' => $optionId,
                'device_id' => $device['id'],
                'ip_address' => Visitor::ip(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Pilihan belum dapat disimpan.'];
        }

        self::insertEvent((int) $device['id'], 'poll_vote', null, 0, 0, ['poll_id' => $pollId, 'option_id' => $optionId]);
        $points = self::rewardOnce((int) $device['id'], 'poll_vote', 5, 'poll:' . $pollId, ['poll_id' => $pollId]);
        self::awardDailyMaster((int) $device['id']);
        return ['ok' => true, 'points' => $points, 'poll' => self::pollState((int) $device['id'])];
    }

    private static function activePoll(): ?array {
        return db_row("SELECT * FROM entertainment_polls
                       WHERE is_active = 1
                         AND (starts_at IS NULL OR starts_at <= NOW())
                         AND (ends_at IS NULL OR ends_at >= NOW())
                       ORDER BY id DESC LIMIT 1");
    }

    private static function pollState(int $deviceId): ?array {
        $poll = self::activePoll();
        if (!$poll) return null;
        $options = db_all("SELECT o.id, o.label, o.sort_order, COUNT(v.id) AS votes
                          FROM entertainment_poll_options o
                          LEFT JOIN entertainment_poll_votes v ON v.option_id = o.id
                          WHERE o.poll_id = ?
                          GROUP BY o.id, o.label, o.sort_order
                          ORDER BY o.sort_order, o.id", [$poll['id']]);
        $total = array_sum(array_map(static fn(array $o): int => (int) $o['votes'], $options));
        $vote = db_row("SELECT option_id FROM entertainment_poll_votes WHERE poll_id = ? AND device_id = ? LIMIT 1", [$poll['id'], $deviceId]);
        return [
            'id' => (int) $poll['id'],
            'question' => $poll['question'],
            'voted' => (bool) $vote,
            'selected_option' => $vote ? (int) $vote['option_id'] : null,
            'total_votes' => $total,
            'options' => array_map(static fn(array $o): array => [
                'id' => (int) $o['id'],
                'label' => $o['label'],
                'votes' => (int) $o['votes'],
                'percent' => $total > 0 ? round(((int) $o['votes'] / $total) * 100, 1) : 0,
            ], $options),
        ];
    }

    public static function story($tokenValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan.'];
        if (setting('entertainment_story_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Cerita interaktif sedang dinonaktifkan.'];
        return ['ok' => true, 'story' => self::storyState((int) $device['id'])];
    }

    public static function chooseStory($tokenValue, $choiceValue, bool $reset = false): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan.'];
        if (setting('entertainment_story_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Cerita interaktif sedang dinonaktifkan.'];

        $definition = self::storyDefinition();
        $storyKey = $definition['key'];
        $row = db_row("SELECT * FROM entertainment_story_progress WHERE device_id = ? AND story_key = ? LIMIT 1", [$device['id'], $storyKey]);
        $path = $row && $row['path_json'] ? json_decode((string) $row['path_json'], true) : [];
        if (!is_array($path)) $path = [];

        if ($reset) {
            $nodeKey = 'start';
            $path = [];
            $completed = 0;
        } else {
            $currentKey = $row['node_key'] ?? 'start';
            $current = $definition['nodes'][$currentKey] ?? $definition['nodes']['start'];
            $choice = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $choiceValue)) ?? '';
            if (empty($current['choices'][$choice])) return ['ok' => false, 'error' => 'Pilihan cerita tidak valid.'];
            $nodeKey = $current['choices'][$choice]['next'];
            $path[] = $choice;
            $completed = !empty($definition['nodes'][$nodeKey]['ending']) ? 1 : 0;
        }

        db_exec("INSERT INTO entertainment_story_progress
                    (device_id, story_key, node_key, path_json, completed, updated_at)
                 VALUES (?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE node_key = VALUES(node_key), path_json = VALUES(path_json),
                    completed = VALUES(completed), updated_at = NOW()", [
            $device['id'], $storyKey, $nodeKey,
            json_encode($path, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $completed,
        ]);

        $points = 0;
        if ($completed) {
            self::insertEvent((int) $device['id'], 'story_complete', null, 0, 0, ['story_key' => $storyKey, 'ending' => $nodeKey]);
            $points = self::rewardOnce((int) $device['id'], 'story_complete', 5, 'story:' . $storyKey, ['ending' => $nodeKey]);
        }
        return ['ok' => true, 'points' => $points, 'story' => self::storyState((int) $device['id'])];
    }

    private static function storyDefinition(): array {
        return [
            'key' => 'langkah_baik',
            'title' => 'Langkah Kecil Hari Ini',
            'nodes' => [
                'start' => [
                    'title' => 'Sore yang Cerah',
                    'text' => 'Dalam perjalanan pulang, kamu melihat dua kegiatan menarik: beberapa orang sedang menata pojok baca, sementara di sisi lain ada warga yang merawat kebun bersama.',
                    'choices' => [
                        'baca' => ['label' => 'Membantu menata pojok baca', 'next' => 'library'],
                        'kebun' => ['label' => 'Membantu merawat kebun', 'next' => 'garden'],
                    ],
                ],
                'library' => [
                    'title' => 'Pojok Baca',
                    'text' => 'Buku sudah tersusun, tetapi masih ada dua hal yang bisa dilakukan sebelum tempat ini dibuka.',
                    'choices' => [
                        'label' => ['label' => 'Membuat label kategori buku', 'next' => 'ending_knowledge'],
                        'ajak' => ['label' => 'Mengajak teman membaca bersama', 'next' => 'ending_together'],
                    ],
                ],
                'garden' => [
                    'title' => 'Kebun Bersama',
                    'text' => 'Tanaman terlihat segar. Kamu dapat membantu menyiram bibit atau menyiapkan papan informasi sederhana.',
                    'choices' => [
                        'siram' => ['label' => 'Menyiram bibit baru', 'next' => 'ending_nature'],
                        'papan' => ['label' => 'Membuat papan informasi', 'next' => 'ending_helpful'],
                    ],
                ],
                'ending_knowledge' => [
                    'title' => 'Rapi dan Mudah Dicari',
                    'text' => 'Label yang kamu buat membantu setiap pengunjung menemukan buku dengan lebih cepat. Langkah kecilmu memberi manfaat untuk banyak orang.',
                    'ending' => true,
                ],
                'ending_together' => [
                    'title' => 'Membaca Jadi Lebih Seru',
                    'text' => 'Ajakanmu membuat suasana pojok baca menjadi hangat. Beberapa orang bahkan saling bertukar rekomendasi buku.',
                    'ending' => true,
                ],
                'ending_nature' => [
                    'title' => 'Bibit yang Terjaga',
                    'text' => 'Bibit mendapat cukup air dan siap tumbuh. Kepedulian sederhana membuat lingkungan terasa lebih hidup.',
                    'ending' => true,
                ],
                'ending_helpful' => [
                    'title' => 'Informasi yang Bermanfaat',
                    'text' => 'Papan yang kamu siapkan membantu warga memahami jenis tanaman dan cara merawatnya.',
                    'ending' => true,
                ],
            ],
        ];
    }

    private static function storyState(int $deviceId): array {
        $definition = self::storyDefinition();
        $row = db_row("SELECT * FROM entertainment_story_progress WHERE device_id = ? AND story_key = ? LIMIT 1", [$deviceId, $definition['key']]);
        $nodeKey = $row['node_key'] ?? 'start';
        $node = $definition['nodes'][$nodeKey] ?? $definition['nodes']['start'];
        $choices = [];
        foreach (($node['choices'] ?? []) as $key => $choice) {
            $choices[] = ['key' => $key, 'label' => $choice['label']];
        }
        return [
            'key' => $definition['key'],
            'title' => $definition['title'],
            'node' => $nodeKey,
            'node_title' => $node['title'],
            'text' => $node['text'],
            'choices' => $choices,
            'completed' => !empty($node['ending']),
        ];
    }

    public static function nextFact($tokenValue, $excludeValue = 0): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan.'];
        if (setting('entertainment_facts_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Kartu fakta sedang dinonaktifkan.'];
        $fact = self::factForDevice((int) $device['id'], max(0, (int) $excludeValue));
        if (!$fact) return ['ok' => false, 'error' => 'Belum ada fakta yang tersedia.'];
        $points = self::rewardOnce((int) $device['id'], 'fact_read', 1, 'fact:' . $fact['id'], ['fact_id' => $fact['id']]);
        return ['ok' => true, 'points' => $points, 'fact' => $fact];
    }

    private static function factForDevice(int $deviceId, int $exclude = 0): ?array {
        $rows = db_all("SELECT id, category, content FROM entertainment_facts WHERE is_active = 1" . ($exclude > 0 ? " AND id <> ?" : "") . " ORDER BY sort_order, id", $exclude > 0 ? [$exclude] : []);
        if (!$rows) return null;
        $index = random_int(0, count($rows) - 1);
        return [
            'id' => (int) $rows[$index]['id'],
            'category' => $rows[$index]['category'],
            'content' => $rows[$index]['content'],
        ];
    }

    public static function claimDailyBox($tokenValue): array {
        $device = self::identify($tokenValue, false);
        if (!$device) return ['ok' => false, 'error' => 'Profil pemain belum dapat disiapkan.'];
        if (setting('entertainment_daily_box_enabled', '1') !== '1') return ['ok' => false, 'error' => 'Kotak harian sedang dinonaktifkan.'];

        $existing = db_row("SELECT reward_label, reward_points FROM entertainment_daily_boxes
                            WHERE device_id = ? AND claim_date = CURDATE() LIMIT 1", [$device['id']]);
        if ($existing) return [
            'ok' => true, 'already_claimed' => true,
            'reward' => ['label' => $existing['reward_label'], 'points' => (int) $existing['reward_points']],
        ];

        $pool = [
            ['Bonus Kunjungan', 3, 34],
            ['Poin Semangat', 5, 30],
            ['Kejutan Harian', 7, 22],
            ['Hadiah Istimewa', 10, 11],
            ['Bonus Langka', 15, 3],
        ];
        $roll = random_int(1, 100);
        $acc = 0; $picked = $pool[0];
        foreach ($pool as $item) {
            $acc += $item[2];
            if ($roll <= $acc) { $picked = $item; break; }
        }

        try {
            db_insert('entertainment_daily_boxes', [
                'device_id' => $device['id'],
                'claim_date' => date('Y-m-d'),
                'reward_label' => $picked[0],
                'reward_points' => $picked[1],
                'ip_address' => Visitor::ip(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            self::addPoints((int) $device['id'], (int) $picked[1]);
            self::insertEvent((int) $device['id'], 'daily_box', null, 0, (int) $picked[1], ['label' => $picked[0]]);
            self::awardBadges((int) $device['id']);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Kotak harian belum dapat dibuka.'];
        }

        return ['ok' => true, 'reward' => ['label' => $picked[0], 'points' => (int) $picked[1]]];
    }

    private static function missions(array $daily, array $activities, bool $pollDone): array {
        $items = [];
        if (setting('entertainment_activity_points_enabled', '1') === '1') {
            $items[] = ['key' => 'read_news', 'title' => 'Baca satu berita', 'done' => !empty($activities['activity_read_news'])];
            $items[] = ['key' => 'open_gallery', 'title' => 'Buka galeri', 'done' => !empty($activities['activity_open_gallery'])];
            if (setting('feedback_enabled', '1') === '1') {
                $items[] = ['key' => 'survey', 'title' => 'Beri tanggapan pada survei', 'done' => !empty($activities['activity_survey'])];
            }
        }
        if (setting('entertainment_quiz_enabled', '1') === '1') {
            $items[] = ['key' => 'quiz', 'title' => 'Jawab kuis harian', 'done' => !empty($daily['quiz']['completed'])];
        }
        if (setting('entertainment_poll_enabled', '1') === '1' && self::activePoll()) {
            $items[] = ['key' => 'poll', 'title' => 'Beri tanggapan pada polling', 'done' => $pollDone];
        }
        $done = count(array_filter($items, static fn(array $m): bool => (bool) $m['done']));
        $total = count($items);
        return ['items' => $items, 'done' => $done, 'total' => $total, 'bonus' => 20, 'complete' => $total > 0 && $done === $total];
    }

    private static function awardDailyMaster(int $deviceId): void {
        $required = [];
        if (setting('entertainment_activity_points_enabled', '1') === '1') {
            $required['read_news'] = (bool) db_val("SELECT 1 FROM entertainment_events WHERE device_id = ? AND event_type = 'activity_read_news' AND created_at >= CURDATE() LIMIT 1", [$deviceId]);
            $required['open_gallery'] = (bool) db_val("SELECT 1 FROM entertainment_events WHERE device_id = ? AND event_type = 'activity_open_gallery' AND created_at >= CURDATE() LIMIT 1", [$deviceId]);
            if (setting('feedback_enabled', '1') === '1') {
                $required['survey'] = (bool) db_val("SELECT 1 FROM entertainment_events WHERE device_id = ? AND event_type = 'activity_survey' AND created_at >= CURDATE() LIMIT 1", [$deviceId]);
            }
        }
        if (setting('entertainment_quiz_enabled', '1') === '1') {
            $required['quiz'] = (bool) db_val("SELECT 1 FROM entertainment_daily_games WHERE device_id = ? AND play_date = CURDATE() AND game_type = 'quiz' AND completed = 1 LIMIT 1", [$deviceId]);
        }
        if (setting('entertainment_poll_enabled', '1') === '1' && self::activePoll()) {
            $required['poll'] = (bool) db_val("SELECT 1 FROM entertainment_events WHERE device_id = ? AND event_type = 'poll_vote' AND created_at >= CURDATE() LIMIT 1", [$deviceId]);
        }
        if (!$required || in_array(false, $required, true)) return;

        try {
            $inserted = db_exec("INSERT IGNORE INTO entertainment_daily_bonuses
                                (device_id, bonus_date, bonus_key, points, created_at)
                                VALUES (?, CURDATE(), 'daily_master', 20, NOW())", [$deviceId]);
            if ($inserted > 0) {
                self::addPoints($deviceId, 20);
                self::insertEvent($deviceId, 'mission_bonus', null, 0, 20, ['mission' => 'daily_master']);
            }
        } catch (Throwable $e) {
            error_log('Entertainment daily bonus: ' . $e->getMessage());
        }
    }

    private static function badgeDefinitions(): array {
        return [
            'pendatang_baru' => ['Langkah Pertama', 'bi-stars'],
            'pengunjung_setia' => ['Pengunjung Setia', 'bi-calendar2-check-fill'],
            'juara_kuis' => ['Juara Kuis', 'bi-lightbulb-fill'],
            'pembaca_aktif' => ['Pembaca Aktif', 'bi-newspaper'],
            'penjelajah_galeri' => ['Penjelajah Galeri', 'bi-images'],
            'kontributor' => ['Kontributor', 'bi-chat-heart-fill'],
            'pemain_aktif' => ['Pemain Aktif', 'bi-controller'],
            'ingatan_tajam' => ['Ingatan Tajam', 'bi-grid-3x3-gap-fill'],
            'ahli_puzzle' => ['Ahli Puzzle', 'bi-puzzle-fill'],
            'kolektor_500' => ['Kolektor 500', 'bi-trophy-fill'],
            'master_harian' => ['Misi Sempurna', 'bi-award-fill'],
        ];
    }

    private static function awardBadges(int $deviceId): void {
        try {
            $device = db_row("SELECT total_points, current_streak FROM entertainment_devices WHERE id = ?", [$deviceId]);
            if (!$device) return;
            $stats = db_row("SELECT
                    SUM(game_type IS NOT NULL) AS games,
                    SUM(game_type='quiz' AND score=100) AS quiz_correct,
                    MAX(CASE WHEN game_type='memory' THEN score ELSE 0 END) AS memory_best,
                    MAX(CASE WHEN game_type='puzzle' THEN score ELSE 0 END) AS puzzle_best,
                    SUM(event_type='activity_read_news') AS news_reads,
                    SUM(event_type='activity_open_gallery') AS gallery_visits,
                    SUM(event_type IN ('activity_survey','poll_vote')) AS contributions
                FROM entertainment_events WHERE device_id = ?", [$deviceId]);
            $master = (bool) db_val("SELECT 1 FROM entertainment_daily_bonuses WHERE device_id = ? AND bonus_key='daily_master' LIMIT 1", [$deviceId]);

            $checks = [
                'pendatang_baru' => true,
                'pengunjung_setia' => (int) $device['current_streak'] >= 7,
                'juara_kuis' => (int) ($stats['quiz_correct'] ?? 0) >= 5,
                'pembaca_aktif' => (int) ($stats['news_reads'] ?? 0) >= 5,
                'penjelajah_galeri' => (int) ($stats['gallery_visits'] ?? 0) >= 3,
                'kontributor' => (int) ($stats['contributions'] ?? 0) >= 3,
                'pemain_aktif' => (int) ($stats['games'] ?? 0) >= 5,
                'ingatan_tajam' => (int) ($stats['memory_best'] ?? 0) >= 80,
                'ahli_puzzle' => (int) ($stats['puzzle_best'] ?? 0) >= 80,
                'kolektor_500' => (int) $device['total_points'] >= 500,
                'master_harian' => $master,
            ];
            foreach ($checks as $key => $earned) {
                if ($earned) {
                    db_exec("INSERT IGNORE INTO entertainment_device_badges (device_id, badge_key, awarded_at)
                             VALUES (?, ?, NOW())", [$deviceId, $key]);
                }
            }
        } catch (Throwable $e) {
            error_log('Entertainment badges: ' . $e->getMessage());
        }
    }

    public static function leaderboard(int $limit = 20): array {
        if (!self::ensureSchema() || setting('entertainment_leaderboard_enabled', '1') !== '1') return [];
        $limit = max(3, min(50, $limit));
        $rows = db_all("SELECT token_hint, nickname, avatar_key, frame_key, featured_badge,
                              total_points, current_streak,
                              (SELECT COUNT(*) FROM entertainment_device_badges b WHERE b.device_id=d.id) AS badges
                       FROM entertainment_devices d
                       WHERE total_points > 0
                       ORDER BY total_points DESC, longest_streak DESC, first_seen_at ASC
                       LIMIT {$limit}");
        return array_map(static fn(array $r): array => [
            'name' => $r['nickname'] ?: 'Pemain #' . substr($r['token_hint'], 0, 6),
            'avatar' => $r['avatar_key'] ?: 'leaf',
            'frame' => $r['frame_key'] ?: 'simple',
            'featured_badge' => $r['featured_badge'] ?: '',
            'points' => (int) $r['total_points'],
            'streak' => (int) $r['current_streak'],
            'badges' => (int) $r['badges'],
        ], $rows);
    }

    public static function adminStats(): array {
        self::ensureSchema();
        return [
            'total_devices' => (int) db_val("SELECT COUNT(*) FROM entertainment_devices"),
            'active_today' => (int) db_val("SELECT COUNT(*) FROM entertainment_devices WHERE last_seen_at >= CURDATE()"),
            'games_today' => (int) db_val("SELECT COUNT(*) FROM entertainment_events WHERE created_at >= CURDATE() AND game_type IS NOT NULL"),
            'interactions_today' => (int) db_val("SELECT COUNT(*) FROM entertainment_events WHERE created_at >= CURDATE() AND game_type IS NULL"),
            'points_total' => (int) db_val("SELECT COALESCE(SUM(total_points),0) FROM entertainment_devices"),
            'top' => db_all("SELECT id, token_hint, nickname, ip_address, device, browser, os, total_points,
                                    current_streak, longest_streak, first_seen_at, last_seen_at
                             FROM entertainment_devices ORDER BY total_points DESC, last_seen_at DESC LIMIT 50"),
            'recent' => db_all("SELECT e.*, d.token_hint, d.nickname
                                FROM entertainment_events e
                                JOIN entertainment_devices d ON d.id=e.device_id
                                ORDER BY e.id DESC LIMIT 100"),
            'ip_logs' => db_all("SELECT i.*, d.token_hint, d.nickname
                                 FROM entertainment_device_ips i
                                 JOIN entertainment_devices d ON d.id=i.device_id
                                 ORDER BY i.last_seen_at DESC LIMIT 100"),
            'game_totals' => db_all("SELECT activity_key AS game_type,
                                            COUNT(*) total,
                                            COALESCE(SUM(points),0) points
                                     FROM (
                                         SELECT CASE
                                                  WHEN game_type IS NOT NULL THEN game_type
                                                  WHEN event_type LIKE 'activity_%' THEN REPLACE(event_type,'activity_','')
                                                  ELSE event_type
                                                END AS activity_key,
                                                points,
                                                created_at
                                         FROM entertainment_events
                                     ) activity_summary
                                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                                     GROUP BY activity_key
                                     ORDER BY total DESC"),
        ];
    }
}
