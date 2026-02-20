<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Coupon.php';
require_once MODEL_PATH . '/Merchant.php';
require_once MODEL_PATH . '/Store.php';
require_once MODEL_PATH . '/Tag.php';

class CouponsController extends Controller {

    private $auth;
    private $couponModel;

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

        $this->couponModel = new Coupon();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'merchant_id'     => !empty($_GET['merchant_id'])    ? (int)$_GET['merchant_id']  : '',
            'status'          => $_GET['status']                 ?? '',
            'approval_status' => $_GET['approval_status']        ?? '',
            'discount_type'   => $_GET['discount_type']          ?? '',
            'is_admin_coupon' => $_GET['is_admin_coupon']         ?? '',
            'expiry'          => $_GET['expiry']                  ?? '',
            'search'          => trim($_GET['search']             ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->couponModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $coupons      = $this->couponModel->getAllWithDetails($fetchFilters);
        $stats        = $this->couponModel->getStats();

        // Merchant list for filter dropdown
        $merchantModel = new Merchant();
        $merchants = $merchantModel->getAllWithDetails(['limit' => 200]);

        $this->loadView('coupons/index', [
            'title'         => 'Coupon Management',
            'coupons'       => $coupons,
            'stats'         => $stats,
            'merchants'     => $merchants,
            'filters'       => $filters,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'totalCount'    => $totalCount,
            'perPage'       => $perPage,
            'current_user'  => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── DETAIL ───────────────────────────────────────────────────────────────

    public function detail() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $coupon = $this->couponModel->findWithDetails($id);
        if (!$coupon) { $this->redirectWithError('coupons', 'Coupon not found.'); return; }

        $redemptions = $this->couponModel->getRedemptions($id);
        $gifts       = $this->couponModel->getGiftHistory($id);
        $tags        = $this->couponModel->getTags($id);

        $this->loadView('coupons/view', [
            'title'        => 'Coupon — ' . escape($coupon['title']),
            'coupon'       => $coupon,
            'redemptions'  => $redemptions,
            'gifts'        => $gifts,
            'tags'         => $tags,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $merchantModel = new Merchant();
        $tagModel      = new Tag();
        $this->loadView('coupons/add', [
            'title'        => 'Create Coupon',
            'merchants'    => $merchantModel->getAllWithDetails(['limit' => 200]),
            'tags'         => $tagModel->getAllWithDetails(),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $coupon = $this->couponModel->findWithDetails($id);
        if (!$coupon) { $this->redirectWithError('coupons', 'Coupon not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $merchantModel = new Merchant();
        $storeModel    = new Store();
        $tagModel      = new Tag();
        $selectedTags  = array_column($this->couponModel->getTags($id), 'id');

        $this->loadView('coupons/edit', [
            'title'        => 'Edit Coupon — ' . escape($coupon['title']),
            'coupon'       => $coupon,
            'merchants'    => $merchantModel->getAllWithDetails(['limit' => 200]),
            'stores'       => $coupon['merchant_id'] ? $storeModel->getByMerchant($coupon['merchant_id']) : [],
            'tags'         => $tagModel->getAllWithDetails(),
            'selectedTags' => $selectedTags,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SAVE (shared) ────────────────────────────────────────────────────────

    private function handleSave($couponId) {
        $this->requireCSRF();

        $title           = sanitize($_POST['title']           ?? '');
        $description     = sanitize($_POST['description']     ?? '');
        $couponCode      = strtoupper(trim($_POST['coupon_code'] ?? ''));
        $discountType    = sanitize($_POST['discount_type']   ?? 'percentage');
        $discountValue   = (float)($_POST['discount_value']   ?? 0);
        $minPurchase     = trim($_POST['min_purchase_amount'] ?? '');
        $maxDiscount     = trim($_POST['max_discount_amount'] ?? '');
        $merchantId      = (int)($_POST['merchant_id']        ?? 0);
        $storeId         = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;
        $validFrom       = sanitize($_POST['valid_from']      ?? '');
        $validUntil      = sanitize($_POST['valid_until']     ?? '');
        $usageLimit      = trim($_POST['usage_limit']         ?? '');
        $isAdminCoupon   = isset($_POST['is_admin_coupon']) ? 1 : 0;
        $approvalStatus  = sanitize($_POST['approval_status'] ?? 'pending');
        $status          = sanitize($_POST['status']          ?? 'active');
        $terms           = sanitize($_POST['terms_conditions'] ?? '');
        $tagIds          = $_POST['tags'] ?? [];

        $redirect = $couponId ? "coupons/edit?id={$couponId}" : 'coupons/add';
        $cu = $this->auth->getCurrentUser();

        // ── Validation ──
        if (!$title) {
            $_SESSION['error'] = 'Coupon title is required.';
            $this->redirect($redirect); return;
        }
        if (!$couponCode) {
            $_SESSION['error'] = 'Coupon code is required.';
            $this->redirect($redirect); return;
        }
        if (!preg_match('/^[A-Z0-9_\-]+$/', $couponCode)) {
            $_SESSION['error'] = 'Coupon code may only contain letters, numbers, hyphens and underscores.';
            $this->redirect($redirect); return;
        }
        if ($discountValue <= 0) {
            $_SESSION['error'] = 'Discount value must be greater than zero.';
            $this->redirect($redirect); return;
        }
        if ($discountType === 'percentage' && $discountValue > 100) {
            $_SESSION['error'] = 'Percentage discount cannot exceed 100%.';
            $this->redirect($redirect); return;
        }
        if (!$merchantId) {
            $_SESSION['error'] = 'Please select a merchant.';
            $this->redirect($redirect); return;
        }
        if ($this->couponModel->codeExists($couponCode, $couponId)) {
            $_SESSION['error'] = "Coupon code '{$couponCode}' is already in use.";
            $this->redirect($redirect); return;
        }

        $data = [
            'title'              => $title,
            'description'        => $description     ?: null,
            'coupon_code'        => $couponCode,
            'discount_type'      => $discountType,
            'discount_value'     => $discountValue,
            'min_purchase_amount'=> $minPurchase !== '' ? (float)$minPurchase : null,
            'max_discount_amount'=> $maxDiscount !== '' ? (float)$maxDiscount : null,
            'merchant_id'        => $merchantId,
            'store_id'           => $storeId,
            'valid_from'         => $validFrom   !== '' ? $validFrom  : null,
            'valid_until'        => $validUntil  !== '' ? $validUntil : null,
            'usage_limit'        => $usageLimit  !== '' ? (int)$usageLimit : null,
            'is_admin_coupon'    => $isAdminCoupon,
            'approval_status'    => $approvalStatus,
            'status'             => $status,
            'terms_conditions'   => $terms       !== '' ? $terms : null,
        ];

        try {
            if ($couponId) {
                $this->couponModel->updateCoupon($couponId, $data);
                $this->couponModel->syncTags($couponId, $tagIds);
                logAudit('coupon_updated', $couponId, 'coupon', $cu['id']);
                $_SESSION['success'] = "Coupon '{$title}' updated.";
                $this->redirect("coupons/detail?id={$couponId}");
            } else {
                $data['created_by']   = $cu['id'];
                $data['usage_count']  = 0;
                $newId = $this->couponModel->createCoupon($data);
                $this->couponModel->syncTags($newId, $tagIds);
                logAudit('coupon_created', $newId, 'coupon', $cu['id']);
                $_SESSION['success'] = "Coupon '{$title}' created.";
                $this->redirect("coupons/detail?id={$newId}");
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to save coupon: ' . $e->getMessage();
            $this->redirect($redirect);
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $coupon = $this->couponModel->findWithDetails($id);
        if (!$coupon) { $this->redirectWithError('coupons', 'Coupon not found.'); return; }

        try {
            $this->couponModel->deleteCoupon($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('coupon_deleted', $id, 'coupon', $cu['id']);
            $_SESSION['success'] = "Coupon '{$coupon['title']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete coupon: ' . $e->getMessage();
        }
        $this->redirect('coupons');
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id       = (int)($_POST['id']       ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'coupons');
        if ($id) $this->couponModel->toggleStatus($id);
        $this->redirect($redirect);
    }

    // ─── APPROVE / REJECT ─────────────────────────────────────────────────────

    public function approve() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $cu = $this->auth->getCurrentUser();
        $this->couponModel->approve($id, $cu['id']);
        logAudit('coupon_approved', $id, 'coupon', $cu['id']);
        $_SESSION['success'] = 'Coupon approved.';
        $this->redirect($_POST['redirect'] ?? "coupons/detail?id={$id}");
    }

    public function reject() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $cu = $this->auth->getCurrentUser();
        $this->couponModel->reject($id, $cu['id']);
        logAudit('coupon_rejected', $id, 'coupon', $cu['id']);
        $_SESSION['success'] = 'Coupon rejected.';
        $this->redirect($_POST['redirect'] ?? "coupons/detail?id={$id}");
    }

    // ─── AJAX: stores for merchant ────────────────────────────────────────────

    public function storesJson() {
        $merchantId = (int)($_GET['merchant_id'] ?? 0);
        if (!$merchantId) { $this->json([]); return; }
        $storeModel = new Store();
        $this->json($storeModel->getByMerchant($merchantId));
    }
}
