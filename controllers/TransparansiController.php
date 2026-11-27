<?php

class TransparansiController extends Controller {

    public array $actions = ['index', 'idm', 'bansos', 'stunting', 'cetak'];

    /** Halaman cetak universal (semua data transparansi & anak-modul → PDF via window.print()) */
    public function cetak(string $kind = ''): void {
        $kind = $kind !== '' ? $kind : trim((string) ($_GET['jenis'] ?? 'anggaran'));
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        $data = ['kind' => $kind, 'tahun' => $tahun, 'siteName' => setting('village_name', 'Desa')];
        switch ($kind) {
            case 'idm':
                $data['rows'] = db_all("SELECT * FROM idm_data ORDER BY tahun DESC");
                $data['title'] = 'Indeks Desa Membangun (IDM)';
                break;
            case 'bansos':
                $data['programs'] = db_all("SELECT p.*, COUNT(r.id) jml FROM bansos_programs p LEFT JOIN bansos_recipients r ON r.program_id=p.id WHERE p.tahun=? GROUP BY p.id ORDER BY p.nama ASC", [$tahun]);
                $data['recipients'] = db_all("SELECT r.*, p.nama AS program FROM bansos_recipients r JOIN bansos_programs p ON p.id=r.program_id WHERE p.tahun=? ORDER BY p.nama, r.nama", [$tahun]);
                $data['title'] = 'Data Bantuan Sosial';
                break;
            case 'stunting':
                $data['byRt'] = db_all("SELECT rt, rw, COUNT(*) total, SUM(status IN ('pendek','sangat_pendek','beresiko')) stunting FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY rt, rw ORDER BY rt, rw", [$tahun]);
                $data['byStatus'] = db_all("SELECT status, COUNT(*) total FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY status", [$tahun]);
                $data['title'] = 'Data Stunting Balita';
                break;
            default:
                $data['budgets'] = db_all("SELECT * FROM budget_transparencies WHERE deleted_at IS NULL AND year=? ORDER BY type,id", [$tahun]);
                $data['projects'] = db_all("SELECT * FROM development_projects WHERE deleted_at IS NULL AND year=? ORDER BY id DESC", [$tahun]);
                $data['title'] = 'Transparansi Anggaran Desa';
        }
        $this->view('transparansi_cetak', $data);
    }

    /** IDM publik */
    public function idm(): void {
        $rows = db_all("SELECT * FROM idm_data ORDER BY tahun DESC LIMIT 10");
        $latest = $rows[0] ?? null;
        $this->view('idm_public', [
            'pageTitle' => 'Indeks Desa Membangun (IDM) — ' . setting('village_name'),
            'rows' => $rows, 'latest' => $latest,
        ]);
    }

    /** Bansos publik — nama & alamat di-mask sederhana */
    public function bansos(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        $programs = db_all("SELECT p.*, COUNT(r.id) AS jml_penerima FROM bansos_programs p LEFT JOIN bansos_recipients r ON r.program_id=p.id WHERE p.tahun=? AND p.is_active=1 GROUP BY p.id ORDER BY p.nama ASC", [$tahun]);
        $recipientPg = paginate("SELECT r.id, r.nama, r.nik, r.alamat, r.rt, r.rw, r.keterangan,
                p.nama AS program
            FROM bansos_recipients r
            JOIN bansos_programs p ON p.id=r.program_id
            WHERE p.tahun=? AND p.is_active=1
            ORDER BY p.nama, r.nama", [$tahun], 25);
        $this->view('bansos_public', [
            'pageTitle' => 'Data Bantuan Sosial — ' . setting('village_name'),
            'tahun' => $tahun, 'programs' => $programs,
            'recipients' => $recipientPg['data'], 'recipientPg' => $recipientPg,
            'tahunList' => db_all("SELECT DISTINCT tahun FROM bansos_programs ORDER BY tahun DESC"),
        ]);
    }

    /** Stunting publik — agregat per RT saja (privasi) */
    public function stunting(): void {
        $tahun = (int) ($_GET['tahun'] ?? date('Y'));
        $byRt = db_all("SELECT rt, rw, COUNT(*) total, SUM(status IN ('pendek','sangat_pendek','beresiko')) stunting FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY rt, rw ORDER BY rt, rw", [$tahun]);
        $byStatus = db_all("SELECT status, COUNT(*) total FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY status", [$tahun]);
        $byMonth = db_all("SELECT DATE_FORMAT(tanggal_pemeriksaan, '%Y-%m') periode, COUNT(*) total, SUM(status IN ('pendek','sangat_pendek','beresiko')) stunting FROM stunting_data WHERE YEAR(tanggal_pemeriksaan)=? GROUP BY periode ORDER BY periode ASC", [$tahun]);
        $this->view('stunting_public', [
            'pageTitle' => 'Data Stunting Balita — ' . setting('village_name'),
            'tahun' => $tahun, 'byRt' => $byRt, 'byStatus' => $byStatus, 'byMonth' => $byMonth,
            'tahunList' => db_all("SELECT DISTINCT YEAR(tanggal_pemeriksaan) tahun FROM stunting_data ORDER BY tahun DESC"),
        ]);
    }

    public function index(): void {
        $tahun = (int) ($_GET['tahun'] ?? 0);
        $sumber = trim($_GET['sumber'] ?? '');
        $tahunList = db_all("SELECT DISTINCT year FROM budget_transparencies WHERE deleted_at IS NULL
            UNION SELECT DISTINCT year FROM development_projects WHERE deleted_at IS NULL ORDER BY year DESC");
        if (!$tahun && $tahunList) $tahun = (int) $tahunList[0]['year'];

        $bw = ["deleted_at IS NULL", "year = ?"]; $bp = [$tahun];
        if ($sumber !== '') { $bw[] = "source = ?"; $bp[] = $sumber; }
        $budgets = db_all("SELECT * FROM budget_transparencies WHERE " . implode(' AND ', $bw) . " ORDER BY type ASC, id ASC", $bp);

        $pw = ["deleted_at IS NULL", "year = ?"]; $pp = [$tahun];
        if ($sumber !== '') { $pw[] = "source = ?"; $pp[] = $sumber; }
        $projects = db_all("SELECT * FROM development_projects WHERE " . implode(' AND ', $pw) . " ORDER BY id DESC", $pp);

        $summary = ['pendapatan' => 0, 'belanja' => 0, 'realisasi_pendapatan' => 0, 'realisasi_belanja' => 0];
        foreach ($budgets as $b) {
            if ($b['type'] === 'pendapatan') { $summary['pendapatan'] += $b['budget_amount']; $summary['realisasi_pendapatan'] += $b['realization_amount']; }
            if ($b['type'] === 'belanja') { $summary['belanja'] += $b['budget_amount']; $summary['realisasi_belanja'] += $b['realization_amount']; }
        }
        $this->view('transparansi', [
            'pageTitle' => 'Transparansi Anggaran - ' . setting('village_name'),
            'tahun' => $tahun, 'sumber' => $sumber, 'tahunList' => $tahunList,
            'sumberList' => db_all("SELECT DISTINCT source FROM budget_transparencies WHERE deleted_at IS NULL AND source IS NOT NULL
                UNION SELECT DISTINCT source FROM development_projects WHERE deleted_at IS NULL AND source IS NOT NULL"),
            'budgets' => $budgets, 'projects' => $projects, 'summary' => $summary,
        ]);
    }
}
