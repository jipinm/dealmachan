<?php
/**
 * Merchant Coupon Controller
 *
 * GET    /merchants/coupons                       → index
 * POST   /merchants/coupons                       → store
 * GET    /merchants/coupons/:id                   → show
 * PUT    /merchants/coupons/:id                   → update
 * DELETE /merchants/coupons/:id                   → destroy
 * GET    /merchants/coupons/:id/redemptions       → redemptions
 * POST   /merchants/coupons/scan-redeem           → scanRedeem
 * POST   /merchants/coupons/manual-redeem         → manualRedeem
 */
class CouponController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/coupons ────────────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $tab    = $params['tab']   ?? 'all';
        $page   = max(1, (int)($params['page']  ?? 1));
        $limit  = min(50, max(10, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where  = 'c.merchant_id = ?';
        $binds  = [$merchantId];

        switch ($tab) {
            case 'active':
                $where .= " AND c.status = 'active' AND c.approval_status = 'approved'"
                        . " AND (c.valid_until IS NULL OR c.valid_until >= NOW())";
                break;
            case 'pending':
                $where .= " AND c.approval_status = 'pending'";
                break;
            case 'expired':
                $where .= " AND (c.status = 'expired'"
                        . " OR (c.valid_until IS NOT NULL AND c.valid_until < NOW()))";
                break;
            case 'inactive':
                $where .= " AND c.status = 'inactive'";
                break;
        }

        if (!empty($params['store_id'])) {
            $where  .= ' AND c.store_id = ?';
            $binds[] = (int)$params['store_id'];
        }

        $total = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM coupons c WHERE {$where}",
            $binds
        )['cnt'];

        $rows = $this->db->query(
            "SELECT c.*,
                    s.store_name,
                    COUNT(cr.id) AS redemption_count
             FROM coupons c
             LEFT JOIN stores s  ON s.id  = c.store_id
             LEFT JOIN coupon_redemptions cr ON cr.coupon_id = c.id
             WHERE {$where}
             GROUP BY c.id
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );

        foreach ($rows as &$r) {
            $r['banner_image'] = imageUrl($r['banner_image'] ?? null);
        }
        unset($r);

        Response::success($rows, 'OK', 200, [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ]);
    }

    // ── POST /merchants/coupons ───────────────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];
        $userId     = (int)$user['id'];

        $v = new Validator($body);
        $v->required('title')
          ->required('discount_type')
          ->required('discount_value');
        if ($v->fails()) Response::validationError($v->errors());

        if (!in_array($body['discount_type'] ?? '', ['percentage', 'fixed'])) {
            Response::error('discount_type must be percentage or fixed', 422, 'VALIDATION_ERROR');
        }
        if (($body['discount_type'] ?? '') === 'percentage') {
            $val = (float)($body['discount_value'] ?? 0);
            if ($val <= 0 || $val > 100) {
                Response::error('Percentage discount must be between 1 and 100', 422, 'VALIDATION_ERROR');
            }
        }

        // Auto-generate coupon code if not provided
        $code = !empty($body['coupon_code'])
            ? strtoupper(trim((string)$body['coupon_code']))
            : strtoupper(substr(md5(uniqid((string)$merchantId, true)), 0, 8));

        // Ensure uniqueness
        $exists = $this->db->queryOne('SELECT id FROM coupons WHERE coupon_code = ?', [$code]);
        if ($exists) {
            $code .= rand(10, 99);
        }

        // Validate store ownership
        $storeId = !empty($body['store_id']) ? (int)$body['store_id'] : null;
        if ($storeId) {
            $store = $this->db->queryOne(
                'SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL',
                [$storeId, $merchantId]
            );
            if (!$store) Response::notFound('Store not found');
        }

        $this->db->execute(
            "INSERT INTO coupons (
                title, description, coupon_code, discount_type, discount_value,
                min_purchase_amount, max_discount_amount, merchant_id, store_id,
                valid_from, valid_until, usage_limit, terms_conditions,
                created_by, approval_status, status, created_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'active', NOW())",
            [
                trim((string)$body['title']),
                $body['description'] ?? null,
                $code,
                $body['discount_type'],
                (float)$body['discount_value'],
                !empty($body['min_purchase_amount']) ? (float)$body['min_purchase_amount'] : null,
                !empty($body['max_discount_amount'])  ? (float)$body['max_discount_amount']  : null,
                $merchantId,
                $storeId,
                !empty($body['valid_from'])   ? $body['valid_from']   : null,
                !empty($body['valid_until'])  ? $body['valid_until']  : null,
                !empty($body['usage_limit'])  ? (int)$body['usage_limit'] : null,
                $body['terms_conditions'] ?? null,
                $userId,
            ]
        );

        $newId   = $this->db->lastInsertId();
        $coupon  = $this->db->queryOne('SELECT * FROM coupons WHERE id = ?', [$newId]);
        Response::created($coupon, 'Coupon created — pending admin approval.');
    }

    // ── GET /merchants/coupons/:id ────────────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            "SELECT c.*,
                    s.store_name,
                    COUNT(cr.id) AS redemption_count
             FROM coupons c
             LEFT JOIN stores s  ON  s.id = c.store_id
             LEFT JOIN coupon_redemptions cr ON cr.coupon_id = c.id
             WHERE c.id = ? AND c.merchant_id = ?
             GROUP BY c.id",
            [$id, $merchantId]
        );

        if (!$coupon) Response::notFound('Coupon not found');
        $coupon['banner_image'] = imageUrl($coupon['banner_image'] ?? null);
        Response::success($coupon);
    }

    // ── POST /merchants/coupons/:id/image ─────────────────────────────────────
    public function uploadImage(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            'SELECT id, banner_image FROM coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Coupon not found');

        if (empty($_FILES['image'])) {
            Response::error('No image uploaded.', 400, 'NO_FILE');
        }

        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            Response::error('Upload error.', 422, 'UPLOAD_ERROR');
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $maxSize      = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            Response::error('Image must be under 5MB.', 422, 'FILE_TOO_LARGE');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            Response::error('Only JPEG, PNG, or WebP images are allowed.', 422, 'INVALID_TYPE');
        }

        $uploadDir = API_UPLOAD_PATH . '/coupon-banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Delete old banner if it exists
        if (!empty($coupon['banner_image'])) {
            $oldFile = $uploadDir . basename($coupon['banner_image']);
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'coupon_' . $id . '_' . uniqid() . '.' . strtolower($ext);
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::error('Failed to save image.', 500, 'SAVE_FAILED');
        }

        $relPath = '/uploads/coupon-banners/' . $filename;
        $this->db->execute(
            'UPDATE coupons SET banner_image = ? WHERE id = ?',
            [$relPath, $id]
        );

        Response::success(['banner_image' => imageUrl($relPath)], 'Deal image uploaded.');
    }

    // ── DELETE /merchants/coupons/:id/image ────────────────────────────────────
    public function deleteImage(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            'SELECT id, banner_image FROM coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Coupon not found');

        if (empty($coupon['banner_image'])) {
            Response::error('No image to delete.', 400, 'NO_IMAGE');
        }

        $uploadDir = API_UPLOAD_PATH . '/coupon-banners/';
        $oldFile   = $uploadDir . basename($coupon['banner_image']);
        if (file_exists($oldFile)) @unlink($oldFile);

        $this->db->execute(
            'UPDATE coupons SET banner_image = NULL WHERE id = ?',
            [$id]
        );

        Response::success(null, 'Deal image removed.');
    }

    // ── PUT /merchants/coupons/:id ────────────────────────────────────────────
    public function update(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            'SELECT * FROM coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Coupon not found');

        $allowed = [
            'title', 'description', 'discount_type', 'discount_value',
            'min_purchase_amount', 'max_discount_amount',
            'valid_from', 'valid_until', 'usage_limit',
            'terms_conditions', 'status',
        ];

        $fields = [];
        $binds  = [];

        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "{$f} = ?";
                $binds[]  = ($body[$f] === '') ? null : $body[$f];
            }
        }

        if (empty($fields)) Response::error('No fields to update.', 400, 'NOTHING_TO_UPDATE');

        // Editing resets approval
        $fields[] = "approval_status = 'pending'";
        $fields[] = 'approved_by_admin_id = NULL';
        $fields[] = 'approved_at = NULL';
        $binds[]  = $id;

        $this->db->execute(
            'UPDATE coupons SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $binds
        );

        $updated = $this->db->queryOne('SELECT * FROM coupons WHERE id = ?', [$id]);
        Response::success($updated, 'Coupon updated — pending re-approval.');
    }

    // ── DELETE /merchants/coupons/:id ─────────────────────────────────────────
    public function destroy(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            'SELECT id FROM coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Coupon not found');

        $this->db->execute(
            "UPDATE coupons SET status = 'inactive' WHERE id = ?",
            [$id]
        );
        Response::success(null, 'Coupon deactivated');
    }

    // ── GET /merchants/coupons/:id/redemptions ────────────────────────────────
    public function redemptions(array $user, int $id, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            'SELECT id FROM coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Coupon not found');

        $page   = max(1, (int)($params['page']  ?? 1));
        $limit  = min(50, max(10, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $total = (int)$this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM coupon_redemptions WHERE coupon_id = ?',
            [$id]
        )['cnt'];

        $rows = $this->db->query(
            "SELECT cr.*,
                    cu.name  AS customer_name,
                    cu.phone AS customer_phone,
                    s.store_name
             FROM coupon_redemptions cr
             LEFT JOIN customers cu ON cu.id = cr.customer_id
             LEFT JOIN stores    s  ON  s.id = cr.store_id
             WHERE cr.coupon_id = ?
             ORDER BY cr.redeemed_at DESC
             LIMIT ? OFFSET ?",
            [$id, $limit, $offset]
        );

        Response::success($rows, 'OK', 200, [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    // ── POST /merchants/coupons/scan-redeem ───────────────────────────────────
    public function scanRedeem(array $user, array $body): never {
        $this->doRedeem($user, $body);
    }

    // ── POST /merchants/coupons/manual-redeem ─────────────────────────────────
    public function manualRedeem(array $user, array $body): never {
        $this->doRedeem($user, $body);
    }

    // ── Shared redeem logic ───────────────────────────────────────────────────
    private function doRedeem(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        if (empty($body['coupon_code'])) {
            Response::error('coupon_code is required', 422, 'VALIDATION_ERROR');
        }

        $code   = strtoupper(trim((string)$body['coupon_code']));
        $coupon = $this->db->queryOne(
            'SELECT * FROM coupons WHERE coupon_code = ?',
            [$code]
        );

        if (!$coupon)                                        Response::notFound('Coupon not found');
        if ((int)$coupon['merchant_id'] !== $merchantId)    Response::error('This coupon does not belong to your account', 403, 'FORBIDDEN');
        if ($coupon['approval_status'] !== 'approved')      Response::error('This coupon has not been approved yet', 422, 'NOT_APPROVED');
        if ($coupon['status'] !== 'active')                 Response::error('This coupon is not active', 422, 'INACTIVE');

        // Date range validation
        if (!empty($coupon['valid_from']) && strtotime($coupon['valid_from']) > time()) {
            Response::error(
                'Coupon is not valid yet (starts ' . date('d M Y', strtotime($coupon['valid_from'])) . ')',
                422, 'NOT_STARTED'
            );
        }
        if (!empty($coupon['valid_until']) && strtotime($coupon['valid_until']) < time()) {
            Response::error('This coupon has expired', 422, 'EXPIRED');
        }

        // Usage limit
        if (!empty($coupon['usage_limit']) && (int)$coupon['usage_count'] >= (int)$coupon['usage_limit']) {
            Response::error('This coupon has reached its usage limit', 422, 'LIMIT_REACHED');
        }

        // Resolve customer (phone takes priority, then explicit ID)
        $customerId = null;
        if (!empty($body['customer_phone'])) {
            $phone    = preg_replace('/\D/', '', (string)$body['customer_phone']);
            $customer = $this->db->queryOne(
                'SELECT cu.id FROM customers cu JOIN users u ON u.id = cu.user_id WHERE u.phone = ?',
                [$phone]
            );
            if ($customer) $customerId = (int)$customer['id'];
        }
        if (!$customerId && !empty($body['customer_id'])) {
            $customerId = (int)$body['customer_id'];
        }

        if (!$customerId) {
            Response::error(
                'Provide customer_phone or customer_id to record this redemption',
                422, 'CUSTOMER_REQUIRED'
            );
        }

        $storeId           = !empty($body['store_id'])           ? (int)$body['store_id']           : null;
        $transactionAmount = !empty($body['transaction_amount']) ? (float)$body['transaction_amount'] : null;

        // Calculate discount amount
        if ($coupon['discount_type'] === 'percentage') {
            $base           = $transactionAmount ?? 100;
            $discountAmount = round($base * ((float)$coupon['discount_value'] / 100), 2);
            if (!empty($coupon['max_discount_amount'])) {
                $discountAmount = min($discountAmount, (float)$coupon['max_discount_amount']);
            }
        } else {
            $discountAmount = (float)$coupon['discount_value'];
        }

        // Record redemption
        $this->db->execute(
            'INSERT INTO coupon_redemptions
                (coupon_id, customer_id, store_id, discount_amount, transaction_amount, verified_by_merchant, redeemed_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW())',
            [$coupon['id'], $customerId, $storeId, $discountAmount, $transactionAmount]
        );

        // Increment usage count on the coupon
        $this->db->execute(
            'UPDATE coupons SET usage_count = usage_count + 1 WHERE id = ?',
            [$coupon['id']]
        );

        Response::success([
            'coupon_code'     => $code,
            'coupon_title'    => $coupon['title'],
            'discount_type'   => $coupon['discount_type'],
            'discount_value'  => $coupon['discount_value'],
            'discount_amount' => $discountAmount,
            'transaction_amount' => $transactionAmount,
        ], 'Coupon redeemed successfully!');
    }
}
