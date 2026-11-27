<?php

class StatistikController extends Controller {

    public function index(): void {
        $base = "FROM residents WHERE deleted_at IS NULL AND status_penduduk='hidup'";
        $umurGroups = [
            '0-5' => [0, 5], '6-12' => [6, 12], '13-17' => [13, 17], '18-25' => [18, 25],
            '26-40' => [26, 40], '41-60' => [41, 60], '60+' => [61, 200],
        ];
        $umurData = [];
        foreach ($umurGroups as $label => [$min, $max]) {
            $umurData[$label] = (int) db_val("SELECT COUNT(*) $base AND tanggal_lahir IS NOT NULL
                AND TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) BETWEEN ? AND ?", [$min, $max]);
        }
        $this->view('statistik', [
            'pageTitle' => 'Statistik Desa - ' . setting('village_name'),
            'total' => (int) db_val("SELECT COUNT(*) $base"),
            'kk' => (int) db_val("SELECT COUNT(*) FROM families WHERE deleted_at IS NULL"),
            'laki' => (int) db_val("SELECT COUNT(*) $base AND jenis_kelamin='L'"),
            'perempuan' => (int) db_val("SELECT COUNT(*) $base AND jenis_kelamin='P'"),
            'umur' => $umurData,
            'pendidikan' => db_all("SELECT COALESCE(NULLIF(pendidikan,''),'Tidak Diketahui') AS label, COUNT(*) AS total $base GROUP BY label ORDER BY total DESC"),
            'pekerjaan' => db_all("SELECT COALESCE(NULLIF(pekerjaan,''),'Tidak Diketahui') AS label, COUNT(*) AS total $base GROUP BY label ORDER BY total DESC LIMIT 10"),
            'agama' => db_all("SELECT COALESCE(NULLIF(agama,''),'Tidak Diketahui') AS label, COUNT(*) AS total $base GROUP BY label ORDER BY total DESC"),
            'kawin' => db_all("SELECT COALESCE(NULLIF(status_perkawinan,''),'Tidak Diketahui') AS label, COUNT(*) AS total $base GROUP BY label ORDER BY total DESC"),
            'dusun' => db_all("SELECT d.name AS label, COUNT(r.id) AS total FROM regions d
                LEFT JOIN residents r ON r.dusun_id = d.id AND r.deleted_at IS NULL AND r.status_penduduk='hidup'
                WHERE d.type='dusun' GROUP BY d.id, d.name ORDER BY d.name"),
        ]);
    }
}
