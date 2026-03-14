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
        // Primary path: full merchant user (has a row in merchants table)
        $db   = Database::getInstance();
        $user = $db->queryOne(
            "SELECT u.id, u.email, u.status,
                    m.id AS merchant_id, m.business_name, m.profile_status,
                    m.subscription_plan
             FROM users u
             JOIN merchants m ON m.user_id = u.id
             WHERE u.id = ? AND u.user_type = 'merchant'",
            [$payload['sub']]
        );

        // Fallback path: store sub-user (has a row in merchant_store_users, not merchants)
        if (!$user) {
            $user = $db->queryOne(
                "SELECT u.id, u.email, u.status,
                        m.id AS merchant_id, m.business_name, m.profile_status,
                        m.subscription_plan,
                        msu.store_id, msu.access_scope
                 FROM users u
                 JOIN merchant_store_users msu ON msu.user_id = u.id
                 JOIN merchants m ON m.id = msu.merchant_id
                 WHERE u.id = ? AND u.user_type = 'merchant'
                   AND msu.status = 'active'",
                [$payload['sub']]
            );
        }

        if (!$user) {
            Response::unauthorized('Merchant account not found.');
        }

        if ($user['status'] !== 'active') {
            Response::forbidden('Your account has been ' . $user['status'] . '. Please contact support.');
        }

        // Merge JWT claims; for store sub-users the DB query already supplies
        // access_scope and store_id, but fall back to JWT claims if absent.
        $merged = array_merge($payload, $user);
        if (empty($merged['access_scope'])) {
            $merged['access_scope'] = $payload['access_scope'] ?? 'merchant';
        }
        if (!isset($merged['store_id'])) {
            $merged['store_id'] = $payload['store_id'] ?? null;
        }

        self::$currentUser = $merged;
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
     * Require a valid token scoped to a specific STORE (partial merchant access).
     * Accepts both merchant-scope and store-scope tokens and enforces store_id
     * from the JWT claim rather than a request body parameter.
     *
     * Returns the user array with 'store_id' guaranteed.
     */
    public static function requireStore(): array {
        $user = self::require(); // validates merchant token itself

        $storeId = $user['store_id'] ?? null;
        if (!$storeId) {
            // Full-merchant tokens can access any store; store-scope tokens are restricted
            // to the one store in their JWT.  Either way, a store_id query/route param
            // must ultimately be provided by the caller when scoping store actions.
            return $user;
        }

        // Confirm the store belongs to this merchant and is not deleted
        $db    = Database::getInstance();
        $store = $db->queryOne(
            "SELECT id, merchant_id, store_name, status FROM stores WHERE id = ? AND deleted_at IS NULL",
            [$storeId]
        );

        if (!$store) {
            Response::forbidden('The store attached to your account is no longer available.');
        }

        if ((int)$store['merchant_id'] !== (int)$user['merchant_id']) {
            Response::forbidden('Store does not belong to your merchant account.');
        }

        $user['scoped_store'] = $store;
        self::$currentUser    = $user;
        return $user;
    }

    /**
     * Get the authenticated user payload (call after require()).
     */
    public static function user(): ?array {
        return self::$currentUser;
    }

    /**
     * Verify the authenticated customer has an active, non-expired loyalty card.
     * Call at the top of endpoints that require a card (coupon wallet, redeem, contests, etc.).
     * Returns the card row on success; terminates with 403 on failure.
     *
     * Do NOT call on: grievances, reviews/ratings, profile, and public endpoints.
     */
    public static function requireActiveCard(array $user): array {
        $db   = Database::getInstance();
        $card = $db->queryOne(
            "SELECT id, expiry_date FROM cards
             WHERE assigned_to_customer_id = ? AND status = 'activated' LIMIT 1",
            [$user['customer_id']]
        );
        if (!$card) {
            Response::error('A loyalty card is required to access this feature.', 403, ['code' => 'no_card']);
        }
        if ($card['expiry_date'] && $card['expiry_date'] < date('Y-m-d')) {
            Response::error('Your loyalty card has expired. Please renew or select a new card.', 403, ['code' => 'card_expired']);
        }
        return $card;
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
