<?php
/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data ?? '')), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape output for display
 */
function escape($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time']) 
        || (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date for display
 */
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        STATUS_ACTIVE => '<span class="badge bg-success">Active</span>',
        STATUS_INACTIVE => '<span class="badge bg-secondary">Inactive</span>',
        STATUS_BLOCKED => '<span class="badge bg-danger">Blocked</span>',
        STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
        STATUS_APPROVED => '<span class="badge bg-success">Approved</span>',
        STATUS_REJECTED => '<span class="badge bg-danger">Rejected</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . escape($status) . '</span>';
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Indian format)
 */
function isValidPhone($phone) {
    return preg_match('/^[6-9]\d{9}$/', $phone);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === USER_TYPE_ADMIN;
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current admin ID
 */
function getCurrentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'auth/login');
        exit();
    }
}

/**
 * Redirect if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'auth/login');
        exit();
    }
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalFilename) {
    $extension = getFileExtension($originalFilename);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Log audit trail
 */
function logAudit($action, $tableName, $recordId = null, $newValues = null, $oldValues = null) {
    try {
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO audit_logs (user_id, user_type, action, table_name, record_id, old_values, new_values, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $_SESSION['user_id']   ?? null,
            'admin',
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}
