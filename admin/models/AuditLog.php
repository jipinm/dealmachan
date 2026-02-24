<?php
require_once CORE_PATH . '/Model.php';

class AuditLog extends Model {
    protected $table = 'audit_logs';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 50));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT al.*,
                       COALESCE(adm.name, m.business_name, c.name) AS actor_name
                FROM {$this->table} al
                LEFT JOIN admins    adm ON al.user_type = 'admin'    AND al.user_id = adm.id
                LEFT JOIN merchants m   ON al.user_type = 'merchant' AND al.user_id = m.user_id
                LEFT JOIN customers c   ON al.user_type = 'customer' AND al.user_id = c.user_id
                {$where}
                ORDER BY al.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} al
                LEFT JOIN admins    adm ON al.user_type = 'admin'    AND al.user_id = adm.id
                LEFT JOIN merchants m   ON al.user_type = 'merchant' AND al.user_id = m.user_id
                LEFT JOIN customers c   ON al.user_type = 'customer' AND al.user_id = c.user_id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['user_type'])) {
            $conds[]  = "al.user_type = ?";
            $params[] = $filters['user_type'];
        }
        if (!empty($filters['user_id'])) {
            $conds[]  = "al.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $conds[]  = "al.action = ?";
            $params[] = $filters['action'];
        }
        if (!empty($filters['table_name'])) {
            $conds[]  = "al.table_name = ?";
            $params[] = $filters['table_name'];
        }
        if (!empty($filters['record_id'])) {
            $conds[]  = "al.record_id = ?";
            $params[] = (int)$filters['record_id'];
        }
        if (!empty($filters['date_from'])) {
            $conds[]  = "DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conds[]  = "DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['ip_address'])) {
            $conds[]  = "al.ip_address = ?";
            $params[] = $filters['ip_address'];
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── DETAIL ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT al.*,
                       COALESCE(adm.name, m.business_name, c.name) AS actor_name,
                       adm.admin_type
                FROM {$this->table} al
                LEFT JOIN admins    adm ON al.user_type = 'admin'    AND al.user_id = adm.id
                LEFT JOIN merchants m   ON al.user_type = 'merchant' AND al.user_id = m.user_id
                LEFT JOIN customers c   ON al.user_type = 'customer' AND al.user_id = c.user_id
                WHERE al.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ─── DISTINCT LOOKUPS ────────────────────────────────────────────────────

    public function getDistinctActions() {
        return $this->db->query("SELECT DISTINCT action FROM {$this->table} ORDER BY action")->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getDistinctTables() {
        return $this->db->query("SELECT DISTINCT table_name FROM {$this->table} WHERE table_name IS NOT NULL ORDER BY table_name")->fetchAll(\PDO::FETCH_COLUMN);
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                                AS total,
                    SUM(CASE WHEN al.user_type = 'admin'    THEN 1 ELSE 0 END)             AS admin_actions,
                    SUM(CASE WHEN al.user_type = 'merchant' THEN 1 ELSE 0 END)             AS merchant_actions,
                    SUM(CASE WHEN al.user_type = 'customer' THEN 1 ELSE 0 END)             AS customer_actions,
                    SUM(CASE WHEN DATE(al.created_at) = CURDATE()             THEN 1 ELSE 0 END) AS today
                FROM {$this->table} al";
        return $this->db->query($sql)->fetch();
    }

    // ─── EXPORT ──────────────────────────────────────────────────────────────

    public function getForExport($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT al.id, al.created_at, al.user_type, al.user_id,
                       COALESCE(adm.name, m.business_name, c.name) AS actor_name,
                       al.action, al.table_name, al.record_id, al.ip_address
                FROM {$this->table} al
                LEFT JOIN admins    adm ON al.user_type = 'admin'    AND al.user_id = adm.id
                LEFT JOIN merchants m   ON al.user_type = 'merchant' AND al.user_id = m.user_id
                LEFT JOIN customers c   ON al.user_type = 'customer' AND al.user_id = c.user_id
                {$where}
                ORDER BY al.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
