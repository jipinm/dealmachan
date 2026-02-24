<?php
/**
 * Auth Middleware
 *
 * Extracts and verifies the Bearer JWT from the Authorization header.
 * On success, injects the payload as a superglobal-like array available
 * to all controllers via AuthMiddleware::user().
 */
class AuthMiddleware {

    private static ?array $currentUser = null;

    /**
     * Optionally verify the token — returns true if authenticated, false if not.
     * Never terminates. Safe to call on public endpoints that want to customise
     * their response based on whether the caller is logged in.
     */
    public static function optional(): bool {
        $token = self::extractToken();
        if (!$token) return false;

        try {
            $payload = JWT::verifyAccess($token);
        } catch (\RuntimeException) {
            return false;
        }

        self::$currentUser = $payload;
        return true;
    }

    /**
     * Require a valid access token for a MERCHANT. Terminates with 401 on failure.
     */
    public static function require(): array {
        $token = self::extractToken();

        if (!$token) {
            Response::unauthorized('No authentication token provided.');
        }

        try {
            $payload = JWT::verifyAccess($token);
        } catch (\RuntimeException $e) {
            Response::unauthorized($e->getMessage());
        }

        // Verify merchant still exists and is active in DB
        $db   = Database::getInstance();
        $user = $db->queryOne(
            "SELECT u.id, u.email, u.status, m.id AS merchant_id, m.business_name, m.profile_status
             FROM users u
             JOIN merchants m ON m.user_id = u.id
             WHERE u.id = ? AND u.user_type = 'merchant'",
            [$payload['sub']]
        );

        if (!$user) {
            Response::unauthorized('Merchant account not found.');
        }

        if ($user['status'] !== 'active') {
            Response::forbidden('Your account has been ' . $user['status'] . '. Please contact support.');
        }

        self::$currentUser = array_merge($payload, $user);
        return self::$currentUser;
    }

    /**
     * Require a valid access token for a CUSTOMER. Terminates with 401 on failure.
     */
    public static function requireCustomer(): array {
        $token = self::extractToken();

        if (!$token) {
            Response::unauthorized('No authentication token provided.');
        }

        try {
            $payload = JWT::verifyAccess($token);
        } catch (\RuntimeException $e) {
            Response::unauthorized($e->getMessage());
        }

        // Verify customer still exists and is active in DB
        $db   = Database::getInstance();
        $user = $db->queryOne(
            "SELECT u.id, u.email, u.phone, u.status,
                    c.id AS customer_id, c.name, c.customer_type,
                    c.subscription_status, c.is_dealmaker
             FROM users u
             JOIN customers c ON c.user_id = u.id
             WHERE u.id = ? AND u.user_type = 'customer'",
            [$payload['sub']]
        );

        if (!$user) {
            Response::unauthorized('Customer account not found.');
        }

        if ($user['status'] !== 'active') {
            Response::forbidden('Your account has been ' . $user['status'] . '. Please contact support.');
        }

        self::$currentUser = array_merge($payload, $user);
        return self::$currentUser;
    }

    /**
     * Get the authenticated user payload (call after require()).
     */
    public static function user(): ?array {
        return self::$currentUser;
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private static function extractToken(): ?string {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return $m[1];
        }

        // Fallback: allow token as query param in dev (for browser testing)
        if (API_ENV === 'development' && isset($_GET['token'])) {
            return $_GET['token'];
        }

        return null;
    }
}
