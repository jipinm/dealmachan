<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/GiftCoupon.php';

class GiftCouponsController extends Controller {

    private $auth;
    private $model;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin'];
    private const PER_PAGE      = 25;

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

        $this->model = new GiftCoupon();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'acceptance_status' => $_GET['acceptance_status'] ?? '',
            'customer_id'       => (int)($_GET['customer_id'] ?? 0) ?: '',
            'coupon_id'         => (int)($_GET['coupon_id']   ?? 0) ?: '',
            'date_from'         => $_GET['date_from'] ?? '',
            'date_to'           => $_GET['date_to']   ?? '',
            'search'            => trim($_GET['search'] ?? ''),
        ];

        if (isset($_GET['expiring_soon'])) {
            $filters['expiring_soon'] = true;
        }

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->model->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $gifts   = $this->model->getAllWithDetails(array_merge($filters, ['limit' => $perPage, 'offset' => $offset]));
        $stats   = $this->model->getStats();

        $this->loadView('gift-coupons/index', [
            'title'       => 'Gift Coupon Management',
            'gifts'       => $gifts,
            'stats'       => $stats,
            'filters'     => $filters,
            'totalCount'  => $totalCount,
            'totalPages'  => $totalPages,
            'currentPage' => $currentPage,
            'perPage'     => $perPage,
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id   = (int)($_GET['id'] ?? 0);
        $gift = $this->model->findWithDetails($id);

        if (!$gift) {
            $_SESSION['error'] = 'Gift coupon not found.';
            $this->redirect('gift-coupons');
            return;
        }

        $this->loadView('gift-coupons/view', [
            'title' => 'Gift Coupon #' . $id,
            'gift'  => $gift,
        ]);
    }

    // ─── ADD FORM ─────────────────────────────────────────────────────────────

    public function add() {
        $coupons   = $this->model->getActiveCoupons();
        $customers = $this->model->getCustomerList();

        $this->loadView('gift-coupons/add', [
            'title'     => 'Gift a Coupon',
            'coupons'   => $coupons,
            'customers' => $customers,
            'errors'    => [],
            'old'       => [],
        ]);
    }

    // ─── SAVE ─────────────────────────────────────────────────────────────────

    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gift-coupons');
            return;
        }
        $this->requireCSRF();

        $cu = $this->auth->getCurrentUser();

        $errors = [];
        $customerId         = (int)($_POST['customer_id']        ?? 0);
        $couponId           = (int)($_POST['coupon_id']          ?? 0);
        $requiresAcceptance = isset($_POST['requires_acceptance']) ? 1 : 0;
        $expiresAt          = trim($_POST['expires_at'] ?? '');

        if (!$customerId) $errors[] = 'Please select a customer.';
        if (!$couponId)   $errors[] = 'Please select a coupon.';
        if ($expiresAt && strtotime($expiresAt) <= time()) {
            $errors[] = 'Expiry date must be in the future.';
        }

        if ($errors) {
            $coupons   = $this->model->getActiveCoupons();
            $customers = $this->model->getCustomerList();
            $this->loadView('gift-coupons/add', [
                'title'     => 'Gift a Coupon',
                'coupons'   => $coupons,
                'customers' => $customers,
                'errors'    => $errors,
                'old'       => $_POST,
            ]);
            return;
        }

        $newId = $this->model->createGift([
            'admin_id'            => $cu['id'],
            'customer_id'         => $customerId,
            'coupon_id'           => $couponId,
            'requires_acceptance' => $requiresAcceptance,
            'expires_at'          => $expiresAt ?: null,
        ]);

        logAudit('gift_coupon_created', 'gift_coupons', $newId, [
            'admin_id'    => $cu['id'],
            'customer_id' => $customerId,
            'coupon_id'   => $couponId,
        ]);

        $_SESSION['success'] = 'Coupon gifted successfully.';
        $this->redirect('gift-coupons/detail?id=' . $newId);
    }

    // ─── REVOKE ───────────────────────────────────────────────────────────────

    public function revoke() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('gift-coupons');
            return;
        }
        $this->requireCSRF();

        $id   = (int)($_POST['id'] ?? 0);
        $gift = $this->model->findWithDetails($id);

        if (!$gift) {
            $_SESSION['error'] = 'Gift coupon not found.';
            $this->redirect('gift-coupons');
            return;
        }

        $ok = $this->model->revoke($id);

        if ($ok) {
            logAudit('gift_coupon_revoked', 'gift_coupons', $id, null, $gift);
            $_SESSION['success'] = 'Gift coupon revoked.';
        } else {
            $_SESSION['error'] = 'Cannot revoke — coupon has already been accepted.';
        }

        $redirect = $_POST['redirect'] ?? 'gift-coupons';
        $this->redirect($redirect);
    }
}
