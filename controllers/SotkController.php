<?php

class SotkController extends Controller {

    public function index(): void {
        $items = db_all("SELECT * FROM sotk_items WHERE deleted_at IS NULL AND is_active=1 ORDER BY level ASC, sort_order ASC, id ASC");
        $levels = [];
        foreach ($items as $it) $levels[(int) $it['level']][] = $it;
        $this->view('sotk', [
            'pageTitle' => 'Struktur Organisasi (SOTK) - ' . setting('village_name'),
            'levels' => $levels,
        ]);
    }
}
