<?php
require_once CORE_PATH  . '/Auth.php';
require_once MODEL_PATH . '/Store.php';
require_once MODEL_PATH . '/Merchant.php';
require_once MODEL_PATH . '/City.php';

class StoresController extends Controller {

    private Auth   $auth;
    private Store  $storeModel;

    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'sales_admin'];
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

        $this->storeModel = new Store();
    }

    // ─── LIST (all stores across all merchants) ───────────────────────────────

    public function index() {
        $filters = [
            'merchant_id' => (int)($_GET['merchant_id'] ?? 0) ?: null,
            'city_id'     => (int)($_GET['city_id']     ?? 0) ?: null,
            'status'      => $_GET['status']  ?? '',
            'search'      => trim($_GET['search'] ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->storeModel->countAll($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $stores  = $this->storeModel->getAllWithDetails($fetchFilters);
        $stats   = $this->storeModel->getStats();

        $cityModel     = new City();
        $merchantModel = new Merchant();

        // Merchant list for filter dropdown
        $merchants = $merchantModel->getAllWithDetails(['limit' => 0]);

        $this->loadView('stores/index', [
            'title'         => 'Stores',
            'stores'        => $stores,
            'stats'         => $stats,
            'filters'       => $filters,
            'cities'        => $cityModel->getActive(),
            'merchants'     => $merchants,
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

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        $this->requireCSRF();
        $id       = (int)($_POST['id']       ?? 0);
        $redirect = sanitize($_POST['redirect'] ?? 'stores');

        if (!$id) { $this->redirectWithError($redirect, 'Invalid store ID.'); return; }

        $this->storeModel->toggleStatus($id);
        $this->redirect($redirect);
    }
}
