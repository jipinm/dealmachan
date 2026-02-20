<?php
/**
 * CORS Middleware
 * Handles preflight requests and attaches CORS headers.
 */
class CorsMiddleware {

    public static function handle(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check against allowed origins
        if (in_array($origin, CORS_ORIGINS, true)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } elseif (API_ENV === 'development') {
            // In development allow any localhost origin
            if (preg_match('#^https?://localhost(:\d+)?$#', $origin)) {
                header("Access-Control-Allow-Origin: {$origin}");
            }
        }

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept');
        header('Access-Control-Max-Age: 86400');

        // Preflight — respond immediately with 204
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit();
        }
    }
}
