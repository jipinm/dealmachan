<?php
class StoreCoupon extends Model {
    protected $table = 'store_coupons';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All store coupons with joined merchant / store / customer names.
     * Filters: merchant_id, status, is_gifted, is_redeemed, search (coupon_code), expiry
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT sc.*,
                       m.business_name AS merchant_name,
                       s.store_name,
                       c.name AS gifted_to_name
                FROM {$this->table} sc
                JOIN merchants m ON sc.merchant_id = m.id
                JOIN stores s ON sc.store_id = s.id
                LEFT JOIN customers c ON sc.gifted_to_customer_id = c.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY sc.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count matching store coupons. */
    public function countWithDetails($filters = []) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} sc
                   JOIN merchants m ON sc.merchant_id = m.id
                   JOIN stores s ON sc.store_id = s.id
                   LEFT JOIN customers c ON sc.gifted_to_customer_id = c.id
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
            $sql .= " AND sc.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND sc.status = ?";
            $params[] = $filters['status'];
        }
        if (isset($filters['is_gifted']) && $filters['is_gifted'] !== '') {
            $sql .= " AND sc.is_gifted = ?";
            $params[] = (int)$filters['is_gifted'];
        }
        if (isset($filters['is_redeemed']) && $filters['is_redeemed'] !== '') {
            $sql .= " AND sc.is_redeemed = ?";
            $params[] = (int)$filters['is_redeemed'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (sc.coupon_code LIKE ? OR m.business_name LIKE ? OR s.store_name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['expiry'])) {
            if ($filters['expiry'] === 'active') {
                $sql .= " AND (sc.valid_until IS NULL OR sc.valid_until >= NOW())";
                $sql .= " AND (sc.valid_from IS NULL OR sc.valid_from <= NOW())";
            } elseif ($filters['expiry'] === 'expired') {
                $sql .= " AND sc.valid_until < NOW()";
            } elseif ($filters['expiry'] === 'upcoming') {
                $sql .= " AND sc.valid_from > NOW()";
            }
        }
    }

    /** Single store coupon with full joined data. */
    public function findWithDetails($id) {
        $sql = "SELECT sc.*,
                       m.business_name AS merchant_name,
                       s.store_name,
                       c.name AS gifted_to_name
                FROM {$this->table} sc
                JOIN merchants m ON sc.merchant_id = m.id
                JOIN stores s ON sc.store_id = s.id
                LEFT JOIN customers c ON sc.gifted_to_customer_id = c.id
                WHERE sc.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Aggregate stats for listing header. */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                              AS total,
                    SUM(CASE WHEN sc.status='active'              THEN 1 ELSE 0 END)    AS active,
                    SUM(CASE WHEN sc.status='inactive'            THEN 1 ELSE 0 END)    AS inactive,
                    SUM(CASE WHEN sc.status='expired'             THEN 1 ELSE 0 END)    AS expired,
                    SUM(CASE WHEN sc.is_gifted=1                  THEN 1 ELSE 0 END)    AS gifted,
                    SUM(CASE WHEN sc.is_redeemed=1                THEN 1 ELSE 0 END)    AS redeemed,
                    SUM(CASE WHEN DATE(sc.created_at) = CURDATE() THEN 1 ELSE 0 END)    AS today
                FROM {$this->table} sc";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /** Get store coupons belonging to a specific merchant. */
    public function getByMerchant($merchantId) {
        $sql = "SELECT sc.*, s.store_name, c.name AS gifted_to_name
                FROM {$this->table} sc
                JOIN stores s ON sc.store_id = s.id
                LEFT JOIN customers c ON sc.gifted_to_customer_id = c.id
                WHERE sc.merchant_id = ?
                ORDER BY sc.created_at DESC";
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
    // ─── REVOKE GIFT ──────────────────────────────────────────────────────

    /** Revoke gift assignment (only if not yet redeemed) */
    public function revokeGift($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET is_gifted = 0, gifted_to_customer_id = NULL, gifted_at = NULL, updated_at = NOW()
             WHERE id = ? AND is_gifted = 1 AND is_redeemed = 0"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }
    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function deleteStoreCoupon($id) {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }
}
