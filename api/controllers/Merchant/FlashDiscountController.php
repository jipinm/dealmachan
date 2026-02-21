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

        $row = $this->db->queryOne('SELECT * FROM flash_discounts WHERE id = ?', [$this->db->lastInsertId()]);
        Response::created($row, 'Flash discount created');
    }

    // ── PUT /merchants/flash-discounts/:id ────────────────────────────────────
    public function update(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Flash discount not found');

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
        Response::success($row, 'Updated');
    }

    // ── DELETE /merchants/flash-discounts/:id ─────────────────────────────────
    public function destroy(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id FROM flash_discounts WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Flash discount not found');

        $this->db->execute('DELETE FROM flash_discounts WHERE id = ?', [$id]);
        Response::success(['id' => $id], 'Deleted');
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
