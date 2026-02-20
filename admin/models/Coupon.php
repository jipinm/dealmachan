<?php
class Coupon extends Model {
    protected $table = 'coupons';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * Paginated list with joined merchant / store names.
     * Filters: merchant_id, status, approval_status, discount_type,
     *          is_admin_coupon, search (title/code), expiry (active/expired/upcoming)
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT c.*,
                       m.business_name AS merchant_name,
                       s.store_name,
                       (SELECT COUNT(*) FROM coupon_redemptions cr WHERE cr.coupon_id = c.id) AS redemption_count
                FROM {$this->table} c
                JOIN merchants m ON c.merchant_id = m.id
                LEFT JOIN stores s ON c.store_id = s.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY c.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count matching coupons (same filters, no LIMIT). */
    public function countWithDetails($filters = []) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} c
                   JOIN merchants m ON c.merchant_id = m.id
                   LEFT JOIN stores s ON c.store_id = s.id
                   WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Shared WHERE builder used by both getAllWithDetails and countWithDetails. */
    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['merchant_id'])) {
            $sql .= " AND c.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['approval_status'])) {
            $sql .= " AND c.approval_status = ?";
            $params[] = $filters['approval_status'];
        }
        if (!empty($filters['discount_type'])) {
            $sql .= " AND c.discount_type = ?";
            $params[] = $filters['discount_type'];
        }
        if (isset($filters['is_admin_coupon']) && $filters['is_admin_coupon'] !== '') {
            $sql .= " AND c.is_admin_coupon = ?";
            $params[] = (int)$filters['is_admin_coupon'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.title LIKE ? OR c.coupon_code LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
        if (!empty($filters['expiry'])) {
            if ($filters['expiry'] === 'active') {
                $sql .= " AND (c.valid_until IS NULL OR c.valid_until >= NOW())";
                $sql .= " AND (c.valid_from IS NULL OR c.valid_from <= NOW())";
            } elseif ($filters['expiry'] === 'expired') {
                $sql .= " AND c.valid_until < NOW()";
            } elseif ($filters['expiry'] === 'upcoming') {
                $sql .= " AND c.valid_from > NOW()";
            }
        }
    }

    /** Single coupon with full joined data. */
    public function findWithDetails($id) {
        $sql = "SELECT c.*,
                       m.business_name AS merchant_name,
                       s.store_name,
                       a.name AS approved_by_name,
                       (SELECT COUNT(*) FROM coupon_redemptions cr WHERE cr.coupon_id = c.id) AS redemption_count
                FROM {$this->table} c
                JOIN merchants m ON c.merchant_id = m.id
                LEFT JOIN stores s ON c.store_id = s.id
                LEFT JOIN admins a ON c.approved_by_admin_id = a.id
                WHERE c.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Aggregate stats for listing header. */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                             AS total,
                    SUM(CASE WHEN c.status='active'              THEN 1 ELSE 0 END)    AS active,
                    SUM(CASE WHEN c.status='expired'             THEN 1 ELSE 0 END)    AS expired,
                    SUM(CASE WHEN c.approval_status='pending'    THEN 1 ELSE 0 END)    AS pending_approval,
                    SUM(CASE WHEN c.approval_status='approved'   THEN 1 ELSE 0 END)    AS approved,
                    SUM(CASE WHEN c.is_admin_coupon=1            THEN 1 ELSE 0 END)    AS admin_coupons,
                    SUM(CASE WHEN DATE(c.created_at) = CURDATE() THEN 1 ELSE 0 END)    AS today,
                    (SELECT COUNT(*) FROM coupon_redemptions)                           AS total_redemptions
                FROM {$this->table} c";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /** Recent redemptions for a coupon. */
    public function getRedemptions($couponId, $limit = 20) {
        $sql = "SELECT cr.*, cu.name AS customer_name, s.store_name
                FROM coupon_redemptions cr
                JOIN customers cu ON cr.customer_id = cu.id
                LEFT JOIN stores s ON cr.store_id = s.id
                WHERE cr.coupon_id = ?
                ORDER BY cr.redeemed_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$couponId, $limit]);
        return $stmt->fetchAll();
    }

    /** Tags attached to a coupon. */
    public function getTags($couponId) {
        $sql = "SELECT t.* FROM coupon_tags ct JOIN tags t ON ct.tag_id = t.id WHERE ct.coupon_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$couponId]);
        return $stmt->fetchAll();
    }

    /** Replace coupon tags (delete old, insert new). */
    public function syncTags($couponId, array $tagIds) {
        $this->db->prepare("DELETE FROM coupon_tags WHERE coupon_id = ?")->execute([$couponId]);
        if (!empty($tagIds)) {
            $stmt = $this->db->prepare("INSERT IGNORE INTO coupon_tags (coupon_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                $stmt->execute([$couponId, (int)$tagId]);
            }
        }
    }

    /** Check if a coupon code already exists (optionally exclude one record). */
    public function codeExists($code, $excludeId = null) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE coupon_code = ?";
        $params = [$code];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ─── CREATE / UPDATE / DELETE ─────────────────────────────────────────────

    public function createCoupon($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        foreach (['store_id','usage_limit','min_purchase_amount','max_discount_amount',
                  'valid_from','valid_until','terms_conditions','description',
                  'approved_by_admin_id','approved_at'] as $nullable) {
            if (isset($data[$nullable]) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        $cols   = implode(', ', array_keys($data));
        $places = implode(', ', array_fill(0, count($data), '?'));
        $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function updateCoupon($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        foreach (['store_id','usage_limit','min_purchase_amount','max_discount_amount',
                  'valid_from','valid_until','terms_conditions','description'] as $nullable) {
            if (isset($data[$nullable]) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        $sets   = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        return $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE id = ?")->execute($values);
    }

    public function deleteCoupon($id) {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    // ─── APPROVAL / STATUS ────────────────────────────────────────────────────

    public function approve($id, $adminId) {
        return $this->db->prepare(
            "UPDATE {$this->table} SET approval_status='approved', approved_by_admin_id=?, approved_at=NOW(), updated_at=NOW() WHERE id=?"
        )->execute([$adminId, $id]);
    }

    public function reject($id, $adminId) {
        return $this->db->prepare(
            "UPDATE {$this->table} SET approval_status='rejected', approved_by_admin_id=?, approved_at=NOW(), updated_at=NOW() WHERE id=?"
        )->execute([$adminId, $id]);
    }

    public function toggleStatus($id) {
        $coupon = $this->find($id);
        if (!$coupon) return false;
        $new = $coupon['status'] === 'active' ? 'inactive' : 'active';
        return $this->db->prepare("UPDATE {$this->table} SET status=?, updated_at=NOW() WHERE id=?")
                        ->execute([$new, $id]);
    }

    // ─── GIFT COUPONS ─────────────────────────────────────────────────────────

    public function giftToCustomer($couponId, $customerId, $adminId, $requiresAcceptance = false, $expiresAt = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO gift_coupons (admin_id, customer_id, coupon_id, requires_acceptance, acceptance_status, gifted_at, expires_at)
             VALUES (?, ?, ?, ?, ?, NOW(), ?)"
        );
        $acceptStatus = $requiresAcceptance ? 'pending' : null;
        $stmt->execute([$adminId, $customerId, $couponId, (int)$requiresAcceptance, $acceptStatus, $expiresAt ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function getGiftHistory($couponId, $limit = 20) {
        $sql = "SELECT gc.*, cu.name AS customer_name
                FROM gift_coupons gc
                JOIN customers cu ON gc.customer_id = cu.id
                WHERE gc.coupon_id = ?
                ORDER BY gc.gifted_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$couponId, $limit]);
        return $stmt->fetchAll();
    }
}
