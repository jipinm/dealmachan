<?php
/**
 * Merchant Customer Controller
 *
 * GET  /merchants/customers  → index  (customers linked to this merchant)
 * POST /merchants/customers  → store  (create new customer via merchant app)
 */
class MerchantCustomerController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/customers ──────────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $where  = 'c.created_by_merchant_id = ?';
        $binds  = [$merchantId];

        if (!empty($params['search'])) {
            $where  .= ' AND (c.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)';
            $like    = '%' . $params['search'] . '%';
            $binds[] = $like;
            $binds[] = $like;
            $binds[] = $like;
        }

        $page   = max(1, (int)($params['page'] ?? 1));
        $limit  = min(50, max(10, (int)($params['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $total = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE {$where}",
            $binds
        )['cnt'];

        $rows = $this->db->query(
            "SELECT c.id, c.name, c.customer_type, c.subscription_status, c.created_at,
                    u.phone, u.email, u.status AS user_status
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE {$where}
             ORDER BY c.created_at DESC
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

    // ── POST /merchants/customers ─────────────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $v = new Validator($body);
        $v->required('name');
        if (empty($body['phone']) && empty($body['email'])) {
            Response::validationError(['phone' => 'Phone or email is required']);
        }
        if ($v->fails()) Response::validationError($v->errors());

        $name  = trim((string)$body['name']);
        $phone = !empty($body['phone']) ? trim((string)$body['phone']) : null;
        $email = !empty($body['email']) ? strtolower(trim((string)$body['email'])) : null;

        // Check if user already exists
        if ($phone) {
            $existing = $this->db->queryOne('SELECT id FROM users WHERE phone = ?', [$phone]);
            if ($existing) Response::error('A customer with this phone number already exists', 409);
        }
        if ($email) {
            $existing = $this->db->queryOne('SELECT id FROM users WHERE email = ?', [$email]);
            if ($existing) Response::error('A customer with this email already exists', 409);
        }

        // Create user record (random temporary password — customer will need to reset)
        $tempPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);

        $this->db->execute(
            "INSERT INTO users (email, phone, password_hash, user_type, status)
             VALUES (?, ?, ?, 'customer', 'active')",
            [$email, $phone, $tempPassword]
        );
        $userId = $this->db->lastInsertId();

        // Create customer record
        $referralCode = 'REF' . strtoupper(uniqid());

        $this->db->execute(
            "INSERT INTO customers (user_id, name, registration_type, created_by_merchant_id, referral_code)
             VALUES (?, ?, 'merchant_app', ?, ?)",
            [$userId, $name, $merchantId, $referralCode]
        );
        $customerId = $this->db->lastInsertId();

        $customer = $this->db->queryOne(
            "SELECT c.*, u.phone, u.email FROM customers c JOIN users u ON u.id = c.user_id WHERE c.id = ?",
            [$customerId]
        );

        Response::created($customer, 'Customer created successfully');
    }

    // ── GET /merchants/customers/:id ──────────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $customer = $this->db->queryOne(
            "SELECT c.*, u.phone, u.email, u.status AS user_status
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE c.id = ? AND c.created_by_merchant_id = ?",
            [$id, $merchantId]
        );

        if (!$customer) Response::notFound('Customer not found');

        Response::success($customer);
    }

    // ── PUT /merchants/customers/:id ──────────────────────────────────────────
    public function update(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $customer = $this->db->queryOne(
            "SELECT c.id, c.user_id FROM customers c WHERE c.id = ? AND c.created_by_merchant_id = ?",
            [$id, $merchantId]
        );
        if (!$customer) Response::notFound('Customer not found');

        // Update customer fields
        $custFields = [];
        $custBinds  = [];
        if (array_key_exists('name', $body)) {
            $custFields[] = 'name = ?';
            $custBinds[]  = trim((string)$body['name']);
        }
        if (!empty($custFields)) {
            $custBinds[] = $id;
            $this->db->execute(
                'UPDATE customers SET ' . implode(', ', $custFields) . ' WHERE id = ?',
                $custBinds
            );
        }

        // Update user fields (phone, email)
        $userFields = [];
        $userBinds  = [];
        if (array_key_exists('phone', $body) && $body['phone'] !== null) {
            $phone = trim((string)$body['phone']);
            $dup = $this->db->queryOne(
                'SELECT id FROM users WHERE phone = ? AND id != ?',
                [$phone, $customer['user_id']]
            );
            if ($dup) Response::error('Phone number already in use', 409);
            $userFields[] = 'phone = ?';
            $userBinds[]  = $phone;
        }
        if (array_key_exists('email', $body) && $body['email'] !== null) {
            $email = strtolower(trim((string)$body['email']));
            $dup = $this->db->queryOne(
                'SELECT id FROM users WHERE email = ? AND id != ?',
                [$email, $customer['user_id']]
            );
            if ($dup) Response::error('Email already in use', 409);
            $userFields[] = 'email = ?';
            $userBinds[]  = $email;
        }
        if (!empty($userFields)) {
            $userBinds[] = $customer['user_id'];
            $this->db->execute(
                'UPDATE users SET ' . implode(', ', $userFields) . ' WHERE id = ?',
                $userBinds
            );
        }

        if (empty($custFields) && empty($userFields)) {
            Response::error('Nothing to update', 400);
        }

        $updated = $this->db->queryOne(
            "SELECT c.*, u.phone, u.email, u.status AS user_status
             FROM customers c JOIN users u ON u.id = c.user_id WHERE c.id = ?",
            [$id]
        );
        Response::success($updated, 'Customer updated');
    }

    // ── GET /merchants/customers/:id/analytics ────────────────────────────────
    public function analytics(array $user, int $id, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $customer = $this->db->queryOne(
            "SELECT c.id, c.user_id, c.name FROM customers c WHERE c.id = ? AND c.created_by_merchant_id = ?",
            [$id, $merchantId]
        );
        if (!$customer) Response::notFound('Customer not found');

        $from = !empty($params['from']) ? $params['from'] : null;
        $to   = !empty($params['to'])   ? $params['to']   : null;

        $dateWhere = '';
        $dateBinds = [];
        if ($from) {
            $dateWhere .= ' AND sr.transaction_date >= ?';
            $dateBinds[] = $from;
        }
        if ($to) {
            $dateWhere .= ' AND sr.transaction_date <= ?';
            $dateBinds[] = $to . ' 23:59:59';
        }

        // Total coupon assignments for this customer from this merchant
        $couponAssignments = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM store_coupons
             WHERE merchant_id = ? AND gifted_to_customer_id = ?",
            [$merchantId, $id]
        )['cnt'];

        // Transaction data from sales_registry
        $txData = $this->db->queryOne(
            "SELECT COUNT(*) AS total_transactions,
                    COALESCE(SUM(sr.transaction_amount), 0) AS total_value,
                    COALESCE(MAX(sr.transaction_amount), 0) AS highest_transaction,
                    MAX(sr.transaction_date) AS last_visit
             FROM sales_registry sr
             WHERE sr.merchant_id = ? AND sr.customer_id = ?{$dateWhere}",
            array_merge([$merchantId, $id], $dateBinds)
        );

        $totalTx    = (int)($txData['total_transactions'] ?? 0);
        $totalValue = (float)($txData['total_value'] ?? 0);
        $highestTx  = (float)($txData['highest_transaction'] ?? 0);
        $lastVisit  = $txData['last_visit'] ?? null;

        // Average frequency (days between transactions)
        $avgFrequency = 0;
        if ($totalTx > 1) {
            $freq = $this->db->queryOne(
                "SELECT DATEDIFF(MAX(sr.transaction_date), MIN(sr.transaction_date)) / (COUNT(*) - 1) AS avg_days
                 FROM sales_registry sr
                 WHERE sr.merchant_id = ? AND sr.customer_id = ?{$dateWhere}",
                array_merge([$merchantId, $id], $dateBinds)
            );
            $avgFrequency = round((float)($freq['avg_days'] ?? 0), 1);
        }

        // Response rate (redeemed coupons / assigned coupons)
        $assignedCount = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM store_coupons WHERE merchant_id = ? AND gifted_to_customer_id = ?",
            [$merchantId, $id]
        )['cnt'];
        $redeemedCount = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM store_coupons WHERE merchant_id = ? AND gifted_to_customer_id = ? AND is_redeemed = 1",
            [$merchantId, $id]
        )['cnt'];
        $responseRate = $assignedCount > 0 ? round(($redeemedCount / $assignedCount) * 100, 1) : 0;

        Response::success([
            'customer'                => $customer,
            'total_coupon_assignments' => $couponAssignments,
            'total_transactions'       => $totalTx,
            'total_transaction_value'  => $totalValue,
            'highest_transaction'      => $highestTx,
            'last_visit_date'          => $lastVisit,
            'average_frequency_days'   => $avgFrequency,
            'response_rate'            => $responseRate,
        ]);
    }

    // ── GET /merchants/redemption/customer-lookup ─────────────────────────────
    public function customerLookup(array $user, array $params = []): never {
        if (empty($params['search'])) {
            Response::validationError(['search' => 'Phone or email is required']);
        }

        $search = trim((string)$params['search']);

        $customer = $this->db->queryOne(
            "SELECT c.id, c.name, c.customer_type, u.phone, u.email
             FROM customers c
             JOIN users u ON u.id = c.user_id
             WHERE u.phone = ? OR u.email = ?",
            [$search, $search]
        );

        if (!$customer) Response::notFound('Customer not found');

        $merchantId = (int)$user['merchant_id'];

        // Collect all redeemable items for this customer from this merchant

        // 1. Platform coupons saved/subscribed by the customer from this merchant
        $platformCoupons = $this->db->query(
            "SELECT cp.id, 'platform_coupon' AS type, cp.title, cp.discount_type, cp.discount_value, cp.coupon_code
             FROM coupons cp
             JOIN coupon_subscriptions cs ON cs.coupon_id = cp.id
             WHERE cs.customer_id = ? AND cp.merchant_id = ? AND cp.status = 'active'
               AND (cp.valid_until IS NULL OR cp.valid_until >= CURDATE())",
            [$customer['id'], $merchantId]
        );

        // 2. Store coupons gifted to this customer
        $storeCoupons = $this->db->query(
            "SELECT sc.id, 'store_coupon' AS type,
                    CONCAT('Store Coupon: ', sc.coupon_code) AS title,
                    sc.discount_type, sc.discount_value, sc.coupon_code
             FROM store_coupons sc
             WHERE sc.gifted_to_customer_id = ? AND sc.merchant_id = ?
               AND sc.status = 'active' AND sc.is_redeemed = 0
               AND (sc.valid_until IS NULL OR sc.valid_until >= NOW())",
            [$customer['id'], $merchantId]
        );

        // 3. Flash discounts from this merchant (available to all customers)
        $flashDiscounts = $this->db->query(
            "SELECT fd.id, 'flash_discount' AS type, fd.title,
                    'percentage' AS discount_type, fd.discount_percentage AS discount_value,
                    NULL AS coupon_code
             FROM flash_discounts fd
             WHERE fd.merchant_id = ? AND fd.status = 'active'
               AND (fd.valid_until IS NULL OR fd.valid_until >= NOW())
               AND (fd.max_redemptions IS NULL OR fd.current_redemptions < fd.max_redemptions)",
            [$merchantId]
        );

        $redeemableItems = array_merge($platformCoupons, $storeCoupons, $flashDiscounts);

        Response::success([
            'customer'         => $customer,
            'redeemable_items' => $redeemableItems,
        ]);
    }
}
