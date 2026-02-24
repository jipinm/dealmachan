<?php
/**
 * Merchant Authentication Controller
 *
 * POST /api/auth/merchant/login
 * POST /api/auth/merchant/refresh
 * POST /api/auth/merchant/logout
 * POST /api/auth/merchant/forgot-password
 * POST /api/auth/merchant/reset-password
 */
class MerchantAuthController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── POST /api/auth/merchant/login ─────────────────────────────────────────
    public function login(array $body): never {
        $v = new Validator($body);
        $v->required('email', 'Email')
          ->email('email', 'Email')
          ->required('password', 'Password');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $email    = strtolower(trim($body['email']));
        $password = $body['password'];

        // Fetch user + merchant in one query
        $row = $this->db->queryOne(
            "SELECT u.id AS user_id, u.email, u.password_hash, u.status, u.phone,
                    m.id AS merchant_id, m.business_name, m.profile_status,
                    m.subscription_status, m.business_logo
             FROM users u
             JOIN merchants m ON m.user_id = u.id
             WHERE u.email = ? AND u.user_type = 'merchant'
             LIMIT 1",
            [$email]
        );

        if (!$row || !password_verify($password, $row['password_hash'])) {
            Response::error('Invalid email or password.', 401, 'INVALID_CREDENTIALS');
        }

        if ($row['status'] !== 'active') {
            Response::forbidden('Your account has been ' . $row['status'] . '. Please contact support.');
        }

        // Update last login
        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$row['user_id']]
        );

        // Generate token pair
        $accessToken  = JWT::createAccessToken($row['user_id'], $row['email']);
        $refreshToken = JWT::createRefreshToken($row['user_id']);

        // Store refresh token in DB (password_resets table re-purposed as token store, or use a dedicated table)
        $this->storeRefreshToken($row['user_id'], $refreshToken);

        Response::success([
            'tokens' => [
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_in'    => JWT_ACCESS_EXPIRY,
            ],
            'merchant' => [
                'id'                  => $row['merchant_id'],
                'user_id'             => $row['user_id'],
                'email'               => $row['email'],
                'business_name'       => $row['business_name'],
                'phone'               => $row['phone'],
                'logo_url'            => imageUrl($row['business_logo']),
                'profile_status'      => $row['profile_status'],
                'subscription_status' => $row['subscription_status'],
            ],
        ], 'Login successful');
    }

    // ── POST /api/auth/merchant/refresh ───────────────────────────────────────
    public function refresh(array $body): never {
        $refreshToken = $body['refresh_token'] ?? null;

        if (!$refreshToken) {
            Response::error('Refresh token is required.', 400, 'MISSING_REFRESH_TOKEN');
        }

        // Verify token structure + expiry
        try {
            $payload = JWT::verifyRefresh($refreshToken);
        } catch (\RuntimeException $e) {
            Response::unauthorized($e->getMessage());
        }

        // Check token exists in DB (one-time use protection)
        $stored = $this->db->queryOne(
            "SELECT id FROM refresh_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()",
            [$payload['sub'], hash('sha256', $refreshToken)]
        );

        if (!$stored) {
            Response::unauthorized('Refresh token is invalid or has already been used.');
        }

        // Rotate: delete old, issue new pair
        $this->db->execute(
            "DELETE FROM refresh_tokens WHERE user_id = ?",
            [$payload['sub']]
        );

        $user = $this->db->queryOne(
            "SELECT u.id, u.email, u.status FROM users u WHERE u.id = ? AND u.user_type = 'merchant'",
            [$payload['sub']]
        );

        if (!$user || $user['status'] !== 'active') {
            Response::unauthorized('Account no longer active.');
        }

        $newAccessToken  = JWT::createAccessToken($user['id'], $user['email']);
        $newRefreshToken = JWT::createRefreshToken($user['id']);
        $this->storeRefreshToken($user['id'], $newRefreshToken);

        Response::success([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in'    => JWT_ACCESS_EXPIRY,
        ], 'Token refreshed');
    }

    // ── POST /api/auth/merchant/logout ────────────────────────────────────────
    public function logout(array $body): never {
        $refreshToken = $body['refresh_token'] ?? null;

        if ($refreshToken) {
            try {
                $payload = JWT::verifyRefresh($refreshToken);
                $this->db->execute(
                    "DELETE FROM refresh_tokens WHERE user_id = ?",
                    [$payload['sub']]
                );
            } catch (\RuntimeException) {
                // Ignore invalid tokens on logout
            }
        }

        Response::success(null, 'Logged out successfully');
    }

    // ── POST /api/auth/merchant/forgot-password ───────────────────────────────
    public function forgotPassword(array $body): never {
        $v = new Validator($body);
        $v->required('email', 'Email')->email('email', 'Email');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $email = strtolower(trim($body['email']));

        $user = $this->db->queryOne(
            "SELECT u.id FROM users u
             JOIN merchants m ON m.user_id = u.id
             WHERE u.email = ? AND u.user_type = 'merchant' AND u.status = 'active'",
            [$email]
        );

        // Always respond success to prevent email enumeration
        if ($user) {
            $resetToken = JWT::createResetToken($user['id'], $email);
            $expiresAt  = date('Y-m-d H:i:s', time() + 900);

            // Clear old reset tokens for this user
            $this->db->execute(
                "DELETE FROM password_resets WHERE user_id = ?",
                [$user['id']]
            );

            $this->db->execute(
                "INSERT INTO password_resets (user_id, token, expires_at, created_at)
                 VALUES (?, ?, ?, NOW())",
                [$user['id'], hash('sha256', $resetToken), $expiresAt]
            );

            // In production: send email with the reset token/link.
            // For development, return the token directly.
            if (API_ENV === 'development') {
                Response::success([
                    'reset_token' => $resetToken,
                    'note'        => 'Token returned in dev mode only. In production this would be sent via email.',
                ], 'Password reset token generated');
            }
        }

        Response::success(null, 'If that email is registered, a reset link has been sent.');
    }

    // ── POST /api/auth/merchant/reset-password ────────────────────────────────
    public function resetPassword(array $body): never {
        $v = new Validator($body);
        $v->required('token', 'Reset token')
          ->required('password', 'Password')
          ->minLength('password', 8, 'Password')
          ->confirmed('password');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $token    = $body['token'];
        $password = $body['password'];

        // Verify JWT structure
        try {
            $payload = JWT::verify($token);
            if (($payload['type'] ?? '') !== 'reset') {
                throw new \RuntimeException('Invalid token type');
            }
        } catch (\RuntimeException $e) {
            Response::error('Invalid or expired reset token.', 400, 'INVALID_RESET_TOKEN');
        }

        // Check DB
        $stored = $this->db->queryOne(
            "SELECT id FROM password_resets
             WHERE user_id = ? AND token = ? AND used = 0 AND expires_at > NOW()",
            [$payload['sub'], hash('sha256', $token)]
        );

        if (!$stored) {
            Response::error('Reset token is invalid or has expired.', 400, 'INVALID_RESET_TOKEN');
        }

        // Update password
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->db->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$hash, $payload['sub']]
        );

        // Invalidate reset token (mark used)
        $this->db->execute(
            "UPDATE password_resets SET used = 1 WHERE user_id = ? AND token = ?",
            [$payload['sub'], hash('sha256', $token)]
        );

        Response::success(null, 'Password has been reset successfully. Please log in.');
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function storeRefreshToken(int $userId, string $token): void {
        // Upsert — one refresh token per user (single-session)
        $this->db->execute(
            "INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = NOW()",
            [$userId, hash('sha256', $token), date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY)]
        );
    }
}
