<?php
/**
 * Customer Notification Controller
 *
 * GET  /api/customers/notifications
 * GET  /api/customers/notifications/unread-count
 * PUT  /api/customers/notifications/read-all
 * PUT  /api/customers/notifications/:id/read
 * DELETE /api/customers/notifications/:id
 */
class CustomerNotificationController {

    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // ── GET /api/customers/notifications ─────────────────────────────────────
    public function index(array $user, array $query): never {
        $page    = max(1, (int)($query['page'] ?? 1));
        $perPage = min(50, max(10, (int)($query['per_page'] ?? 20)));
        $offset  = ($page - 1) * $perPage;

        $typeFilter = isset($query['type']) && $query['type'] !== '' ? $query['type'] : null;

        $whereParams = [$user['id'], 'customer'];
        $typeClause  = '';
        if ($typeFilter) {
            $typeClause    = ' AND notification_type = ?';
            $whereParams[] = $typeFilter;
        }

        $notifications = $this->db->query(
            "SELECT id, notification_type AS type, title, message,
                    read_status AS is_read, action_url, created_at
             FROM notifications
             WHERE user_id = ? AND user_type = ?" . $typeClause . "
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            array_merge($whereParams, [$perPage, $offset])
        );

        $total = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM notifications
             WHERE user_id = ? AND user_type = ?" . $typeClause,
            $whereParams
        )['cnt'] ?? 0;

        Response::success([
            'data'       => $notifications,
            'pagination' => [
                'total'    => (int)$total,
                'page'     => $page,
                'per_page' => $perPage,
                'pages'    => (int)ceil($total / $perPage),
            ],
        ]);
    }

    // ── GET /api/customers/notifications/unread-count ────────────────────────
    public function unreadCount(array $user): never {
        $count = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND user_type = 'customer' AND read_status = 0",
            [$user['id']]
        )['cnt'] ?? 0;

        Response::success(['count' => (int)$count]);
    }

    // ── PUT /api/customers/notifications/read-all ─────────────────────────────
    public function markAllRead(array $user): never {
        $this->db->execute(
            "UPDATE notifications SET read_status = 1, read_at = NOW() WHERE user_id = ? AND user_type = 'customer' AND read_status = 0",
            [$user['id']]
        );

        Response::success(null, 'All notifications marked as read');
    }

    // ── PUT /api/customers/notifications/:id/read ─────────────────────────────
    public function markRead(array $user, int $id): never {
        $this->db->execute(
            "UPDATE notifications SET read_status = 1, read_at = NOW() WHERE id = ? AND user_id = ? AND user_type = 'customer'",
            [$id, $user['id']]
        );

        Response::success(null, 'Notification marked as read');
    }

    // ── DELETE /api/customers/notifications/:id ───────────────────────────────
    public function delete(array $user, int $id): never {
        $this->db->execute(
            "DELETE FROM notifications WHERE id = ? AND user_id = ? AND user_type = 'customer'",
            [$id, $user['id']]
        );

        Response::success(null, 'Notification deleted');
    }
}
