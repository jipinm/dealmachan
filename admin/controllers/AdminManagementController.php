<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Admin.php';
require_once MODEL_PATH . '/User.php';
require_once MODEL_PATH . '/City.php';

class AdminManagementController extends Controller {

    private $auth;
    private $adminModel;

    private const ADMIN_TYPES = [
        'super_admin'    => 'Super Admin',
        'city_admin'     => 'City Admin',
        'sales_admin'    => 'Sales Admin',
        'promoter_admin' => 'Promoter Admin',
        'partner_admin'  => 'Partner Admin',
        'club_admin'     => 'Club Admin',
    ];

    public function __construct() {
        $this->auth = new Auth();
        if (!$this->auth->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'auth/login');
            exit();
        }
        $cu = $this->auth->getCurrentUser();
        if ($cu['admin_type'] !== 'super_admin') {
            $_SESSION['error'] = 'Access denied. Super Admin only.';
            header('Location: ' . BASE_URL . 'dashboard');
            exit();
        }
        $this->adminModel = new Admin();
    }

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function index() {
        $filterType = $_GET['type'] ?? '';
        $admins     = $this->adminModel->getAllWithUsers($filterType ?: null);
        $stats      = $this->adminModel->getStatistics();
        $statsMap   = [];
        foreach ($stats as $s) {
            $statsMap[$s['admin_type']] = $s;
        }
        $totalAdmins  = array_sum(array_column($stats, 'count'));
        $totalActive  = array_sum(array_column($stats, 'active_count'));

        $this->loadView('admin-management/index', [
            'title'        => 'Admin Management',
            'admins'       => $admins,
            'statsMap'     => $statsMap,
            'totalAdmins'  => $totalAdmins,
            'totalActive'  => $totalActive,
            'adminTypes'   => self::ADMIN_TYPES,
            'filterType'   => $filterType,
            'current_user' => $this->auth->getCurrentUser(),
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ─── ADD ──────────────────────────────────────────────────────────────────

    public function add() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave(null);
            return;
        }

        $cityModel = new City();
        $this->loadView('admin-management/add', [
            'title'        => 'Add Admin',
            'adminTypes'   => self::ADMIN_TYPES,
            'cities'       => $cityModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────

    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            $_SESSION['error'] = 'Invalid admin ID.';
            $this->redirect('admin-management');
            return;
        }

        $admin = $this->adminModel->findWithUser($id);
        if (!$admin) {
            $_SESSION['error'] = 'Admin not found.';
            $this->redirect('admin-management');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSave($id);
            return;
        }

        $cityModel = new City();
        $this->loadView('admin-management/edit', [
            'title'        => 'Edit Admin &mdash; ' . escape($admin['name'] ?: $admin['email']),
            'admin'        => $admin,
            'adminTypes'   => self::ADMIN_TYPES,
            'cities'       => $cityModel->getActive(),
            'current_user' => $this->auth->getCurrentUser(),
            'flash_error'  => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['error']);
    }

    // ─── SAVE (shared by add + edit) ──────────────────────────────────────────

    private function handleSave($adminId) {
        $this->requireCSRF();

        $name       = sanitize($_POST['name'] ?? '');
        $email      = strtolower(trim($_POST['email'] ?? ''));
        $phone      = sanitize($_POST['phone'] ?? '');
        $adminType  = sanitize($_POST['admin_type'] ?? '');
        $status     = sanitize($_POST['status'] ?? 'active');
        $cityId     = !empty($_POST['city_id'])     ? (int)$_POST['city_id']     : null;
        $managedBy  = !empty($_POST['managed_by'])  ? (int)$_POST['managed_by']  : null;
        $customerCap= !empty($_POST['customer_cap'])? (int)$_POST['customer_cap']: null;
        $password   = $_POST['password'] ?? '';
        $redirect   = $adminId ? "admin-management/edit?id={$adminId}" : 'admin-management/add';

        // Validation
        if (!$name) {
            $_SESSION['error'] = 'Name is required.';
            $this->redirect($redirect);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'A valid email address is required.';
            $this->redirect($redirect);
            return;
        }
        if (!array_key_exists($adminType, self::ADMIN_TYPES)) {
            $_SESSION['error'] = 'Invalid admin type selected.';
            $this->redirect($redirect);
            return;
        }
        if (!$adminId && strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            $this->redirect($redirect);
            return;
        }
        if ($adminId && !empty($password) && strlen($password) < 8) {
            $_SESSION['error'] = 'New password must be at least 8 characters.';
            $this->redirect($redirect);
            return;
        }

        // Check email uniqueness
        $existingAdmin = $adminId ? $this->adminModel->findWithUser($adminId) : null;
        $excludeUserId = $existingAdmin ? $existingAdmin['user_id'] : null;
        if ($this->adminModel->emailExists($email, $excludeUserId)) {
            $_SESSION['error'] = "Email '{$email}' is already in use.";
            $this->redirect($redirect);
            return;
        }

        $cu = $this->auth->getCurrentUser();

        if ($adminId) {
            // ── UPDATE ──
            $userData  = ['email' => $email, 'phone' => $phone, 'status' => $status];
            if (!empty($password)) {
                $userData['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $adminData = [
                'name'                => $name,
                'admin_type'          => $adminType,
                'city_id'             => $cityId,
                'managed_by_admin_id' => $managedBy,
                'customer_cap'        => $customerCap,
            ];
            $this->adminModel->updateAdminWithUser($adminId, $userData, $adminData);
            $_SESSION['success'] = "Admin '{$name}' updated successfully.";
            logAudit('update', 'admin', $adminId, ['name' => $name, 'admin_type' => $adminType]);
            $this->redirect('admin-management');
        } else {
            // ── CREATE ──
            $userData  = [
                'email'         => $email,
                'phone'         => $phone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'user_type'     => 'admin',
                'status'        => $status,
            ];
            $adminData = [
                'name'                => $name,
                'admin_type'          => $adminType,
                'city_id'             => $cityId,
                'managed_by_admin_id' => $managedBy,
                'customer_cap'        => $customerCap,
            ];
            $newId = $this->adminModel->createAdminWithUser($userData, $adminData);
            $_SESSION['success'] = "Admin '{$name}' created successfully.";
            logAudit('create', 'admin', $newId, ['name' => $name, 'admin_type' => $adminType]);
            $this->redirect('admin-management');
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('admin-management'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['id'] ?? 0);
        $cu = $this->auth->getCurrentUser();

        $check = $this->adminModel->canDelete($id, $cu['admin_id']);
        if ($check !== true) {
            $_SESSION['error'] = $check;
            $this->redirect('admin-management');
            return;
        }

        $admin = $this->adminModel->findWithUser($id);
        $this->adminModel->deleteWithUser($id);
        $_SESSION['success'] = "Admin '{$admin['name']}' deleted successfully.";
        logAudit('delete', 'admin', $id, ['name' => $admin['name']]);
        $this->redirect('admin-management');
    }

    // ─── TOGGLE STATUS ────────────────────────────────────────────────────────

    public function toggle() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('admin-management'); return; }
        $this->requireCSRF();

        $id = (int)($_POST['id'] ?? 0);
        $cu = $this->auth->getCurrentUser();

        $result = $this->adminModel->toggleStatus($id, $cu['admin_id']);
        if (is_string($result)) {
            $_SESSION['error'] = $result;
        } else {
            $_SESSION['success'] = "Admin status changed to {$result}.";
            logAudit('toggle_status', 'admin', $id, ['new_status' => $result]);
        }
        $this->redirect('admin-management');
    }

    // ─── RESET PASSWORD ───────────────────────────────────────────────────────

    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('admin-management'); return; }
        $this->requireCSRF();

        $id          = (int)($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if (strlen($newPassword) < 8) {
            $_SESSION['error'] = 'New password must be at least 8 characters.';
            $this->redirect('admin-management');
            return;
        }

        $this->adminModel->resetPassword($id, $newPassword);
        $admin = $this->adminModel->findWithUser($id);
        $_SESSION['success'] = "Password reset successfully for '{$admin['name']}'.";
        logAudit('reset_password', 'admin', $id);
        $this->redirect('admin-management');
    }

}
