<?php
require_once __DIR__ . '/env.php';

// Application
define('API_ENV',      getenv('APP_ENV')    ?: 'development');
define('API_ROOT',     dirname(__DIR__));
define('API_DEBUG',    API_ENV === 'development');

// JWT
define('JWT_SECRET',         getenv('JWT_SECRET')         ?: 'dealmachan-jwt-secret-change-in-prod');
define('JWT_ACCESS_EXPIRY',  (int)(getenv('JWT_ACCESS_EXPIRY_SECONDS')  ?: 900));    // 15 min
define('JWT_REFRESH_EXPIRY', (int)(getenv('JWT_REFRESH_EXPIRY_SECONDS') ?: 604800)); // 7 days

// Database (same DB as admin)
define('DB_HOST',    getenv('DB_HOST')     ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')     ?: 'deal_machan');
define('DB_USER',    getenv('DB_USER')     ?: 'root');
define('DB_PASS',    getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// CORS — allowed origins (admin panel, merchant app, customer app)
// Dev note: CorsMiddleware also allows any localhost:* origin in development mode
define('CORS_ORIGINS', [
    getenv('ADMIN_URL')         ?: 'http://dealmachan-admin.local',
    getenv('MERCHANT_URL')      ?: 'http://localhost:5173',
    getenv('CUSTOMER_URL')      ?: 'http://localhost:5174',
    // Customer app alternate port (Vite may use 5175 if 5174 is taken)
    'http://localhost:5175',
]);

// Upload
define('API_UPLOAD_PATH', API_ROOT . '/uploads');
define('MAX_UPLOAD_SIZE',  5 * 1024 * 1024); // 5 MB
define('ALLOWED_IMG_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE',     100);
