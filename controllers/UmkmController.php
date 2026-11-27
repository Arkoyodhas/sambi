<?php

class UmkmController extends Controller {

    public function index(): void {
        $kategori = trim($_GET['kategori'] ?? '');
        $where = ["deleted_at IS NULL", "is_active = 1"]; $params = [];
        if ($kategori !== '') { $where[] = "category = ?"; $params[] = $kategori; }
        $pg = paginate("SELECT * FROM umkm_products WHERE " . implode(' AND ', $where) . " ORDER BY id DESC", $params, 12);
        $this->view('umkm', [
            'pageTitle' => 'UMKM Desa - ' . setting('village_name'),
            'pg' => $pg, 'kategori' => $kategori,
            'kategoriList' => db_all("SELECT DISTINCT category FROM umkm_products WHERE deleted_at IS NULL AND category IS NOT NULL AND category != ''"),
            'potentials' => db_all("SELECT * FROM village_potentials WHERE deleted_at IS NULL AND is_active=1 ORDER BY category, id DESC LIMIT 6"),
        ]);
    }

    public function potensi(): void {
        $pg = paginate("SELECT * FROM village_potentials WHERE deleted_at IS NULL AND is_active=1 ORDER BY category, id DESC", [], 12);
        $this->view('potensi', ['pageTitle' => 'Potensi Desa - ' . setting('village_name'), 'pg' => $pg]);
    }
}
