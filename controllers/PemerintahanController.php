<?php

class PemerintahanController extends Controller {

    public function index(): void {
        $officials = db_all("SELECT * FROM village_officials WHERE deleted_at IS NULL AND is_active=1 ORDER BY sort_order ASC, id ASC");
        $groups = [];
        $labels = ['pemerintah' => 'Pemerintah Desa', 'bpd' => 'Badan Permusyawaratan Desa (BPD)', 'lpm' => 'LPM',
            'pkk' => 'Tim Penggerak PKK', 'karang_taruna' => 'Karang Taruna', 'rt_rw' => 'Ketua RT / RW',
            'adat' => 'Lembaga Adat', 'lainnya' => 'Lembaga Kemasyarakatan Lain'];
        foreach ($officials as $o) $groups[$o['kategori']][] = $o;
        $this->view('pemerintahan', [
            'pageTitle' => 'Pemerintahan Desa - ' . setting('village_name'),
            'groups' => $groups,
            'labels' => $labels,
            'rtrw' => db_all("SELECT * FROM regions WHERE type IN ('rw','rt') AND kepala IS NOT NULL ORDER BY FIELD(type,'rw','rt'), name"),
        ]);
    }
}
