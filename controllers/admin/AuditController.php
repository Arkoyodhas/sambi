<?php

class AuditController extends Controller {

    public array $actions = ['index', 'import'];

    public function __construct() { Auth::requirePermission('lihat_audit'); }

    public function index(): void {
        $where = ['1=1']; $params = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = "(l.description LIKE ? OR l.action LIKE ? OR l.module LIKE ?)"; array_push($params, "%$q%", "%$q%", "%$q%"); }
        if (!empty($_GET['user'])) { $where[] = "l.user_id = ?"; $params[] = (int) $_GET['user']; }
        if (!empty($_GET['tanggal'])) { $where[] = "DATE(l.created_at) = ?"; $params[] = $_GET['tanggal']; }
        $sql = "SELECT l.*, u.name AS user_name FROM activity_logs l LEFT JOIN users u ON u.id=l.user_id
            WHERE " . implode(' AND ', $where) . " ORDER BY l.id DESC";
        $pg = paginate($sql, $params, 30);
        $this->adminView('audit_list', ['pageTitle' => 'Audit Log', 'pg' => $pg,
            'users' => db_all("SELECT id, name FROM users ORDER BY name")]);
    }

    public function import(): void {
        $pg = paginate("SELECT il.*, u.name AS user_name FROM import_logs il LEFT JOIN users u ON u.id=il.user_id ORDER BY il.id DESC", [], 20);
        $this->adminView('audit_import', ['pageTitle' => 'Log Import', 'pg' => $pg]);
    }
}
