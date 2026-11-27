<?php

class EntertainmentAdminController extends Controller {

    public array $actions = ['index', 'simpan', 'export', 'question_save', 'question_delete'];

    public function __construct() {
        Auth::requirePermission('kelola_hiburan');
        Entertainment::ensureSchema();
    }

    public function index(): void {
        $stats = Entertainment::adminStats();
        $questions = db_all("SELECT * FROM entertainment_quiz_questions WHERE COALESCE(source, 'manual') <> 'ai' ORDER BY sort_order ASC, id ASC");
        $this->adminView('hiburan', [
            'pageTitle' => 'Zona Hiburan',
            'stats' => $stats,
            'questions' => $questions,
            'settings' => [
                'enabled' => setting('entertainment_enabled', '1') === '1',
                'quiz' => setting('entertainment_quiz_enabled', '1') === '1',
                'memory' => setting('entertainment_memory_enabled', '1') === '1',
                'puzzle' => setting('entertainment_puzzle_enabled', '1') === '1',
                'wheel' => setting('entertainment_wheel_enabled', '1') === '1',
                'leaderboard' => setting('entertainment_leaderboard_enabled', '1') === '1',
                'activity_points' => setting('entertainment_activity_points_enabled', '1') === '1',
                'poll' => setting('entertainment_poll_enabled', '1') === '1',
                'story' => setting('entertainment_story_enabled', '1') === '1',
                'facts' => setting('entertainment_facts_enabled', '1') === '1',
                'profile' => setting('entertainment_profile_enabled', '1') === '1',
                'daily_box' => setting('entertainment_daily_box_enabled', '1') === '1',
            ],
        ]);
    }

    public function simpan(): void {
        verify_csrf();
        foreach ([
            'entertainment_enabled',
            'entertainment_quiz_enabled',
            'entertainment_memory_enabled',
            'entertainment_puzzle_enabled',
            'entertainment_wheel_enabled',
            'entertainment_leaderboard_enabled',
            'entertainment_activity_points_enabled',
            'entertainment_poll_enabled',
            'entertainment_story_enabled',
            'entertainment_facts_enabled',
            'entertainment_profile_enabled',
            'entertainment_daily_box_enabled',
        ] as $key) {
            set_setting($key, isset($_POST[$key]) ? '1' : '0');
        }
        set_setting('entertainment_retention', 'permanent');
        set_setting('asset_version', (string) time());
        log_activity('edit', 'hiburan', 'Memperbarui pengaturan Zona Hiburan');
        flash('success', 'Pengaturan Zona Hiburan berhasil disimpan.');
        redirect('admin/hiburan');
    }

    public function question_save(): void {
        verify_csrf();
        $id = max(0, (int) ($_POST['id'] ?? 0));
        $question = self::limitText(trim(strip_tags((string) ($_POST['question'] ?? ''))), 500);
        $options = [];
        foreach (['a','b','c','d'] as $key) {
            $options[$key] = self::limitText(trim(strip_tags((string) ($_POST['option_' . $key] ?? ''))), 255);
        }
        $correct = strtoupper(trim((string) ($_POST['correct_option'] ?? 'A')));
        $explanation = self::limitText(trim(strip_tags((string) ($_POST['explanation'] ?? ''))), 500);
        $sort = max(0, min(9999, (int) ($_POST['sort_order'] ?? 0)));
        $active = isset($_POST['is_active']) ? 1 : 0;

        if ($question === '' || in_array('', $options, true) || !in_array($correct, ['A','B','C','D'], true)) {
            flash('danger', 'Pertanyaan, seluruh pilihan, dan jawaban benar wajib diisi.');
            redirect('admin/hiburan#quizQuestions');
        }

        $data = [
            'question' => $question,
            'option_a' => $options['a'],
            'option_b' => $options['b'],
            'option_c' => $options['c'],
            'option_d' => $options['d'],
            'correct_option' => $correct,
            'explanation' => $explanation ?: null,
            'is_active' => $active,
            'sort_order' => $sort,
            'question_hash' => hash('sha256', self::lowerText(trim($question))),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id > 0 && db_val("SELECT id FROM entertainment_quiz_questions WHERE id = ?", [$id])) {
            db_update('entertainment_quiz_questions', $data, 'id = ?', [$id]);
            log_activity('edit', 'hiburan', 'Mengubah soal kuis #' . $id);
        } else {
            $data['source'] = 'manual';
            $data['created_at'] = date('Y-m-d H:i:s');
            $id = db_insert('entertainment_quiz_questions', $data);
            log_activity('create', 'hiburan', 'Menambah soal kuis #' . $id);
        }

        flash('success', 'Soal kuis berhasil disimpan.');
        redirect('admin/hiburan#quizQuestions');
    }

    public function question_delete(): void {
        verify_csrf();
        $id = max(0, (int) ($_POST['id'] ?? 0));
        if ($id < 1) {
            flash('danger', 'Soal tidak valid.');
            redirect('admin/hiburan#quizQuestions');
        }
        $used = (bool) db_val("SELECT 1 FROM entertainment_quiz_answers WHERE question_id = ? LIMIT 1", [$id]);
        if ($used) {
            db_exec("UPDATE entertainment_quiz_questions SET is_active = 0, updated_at = NOW() WHERE id = ?", [$id]);
            flash('warning', 'Soal sudah memiliki riwayat jawaban sehingga tidak dihapus; statusnya dinonaktifkan.');
        } else {
            db_exec("DELETE FROM entertainment_quiz_questions WHERE id = ?", [$id]);
            flash('success', 'Soal kuis berhasil dihapus.');
        }
        log_activity('delete', 'hiburan', 'Menghapus/nonaktifkan soal kuis #' . $id);
        redirect('admin/hiburan#quizQuestions');
    }

    public function export(): void {
        $type = strtolower(trim((string) ($_GET['type'] ?? 'devices')));
        $filename = 'zona-hiburan-' . $type . '-' . date('Ymd-His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");

        if ($type === 'events') {
            fputcsv($out, ['ID','Perangkat','Nama','Jenis','Game','Skor','Poin','IP','Halaman','Waktu']);
            $st = db()->query("SELECT e.*, d.token_hint, d.nickname
                              FROM entertainment_events e
                              JOIN entertainment_devices d ON d.id=e.device_id
                              ORDER BY e.id ASC");
            while ($r = $st->fetch()) {
                fputcsv($out, [
                    $r['id'], $r['token_hint'], $r['nickname'] ?: '',
                    $r['event_type'], $r['game_type'] ?: '', $r['score'], $r['points'],
                    $r['ip_address'], $r['page_path'], $r['created_at']
                ]);
            }
        } elseif ($type === 'ips') {
            fputcsv($out, ['ID','Perangkat','Nama','IP','Pertama Dilihat','Terakhir Dilihat','Jumlah Kunjungan']);
            $st = db()->query("SELECT i.*, d.token_hint, d.nickname
                              FROM entertainment_device_ips i
                              JOIN entertainment_devices d ON d.id=i.device_id
                              ORDER BY i.id ASC");
            while ($r = $st->fetch()) {
                fputcsv($out, [
                    $r['id'], $r['token_hint'], $r['nickname'] ?: '', $r['ip_address'],
                    $r['first_seen_at'], $r['last_seen_at'], $r['visit_count']
                ]);
            }
        } else {
            fputcsv($out, ['ID','Perangkat','Nama','IP Terakhir','Device','Browser','OS','Poin','Streak','Streak Terpanjang','Pertama Dilihat','Terakhir Dilihat']);
            $st = db()->query("SELECT * FROM entertainment_devices ORDER BY id ASC");
            while ($r = $st->fetch()) {
                fputcsv($out, [
                    $r['id'], $r['token_hint'], $r['nickname'] ?: '', $r['ip_address'],
                    $r['device'], $r['browser'], $r['os'], $r['total_points'],
                    $r['current_streak'], $r['longest_streak'], $r['first_seen_at'], $r['last_seen_at']
                ]);
            }
        }

        fclose($out);
        log_activity('export', 'hiburan', 'Ekspor data Zona Hiburan: ' . $type);
        exit;
    }

    private static function limitText(string $text, int $limit): string {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $limit, 'UTF-8');
        }
        if (function_exists('iconv_substr')) {
            $value = iconv_substr($text, 0, $limit, 'UTF-8');
            if (is_string($value)) return $value;
        }
        if (preg_match_all('/./us', $text, $matches) === false) {
            return substr($text, 0, $limit);
        }
        return implode('', array_slice($matches[0], 0, $limit));
    }

    private static function lowerText(string $text): string {
        return function_exists('mb_strtolower')
            ? mb_strtolower($text, 'UTF-8')
            : strtolower($text);
    }


}
