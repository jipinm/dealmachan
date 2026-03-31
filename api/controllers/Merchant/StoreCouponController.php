<?php
/**
 * Store Coupon Controller
 *
 * Merchant-created store-level coupons that can be gifted (assigned) to customers.
 *
 * GET    /merchants/store-coupons              → index
 * POST   /merchants/store-coupons              → store
 * GET    /merchants/store-coupons/:id          → show
 * PUT    /merchants/store-coupons/:id          → update
 * DELETE /merchants/store-coupons/:id          → destroy
 * POST   /merchants/store-coupons/:id/gift     → gift  (assign to customer)
 * POST   /merchants/store-coupons/:id/assign   → assign (alias)
 * POST   /merchants/store-coupons/:id/bulk-assign → bulkAssign
 * POST   /merchants/store-coupons/:id/request-assignment → requestAssignment
 * POST   /merchants/store-coupons/:id/redeem   → redeem
 */
class StoreCouponController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/store-coupons ──────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $where = 'sc.merchant_id = ?';
        $binds = [$merchantId];

        if (!empty($params['status'])) {
            $where  .= ' AND sc.status = ?';
            $binds[] = $params['status'];
        }
        // Store-scoped users can only see store coupons for their own store
        if (($user['access_scope'] ?? 'merchant') === 'store' && !empty($user['store_id'])) {
            $params['store_id'] = (int)$user['store_id'];
        }
        if (!empty($params['store_id'])) {
            $where  .= ' AND sc.store_id = ?';
            $binds[] = (int)$params['store_id'];
        }

        // Auto-expire past coupons
        $this->db->execute(
            "UPDATE store_coupons SET status = 'expired'
             WHERE merchant_id = ? AND valid_until < NOW() AND status = 'active'",
            [$merchantId]
        );

        $page   = max(1, (int)($params['page'] ?? 1));
        $limit  = min(50, max(10, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $total = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM store_coupons sc WHERE {$where}",
            $binds
        )['cnt'];

        $rows = $this->db->query(
            "SELECT sc.*, s.store_name,
                    c.name AS gifted_to_name
             FROM store_coupons sc
             LEFT JOIN stores s ON s.id = sc.store_id
             LEFT JOIN customers c ON c.id = sc.gifted_to_customer_id
             WHERE {$where}
             ORDER BY sc.created_at DESC
             LIMIT ? OFFSET ?",
            [...$binds, $limit, $offset]
        );

        Response::success($rows, 'OK', 200, [
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
            'pages' => (int)ceil($total / $limit),
        ]);
    }

    // ── GET /merchants/store-coupons/:id ──────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            "SELECT sc.*, s.store_name, c.name AS gifted_to_name
             FROM store_coupons sc
             LEFT JOIN stores s ON s.id = sc.store_id
             LEFT JOIN customers c ON c.id = sc.gifted_to_customer_id
             WHERE sc.id = ? AND sc.merchant_id = ?",
            [$id, $merchantId]
        );

        if (!$coupon) Response::notFound('Store coupon not found');

        $merchantLimits = $this->db->queryOne(
            "SELECT coupon_limit, monthly_assignment_limit
             FROM merchants
             WHERE id = ? LIMIT 1",
            [$merchantId]
        ) ?: ['coupon_limit' => null, 'monthly_assignment_limit' => null];

        $monthlyUsed = (int)($this->db->queryOne(
            "SELECT COALESCE(SUM(cba.quantity), 0) AS used_qty
             FROM coupon_bulk_allotments cba
             JOIN stores s ON s.id = cba.store_id
             WHERE s.merchant_id = ?
               AND cba.status = 'approved'
               AND YEAR(cba.created_at) = YEAR(CURDATE())
               AND MONTH(cba.created_at) = MONTH(CURDATE())",
            [$merchantId]
        )['used_qty'] ?? 0);

        $totalUsed = (int)($this->db->queryOne(
            "SELECT COALESCE(SUM(cba.quantity), 0) AS used_qty
             FROM coupon_bulk_allotments cba
             JOIN stores s ON s.id = cba.store_id
             WHERE s.merchant_id = ?
               AND cba.status = 'approved'",
            [$merchantId]
        )['used_qty'] ?? 0);

        $coupon['coupon_usage'] = [
            'this_month'    => $monthlyUsed,
            'monthly_limit' => $merchantLimits['monthly_assignment_limit'] !== null ? (int)$merchantLimits['monthly_assignment_limit'] : null,
            'total_limit'   => $merchantLimits['coupon_limit'] !== null ? (int)$merchantLimits['coupon_limit'] : null,
            'total_used'    => $totalUsed,
        ];

        Response::success($coupon);
    }

    // ── POST /merchants/store-coupons/:id/request-assignment ────────────────
    public function requestAssignment(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];
        $requestedBy = (int)($user['id'] ?? 0);

        $quantity = (int)($body['quantity'] ?? 0);
        if ($quantity <= 0) {
            Response::validationError(['quantity' => 'Quantity must be greater than 0']);
        }

        $coupon = $this->db->queryOne(
                "SELECT sc.id, sc.title, sc.description, sc.coupon_code, sc.store_id, sc.merchant_id,
                    sc.discount_type, sc.discount_value, sc.valid_from, sc.valid_until,
                    sc.assignment_type, sc.total_quantity, sc.assigned_count, sc.status
             FROM store_coupons sc
             WHERE sc.id = ? AND sc.merchant_id = ?",
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Store coupon not found');

        if (($coupon['status'] ?? '') !== 'active') {
            Response::error('Store coupon must be active to request assignment.', 409, 'STORE_COUPON_INACTIVE');
        }

        if (($coupon['assignment_type'] ?? 'merchant_request') !== 'merchant_request') {
            Response::error('This store coupon is auto-assigned and cannot be requested.', 409, 'STORE_COUPON_AUTO_ASSIGN');
        }

        $limits = $this->db->queryOne(
            "SELECT coupon_limit, monthly_assignment_limit
             FROM merchants
             WHERE id = ? LIMIT 1",
            [$merchantId]
        );
        if (!$limits) Response::notFound('Merchant not found');

        $monthlyUsed = (int)($this->db->queryOne(
            "SELECT COALESCE(SUM(cba.quantity), 0) AS used_qty
             FROM coupon_bulk_allotments cba
             JOIN stores s ON s.id = cba.store_id
             WHERE s.merchant_id = ?
               AND cba.status = 'approved'
               AND YEAR(cba.created_at) = YEAR(CURDATE())
               AND MONTH(cba.created_at) = MONTH(CURDATE())",
            [$merchantId]
        )['used_qty'] ?? 0);

        $totalUsed = (int)($this->db->queryOne(
            "SELECT COALESCE(SUM(cba.quantity), 0) AS used_qty
             FROM coupon_bulk_allotments cba
             JOIN stores s ON s.id = cba.store_id
             WHERE s.merchant_id = ?
               AND cba.status = 'approved'",
            [$merchantId]
        )['used_qty'] ?? 0);

        $monthlyLimit = $limits['monthly_assignment_limit'] !== null ? (int)$limits['monthly_assignment_limit'] : null;
        if ($monthlyLimit !== null && ($monthlyUsed + $quantity) > $monthlyLimit) {
            Response::error('Monthly assignment limit exceeded.', 409, 'MONTHLY_LIMIT_EXCEEDED');
        }

        $totalLimit = $limits['coupon_limit'] !== null ? (int)$limits['coupon_limit'] : null;
        if ($totalLimit !== null && ($totalUsed + $quantity) > $totalLimit) {
            Response::error('Total coupon assignment limit exceeded.', 409, 'TOTAL_LIMIT_EXCEEDED');
        }

        if ($coupon['total_quantity'] !== null) {
            $remaining = (int)$coupon['total_quantity'] - (int)$coupon['assigned_count'];
            if ($quantity > $remaining) {
                Response::error('Requested quantity exceeds available store coupon quantity.', 409, 'STORE_COUPON_QTY_EXCEEDED');
            }
        }

        try {
            $this->db->beginTransaction();

            $linkedCouponId = $this->ensureCouponMirror($coupon, $requestedBy);

            $this->db->execute(
                "INSERT INTO coupon_bulk_allotments (coupon_id, store_id, requested_by, quantity, status, created_at)
                 VALUES (?, ?, ?, ?, 'pending', NOW())",
                [$linkedCouponId, (int)$coupon['store_id'], $requestedBy, $quantity]
            );

            $requestId = (int)$this->db->lastInsertId();
            $this->db->commit();

            Response::created([
                'request_id'      => $requestId,
                'store_coupon_id' => (int)$coupon['id'],
                'quantity'        => $quantity,
                'status'          => 'pending',
                'coupon_usage'    => [
                    'this_month'    => $monthlyUsed,
                    'monthly_limit' => $monthlyLimit,
                    'total_limit'   => $totalLimit,
                    'total_used'    => $totalUsed,
                ],
            ], 'Bulk assignment request submitted successfully.');
        } catch (Throwable $e) {
            if ($this->db->pdo()->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('requestAssignment error: ' . $e->getMessage());
            Response::error('Failed to submit assignment request.', 500, 'REQUEST_ASSIGNMENT_FAILED');
        }
    }

    private function ensureCouponMirror(array $storeCoupon, int $createdBy): int {
        $existing = $this->db->queryOne(
            "SELECT id
             FROM coupons
             WHERE coupon_code = ?
               AND merchant_id = ?
               AND (store_id = ? OR (store_id IS NULL AND ? IS NULL))
             LIMIT 1",
            [
                $storeCoupon['coupon_code'],
                (int)$storeCoupon['merchant_id'],
                $storeCoupon['store_id'] !== null ? (int)$storeCoupon['store_id'] : null,
                $storeCoupon['store_id'] !== null ? (int)$storeCoupon['store_id'] : null,
            ]
        );

        if ($existing) {
            return (int)$existing['id'];
        }

        $this->db->execute(
            "INSERT INTO coupons
                (title, description, coupon_code, discount_type, discount_value,
                 merchant_id, store_id, valid_from, valid_until,
                 is_admin_coupon, approval_status, status, terms_conditions, created_by, created_at)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'approved', 'active', NULL, ?, NOW())",
            [
                $storeCoupon['title'] ?: ('Store Coupon ' . $storeCoupon['coupon_code']),
                $storeCoupon['description'] ?? null,
                $storeCoupon['coupon_code'],
                $storeCoupon['discount_type'],
                (float)$storeCoupon['discount_value'],
                (int)$storeCoupon['merchant_id'],
                $storeCoupon['store_id'] !== null ? (int)$storeCoupon['store_id'] : null,
                $storeCoupon['valid_from'] ?: null,
                $storeCoupon['valid_until'] ?: null,
                $createdBy > 0 ? $createdBy : 1,
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    // ── POST /merchants/store-coupons ─────────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];
        // For store-scoped users, force store_id to their assigned store
        if (($user['access_scope'] ?? 'merchant') === 'store' && !empty($user['store_id'])) {
            $body['store_id'] = (int)$user['store_id'];
        }
        $v = new Validator($body);
        $v->required('store_id')
          ->required('coupon_code')
          ->required('discount_type')
          ->required('discount_value');
        if ($v->fails()) Response::validationError($v->errors());

        // Validate store ownership
        $store = $this->db->queryOne(
            'SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL',
            [(int)$body['store_id'], $merchantId]
        );
        if (!$store) Response::notFound('Store not found or does not belong to you');

        // Check coupon_code uniqueness
        $existing = $this->db->queryOne(
            'SELECT id FROM store_coupons WHERE coupon_code = ?',
            [trim((string)$body['coupon_code'])]
        );
        if ($existing) Response::error('Coupon code already exists', 409);

        $discountType = in_array($body['discount_type'], ['percentage', 'fixed']) ? $body['discount_type'] : 'percentage';

        $this->db->execute(
            "INSERT INTO store_coupons
               (merchant_id, store_id, coupon_code, discount_type, discount_value,
                valid_from, valid_until, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'active')",
            [
                $merchantId,
                (int)$body['store_id'],
                strtoupper(trim((string)$body['coupon_code'])),
                $discountType,
                (float)$body['discount_value'],
                !empty($body['valid_from'])  ? $body['valid_from']  : null,
                !empty($body['valid_until']) ? $body['valid_until'] : null,
            ]
        );

        $row = $this->db->queryOne('SELECT * FROM store_coupons WHERE id = ?', [$this->db->lastInsertId()]);
        Response::created($row, 'Store coupon created');
    }

    // ── PUT /merchants/store-coupons/:id ──────────────────────────────────────
    public function update(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id FROM store_coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Store coupon not found');

        $fields = [];
        $binds  = [];

        $allowed = ['coupon_code', 'discount_type', 'discount_value', 'valid_from', 'valid_until', 'status'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $body)) {
                $fields[] = "`{$f}` = ?";
                $binds[]  = $body[$f] === '' ? null : $body[$f];
            }
        }
        if (!empty($body['store_id'])) {
            $store = $this->db->queryOne(
                'SELECT id FROM stores WHERE id = ? AND merchant_id = ? AND deleted_at IS NULL',
                [(int)$body['store_id'], $merchantId]
            );
            if (!$store) Response::notFound('Store not found');
            $fields[] = 'store_id = ?';
            $binds[]  = (int)$body['store_id'];
        }

        if (empty($fields)) Response::error('Nothing to update', 400);

        $binds[] = $id;
        $this->db->execute(
            'UPDATE store_coupons SET ' . implode(', ', $fields) . ' WHERE id = ?',
            $binds
        );

        $row = $this->db->queryOne('SELECT * FROM store_coupons WHERE id = ?', [$id]);
        Response::success($row, 'Updated');
    }

    // ── DELETE /merchants/store-coupons/:id ───────────────────────────────────
    public function destroy(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $existing = $this->db->queryOne(
            'SELECT id FROM store_coupons WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$existing) Response::notFound('Store coupon not found');

        $this->db->execute('DELETE FROM store_coupons WHERE id = ?', [$id]);
        Response::success(['id' => $id], 'Deleted');
    }

    // ── POST /merchants/store-coupons/:id/gift ────────────────────────────────
    // Also used as /assign
    public function gift(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            "SELECT * FROM store_coupons WHERE id = ? AND merchant_id = ? AND status = 'active'",
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Store coupon not found or not active');

        if ((int)$coupon['is_gifted'] === 1) {
            Response::error('This coupon is already assigned to a customer', 409);
        }

        $v = new Validator($body);
        $v->required('customer_id');
        if ($v->fails()) Response::validationError($v->errors());

        $customerId = (int)$body['customer_id'];
        $customer = $this->db->queryOne('SELECT id FROM customers WHERE id = ?', [$customerId]);
        if (!$customer) Response::notFound('Customer not found');

        $this->db->execute(
            "UPDATE store_coupons SET is_gifted = 1, gifted_to_customer_id = ?, gifted_at = NOW() WHERE id = ?",
            [$customerId, $id]
        );

        $updated = $this->db->queryOne(
            "SELECT sc.*, c.name AS gifted_to_name
             FROM store_coupons sc
             LEFT JOIN customers c ON c.id = sc.gifted_to_customer_id
             WHERE sc.id = ?",
            [$id]
        );
        Response::success($updated, 'Coupon assigned to customer');
    }

    // ── POST /merchants/store-coupons/:id/assign ──────────────────────────────
    public function assign(array $user, int $id, array $body): never {
        $this->gift($user, $id, $body);
    }

    // ── POST /merchants/store-coupons/:id/bulk-assign ─────────────────────────
    public function bulkAssign(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        if (empty($body['customer_ids']) || !is_array($body['customer_ids'])) {
            Response::validationError(['customer_ids' => 'An array of customer IDs is required']);
        }

        $customerIds = array_map('intval', $body['customer_ids']);
        if (count($customerIds) > 10) {
            Response::error('Maximum 10 customers per bulk assign', 400);
        }

        // The original coupon serves as a template — create copies for each customer
        $coupon = $this->db->queryOne(
            "SELECT * FROM store_coupons WHERE id = ? AND merchant_id = ? AND status = 'active'",
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Store coupon not found or not active');

        $assigned = [];
        foreach ($customerIds as $custId) {
            $customer = $this->db->queryOne('SELECT id, name FROM customers WHERE id = ?', [$custId]);
            if (!$customer) continue;

            // Generate a unique code for the copy
            $newCode = $coupon['coupon_code'] . '-C' . $custId;
            $existingCode = $this->db->queryOne('SELECT id FROM store_coupons WHERE coupon_code = ?', [$newCode]);
            if ($existingCode) {
                $newCode .= '-' . substr(uniqid(), -4);
            }

            $this->db->execute(
                "INSERT INTO store_coupons
                   (merchant_id, store_id, coupon_code, discount_type, discount_value,
                    valid_from, valid_until, is_gifted, gifted_to_customer_id, gifted_at, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), 'active')",
                [
                    $merchantId,
                    $coupon['store_id'],
                    $newCode,
                    $coupon['discount_type'],
                    $coupon['discount_value'],
                    $coupon['valid_from'],
                    $coupon['valid_until'],
                    $custId,
                ]
            );
            $assigned[] = ['customer_id' => $custId, 'customer_name' => $customer['name'], 'coupon_code' => $newCode];
        }

        Response::success([
            'assigned_count' => count($assigned),
            'assignments'    => $assigned,
        ], 'Bulk assignment complete');
    }

    // ── POST /merchants/store-coupons/:id/redeem ──────────────────────────────
    public function redeem(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $coupon = $this->db->queryOne(
            "SELECT * FROM store_coupons WHERE id = ? AND merchant_id = ? AND status = 'active'",
            [$id, $merchantId]
        );
        if (!$coupon) Response::notFound('Store coupon not found or not active');

        if ((int)$coupon['is_redeemed'] === 1) {
            Response::error('This coupon has already been redeemed', 409);
        }

        $transactionAmount = !empty($body['transaction_amount']) ? (float)$body['transaction_amount'] : 0;

        // Calculate discount
        $discountAmount = 0;
        if ($coupon['discount_type'] === 'percentage') {
            $discountAmount = round($transactionAmount * (float)$coupon['discount_value'] / 100, 2);
        } else {
            $discountAmount = (float)$coupon['discount_value'];
        }

        // Mark as redeemed
        $this->db->execute(
            "UPDATE store_coupons SET is_redeemed = 1, redeemed_at = NOW() WHERE id = ?",
            [$id]
        );

        // Optionally record in sales_registry
        if ($transactionAmount > 0 && $coupon['gifted_to_customer_id']) {
            $this->db->execute(
                "INSERT INTO sales_registry (merchant_id, store_id, customer_id, transaction_amount, discount_amount, coupon_used, transaction_date)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [
                    $merchantId,
                    $coupon['store_id'],
                    $coupon['gifted_to_customer_id'],
                    $transactionAmount,
                    $discountAmount,
                    $id,
                ]
            );
        }

        Response::success([
            'coupon_id'          => $id,
            'coupon_code'        => $coupon['coupon_code'],
            'discount_type'      => $coupon['discount_type'],
            'discount_value'     => $coupon['discount_value'],
            'discount_amount'    => $discountAmount,
            'transaction_amount' => $transactionAmount,
        ], 'Store coupon redeemed successfully');
    }
}
