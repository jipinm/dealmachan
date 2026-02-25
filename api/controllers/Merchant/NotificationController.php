<?php
/**
 * Merchant Notification Controller
 *
 * GET /merchants/notifications
 * PUT /merchants/notifications/read-all
 * PUT /merchants/notifications/:id/read
 */
class NotificationController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /merchants/notifications ──────────────────────────────────────────
    public function index(): never {
        $merchant = AuthMiddleware::user();
        $userId   = $merchant['id'] ?? $merchant['sub'];

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? DEFAULT_PAGE_SIZE)));
        $offset  = ($page - 1) * $limit;
        $unread  = isset($_GET['unread']) ? (bool)$_GET['unread'] : null;

        $where   = "WHERE user_id = ? AND user_type = 'merchant'";
        $params  = [$userId];

        if ($unread === true) {
            $where  .= " AND read_status = 0";
        }

        $total = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM notifications $where",
            $params
        )['cnt'] ?? 0);

        $rows = $this->db->query(
            "SELECT id, notification_type, title, message, action_url, read_status, read_at, created_at
             FROM notifications
             $where
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );

        $unreadCount = (int)($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM notifications
             WHERE user_id = ? AND user_type = 'merchant' AND read_status = 0",
            [$userId]
        )['cnt'] ?? 0);

        Response::success($rows, 'OK', 200, [
            'page'         => $page,
            'limit'        => $limit,
            'total'        => $total,
            'unread_count' => $unreadCount,
        ]);
    }

    // ── PUT /merchants/notifications/read-all ─────────────────────────────────
    public function markAllRead(): never {
        $merchant = AuthMiddleware::user();
        $userId   = $merchant['id'] ?? $merchant['sub'];

        $this->db->execute(
            "UPDATE notifications
             SET read_status = 1, read_at = NOW()
             WHERE user_id = ? AND user_type = 'merchant' AND read_status = 0",
            [$userId]
        );

        Response::success(null, 'All notifications marked as read');
    }

    // ── PUT /merchants/notifications/:id/read ────────────────────────────────
    public function markRead(int $id): never {
        $merchant = AuthMiddleware::user();
        $userId   = $merchant['id'] ?? $merchant['sub'];

        $notification = $this->db->queryOne(
            "SELECT id FROM notifications WHERE id = ? AND user_id = ? AND user_type = 'merchant'",
            [$id, $userId]
        );

        if (!$notification) {
            Response::notFound('Notification not found');
        }

        $this->db->execute(
            "UPDATE notifications SET read_status = 1, read_at = NOW() WHERE id = ?",
            [$id]
        );

        Response::success(null, 'Notification marked as read');
    }
}
