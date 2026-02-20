<?php
require_once CORE_PATH . '/Auth.php';
require_once HELPER_PATH . '/Logger.php';

class AuthController extends Controller {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    /**
     * Show login page
     */
    public function index() {
        $this->login();
    }
    
    /**
     * Show login page
     */
    public function login() {
        // If already logged in, redirect to dashboard
        if ($this->auth->isLoggedIn()) {
            $this->redirect('dashboard');
            return;
        }
        
        $data = [
            'title' => 'Admin Login - Deal Machan',
            'csrf_token' => $this->auth->generateCSRFToken(),
            'error' => $_SESSION['error'] ?? null,
            'success' => $_SESSION['success'] ?? null
        ];
        
        // Clear flash messages
        unset($_SESSION['error'], $_SESSION['success']);
        
        $this->loadView('auth/login', $data);
    }
    
    /**
     * Process login form
     */
    public function processLogin() {
        Logger::info("ProcessLogin called", [
            'method' => $_SERVER['REQUEST_METHOD'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::warning("ProcessLogin - Invalid request method", [
                'method' => $_SERVER['REQUEST_METHOD']
            ]);
            $this->redirect('auth/login');
            return;
        }
        
        // Verify CSRF token
        $csrf_token = $_POST['csrf_token'] ?? '';
        Logger::debug("CSRF token verification", [
            'token_provided' => !empty($csrf_token),
            'token_length' => strlen($csrf_token)
        ]);
        
        if (!$this->auth->verifyCSRFToken($csrf_token)) {
            Logger::warning("ProcessLogin - CSRF token verification failed");
            $_SESSION['error'] = 'Invalid security token. Please try again.';
            $this->redirect('auth/login');
            return;
        }
        
        // Get form data
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        Logger::debug("Form data received", [
            'email' => $email,
            'password_length' => strlen($password),
            'email_valid' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        ]);
        
        // Validate input
        if (empty($email) || empty($password)) {
            Logger::warning("ProcessLogin - Empty credentials", [
                'email_empty' => empty($email),
                'password_empty' => empty($password)
            ]);
            $_SESSION['error'] = 'Please enter both email and password.';
            $this->redirect('auth/login');
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Logger::warning("ProcessLogin - Invalid email format", ['email' => $email]);
            $_SESSION['error'] = 'Please enter a valid email address.';
            $this->redirect('auth/login');
            return;
        }
        
        // Attempt login
        Logger::info("Calling Auth->login()", ['email' => $email]);
        $result = $this->auth->login($email, $password);
        
        Logger::info("Login result received", [
            'success' => $result['success'],
            'message' => $result['message']
        ]);
        
        if ($result['success']) {
            Logger::info("Login successful - Redirecting to dashboard");
            // Redirect to dashboard
            $this->redirect('dashboard');
        } else {
            Logger::warning("Login failed - Redirecting to login page", [
                'error_message' => $result['message']
            ]);
            $_SESSION['error'] = $result['message'];
            $this->redirect('auth/login');
        }
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $this->auth->logout();
        $_SESSION['success'] = 'You have been logged out successfully.';
        $this->redirect('auth/login');
    }
    
    /**
     * Show forgot password page
     */
    public function forgotPassword() {
        $data = [
            'title' => 'Forgot Password - Deal Machan Admin',
            'csrf_token' => $this->auth->generateCSRFToken()
        ];
        
        $this->loadView('auth/forgot-password', $data);
    }
    
    /**
     * Check if user is authenticated (middleware function)
     */
    public function requireAuth() {
        if (!$this->auth->isLoggedIn()) {
            $_SESSION['error'] = 'Please login to access this page.';
            $this->redirect('auth/login');
            return false;
        }
        return true;
    }
    
    /**
     * Check if user has required admin type
     */
    public function requireAdminType($required_type) {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->auth->hasPermission($required_type)) {
            $_SESSION['error'] = 'You do not have permission to access this page.';
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
}
?>