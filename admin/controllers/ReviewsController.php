<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Review.php';

class ReviewsController extends Controller {

    private $auth;
    private $reviewModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];
    private const PER_PAGE      = 20;

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

        $this->reviewModel = new Review();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'      => $_GET['status']      ?? '',
            'rating'      => !empty($_GET['rating']) ? (int)$_GET['rating'] : '',
            'merchant_id' => !empty($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : '',
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->reviewModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $reviews      = $this->reviewModel->getAllWithDetails($fetchFilters);
        $stats        = $this->reviewModel->getStats();
        $merchants    = $this->reviewModel->getMerchantsWithReviews();

        $this->loadView('reviews/index', [
            'title'       => 'Review Moderation',
            'reviews'     => $reviews,
            'stats'       => $stats,
            'merchants'   => $merchants,
            'filters'     => $filters,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages,
            'totalCount'  => $totalCount,
            'perPage'     => $perPage,
            'current_user'=> $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('reviews', 'Invalid review ID.'); return; }

        $review = $this->reviewModel->findWithDetails($id);
        if (!$review) { $this->redirectWithError('reviews', 'Review not found.'); return; }

        $this->loadView('reviews/view', [
            'title'       => 'Review #' . $review['id'] . ' &mdash; ' . escape($review['customer_name']),
            'review'      => $review,
            'current_user'=> $this->auth->getCurrentUser(),
        ]);
    }

    // ─── RESTORE (un-flag → back to approved) ───────────────────────────────

    public function restore() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'reviews');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid review ID.'); return; }

        $review = $this->reviewModel->find($id);
        if (!$review) { $this->redirectWithError($redirect, 'Review not found.'); return; }

        $this->reviewModel->restore($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('review_restored', 'reviews', $id);
        $_SESSION['success'] = 'Review restored and is now publicly visible.';
        $this->redirect($redirect);
    }

    // ─── FLAG (hide from public without deleting) ─────────────────────────────

    public function flag() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'reviews');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid review ID.'); return; }

        $review = $this->reviewModel->find($id);
        if (!$review) { $this->redirectWithError($redirect, 'Review not found.'); return; }

        $this->reviewModel->flag($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('review_flagged', 'reviews', $id);
        $_SESSION['success'] = 'Review flagged and hidden from public view.';
        $this->redirect($redirect);
    }

    // ─── BULK RESTORE ─────────────────────────────────────────────────────────

    public function bulkRestore() {
        $this->requireCSRF();
        $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));

        if (empty($ids)) {
            $_SESSION['error'] = 'No reviews selected.';
            $this->redirect('reviews');
            return;
        }

        $this->reviewModel->bulkUpdateStatus($ids, 'approved');
        $cu = $this->auth->getCurrentUser();
        logAudit('reviews_bulk_restored', 'reviews', implode(',', $ids));
        $_SESSION['success'] = count($ids) . ' review(s) restored to public.';
        $this->redirect('reviews');
    }

    // ─── BULK FLAG ────────────────────────────────────────────────────────────

    public function bulkFlag() {
        $this->requireCSRF();
        $ids = array_filter(array_map('intval', (array)($_POST['ids'] ?? [])));

        if (empty($ids)) {
            $_SESSION['error'] = 'No reviews selected.';
            $this->redirect('reviews');
            return;
        }

        $this->reviewModel->bulkUpdateStatus($ids, 'flagged');
        $cu = $this->auth->getCurrentUser();
        logAudit('reviews_bulk_flagged', 'reviews', implode(',', $ids));
        $_SESSION['success'] = count($ids) . ' review(s) flagged and hidden.';
        $this->redirect('reviews');
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'reviews');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid review ID.'); return; }

        $review = $this->reviewModel->find($id);
        if (!$review) { $this->redirectWithError($redirect, 'Review not found.'); return; }

        $this->reviewModel->deleteReview($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('review_deleted', 'reviews', $id);
        $_SESSION['success'] = 'Review permanently deleted.';
        $this->redirect('reviews');
    }
}
