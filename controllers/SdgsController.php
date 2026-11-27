<?php

class SdgsController extends Controller {

    public function index(): void {
        $goals = db_all("SELECT * FROM sdgs_scores WHERE deleted_at IS NULL AND is_active=1 ORDER BY goal_number ASC");
        $avg = 0.0;
        if ($goals) $avg = round(array_sum(array_column($goals, 'score')) / count($goals), 2);
        $this->view('sdgs', [
            'pageTitle' => 'SDGs Desa - ' . setting('village_name'),
            'goals' => $goals,
            'avg' => $avg,
        ]);
    }
}
