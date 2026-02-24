<?php
require_once CORE_PATH . '/Model.php';

class Advertisement extends Model {
    protected $table = 'advertisements';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 25));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT a.*, adm.name AS created_by_name
                FROM {$this->table} a
                JOIN admins adm ON a.created_by_admin_id = adm.id
                {$where}
                ORDER BY a.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} a
                JOIN admins adm ON a.created_by_admin_id = adm.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conds[]  = "a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['media_type'])) {
            $conds[]  = "a.media_type = ?";
            $params[] = $filters['media_type'];
        }
        if (isset($filters['active_now']) && $filters['active_now']) {
            $conds[] = "(a.start_date IS NULL OR a.start_date <= NOW()) AND (a.end_date IS NULL OR a.end_date >= NOW())";
        }
        if (!empty($filters['search'])) {
            $conds[]  = "a.title LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── DETAIL ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT a.*, adm.name AS created_by_name
                FROM {$this->table} a
                JOIN admins adm ON a.created_by_admin_id = adm.id
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                              AS total,
                    SUM(CASE WHEN a.status = 'active'                       THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN a.status = 'inactive'                     THEN 1 ELSE 0 END) AS inactive,
                    SUM(CASE WHEN a.media_type = 'image'                    THEN 1 ELSE 0 END) AS images,
                    SUM(CASE WHEN a.media_type = 'video'                    THEN 1 ELSE 0 END) AS videos,
                    SUM(CASE WHEN a.status = 'active'
                              AND (a.start_date IS NULL OR a.start_date <= NOW())
                              AND (a.end_date   IS NULL OR a.end_date   >= NOW()) THEN 1 ELSE 0 END) AS live_now
                FROM {$this->table} a";
        return $this->db->query($sql)->fetch();
    }

    // ─── WRITE ───────────────────────────────────────────────────────────────

    public function createAdvertisement($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols   = implode(', ', array_keys($data));
        $places = ':' . implode(', :', array_keys($data));
        $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
        return $stmt->execute($data) ? $this->db->lastInsertId() : false;
    }

    public function updateAdvertisement($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");
        return $stmt->execute($vals);
    }

    public function toggleStatus($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END,
                 updated_at = NOW()
             WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function deleteAdvertisement($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
