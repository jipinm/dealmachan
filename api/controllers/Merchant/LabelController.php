<?php
/**
 * Label Controller
 *
 * GET  /merchants/labels          → index   (assigned + all available)
 * POST /merchants/labels/request  → request (send request message to admin)
 */
class LabelController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/labels ─────────────────────────────────────────────────
    public function index(array $user): never {
        $merchantId = (int)$user['merchant_id'];

        // Labels already assigned to this merchant
        $assigned = $this->db->query(
            "SELECT l.id, l.label_name, l.label_icon, l.description, l.priority_weight,
                    ml.assigned_at
             FROM merchant_labels ml
             JOIN labels l ON l.id = ml.label_id
             WHERE ml.merchant_id = ? AND l.status = 'active'
             ORDER BY l.priority_weight DESC",
            [$merchantId]
        );

        $assignedIds = array_column($assigned, 'id');

        // All available labels (so merchant knows what exists to request)
        $all = $this->db->query(
            "SELECT id, label_name, label_icon, description, priority_weight
             FROM labels WHERE status = 'active'
             ORDER BY priority_weight DESC",
            []
        );

        // Mark which are already assigned
        foreach ($all as &$label) {
            $label['assigned'] = in_array((int)$label['id'], array_map('intval', $assignedIds));
        }
        unset($label);

        Response::success([
            'assigned'  => $assigned,
            'available' => $all,
        ]);
    }

    // ── POST /merchants/labels/request ────────────────────────────────────────
    public function request(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $labelId = !empty($body['label_id']) ? (int)$body['label_id'] : null;
        $note    = trim((string)($body['note'] ?? ''));

        if (!$labelId) Response::validationError(['label_id' => 'Label ID is required']);

        // Verify label exists
        $label = $this->db->queryOne('SELECT id, label_name FROM labels WHERE id = ? AND status = "active"', [$labelId]);
        if (!$label) Response::notFound('Label not found');

        // Check if already assigned
        $already = $this->db->queryOne(
            'SELECT id FROM merchant_labels WHERE merchant_id = ? AND label_id = ?',
            [$merchantId, $labelId]
        );
        if ($already) Response::error('Label already assigned to your account', 400);

        // Get merchant name for the message
        $merchant = $this->db->queryOne('SELECT business_name FROM merchants WHERE id = ?', [$merchantId]);
        $bizName  = $merchant['business_name'] ?? 'Merchant #' . $merchantId;

        // Post as message to admin
        $admin    = $this->db->queryOne('SELECT id FROM admins ORDER BY id LIMIT 1', []);
        $adminId  = $admin ? (int)$admin['id'] : 1;

        $msgText  = "Label request from {$bizName}:\n\nRequested label: {$label['label_name']} (ID: {$labelId})";
        if ($note !== '') {
            $msgText .= "\n\nNote: {$note}";
        }

        $this->db->execute(
            "INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, message_text)
             VALUES (?, 'merchant', ?, 'admin', ?, ?)",
            [
                $merchantId,
                $adminId,
                "Label Request: {$label['label_name']}",
                $msgText,
            ]
        );

        Response::success([
            'label_id'   => $labelId,
            'label_name' => $label['label_name'],
        ], 'Label request submitted. Admin will review and assign.');
    }
}
