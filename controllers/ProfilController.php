<?php

class ProfilController extends Controller {

    public function index(): void {
        $sections = db_all("SELECT * FROM village_profiles ORDER BY sort_order ASC");
        $byKey = [];
        foreach ($sections as $s) $byKey[$s['section_key']] = $s;
        $this->view('profil', [
            'pageTitle' => 'Profil Desa - ' . setting('village_name'),
            'sections' => $sections,
            'byKey' => $byKey,
            'wilayah' => [
                'dusun' => db_val("SELECT COUNT(*) FROM regions WHERE type='dusun'"),
                'rw' => db_val("SELECT COUNT(*) FROM regions WHERE type='rw'"),
                'rt' => db_val("SELECT COUNT(*) FROM regions WHERE type='rt'"),
            ],
            'potentials' => db_all("SELECT * FROM village_potentials WHERE deleted_at IS NULL AND is_active=1 ORDER BY category, id DESC LIMIT 12"),
            'officials' => db_all("SELECT * FROM village_officials WHERE deleted_at IS NULL AND is_active=1 AND kategori='pemerintah' ORDER BY sort_order ASC LIMIT 6"),
        ]);
    }
}
