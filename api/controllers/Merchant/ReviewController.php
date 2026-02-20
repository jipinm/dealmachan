<?php
/**
 * Merchant Review Controller
 *
 * GET  /merchants/reviews         → index (list + rating summary)
 * GET  /merchants/reviews/:id     → show  (single review + reply)
 * POST /merchants/reviews/:id/reply → reply (stores reply in messages table)
 *
 * Note: Replies are stored in the `messages` table with
 *   subject = "review_reply:{review_id}"
 *   sender_type = 'merchant', receiver_type = 'customer'
 */
class ReviewController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/reviews ─────────────────────────────────────────────────
    public function index(array $user, array $params = []): never {
        $merchantId = (int)$user['merchant_id'];

        $where  = 'r.merchant_id = ?';
        $binds  = [$merchantId];

        if (!empty($params['rating'])) {
            $where  .= ' AND r.rating = ?';
            $binds[] = (int)$params['rating'];
        }
        if (!empty($params['status'])) {
            $where  .= ' AND r.status = ?';
            $binds[] = $params['status'];
        }

        $rows = $this->db->query(
            "SELECT r.id, r.rating, r.review_text, r.status, r.created_at,
                    CONCAT(LEFT(c.name, 1), REPEAT('*', GREATEST(CHAR_LENGTH(c.name) - 2, 0)), RIGHT(c.name, 1)) AS customer_name,
                    s.store_name,
                    -- Check if a reply exists
                    (SELECT message_text FROM messages
                     WHERE sender_type = 'merchant'
                       AND sender_id = r.merchant_id
                       AND subject = CONCAT('review_reply:', r.id)
                     LIMIT 1) AS merchant_reply,
                    (SELECT sent_at FROM messages
                     WHERE sender_type = 'merchant'
                       AND sender_id = r.merchant_id
                       AND subject = CONCAT('review_reply:', r.id)
                     LIMIT 1) AS merchant_reply_at
             FROM reviews r
             LEFT JOIN customers c ON c.id = r.customer_id
             LEFT JOIN stores    s ON s.id = r.store_id
             WHERE {$where}
             ORDER BY r.created_at DESC",
            $binds
        );

        // Rating summary
        $summary = $this->db->queryOne(
            "SELECT
               COUNT(*) AS total,
               ROUND(AVG(rating), 1) AS avg_rating,
               SUM(rating = 5) AS five,
               SUM(rating = 4) AS four,
               SUM(rating = 3) AS three,
               SUM(rating = 2) AS two,
               SUM(rating = 1) AS one
             FROM reviews WHERE merchant_id = ? AND status = 'approved'",
            [$merchantId]
        );

        Response::success($rows, 'OK', ['summary' => $summary]);
    }

    // ── GET /merchants/reviews/:id ─────────────────────────────────────────────
    public function show(array $user, int $id): never {
        $merchantId = (int)$user['merchant_id'];

        $review = $this->db->queryOne(
            "SELECT r.*,
                    c.name AS customer_name_raw,
                    s.store_name,
                    (SELECT message_text FROM messages
                     WHERE sender_type = 'merchant'
                       AND sender_id = r.merchant_id
                       AND subject = CONCAT('review_reply:', r.id)
                     LIMIT 1) AS merchant_reply,
                    (SELECT sent_at FROM messages
                     WHERE sender_type = 'merchant'
                       AND sender_id = r.merchant_id
                       AND subject = CONCAT('review_reply:', r.id)
                     LIMIT 1) AS merchant_reply_at
             FROM reviews r
             LEFT JOIN customers c ON c.id = r.customer_id
             LEFT JOIN stores    s ON s.id = r.store_id
             WHERE r.id = ? AND r.merchant_id = ?",
            [$id, $merchantId]
        );

        if (!$review) Response::notFound('Review not found');

        // Mask customer name
        $name = $review['customer_name_raw'] ?? '';
        $review['customer_name'] = strlen($name) > 2
            ? substr($name, 0, 1) . str_repeat('*', max(strlen($name) - 2, 0)) . substr($name, -1)
            : $name;
        unset($review['customer_name_raw']);

        Response::success($review);
    }

    // ── POST /merchants/reviews/:id/reply ─────────────────────────────────────
    public function reply(array $user, int $id, array $body): never {
        $merchantId = (int)$user['merchant_id'];

        $review = $this->db->queryOne(
            'SELECT id, customer_id, merchant_id FROM reviews WHERE id = ? AND merchant_id = ?',
            [$id, $merchantId]
        );
        if (!$review) Response::notFound('Review not found');

        $replyText = trim((string)($body['reply_text'] ?? $body['reply'] ?? ''));
        if ($replyText === '') Response::validationError(['reply_text' => 'Reply text is required']);

        $subjectKey = "review_reply:{$id}";

        // Check if already replied — update instead of insert
        $existing = $this->db->queryOne(
            "SELECT id FROM messages WHERE sender_type = 'merchant' AND sender_id = ? AND subject = ?",
            [$merchantId, $subjectKey]
        );

        if ($existing) {
            $this->db->execute(
                "UPDATE messages SET message_text = ?, sent_at = NOW() WHERE id = ?",
                [$replyText, $existing['id']]
            );
        } else {
            $this->db->execute(
                "INSERT INTO messages (sender_id, sender_type, receiver_id, receiver_type, subject, message_text)
                 VALUES (?, 'merchant', ?, 'customer', ?, ?)",
                [$merchantId, (int)$review['customer_id'], $subjectKey, $replyText]
            );
        }

        Response::success(['reply_text' => $replyText], 'Reply posted');
    }
}
