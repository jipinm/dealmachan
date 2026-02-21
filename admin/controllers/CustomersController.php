<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Customer.php';
require_once MODEL_PATH . '/User.php';
require_once MODEL_PATH . '/City.php';
require_once MODEL_PATH . '/Profession.php';

class CustomersController extends Controller {

    private $auth;
    private $customerModel;

    /** Admin types that are permitted to access this module */
    private const ALLOWED_TYPES = ['super_admin', 'city_admin', 'partner_admin', 'club_admin'];

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

        $this->customerModel = new Customer();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    private const PER_PAGE = 20;

    public function index() {
        $filters = [
            'status'            => $_GET['status']            ?? '',
            'customer_type'     => $_GET['customer_type']     ?? '',
            'registration_type' => $_GET['registration_type'] ?? '',
            'search'            => trim($_GET['search']       ?? ''),
        ];

        $perPage     = self::PER_PAGE;
        $totalCount  = $this->customerModel->countWithDetails($filters);
        $totalPages  = max(1, (int)ceil($totalCount / $perPage));
        $currentPage = max(1, min((int)($_GET['page'] ?? 1), $totalPages));
        $offset      = ($currentPage - 1) * $perPage;

        $fetchFilters = array_merge($filters, ['limit' => $perPage, 'offset' => $offset]);
        $customers    = $this->customerModel->getAllWithDetails($fetchFilters);
        $stats        = $this->customerModel->getStats();

        $this->loadView('customers/index', [
            'title'         => 'Customer Management',
            'customers'     => $customers,
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

    // ─── VIEW PROFILE ─────────────────────────────────────────────────────────

    public function profile() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('customers', 'Invalid customer ID.'); return; }

        $customer = $this->customerModel->findWithDetails($id);
        if (!$customer) { $this->redirectWithError('customers', 'Customer not found.'); return; }

        $redemptions  = $this->customerModel->getRedemptions($id);
        $analytics    = $this->customerModel->getTransactionAnalytics($id);
        $transactions = $this->customerModel->getRecentTransactions($id);
        $storeCoupons = $this->customerModel->getStoreCoupons($id);

        $this->loadView('customers/view', [
            'title'        => 'Customer Profile — ' . escape($customer['name']),
            'customer'     => $customer,
            'redemptions'  => $redemptions,
            'analytics'    => $analytics,
            'transactions' => $transactions,
            'storeCoupons' => $storeCoupons,
            'current_user' => $this->auth->getCurrentUser(),
        ]);
    }


    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $professionModel = new Profession();
        $this->loadView('customers/add', [
            'title'        => 'Add Customer',
            'professions'  => $professionModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirectWithError('customers', 'Invalid customer ID.'); return; }

        $customer = $this->customerModel->findWithDetails($id);
        if (!$customer) { $this->redirectWithError('customers', 'Customer not found.'); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $professionModel = new Profession();
        $this->loadView('customers/edit', [
            'title'        => 'Edit Customer — ' . escape($customer['name']),
            'customer'     => $customer,
            'professions'  => $professionModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── SAVE (shared by add + edit) ─────────────────────────────────────────

    private function handleSave($customerId) {
        $this->requireCSRF();

        $name             = sanitize($_POST['name'] ?? '');
        $email            = strtolower(trim($_POST['email'] ?? ''));
        $phone            = sanitize($_POST['phone'] ?? '');
        $dob              = sanitize($_POST['date_of_birth'] ?? '');
        $gender           = sanitize($_POST['gender'] ?? '');
        $professionId     = !empty($_POST['profession_id']) ? (int)$_POST['profession_id'] : null;
        $customerType     = sanitize($_POST['customer_type'] ?? 'standard');
        $registrationType = sanitize($_POST['registration_type'] ?? 'admin_registration');
        $status           = sanitize($_POST['status'] ?? 'active');
        $password         = $_POST['password'] ?? '';
        $redirect         = $customerId ? "customers/edit?id={$customerId}" : 'customers/add';

        // ── Validation ──
        if (!$name) {
            $_SESSION['error'] = 'Full name is required.';
            $this->redirect($redirect);
            return;
        }
        if (!$email && !$phone) {
            $_SESSION['error'] = 'Either email or phone number is required.';
            $this->redirect($redirect);
            return;
        }
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email address.';
            $this->redirect($redirect);
            return;
        }
        if (!$customerId && strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            $this->redirect($redirect);
            return;
        }
        if ($customerId && !empty($password) && strlen($password) < 8) {
            $_SESSION['error'] = 'New password must be at least 8 characters.';
            $this->redirect($redirect);
            return;
        }

        // Uniqueness checks
        $existingCustomer = $customerId ? $this->customerModel->findWithDetails($customerId) : null;
        $excludeUserId    = $existingCustomer ? $existingCustomer['user_id'] : null;

        if ($email && $this->customerModel->emailExists($email, $excludeUserId)) {
            $_SESSION['error'] = "Email '{$email}' is already registered.";
            $this->redirect($redirect);
            return;
        }
        if ($phone && $this->customerModel->phoneExists($phone, $excludeUserId)) {
            $_SESSION['error'] = "Phone '{$phone}' is already registered.";
            $this->redirect($redirect);
            return;
        }

        $cu = $this->auth->getCurrentUser();

        if ($customerId) {
            // ── UPDATE ──
            $userData = [];
            if ($email) $userData['email']  = $email;
            if ($phone) $userData['phone']  = $phone;
            $userData['status'] = $status;
            if (!empty($password)) {
                $userData['password'] = $password; // model hashes it
            }

            $customerData = [
                'name'              => $name,
                'date_of_birth'     => $dob     ?: null,
                'gender'            => $gender  ?: null,
                'profession_id'     => $professionId,
                'customer_type'     => $customerType,
                'registration_type' => $registrationType,
            ];

            $this->customerModel->updateWithUser($customerId, $userData, $customerData);
            $_SESSION['success'] = "Customer '{$name}' updated successfully.";
            logAudit('update', 'customer', $customerId, ['name' => $name]);
            $this->redirect('customers/profile?id=' . $customerId);
        } else {
            // ── CREATE ──
            $userData = [
                'email'    => $email ?: null,
                'phone'    => $phone ?: null,
                'password' => $password,
                'status'   => $status,
            ];

            $customerData = [
                'name'                  => $name,
                'date_of_birth'         => $dob ?: null,
                'gender'                => $gender ?: null,
                'profession_id'         => $professionId,
                'customer_type'         => $customerType,
                'registration_type'     => $registrationType,
                'created_by_admin_id'   => $cu['admin_id'],
            ];

            $newId = $this->customerModel->createWithUser($userData, $customerData);
            $_SESSION['success'] = "Customer '{$name}' created successfully.";
            logAudit('create', 'customer', $newId, ['name' => $name]);
            $this->redirect('customers/profile?id=' . $newId);
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('customers'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['id'] ?? 0);
        $customer = $this->customerModel->findWithDetails($id);
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found.';
            $this->redirect('customers');
            return;
        }

        $name = $customer['name'];
        $this->customerModel->deleteWithUser($id);
        $_SESSION['success'] = "Customer '{$name}' deleted successfully.";
        logAudit('delete', 'customer', $id, ['name' => $name]);
        $this->redirect('customers');
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('customers'); return; }
        $this->requireCSRF();

        $id     = (int)($_POST['id'] ?? 0);
        $result = $this->customerModel->toggleStatus($id);

        if (is_string($result) && !in_array($result, ['active', 'blocked', 'inactive'])) {
            $_SESSION['error'] = $result;
        } else {
            $label = ucfirst($result);
            $_SESSION['success'] = "Customer status changed to {$label}.";
            logAudit('toggle_status', 'customer', $id, ['new_status' => $result]);
        }

        $referer = $_POST['redirect'] ?? 'customers';
        $this->redirect($referer);
    }

}
?>
