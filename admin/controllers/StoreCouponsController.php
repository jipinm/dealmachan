<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/StoreCoupon.php';
require_once MODEL_PATH . '/Merchant.php';

class StoreCouponsController extends Controller {

    private $auth;
    private $storeCouponModel;

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

        $this->storeCouponModel = new StoreCoupon();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'merchant_id' => !empty($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : '',
            'status'      => $_GET['status']      ?? '',
            'is_gifted'   => $_GET['is_gifted']   ?? '',
            'is_redeemed' => $_GET['is_redeemed']  ?? '',
            'expiry'      => $_GET['expiry']       ?? '',
            'search'      => trim($_GET['search']  ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->storeCouponModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters   = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $storeCoupons   = $this->storeCouponModel->getAllWithDetails($fetchFilters);
        $stats          = $this->storeCouponModel->getStats();

        // Merchant list for filter dropdown
        $merchantModel = new Merchant();
        $merchants = $merchantModel->getAllWithDetails(['limit' => 200]);

        $this->loadView('store-coupons/index', [
            'title'        => 'Store Coupon Management',
            'storeCoupons' => $storeCoupons,
            'stats'        => $stats,
            'merchants'    => $merchants,
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
        if (!$id) { $this->redirectWithError('store-coupons', 'Invalid store coupon ID.'); return; }

        $storeCoupon = $this->storeCouponModel->findWithDetails($id);
        if (!$storeCoupon) { $this->redirectWithError('store-coupons', 'Store coupon not found.'); return; }

        $this->loadView('store-coupons/view', [
            'title'       => 'Store Coupon &mdash; ' . escape($storeCoupon['coupon_code']),
            'storeCoupon' => $storeCoupon,
            'current_user'=> $this->auth->getCurrentUser(),
        ]);
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'store-coupons');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid store coupon ID.'); return; }

        $this->storeCouponModel->toggleStatus($id);
        $cu = $this->auth->getCurrentUser();
        logAudit('store_coupon_toggled', 'store_coupon', $id);
        $_SESSION['success'] = 'Store coupon status updated.';
        $this->redirect($redirect);
    }
    // ─── REVOKE GIFT ──────────────────────────────────────────────────────

    public function revoke(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST only']);
            return;
        }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID']); return; }
        $ok = $this->storeCouponModel->revokeGift($id);
        if ($ok) {
            logAudit('store_coupon_gift_revoked', $id, 'store_coupon', $this->auth->getCurrentUser()['admin_id']);
        }
        echo json_encode([
            'success' => $ok,
            'error'   => $ok ? null : 'Cannot revoke: coupon is already redeemed or not gifted.',
        ]);
    }
    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('store-coupons', 'Invalid store coupon ID.'); return; }

        $sc = $this->storeCouponModel->findWithDetails($id);
        if (!$sc) { $this->redirectWithError('store-coupons', 'Store coupon not found.'); return; }

        try {
            $this->storeCouponModel->deleteStoreCoupon($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('store_coupon_deleted', 'store_coupon', $id);
            $_SESSION['success'] = "Store coupon '{$sc['coupon_code']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete store coupon: ' . $e->getMessage();
        }
        $this->redirect('store-coupons');
    }
}
