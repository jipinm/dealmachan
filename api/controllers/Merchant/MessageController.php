<?php
/**
 * Merchant Message Controller
 *
 * GET  /merchants/messages         → index   (inbox list, unread count)
 * POST /merchants/messages         → store   (send new message to admin)
 * GET  /merchants/messages/:id     → show    (full thread by root message id)
 * PUT  /merchants/messages/:id/read → markRead (mark single message read)
 */
class MessageController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/messages ────────────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        // Top-level messages (no parent) sent to/from this merchant
        $rows = $this->db->query(
            "SELECT m.id, m.subject, m.message_text, m.read_status, m.sent_at,
                    m.sender_type, m.sender_id, m.receiver_type, m.receiver_id,
                    -- Reply count for this thread
                    (SELECT COUNT(*) FROM messages r WHERE r.parent_message_id = m.id) AS reply_count,
                    -- Has this thread any unread for merchant?
                    (SELECT MAX(r2.read_status = 0 AND r2.receiver_type = 'merchant' AND r2.receiver_id = ?)
                     FROM messages r2 WHERE r2.parent_message_id = m.id OR r2.id = m.id) AS has_unread
             FROM messages m
             WHERE m.parent_message_id IS NULL
               AND (
                 (m.sender_type = 'merchant' AND m.sender_id = ?)
                 OR (m.receiver_type = 'merchant' AND m.receiver_id = ?)
               )
               AND m.subject NOT LIKE 'review_reply:%'
             ORDER BY m.sent_at DESC",
            [$merchantId, $merchantId, $merchantId]
        );

        $unreadCount = (int)$this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM messages
             WHERE receiver_type = 'merchant' AND receiver_id = ? AND read_status = 0",
            [$merchantId]
        )['cnt'];

        Response::success($rows, 'OK', ['unread_count' => $unreadCount]);
    }

    // ── POST /merchants/messages ───────────────────────────────────────────────
    public function store(array $user, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $v = new Validator($body);
        $v->required('message_text');
        if ($v->fails()) Response::validationError($v->errors());

        // Find admin id = 1 (or first admin) as default receiver
        $admin = $this->db->queryOne('SELECT id FROM admins ORDER BY id LIMIT 1', []);
        $adminId = $admin ? (int)$admin['id'] : 1;

        $parentId = !empty($body['parent_message_id']) ? (int)$body['parent_message_id'] : null;

        $this->db->execute(
            "INSERT INTO messages
               (sender_id, sender_type, receiver_id, receiver_type, subject, message_text, parent_message_id)
             VALUES (?, 'merchant', ?, 'admin', ?, ?, ?)",
            [
                $merchantId,
                $adminId,
                trim((string)($body['subject'] ?? 'Message from merchant')),
                trim((string)$body['message_text']),
                $parentId,
            ]
        );

        $msg = $this->db->queryOne('SELECT * FROM messages WHERE id = ?', [$this->db->lastInsertId()]);
        Response::created($msg, 'Message sent');
    }

    // ── GET /merchants/messages/:id ────────────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        // Root message
        $root = $this->db->queryOne(
            "SELECT * FROM messages
             WHERE id = ?
               AND (
                 (sender_type = 'merchant' AND sender_id = ?)
                 OR (receiver_type = 'merchant' AND receiver_id = ?)
               )",
            [$id, $merchantId, $merchantId]
        );
        if (!$root) Response::notFound('Message not found');

        // Replies in thread
        $replies = $this->db->query(
            "SELECT * FROM messages WHERE parent_message_id = ? ORDER BY sent_at ASC",
            [$id]
        );

        // Mark root as read if receiver is this merchant
        if (!$root['read_status'] && $root['receiver_type'] === 'merchant' && (int)$root['receiver_id'] === $merchantId) {
            $this->db->execute(
                "UPDATE messages SET read_status = 1, read_at = NOW() WHERE id = ?",
                [$id]
            );
        }

        Response::success([
            'message' => $root,
            'replies' => $replies,
        ]);
    }

    // ── PUT /merchants/messages/:id/read ──────────────────────────────────────
    public function markRead(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $this->db->execute(
            "UPDATE messages
             SET read_status = 1, read_at = NOW()
             WHERE id = ? AND receiver_type = 'merchant' AND receiver_id = ?",
            [$id, $merchantId]
        );

        Response::success(['id' => $id, 'read_status' => 1], 'Marked as read');
    }
}
