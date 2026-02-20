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
     * Require a valid access token. Terminates with 401 on failure.
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
