<?php
require_once CORE_PATH . '/Auth.php';
require_once MODEL_PATH . '/Admin.php';
require_once MODEL_PATH . '/User.php';
require_once MODEL_PATH . '/PlatformSetting.php';

class SettingsController extends Controller {

    private $auth;
    private $adminModel;
    private $platformSetting;

    public function __construct() {
        $this->auth = new Auth();
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to continue.';
            $this->redirect('auth/login');
            return;
        }
        $this->adminModel      = new Admin();
        $this->platformSetting = new PlatformSetting();
    }

    // ─── PROFILE ──────────────────────────────────────────────────────────────

    public function profile() {
        $current_user = $this->auth->getCurrentUser();
        $admin = $this->adminModel->findWithUser($current_user['admin_id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleProfileUpdate($current_user, $admin);
            return;
        }

        $this->loadView('settings/profile', [
            'title'         => 'Profile Settings',
            'current_user'  => $current_user,
            'admin'         => $admin,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    private function handleProfileUpdate($current_user, $admin) {
        $name         = trim($_POST['name']          ?? '');
        $email        = trim($_POST['email']         ?? '');
        $phone        = trim($_POST['phone']         ?? '');
        $current_pass = $_POST['current_password']   ?? '';
        $new_pass     = $_POST['new_password']       ?? '';
        $confirm_pass = $_POST['confirm_password']   ?? '';

        // Basic validation
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Name and email are required.';
            $this->redirect('settings/profile');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
            $this->redirect('settings/profile');
            return;
        }

        // Check email uniqueness (excluding current user)
        if ($this->adminModel->emailExists($email, $current_user['user_id'])) {
            $_SESSION['error'] = 'That email is already in use by another account.';
            $this->redirect('settings/profile');
            return;
        }

        // Build user update payload
        $userData = [
            'email'      => $email,
            'phone'      => $phone,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Handle password change (optional)
        if (!empty($current_pass) || !empty($new_pass) || !empty($confirm_pass)) {
            if (empty($current_pass)) {
                $_SESSION['error'] = 'Enter your current password to set a new one.';
                $this->redirect('settings/profile');
                return;
            }

            // Verify current password
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$current_user['user_id']]);
            $row  = $stmt->fetch();

            if (!$row || !password_verify($current_pass, $row['password_hash'])) {
                $_SESSION['error'] = 'Current password is incorrect.';
                $this->redirect('settings/profile');
                return;
            }

            if (strlen($new_pass) < 8) {
                $_SESSION['error'] = 'New password must be at least 8 characters.';
                $this->redirect('settings/profile');
                return;
            }

            if ($new_pass !== $confirm_pass) {
                $_SESSION['error'] = 'New password and confirmation do not match.';
                $this->redirect('settings/profile');
                return;
            }

            $userData['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
        }

        // Build admin update payload
        $adminData = [
            'name' => $name,
        ];

        try {
            $this->adminModel->updateAdminWithUser($current_user['admin_id'], $userData, $adminData);

            // Refresh name in session
            $_SESSION['name'] = $name;

            $_SESSION['success'] = 'Profile updated successfully.';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Failed to update profile. Please try again.';
        }

        $this->redirect('settings/profile');
    }

    // ─── PREFERENCES ──────────────────────────────────────────────────────────

    public function preferences() {
        $current_user = $this->auth->getCurrentUser();

        $this->loadView('settings/preferences', [
            'title'        => 'Preferences',
            'current_user' => $current_user,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    // ─── SYSTEM SETTINGS (super_admin only) ───────────────────────────────────

    public function system() {
        $current_user = $this->auth->getCurrentUser();
        if ($current_user['admin_type'] !== 'super_admin') {
            $_SESSION['error'] = 'Access denied.';
            $this->redirect('dashboard');
            return;
        }
        $settings = $this->platformSetting->getAll();
        $this->loadView('settings/system', [
            'title'         => 'System Settings',
            'current_user'  => $current_user,
            'settings'      => $settings,
            'flash_success' => $_SESSION['success'] ?? null,
            'flash_error'   => $_SESSION['error']   ?? null,
        ]);
        unset($_SESSION['success'], $_SESSION['error']);
    }

    public function saveSystem() {
        $current_user = $this->auth->getCurrentUser();
        if ($current_user['admin_type'] !== 'super_admin') {
            http_response_code(403); echo json_encode(['error' => 'Access denied']); return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('settings/system'); return;
        }
        $allowed = [
            'app_name','app_tagline','support_email','support_phone',
            'referral_reward_amount','welcome_bonus_amount','maintenance_mode',
            'min_app_version_ios','min_app_version_android',
            'play_store_url','app_store_url',
            'facebook_url','instagram_url','twitter_url',
            'default_city_id','coupon_expiry_days',
        ];
        $data = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = trim($_POST[$key]);
            }
        }
        if ($this->platformSetting->saveMany($data)) {
            $_SESSION['success'] = 'System settings saved.';
        } else {
            $_SESSION['error'] = 'Failed to save settings.';
        }
        $this->redirect('settings/system');
    }
}
?>
