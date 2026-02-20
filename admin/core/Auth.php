<?php
require_once CORE_PATH . '/Database.php';
require_once HELPER_PATH . '/Logger.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate user with email and password
     */
    public function login($email, $password) {
        Logger::info("Login attempt started", ['email' => $email]);
        
        try {
            $pdo = $this->db->getConnection();
            
            Logger::debug("Database connection established");
            
            // First check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND user_type = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            Logger::debug("User query executed", [
                'email' => $email,
                'user_found' => $user ? 'yes' : 'no'
            ]);
            
            if (!$user) {
                Logger::warning("Login failed - User not found", ['email' => $email]);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            Logger::debug("User details retrieved", [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'user_type' => $user['user_type'],
                'status' => $user['status'],
                'password_hash_length' => strlen($user['password_hash'])
            ]);
            
            // Verify password
            $passwordVerified = password_verify($password, $user['password_hash']);
            Logger::debug("Password verification", [
                'verified' => $passwordVerified ? 'yes' : 'no',
                'password_length' => strlen($password),
                'hash_algorithm' => password_get_info($user['password_hash'])
            ]);
            
            if (!$passwordVerified) {
                Logger::warning("Login failed - Invalid password", ['email' => $email]);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                Logger::warning("Login failed - Account inactive", [
                    'email' => $email,
                    'status' => $user['status']
                ]);
                return ['success' => false, 'message' => 'Account is inactive. Contact administrator.'];
            }
            
            // Get admin details
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            $admin = $stmt->fetch();
            
            Logger::debug("Admin query executed", [
                'user_id' => $user['id'],
                'admin_found' => $admin ? 'yes' : 'no'
            ]);
            
            if (!$admin) {
                Logger::error("Login failed - Admin profile not found", [
                    'email' => $email,
                    'user_id' => $user['id']
                ]);
                return ['success' => false, 'message' => 'Admin profile not found'];
            }
            
            Logger::debug("Admin details retrieved", [
                'admin_id' => $admin['id'],
                'admin_type' => $admin['admin_type'],
                'city_id' => $admin['city_id']
            ]);
            
            // Create session
            $this->createSession($user, $admin);
            Logger::info("Session created successfully", [
                'user_id' => $user['id'],
                'admin_id' => $admin['id']
            ]);
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            Logger::info("Login successful", [
                'email' => $email,
                'user_id' => $user['id'],
                'admin_id' => $admin['id']
            ]);
            
            return ['success' => true, 'message' => 'Login successful'];
            
        } catch (Exception $e) {
            Logger::critical("Login exception occurred", [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    /**
     * Create user session
     */
    private function createSession($user, $admin) {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_type'] = $admin['admin_type'];
        $_SESSION['name'] = isset($admin['name']) ? $admin['name'] : 'Administrator';
        $_SESSION['city_id'] = $admin['city_id'];
        $_SESSION['is_logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Get current user info
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'admin_id' => $_SESSION['admin_id'],
            'admin_type' => $_SESSION['admin_type'],
            'name' => $_SESSION['name'],
            'city_id' => $_SESSION['city_id']
        ];
    }
    
    /**
     * Check if user has required permissions
     */
    public function hasPermission($required_type) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $admin_type = $_SESSION['admin_type'];
        
        // Super admin has all permissions
        if ($admin_type === 'super_admin') {
            return true;
        }
        
        // Define permission hierarchy
        $permissions = [
            'super_admin' => ['super_admin', 'city_admin', 'sales_admin', 'promoter_admin', 'partner_admin', 'club_admin'],
            'city_admin' => ['city_admin', 'sales_admin', 'promoter_admin'],
            'sales_admin' => ['sales_admin'],
            'promoter_admin' => ['promoter_admin'],
            'partner_admin' => ['partner_admin'],
            'club_admin' => ['club_admin']
        ];
        
        return in_array($required_type, $permissions[$admin_type] ?? []);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Destroy session
        session_destroy();
        
        // Start new session
        session_start();
        
        return true;
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) || 
            (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>