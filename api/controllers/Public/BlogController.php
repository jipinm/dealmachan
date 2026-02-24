<?php
/**
 * Public Blog Controller
 *
 * GET /api/public/blog             — paginated published posts list
 * GET /api/public/blog/:slug       — single post by slug
 */
class PublicBlogController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/public/blog ──────────────────────────────────────────────────
    public function index(array $query): never {
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = 12;
        $offset  = ($page - 1) * $perPage;

        $tagId   = !empty($query['tag_id']) ? (int)$query['tag_id'] : null;
        $search  = !empty($query['q'])      ? '%' . trim($query['q']) . '%' : null;

        $where  = ["b.status = 'published'", "b.published_at <= NOW()"];
        $params = [];

        if ($tagId) {
            $where[]  = "EXISTS (SELECT 1 FROM blog_tags bt WHERE bt.blog_id = b.id AND bt.tag_id = ?)";
            $params[] = $tagId;
        }
        if ($search) {
            $where[]  = "(b.title LIKE ? OR b.content LIKE ?)";
            $params[] = $search;
            $params[] = $search;
        }

        $whereSQL = implode(' AND ', $where);

        $countRow = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM blog_posts b WHERE {$whereSQL}",
            $params
        );
        $total = (int)($countRow['cnt'] ?? 0);

        $posts = $this->db->query(
            "SELECT b.id, b.title, b.slug, b.featured_image, b.published_at,
                    LEFT(REGEXP_REPLACE(b.content, '<[^>]+>', ''), 200) AS excerpt
             FROM blog_posts b
             WHERE {$whereSQL}
             ORDER BY b.published_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        Response::success($posts, 'OK', 200, [
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int)ceil($total / $perPage),
        ]);
    }

    // ── GET /api/public/blog/:slug ────────────────────────────────────────────
    public function show(string $slug): never {
        $post = $this->db->queryOne(
            "SELECT b.id, b.title, b.slug, b.content, b.featured_image, b.published_at,
                    LEFT(REGEXP_REPLACE(b.content, '<[^>]+>', ''), 200) AS excerpt
             FROM blog_posts b
             WHERE b.slug = ? AND b.status = 'published' AND b.published_at <= NOW()",
            [$slug]
        );

        if (!$post) Response::notFound('Blog post not found.');

        // Related posts (same category / tags)
        $related = $this->db->query(
            "SELECT b.id, b.title, b.slug, b.featured_image, b.published_at
             FROM blog_posts b
             WHERE b.status = 'published' AND b.published_at <= NOW() AND b.id != ?
             ORDER BY b.published_at DESC
             LIMIT 4",
            [$post['id']]
        );

        Response::success(array_merge($post, ['related' => $related]));
    }
}
