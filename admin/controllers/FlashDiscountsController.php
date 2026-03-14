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

        // Ensure junction table exists before any query runs
        $db = Database::getInstance()->getConnection();
        $db->exec("CREATE TABLE IF NOT EXISTS `flash_discount_stores` (
            `flash_discount_id` int(10) unsigned NOT NULL,
            `store_id`          int(10) unsigned NOT NULL,
            PRIMARY KEY (`flash_discount_id`,`store_id`),
            KEY `idx_fds_store` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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

        $db = Database::getInstance()->getConnection();
        $stmtFdCats = $db->prepare(
            "SELECT c.name AS category_name, sc.name AS sub_category_name
             FROM flash_discount_categories fdc
             JOIN categories c ON c.id = fdc.category_id
             LEFT JOIN sub_categories sc ON sc.id = fdc.sub_category_id
             WHERE fdc.flash_discount_id = ?
             ORDER BY c.name, sc.name"
        );
        $stmtFdCats->execute([$id]);
        $fdCategoryDetails = $stmtFdCats->fetchAll(PDO::FETCH_ASSOC);

        $this->loadView('flash-discounts/view', [
            'title'            => 'Flash Discount &mdash; ' . escape($flashDiscount['title']),
            'flashDiscount'    => $flashDiscount,
            'fdCategoryDetails'=> $fdCategoryDetails,
            'current_user'     => $this->auth->getCurrentUser(),
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
        logAudit('flash_discount_toggled', 'flash_discount', $id);
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
        $db = Database::getInstance()->getConnection();
        $categories = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        require_once MODEL_PATH . '/City.php';
        $cityModel = new City();
        $this->loadView('flash-discounts/add', [
            'title'        => 'Create Flash Discount',
            'merchants'    => $merchantModel->getAllWithDetails(['limit' => 200]),
            'categories'   => $categories,
            'cities'       => $cityModel->getActive(),
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
        $db = Database::getInstance()->getConnection();
        $categories = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $stmtCat = $db->prepare("SELECT category_id, sub_category_id FROM flash_discount_categories WHERE flash_discount_id = ?");
        $stmtCat->execute([$id]);
        $catRows = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        $selectedCategoryIds = array_column($catRows, 'category_id');
        $selectedSubCategoryIds = array_values(array_filter(array_column($catRows, 'sub_category_id')));
        $subCategories = [];
        if (!empty($selectedCategoryIds)) {
            $inCats = implode(',', array_map('intval', $selectedCategoryIds));
            $subCategories = $db->query("SELECT id, name, category_id FROM sub_categories WHERE category_id IN ({$inCats}) AND status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        }
        require_once MODEL_PATH . '/City.php';
        $cityModel = new City();
        $stmtLoc = $db->prepare(
            "SELECT fdl.city_id, fdl.area_id, fdl.location_id, ci.city_name,
                    a.area_name, l.location_name
             FROM flash_discount_locations fdl
             JOIN cities ci ON ci.id = fdl.city_id
             LEFT JOIN areas a  ON a.id  = fdl.area_id
             LEFT JOIN locations l ON l.id = fdl.location_id
             WHERE fdl.flash_discount_id = ?"
        );
        $stmtLoc->execute([$id]);
        $existingLocations = $stmtLoc->fetchAll(PDO::FETCH_ASSOC);

        $stmtStores = $db->prepare("SELECT store_id FROM flash_discount_stores WHERE flash_discount_id = ?");
        $stmtStores->execute([$id]);
        $selectedStoreIds = array_column($stmtStores->fetchAll(PDO::FETCH_ASSOC), 'store_id');
        $this->loadView('flash-discounts/edit', [
            'title'              => 'Edit Flash Discount &mdash; ' . escape($fd['title']),
            'flashDiscount'      => $fd,
            'merchants'          => $merchantModel->getAllWithDetails(['limit' => 200]),
            'stores'             => $fd['merchant_id'] ? $storeModel->getByMerchant($fd['merchant_id']) : [],
            'categories'            => $categories,
            'selectedCategoryIds'   => $selectedCategoryIds,
            'selectedSubCategoryIds'=> $selectedSubCategoryIds,
            'subCategories'         => $subCategories,
            'cities'                => $cityModel->getActive(),
            'existingLocations'  => $existingLocations,
            'selectedStoreIds'   => $selectedStoreIds,
            'current_user'       => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── SAVE (shared add + edit) ─────────────────────────────────────────────

    private function handleSave($fdId) {
        $this->requireCSRF();

        $title          = sanitize($_POST['title']               ?? '');
        $description    = trim($_POST['description']             ?? '');
        $merchantId     = (int)($_POST['merchant_id']            ?? 0);
        $storeIds       = array_filter(array_map('intval', $_POST['store_ids'] ?? []));
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
            $uploadDir  = API_FLASH_UPLOAD_DIR;
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
            'store_id'             => null,
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
            // Ensure multi-store junction table exists
            $db = Database::getInstance()->getConnection();
            $db->exec("CREATE TABLE IF NOT EXISTS `flash_discount_stores` (
                `flash_discount_id` int(10) unsigned NOT NULL,
                `store_id`          int(10) unsigned NOT NULL,
                PRIMARY KEY (`flash_discount_id`,`store_id`),
                KEY `idx_fds_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            // Sync locations and categories
            $db = Database::getInstance()->getConnection();
            $this->syncFDLocationsAndCategories($db, $fdId, $storeIds);
            logAudit('flash_discount_updated', 'flash_discounts', $fdId);
            $_SESSION['success'] = "Flash discount '{$title}' updated.";
            $this->redirect("flash-discounts/detail?id={$fdId}");
        } else {
            $newId = $this->flashDiscountModel->createFlashDiscount($data);
            // Ensure multi-store junction table exists then sync
            $db = Database::getInstance()->getConnection();
            $db->exec("CREATE TABLE IF NOT EXISTS `flash_discount_stores` (
                `flash_discount_id` int(10) unsigned NOT NULL,
                `store_id`          int(10) unsigned NOT NULL,
                PRIMARY KEY (`flash_discount_id`,`store_id`),
                KEY `idx_fds_store` (`store_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $this->syncFDLocationsAndCategories($db, $newId, $storeIds);
            logAudit('flash_discount_created', 'flash_discounts', $newId);
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

    private function syncFDLocationsAndCategories(\PDO $db, int $fdId, array $storeIds): void {
        // Sync flash_discount_stores junction table
        $db->prepare("DELETE FROM flash_discount_stores WHERE flash_discount_id = ?")->execute([$fdId]);
        if (!empty($storeIds)) {
            $ins = $db->prepare("INSERT IGNORE INTO flash_discount_stores (flash_discount_id, store_id) VALUES (?, ?)");
            foreach ($storeIds as $sid) { $ins->execute([$fdId, $sid]); }
        }

        // Locations
        $cityIds     = array_filter(array_map('intval', (array)($_POST['location_city_ids']     ?? [])));
        $areaIds     = array_filter(array_map('intval', (array)($_POST['location_area_ids']     ?? [])));
        $locationIds = array_filter(array_map('intval', (array)($_POST['location_location_ids'] ?? [])));

        $db->prepare("DELETE FROM flash_discount_locations WHERE flash_discount_id = ?")->execute([$fdId]);

        if (!empty($cityIds)) {
            $ins = $db->prepare("INSERT INTO flash_discount_locations (flash_discount_id, city_id, area_id, location_id) VALUES (?, ?, ?, ?)");
            foreach ($cityIds as $i => $cid) {
                $ins->execute([$fdId, $cid, $areaIds[$i] ?: null, $locationIds[$i] ?: null]);
            }
        } elseif (!empty($storeIds)) {
            $store = $db->prepare("SELECT city_id, area_id, location_id FROM stores WHERE id = ?");
            $store->execute([reset($storeIds)]);
            $s = $store->fetch(PDO::FETCH_ASSOC);
            if ($s && $s['city_id']) {
                $db->prepare("INSERT INTO flash_discount_locations (flash_discount_id, city_id, area_id, location_id) VALUES (?, ?, ?, ?)")
                   ->execute([$fdId, $s['city_id'], $s['area_id'] ?: null, $s['location_id'] ?: null]);
            }
        }

        // Categories + sub-categories
        $categoryIds    = array_filter(array_map('intval', (array)($_POST['category_ids']     ?? [])));
        $subCategoryIds = array_filter(array_map('intval', (array)($_POST['sub_category_ids'] ?? [])));
        $db->prepare("DELETE FROM flash_discount_categories WHERE flash_discount_id = ?")->execute([$fdId]);

        if (!empty($categoryIds)) {
            $ins = $db->prepare("INSERT INTO flash_discount_categories (flash_discount_id, category_id) VALUES (?, ?)");
            foreach ($categoryIds as $cid) {
                $ins->execute([$fdId, $cid]);
            }
            if (!empty($subCategoryIds)) {
                $updSubCat = $db->prepare(
                    "UPDATE flash_discount_categories
                     SET sub_category_id = ?
                     WHERE flash_discount_id = ? AND category_id = (SELECT category_id FROM sub_categories WHERE id = ? LIMIT 1)"
                );
                foreach ($subCategoryIds as $subCatId) {
                    $updSubCat->execute([$subCatId, $fdId, $subCatId]);
                }
            }
        } elseif (!empty($storeIds)) {
            $sc = $db->prepare("SELECT category_id FROM store_categories WHERE store_id = ?");
            $sc->execute([reset($storeIds)]);
            $ins = $db->prepare("INSERT INTO flash_discount_categories (flash_discount_id, category_id) VALUES (?, ?)");
            foreach ($sc->fetchAll(PDO::FETCH_COLUMN) as $cid) {
                $ins->execute([$fdId, $cid]);
            }
        }
    }

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('flash-discounts', 'Invalid flash discount ID.'); return; }

        $fd = $this->flashDiscountModel->findWithDetails($id);
        if (!$fd) { $this->redirectWithError('flash-discounts', 'Flash discount not found.'); return; }

        try {
            $this->flashDiscountModel->deleteFlashDiscount($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('flash_discount_deleted', 'flash_discounts', $id);
            $_SESSION['success'] = "Flash discount '{$fd['title']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete flash discount: ' . $e->getMessage();
        }
        $this->redirect('flash-discounts');
    }
}
