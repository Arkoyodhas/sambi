<?php

class PanduanController extends Controller {

    public array $actions = ['index'];

    public function index(): void {
        if (!Auth::user()) redirect('admin/login');
        $this->adminView('panduan', ['pageTitle' => 'Panduan Penggunaan']);
    }
}
