<?php

class BeritaController extends Controller {

    public array $actions = ['komentar'];

    public function index(): void {
        $where = ["n.deleted_at IS NULL", "n.status='published'", "n.published_at <= NOW()"];
        $params = [];
        $q = trim($_GET['q'] ?? '');
        if ($q !== '') { $where[] = "(n.title LIKE ? OR n.content LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
        $cat = trim($_GET['kategori'] ?? '');
        if ($cat !== '') { $where[] = "c.slug = ?"; $params[] = $cat; }
        $tag = trim($_GET['tag'] ?? '');
        if ($tag !== '') {
            $where[] = "n.id IN (SELECT ntr.news_id FROM news_tag_relations ntr JOIN news_tags t ON t.id=ntr.tag_id WHERE t.slug = ?)";
            $params[] = $tag;
        }
        $sql = "SELECT n.*, c.name AS category, c.slug AS category_slug FROM news n
                LEFT JOIN news_categories c ON c.id=n.category_id WHERE " . implode(' AND ', $where) . " ORDER BY n.published_at DESC";
        $pg = paginate($sql, $params, 9);
        $this->view('berita/index', [
            'pageTitle' => 'Berita Desa - ' . setting('village_name'),
            'pg' => $pg, 'q' => $q, 'cat' => $cat,
            'categories' => db_all("SELECT c.*, (SELECT COUNT(*) FROM news n WHERE n.category_id=c.id AND n.deleted_at IS NULL AND n.status='published' AND n.published_at <= NOW()) AS total FROM news_categories c ORDER BY c.name"),
            'popular' => db_all("SELECT id, title, slug, views, published_at, thumbnail FROM news WHERE deleted_at IS NULL AND status='published' AND published_at <= NOW() ORDER BY views DESC LIMIT 5"),
        ]);
    }

    public function detail(string $slug): void {
        $news = db_row("SELECT n.*, c.name AS category, c.slug AS category_slug, u.name AS author FROM news n
            LEFT JOIN news_categories c ON c.id=n.category_id LEFT JOIN users u ON u.id=n.created_by
            WHERE n.slug = ? AND n.deleted_at IS NULL AND n.status='published' AND n.published_at <= NOW()", [$slug]);
        if (!$news) { $this->notFound(); }
        db_exec("UPDATE news SET views = views + 1 WHERE id = ?", [$news['id']]);
        $this->view('berita/detail', [
            'pageTitle' => ($news['meta_title'] ?: $news['title']) . ' - ' . setting('village_name'),
            'metaDescription' => $news['meta_description'] ?: excerpt($news['content'], 160),
            'ogImage' => $news['thumbnail'] ? upload_url($news['thumbnail']) : '',
            'news' => $news,
            'tags' => db_all("SELECT t.* FROM news_tags t JOIN news_tag_relations r ON r.tag_id=t.id WHERE r.news_id = ?", [$news['id']]),
            'related' => db_all("SELECT id, title, slug, thumbnail, published_at FROM news
                WHERE deleted_at IS NULL AND status='published' AND published_at <= NOW()
                  AND id != ? AND (category_id = ? OR category_id IS NULL)
                ORDER BY published_at DESC LIMIT 3", [$news['id'], $news['category_id']]),
            'comments' => db_all("SELECT * FROM news_comments WHERE news_id = ? AND is_approved = 1 ORDER BY created_at DESC", [$news['id']]),
            'captcha' => Security::captcha('komentar'),
        ]);
    }

    public function komentar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('berita');
        verify_csrf();
        $newsId = (int) ($_POST['news_id'] ?? 0);
        $news = db_row("SELECT id, slug, allow_comments FROM news
            WHERE id = ? AND deleted_at IS NULL AND status='published' AND published_at <= NOW()", [$newsId]);
        if (!$news || !$news['allow_comments'] || setting('comments_enabled', '1') !== '1') redirect('berita');
        $back = 'berita/' . $news['slug'];
        $fail = static function (string $message) use ($back): void {
            remember_old_input($_POST);
            flash('danger', $message);
            redirect($back);
        };
        if (!Security::rateLimitPersistent('form:comment', 3, 300, (string) $newsId)) {
            $fail('Terlalu banyak komentar dikirim. Coba beberapa menit lagi.');
        }
        if (!Security::verifyCaptcha($_POST['captcha'] ?? '', $_POST['captcha_id'] ?? '', 'komentar')) {
            $fail('Jawaban captcha salah atau sudah kedaluwarsa.');
        }
        $name = trim((string) ($_POST['name'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        if (mb_strlen($name) < 2 || mb_strlen($content) < 3) $fail('Nama dan komentar wajib diisi.');
        if (!valid_email_or_empty($email)) $fail('Alamat email tidak valid.');
        db_insert('news_comments', [
            'news_id' => $newsId, 'name' => mb_substr($name, 0, 100),
            'email' => $email !== '' ? mb_substr($email, 0, 150) : null,
            'content' => mb_substr($content, 0, 1000),
            'is_approved' => setting('comment_moderation', '1') === '1' ? 0 : 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        clear_old_input();
        flash('success', setting('comment_moderation', '1') === '1' ? 'Komentar terkirim dan menunggu moderasi admin.' : 'Komentar berhasil ditampilkan.');
        redirect('berita/' . $news['slug']);
    }
}
