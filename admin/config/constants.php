<?php
// Load environment variables
require_once __DIR__ . '/env.php';

// Environment
define('ENVIRONMENT', getenv('APP_ENV') ?: 'development');

// Base URL
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/deal-machan-admin/');

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Upload directories
define('PROFILE_UPLOAD_DIR', UPLOAD_PATH . '/profiles/');
define('MERCHANT_UPLOAD_DIR', UPLOAD_PATH . '/merchants/');
define('CARD_UPLOAD_DIR', UPLOAD_PATH . '/cards/');
define('GALLERY_UPLOAD_DIR', UPLOAD_PATH . '/gallery/');

// File upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);

// Pagination
define('RECORDS_PER_PAGE', 25);

// Date format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');
define('DISPLAY_DATETIME_FORMAT', 'd/m/Y h:i A');

// User types
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_MERCHANT', 'merchant');
define('USER_TYPE_CUSTOMER', 'customer');

// Admin types
define('ADMIN_TYPE_SUPER', 'super_admin');
define('ADMIN_TYPE_CITY', 'city_admin');
define('ADMIN_TYPE_SALES', 'sales_admin');
define('ADMIN_TYPE_PROMOTER', 'promoter_admin');
define('ADMIN_TYPE_PARTNER', 'partner_admin');
define('ADMIN_TYPE_CLUB', 'club_admin');

// Status constants
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_BLOCKED', 'blocked');
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');

// Customer types
define('CUSTOMER_TYPE_STANDARD', 'standard');
define('CUSTOMER_TYPE_PREMIUM', 'premium');
define('CUSTOMER_TYPE_DEALMAKER', 'dealmaker');

// Registration types
define('REG_TYPE_SELF', 'self_registration');
define('REG_TYPE_MERCHANT', 'merchant_app');
define('REG_TYPE_ADMIN', 'admin_registration');
define('REG_TYPE_PREPRINTED', 'preprinted_card');
define('REG_TYPE_AUTO', 'auto_profile');

// Discount types
define('DISCOUNT_TYPE_PERCENTAGE', 'percentage');
define('DISCOUNT_TYPE_FIXED', 'fixed');

// Error messages
define('ERROR_GENERIC', 'An error occurred. Please try again.');
define('ERROR_UNAUTHORIZED', 'Unauthorized access.');
define('ERROR_NOT_FOUND', 'Resource not found.');
define('ERROR_VALIDATION', 'Please check your input and try again.');

// Success messages
define('SUCCESS_CREATED', 'Record created successfully.');
define('SUCCESS_UPDATED', 'Record updated successfully.');
define('SUCCESS_DELETED', 'Record deleted successfully.');

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

// CSRF token expiry (1 hour)
define('CSRF_TOKEN_EXPIRY', 3600);

// Password settings
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Rate limiting
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// API settings (for future API integration)
define('API_URL', getenv('API_URL') ?: 'http://localhost:8000/api');
define('MERCHANT_APP_URL', getenv('MERCHANT_URL') ?: 'http://localhost:5173');
define('CUSTOMER_APP_URL', getenv('CUSTOMER_URL') ?: 'http://localhost:5174');

// Email settings
define('FROM_EMAIL', getenv('FROM_EMAIL') ?: 'noreply@dealmachan.com');
define('FROM_NAME', 'Deal Machan Admin');
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.sendgrid.net');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');

// Logging
define('LOG_PATH', ROOT_PATH . '/logs/');
define('ERROR_LOG_FILE', LOG_PATH . 'error.log');
define('ACCESS_LOG_FILE', LOG_PATH . 'access.log');
define('AUDIT_LOG_FILE', LOG_PATH . 'audit.log');

// Error reporting based on environment
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
}
