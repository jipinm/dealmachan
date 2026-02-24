<?php
/**
 * Customer Profile Controller
 *
 * GET  /api/customers/profile
 * PUT  /api/customers/profile
 * POST /api/customers/profile/photo
 */
class CustomerProfileController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/customers/profile ────────────────────────────────────────────
    public function show(array $user): never {
        $row = $this->db->queryOne(
            "SELECT u.id AS user_id, u.email, u.phone,
                    c.id, c.name, c.last_name, c.profile_image, c.date_of_birth, c.gender,
                    c.city_id, c.area_id, c.bio, c.occupation, c.full_address, c.pincode,
                    c.profession_id, c.customer_type, c.subscription_status,
                    c.subscription_expiry, c.is_dealmaker, c.referral_code,
                    COALESCE(c.temp_password, 0) AS temp_password,
                    ci.city_name, a.area_name,
                    p.profession_name
             FROM users u
             JOIN customers c ON c.user_id = u.id
             LEFT JOIN cities ci ON ci.id = c.city_id
             LEFT JOIN areas  a  ON a.id  = c.area_id
             LEFT JOIN professions p ON p.id = c.profession_id
             WHERE u.id = ?",
            [$user['id']]
        );

        if (!$row) Response::notFound('Customer profile not found.');

        Response::success([
            'id'                  => (int)$row['id'],
            'user_id'             => (int)$row['user_id'],
            'name'                => $row['name'],
            'last_name'           => $row['last_name'] ?? null,
            'email'               => $row['email'],
            'phone'               => $row['phone'],
            'profile_image'       => imageUrl($row['profile_image']),
            'date_of_birth'       => $row['date_of_birth'],
            'gender'              => $row['gender'],
            'bio'                 => $row['bio'] ?? null,
            'occupation'          => $row['occupation'] ?? null,
            'full_address'        => $row['full_address'] ?? null,
            'pincode'             => $row['pincode'] ?? null,
            'city_id'             => isset($row['city_id']) ? (int)$row['city_id'] : null,
            'area_id'             => isset($row['area_id']) ? (int)$row['area_id'] : null,
            'city_name'           => $row['city_name'],
            'area_name'           => $row['area_name'],
            'profession_id'       => isset($row['profession_id']) ? (int)$row['profession_id'] : null,
            'profession_name'     => $row['profession_name'],
            'customer_type'       => $row['customer_type'],
            'subscription_status' => $row['subscription_status'],
            'subscription_expiry' => $row['subscription_expiry'],
            'is_dealmaker'        => (bool)$row['is_dealmaker'],
            'referral_code'       => $row['referral_code'],
            'temp_password'       => (int)($row['temp_password'] ?? 0),
            'is_new_user'         => empty($row['city_id']),
        ]);
    }

    // ── PUT /api/customers/profile ────────────────────────────────────────────
    public function update(array $user, array $body): never {
        $allowed = ['name', 'last_name', 'date_of_birth', 'gender', 'bio', 'occupation', 'full_address', 'pincode', 'city_id', 'area_id', 'profession_id'];
        $set     = [];
        $params  = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $body)) {
                $set[]    = "`{$field}` = ?";
                $params[] = $body[$field] !== '' ? $body[$field] : null;
            }
        }

        if (empty($set)) {
            Response::error('No updatable fields provided.', 400, 'NO_FIELDS');
        }

        $params[] = $user['id']; // WHERE user_id
        $this->db->execute(
            "UPDATE customers SET " . implode(', ', $set) . ", updated_at = NOW() WHERE user_id = ?",
            $params
        );

        // Return updated profile
        $this->show($user);
    }

    // ── POST /api/customers/profile/image ─────────────────────────────────────
    public function uploadImage(array $user): never {
        if (empty($_FILES['image'])) Response::error('No image provided.', 400, 'NO_IMAGE');

        $file    = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowed, true)) {
            Response::error('Invalid image type. Use JPG, PNG, or WebP.', 400, 'INVALID_TYPE');
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::error('Image too large. Max 5 MB.', 400, 'TOO_LARGE');
        }

        $ext     = pathinfo($file['name'], PATHINFO_EXTENSION);
        $name    = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
        $dest    = API_UPLOAD_PATH . '/avatars/' . $name;
        $url     = '/uploads/avatars/' . $name;

        if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::error('Upload failed.', 500, 'UPLOAD_FAILED');
        }

        $this->db->execute(
            "UPDATE customers SET profile_image = ?, updated_at = NOW() WHERE user_id = ?",
            [$url, $user['id']]
        );

        Response::success(['image_url' => imageUrl($url)]);
    }

    // ── GET /api/customers/subscription ──────────────────────────────────────
    public function subscription(array $user): never {
        $row = $this->db->queryOne(
            "SELECT c.subscription_status AS status,
                    cs.plan_type, cs.start_date, cs.end_date AS expiry_date, cs.auto_renew,
                    GREATEST(0, DATEDIFF(cs.end_date, CURDATE())) AS days_remaining
             FROM customers c
             LEFT JOIN customer_subscriptions cs
               ON cs.customer_id = c.id AND cs.status = 'active'
             WHERE c.user_id = ?
             ORDER BY cs.end_date DESC
             LIMIT 1",
            [$user['id']]
        );
        Response::success($row ?? ['status' => 'none', 'plan_type' => null,
            'start_date' => null, 'expiry_date' => null, 'auto_renew' => false, 'days_remaining' => 0]);
    }

    // ── PUT /api/customers/profile/password ───────────────────────────────────
    public function changePassword(array $user, array $body): never {
        $current = $body['current_password'] ?? '';
        $new     = $body['new_password']     ?? '';

        if (!$current || !$new) Response::error('Both current and new passwords required.', 400, 'MISSING_FIELDS');
        if (strlen($new) < 8)   Response::error('New password must be at least 8 characters.', 400, 'PASSWORD_TOO_SHORT');

        $userRow = $this->db->queryOne("SELECT password_hash FROM users WHERE id = ?", [$user['id']]);
        if (!$userRow || !password_verify($current, $userRow['password_hash'])) {
            Response::error('Current password is incorrect.', 401, 'WRONG_PASSWORD');
        }

        $this->db->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($new, PASSWORD_DEFAULT), $user['id']]
        );

        Response::success(null, 'Password changed successfully');
    }

    // ── POST /api/customers/password/set-new ──────────────────────────────────
    // Mandatory reset — no current password required; clears temp_password flag
    public function setNewPassword(array $user, array $body): never {
        $new     = trim($body['new_password']     ?? '');
        $confirm = trim($body['confirm_password'] ?? '');

        if (!$new)               Response::error('New password is required.', 400, 'MISSING_FIELDS');
        if (strlen($new) < 8)    Response::error('Password must be at least 8 characters.', 400, 'PASSWORD_TOO_SHORT');
        if ($new !== $confirm)   Response::error('Passwords do not match.', 400, 'PASSWORD_MISMATCH');

        $this->db->execute(
            "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($new, PASSWORD_DEFAULT), $user['id']]
        );

        // Clear temp_password flag if the column exists (graceful — column may not be in older DBs)
        try {
            $this->db->execute(
                "UPDATE customers SET temp_password = 0 WHERE user_id = ?",
                [$user['id']]
            );
        } catch (\Throwable $e) {
            // Column may not exist yet — ignore
        }

        Response::success(null, 'Password set successfully. Please continue.');
    }

    // ── GET /api/customers/stats ──────────────────────────────────────────────
    public function stats(array $user): never {
        $customer = $this->db->queryOne("SELECT id FROM customers WHERE user_id = ?", [$user['id']]);
        if (!$customer) Response::notFound('Customer profile not found.');

        $cid = (int)$customer['id'];

        $saved    = (int)($this->db->queryOne("SELECT COUNT(*) AS c FROM coupon_subscriptions WHERE customer_id = ? AND status = 'saved'",    [$cid])['c'] ?? 0);
        $redeemed = (int)($this->db->queryOne("SELECT COUNT(*) AS c FROM coupon_redemptions WHERE customer_id = ?",                             [$cid])['c'] ?? 0);
        $referrals = (int)($this->db->queryOne("SELECT COUNT(*) AS c FROM referrals WHERE referrer_customer_id = ? AND status IN ('completed','rewarded')", [$cid])['c'] ?? 0);

        Response::success(['saved' => $saved, 'redeemed' => $redeemed, 'referrals' => $referrals]);
    }
}
