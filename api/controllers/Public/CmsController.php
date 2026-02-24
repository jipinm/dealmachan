<?php
/**
 * Public CMS Page Controller
 *
 * GET /api/public/page/:slug — fetch a published CMS page by slug
 */
class CmsController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function show(string $slug): never
    {
        $slug = trim(strtolower($slug));
        if (!preg_match('/^[a-z0-9\-]{1,100}$/', $slug)) {
            Response::notFound('Page not found.');
        }

        $page = $this->db->queryOne(
            "SELECT id, slug, title, content, meta_description, updated_at
             FROM cms_pages
             WHERE slug = ? AND is_published = 1",
            [$slug]
        );

        if (!$page) Response::notFound('Page not found.');

        Response::success($page);
    }
}
