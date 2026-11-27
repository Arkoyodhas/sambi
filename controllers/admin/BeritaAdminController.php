<?php

class BeritaAdminController extends Controller {

    public array $actions = ['index', 'create', 'store', 'edit', 'update', 'delete', 'restore', 'trash'];

    public function __construct() { Auth::requirePermission('kelola_berita'); }

    public function index(): void { $this->listView(false); }
    public function trash(): void { $this->listView(true); }

    private function listView(bool $trashed): void {
        $where = [$trashed ? 'n.deleted_at IS NOT NULL' : 'n.deleted_at IS NULL'];
        $params = [];
        $q = trim((string) ($_GET['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(n.title LIKE ? OR n.excerpt LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if (!empty($_GET['status']) && in_array($_GET['status'], ['draft', 'published', 'scheduled'], true)) {
            $where[] = 'n.status = ?';
            $params[] = $_GET['status'];
        }
        $sql = "SELECT n.*, c.name AS category
                FROM news n
                LEFT JOIN news_categories c ON c.id=n.category_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY n.id DESC";
        $pg = paginate($sql, $params, 15);
        $this->adminView('berita_list', [
            'pageTitle' => ($trashed ? 'Sampah ' : '') . 'Berita',
            'pg' => $pg,
            'trashed' => $trashed,
        ]);
    }

    public function create(): void { $this->form(null); }

    public function edit($id = null): void {
        $n = db_row("SELECT * FROM news WHERE id = ?", [(int) $id]);
        if (!$n) {
            flash('warning', 'Berita yang akan diedit tidak ditemukan.');
            redirect('admin/berita');
        }
        $this->form($n);
    }

    private function form(?array $n): void {
        $tags = '';
        if ($n) {
            $tagRows = db_all(
                "SELECT t.name FROM news_tags t
                 JOIN news_tag_relations r ON r.tag_id=t.id
                 WHERE r.news_id = ?
                 ORDER BY t.name",
                [$n['id']]
            );
            $tags = implode(', ', array_column($tagRows, 'name'));
        }
        $this->adminView('berita_form', [
            'pageTitle' => ($n ? 'Edit' : 'Tulis') . ' Berita',
            'n' => $n,
            'tags' => $tags,
            'categories' => db_all("SELECT * FROM news_categories ORDER BY name"),
        ]);
    }

    public function store(): void { $this->save(null); }
    public function update($id = null): void { $this->save((int) $id); }

    private function save(?int $id): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/berita');
        verify_csrf();

        $isNew = $id === null;
        $old = $id ? db_row("SELECT * FROM news WHERE id = ?", [$id]) : null;
        if (!$isNew && !$old) {
            flash('warning', 'Berita yang akan diperbarui tidak ditemukan.');
            redirect('admin/berita');
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $content = Security::cleanHtml((string) ($_POST['content'] ?? ''));
        $plainContent = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $hasMedia = preg_match('/<(?:img|iframe|table)\b/i', $content) === 1;
        $errors = [];

        if ($title === '') $errors[] = 'Judul wajib diisi.';
        if ($plainContent === '' && !$hasMedia) $errors[] = 'Isi berita wajib diisi.';

        $categoryId = (int) ($_POST['category_id'] ?? 0);
        if ($categoryId > 0 && !db_val("SELECT id FROM news_categories WHERE id = ? LIMIT 1", [$categoryId])) {
            $errors[] = 'Kategori berita tidak valid.';
        }

        $status = in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled'], true)
            ? (string) $_POST['status']
            : 'draft';
        $publishedRaw = trim((string) ($_POST['published_at'] ?? ''));
        $publishedAt = null;
        if ($publishedRaw !== '') {
            $timestamp = strtotime($publishedRaw);
            if ($timestamp === false) {
                $errors[] = 'Tanggal publikasi tidak valid.';
            } else {
                $publishedAt = date('Y-m-d H:i:s', $timestamp);
            }
        }
        if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');
        if ($status === 'scheduled' && !$publishedAt) {
            $errors[] = 'Tanggal publikasi wajib diisi untuk berita terjadwal.';
        }

        $uploadedThumbnail = null;
        if (!empty($_FILES['thumbnail']['name'])
            || ((int) ($_FILES['thumbnail']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
            $res = Upload::image($_FILES['thumbnail'], 'news');
            if (is_array($res)) {
                $errors[] = (string) ($res['error'] ?? 'Thumbnail gagal diunggah.');
            } else {
                $uploadedThumbnail = $res;
            }
        }

        if ($errors) {
            if ($uploadedThumbnail) Upload::delete($uploadedThumbnail);
            $_SESSION['form_old'] = $_POST;
            flash('danger', implode(' ', array_unique($errors)));
            redirect('admin/berita' . ($id ? "/edit/$id" : '/create'));
        }

        $data = [
            'title' => mb_substr($title, 0, 255, 'UTF-8'),
            'slug' => unique_slug('news', trim((string) ($_POST['slug'] ?? '')) ?: $title, $id ?? 0),
            'category_id' => $categoryId > 0 ? $categoryId : null,
            'excerpt' => mb_substr(trim((string) ($_POST['excerpt'] ?? '')), 0, 500, 'UTF-8') ?: null,
            'content' => $content,
            'meta_title' => mb_substr(trim((string) ($_POST['meta_title'] ?? '')), 0, 255, 'UTF-8') ?: null,
            'meta_description' => mb_substr(trim((string) ($_POST['meta_description'] ?? '')), 0, 300, 'UTF-8') ?: null,
            'status' => $status,
            'published_at' => $publishedAt,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($uploadedThumbnail) $data['thumbnail'] = $uploadedThumbnail;

        $tags = [];
        foreach (explode(',', (string) ($_POST['tags'] ?? '')) as $tagName) {
            $tagName = mb_substr(trim($tagName), 0, 80, 'UTF-8');
            if ($tagName === '') continue;
            $slug = slugify($tagName);
            if ($slug === '') continue;
            $tags[$slug] = $tagName;
            if (count($tags) >= 30) break;
        }

        $pdo = db();
        $savedId = (int) ($id ?? 0);
        try {
            $pdo->beginTransaction();

            if ($isNew) {
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['created_by'] = (int) (auth_user()['id'] ?? 0);
                $savedId = db_insert('news', $data);
            } else {
                $data['updated_by'] = (int) (auth_user()['id'] ?? 0);
                db_update('news', $data, 'id = ?', [$id]);
                $savedId = (int) $id;
            }

            db_exec("DELETE FROM news_tag_relations WHERE news_id = ?", [$savedId]);
            foreach ($tags as $slug => $tagName) {
                $tagId = db_val("SELECT id FROM news_tags WHERE slug = ? LIMIT 1", [$slug]);
                if (!$tagId) {
                    $tagId = db_insert('news_tags', [
                        'name' => $tagName,
                        'slug' => $slug,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                db_exec(
                    "INSERT IGNORE INTO news_tag_relations (news_id, tag_id) VALUES (?, ?)",
                    [$savedId, (int) $tagId]
                );
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            if ($uploadedThumbnail) Upload::delete($uploadedThumbnail);
            $_SESSION['form_old'] = $_POST;
            flash('danger', 'Berita gagal disimpan. Periksa data dan koneksi database, lalu coba kembali.');
            redirect('admin/berita' . ($id ? "/edit/$id" : '/create'));
        }

        if ($uploadedThumbnail && $old && !empty($old['thumbnail'])) {
            Upload::delete((string) $old['thumbnail']);
        }

        unset($_SESSION['form_old']);
        log_activity($isNew ? 'tambah' : 'edit', 'berita', ($isNew ? 'Tulis' : 'Edit') . " berita: $title");
        flash('success', $isNew ? 'Berita berhasil disimpan.' : 'Berita berhasil diperbarui.');

        if (($_POST['send_push'] ?? '0') === '1') {
            try {
                $saved = db_row("SELECT * FROM news WHERE id = ?", [$savedId]);
                $isLive = $saved
                    && $saved['status'] === 'published'
                    && !empty($saved['published_at'])
                    && strtotime((string) $saved['published_at']) <= time();
                if ($isLive) {
                    $body = $saved['excerpt'] ?: excerpt((string) ($saved['content'] ?? ''), 160);
                    $result = PushNotifier::send(
                        'Berita: ' . $saved['title'],
                        $body ?: 'Berita terbaru dari ' . setting('village_name', 'Pemerintah Desa'),
                        url('berita/' . $saved['slug']),
                        (int) (auth_user()['id'] ?? 0)
                    );
                    PushNotifier::flashResult($result);
                } else {
                    flash('warning', 'Berita tersimpan, tetapi push tidak dikirim karena belum dipublikasikan.');
                }
            } catch (Throwable $e) {
                flash('warning', 'Berita tersimpan, tetapi notifikasi push gagal dikirim.');
            }
        }

        redirect('admin/berita');
    }

    public function delete($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/berita');
        verify_csrf();

        $changed = db_update(
            'news',
            ['deleted_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            'id = ? AND deleted_at IS NULL',
            [(int) $id]
        );
        if ($changed) {
            log_activity('hapus', 'berita', "Hapus berita #$id");
            flash('success', 'Berita dipindahkan ke sampah.');
        } else {
            flash('warning', 'Berita tidak ditemukan atau sudah berada di sampah.');
        }
        redirect('admin/berita');
    }

    public function restore($id = null): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') redirect('admin/berita/trash');
        verify_csrf();

        $changed = db_update(
            'news',
            ['deleted_at' => null, 'updated_at' => date('Y-m-d H:i:s')],
            'id = ? AND deleted_at IS NOT NULL',
            [(int) $id]
        );
        flash($changed ? 'success' : 'warning', $changed ? 'Berita berhasil dipulihkan.' : 'Berita tidak ditemukan atau sudah aktif.');
        redirect('admin/berita/trash');
    }
}
