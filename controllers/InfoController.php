<?php

class InfoController extends Controller {

    public function pengumuman(): void {
        $pg = paginate("SELECT * FROM announcements WHERE deleted_at IS NULL AND is_active=1
            AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
            ORDER BY is_important DESC, start_date DESC", [], 10);
        $this->view('pengumuman', ['pageTitle' => 'Pengumuman - ' . setting('village_name'), 'pg' => $pg]);
    }

    public function pengumumanDetail($id): void {
        $item = db_row("SELECT * FROM announcements WHERE id = ? AND deleted_at IS NULL AND is_active=1", [(int) $id]);
        if (!$item) $this->notFound();
        $this->view('pengumuman_detail', ['pageTitle' => $item['title'] . ' - ' . setting('village_name'), 'item' => $item]);
    }

    public function agenda(): void {
        $bulan = (int) ($_GET['bulan'] ?? 0);
        $tahun = (int) ($_GET['tahun'] ?? 0);
        $where = ["deleted_at IS NULL"]; $params = [];
        if ($bulan >= 1 && $bulan <= 12) { $where[] = "MONTH(event_date) = ?"; $params[] = $bulan; }
        if ($tahun > 2000) { $where[] = "YEAR(event_date) = ?"; $params[] = $tahun; }
        $pg = paginate("SELECT * FROM agendas WHERE " . implode(' AND ', $where) . " ORDER BY event_date DESC", $params, 12);
        $this->view('agenda', [
            'pageTitle' => 'Agenda Kegiatan - ' . setting('village_name'),
            'pg' => $pg, 'bulan' => $bulan, 'tahun' => $tahun,
            'tahunList' => db_all("SELECT DISTINCT YEAR(event_date) AS y FROM agendas WHERE deleted_at IS NULL ORDER BY y DESC"),
            'needsLightbox' => true,
        ]);
    }
}
