<?php
/**
 * Merchant Grievance Controller
 *
 * GET  /merchants/grievances                      → index   (list with status filter)
 * GET  /merchants/grievances/:id                  → show    (detail + messages)
 * POST /merchants/grievances/:id/respond          → respond (legacy: set resolution_notes)
 * PUT  /merchants/grievances/:id/resolve          → resolve (close grievance)
 * GET  /merchants/grievances/:id/messages         → messages (thread)
 * POST /merchants/grievances/:id/messages         → addMessage
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
        // Store-scoped users can only see grievances for their own store
        if (($user['access_scope'] ?? 'merchant') === 'store' && !empty($user['store_id'])) {
            $where  .= ' AND g.store_id = ?';
            $binds[] = (int)$user['store_id'];
        }

        $rows = $this->db->query(
            "SELECT g.id, g.subject, g.description, g.status, g.priority,
                    g.created_at, g.resolved_at, g.resolution_notes,
                    c.name  AS customer_name,
                    u.phone AS customer_phone,
                    s.store_name,
                    (SELECT COUNT(*) FROM grievance_messages gm WHERE gm.grievance_id = g.id) AS message_count
             FROM grievances g
             LEFT JOIN customers c ON c.id = g.customer_id
             LEFT JOIN users    u ON u.id = c.user_id
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
                    u.phone AS customer_phone,
                    u.email AS customer_email,
                    s.store_name
             FROM grievances g
             LEFT JOIN customers c ON c.id = g.customer_id
             LEFT JOIN users    u ON u.id = c.user_id
             LEFT JOIN stores    s ON s.id = g.store_id
             WHERE g.id = ? AND g.merchant_id = ?",
            [$id, $merchantId]
        );

        if (!$grievance) Response::notFound('Grievance not found');

        // Include message thread
        $grievance['messages'] = $this->fetchMessages($id);

        Response::success($grievance);
    }

    // ── GET /merchants/grievances/:id/messages ────────────────────────────────
    public function messages(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            'SELECT id FROM grievances WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$grievance) Response::notFound('Grievance not found');

        Response::success($this->fetchMessages($id));
    }

    // ── POST /merchants/grievances/:id/messages ───────────────────────────────
    public function addMessage(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            'SELECT id, status FROM grievances WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$grievance) Response::notFound('Grievance not found');
        if ($grievance['status'] === 'closed') {
            Response::error('Cannot add messages to a closed grievance.', 400, 'GRIEVANCE_CLOSED');
        }

        $message = trim((string)($body['message'] ?? ''));
        if ($message === '') Response::validationError(['message' => 'Message text is required.']);

        $this->db->execute(
            "INSERT INTO grievance_messages (grievance_id, sender_type, sender_id, message)
             VALUES (?, 'merchant', ?, ?)",
            [$id, $user['id'], $message]
        );

        // Move to in_progress if still open
        if ($grievance['status'] === 'open') {
            $this->db->execute(
                "UPDATE grievances SET status = 'in_progress' WHERE id = ?",
                [$id]
            );
        }

        Response::created([
            'messages' => $this->fetchMessages($id),
        ], 'Message sent.');
    }

    // ── POST /merchants/grievances/:id/respond ────────────────────────────────
    // Legacy endpoint: still supported, just creates a message + updates notes.
    public function respond(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $grievance = $this->db->queryOne(
            'SELECT id, status FROM grievances WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$grievance) Response::notFound('Grievance not found');
        if (in_array($grievance['status'], ['resolved', 'closed'])) {
            Response::error('Grievance is already closed.', 400, 'ALREADY_CLOSED');
        }

        $notes = trim((string)($body['resolution_notes'] ?? $body['response'] ?? ''));
        if ($notes === '') Response::validationError(['resolution_notes' => 'Response text is required.']);

        $this->db->execute(
            "UPDATE grievances SET resolution_notes = ?, status = 'in_progress' WHERE id = ?",
            [$notes, $id]
        );

        // Also save as a message to the thread
        $this->db->execute(
            "INSERT INTO grievance_messages (grievance_id, sender_type, sender_id, message)
             VALUES (?, 'merchant', ?, ?)",
            [$id, $user['id'], $notes]
        );

        $updated             = $this->db->queryOne('SELECT * FROM grievances WHERE id = ?', [$id]);
        $updated['messages'] = $this->fetchMessages($id);
        Response::success($updated, 'Response submitted.');
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
            Response::error('Grievance is already closed.', 400, 'ALREADY_CLOSED');
        }

        $this->db->execute(
            "UPDATE grievances SET status = 'resolved', resolved_at = NOW() WHERE id = ?",
            [$id]
        );

        $updated             = $this->db->queryOne('SELECT * FROM grievances WHERE id = ?', [$id]);
        $updated['messages'] = $this->fetchMessages($id);
        Response::success($updated, 'Grievance resolved.');
    }

    // ── Private: fetch message thread ─────────────────────────────────────────
    private function fetchMessages(int $grievanceId): array {
        return $this->db->query(
            "SELECT gm.id, gm.sender_type, gm.sender_id, gm.message, gm.created_at,
                    CASE gm.sender_type
                        WHEN 'customer' THEN (SELECT c.name FROM customers c WHERE c.id = gm.sender_id)
                        WHEN 'merchant' THEN (SELECT u.email FROM users u WHERE u.id = gm.sender_id)
                        ELSE 'Store'
                    END AS sender_name
             FROM grievance_messages gm
             WHERE gm.grievance_id = ?
             ORDER BY gm.created_at ASC",
            [$grievanceId]
        );
    }
}
