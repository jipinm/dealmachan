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

        Response::success($rows, 'OK', [
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
}
