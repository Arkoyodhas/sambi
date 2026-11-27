<?php

class FeedbackController extends Controller {

    public array $actions = ['index', 'simpan', 'hapus_pertanyaan', 'hapus_respons', 'cetak'];

    public function __construct() { Auth::requirePermission('lihat_feedback'); }

    public function index(): void {
        $this->adminView('feedback', [
            'pageTitle' => 'Kelola Feedback',
            'questions' => db_all("SELECT * FROM feedback_questions ORDER BY sort_order ASC, id ASC"),
            'responses' => db_all("SELECT * FROM feedback_responses ORDER BY id DESC LIMIT 100"),
            'total' => (int) db_val("SELECT COUNT(*) FROM feedback_responses"),
            'today' => (int) db_val("SELECT COUNT(*) FROM feedback_responses WHERE DATE(created_at) = CURDATE()"),
            'enabled' => setting('feedback_enabled', '1') === '1',
            'delay' => (int) setting('feedback_delay_seconds', '25'),
            'intro' => setting('feedback_intro', ''),
        ]);
    }

    public function simpan(): void {
        verify_csrf();
        set_setting('feedback_enabled', ($_POST['enabled'] ?? '0') === '1' ? '1' : '0');
        set_setting('feedback_delay_seconds', (string) max(3, min(120, (int) ($_POST['delay'] ?? 25))));
        set_setting('feedback_intro', mb_substr(trim((string) ($_POST['intro'] ?? '')), 0, 400));

        // Simpan/perbarui pertanyaan (array)
        $ids = (array) ($_POST['id'] ?? []);
        $labels = (array) ($_POST['label'] ?? []);
        $types = (array) ($_POST['type'] ?? []);
        $optionsArr = (array) ($_POST['options'] ?? []);
        $requireds = (array) ($_POST['required'] ?? []);
        $actives = (array) ($_POST['active'] ?? []);
        $orders = (array) ($_POST['order'] ?? []);
        foreach ($labels as $i => $label) {
            $label = trim((string) $label); if ($label === '') continue;
            $type = in_array($types[$i] ?? '', ['rating', 'choice', 'text', 'textarea'], true) ? $types[$i] : 'rating';
            $opt = null;
            if ($type === 'choice') {
                $parts = array_filter(array_map('trim', explode("\n", (string) ($optionsArr[$i] ?? ''))));
                if ($parts) $opt = json_encode(array_values($parts), JSON_UNESCAPED_UNICODE);
            }
            $data = [
                'label' => mb_substr($label, 0, 255), 'type' => $type, 'options' => $opt,
                'required' => in_array($i, $requireds) ? 1 : 0,
                'is_active' => in_array($i, $actives) ? 1 : 0,
                'sort_order' => (int) ($orders[$i] ?? $i),
            ];
            $id = (int) ($ids[$i] ?? 0);
            if ($id > 0) db_exec("UPDATE feedback_questions SET label=?,type=?,options=?,required=?,is_active=?,sort_order=? WHERE id=?", [
                $data['label'], $data['type'], $data['options'], $data['required'], $data['is_active'], $data['sort_order'], $id]);
            else db_insert('feedback_questions', $data);
        }
        flash('success', 'Pengaturan & pertanyaan feedback disimpan.');
        redirect('admin/feedback');
    }

    public function hapus_pertanyaan(): void {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) db_exec("DELETE FROM feedback_questions WHERE id = ?", [$id]);
        flash('success', 'Pertanyaan dihapus.');
        redirect('admin/feedback');
    }

    public function hapus_respons(): void {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) db_exec("DELETE FROM feedback_responses WHERE id = ?", [$id]);
        flash('success', 'Respons dihapus.');
        redirect('admin/feedback');
    }

    /** Halaman cetak (window.print → PDF via browser) */
    public function cetak(): void {
        $qs = db_all("SELECT * FROM feedback_questions ORDER BY sort_order ASC, id ASC");
        $qMap = []; foreach ($qs as $q) $qMap[$q['id']] = $q;
        $rows = db_all("SELECT * FROM feedback_responses ORDER BY id DESC");
        $this->view('feedback_cetak', [
            'pageTitle' => 'Laporan Feedback',
            'questions' => $qs, 'qMap' => $qMap, 'rows' => $rows,
            'siteName' => setting('village_name', 'Desa'),
        ]);
    }
}
