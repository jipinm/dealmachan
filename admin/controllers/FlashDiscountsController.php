<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/FlashDiscount.php';
require_once MODEL_PATH . '/Merchant.php';
require_once MODEL_PATH . '/Store.php';

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

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $merchantModel = new Merchant();
        $this->loadView('flash-discounts/add', [
            'title'        => 'Create Flash Discount',
            'merchants'    => $merchantModel->getAllWithDetails(['limit' => 200]),
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('flash-discounts', 'Invalid flash discount ID.'); return; }

        $fd = $this->flashDiscountModel->findWithDetails($id);
        if (!$fd) { $this->redirectWithError('flash-discounts', 'Flash discount not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $merchantModel = new Merchant();
        $storeModel    = new Store();

        $this->loadView('flash-discounts/edit', [
            'title'         => 'Edit Flash Discount — ' . escape($fd['title']),
            'flashDiscount' => $fd,
            'merchants'     => $merchantModel->getAllWithDetails(['limit' => 200]),
            'stores'        => $fd['merchant_id'] ? $storeModel->getByMerchant($fd['merchant_id']) : [],
            'current_user'  => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SAVE (shared add + edit) ─────────────────────────────────────────────

    private function handleSave($fdId) {
        $this->requireCSRF();

        $title          = sanitize($_POST['title']               ?? '');
        $description    = trim($_POST['description']             ?? '');
        $merchantId     = (int)($_POST['merchant_id']            ?? 0);
        $storeId        = !empty($_POST['store_id']) ? (int)$_POST['store_id'] : null;
        $discountPct    = (float)($_POST['discount_percentage']  ?? 0);
        $validFrom      = trim($_POST['valid_from']              ?? '');
        $validUntil     = trim($_POST['valid_until']             ?? '');
        $maxRedemptions = trim($_POST['max_redemptions']         ?? '');
        $status         = sanitize($_POST['status']              ?? 'active');

        $redirect = $fdId ? "flash-discounts/edit?id={$fdId}" : 'flash-discounts/add';
        $cu       = $this->auth->getCurrentUser();

        // ── Validation ──
        if (!$title) {
            $_SESSION['error'] = 'Title is required.';
            $this->redirect($redirect); return;
        }
        if (!$merchantId) {
            $_SESSION['error'] = 'Please select a merchant.';
            $this->redirect($redirect); return;
        }
        if ($discountPct <= 0 || $discountPct > 100) {
            $_SESSION['error'] = 'Discount percentage must be between 1 and 100.';
            $this->redirect($redirect); return;
        }
        if ($validFrom && $validUntil && strtotime($validUntil) <= strtotime($validFrom)) {
            $_SESSION['error'] = 'Valid Until must be after Valid From.';
            $this->redirect($redirect); return;
        }

        // ── Banner image upload ──
        $bannerImage = null;
        if (!empty($_FILES['banner_image']['name'])) {
            $uploadDir  = ROOT_PATH . '/public/uploads/flash-banners/';
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext        = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt)) {
                $_SESSION['error'] = 'Banner image must be JPG, PNG, GIF, or WebP.';
                $this->redirect($redirect); return;
            }
            if ($_FILES['banner_image']['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = 'Banner image must be under 2 MB.';
                $this->redirect($redirect); return;
            }

            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename    = 'fd_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $filename;

            if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                $_SESSION['error'] = 'Failed to upload banner image.';
                $this->redirect($redirect); return;
            }
            $bannerImage = 'uploads/flash-banners/' . $filename;
        }

        $data = [
            'merchant_id'          => $merchantId,
            'store_id'             => $storeId,
            'title'                => $title,
            'description'          => $description !== '' ? $description : null,
            'discount_percentage'  => $discountPct,
            'valid_from'           => $validFrom  !== '' ? $validFrom  : null,
            'valid_until'          => $validUntil !== '' ? $validUntil : null,
            'max_redemptions'      => $maxRedemptions !== '' ? (int)$maxRedemptions : null,
            'status'               => $status,
        ];

        // Only set banner_image if a new file was uploaded (preserve existing on edit)
        if ($bannerImage !== null) {
            $data['banner_image'] = $bannerImage;
        }

        if ($fdId) {
            $this->flashDiscountModel->updateFlashDiscount($fdId, $data);
            logAudit('flash_discount_updated', $fdId, 'flash_discounts', $cu['id']);
            $_SESSION['success'] = "Flash discount '{$title}' updated.";
            $this->redirect("flash-discounts/detail?id={$fdId}");
        } else {
            $newId = $this->flashDiscountModel->createFlashDiscount($data);
            logAudit('flash_discount_created', $newId, 'flash_discounts', $cu['id']);
            $_SESSION['success'] = "Flash discount '{$title}' created.";
            $this->redirect("flash-discounts/detail?id={$newId}");
        }
    }

    // ─── STORES JSON (AJAX) ───────────────────────────────────────────────────

    public function storesJson() {
        $merchantId = (int)($_GET['merchant_id'] ?? 0);
        if (!$merchantId) { $this->json([]); return; }
        $storeModel = new Store();
        $stores = $storeModel->getByMerchant($merchantId);
        $this->json(array_map(fn($s) => ['id' => $s['id'], 'name' => $s['store_name']], $stores));
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
            logAudit('flash_discount_deleted', $id, 'flash_discounts', $cu['id']);
            $_SESSION['success'] = "Flash discount '{$fd['title']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete flash discount: ' . $e->getMessage();
        }
        $this->redirect('flash-discounts');
    }
}
