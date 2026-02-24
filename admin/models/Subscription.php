<?php
require_once CORE_PATH . '/Model.php';

class Subscription extends Model {
    protected $table = 'subscriptions';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 25));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT s.*,
                       u.email         AS user_email,
                       u.phone         AS user_phone,
                       u.status        AS user_status,
                       COALESCE(m.business_name, c.name) AS display_name,
                       m.id            AS merchant_id,
                       c.id            AS customer_id
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN merchants m ON s.user_type = 'merchant' AND m.user_id = s.user_id
                LEFT JOIN customers c ON s.user_type = 'customer' AND c.user_id = s.user_id
                {$where}
                ORDER BY s.expiry_date ASC, s.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN merchants m ON s.user_type = 'merchant' AND m.user_id = s.user_id
                LEFT JOIN customers c ON s.user_type = 'customer' AND c.user_id = s.user_id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['user_type'])) {
            $conds[]  = "s.user_type = ?";
            $params[] = $filters['user_type'];
        }
        if (!empty($filters['status'])) {
            $conds[]  = "s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['plan_type'])) {
            $conds[]  = "s.plan_type = ?";
            $params[] = $filters['plan_type'];
        }
        if (!empty($filters['expiry_before'])) {
            $conds[]  = "s.expiry_date <= ?";
            $params[] = $filters['expiry_before'];
        }
        if (!empty($filters['expiry_after'])) {
            $conds[]  = "s.expiry_date >= ?";
            $params[] = $filters['expiry_after'];
        }
        if (!empty($filters['search'])) {
            $conds[]  = "(u.email LIKE ? OR u.phone LIKE ? OR m.business_name LIKE ? OR c.name LIKE ?)";
            $like     = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like, $like);
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── DETAIL ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT s.*,
                       u.email         AS user_email,
                       u.phone         AS user_phone,
                       u.status        AS user_status,
                       COALESCE(m.business_name, c.name) AS display_name,
                       m.id            AS merchant_id,
                       c.id            AS customer_id
                FROM {$this->table} s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN merchants m ON s.user_type = 'merchant' AND m.user_id = s.user_id
                LEFT JOIN customers c ON s.user_type = 'customer' AND c.user_id = s.user_id
                WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                               AS total,
                    SUM(CASE WHEN s.status = 'active'                    THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN s.status = 'expired'                   THEN 1 ELSE 0 END) AS expired,
                    SUM(CASE WHEN s.status = 'cancelled'                 THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN s.user_type = 'merchant'               THEN 1 ELSE 0 END) AS merchants,
                    SUM(CASE WHEN s.user_type = 'customer'               THEN 1 ELSE 0 END) AS customers,
                    SUM(CASE WHEN s.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                              AND s.status = 'active'                    THEN 1 ELSE 0 END) AS expiring_soon,
                    COALESCE(SUM(s.payment_amount), 0)                                     AS total_revenue
                FROM {$this->table} s";
        return $this->db->query($sql)->fetch();
    }

    // ─── WRITE ───────────────────────────────────────────────────────────────

    public function createSubscription($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols   = implode(', ', array_keys($data));
        $places = ':' . implode(', :', array_keys($data));
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
        return $stmt->execute($data) ? $this->db->lastInsertId() : false;
    }

    public function updateSubscription($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");
        return $stmt->execute($vals);
    }

    /** Fetch all subscriptions for a given user_id (merchant or customer history). */
    public function getByUser($userId, $userType) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$userId, $userType]);
        return $stmt->fetchAll();
    }
}
