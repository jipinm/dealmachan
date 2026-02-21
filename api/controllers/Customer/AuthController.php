<?php
/**
 * Customer Authentication Controller
 *
 * POST /api/auth/customer/register
 * POST /api/auth/customer/login
 * POST /api/auth/customer/verify-otp
 * POST /api/auth/customer/resend-otp
 * POST /api/auth/customer/forgot-password
 * POST /api/auth/customer/reset-password
 * POST /api/auth/customer/refresh
 * POST /api/auth/customer/logout
 */
class CustomerAuthController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── POST /api/auth/customer/register ──────────────────────────────────────
    public function register(array $body): never {
        $v = new Validator($body);
        $v->required('name',  'Name')
          ->required('email', 'Email')->email('email', 'Email')
          ->required('phone', 'Phone')
          ->required('password', 'Password')->minLength('password', 8, 'Password');

        if ($v->fails()) Response::validationError($v->errors());

        $name     = trim($body['name']);
        $email    = strtolower(trim($body['email']));
        $phone    = preg_replace('/\D/', '', $body['phone']);
        $password = $body['password'];
        $referralCode = trim($body['referral_code'] ?? '');

        // Duplicate checks
        if ($this->db->queryOne("SELECT id FROM users WHERE email = ?", [$email])) {
            Response::error('An account with this email already exists.', 409, 'EMAIL_EXISTS');
        }
        if ($this->db->queryOne("SELECT id FROM users WHERE phone = ?", [$phone])) {
            Response::error('An account with this phone number already exists.', 409, 'PHONE_EXISTS');
        }

        // Validate referral code
        $referrerId = null;
        if ($referralCode) {
            $referrer = $this->db->queryOne(
                "SELECT id FROM customers WHERE referral_code = ?",
                [$referralCode]
            );
            if ($referrer) $referrerId = $referrer['id'];
        }

        // Generate OTP
        $otp       = (string)random_int(100000, 999999);
        $otpExpiry = date('Y-m-d H:i:s', time() + 600); // 10 min

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->execute(
            "INSERT INTO users (email, phone, password_hash, user_type, status, otp_code, otp_expiry, created_at)
             VALUES (?, ?, ?, 'customer', 'pending', ?, ?, NOW())",
            [$email, $phone, $hash, $otp, $otpExpiry]
        );
        $userId = $this->db->lastInsertId();

        // Generate unique referral code for new customer
        $newReferralCode = $this->generateReferralCode($userId);

        $this->db->execute(
            "INSERT INTO customers (user_id, name, registration_type, customer_type, referral_code, referred_by, created_at)
             VALUES (?, ?, 'self_registration', 'standard', ?, ?, NOW())",
            [$userId, $name, $newReferralCode, $referrerId]
        );

        // In production: send OTP via SMS here.
        $response = ['phone' => $phone, 'expires_in' => 600];
        if (API_ENV === 'development') {
            $response['otp'] = $otp;
            $response['note'] = 'OTP returned in development mode only';
        }

        Response::success($response, 'OTP sent to your phone. Please verify.', 201);
    }

    // ── POST /api/auth/customer/login ─────────────────────────────────────────
    public function login(array $body): never {
        $v = new Validator($body);
        $v->required('login',    'Email or phone')
          ->required('password', 'Password');

        if ($v->fails()) Response::validationError($v->errors());

        $login    = trim($body['login']);
        $password = $body['password'];

        // Accept email or phone
        $isPhone  = preg_match('/^\d{10}$/', preg_replace('/\D/', '', $login));
        $loginCol = $isPhone ? 'u.phone' : 'u.email';
        $loginVal = $isPhone ? preg_replace('/\D/', '', $login) : strtolower($login);

        $row = $this->db->queryOne(
            "SELECT u.id AS user_id, u.email, u.phone, u.password_hash, u.status,
                    c.id AS customer_id, c.name, c.profile_image, c.city_id, c.area_id,
                    c.customer_type, c.subscription_status, c.subscription_expiry,
                    c.is_dealmaker, c.referral_code
             FROM users u
             JOIN customers c ON c.user_id = u.id
             WHERE {$loginCol} = ? AND u.user_type = 'customer'
             LIMIT 1",
            [$loginVal]
        );

        if (!$row || !password_verify($password, $row['password_hash'])) {
            Response::error('Invalid credentials.', 401, 'INVALID_CREDENTIALS');
        }

        if ($row['status'] === 'pending') {
            Response::error('Phone number not verified. Please verify your OTP.', 403, 'UNVERIFIED_PHONE');
        }

        if ($row['status'] !== 'active') {
            Response::forbidden('Your account has been ' . $row['status'] . '. Please contact support.');
        }

        $this->db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$row['user_id']]
        );

        $accessToken  = JWT::createAccessToken($row['user_id'], $row['email'] ?? $row['phone']);
        $refreshToken = JWT::createRefreshToken($row['user_id']);
        $this->storeRefreshToken($row['user_id'], $refreshToken);

        // is_new_user: city_id is null → hasn't completed onboarding
        $isNewUser = empty($row['city_id']);

        Response::success([
            'customer'      => $this->formatCustomer($row, $isNewUser),
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => JWT_ACCESS_EXPIRY,
        ], 'Login successful');
    }

    // ── POST /api/auth/customer/verify-otp ───────────────────────────────────
    public function verifyOtp(array $body): never {
        $v = new Validator($body);
        $v->required('phone', 'Phone')
          ->required('otp',   'OTP');

        if ($v->fails()) Response::validationError($v->errors());

        $phone = preg_replace('/\D/', '', $body['phone']);
        $otp   = trim($body['otp']);

        $row = $this->db->queryOne(
            "SELECT u.id AS user_id, u.email, u.phone, u.otp_code, u.otp_expiry, u.status,
                    c.id AS customer_id, c.name, c.profile_image, c.city_id, c.area_id,
                    c.customer_type, c.subscription_status, c.subscription_expiry,
                    c.is_dealmaker, c.referral_code
             FROM users u
             JOIN customers c ON c.user_id = u.id
             WHERE u.phone = ? AND u.user_type = 'customer'
             LIMIT 1",
            [$phone]
        );

        if (!$row) {
            Response::notFound('No account found with this phone number.');
        }

        if ($row['status'] === 'active') {
            Response::error('Phone already verified. Please login.', 409, 'ALREADY_VERIFIED');
        }

        if ($row['otp_code'] !== $otp) {
            Response::error('Invalid OTP.', 400, 'INVALID_OTP');
        }

        if (strtotime($row['otp_expiry']) < time()) {
            Response::error('OTP has expired. Please request a new one.', 400, 'OTP_EXPIRED');
        }

        // Activate user and clear OTP
        $this->db->execute(
            "UPDATE users SET status = 'active', otp_code = NULL, otp_expiry = NULL,
             last_login = NOW() WHERE id = ?",
            [$row['user_id']]
        );

        $accessToken  = JWT::createAccessToken($row['user_id'], $row['email'] ?? $phone);
        $refreshToken = JWT::createRefreshToken($row['user_id']);
        $this->storeRefreshToken($row['user_id'], $refreshToken);

        Response::success([
            'customer'      => $this->formatCustomer($row, true), // is_new_user = true (no city yet)
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in'    => JWT_ACCESS_EXPIRY,
        ], 'Phone verified successfully');
    }

    // ── POST /api/auth/customer/resend-otp ───────────────────────────────────
    public function resendOtp(array $body): never {
        $v = new Validator($body);
        $v->required('phone', 'Phone');

        if ($v->fails()) Response::validationError($v->errors());

        $phone = preg_replace('/\D/', '', $body['phone']);

        $user = $this->db->queryOne(
            "SELECT id, status FROM users WHERE phone = ? AND user_type = 'customer'",
            [$phone]
        );

        if (!$user) Response::notFound('No account found with this phone number.');

        if ($user['status'] === 'active') {
            Response::error('Phone already verified.', 409, 'ALREADY_VERIFIED');
        }

        $otp       = (string)random_int(100000, 999999);
        $otpExpiry = date('Y-m-d H:i:s', time() + 600);

        $this->db->execute(
            "UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?",
            [$otp, $otpExpiry, $user['id']]
        );

        // In production: send SMS.
        $response = ['expires_in' => 600];
        if (API_ENV === 'development') {
            $response['otp']  = $otp;
            $response['note'] = 'OTP returned in development mode only';
        }

        Response::success($response, 'New OTP sent');
    }

    // ── POST /api/auth/customer/forgot-password ───────────────────────────────
    public function forgotPassword(array $body): never {
        $v = new Validator($body);
        $v->required('email', 'Email')->email('email', 'Email');

        if ($v->fails()) Response::validationError($v->errors());

        $email = strtolower(trim($body['email']));

        $user = $this->db->queryOne(
            "SELECT u.id FROM users u
             JOIN customers c ON c.user_id = u.id
             WHERE u.email = ? AND u.user_type = 'customer' AND u.status = 'active'",
            [$email]
        );

        if ($user) {
            $resetToken = JWT::createResetToken($user['id'], $email);
            $expiresAt  = date('Y-m-d H:i:s', time() + 900);

            $this->db->execute(
                "DELETE FROM password_resets WHERE user_id = ?",
                [$user['id']]
            );
            $this->db->execute(
                "INSERT INTO password_resets (user_id, token, expires_at, created_at)
                 VALUES (?, ?, ?, NOW())",
                [$user['id'], hash('sha256', $resetToken), $expiresAt]
            );

            if (API_ENV === 'development') {
                Response::success([
                    'reset_token' => $resetToken,
                    'note'        => 'Token returned in dev mode only',
                ], 'Password reset link generated');
            }
        }

        Response::success(null, 'If that email is registered, a reset link has been sent.');
    }

    // ── POST /api/auth/customer/reset-password ────────────────────────────────
    public function resetPassword(array $body): never {
        $v = new Validator($body);
        $v->required('token', 'Reset token')
          ->required('password', 'New password')
          ->minLength('password', 8, 'Password');

        if ($v->fails()) Response::validationError($v->errors());

        $token    = $body['token'];
        $password = $body['password'];

        try {
            $payload = JWT::verify($token);
            if (($payload['type'] ?? '') !== 'reset') {
                throw new \RuntimeException('Invalid token type');
            }
        } catch (\RuntimeException) {
            Response::error('Invalid or expired reset token.', 400, 'INVALID_RESET_TOKEN');
        }

        $stored = $this->db->queryOne(
            "SELECT id FROM password_resets
             WHERE user_id = ? AND token = ? AND used = 0 AND expires_at > NOW()",
            [$payload['sub'], hash('sha256', $token)]
        );

        if (!$stored) {
            Response::error('Reset token is invalid or has expired.', 400, 'INVALID_RESET_TOKEN');
        }

        $this->db->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($password, PASSWORD_DEFAULT), $payload['sub']]
        );
        $this->db->execute(
            "UPDATE password_resets SET used = 1 WHERE user_id = ?",
            [$payload['sub']]
        );

        Response::success(null, 'Password reset successfully. Please log in.');
    }

    // ── POST /api/auth/customer/refresh ───────────────────────────────────────
    public function refresh(array $body): never {
        $refreshToken = $body['refresh_token'] ?? null;

        if (!$refreshToken) {
            Response::error('Refresh token is required.', 400, 'MISSING_REFRESH_TOKEN');
        }

        try {
            $payload = JWT::verifyRefresh($refreshToken);
        } catch (\RuntimeException $e) {
            Response::unauthorized($e->getMessage());
        }

        $stored = $this->db->queryOne(
            "SELECT id FROM refresh_tokens WHERE user_id = ? AND token_hash = ? AND expires_at > NOW()",
            [$payload['sub'], hash('sha256', $refreshToken)]
        );

        if (!$stored) {
            Response::unauthorized('Refresh token is invalid or has already been used.');
        }

        $this->db->execute(
            "DELETE FROM refresh_tokens WHERE user_id = ?",
            [$payload['sub']]
        );

        $user = $this->db->queryOne(
            "SELECT u.id, u.email, u.phone, u.status FROM users u
             WHERE u.id = ? AND u.user_type = 'customer'",
            [$payload['sub']]
        );

        if (!$user || $user['status'] !== 'active') {
            Response::unauthorized('Account no longer active.');
        }

        $newAccessToken  = JWT::createAccessToken($user['id'], $user['email'] ?? $user['phone']);
        $newRefreshToken = JWT::createRefreshToken($user['id']);
        $this->storeRefreshToken($user['id'], $newRefreshToken);

        Response::success([
            'access_token'  => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in'    => JWT_ACCESS_EXPIRY,
        ], 'Token refreshed');
    }

    // ── POST /api/auth/customer/logout ────────────────────────────────────────
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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function formatCustomer(array $row, bool $isNewUser): array {
        return [
            'id'                  => (int)$row['customer_id'],
            'user_id'             => (int)$row['user_id'],
            'name'                => $row['name'],
            'email'               => $row['email'] ?? null,
            'phone'               => $row['phone'],
            'profile_image'       => $row['profile_image'],
            'city_id'             => isset($row['city_id']) ? (int)$row['city_id'] : null,
            'area_id'             => isset($row['area_id']) ? (int)$row['area_id'] : null,
            'customer_type'       => $row['customer_type'],
            'subscription_status' => $row['subscription_status'],
            'subscription_expiry' => $row['subscription_expiry'],
            'is_dealmaker'        => (bool)$row['is_dealmaker'],
            'referral_code'       => $row['referral_code'],
            'is_new_user'         => $isNewUser,
        ];
    }

    private function generateReferralCode(int $userId): string {
        return 'REF' . str_pad($userId, 6, '0', STR_PAD_LEFT) . strtoupper(substr(bin2hex(random_bytes(4)), 0, 4));
    }

    private function storeRefreshToken(int $userId, string $token): void {
        $this->db->execute(
            "INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), expires_at = VALUES(expires_at), created_at = NOW()",
            [$userId, hash('sha256', $token), date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY)]
        );
    }
}
