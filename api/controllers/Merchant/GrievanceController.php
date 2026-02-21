<?php
/**
 * Merchant Grievance Controller
 *
 * GET  /merchants/grievances                → index   (list with status filter)
 * GET  /merchants/grievances/:id            → show    (detail)
 * POST /merchants/grievances/:id/respond    → respond (set resolution_notes + in_progress)
 * PUT  /merchants/grievances/:id/resolve    → resolve (close grievance)
 */
class GrievanceController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/grievances ──────────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $where  = 'g.merchant_id = ?';
        $binds  = [$merchantId];

        if (!empty($params['status'])) {
            $where  .= ' AND g.status = ?';
            $binds[] = $params['status'];
        }

        $rows = $this->db->query(
            "SELECT g.id, g.subject, g.description, g.status, g.priority,
                    g.created_at, g.resolved_at, g.resolution_notes,
                    c.name  AS customer_name,
                    c.phone AS customer_phone,
                    s.store_name
             FROM grievances g
             LEFT JOIN customers c ON c.id = g.customer_id
             LEFT JOIN stores    s ON s.id = g.store_id
             WHERE {$where}
             ORDER BY
               FIELD(g.status, 'open', 'in_progress', 'resolved', 'closed'),
               FIELD(g.priority, 'urgent', 'high', 'medium', 'low'),
               g.created_at DESC",
            $binds
        );

        // Count by status
        $counts = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM grievances WHERE merchant_id = ? GROUP BY status",
            [$merchantId]
        );
        $countMap = array_column($counts, 'cnt', 'status');

        Response::success($rows, 'OK', 200, [
            'counts' => [
                'open'        => (int)($countMap['open']        ?? 0),
                'in_progress' => (int)($countMap['in_progress'] ?? 0),
                'resolved'    => (int)($countMap['resolved']    ?? 0),
                'closed'      => (int)($countMap['closed']      ?? 0),
            ],
        ]);
    }

    // ── GET /merchants/grievances/:id ──────────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            "SELECT g.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone,
                    c.email AS customer_email,
                    s.store_name
             FROM grievances g
             LEFT JOIN customers c ON c.id = g.customer_id
             LEFT JOIN stores    s ON s.id = g.store_id
             WHERE g.id = ? AND g.merchant_id = ?",
            [$id, $merchantId]
        );

        if (!$grievance) Response::notFound('Grievance not found');
        Response::success($grievance);
    }

    // ── POST /merchants/grievances/:id/respond ────────────────────────────────
    public function respond(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            'SELECT id, status FROM grievances WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$grievance) Response::notFound('Grievance not found');
        if (in_array($grievance['status'], ['resolved', 'closed'])) {
            Response::error('Grievance is already closed', 400);
        }

        $notes = trim((string)($body['resolution_notes'] ?? $body['response'] ?? ''));
        if ($notes === '') Response::validationError(['resolution_notes' => 'Response text is required']);

        $this->db->execute(
            "UPDATE grievances SET resolution_notes = ?, status = 'in_progress' WHERE id = ?",
            [$notes, $id]
        );

        $updated = $this->db->queryOne('SELECT * FROM grievances WHERE id = ?', [$id]);
        Response::success($updated, 'Response submitted');
    }

    // ── PUT /merchants/grievances/:id/resolve ─────────────────────────────────
    public function resolve(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            'SELECT id, status FROM grievances WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$grievance) Response::notFound('Grievance not found');
        if ($grievance['status'] === 'closed') {
            Response::error('Grievance is already closed', 400);
        }

        $this->db->execute(
            "UPDATE grievances SET status = 'resolved', resolved_at = NOW() WHERE id = ?",
            [$id]
        );

        $updated = $this->db->queryOne('SELECT * FROM grievances WHERE id = ?', [$id]);
        Response::success($updated, 'Grievance resolved');
    }
}
