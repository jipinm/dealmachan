<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/CmsPage.php';

class CmsPagesController extends Controller {

    private $auth;
    private $cmsModel;

    public function __construct() {
        $this->auth     = new Auth();
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }
        $this->cmsModel = new CmsPage();
        // Only super_admin may manage CMS pages
        $cu = $this->auth->getCurrentUser();
        if ($cu['admin_type'] !== 'super_admin') {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            exit;
        }
    }

    // ── LIST ─────────────────────────────────────────────────────────────────
    public function index() {
        $current_user = $this->auth->getCurrentUser();
        $pages        = $this->cmsModel->getAll();
        $this->loadView('cms-pages/index', [
            'title'         => 'CMS Pages',
            'current_user'  => $current_user,
            'pages'         => $pages,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ── ADD FORM ─────────────────────────────────────────────────────────────
    public function add() {
        $current_user = $this->auth->getCurrentUser();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $slug   = trim($_POST['slug'] ?? '');
            $title  = trim($_POST['title'] ?? '');
            if (!$slug)  $errors[] = 'Slug is required.';
            if (!$title) $errors[] = 'Title is required.';
            if ($this->cmsModel->findBySlug($slug)) $errors[] = 'Slug already exists.';
            if (empty($errors)) {
                $this->cmsModel->create([
                    'slug'              => $slug,
                    'title'             => $title,
                    'content'           => $_POST['content'] ?? '',
                    'meta_description'  => trim($_POST['meta_description'] ?? ''),
                    'status'            => $_POST['status'] ?? 'draft',
                    'created_by_admin_id' => $current_user['id'],
                ]);
                $_SESSION['success'] = "Page «$title» created.";
                $this->redirect('cms-pages');
                return;
            }
            $this->loadView('cms-pages/edit', [
                'title'        => 'Add CMS Page',
                'current_user' => $current_user,
                'page'         => null,
                'errors'       => $errors,
                'post'         => $_POST,
            ]);
            return;
        }
        $this->loadView('cms-pages/edit', [
            'title'        => 'Add CMS Page',
            'current_user' => $current_user,
            'page'         => null,
            'errors'       => [],
            'post'         => [],
        ]);
    }

    // ── EDIT FORM ────────────────────────────────────────────────────────────
    public function edit() {
        $current_user = $this->auth->getCurrentUser();
        $id           = (int)($_GET['id'] ?? 0);
        $page         = $this->cmsModel->find($id);
        if (!$page) {
            $_SESSION['error'] = 'Page not found.'; $this->redirect('cms-pages'); return;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = [];
            $slug   = trim($_POST['slug'] ?? '');
            $title  = trim($_POST['title'] ?? '');
            if (!$slug)  $errors[] = 'Slug is required.';
            if (!$title) $errors[] = 'Title is required.';
            $existing = $this->cmsModel->findBySlug($slug);
            if ($existing && $existing['id'] != $id) $errors[] = 'Slug already used by another page.';
            if (empty($errors)) {
                $this->cmsModel->update($id, [
                    'slug'             => $slug,
                    'title'            => $title,
                    'content'          => $_POST['content'] ?? '',
                    'meta_description' => trim($_POST['meta_description'] ?? ''),
                    'status'           => $_POST['status'] ?? 'draft',
                ]);
                $_SESSION['success'] = "Page «$title» updated.";
                $this->redirect('cms-pages');
                return;
            }
            $page = array_merge($page, $_POST);
            $this->loadView('cms-pages/edit', compact('current_user','page','errors') + ['title'=>'Edit CMS Page','post'=>$_POST]);
            return;
        }
        $this->loadView('cms-pages/edit', [
            'title'        => 'Edit CMS Page',
            'current_user' => $current_user,
            'page'         => $page,
            'errors'       => [],
            'post'         => [],
        ]);
    }

    // ── DELETE ───────────────────────────────────────────────────────────────
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('cms-pages'); return; }
        $id = (int)($_POST['id'] ?? 0);
        $this->cmsModel->delete($id);
        $_SESSION['success'] = 'Page deleted.';
        $this->redirect('cms-pages');
    }
}
