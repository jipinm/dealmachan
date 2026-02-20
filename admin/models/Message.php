<?php

class Message {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ─── INBOX / SENT ─────────────────────────────────────────────────────────

    public function getInbox(int $adminId, int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT m.*,
                CASE m.sender_type
                    WHEN 'admin'    THEN (SELECT name FROM admins WHERE id = m.sender_id)
                    WHEN 'merchant' THEN (SELECT business_name FROM merchants WHERE id = m.sender_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = m.sender_id)
                END AS sender_name,
                (SELECT COUNT(*) FROM messages r WHERE r.parent_message_id = m.id) AS reply_count
             FROM messages m
             WHERE m.receiver_id = ? AND m.receiver_type = 'admin'
               AND m.parent_message_id IS NULL
             ORDER BY m.sent_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$adminId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countInbox(int $adminId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM messages
             WHERE receiver_id = ? AND receiver_type = 'admin' AND parent_message_id IS NULL"
        );
        $stmt->execute([$adminId]);
        return (int)$stmt->fetchColumn();
    }

    public function countUnread(int $adminId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM messages
             WHERE receiver_id = ? AND receiver_type = 'admin' AND read_status = 0"
        );
        $stmt->execute([$adminId]);
        return (int)$stmt->fetchColumn();
    }

    public function getSent(int $adminId, int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT m.*,
                CASE m.receiver_type
                    WHEN 'admin'    THEN (SELECT name FROM admins WHERE id = m.receiver_id)
                    WHEN 'merchant' THEN (SELECT business_name FROM merchants WHERE id = m.receiver_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = m.receiver_id)
                END AS receiver_name
             FROM messages m
             WHERE m.sender_id = ? AND m.sender_type = 'admin'
               AND m.parent_message_id IS NULL
             ORDER BY m.sent_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$adminId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSent(int $adminId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM messages
             WHERE sender_id = ? AND sender_type = 'admin' AND parent_message_id IS NULL"
        );
        $stmt->execute([$adminId]);
        return (int)$stmt->fetchColumn();
    }

    // ─── SINGLE MESSAGE / THREAD ─────────────────────────────────────────────

    public function find(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT m.*,
                CASE m.sender_type
                    WHEN 'admin'    THEN (SELECT name FROM admins WHERE id = m.sender_id)
                    WHEN 'merchant' THEN (SELECT business_name FROM merchants WHERE id = m.sender_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = m.sender_id)
                END AS sender_name,
                CASE m.receiver_type
                    WHEN 'admin'    THEN (SELECT name FROM admins WHERE id = m.receiver_id)
                    WHEN 'merchant' THEN (SELECT business_name FROM merchants WHERE id = m.receiver_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = m.receiver_id)
                END AS receiver_name
             FROM messages m WHERE m.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getThread(int $rootId): array {
        // Return root message + all replies ordered by sent_at
        $stmt = $this->db->prepare(
            "SELECT m.*,
                CASE m.sender_type
                    WHEN 'admin'    THEN (SELECT name FROM admins WHERE id = m.sender_id)
                    WHEN 'merchant' THEN (SELECT business_name FROM merchants WHERE id = m.sender_id)
                    WHEN 'customer' THEN (SELECT name FROM customers WHERE id = m.sender_id)
                END AS sender_name
             FROM messages m
             WHERE m.id = ? OR m.parent_message_id = ?
             ORDER BY m.sent_at ASC"
        );
        $stmt->execute([$rootId, $rootId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── SEND / REPLY ─────────────────────────────────────────────────────────

    public function send(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO messages
             (sender_id, sender_type, receiver_id, receiver_type, subject, message_text, parent_message_id)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['sender_id'],
            $data['sender_type'],
            $data['receiver_id'],
            $data['receiver_type'],
            $data['subject'] ?? null,
            $data['message_text'],
            $data['parent_message_id'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ─── READ / DELETE ────────────────────────────────────────────────────────

    public function markRead(int $id): void {
        $this->db->prepare(
            "UPDATE messages SET read_status = 1, read_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public function markThreadRead(int $rootId, int $adminId): void {
        $this->db->prepare(
            "UPDATE messages SET read_status = 1, read_at = NOW()
             WHERE (id = ? OR parent_message_id = ?)
               AND receiver_id = ? AND receiver_type = 'admin'"
        )->execute([$rootId, $rootId, $adminId]);
    }

    public function delete(int $id): void {
        // Delete message and its replies
        $this->db->prepare("DELETE FROM messages WHERE id = ? OR parent_message_id = ?")->execute([$id, $id]);
    }

    // ─── NOTIFICATIONS ────────────────────────────────────────────────────────

    public function getNotifications(int $userId, string $userType = 'admin', int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM notifications
             WHERE user_id = ? AND user_type = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$userId, $userType, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countNotifications(int $userId, string $userType = 'admin'): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = ?"
        );
        $stmt->execute([$userId, $userType]);
        return (int)$stmt->fetchColumn();
    }

    public function countUnreadNotifications(int $userId, string $userType = 'admin'): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = ? AND read_status = 0"
        );
        $stmt->execute([$userId, $userType]);
        return (int)$stmt->fetchColumn();
    }

    public function markNotificationRead(int $id): void {
        $this->db->prepare(
            "UPDATE notifications SET read_status = 1, read_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public function markAllNotificationsRead(int $userId, string $userType = 'admin'): void {
        $this->db->prepare(
            "UPDATE notifications SET read_status = 1, read_at = NOW()
             WHERE user_id = ? AND user_type = ? AND read_status = 0"
        )->execute([$userId, $userType]);
    }

    public function createNotification(array $data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, user_type, notification_type, title, message, action_url)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['user_id'],
            $data['user_type'],
            $data['notification_type'] ?? 'info',
            $data['title'],
            $data['message'],
            $data['action_url'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // Broadcast a notification to all admins (or a subset)
    public function broadcastToAdmins(array $data, array $adminIds = []): int {
        if (empty($adminIds)) {
            $adminIds = $this->db->query("SELECT id FROM admins")->fetchAll(PDO::FETCH_COLUMN);
        }
        $count = 0;
        foreach ($adminIds as $aid) {
            $this->createNotification(array_merge($data, ['user_id' => $aid, 'user_type' => 'admin']));
            $count++;
        }
        return $count;
    }

    // ─── DROPDOWNS ────────────────────────────────────────────────────────────

    public function getAdminList(): array {
        return $this->db->query(
            "SELECT id, name, admin_type FROM admins ORDER BY name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMerchantList(): array {
        return $this->db->query(
            "SELECT id, business_name FROM merchants WHERE profile_status = 'approved' ORDER BY business_name"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCustomerList(): array {
        return $this->db->query(
            "SELECT c.id, c.name, u.phone FROM customers c JOIN users u ON c.user_id = u.id
             WHERE u.status = 'active' ORDER BY c.name LIMIT 200"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStats(int $adminId): array {
        $inbox   = $this->countInbox($adminId);
        $unread  = $this->countUnread($adminId);
        $sent    = $this->countSent($adminId);
        $notifTotal  = $this->countNotifications($adminId);
        $notifUnread = $this->countUnreadNotifications($adminId);
        return compact('inbox', 'unread', 'sent', 'notifTotal', 'notifUnread');
    }
}
