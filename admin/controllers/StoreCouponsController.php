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
    // ─── DELETE (DISABLED) ───────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $_SESSION['error'] = 'Delete is disabled. Set coupon status to inactive instead.';
        $this->redirect('store-coupons');
    }

    // ─── ADD (Admin-Only Creation) ────────────────────────────────────────────

    public function add(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        // Load all active stores with their merchant info for the dropdown
        $db = Database::getInstance()->getConnection();
        $stores = $db->prepare(
            "SELECT s.id, s.store_name, s.store_logo, m.id AS merchant_id, m.business_name
             FROM stores s
             JOIN merchants m ON s.merchant_id = m.id
             WHERE s.status = 'active'
             ORDER BY m.business_name, s.store_name"
        );
        $stores->execute();
        $storeList = $stores->fetchAll();

        $this->loadView('store-coupons/add', [
            'title'        => 'Create Store Coupon',
            'stores'       => $storeList,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    private function handleSave(?int $id): void {
        $this->requireCSRF();
        $cu = $this->auth->getCurrentUser();

        // ── Validate required fields ──────────────────────────────────────────
        $title    = trim($_POST['title'] ?? '');
        $storeId  = (int)($_POST['store_id'] ?? 0);

        if ($title === '') {
            $this->redirectWithError('store-coupons/add', 'Title is required.');
            return;
        }
        if ($storeId <= 0) {
            $this->redirectWithError('store-coupons/add', 'Please select a store.');
            return;
        }

        $discountType  = $_POST['discount_type'] ?? '';
        $discountValue = (float)($_POST['discount_value'] ?? 0);
        if (!in_array($discountType, ['percentage', 'fixed'], true)) {
            $this->redirectWithError('store-coupons/add', 'Invalid discount type.');
            return;
        }
        if ($discountValue <= 0) {
            $this->redirectWithError('store-coupons/add', 'Discount value must be greater than zero.');
            return;
        }

        // ── Look up merchant_id from store ────────────────────────────────────
        $db = Database::getInstance()->getConnection();
        $storeStmt = $db->prepare("SELECT merchant_id FROM stores WHERE id = ? AND status = 'active' LIMIT 1");
        $storeStmt->execute([$storeId]);
        $storeRow = $storeStmt->fetch();
        if (!$storeRow) {
            $this->redirectWithError('store-coupons/add', 'Selected store not found.');
            return;
        }

        // ── Auto-generate unique coupon code ──────────────────────────────────
        do {
            $couponCode = 'SC-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            $checkStmt  = $db->prepare("SELECT id FROM store_coupons WHERE coupon_code = ? LIMIT 1");
            $checkStmt->execute([$couponCode]);
        } while ($checkStmt->fetch());

        // ── Build data array ──────────────────────────────────────────────────
        $assignmentType = in_array($_POST['assignment_type'] ?? '', ['auto_assign', 'merchant_request'], true)
            ? $_POST['assignment_type']
            : 'merchant_request';

        $totalQty = trim($_POST['total_quantity'] ?? '');
        $validFrom  = trim($_POST['valid_from']  ?? '');
        $validUntil = trim($_POST['valid_until'] ?? '');

        $data = [
            'title'               => sanitize($title),
            'description'         => sanitize(trim($_POST['description'] ?? '')) ?: null,
            'terms_conditions'    => sanitize(trim($_POST['terms_conditions'] ?? '')) ?: null,
            'created_by_admin_id' => (int)$cu['admin_id'],
            'merchant_id'         => (int)$storeRow['merchant_id'],
            'store_id'            => $storeId,
            'coupon_code'         => $couponCode,
            'discount_type'       => $discountType,
            'discount_value'      => $discountValue,
            'valid_from'          => $validFrom  !== '' ? $validFrom  : null,
            'valid_until'         => $validUntil !== '' ? $validUntil : null,
            'assignment_type'     => $assignmentType,
            'requires_acceptance' => isset($_POST['requires_acceptance']) ? 1 : 0,
            'total_quantity'      => $totalQty !== '' ? (int)$totalQty : null,
            'status'              => 'active',
        ];

        $newId = $this->storeCouponModel->insert($data);
        if (!$newId) {
            $this->redirectWithError('store-coupons/add', 'Failed to create store coupon. Please try again.');
            return;
        }

        logAudit('store_coupon_created', 'store_coupon', $newId);
        $_SESSION['success'] = "Store coupon '{$couponCode}' created successfully.";
        $this->redirect('store-coupons/detail?id=' . $newId);
    }

    // ─── D3: BULK ALLOTMENT APPROVAL QUEUE ──────────────────────────────────

    public function allotmentRequests(): void {
        $db = Database::getInstance()->getConnection();

        $stmt = $db->prepare(
            "SELECT cba.id,
                    cba.coupon_id,
                    cba.store_id,
                    cba.quantity,
                    cba.status,
                    cba.created_at,
                    cba.requested_by,
                    m.id AS merchant_id,
                    m.business_name AS merchant_name,
                    s.store_name,
                    c.title AS coupon_title,
                    c.coupon_code,
                    sc.id AS store_coupon_id
             FROM coupon_bulk_allotments cba
             JOIN stores s    ON s.id = cba.store_id
             JOIN merchants m ON m.id = s.merchant_id
             LEFT JOIN coupons c ON c.id = cba.coupon_id
             LEFT JOIN store_coupons sc
                ON sc.coupon_code = c.coupon_code
               AND sc.store_id = cba.store_id
             WHERE cba.status = 'pending'
             ORDER BY cba.created_at ASC"
        );
        $stmt->execute();
        $requests = $stmt->fetchAll();

        $this->loadView('store-coupons/allotment-requests', [
            'title'        => 'Store Coupon Allotment Requests',
            'requests'     => $requests,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    public function approveAllotment(): void {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithError('store-coupons/allotment-requests', 'Invalid request ID.');
            return;
        }

        $db = Database::getInstance()->getConnection();
        $cu = $this->auth->getCurrentUser();

        $stmt = $db->prepare(
            "SELECT cba.id, cba.store_id, cba.quantity, cba.status,
                    s.merchant_id
             FROM coupon_bulk_allotments cba
             JOIN stores s ON s.id = cba.store_id
             WHERE cba.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $req = $stmt->fetch();

        if (!$req || $req['status'] !== 'pending') {
            $this->redirectWithError('store-coupons/allotment-requests', 'Pending request not found.');
            return;
        }

        try {
            $db->beginTransaction();

            $db->prepare(
                "UPDATE coupon_bulk_allotments
                 SET status = 'approved', reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND status = 'pending'"
            )->execute([(int)$cu['id'], $id]);

            // Build initial allotment queue for active-card customers of the store.
            $custStmt = $db->prepare(
                "SELECT DISTINCT c.assigned_to_customer_id AS customer_id
                 FROM cards c
                 WHERE c.status = 'activated'
                   AND c.assigned_to_customer_id IS NOT NULL
                   AND (c.assigned_to_store_id = ? OR (c.assigned_to_store_id IS NULL AND c.assigned_to_merchant_id = ?))
                 ORDER BY c.activated_at DESC
                 LIMIT ?"
            );
            $custStmt->bindValue(1, (int)$req['store_id'], PDO::PARAM_INT);
            $custStmt->bindValue(2, (int)$req['merchant_id'], PDO::PARAM_INT);
            $custStmt->bindValue(3, (int)$req['quantity'], PDO::PARAM_INT);
            $custStmt->execute();
            $customers = $custStmt->fetchAll();

            if (!empty($customers)) {
                $ins = $db->prepare(
                    "INSERT INTO coupon_allotment_items (allotment_id, customer_id, allotted_at, status)
                     VALUES (?, ?, NOW(), 'pending')"
                );
                foreach ($customers as $row) {
                    $ins->execute([$id, (int)$row['customer_id']]);
                }
            }

            $db->commit();
            logAudit('store_coupon_allotment_approved', 'coupon_bulk_allotments', $id);
            $_SESSION['success'] = 'Allotment request approved.';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error'] = 'Failed to approve request: ' . $e->getMessage();
        }

        $this->redirect('store-coupons/allotment-requests');
    }

    public function rejectAllotment(): void {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        $note = trim($_POST['review_note'] ?? '');

        if ($id <= 0) {
            $this->redirectWithError('store-coupons/allotment-requests', 'Invalid request ID.');
            return;
        }

        $db = Database::getInstance()->getConnection();
        $cu = $this->auth->getCurrentUser();
        $ok = $db->prepare(
            "UPDATE coupon_bulk_allotments
             SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), review_note = ?, updated_at = NOW()
             WHERE id = ? AND status = 'pending'"
        )->execute([(int)$cu['id'], $note !== '' ? $note : null, $id]);

        if ($ok) {
            logAudit('store_coupon_allotment_rejected', 'coupon_bulk_allotments', $id);
            $_SESSION['success'] = 'Allotment request rejected.';
        } else {
            $_SESSION['error'] = 'Unable to reject request.';
        }

        $this->redirect('store-coupons/allotment-requests');
    }
}
