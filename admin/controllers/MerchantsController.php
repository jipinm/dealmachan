<?php
require_once CORE_PATH   . '/Auth.php';
require_once MODEL_PATH  . '/Merchant.php';
require_once MODEL_PATH  . '/Store.php';
require_once MODEL_PATH  . '/City.php';
require_once MODEL_PATH  . '/Area.php';
require_once MODEL_PATH  . '/Location.php';
require_once MODEL_PATH  . '/Label.php';

class MerchantsController extends Controller {

    private $auth;
    private $merchantModel;
    private $storeModel;

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

        $this->merchantModel = new Merchant();
        $this->storeModel    = new Store();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filters = [
            'status'               => $_GET['status']               ?? '',
            'profile_status'       => $_GET['profile_status']       ?? '',
            'subscription_status'  => $_GET['subscription_status']  ?? '',
            'is_premium'           => $_GET['is_premium']           ?? '',
            'search'               => trim($_GET['search']          ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->merchantModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $merchants    = $this->merchantModel->getAllWithDetails($fetchFilters);
        $stats        = $this->merchantModel->getStats();

        $this->loadView('merchants/index', [
            'title'         => 'Merchant Management',
            'merchants'     => $merchants,
            'stats'         => $stats,
            'filters'       => $filters,
            'currentPage'   => $currentPage,
            'totalPages'    => $totalPages,
            'totalCount'    => $totalCount,
            'perPage'       => $perPage,
            'current_user'  => $this->auth->getCurrentUser(),
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ─── PROFILE ──────────────────────────────────────────────────────────────

    public function profile() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $merchant = $this->merchantModel->findWithDetails($id);
        if (!$merchant) { $this->redirectWithError('merchants', 'Merchant not found.'); return; }

        $stores  = $this->merchantModel->getStores($id);
        $reviews = $this->merchantModel->getReviews($id, 10);
        $labels  = $this->merchantModel->getMerchantLabels($id);
        $gallery = $this->merchantModel->getStoreGallery($id);

        $this->loadView('merchants/view', [
            'title'        => 'Merchant Profile &mdash; ' . escape($merchant['business_name']),
            'merchant'     => $merchant,
            'stores'       => $stores,
            'reviews'      => $reviews,
            'labels'       => $labels,
            'gallery'      => $gallery,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── AJAX: CHECK UNIQUE EMAIL / PHONE ─────────────────────────────────────

    public function checkUnique() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['error' => 'GET only'], 405); return;
        }

        $field         = $_GET['field'] ?? '';
        $value         = trim($_GET['value'] ?? '');
        $excludeUserId = isset($_GET['exclude_user_id']) ? (int)$_GET['exclude_user_id'] : null;

        if (!in_array($field, ['email', 'phone'], true) || $value === '') {
            $this->json(['exists' => false]); return;
        }

        if ($field === 'email') {
            $exists = $this->merchantModel->emailExists($value, $excludeUserId);
        } else {
            $exists = $this->merchantModel->phoneExists($value, $excludeUserId);
        }

        $this->json(['exists' => $exists]);
    }

    // ─── AJAX: CHECK UNIQUE STORE ADMIN EMAIL / PHONE ────────────────────────────

    public function checkStoreAdminUnique() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json(['error' => 'GET only'], 405); return;
        }

        $field = $_GET['field'] ?? '';
        $value = trim($_GET['value'] ?? '');

        if (!in_array($field, ['email', 'phone'], true) || $value === '') {
            $this->json(['exists' => false]); return;
        }

        $db = Database::getInstance()->getConnection();
        if ($field === 'email') {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        } else {
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
        }
        $stmt->execute([$value]);
        $this->json(['exists' => (int)$stmt->fetchColumn() > 0]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $labelModel = new Label();
        $db = Database::getInstance()->getConnection();
        $categories = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $subCategories = $db->query("SELECT MIN(sc.id) AS id, sc.name, sc.category_id, c.name AS category_name FROM sub_categories sc JOIN categories c ON c.id = sc.category_id WHERE sc.status='active' GROUP BY sc.category_id, sc.name ORDER BY c.name, sc.name")->fetchAll(PDO::FETCH_ASSOC);
        $this->loadView('merchants/add', [
            'title'                 => 'Add Merchant',
            'labels'                => $labelModel->getActive(),
            'categories'            => $categories,
            'subCategories'         => $subCategories,
            'selectedCategoryIds'   => [],
            'selectedSubCategoryIds'=> [],
            'current_user'          => $this->auth->getCurrentUser(),
            'flash_error'           => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $merchant = $this->merchantModel->findWithDetails($id);
        if (!$merchant) { $this->redirectWithError('merchants', 'Merchant not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $labelModel = new Label();
        $db = Database::getInstance()->getConnection();
        $categories = $db->query("SELECT id, name FROM categories WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $subCategories = $db->query("SELECT MIN(sc.id) AS id, sc.name, sc.category_id, c.name AS category_name FROM sub_categories sc JOIN categories c ON c.id = sc.category_id WHERE sc.status='active' GROUP BY sc.category_id, sc.name ORDER BY c.name, sc.name")->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->prepare("SELECT DISTINCT category_id FROM merchant_categories WHERE merchant_id = ?");
        $stmt->execute([$id]);
        $selectedCategoryIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category_id');
        $stmtSub = $db->prepare("SELECT sub_category_id FROM merchant_categories WHERE merchant_id = ? AND sub_category_id IS NOT NULL");
        $stmtSub->execute([$id]);
        $selectedSubCategoryIds = array_column($stmtSub->fetchAll(PDO::FETCH_ASSOC), 'sub_category_id');
        $this->loadView('merchants/edit', [
            'title'                 => 'Edit Merchant &mdash; ' . escape($merchant['business_name']),
            'merchant'              => $merchant,
            'labels'                => $labelModel->getActive(),
            'categories'            => $categories,
            'subCategories'         => $subCategories,
            'selectedCategoryIds'   => $selectedCategoryIds,
            'selectedSubCategoryIds'=> $selectedSubCategoryIds,
            'current_user'          => $this->auth->getCurrentUser(),
            'flash_error'           => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── SAVE (shared add + edit) ─────────────────────────────────────────────

    private function handleSave($merchantId) {
        $this->requireCSRF();

        $businessName    = sanitize($_POST['business_name']    ?? '');
        $email           = strtolower(trim($_POST['email']     ?? ''));
        $phone           = sanitize($_POST['phone']            ?? '');
        $regNumber       = sanitize($_POST['registration_number'] ?? '');
        $gstNumber       = sanitize($_POST['gst_number']       ?? '');
        $isPremium       = isset($_POST['is_premium']) ? 1 : 0;
        $labelId         = !empty($_POST['label_id']) ? (int)$_POST['label_id'] : null;
        $subsStatus      = sanitize($_POST['subscription_status'] ?? 'trial');
        $subscriptionPlan = sanitize($_POST['subscription_plan'] ?? 'merchant_only');
        $subsExpiry      = sanitize($_POST['subscription_expiry']  ?? '');
        $profileStatus   = sanitize($_POST['profile_status']  ?? 'pending');
        $priorityWeight  = (int)($_POST['priority_weight']    ?? 0);
        $userStatus      = sanitize($_POST['status']           ?? 'active');
        $password        = $_POST['password'] ?? '';
        $removeLogo      = ($_POST['remove_logo']   ?? '0') === '1';
        $removeBanner    = ($_POST['remove_banner'] ?? '0') === '1';

        $redirect = $merchantId ? "merchants/edit?id={$merchantId}" : 'merchants/add';

        // ── Validation ──
        if (!$businessName) {
            $_SESSION['error'] = 'Business name is required.';
            $this->redirect($redirect); return;
        }
        if (!$email && !$phone) {
            $_SESSION['error'] = 'Either email or phone is required.';
            $this->redirect($redirect); return;
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email address.';
            $this->redirect($redirect); return;
        }
        if (!$merchantId && strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            $this->redirect($redirect); return;
        }
        if ($merchantId && !empty($password) && strlen($password) < 8) {
            $_SESSION['error'] = 'New password must be at least 8 characters.';
            $this->redirect($redirect); return;
        }

        $existing      = $merchantId ? $this->merchantModel->findWithDetails($merchantId) : null;
        $excludeUserId = $existing ? $existing['user_id'] : null;

        if ($email && $this->merchantModel->emailExists($email, $excludeUserId)) {
            $_SESSION['error'] = "Email '{$email}' is already registered.";
            $this->redirect($redirect); return;
        }
        if ($phone && $this->merchantModel->phoneExists($phone, $excludeUserId)) {
            $_SESSION['error'] = "Phone '{$phone}' is already registered.";
            $this->redirect($redirect); return;
        }

        // ── Business logo upload ──
        $businessLogo = null;
        if (!empty($_FILES['business_logo']['name'])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext        = strtolower(pathinfo($_FILES['business_logo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $_SESSION['error'] = 'Logo must be a JPG, PNG, GIF, or WebP image.';
                $this->redirect($redirect); return;
            }
            if ($_FILES['business_logo']['size'] > 2 * 1024 * 1024) {
                $_SESSION['error'] = 'Logo image must be under 2 MB.';
                $this->redirect($redirect); return;
            }
            $uploadDir = API_LOGOS_UPLOAD_DIR;
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename    = 'logo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (!move_uploaded_file($_FILES['business_logo']['tmp_name'], $destination)) {
                $_SESSION['error'] = 'Failed to upload logo. Please try again.';
                $this->redirect($redirect); return;
            }
            $businessLogo = 'uploads/logos/' . $filename;
        }

        // ── Banner image upload ──
        $bannerImage = null;
        if (!empty($_FILES['banner_image']['name'])) {
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext        = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $_SESSION['error'] = 'Banner image must be a JPG, PNG, GIF, or WebP image.';
                $this->redirect($redirect); return;
            }
            if ($_FILES['banner_image']['size'] > 3 * 1024 * 1024) {
                $_SESSION['error'] = 'Banner image must be under 3 MB.';
                $this->redirect($redirect); return;
            }
            $uploadDir = API_MERCHANT_BANNERS_UPLOAD_DIR;
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
            $filename    = 'banner_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $destination = $uploadDir . $filename;
            if (!move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                $_SESSION['error'] = 'Failed to upload banner image. Please try again.';
                $this->redirect($redirect); return;
            }
            $bannerImage = 'uploads/merchant-banners/' . $filename;
        }

        $cu = $this->auth->getCurrentUser();

        if ($merchantId) {
            // ── UPDATE ──
            $userData = [];
            if ($email) $userData['email']  = $email;
            if ($phone) $userData['phone']  = $phone;
            if (!empty($password)) $userData['password'] = $password;
            $userData['status'] = $userStatus;

            $merchantData = [
                'business_name'       => $businessName,
                'registration_number' => $regNumber  ?: null,
                'gst_number'          => $gstNumber  ?: null,
                'is_premium'          => $isPremium,
                'label_id'            => $labelId,
                'subscription_status' => $subsStatus,
                'subscription_expiry' => $subsExpiry ?: null,
                'profile_status'      => $profileStatus,
                'priority_weight'     => $priorityWeight,
                'subscription_plan'   => $subscriptionPlan,
            ];
            // Logo: new upload, or explicit removal, or keep existing
            if ($businessLogo !== null)    { $merchantData['business_logo'] = $businessLogo; }
            elseif ($removeLogo)           { $merchantData['business_logo'] = null; }
            // Banner: new upload, or explicit removal, or keep existing
            if ($bannerImage !== null)     { $merchantData['banner_image'] = $bannerImage; }
            elseif ($removeBanner)         { $merchantData['banner_image'] = null; }

            try {
                $this->merchantModel->updateWithUser($merchantId, $userData, $merchantData);
                // Sync merchant categories
                $categoryIds    = array_filter(array_map('intval', $_POST['category_ids']     ?? []));
                $subCategoryIds = array_filter(array_map('intval', $_POST['sub_category_ids'] ?? []));
                $db = Database::getInstance()->getConnection();
                $db->prepare("DELETE FROM merchant_categories WHERE merchant_id = ?")->execute([$merchantId]);
                $coveredCatIds = [];
                if ($subCategoryIds) {
                    $ph = implode(',', array_fill(0, count($subCategoryIds), '?'));
                    $stmtSP = $db->prepare("SELECT id, category_id FROM sub_categories WHERE id IN ($ph)");
                    $stmtSP->execute($subCategoryIds);
                    $subCatParents = array_column($stmtSP->fetchAll(PDO::FETCH_ASSOC), 'category_id', 'id');
                    $ins = $db->prepare("INSERT IGNORE INTO merchant_categories (merchant_id, category_id, sub_category_id) VALUES (?, ?, ?)");
                    foreach ($subCategoryIds as $sId) {
                        if (isset($subCatParents[$sId])) {
                            $ins->execute([$merchantId, $subCatParents[$sId], $sId]);
                            $coveredCatIds[] = (int)$subCatParents[$sId];
                        }
                    }
                }
                $catsWithoutSubs = array_diff($categoryIds, array_unique($coveredCatIds));
                if ($catsWithoutSubs) {
                    $insCat = $db->prepare("INSERT IGNORE INTO merchant_categories (merchant_id, category_id, sub_category_id) VALUES (?, ?, NULL)");
                    foreach ($catsWithoutSubs as $cId) { $insCat->execute([$merchantId, $cId]); }
                }
                logAudit('merchant_updated', 'merchant', $merchantId);
                $_SESSION['success'] = "Merchant '{$businessName}' updated successfully.";
                $this->redirect("merchants/profile?id={$merchantId}");
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to update merchant: ' . $e->getMessage();
                $this->redirect($redirect);
            }

        } else {
            // ── CREATE ──
            $userData = ['status' => $userStatus];
            if ($email) $userData['email'] = $email;
            if ($phone) $userData['phone'] = $phone;
            $userData['password'] = $password;

            $merchantData = [
                'business_name'       => $businessName,
                'registration_number' => $regNumber  ?: null,
                'gst_number'          => $gstNumber  ?: null,
                'is_premium'          => $isPremium,
                'label_id'            => $labelId,
                'subscription_status' => $subsStatus,
                'subscription_expiry' => $subsExpiry ?: null,
                'profile_status'      => $profileStatus,
                'priority_weight'     => $priorityWeight,
                'subscription_plan'   => $subscriptionPlan,
            ];
            if ($businessLogo !== null) { $merchantData['business_logo'] = $businessLogo; }
            if ($bannerImage  !== null) { $merchantData['banner_image']  = $bannerImage; }

            try {
                $newId = $this->merchantModel->createWithUser($userData, $merchantData);
                // Sync merchant categories
                $categoryIds    = array_filter(array_map('intval', $_POST['category_ids']     ?? []));
                $subCategoryIds = array_filter(array_map('intval', $_POST['sub_category_ids'] ?? []));
                $db = Database::getInstance()->getConnection();
                $coveredCatIds = [];
                if ($subCategoryIds) {
                    $ph = implode(',', array_fill(0, count($subCategoryIds), '?'));
                    $stmtSP = $db->prepare("SELECT id, category_id FROM sub_categories WHERE id IN ($ph)");
                    $stmtSP->execute($subCategoryIds);
                    $subCatParents = array_column($stmtSP->fetchAll(PDO::FETCH_ASSOC), 'category_id', 'id');
                    $ins = $db->prepare("INSERT IGNORE INTO merchant_categories (merchant_id, category_id, sub_category_id) VALUES (?, ?, ?)");
                    foreach ($subCategoryIds as $sId) {
                        if (isset($subCatParents[$sId])) {
                            $ins->execute([$newId, $subCatParents[$sId], $sId]);
                            $coveredCatIds[] = (int)$subCatParents[$sId];
                        }
                    }
                }
                $catsWithoutSubs = array_diff($categoryIds, array_unique($coveredCatIds));
                if ($catsWithoutSubs) {
                    $insCat = $db->prepare("INSERT IGNORE INTO merchant_categories (merchant_id, category_id, sub_category_id) VALUES (?, ?, NULL)");
                    foreach ($catsWithoutSubs as $cId) { $insCat->execute([$newId, $cId]); }
                }
                logAudit('merchant_created', 'merchant', $newId);
                $_SESSION['success'] = "Merchant '{$businessName}' added successfully.";
                $this->redirect("merchants/profile?id={$newId}");
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to create merchant: ' . $e->getMessage();
                $this->redirect($redirect);
            }
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $merchant = $this->merchantModel->findWithDetails($id);
        if (!$merchant) { $this->redirectWithError('merchants', 'Merchant not found.'); return; }

        try {
            $this->merchantModel->deleteWithUser($id);
            $cu = $this->auth->getCurrentUser();
            logAudit('merchant_deleted', 'merchant', $id);
            $_SESSION['success'] = "Merchant '{$merchant['business_name']}' deleted.";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete merchant: ' . $e->getMessage();
        }
        $this->redirect('merchants');
    }

    // ─── TOGGLE USER STATUS ───────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id       = (int)($_POST['id'] ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'merchants');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid merchant ID.'); return; }

        $this->merchantModel->toggleStatus($id);
        $this->redirect($redirect);
    }

    // ─── APPROVE / REJECT ────────────────────────────────────────────────────

    public function approve() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $this->merchantModel->updateProfileStatus($id, 'approved');
        $cu = $this->auth->getCurrentUser();
        logAudit('merchant_approved', 'merchant', $id);
        $_SESSION['success'] = 'Merchant approved.';
        $this->redirect($_POST['redirect'] ?? "merchants/profile?id={$id}");
    }

    public function reject() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $this->merchantModel->updateProfileStatus($id, 'rejected');
        $cu = $this->auth->getCurrentUser();
        logAudit('merchant_rejected', 'merchant', $id);
        $_SESSION['success'] = 'Merchant rejected.';
        $this->redirect($_POST['redirect'] ?? "merchants/profile?id={$id}");
    }

    // ─── STORE MANAGEMENT ────────────────────────────────────────────────────

    public function addStore() {
        $merchantId = (int)($_GET['merchant_id'] ?? $_POST['merchant_id'] ?? 0);
        if (!$merchantId) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $merchant = $this->merchantModel->find($merchantId);
        if (!$merchant) { $this->redirectWithError('merchants', 'Merchant not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $storeName   = sanitize($_POST['store_name']   ?? '');
            $address     = sanitize($_POST['address']      ?? '');
            $cityId      = (int)($_POST['city_id']         ?? 0);
            $areaId      = (int)($_POST['area_id']         ?? 0);
            $locationId  = !empty($_POST['location_id'])   ? (int)$_POST['location_id'] : null;
            $phone       = sanitize($_POST['phone']        ?? '');
            $email       = sanitize($_POST['email']        ?? '');
            $description = sanitize($_POST['description']  ?? '');
            $storeStatus = sanitize($_POST['status']       ?? 'active');
            $redirect    = "merchants/add-store?merchant_id={$merchantId}";

            // Parse opening hours from form
            $openingHours = null;
            if (!empty($_POST['hours']) && is_array($_POST['hours'])) {
                $hoursData = [];
                foreach ($_POST['hours'] as $day => $times) {
                    $hoursData[$day] = [
                        'open'   => $times['open']  ?? '09:00',
                        'close'  => $times['close'] ?? '21:00',
                        'closed' => isset($times['closed']) && $times['closed'] ? true : false,
                    ];
                }
                $openingHours = json_encode($hoursData);
            }

            if (!$storeName || !$address || !$cityId || !$areaId) {
                $_SESSION['error'] = 'Store name, address, city and area are required.';
                $this->redirect($redirect); return;
            }

            // ── Store image upload ──
            $storeImage = null;
            if (!empty($_FILES['store_image']['name'])) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext        = strtolower(pathinfo($_FILES['store_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt)) {
                    $_SESSION['error'] = 'Store image must be a JPG, PNG, GIF, or WebP file.';
                    $this->redirect($redirect); return;
                }
                if ($_FILES['store_image']['size'] > 3 * 1024 * 1024) {
                    $_SESSION['error'] = 'Store image must be under 3 MB.';
                    $this->redirect($redirect); return;
                }
                $uploadDir = API_STORE_IMAGES_UPLOAD_DIR;
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $filename    = 'store_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destination = $uploadDir . $filename;
                if (!move_uploaded_file($_FILES['store_image']['tmp_name'], $destination)) {
                    $_SESSION['error'] = 'Failed to upload store image. Please try again.';
                    $this->redirect($redirect); return;
                }
                $storeImage = 'uploads/store-images/' . $filename;
            }

            try {
                $db = Database::getInstance()->getConnection();
                $db->beginTransaction();

                $storeData = [
                    'merchant_id'   => $merchantId,
                    'store_name'    => $storeName,
                    'address'       => $address,
                    'city_id'       => $cityId,
                    'area_id'       => $areaId,
                    'location_id'   => $locationId,
                    'phone'         => $phone  ?: null,
                    'email'         => $email  ?: null,
                    'description'   => $description ?: null,
                    'opening_hours' => $openingHours,
                    'status'        => $storeStatus,
                ];
                if ($storeImage !== null) { $storeData['store_image'] = $storeImage; }

                $storeId = $this->storeModel->createStore($storeData);
                // Sync store_categories (only allow categories already assigned to merchant)
                $categoryIds    = array_filter(array_map('intval', $_POST['category_ids']    ?? []));
                $subCategoryIds = array_filter(array_map('intval', $_POST['sub_category_ids'] ?? []));
                $stmtAllowed = $db->prepare("SELECT DISTINCT category_id FROM merchant_categories WHERE merchant_id = ?");
                $stmtAllowed->execute([$merchantId]);
                $allowedCatIds = array_column($stmtAllowed->fetchAll(PDO::FETCH_ASSOC), 'category_id');
                $categoryIds = array_values(array_intersect($categoryIds, $allowedCatIds));
                $coveredCatIds = [];
                if ($subCategoryIds) {
                    $phS = implode(',', array_fill(0, count($subCategoryIds), '?'));
                    $stmtSP = $db->prepare("SELECT id, category_id FROM sub_categories WHERE id IN ($phS)");
                    $stmtSP->execute($subCategoryIds);
                    $subCatParents = array_column($stmtSP->fetchAll(PDO::FETCH_ASSOC), 'category_id', 'id');
                    $ins = $db->prepare("INSERT IGNORE INTO store_categories (store_id, category_id, sub_category_id) VALUES (?, ?, ?)");
                    foreach ($subCategoryIds as $sId) {
                        if (isset($subCatParents[$sId])) {
                            $ins->execute([$storeId, $subCatParents[$sId], $sId]);
                            $coveredCatIds[] = (int)$subCatParents[$sId];
                        }
                    }
                }
                $catsWithoutSubs = array_diff($categoryIds, array_unique($coveredCatIds));
                if ($catsWithoutSubs) {
                    $insCat = $db->prepare("INSERT IGNORE INTO store_categories (store_id, category_id, sub_category_id) VALUES (?, ?, NULL)");
                    foreach ($catsWithoutSubs as $cId) { $insCat->execute([$storeId, $cId]); }
                }
                // Store admin credentials (optional)
                $adminEmail = trim(sanitize($_POST['admin_email'] ?? ''));
                $adminPhone = trim(sanitize($_POST['admin_phone'] ?? ''));
                $adminPass  = $_POST['admin_password'] ?? '';
                if ($adminEmail !== '' || $adminPhone !== '') {
                    if ($adminEmail !== '') {
                        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
                        $chk->execute([$adminEmail]);
                        if ($chk->fetch()) {
                            throw new Exception("Store admin email '{$adminEmail}' is already registered.");
                        }
                    }
                    if ($adminPhone !== '') {
                        $chk = $db->prepare("SELECT id FROM users WHERE phone = ?");
                        $chk->execute([$adminPhone]);
                        if ($chk->fetch()) {
                            throw new Exception("Store admin phone '{$adminPhone}' is already registered.");
                        }
                    }
                    if (strlen($adminPass) < 6) {
                        throw new Exception("Store admin password must be at least 6 characters.");
                    }
                    $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $db->prepare("INSERT INTO users (email, phone, password_hash, user_type, status, created_at) VALUES (?, ?, ?, 'merchant', 'active', NOW())")
                       ->execute([$adminEmail ?: null, $adminPhone ?: null, $passHash]);
                    $adminUserId = (int)$db->lastInsertId();
                    $db->prepare("INSERT INTO merchant_store_users (merchant_id, user_id, store_id, access_scope, status) VALUES (?, ?, ?, 'store', 'active')")
                       ->execute([$merchantId, $adminUserId, $storeId]);
                    logAudit('store_admin_created', 'merchant_store_users', $adminUserId, [
                        'email'       => $adminEmail ?: null,
                        'phone'       => $adminPhone ?: null,
                        'merchant_id' => $merchantId,
                        'store_id'    => $storeId,
                    ]);
                }
                $db->commit();
                $cu = $this->auth->getCurrentUser();
                logAudit('store_created', 'store', $storeId);
                $_SESSION['success'] = "Store '{$storeName}' added.";
                $this->redirect("merchants/profile?id={$merchantId}");
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) $db->rollBack();
                $_SESSION['error'] = 'Failed to add store: ' . $e->getMessage();
                $this->redirect($redirect);
            }
            return;
        }

        $cityModel = new City();
        $db = Database::getInstance()->getConnection();
        $stmtCat = $db->prepare("SELECT DISTINCT c.id, c.name FROM categories c JOIN merchant_categories mc ON mc.category_id = c.id WHERE mc.merchant_id = ? AND c.status = 'active' ORDER BY c.name");
        $stmtCat->execute([$merchantId]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        $catIds = array_column($categories, 'id');
        $subCategories = [];
        if ($catIds) {
            $ph = implode(',', array_fill(0, count($catIds), '?'));
            $stmtSub = $db->prepare("SELECT MIN(id) AS id, name, category_id FROM sub_categories WHERE category_id IN ($ph) AND status = 'active' GROUP BY category_id, name ORDER BY name");
            $stmtSub->execute($catIds);
            $subCategories = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
        }
        $this->loadView('merchants/add-store', [
            'title'          => 'Add Store &mdash; ' . escape($merchant['business_name']),
            'merchant'       => $merchant,
            'cities'         => $cityModel->getActive(),
            'categories'     => $categories,
            'subCategories'  => $subCategories,
            'noCategories'   => empty($categories),
            'current_user'   => $this->auth->getCurrentUser(),
            'flash_error'    => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    public function editStore() {
        $storeId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$storeId) { $this->redirectWithError('merchants', 'Invalid store ID.'); return; }

        $store = $this->storeModel->findWithDetails($storeId);
        if (!$store) { $this->redirectWithError('merchants', 'Store not found.'); return; }

        $merchantId = $store['merchant_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();

            $storeName   = sanitize($_POST['store_name']   ?? '');
            $address     = sanitize($_POST['address']      ?? '');
            $cityId      = (int)($_POST['city_id']         ?? 0);
            $areaId      = (int)($_POST['area_id']         ?? 0);
            $locationId  = !empty($_POST['location_id'])   ? (int)$_POST['location_id'] : null;
            $phone       = sanitize($_POST['phone']        ?? '');
            $email       = sanitize($_POST['email']        ?? '');
            $description = sanitize($_POST['description']  ?? '');
            $storeStatus = sanitize($_POST['status']       ?? 'active');
            $redirect    = "merchants/edit-store?id={$storeId}";

            // Parse opening hours from form
            $openingHours = null;
            if (!empty($_POST['hours']) && is_array($_POST['hours'])) {
                $hoursData = [];
                foreach ($_POST['hours'] as $day => $times) {
                    $hoursData[$day] = [
                        'open'   => $times['open']  ?? '09:00',
                        'close'  => $times['close'] ?? '21:00',
                        'closed' => isset($times['closed']) && $times['closed'] ? true : false,
                    ];
                }
                $openingHours = json_encode($hoursData);
            }

            if (!$storeName || !$address || !$cityId || !$areaId) {
                $_SESSION['error'] = 'Store name, address, city and area are required.';
                $this->redirect($redirect); return;
            }

            // ── Store image upload ──
            $storeImage = null;
            if (!empty($_FILES['store_image']['name'])) {
                $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext        = strtolower(pathinfo($_FILES['store_image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedExt)) {
                    $_SESSION['error'] = 'Store image must be a JPG, PNG, GIF, or WebP file.';
                    $this->redirect($redirect); return;
                }
                if ($_FILES['store_image']['size'] > 3 * 1024 * 1024) {
                    $_SESSION['error'] = 'Store image must be under 3 MB.';
                    $this->redirect($redirect); return;
                }
                $uploadDir = API_STORE_IMAGES_UPLOAD_DIR;
                if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                $filename    = 'store_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destination = $uploadDir . $filename;
                if (!move_uploaded_file($_FILES['store_image']['tmp_name'], $destination)) {
                    $_SESSION['error'] = 'Failed to upload store image. Please try again.';
                    $this->redirect($redirect); return;
                }
                $storeImage = 'uploads/store-images/' . $filename;
            }

            try {
                $db = Database::getInstance()->getConnection();
                $db->beginTransaction();

                $storeUpdateData = [
                    'store_name'    => $storeName,
                    'address'       => $address,
                    'city_id'       => $cityId,
                    'area_id'       => $areaId,
                    'location_id'   => $locationId,
                    'phone'         => $phone  ?: null,
                    'email'         => $email  ?: null,
                    'description'   => $description ?: null,
                    'opening_hours' => $openingHours,
                    'status'        => $storeStatus,
                ];
                if ($storeImage !== null)              { $storeUpdateData['store_image'] = $storeImage; }
                elseif (($_POST['remove_store_image'] ?? '0') === '1') { $storeUpdateData['store_image'] = null; }

                $this->storeModel->updateStore($storeId, $storeUpdateData);
                // Sync store_categories (only allow categories assigned to merchant)
                $categoryIds    = array_filter(array_map('intval', $_POST['category_ids']    ?? []));
                $subCategoryIds = array_filter(array_map('intval', $_POST['sub_category_ids'] ?? []));
                $stmtAllowed = $db->prepare("SELECT DISTINCT category_id FROM merchant_categories WHERE merchant_id = ?");
                $stmtAllowed->execute([$merchantId]);
                $allowedCatIds = array_column($stmtAllowed->fetchAll(PDO::FETCH_ASSOC), 'category_id');
                $categoryIds = array_values(array_intersect($categoryIds, $allowedCatIds));
                $db->prepare("DELETE FROM store_categories WHERE store_id = ?")->execute([$storeId]);
                $coveredCatIds = [];
                if ($subCategoryIds) {
                    $phS = implode(',', array_fill(0, count($subCategoryIds), '?'));
                    $stmtSP = $db->prepare("SELECT id, category_id FROM sub_categories WHERE id IN ($phS)");
                    $stmtSP->execute($subCategoryIds);
                    $subCatParents = array_column($stmtSP->fetchAll(PDO::FETCH_ASSOC), 'category_id', 'id');
                    $ins = $db->prepare("INSERT IGNORE INTO store_categories (store_id, category_id, sub_category_id) VALUES (?, ?, ?)");
                    foreach ($subCategoryIds as $sId) {
                        if (isset($subCatParents[$sId])) {
                            $ins->execute([$storeId, $subCatParents[$sId], $sId]);
                            $coveredCatIds[] = (int)$subCatParents[$sId];
                        }
                    }
                }
                $catsWithoutSubs = array_diff($categoryIds, array_unique($coveredCatIds));
                if ($catsWithoutSubs) {
                    $insCat = $db->prepare("INSERT IGNORE INTO store_categories (store_id, category_id, sub_category_id) VALUES (?, ?, NULL)");
                    foreach ($catsWithoutSubs as $cId) { $insCat->execute([$storeId, $cId]); }
                }
                // Store admin credential handling
                $adminEmail       = trim(sanitize($_POST['admin_email'] ?? ''));
                $adminPass        = $_POST['admin_password'] ?? '';
                $removeAdmin      = ($_POST['remove_store_admin']  ?? '0') === '1';
                $toggleAdmin      = ($_POST['toggle_store_admin']  ?? '0') === '1';
                $stmtAdmin = $db->prepare("SELECT u.id AS user_id, u.email, msu.id AS msu_id, msu.status AS msu_status FROM users u JOIN merchant_store_users msu ON msu.user_id = u.id WHERE msu.store_id = ? AND msu.access_scope = 'store' LIMIT 1");
                $stmtAdmin->execute([$storeId]);
                $existingAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
                if ($toggleAdmin && $existingAdmin) {
                    $newStatus = $existingAdmin['msu_status'] === 'active' ? 'inactive' : 'active';
                    $db->prepare("UPDATE merchant_store_users SET status = ?, updated_at = NOW() WHERE id = ?")
                       ->execute([$newStatus, $existingAdmin['msu_id']]);
                    logAudit('store_admin_' . $newStatus, 'merchant_store_users', $existingAdmin['msu_id'], [
                        'email'    => $existingAdmin['email'],
                        'store_id' => $storeId,
                        'status'   => $newStatus,
                    ]);
                } elseif ($removeAdmin && $existingAdmin) {
                    $db->prepare("DELETE FROM merchant_store_users WHERE id = ?")->execute([$existingAdmin['msu_id']]);
                    $db->prepare("DELETE FROM users WHERE id = ? AND user_type = 'merchant'")->execute([$existingAdmin['user_id']]);
                    logAudit('store_admin_removed', 'merchant_store_users', $existingAdmin['msu_id'], null, [
                        'email'    => $existingAdmin['email'],
                        'store_id' => $storeId,
                    ]);
                } elseif ($existingAdmin && $adminPass !== '') {
                    if (strlen($adminPass) < 6) {
                        throw new Exception("Store admin password must be at least 6 characters.");
                    }
                    $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ? AND user_type = 'merchant'")
                       ->execute([password_hash($adminPass, PASSWORD_DEFAULT), $existingAdmin['user_id']]);
                    logAudit('store_admin_password_reset', 'users', $existingAdmin['user_id'], [
                        'email'    => $existingAdmin['email'],
                        'store_id' => $storeId,
                    ]);
                } elseif (!$existingAdmin && $adminEmail !== '') {
                    $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
                    $chk->execute([$adminEmail]);
                    if ($chk->fetch()) {
                        throw new Exception("Store admin email '{$adminEmail}' is already registered.");
                    }
                    if (strlen($adminPass) < 6) {
                        throw new Exception("Store admin password must be at least 6 characters.");
                    }
                    $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
                    $db->prepare("INSERT INTO users (email, password_hash, user_type, status, created_at) VALUES (?, ?, 'merchant', 'active', NOW())")
                       ->execute([$adminEmail, $passHash]);
                    $adminUserId = (int)$db->lastInsertId();
                    $db->prepare("INSERT INTO merchant_store_users (merchant_id, user_id, store_id, access_scope, status) VALUES (?, ?, ?, 'store', 'active')")
                       ->execute([$merchantId, $adminUserId, $storeId]);
                    logAudit('store_admin_created', 'merchant_store_users', $adminUserId, [
                        'email'       => $adminEmail,
                        'merchant_id' => $merchantId,
                        'store_id'    => $storeId,
                    ]);
                }
                $db->commit();
                $cu = $this->auth->getCurrentUser();
                logAudit('store_updated', 'store', $storeId);
                $_SESSION['success'] = "Store '{$storeName}' updated.";
                $this->redirect("merchants/profile?id={$merchantId}");
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) $db->rollBack();
                $_SESSION['error'] = 'Failed to update store: ' . $e->getMessage();
                $this->redirect($redirect);
            }
            return;
        }

        $cityModel = new City();
        $areaModel = new Area();
        $db = Database::getInstance()->getConnection();
        $stmtCat = $db->prepare("SELECT DISTINCT c.id, c.name FROM categories c JOIN merchant_categories mc ON mc.category_id = c.id WHERE mc.merchant_id = ? AND c.status = 'active' ORDER BY c.name");
        $stmtCat->execute([$merchantId]);
        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
        $catIds = array_column($categories, 'id');
        $subCategories = [];
        if ($catIds) {
            $ph = implode(',', array_fill(0, count($catIds), '?'));
            $stmtAllSub = $db->prepare("SELECT MIN(id) AS id, name, category_id FROM sub_categories WHERE category_id IN ($ph) AND status = 'active' GROUP BY category_id, name ORDER BY name");
            $stmtAllSub->execute($catIds);
            $subCategories = $stmtAllSub->fetchAll(PDO::FETCH_ASSOC);
        }
        // Existing category & sub-category assignments
        $stmtSC = $db->prepare("SELECT DISTINCT category_id FROM store_categories WHERE store_id = ?");
        $stmtSC->execute([$storeId]);
        $storeCategories = array_column($stmtSC->fetchAll(PDO::FETCH_ASSOC), 'category_id');
        $stmtSub = $db->prepare("SELECT DISTINCT sc.sub_category_id FROM store_categories sc WHERE sc.store_id = ? AND sc.sub_category_id IS NOT NULL");
        $stmtSub->execute([$storeId]);
        $storeSubCategoryIds = array_column($stmtSub->fetchAll(PDO::FETCH_ASSOC), 'sub_category_id');
        // Existing store admin
        $stmtAdmin = $db->prepare("SELECT u.id AS user_id, u.email, msu.id AS msu_id, msu.status AS msu_status FROM users u JOIN merchant_store_users msu ON msu.user_id = u.id WHERE msu.store_id = ? AND msu.access_scope = 'store' LIMIT 1");
        $stmtAdmin->execute([$storeId]);
        $storeAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC) ?: null;
        $this->loadView('merchants/edit-store', [
            'title'                => 'Edit Store &mdash; ' . escape($store['store_name']),
            'store'                => $store,
            'cities'               => $cityModel->getActive(),
            'areas'                => $areaModel->getByCity($store['city_id']),
            'categories'           => $categories,
            'subCategories'        => $subCategories,
            'noCategories'         => empty($categories),
            'storeCategories'      => $storeCategories,
            'storeSubCategoryIds'  => $storeSubCategoryIds,
            'storeAdmin'           => $storeAdmin,
            'storeGallery'         => $this->merchantModel->getGalleryByStore($storeId),
            'current_user'         => $this->auth->getCurrentUser(),
            'flash_error'          => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    public function deleteStore() {
        $this->requireCSRF();
        $storeId    = (int)($_POST['id']          ?? 0);
        $merchantId = (int)($_POST['merchant_id'] ?? 0);

        if (!$storeId) { $this->redirectWithError("merchants/profile?id={$merchantId}", 'Invalid store ID.'); return; }

        $store = $this->storeModel->find($storeId);
        if (!$store) { $this->redirectWithError("merchants/profile?id={$merchantId}", 'Store not found.'); return; }

        try {
            $this->storeModel->deleteStore($storeId);
            $cu = $this->auth->getCurrentUser();
            logAudit('store_deleted', 'store', $storeId);
            $_SESSION['success'] = 'Store deleted.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Cannot delete store: ' . $e->getMessage();
        }
        $this->redirect("merchants/profile?id={$merchantId}");
    }

    public function toggleStore() {
        $this->requireCSRF();
        $storeId    = (int)($_POST['id']          ?? 0);
        $merchantId = (int)($_POST['merchant_id'] ?? 0);

        if ($storeId) $this->storeModel->toggleStatus($storeId);
        $this->redirect("merchants/profile?id={$merchantId}");
    }

    // POST /merchants/delete-gallery-image  &ndash; JSON
    public function deleteGalleryImage(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST only']); return; }
        $imageId    = (int)($_POST['image_id']    ?? 0);
        $merchantId = (int)($_POST['merchant_id'] ?? 0);
        if (!$imageId || !$merchantId) { echo json_encode(['success' => false, 'error' => 'Invalid params']); return; }
        $ok = $this->merchantModel->deleteGalleryImage($imageId, $merchantId);
        echo json_encode(['success' => $ok]);
    }

    // POST /merchants/set-cover-image  &ndash; JSON
    public function setCoverImage(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST only']); return; }
        $imageId    = (int)($_POST['image_id']    ?? 0);
        $storeId    = (int)($_POST['store_id']    ?? 0);
        $merchantId = (int)($_POST['merchant_id'] ?? 0);
        if (!$imageId || !$storeId || !$merchantId) { echo json_encode(['success' => false, 'error' => 'Invalid params']); return; }
        $ok = $this->merchantModel->setCoverImage($imageId, $storeId, $merchantId);
        echo json_encode(['success' => $ok]);
    }

    // POST /merchants/upload-gallery-image  &ndash; JSON (AJAX, multipart)
    public function uploadGalleryImage(): void {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST only']); return; }

        $storeId    = (int)($_POST['store_id']    ?? 0);
        $merchantId = (int)($_POST['merchant_id'] ?? 0);
        $caption    = sanitize($_POST['caption']  ?? '');

        if (!$storeId || !$merchantId) { echo json_encode(['success' => false, 'error' => 'Invalid params']); return; }

        // Verify the store belongs to this merchant
        $store = $this->storeModel->find($storeId);
        if (!$store || (int)$store['merchant_id'] !== $merchantId) {
            echo json_encode(['success' => false, 'error' => 'Access denied']); return;
        }

        if (empty($_FILES['gallery_image']['name'])) {
            echo json_encode(['success' => false, 'error' => 'No file uploaded']); return;
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext        = strtolower(pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            echo json_encode(['success' => false, 'error' => 'Image must be JPG, PNG, GIF, or WebP']); return;
        }
        if ($_FILES['gallery_image']['size'] > 3 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'Image must be under 3 MB']); return;
        }

        $uploadDir = API_GALLERY_UPLOAD_DIR;
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        $filename    = 'gallery_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destination = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['gallery_image']['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'error' => 'Failed to save file']); return;
        }

        $imageUrl = 'uploads/gallery/' . $filename;
        $imageId  = $this->merchantModel->addGalleryImage($storeId, $merchantId, $imageUrl, $caption);

        echo json_encode([
            'success'  => true,
            'image_id' => $imageId,
            'image_url'=> $imageUrl,
            'full_url' => rtrim(API_URL, '/') . '/' . $imageUrl,
            'caption'  => $caption,
        ]);
    }
}
