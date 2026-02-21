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

        $this->loadView('merchants/view', [
            'title'        => 'Merchant Profile — ' . escape($merchant['business_name']),
            'merchant'     => $merchant,
            'stores'       => $stores,
            'reviews'      => $reviews,
            'labels'       => $labels,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $labelModel = new Label();
        $this->loadView('merchants/add', [
            'title'        => 'Add Merchant',
            'labels'       => $labelModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
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
        $this->loadView('merchants/edit', [
            'title'        => 'Edit Merchant — ' . escape($merchant['business_name']),
            'merchant'     => $merchant,
            'labels'       => $labelModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
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
        $subsExpiry      = sanitize($_POST['subscription_expiry']  ?? '');
        $profileStatus   = sanitize($_POST['profile_status']  ?? 'pending');
        $priorityWeight  = (int)($_POST['priority_weight']    ?? 0);
        $userStatus      = sanitize($_POST['status']           ?? 'active');
        $password        = $_POST['password'] ?? '';

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
            ];

            try {
                $this->merchantModel->updateWithUser($merchantId, $userData, $merchantData);
                logAudit('merchant_updated', $merchantId, 'merchant', $cu['id']);
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
            ];

            try {
                $newId = $this->merchantModel->createWithUser($userData, $merchantData);
                logAudit('merchant_created', $newId, 'merchant', $cu['id']);
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
            logAudit('merchant_deleted', $id, 'merchant', $cu['id']);
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
        logAudit('merchant_approved', $id, 'merchant', $cu['id']);
        $_SESSION['success'] = 'Merchant approved.';
        $this->redirect($_POST['redirect'] ?? "merchants/profile?id={$id}");
    }

    public function reject() {
        $this->requireCSRF();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $this->redirectWithError('merchants', 'Invalid merchant ID.'); return; }

        $this->merchantModel->updateProfileStatus($id, 'rejected');
        $cu = $this->auth->getCurrentUser();
        logAudit('merchant_rejected', $id, 'merchant', $cu['id']);
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

            try {
                $storeId = $this->storeModel->createStore([
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
                ]);
                $cu = $this->auth->getCurrentUser();
                logAudit('store_created', $storeId, 'store', $cu['id']);
                $_SESSION['success'] = "Store '{$storeName}' added.";
                $this->redirect("merchants/profile?id={$merchantId}");
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to add store: ' . $e->getMessage();
                $this->redirect($redirect);
            }
            return;
        }

        $cityModel = new City();
        $this->loadView('merchants/add-store', [
            'title'        => 'Add Store — ' . escape($merchant['business_name']),
            'merchant'     => $merchant,
            'cities'       => $cityModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
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

            try {
                $this->storeModel->updateStore($storeId, [
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
                ]);
                $cu = $this->auth->getCurrentUser();
                logAudit('store_updated', $storeId, 'store', $cu['id']);
                $_SESSION['success'] = "Store '{$storeName}' updated.";
                $this->redirect("merchants/profile?id={$merchantId}");
            } catch (Exception $e) {
                $_SESSION['error'] = 'Failed to update store: ' . $e->getMessage();
                $this->redirect($redirect);
            }
            return;
        }

        $cityModel = new City();
        $areaModel = new Area();
        $this->loadView('merchants/edit-store', [
            'title'        => 'Edit Store — ' . escape($store['store_name']),
            'store'        => $store,
            'cities'       => $cityModel->getActive(),
            'areas'        => $areaModel->getByCity($store['city_id']),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
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
            logAudit('store_deleted', $storeId, 'store', $cu['id']);
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
}
