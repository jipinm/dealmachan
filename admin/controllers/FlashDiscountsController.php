<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/FlashDiscount.php';
require_once MODEL_PATH . '/Merchant.php';

class FlashDiscountsController extends Controller {

    private $auth;
    private $flashDiscountModel;

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

        $this->flashDiscountModel = new FlashDiscount();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'merchant_id' => !empty($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : '',
            'status'      => $_GET['status']  ?? '',
            'expiry'      => $_GET['expiry']  ?? '',
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->flashDiscountModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters    = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $flashDiscounts  = $this->flashDiscountModel->getAllWithDetails($fetchFilters);
        $stats           = $this->flashDiscountModel->getStats();

        // Merchant list for filter dropdown
        $merchantModel = new Merchant();
        $merchants = $merchantModel->getAllWithDetails(['limit' => 200]);

        $this->loadView('flash-discounts/index', [
            'title'          => 'Flash Discount Management',
            'flashDiscounts' => $flashDiscounts,
            'stats'          => $stats,
            'merchants'      => $merchants,
            'filters'        => $filters,
            'currentPage'    => $currentPage,
            'totalPages'     => $totalPages,
            'totalCount'     => $totalCount,
            'perPage'        => $perPage,
            'current_user'   => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('flash-discounts', 'Invalid flash discount ID.'); return; }

        $flashDiscount = $this->flashDiscountModel->findWithDetails($id);
        if (!$flashDiscount) { $this->redirectWithError('flash-discounts', 'Flash discount not found.'); return; }

        $this->loadView('flash-discounts/view', [
            'title'         => 'Flash Discount — ' . escape($flashDiscount['title']),
            'flashDiscount' => $flashDiscount,
            'current_user'  => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'flash-discounts');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid flash discount ID.'); return; }

        $this->flashDiscountModel->toggleStatus($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('flash_discount_toggled', $id, 'flash_discount', $cu['id']);
        $_SESSION['success'] = 'Flash discount status updated.';
        $this->redirect($redirect);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('flash-discounts', 'Invalid flash discount ID.'); return; }

        $fd = $this->flashDiscountModel->findWithDetails($id);
        if (!$fd) { $this->redirectWithError('flash-discounts', 'Flash discount not found.'); return; }

        try {
            $this->flashDiscountModel->deleteFlashDiscount($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('flash_discount_deleted', $id, 'flash_discount', $cu['id']);
            $_SESSION['success'] = "Flash discount '{$fd['title']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete flash discount: ' . $e->getMessage();
        }
        $this->redirect('flash-discounts');
    }
}
