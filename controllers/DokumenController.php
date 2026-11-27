<?php

class DokumenController extends Controller {

    public array $actions = ['download'];

    public function index(): void {
        $kategori = trim($_GET['kategori'] ?? '');
        $tahun = (int) ($_GET['tahun'] ?? 0);
        $where = ["deleted_at IS NULL", "is_active = 1", "is_public = 1"]; $params = [];
        if ($kategori !== '') { $where[] = "category = ?"; $params[] = $kategori; }
        if ($tahun > 2000) { $where[] = "year = ?"; $params[] = $tahun; }
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = "title LIKE ?"; $params[] = "%$q%"; }
        $pg = paginate("SELECT * FROM documents WHERE " . implode(' AND ', $where) . " ORDER BY year DESC, id DESC", $params, 15);
        $this->view('dokumen', [
            'pageTitle' => 'Dokumen Publik - ' . setting('village_name'),
            'pg' => $pg, 'kategori' => $kategori, 'tahun' => $tahun, 'q' => $q,
            'kategoriList' => db_all("SELECT DISTINCT category FROM documents WHERE deleted_at IS NULL AND is_public=1 AND category IS NOT NULL"),
            'tahunList' => db_all("SELECT DISTINCT year FROM documents WHERE deleted_at IS NULL AND is_public=1 AND year IS NOT NULL ORDER BY year DESC"),
        ]);
    }

    public function download($id): void {
        $doc = db_row("SELECT * FROM documents WHERE id = ? AND deleted_at IS NULL AND is_active=1 AND is_public=1", [(int) $id]);
        if (!$doc || !$doc['file_path']) $this->notFound();
        $file = BASE_PATH . '/uploads/' . $doc['file_path'];
        if (!is_file($file)) $this->notFound();
        db_exec("UPDATE documents SET downloads = downloads + 1 WHERE id = ?", [$doc['id']]);
        $safeName = preg_replace('/[^A-Za-z0-9 _.-]/', '', $doc['title']) . '.' . $doc['file_type'];
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safeName . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}
