<?php
/**
 * Customer Grievance Controller
 *
 * GET    /api/customers/grievances           - List the authed customer's grievances
 * GET    /api/customers/grievances/:id       - Single grievance (ownership enforced)
 * POST   /api/customers/grievances           - Submit a new grievance
 * PUT    /api/customers/grievances/:id/archive - Close (archive) a grievance
 */
class CustomerGrievanceController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /** Resolve customer.id from user.id */
    private function getCustomerId(int $userId): int {
        $row = $this->db->queryOne(
            "SELECT id FROM customers WHERE user_id = ?",
            [$userId]
        );
        if (!$row) Response::error('Customer profile not found.', 404);
        return (int)$row['id'];
    }

    // ── GET /api/customers/grievances ─────────────────────────────────────────
    public function index(array $user, array $query): never {
        $page    = max(1, (int)($query['page']     ?? 1));
        $perPage = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $status  = $query['status'] ?? null;
        $offset  = ($page - 1) * $perPage;

        $where  = 'g.customer_id = ?';
        $customerId = $this->getCustomerId($user['id']);
        $params = [$customerId];

        $allowed = ['open', 'in_progress', 'resolved', 'closed'];
        if ($status && in_array($status, $allowed)) {
            $where   .= ' AND g.status = ?';
            $params[] = $status;
        }

        $grievances = $this->db->query(
            "SELECT g.id, g.subject, g.status, g.priority, g.created_at,
                    g.resolved_at, g.resolution_notes,
                    m.id AS merchant_id, m.business_name, m.business_logo,
                    s.id AS store_id, s.store_name
             FROM grievances g
             JOIN merchants  m ON m.id = g.merchant_id
             LEFT JOIN stores s ON s.id = g.store_id
             WHERE {$where}
             ORDER BY g.created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM grievances g WHERE {$where}",
            $params
        )['cnt'] ?? 0;

        Response::success([
            'data'       => $grievances,
            'pagination' => [
                'total'    => (int)$total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET /api/customers/grievances/:id ──────────────────────────────
    public function show(array $user, int $id): never {
        $customerId = $this->getCustomerId($user['id']);

        $row = $this->db->queryOne(
            "SELECT g.id, g.subject, g.description, g.status, g.priority,
                    g.created_at, g.resolved_at, g.resolution_notes,
                    m.id AS merchant_id, m.business_name, m.business_logo,
                    s.id AS store_id, s.store_name, s.address AS store_address
             FROM grievances g
             JOIN merchants  m ON m.id = g.merchant_id
             LEFT JOIN stores s ON s.id = g.store_id
             WHERE g.id = ? AND g.customer_id = ?",
            [$id, $customerId]
        );

        if (!$row) {
            Response::error('Grievance not found.', 404, 'NOT_FOUND');
        }

        Response::success(['data' => $row]);
    }

    // ── POST /api/customers/grievances ───────────────────────────────────────
    public function store(array $user, array $body): never {
        $customerId  = $this->getCustomerId($user['id']);
        $merchantId  = isset($body['merchant_id'])  ? (int)$body['merchant_id']  : null;
        $storeId     = isset($body['store_id'])      ? (int)$body['store_id']      : null;
        $subject     = trim($body['subject']     ?? '');
        $description = trim($body['description'] ?? '');

        if (!$merchantId) {
            Response::error('merchant_id is required.', 422, 'VALIDATION_ERROR');
        }
        if (strlen($subject) < 5) {
            Response::error('Subject must be at least 5 characters.', 422, 'VALIDATION_ERROR');
        }
        if (strlen($description) < 20) {
            Response::error('Description must be at least 20 characters.', 422, 'VALIDATION_ERROR');
        }

        // Verify merchant exists
        $merchant = $this->db->queryOne(
            "SELECT id FROM merchants WHERE id = ? AND profile_status = 'approved'",
            [$merchantId]
        );
        if (!$merchant) {
            Response::error('Merchant not found.', 404, 'NOT_FOUND');
        }

        // Verify store belongs to merchant (if provided)
        if ($storeId) {
            $store = $this->db->queryOne(
                "SELECT id FROM stores WHERE id = ? AND merchant_id = ?",
                [$storeId, $merchantId]
            );
            if (!$store) {
                $storeId = null; // silently ignore invalid store
            }
        }

        $this->db->execute(
            "INSERT INTO grievances (customer_id, merchant_id, store_id, subject, description)
             VALUES (?, ?, ?, ?, ?)",
            [$customerId, $merchantId, $storeId ?: null, $subject, $description]
        );

        $newId = $this->db->lastInsertId();

        $created = $this->db->queryOne(
            "SELECT g.id, g.subject, g.status, g.priority, g.created_at,
                    m.business_name
             FROM grievances g
             JOIN merchants m ON m.id = g.merchant_id
             WHERE g.id = ?",
            [$newId]
        );

        Response::success(['data' => $created], 'Grievance submitted successfully.', 201);
    }

    // ── PUT /api/customers/grievances/:id/archive ───────────────────────
    public function archive(array $user, int $id): never {
        $customerId = $this->getCustomerId($user['id']);

        $row = $this->db->queryOne(
            "SELECT id, status FROM grievances WHERE id = ? AND customer_id = ?",
            [$id, $customerId]
        );
        if (!$row) Response::error('Grievance not found.', 404, 'NOT_FOUND');

        $this->db->execute(
            "UPDATE grievances SET status = 'closed' WHERE id = ?",
            [$id]
        );

        Response::success(null, 'Grievance archived.');
    }
}
