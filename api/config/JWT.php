<?php
/**
 * Pure-PHP JWT implementation (HS256).
 * No Composer dependency required.
 */
class JWT {

    // ── Token Generation ─────────────────────────────────────────────────────

    /**
     * Create an access token (short-lived).
     *
     * @param array $extraClaims Optional extra claims merged into the payload
     *                           (e.g. ['access_scope' => 'store', 'store_id' => 5]).
     */
    public static function createAccessToken(int $merchantId, string $email, string $adminType = 'merchant', array $extraClaims = []): string {
        $now = time();
        return self::encode(array_merge([
            'iss'         => 'deal-machan-api',
            'sub'         => $merchantId,
            'email'       => $email,
            'type'        => 'access',
            'role'        => $adminType,
            'iat'         => $now,
            'exp'         => $now + JWT_ACCESS_EXPIRY,
        ], $extraClaims));
    }

    /**
     * Create a refresh token (long-lived).
     */
    public static function createRefreshToken(int $merchantId): string {
        $now = time();
        return self::encode([
            'iss'  => 'deal-machan-api',
            'sub'  => $merchantId,
            'type' => 'refresh',
            'jti'  => bin2hex(random_bytes(16)),
            'iat'  => $now,
            'exp'  => $now + JWT_REFRESH_EXPIRY,
        ]);
    }

    /**
     * Create a short-lived password-reset token (15 min).
     */
    public static function createResetToken(int $userId, string $email): string {
        $now = time();
        return self::encode([
            'iss'   => 'deal-machan-api',
            'sub'   => $userId,
            'email' => $email,
            'type'  => 'reset',
            'iat'   => $now,
            'exp'   => $now + 900,
        ]);
    }

    // ── Token Verification ───────────────────────────────────────────────────

    /**
     * Decode and verify a token.
     *
     * @throws \RuntimeException on invalid / expired token
     */
    public static function verify(string $token): array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \RuntimeException('Invalid token structure');
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        $expectedSig = self::sign("{$headerB64}.{$payloadB64}");
        if (!hash_equals($expectedSig, $signatureB64)) {
            throw new \RuntimeException('Invalid token signature');
        }

        $payload = json_decode(self::base64UrlDecode($payloadB64), true);
        if (!$payload) {
            throw new \RuntimeException('Invalid token payload');
        }

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new \RuntimeException('Token has expired');
        }

        return $payload;
    }

    /**
     * Verify an access token specifically.
     *
     * @throws \RuntimeException
     */
    public static function verifyAccess(string $token): array {
        $payload = self::verify($token);
        if (($payload['type'] ?? '') !== 'access') {
            throw new \RuntimeException('Not an access token');
        }
        return $payload;
    }

    /**
     * Verify a refresh token specifically.
     *
     * @throws \RuntimeException
     */
    public static function verifyRefresh(string $token): array {
        $payload = self::verify($token);
        if (($payload['type'] ?? '') !== 'refresh') {
            throw new \RuntimeException('Not a refresh token');
        }
        return $payload;
    }

    // ── Internal ─────────────────────────────────────────────────────────────

    private static function encode(array $payload): string {
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $body = self::base64UrlEncode(json_encode($payload));
        $sig  = self::sign("{$header}.{$body}");

        return "{$header}.{$body}.{$sig}";
    }

    private static function sign(string $input): string {
        return self::base64UrlEncode(
            hash_hmac('sha256', $input, JWT_SECRET, true)
        );
    }

    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
