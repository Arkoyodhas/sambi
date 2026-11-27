<?php

class HalamanController extends Controller {

    public function show(string $slug = ''): void {
        $slug = mb_substr(trim($slug), 0, 200, 'UTF-8');
        if ($slug === '') $this->notFound();

        $canPreview = Auth::user() && Auth::can('kelola_tampilan');
        $sql = "SELECT * FROM pages WHERE slug = ? AND deleted_at IS NULL";
        if (!$canPreview) $sql .= " AND is_active = 1";

        $page = db_row($sql, [$slug]);
        if (!$page) $this->notFound();

        $title = trim((string) ($page['title'] ?? 'Halaman'));
        $content = (string) ($page['content'] ?? '');
        $isPreview = $canPreview && empty($page['is_active']);

        $this->view('halaman', [
            'pageTitle' => (trim((string) ($page['meta_title'] ?? '')) ?: $title) . ' - ' . setting('village_name'),
            'metaDescription' => trim((string) ($page['meta_description'] ?? '')) ?: excerpt($content, 160),
            'metaRobots' => $isPreview ? 'noindex, nofollow' : 'index, follow',
            'page' => $page,
            'isPreview' => $isPreview,
        ]);
    }
}
