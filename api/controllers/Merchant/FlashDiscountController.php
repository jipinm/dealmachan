<?php
/**
 * Flash Discount Controller
 *
 * GET    /merchants/flash-discounts        → index
 * POST   /merchants/flash-discounts        → store
 * PUT    /merchants/flash-discounts/:id    → update
 * DELETE /merchants/flash-discounts/:id    → destroy
 */
class FlashDiscountController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/flash-discounts ────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $where  = 'fd.merchant_id = ?';
        $binds  = [$merchantId];

        if (!empty($params['status'])) {
            $where  .= ' AND fd.status = ?';
            $binds[] = $params['status'];
        }
        // Store-scoped users can only see their own store's flash discounts
        if (($user['access_scope'] ?? 'merchant') === 'store' && !empty($user['store_id'])) {
            $where  .= ' AND fd.store_id = ?';
            $binds[] = (int)$user['store_id'];
        }

        // Auto-expire past discounts
        $this->db->execute(
            "UPDATE flash_discounts SET status = 'expired'
             WHERE merchant_id = ? AND valid_until < NOW() AND status = 'active'",
            [$merchantId]
        );

        $rows = $this->db->query(
            "SELECT fd.*, s.store_name
             FROM flash_discounts fd
             LEFT JOIN stores s ON s.id = fd.store_id
             WHERE {$where}
             ORDER BY fd.created_at DESC",
            $binds
        );

        foreach ($rows as &$row) {
            $row['banner_image'] = imageUrl($row['banner_image'] ?? null);
        }
        unset($row);

        Response::success($rows);
    }

    // ── POST /merchants/flash-discounts ───────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $v = new Validator($body);
        $v->required('title')
          ->required('discount_percentage');
        if ($v->fails()) Response::validationError($v->errors());

        $discount = (float)$body['discount_percentage'];
        if ($discount <= 0 || $discount > 100) {
            Response::validationError(['discount_percentage' => 'Must be between 1 and 100']);
        }

        // For store-scoped users, force store_id to their assigned store
        if (($user['access_scope'] ?? 'merchant') === 'store' && !empty($user['store_id'])) {
            $body['store_id'] = (int)$user['store_id'];
        }

        // Validate store if given
        $storeId = null;
        if (!empty($body['store_id'])) {
            $store = $this->db->queryOne(
                'SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL',
                [(int)$body['store_id'], $merchantId]
            );
            if (!$store) Response::notFound('Store not found');
            $storeId = (int)$body['store_id'];
        }

        $this->db->execute(
            "INSERT INTO flash_discounts
               (merchant_id, store_id, title, description, discount_percentage,
                valid_from, valid_until, max_redemptions, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $merchantId,
                $storeId,
                trim((string)$body['title']),
                !empty($body['description']) ? trim((string)$body['description']) : null,
                $discount,
                !empty($body['valid_from'])      ? $body['valid_from']      : null,
                !empty($body['valid_until'])     ? $body['valid_until']     : null,
                !empty($body['max_redemptions']) ? (int)$body['max_redemptions'] : null,
            ]
        );

        $flashId = $this->db->lastInsertId();

        // Auto-populate location from store
        if ($storeId) {
            $store = $this->db->queryOne(
                "SELECT city_id, area_id, location_id FROM stores WHERE id = ?",
                [$storeId]
            );
            if ($store && $store['city_id']) {
                $this->db->execute(
                    "INSERT IGNORE INTO flash_discount_locations (flash_discount_id, city_id, area_id, location_id) VALUES (?, ?, ?, ?)",
                    [$flashId, $store['city_id'], $store['area_id'], $store['location_id']]
                );
            }

            // Auto-populate categories from store
            $storeCats = $this->db->query(
                "SELECT category_id, sub_category_id FROM store_categories WHERE store_id = ?",
                [$storeId]
            );
            if ($storeCats) {
                $ins = "INSERT IGNORE INTO flash_discount_categories (flash_discount_id, category_id, sub_category_id) VALUES (?, ?, ?)";
                foreach ($storeCats as $sc) {
                    $this->db->execute($ins, [$flashId, $sc['category_id'], $sc['sub_category_id']]);
                }
            }
        }

        $row = $this->db->queryOne('SELECT * FROM flash_discounts WHERE id = ?', [$flashId]);
        $row['banner_image'] = imageUrl($row['banner_image'] ?? null);
        Response::created($row, 'Flash discount created');
    }

    // ── PUT /merchants/flash-discounts/:id ────────────────────────────────────
    public function update(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id, store_id FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Flash discount not found');

        if (($user['access_scope'] ?? 'merchant') === 'store' &&
            (int)($user['store_id'] ?? 0) !== (int)($existing['store_id'] ?? 0)) {
            Response::error('You can only edit flash discounts for your assigned store.', 403, 'FORBIDDEN');
        }

        $fields = [];
        $binds  = [];

        $allowed = ['title', 'description', 'valid_from', 'valid_until', 'max_redemptions', 'status'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "`{$f}` = ?";
                $binds[]  = $body[$f] === '' ? null : $body[$f];
            }
        }
        if (!empty($body['discount_percentage'])) {
            $fields[] = 'discount_percentage = ?';
            $binds[]  = (float)$body['discount_percentage'];
        }
        if (!empty($body['store_id'])) {
            $fields[] = 'store_id = ?';
            $binds[]  = (int)$body['store_id'];
        }

        if (empty($fields)) Response::error('Nothing to update', 400);

        $binds[] = $id;
        $this->db->execute(
            'UPDATE flash_discounts SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $binds
        );

        $row = $this->db->queryOne('SELECT * FROM flash_discounts WHERE id = ?', [$id]);
        $row['banner_image'] = imageUrl($row['banner_image'] ?? null);
        Response::success($row, 'Updated');
    }

    // ── DELETE /merchants/flash-discounts/:id ─────────────────────────────────
    public function destroy(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id, store_id FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Flash discount not found');

        if (($user['access_scope'] ?? 'merchant') === 'store' &&
            (int)($user['store_id'] ?? 0) !== (int)($existing['store_id'] ?? 0)) {
            Response::error('You can only delete flash discounts for your assigned store.', 403, 'FORBIDDEN');
        }

        $this->db->execute('DELETE FROM flash_discounts WHERE id = ?', [$id]);
        Response::success(['id' => $id], 'Deleted');
    }

    // ── POST /merchants/flash-discounts/:id/image ─────────────────────────────
    public function uploadImage(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $discount = $this->db->queryOne(
            'SELECT id, banner_image FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$discount) Response::notFound('Flash discount not found');

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

        $uploadDir = API_UPLOAD_PATH . '/flash-discount-banners/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!empty($discount['banner_image'])) {
            $oldFile = $uploadDir . basename($discount['banner_image']);
            if (file_exists($oldFile)) @unlink($oldFile);
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $filename = 'flash_' . $id . '_' . uniqid() . '.' . strtolower($ext);
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            Response::error('Failed to save image.', 500, 'SAVE_FAILED');
        }

        $relPath = '/uploads/flash-discount-banners/' . $filename;
        $this->db->execute(
            'UPDATE flash_discounts SET banner_image = ? WHERE id = ?',
            [$relPath, $id]
        );

        Response::success(['banner_image' => imageUrl($relPath)], 'Flash deal image uploaded.');
    }

    // ── DELETE /merchants/flash-discounts/:id/image ───────────────────────────
    public function deleteImage(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $discount = $this->db->queryOne(
            'SELECT id, banner_image FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$discount) Response::notFound('Flash discount not found');

        if (empty($discount['banner_image'])) {
            Response::error('No image to delete.', 400, 'NO_IMAGE');
        }

        $uploadDir = API_UPLOAD_PATH . '/flash-discount-banners/';
        $oldFile   = $uploadDir . basename($discount['banner_image']);
        if (file_exists($oldFile)) @unlink($oldFile);

        $this->db->execute(
            'UPDATE flash_discounts SET banner_image = NULL WHERE id = ?',
            [$id]
        );

        Response::success(null, 'Flash deal image removed.');
    }

    // ── POST /merchants/flash-discounts/:id/redeem ────────────────────────────
    public function redeem(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $discount = $this->db->queryOne(
            "SELECT * FROM flash_discounts WHERE id = ? AND merchant_id = ? AND status = 'active'",
            [$id, $merchantId]
        );
        if (!$discount) Response::notFound('Flash discount not found or not active');

        // Check max redemptions
        if ($discount['max_redemptions'] && (int)$discount['current_redemptions'] >= (int)$discount['max_redemptions']) {
            Response::error('Maximum redemptions reached for this flash discount', 400);
        }

        // Check validity period
        if ($discount['valid_until'] && strtotime($discount['valid_until']) < time()) {
            Response::error('This flash discount has expired', 400);
        }

        $v = new Validator($body);
        $v->required('customer_phone');
        if ($v->fails()) Response::validationError($v->errors());

        $customerPhone = trim((string)$body['customer_phone']);
        $transactionAmount = !empty($body['transaction_amount']) ? (float)$body['transaction_amount'] : 0;

        // Find customer by phone
        $customer = $this->db->queryOne(
            "SELECT c.id, c.name, u.phone FROM customers c JOIN users u ON u.id = c.user_id WHERE u.phone = ?",
            [$customerPhone]
        );

        $customerId = $customer ? (int)$customer['id'] : null;

        // Calculate discount amount
        $discountAmount = round($transactionAmount * (float)$discount['discount_percentage'] / 100, 2);

        // Increment redemption count
        $this->db->execute(
            'UPDATE flash_discounts SET current_redemptions = current_redemptions + 1 WHERE id = ?',
            [$id]
        );

        // Record in sales_registry if we have a customer and transaction
        if ($transactionAmount > 0 && $customerId) {
            $this->db->execute(
                "INSERT INTO sales_registry (merchant_id, store_id, customer_id, transaction_amount, discount_amount, coupon_used, transaction_date)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $merchantId,
                    $discount['store_id'],
                    $customerId,
                    $transactionAmount,
                    $discountAmount,
                    $id,
                ]
            );
        }

        Response::success([
            'flash_discount_id'   => $id,
            'title'               => $discount['title'],
            'discount_percentage' => $discount['discount_percentage'],
            'discount_amount'     => $discountAmount,
            'transaction_amount'  => $transactionAmount,
            'customer_name'       => $customer['name'] ?? null,
            'current_redemptions' => (int)$discount['current_redemptions'] + 1,
        ], 'Flash discount redeemed successfully');
    }
}
