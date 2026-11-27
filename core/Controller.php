<?php

class Controller {

    /** Render view publik dengan layout header+footer */
    protected function view(string $name, array $data = []): void {
        Visitor::queue();
        $this->consumeOldInput();
        extract($data);
        require BASE_PATH . '/views/layouts/header.php';
        require BASE_PATH . '/views/' . $name . '.php';
        require BASE_PATH . '/views/layouts/footer.php';
    }

    /** Render view admin dengan layout admin */
    protected function adminView(string $name, array $data = []): void {
        extract($data);
        require BASE_PATH . '/views/layouts/admin_header.php';
        require BASE_PATH . '/views/admin/' . $name . '.php';
        require BASE_PATH . '/views/layouts/admin_footer.php';
    }

    /** Render view tanpa layout (login, cetak) */
    protected function raw(string $name, array $data = []): void {
        $this->consumeOldInput();
        extract($data);
        require BASE_PATH . '/views/' . $name . '.php';
    }


    /** Pindahkan input lama dari session ke request saat ini agar tidak menetap. */
    private function consumeOldInput(): void {
        if (!array_key_exists('old_input', $GLOBALS)) {
            $GLOBALS['old_input'] = isset($_SESSION['old_input']) && is_array($_SESSION['old_input'])
                ? $_SESSION['old_input']
                : [];
            unset($_SESSION['old_input']);
        }
    }

    protected function json($data): void {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function notFound(): void {
        http_response_code(404);
        $this->view('404', ['pageTitle' => 'Halaman Tidak Ditemukan']);
        exit;
    }
}
