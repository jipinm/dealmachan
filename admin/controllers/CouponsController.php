<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Coupon.php';
require_once MODEL_PATH . '/Merchant.php';
require_once MODEL_PATH . '/Store.php';
require_once MODEL_PATH . '/Tag.php';
require_once MODEL_PATH . '/City.php';

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

        // Ensure junction table exists before any query runs
        $db = Database::getInstance()->getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS `coupon_stores` (
            `coupon_id` int(10) unsigned NOT NULL,
            `store_id`  int(10) unsigned NOT NULL,
            PRIMARY KEY (`coupon_id`,`store_id`),
            KEY `idx_cs_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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

        $db = Database::getInstance()->getConnection();
        $stmtViewCats = $db->prepare(
            "SELECT c.name AS category_name, sc.name AS sub_category_name
             FROM coupon_categories cc
             JOIN categories c ON c.id = cc.category_id
             LEFT JOIN sub_categories sc ON sc.id = cc.sub_category_id
             WHERE cc.coupon_id = ?
             ORDER BY c.name, sc.name"
        );
        $stmtViewCats->execute([$id]);
        $couponCategoryDetails = $stmtViewCats->fetchAll(PDO::FETCH_ASSOC);

        $this->loadView('coupons/view', [
            'title'                 => 'Coupon &mdash; ' . escape($coupon['title']),
            'coupon'                => $coupon,
            'redemptions'           => $redemptions,
            'gifts'                 => $gifts,
            'tags'                  => $tags,
            'couponCategoryDetails' => $couponCategoryDetails,
            'current_user'          => $this->auth->getCurrentUser(),
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
        $db            = Database::getInstance()->getConnection();
        $categories    = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $cityModel     = new City();
        $this->loadView('coupons/add', [
            'title'        => 'Create Coupon',
            'merchants'    => $merchantModel->getAllWithDetails(['limit' => 200]),
            'tags'         => $tagModel->getAllWithDetails(),
            'categories'   => $categories,
            'cities'       => $cityModel->getActive(),
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
        $db            = Database::getInstance()->getConnection();
        $categories    = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $selectedCategoryIds = array_column(
            $db->query("SELECT category_id FROM coupon_categories WHERE coupon_id = {$id}")->fetchAll(PDO::FETCH_ASSOC),
            'category_id'
        );
        $selectedSubCategoryIds = array_values(array_filter(array_column(
            $db->query("SELECT sub_category_id FROM coupon_categories WHERE coupon_id = {$id} AND sub_category_id IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC),
            'sub_category_id'
        )));
        $subCategories = [];
        if (!empty($selectedCategoryIds)) {
            $in = implode(',', array_map('intval', $selectedCategoryIds));
            $subCategories = $db->query("SELECT id, name, category_id FROM sub_categories WHERE category_id IN ({$in}) AND status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        }
        $cityModel       = new City();
        $selectedCityIds = array_column(
            $db->query("SELECT city_id FROM coupon_city_targets WHERE coupon_id = {$id}")->fetchAll(PDO::FETCH_ASSOC),
            'city_id'
        );
        $stmtLoc = $db->prepare(
            "SELECT cl.city_id, cl.area_id, cl.location_id, ci.city_name,
                    a.area_name, l.location_name
             FROM coupon_locations cl
             JOIN cities ci ON ci.id = cl.city_id
             LEFT JOIN areas a ON a.id = cl.area_id
             LEFT JOIN locations l ON l.id = cl.location_id
             WHERE cl.coupon_id = ?"
        );
        $stmtLoc->execute([$id]);
        $existingLocations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

        $stmtCS = $db->prepare("SELECT store_id FROM coupon_stores WHERE coupon_id = ?");
        $stmtCS->execute([$id]);
        $selectedStoreIds = array_column($stmtCS->fetchAll(PDO::FETCH_ASSOC), 'store_id');
        $this->loadView('coupons/edit', [
            'title'              => 'Edit Coupon &mdash; ' . escape($coupon['title']),
            'coupon'             => $coupon,
            'merchants'          => $merchantModel->getAllWithDetails(['limit' => 200]),
            'stores'             => $coupon['merchant_id'] ? $storeModel->getByMerchant($coupon['merchant_id']) : [],
            'tags'               => $tagModel->getAllWithDetails(),
            'selectedTags'       => $selectedTags,
            'categories'            => $categories,
            'selectedCategoryIds'   => $selectedCategoryIds,
            'selectedSubCategoryIds'=> $selectedSubCategoryIds,
            'subCategories'         => $subCategories,
            'cities'                => $cityModel->getActive(),
            'selectedCityIds'    => $selectedCityIds,
            'existingLocations'  => $existingLocations,
            'selectedStoreIds'   => $selectedStoreIds,
            'current_user'       => $this->auth->getCurrentUser(),
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
        $storeIds        = array_filter(array_map('intval', $_POST['store_ids'] ?? []));
        $validFrom       = sanitize($_POST['valid_from']      ?? '');
        $validUntil      = sanitize($_POST['valid_until']     ?? '');
        $usageLimit      = trim($_POST['usage_limit']         ?? '');
        $isAdminCoupon   = isset($_POST['is_admin_coupon']) ? 1 : 0;
        $approvalStatus  = sanitize($_POST['approval_status'] ?? 'pending');
        $status          = sanitize($_POST['status']          ?? 'active');
        $terms           = sanitize($_POST['terms_conditions'] ?? '');
        $tagIds          = $_POST['tags'] ?? [];
        $categoryIds     = array_filter(array_map('intval', $_POST['category_ids'] ?? []));
        $subCategoryIds  = array_filter(array_map('intval', $_POST['sub_category_ids'] ?? []));
        $cityIds         = array_filter(array_map('intval', $_POST['city_ids'] ?? []));
        // BOGO / Addon
        $bogoBuyQty      = in_array($discountType, ['bogo']) ? max(1, (int)($_POST['bogo_buy_quantity'] ?? 0)) : null;
        $bogoGetQty      = in_array($discountType, ['bogo']) ? max(1, (int)($_POST['bogo_get_quantity'] ?? 0)) : null;
        $addonDesc       = $discountType === 'addon' ? sanitize($_POST['addon_item_description'] ?? '') : null;
        if ($discountType === 'bogo' || $discountType === 'addon') {
            $discountValue = 0;
        }

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

        // ── Banner image upload ──
        $bannerImage = null;
        // Option 1: path already uploaded via AJAX preview upload
        $preloaded = trim($_POST['banner_image_path'] ?? '');
        if ($preloaded !== '') {
            // Validate format and existence to prevent path traversal
            if (preg_match('#^uploads/coupon-banners/coupon_\d+_[0-9a-f]+\.[a-z]{3,4}$#', $preloaded)
                && file_exists(API_UPLOAD_DIR . '/coupon-banners/' . basename($preloaded))) {
                $bannerImage = $preloaded;
            }
        }
        // Option 2: file submitted directly with the form (fallback)
        if ($bannerImage === null && !empty($_FILES['banner_image']['name'])) {
            $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $ext         = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            $mimeType    = mime_content_type($_FILES['banner_image']['tmp_name']);

            if (!in_array($ext, $allowedExt) || !in_array($mimeType, $allowedMime)) {
                $_SESSION['error'] = 'Banner image must be a valid JPG, PNG, GIF, or WebP file.';
                $this->redirect($redirect); return;
            }
            if ($_FILES['banner_image']['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = 'Banner image must be under 2 MB.';
                $this->redirect($redirect); return;
            }

            $uploadDir = API_UPLOAD_DIR . '/coupon-banners/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename = 'coupon_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

            if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadDir . $filename)) {
                $_SESSION['error'] = 'Failed to upload banner image.';
                $this->redirect($redirect); return;
            }
            $bannerImage = 'uploads/coupon-banners/' . $filename;
        }

        $data = [
            'title'                 => $title,
            'description'           => $description     ?: null,
            'coupon_code'           => $couponCode,
            'discount_type'         => $discountType,
            'discount_value'        => $discountValue,
            'min_purchase_amount'   => $minPurchase !== '' ? (float)$minPurchase : null,
            'max_discount_amount'   => $maxDiscount !== '' ? (float)$maxDiscount : null,
            'merchant_id'           => $merchantId,
            'store_id'              => null,
            'valid_from'            => $validFrom   !== '' ? $validFrom  : null,
            'valid_until'           => $validUntil  !== '' ? $validUntil : null,
            'usage_limit'           => $usageLimit  !== '' ? (int)$usageLimit : null,
            'is_admin_coupon'       => $isAdminCoupon,
            'approval_status'       => $approvalStatus,
            'status'                => $status,
            'terms_conditions'      => $terms       !== '' ? $terms : null,
            'bogo_buy_quantity'     => $bogoBuyQty,
            'bogo_get_quantity'     => $bogoGetQty,
            'addon_item_description'=> $addonDesc   ?: null,
        ];

        // Only set banner_image if a new file was uploaded (preserve existing on edit)
        if ($bannerImage !== null) {
            $data['banner_image'] = $bannerImage;
        }

        try {
            $db = Database::getInstance()->getConnection();
            // Ensure multi-store junction table exists
            $db->exec("CREATE TABLE IF NOT EXISTS `coupon_stores` (
                `coupon_id` int(10) unsigned NOT NULL,
                `store_id`  int(10) unsigned NOT NULL,
                PRIMARY KEY (`coupon_id`,`store_id`),
                KEY `idx_cs_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            if ($couponId) {
                $this->couponModel->updateCoupon($couponId, $data);
                $this->couponModel->syncTags($couponId, $tagIds);
                // Sync category and city targets
                $db->exec("DELETE FROM coupon_categories WHERE coupon_id = {$couponId}");
                $db->exec("DELETE FROM coupon_city_targets WHERE coupon_id = {$couponId}");
                $savedId = $couponId;
            } else {
                $data['created_by']   = $cu['admin_id'];
                $data['usage_count']  = 0;
                $savedId = $this->couponModel->createCoupon($data);
                $this->couponModel->syncTags($savedId, $tagIds);
            }
            // Insert coupon_categories (one row per category, then set sub_category_id)
            foreach ($categoryIds as $catId) {
                $db->prepare("INSERT IGNORE INTO coupon_categories (coupon_id, category_id) VALUES (?, ?)")
                    ->execute([$savedId, $catId]);
            }
            // Update sub-category for each selected sub-category
            if (!empty($subCategoryIds)) {
                $updSubCat = $db->prepare(
                    "UPDATE coupon_categories
                     SET sub_category_id = ?
                     WHERE coupon_id = ? AND category_id = (SELECT category_id FROM sub_categories WHERE id = ? LIMIT 1)"
                );
                foreach ($subCategoryIds as $subCatId) {
                    $updSubCat->execute([$subCatId, $savedId, $subCatId]);
                }
            }
            // Insert coupon_city_targets
            foreach ($cityIds as $cityId) {
                $db->prepare("INSERT IGNORE INTO coupon_city_targets (coupon_id, city_id, set_by) VALUES (?, ?, ?)")
                    ->execute([$savedId, $cityId, $cu['admin_id']]);
            }
            // Sync coupon_stores (multi-store junction table)
            $db->prepare("DELETE FROM coupon_stores WHERE coupon_id = ?")->execute([$savedId]);
            if (!empty($storeIds)) {
                $insStore = $db->prepare("INSERT IGNORE INTO coupon_stores (coupon_id, store_id) VALUES (?, ?)");
                foreach ($storeIds as $sid) { $insStore->execute([$savedId, $sid]); }
            }
            // Sync coupon_locations (fine-grained area/location targeting)
            $locationCityIds = array_filter(array_map('intval', $_POST['location_city_ids'] ?? []));
            $locationAreaIds = $_POST['location_area_ids']     ?? [];
            $locationLocIds  = $_POST['location_location_ids'] ?? [];
            $db->prepare("DELETE FROM coupon_locations WHERE coupon_id = ?")->execute([$savedId]);
            if (!empty($locationCityIds)) {
                $insLoc = $db->prepare("INSERT IGNORE INTO coupon_locations (coupon_id, city_id, area_id, location_id) VALUES (?, ?, ?, ?)");
                foreach ($locationCityIds as $i => $lCityId) {
                    $insLoc->execute([
                        $savedId,
                        $lCityId,
                        !empty($locationAreaIds[$i]) ? (int)$locationAreaIds[$i] : null,
                        !empty($locationLocIds[$i])  ? (int)$locationLocIds[$i]  : null,
                    ]);
                }
            } elseif (!empty($storeIds)) {
                // Auto-populate from first selected store's location
                $storeCity = $db->prepare("SELECT city_id, area_id, location_id FROM stores WHERE id = ?");
                $storeCity->execute([reset($storeIds)]);
                $sc = $storeCity->fetch(PDO::FETCH_ASSOC);
                if ($sc && $sc['city_id']) {
                    $db->prepare("INSERT IGNORE INTO coupon_locations (coupon_id, city_id, area_id, location_id) VALUES (?, ?, ?, ?)")
                        ->execute([$savedId, $sc['city_id'], $sc['area_id'], $sc['location_id']]);
                }
            }
            if ($couponId) {
                logAudit('coupon_updated', 'coupon', $couponId);
                $_SESSION['success'] = "Coupon '{$title}' updated.";
                $this->redirect("coupons/detail?id={$couponId}");
            } else {
                logAudit('coupon_created', 'coupon', $savedId);
                $_SESSION['success'] = "Coupon '{$title}' created.";
                $this->redirect("coupons/detail?id={$savedId}");
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
            logAudit('coupon_deleted', 'coupon', $id);
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
        $this->couponModel->approve($id, $cu['admin_id']);
        logAudit('coupon_approved', 'coupon', $id);
        $_SESSION['success'] = 'Coupon approved.';
        $this->redirect($_POST['redirect'] ?? "coupons/detail?id={$id}");
    }

    public function reject() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('coupons', 'Invalid coupon ID.'); return; }

        $cu = $this->auth->getCurrentUser();
        $this->couponModel->reject($id, $cu['admin_id']);
        logAudit('coupon_rejected', 'coupon', $id);
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

    // ─── AJAX: upload coupon banner (immediate, returns API URL for preview) ──

    public function uploadBanner() {
        if (!$this->auth->isLoggedIn()) {
            $this->json(['success' => false, 'error' => 'Unauthorized'], 401);
        }
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        if (empty($_FILES['banner_image']['name'])) {
            $this->json(['success' => false, 'error' => 'No file provided'], 400);
        }

        $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $ext         = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        $mimeType    = mime_content_type($_FILES['banner_image']['tmp_name']);

        if (!in_array($ext, $allowedExt) || !in_array($mimeType, $allowedMime)) {
            $this->json(['success' => false, 'error' => 'Invalid file type. Use JPG, PNG, GIF, or WebP.']);
        }
        if ($_FILES['banner_image']['size'] > 2 * 1024 * 1024) {
            $this->json(['success' => false, 'error' => 'File must be under 2 MB.']);
        }

        $uploadDir = API_UPLOAD_DIR . '/coupon-banners/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $filename = 'coupon_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $uploadDir . $filename)) {
            $this->json(['success' => false, 'error' => 'Failed to save file.'], 500);
        }

        $path = 'uploads/coupon-banners/' . $filename;
        $url  = rtrim(API_URL, '/') . '/' . $path;
        $this->json(['success' => true, 'path' => $path, 'url' => $url]);
    }
}
