<?php
class DealMaker extends Model {
    protected $table = 'customers';

    // ─── DEALMAKERS LIST ──────────────────────────────────────────────────────

    public function getAllDealmakers($filters = []) {
        $sql = "SELECT c.id, c.name, c.gender, c.customer_type, c.dealmaker_approved_at,
                       c.created_at, c.is_dealmaker,
                       u.phone, u.email, u.status AS user_status,
                       (SELECT COUNT(*) FROM dealmaker_tasks dt WHERE dt.dealmaker_customer_id = c.id) AS total_tasks,
                       (SELECT COUNT(*) FROM dealmaker_tasks dt WHERE dt.dealmaker_customer_id = c.id AND dt.status = 'completed') AS completed_tasks,
                       (SELECT COALESCE(SUM(dt.reward_amount),0) FROM dealmaker_tasks dt WHERE dt.dealmaker_customer_id = c.id AND dt.reward_status = 'paid') AS total_rewards_paid
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.is_dealmaker = 1";
        $params = [];
        $this->applyDealmakerFilters($sql, $params, $filters);
        $sql .= " ORDER BY c.dealmaker_approved_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countDealmakers($filters = []) {
        $sql = "SELECT COUNT(*) FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.is_dealmaker = 1";
        $params = [];
        $this->applyDealmakerFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function applyDealmakerFilters(&$sql, &$params, $filters) {
        if (!empty($filters['user_status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['user_status'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
    }

    // ─── PENDING REQUESTS ─────────────────────────────────────────────────────

    public function getPendingRequests($filters = []) {
        $sql = "SELECT c.id, c.name, c.gender, c.customer_type, c.created_at,
                       u.phone, u.email, u.status AS user_status
                FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.customer_type = 'dealmaker' AND c.is_dealmaker = 0";
        $params = [];
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        $sql .= " ORDER BY c.created_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countPendingRequests($filters = []) {
        $sql = "SELECT COUNT(*) FROM customers c
                JOIN users u ON c.user_id = u.id
                WHERE c.customer_type = 'dealmaker' AND c.is_dealmaker = 0";
        $params = [];
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ? OR u.email LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ─── SINGLE DEALMAKER ─────────────────────────────────────────────────────

    public function findDealmakerById($id) {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.phone, u.email, u.status AS user_status
             FROM customers c
             JOIN users u ON c.user_id = u.id
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ─── APPROVE / REVOKE ─────────────────────────────────────────────────────

    public function approve($customerId, $adminId) {
        $stmt = $this->db->prepare(
            "UPDATE customers SET is_dealmaker = 1, customer_type = 'dealmaker',
             dealmaker_approved_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$customerId]);
    }

    public function revoke($customerId) {
        $stmt = $this->db->prepare(
            "UPDATE customers SET is_dealmaker = 0, customer_type = 'standard',
             dealmaker_approved_at = NULL
             WHERE id = ?"
        );
        return $stmt->execute([$customerId]);
    }

    // ─── STATS ────────────────────────────────────────────────────────────────

    public function getStats() {
        $row = $this->db->query(
            "SELECT
               (SELECT COUNT(*) FROM customers WHERE is_dealmaker = 1)  AS total_dealmakers,
               (SELECT COUNT(*) FROM customers WHERE customer_type = 'dealmaker' AND is_dealmaker = 0) AS pending_requests,
               (SELECT COUNT(*) FROM customers WHERE is_dealmaker = 1 AND MONTH(dealmaker_approved_at) = MONTH(NOW()) AND YEAR(dealmaker_approved_at) = YEAR(NOW())) AS approved_this_month,
               (SELECT COUNT(*) FROM dealmaker_tasks) AS total_tasks,
               (SELECT COUNT(*) FROM dealmaker_tasks WHERE status = 'assigned') AS tasks_assigned,
               (SELECT COUNT(*) FROM dealmaker_tasks WHERE status = 'in_progress') AS tasks_in_progress,
               (SELECT COUNT(*) FROM dealmaker_tasks WHERE status = 'completed') AS tasks_completed,
               (SELECT COUNT(*) FROM dealmaker_tasks WHERE status = 'verified') AS tasks_verified,
               (SELECT COUNT(*) FROM dealmaker_tasks WHERE reward_status = 'pending' AND status = 'verified') AS rewards_pending,
               (SELECT COALESCE(SUM(reward_amount),0) FROM dealmaker_tasks WHERE reward_status = 'paid') AS total_rewards_paid"
        )->fetch();
        return $row;
    }

    // ─── TASKS ────────────────────────────────────────────────────────────────

    public function getAllTasks($filters = []) {
        $sql = "SELECT dt.*,
                       c.name   AS dealmaker_name,
                       u.phone  AS dealmaker_phone,
                       a.name   AS assigned_by_name
                FROM dealmaker_tasks dt
                JOIN customers c ON dt.dealmaker_customer_id = c.id
                JOIN users     u ON c.user_id = u.id
                LEFT JOIN admins a ON dt.assigned_by_admin_id = a.id
                WHERE 1=1";
        $params = [];
        $this->applyTaskFilters($sql, $params, $filters);
        $sql .= " ORDER BY dt.assigned_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countTasks($filters = []) {
        $sql = "SELECT COUNT(*) FROM dealmaker_tasks dt
                JOIN customers c ON dt.dealmaker_customer_id = c.id
                JOIN users     u ON c.user_id = u.id
                WHERE 1=1";
        $params = [];
        $this->applyTaskFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function applyTaskFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND dt.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['task_type'])) {
            $sql .= " AND dt.task_type = ?";
            $params[] = $filters['task_type'];
        }
        if (!empty($filters['reward_status'])) {
            $sql .= " AND dt.reward_status = ?";
            $params[] = $filters['reward_status'];
        }
        if (!empty($filters['dealmaker_id'])) {
            $sql .= " AND dt.dealmaker_customer_id = ?";
            $params[] = (int)$filters['dealmaker_id'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR dt.task_description LIKE ?)";
            $params[] = $like; $params[] = $like;
        }
    }

    public function createTask($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO dealmaker_tasks
             (dealmaker_customer_id, task_type, task_description, assigned_by_admin_id, reward_amount)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['dealmaker_customer_id'],
            $data['task_type'],
            $data['task_description'],
            $data['assigned_by_admin_id'],
            $data['reward_amount'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function updateTaskStatus($taskId, $status, $notes = '') {
        $completedAt = in_array($status, ['completed', 'verified']) ? 'NOW()' : 'NULL';
        $stmt = $this->db->prepare(
            "UPDATE dealmaker_tasks
             SET status = ?, completion_notes = ?,
                 completed_at = IF(status NOT IN ('completed','verified') AND ? IN ('completed','verified'), NOW(), completed_at)
             WHERE id = ?"
        );
        return $stmt->execute([$status, $notes ?: null, $status, $taskId]);
    }

    public function markRewardPaid($taskId) {
        $stmt = $this->db->prepare(
            "UPDATE dealmaker_tasks SET reward_status = 'paid' WHERE id = ?"
        );
        return $stmt->execute([$taskId]);
    }

    public function findTaskById($id) {
        $stmt = $this->db->prepare(
            "SELECT dt.*, c.name AS dealmaker_name, u.phone AS dealmaker_phone,
                    a.name AS assigned_by_name
             FROM dealmaker_tasks dt
             JOIN customers c ON dt.dealmaker_customer_id = c.id
             JOIN users     u ON c.user_id = u.id
             LEFT JOIN admins a ON dt.assigned_by_admin_id = a.id
             WHERE dt.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getDealmakersList() {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.name, u.phone
             FROM customers c JOIN users u ON c.user_id = u.id
             WHERE c.is_dealmaker = 1 ORDER BY c.name"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
