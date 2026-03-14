<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/BlogPost.php';

class BlogController extends Controller {

    private $auth;
    private $blogModel;

    private const ALLOWED_TYPES  = ['super_admin', 'city_admin'];
    private const PER_PAGE       = 20;
    private const MAX_IMG_BYTES  = 5 * 1024 * 1024; // 5 MB
    private const IMG_EXTS       = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct() {
        $this->auth = new Auth();

        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }

        $cu = $this->auth->getCurrentUser();
        if (!in_array($cu['admin_type'], self::ALLOWED_TYPES)) {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }

        $this->blogModel = new BlogPost();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->blogModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $posts = $this->blogModel->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats = $this->blogModel->getStats();

        $this->loadView('blog/index', [
            'title'        => 'Blog / CMS',
            'posts'        => $posts,
            'stats'        => $stats,
            'filters'      => $filters,
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'totalCount'   => $totalCount,
            'perPage'      => $perPage,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('blog', 'Invalid post ID.'); return; }

        $post = $this->blogModel->findWithDetails($id);
        if (!$post) { $this->redirectWithError('blog', 'Post not found.'); return; }

        $this->loadView('blog/view', [
            'title'        => escape($post['title']),
            'post'         => $post,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $this->loadView('blog/add', [
            'title'        => 'New Blog Post',
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('blog', 'Invalid post ID.'); return; }

        $post = $this->blogModel->findWithDetails($id);
        if (!$post) { $this->redirectWithError('blog', 'Post not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $this->loadView('blog/edit', [
            'title'        => 'Edit: ' . escape($post['title']),
            'post'         => $post,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SHARED SAVE ──────────────────────────────────────────────────────────

    private function handleSave($postId) {
        $this->requireCSRF();
        $cu = $this->auth->getCurrentUser();

        $title   = trim($_POST['title']   ?? '');
        $content = trim($_POST['content'] ?? '');
        $status  = trim($_POST['status']  ?? 'draft');
        $customSlug = trim($_POST['slug'] ?? '');

        $redirect = $postId ? "blog/detail?id={$postId}" : 'blog/add';

        if (!$title) {
            $this->redirectWithError($redirect, 'Title is required.');
            return;
        }
        if (!$content) {
            $this->redirectWithError($redirect, 'Content is required.');
            return;
        }
        if (!in_array($status, ['draft', 'published', 'archived'])) {
            $status = 'draft';
        }

        // Slug
        $slug = $customSlug ?: $this->blogModel->generateSlug($title, $postId);
        if ($slug !== $customSlug && $customSlug) {
            // custom slug provided &mdash; validate and ensure uniqueness
            $slug = $this->blogModel->generateSlug($customSlug, $postId);
        }

        // Featured image upload
        $featuredImage = $postId ? ($this->blogModel->findWithDetails($postId)['featured_image'] ?? null) : null;
        if (!empty($_FILES['featured_image']['name'])) {
            $file = $_FILES['featured_image'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, self::IMG_EXTS)) {
                $this->redirectWithError($redirect, 'Invalid image type.'); return;
            }
            if ($file['size'] > self::MAX_IMG_BYTES) {
                $this->redirectWithError($redirect, 'Image exceeds 5 MB.'); return;
            }
            $uploadDir = API_BLOG_UPLOAD_DIR;
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $newName = 'blog_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                $this->redirectWithError($redirect, 'Image upload failed.'); return;
            }
            $featuredImage = 'uploads/blog/' . $newName;
        }

        $publishedAt = null;
        if ($status === 'published') {
            // Keep existing published_at when re-saving a published post
            if ($postId) {
                $existing = $this->blogModel->findWithDetails($postId);
                $publishedAt = $existing['published_at'] ?? date('Y-m-d H:i:s');
            } else {
                $publishedAt = date('Y-m-d H:i:s');
            }
        }

        $data = [
            'title'          => $title,
            'slug'           => $slug,
            'content'        => $content,
            'status'         => $status,
            'featured_image' => $featuredImage,
            'published_at'   => $publishedAt,
        ];

        if ($postId) {
            $this->blogModel->updatePost($postId, $data);
            logAudit('blog_post_updated', 'blog_posts', $postId);
            $_SESSION['success'] = 'Post updated.';
            $this->redirect("blog/detail?id={$postId}");
        } else {
            $data['author_id'] = $cu['admin_id'];
            $newId = $this->blogModel->createPost($data);
            if ($newId) {
                logAudit('blog_post_created', 'blog_posts', $newId);
                $_SESSION['success'] = 'Post created.';
                $this->redirect("blog/detail?id={$newId}");
            } else {
                $this->redirectWithError('blog/add', 'Failed to create post.');
            }
        }
    }

    // ─── PUBLISH ──────────────────────────────────────────────────────────────

    public function publish() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('blog', 'Invalid ID.'); return; }

        $this->blogModel->publish($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('blog_post_published', 'blog_posts', $id);
        $_SESSION['success'] = 'Post published.';
        $this->redirect("blog/detail?id={$id}");
    }

    // ─── ARCHIVE ──────────────────────────────────────────────────────────────

    public function archive() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('blog', 'Invalid ID.'); return; }

        $this->blogModel->setStatus($id, 'archived');
        $cu = $this->auth->getCurrentUser();
        logAudit('blog_post_archived', 'blog_posts', $id);
        $_SESSION['success'] = 'Post archived.';
        $this->redirect("blog/detail?id={$id}");
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('blog', 'Invalid ID.'); return; }

        $post = $this->blogModel->findWithDetails($id);
        if (!$post) { $this->redirectWithError('blog', 'Post not found.'); return; }

        $this->blogModel->deletePost($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('blog_post_deleted', 'blog_posts', $id);
        $_SESSION['success'] = 'Post deleted.';
        $this->redirect('blog');
    }
}
