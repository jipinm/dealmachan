<?php
/**
 * Merchant Profile Controller
 *
 * GET  /merchants/profile
 * PUT  /merchants/profile
 * POST /merchants/profile/logo
 * POST /merchants/subscription/renew
 * POST /merchants/subscription/upgrade
 */
class ProfileController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/profile ────────────────────────────────────────────────
    public function show(): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = $merchant['merchant_id'];

        $profile = $this->db->queryOne(
            "SELECT
                m.id,
                m.user_id,
                m.business_name,
                m.business_logo,
                m.registration_number,
                m.gst_number,
                m.is_premium,
                m.subscription_status,
                m.subscription_expiry,
                m.profile_status,
                m.created_at,
                u.email,
                u.phone,
                u.status   AS account_status,
                u.last_login,
                l.id        AS label_id,
                l.label_name,
                l.label_icon
             FROM merchants m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN labels l ON l.id = m.label_id
             WHERE m.id = ?",
            [$merchantId]
        );

        if (!$profile) {
            Response::notFound('Merchant profile not found');
        }

        // Store summary
        $storeSummary = $this->db->queryOne(
            "SELECT COUNT(*) AS total_stores,
                    SUM(status = 'active') AS active_stores
             FROM stores
             WHERE merchant_id = ?",
            [$merchantId]
        );

        // Assigned labels
        $labels = $this->db->query(
            "SELECT l.id, l.label_name, l.label_icon, ml.assigned_at
             FROM merchant_labels ml
             JOIN labels l ON l.id = ml.label_id
             WHERE ml.merchant_id = ?",
            [$merchantId]
        );

        // Active subscription detail
        $subscription = $this->db->queryOne(
            "SELECT id, plan_type, start_date, expiry_date, auto_renew, status, payment_amount
             FROM subscriptions
             WHERE user_id = ? AND user_type = 'merchant'
             ORDER BY created_at DESC
             LIMIT 1",
            [$profile['user_id']]
        );

        // Coupon stats
        $couponStats = $this->db->queryOne(
            "SELECT COUNT(*) AS total_coupons,
                    SUM(status = 'active') AS active_coupons
             FROM coupons
             WHERE merchant_id = ?",
            [$merchantId]
        );

        Response::success([
            'profile'      => $profile,
            'stores'       => $storeSummary,
            'labels'       => $labels,
            'subscription' => $subscription,
            'coupons'      => $couponStats,
        ]);
    }

    // ── PUT /merchants/profile ────────────────────────────────────────────────
    public function update(array $body): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = $merchant['merchant_id'];

        $v = new Validator($body);
        $v->optional('business_name')->maxLength('business_name', 255, 'Business name')
          ->optional('phone')->maxLength('phone', 20, 'Phone')
          ->optional('gst_number')->maxLength('gst_number', 50, 'GST number')
          ->optional('registration_number')->maxLength('registration_number', 100, 'Registration number');

        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        // Get user_id
        $merchantRow = $this->db->queryOne(
            "SELECT user_id FROM merchants WHERE id = ?",
            [$merchantId]
        );

        if (!$merchantRow) {
            Response::notFound('Merchant not found');
        }

        $userId = $merchantRow['user_id'];

        // Update merchants table
        $merchantFields = array_filter([
            'business_name'       => $body['business_name'] ?? null,
            'gst_number'          => $body['gst_number'] ?? null,
            'registration_number' => $body['registration_number'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($merchantFields)) {
            $sets  = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($merchantFields)));
            $vals  = array_values($merchantFields);
            $vals[] = $merchantId;
            $this->db->execute("UPDATE merchants SET $sets WHERE id = ?", $vals);
        }

        // Update users table
        $userFields = array_filter([
            'phone' => $body['phone'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($userFields)) {
            $sets  = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($userFields)));
            $vals  = array_values($userFields);
            $vals[] = $userId;
            $this->db->execute("UPDATE users SET $sets WHERE id = ?", $vals);
        }

        // Return updated profile
        $this->show();
    }

    // ── POST /merchants/profile/logo ──────────────────────────────────────────
    public function uploadLogo(): never {
        $merchant   = AuthMiddleware::user();
        $merchantId = $merchant['merchant_id'];

        if (empty($_FILES['logo'])) {
            Response::validationError(['logo' => ['Logo file is required']]);
        }

        $file     = $_FILES['logo'];
        $mimeType = $file['type'] ?? '';

        if (!in_array($mimeType, ALLOWED_IMG_TYPES, true)) {
            Response::validationError(['logo' => ['Only JPEG, PNG, or WebP images are allowed']]);
        }

        if ($file['size'] > MAX_UPLOAD_SIZE) {
            Response::validationError(['logo' => ['Image must be under 5 MB']]);
        }

        // Create upload directory if needed
        $uploadDir = API_UPLOAD_PATH . '/logos';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Remove old logo
        $old = $this->db->queryOne("SELECT business_logo FROM merchants WHERE id = ?", [$merchantId]);
        if (!empty($old['business_logo'])) {
            $oldPath = API_UPLOAD_PATH . '/' . ltrim($old['business_logo'], '/');
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $ext      = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            default      => 'webp',
        };
        $filename = "merchant_{$merchantId}_logo_" . time() . ".{$ext}";
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            Response::error('Failed to save image', 'UPLOAD_FAILED', 500);
        }

        $logoUrl = "/uploads/logos/{$filename}";
        $this->db->execute(
            "UPDATE merchants SET business_logo = ? WHERE id = ?",
            [$logoUrl, $merchantId]
        );

        Response::success(['logo_url' => $logoUrl], 'Logo uploaded successfully');
    }

    // ── POST /merchants/subscription/renew ───────────────────────────────────
    public function renewSubscription(array $body): never {
        $merchant = AuthMiddleware::user();
        // Record a renewal request via notification to admin — actual payment handled externally
        $this->db->execute(
            "INSERT INTO notifications (user_id, user_type, notification_type, title, message)
             SELECT a.user_id, 'admin', 'info',
                    'Subscription Renewal Request',
                    CONCAT('Merchant ID ', ?, ' has requested subscription renewal.')
             FROM admins a
             WHERE a.admin_type = 'super_admin'
             LIMIT 1",
            [$merchant['merchant_id']]
        );

        Response::success(null, 'Renewal request submitted. Our team will contact you shortly.');
    }

    // ── POST /merchants/subscription/upgrade ─────────────────────────────────
    public function upgradeSubscription(array $body): never {
        $merchant = AuthMiddleware::user();

        $v = new Validator($body);
        $v->required('plan_type', 'Plan type');
        if ($v->fails()) {
            Response::validationError($v->errors());
        }

        $this->db->execute(
            "INSERT INTO notifications (user_id, user_type, notification_type, title, message)
             SELECT a.user_id, 'admin', 'info',
                    'Subscription Upgrade Request',
                    CONCAT('Merchant ID ', ?, ' has requested upgrade to plan: ', ?)
             FROM admins a
             WHERE a.admin_type = 'super_admin'
             LIMIT 1",
            [$merchant['merchant_id'], $body['plan_type']]
        );

        Response::success(null, 'Upgrade request submitted. Our team will contact you shortly.');
    }
}
