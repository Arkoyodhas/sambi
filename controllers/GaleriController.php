<?php

class GaleriController extends Controller {

    public function index(): void {
        $kategori = trim($_GET['kategori'] ?? '');
        $tahun = (int) ($_GET['tahun'] ?? 0);
        $where = ["g.deleted_at IS NULL", "g.is_active = 1"]; $params = [];
        if ($kategori !== '') { $where[] = "g.category = ?"; $params[] = $kategori; }
        if ($tahun > 2000) { $where[] = "g.year = ?"; $params[] = $tahun; }
        $pg = paginate("SELECT g.*, a.name AS album_name FROM galleries g LEFT JOIN gallery_albums a ON a.id=g.album_id
            WHERE " . implode(' AND ', $where) . " ORDER BY g.id DESC", $params, 12);
        $this->view('galeri', [
            'pageTitle' => 'Galeri Desa - ' . setting('village_name'),
            'pg' => $pg, 'kategori' => $kategori, 'tahun' => $tahun,
            'albums' => db_all("SELECT a.*, (SELECT COUNT(*) FROM galleries g WHERE g.album_id=a.id AND g.deleted_at IS NULL AND g.is_active=1) AS total
                FROM gallery_albums a WHERE a.is_active=1 ORDER BY a.id DESC"),
            'kategoriList' => db_all("SELECT DISTINCT category FROM galleries WHERE deleted_at IS NULL AND category IS NOT NULL AND category != ''"),
            'tahunList' => db_all("SELECT DISTINCT year FROM galleries WHERE deleted_at IS NULL AND year IS NOT NULL ORDER BY year DESC"),
            'album' => null,
            'needsLightbox' => true,
        ]);
    }

    public function album(string $slug): void {
        $album = db_row("SELECT * FROM gallery_albums WHERE slug = ? AND is_active = 1", [$slug]);
        if (!$album) $this->notFound();
        $pg = paginate("SELECT g.*, a.name AS album_name FROM galleries g LEFT JOIN gallery_albums a ON a.id=g.album_id
            WHERE g.deleted_at IS NULL AND g.is_active=1 AND g.album_id = " . (int) $album['id'] . " ORDER BY g.id DESC", [], 12);
        $this->view('galeri', [
            'pageTitle' => $album['name'] . ' - Galeri ' . setting('village_name'),
            'pg' => $pg, 'kategori' => '', 'tahun' => 0,
            'albums' => [], 'kategoriList' => [], 'tahunList' => [],
            'album' => $album,
            'needsLightbox' => true,
        ]);
    }
}
