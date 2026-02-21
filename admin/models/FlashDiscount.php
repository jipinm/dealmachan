<?php
class FlashDiscount extends Model {
    protected $table = 'flash_discounts';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All flash discounts with joined merchant / store names.
     * Filters: merchant_id, status, search (title), expiry (active/expired/upcoming)
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT fd.*,
                       m.business_name AS merchant_name,
                       s.store_name
                FROM {$this->table} fd
                JOIN merchants m ON fd.merchant_id = m.id
                LEFT JOIN stores s ON fd.store_id = s.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY fd.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count matching flash discounts (same filters, no LIMIT). */
    public function countWithDetails($filters = []) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} fd
                   JOIN merchants m ON fd.merchant_id = m.id
                   LEFT JOIN stores s ON fd.store_id = s.id
                   WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Shared WHERE builder. */
    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['merchant_id'])) {
            $sql .= " AND fd.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND fd.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (fd.title LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['expiry'])) {
            if ($filters['expiry'] === 'active') {
                $sql .= " AND (fd.valid_until IS NULL OR fd.valid_until >= NOW())";
                $sql .= " AND (fd.valid_from IS NULL OR fd.valid_from <= NOW())";
            } elseif ($filters['expiry'] === 'expired') {
                $sql .= " AND fd.valid_until < NOW()";
            } elseif ($filters['expiry'] === 'upcoming') {
                $sql .= " AND fd.valid_from > NOW()";
            }
        }
    }

    /** Single flash discount with full joined data. */
    public function findWithDetails($id) {
        $sql = "SELECT fd.*,
                       m.business_name AS merchant_name,
                       s.store_name
                FROM {$this->table} fd
                JOIN merchants m ON fd.merchant_id = m.id
                LEFT JOIN stores s ON fd.store_id = s.id
                WHERE fd.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Aggregate stats for listing header. */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                              AS total,
                    SUM(CASE WHEN fd.status='active'               THEN 1 ELSE 0 END)   AS active,
                    SUM(CASE WHEN fd.status='inactive'             THEN 1 ELSE 0 END)   AS inactive,
                    SUM(CASE WHEN fd.status='expired'              THEN 1 ELSE 0 END)   AS expired,
                    COALESCE(SUM(fd.current_redemptions), 0)                             AS total_redemptions,
                    SUM(CASE WHEN DATE(fd.created_at) = CURDATE()  THEN 1 ELSE 0 END)   AS today
                FROM {$this->table} fd";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /** Get flash discounts belonging to a specific merchant. */
    public function getByMerchant($merchantId) {
        $sql = "SELECT fd.*, s.store_name
                FROM {$this->table} fd
                LEFT JOIN stores s ON fd.store_id = s.id
                WHERE fd.merchant_id = ?
                ORDER BY fd.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$merchantId]);
        return $stmt->fetchAll();
    }

    // ─── TOGGLE ───────────────────────────────────────────────────────────────

    public function toggleStatus($id) {
        $row = $this->find($id);
        if (!$row) return false;
        $newStatus = $row['status'] === 'active' ? 'inactive' : 'active';
        return $this->db->prepare("UPDATE {$this->table} SET status=?, updated_at=NOW() WHERE id=?")
                        ->execute([$newStatus, $id]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function deleteFlashDiscount($id) {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }
}
