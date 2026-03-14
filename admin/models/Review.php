<?php
class Review extends Model {
    protected $table = 'reviews';

    public static $statuses = ['pending', 'approved', 'rejected'];

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All reviews with joined customer, merchant, store names.
     * Filters: status, rating, merchant_id, search (customer name / review text)
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT r.*,
                       c.name           AS customer_name,
                       u.phone          AS customer_phone,
                       m.business_name  AS merchant_name,
                       s.store_name
                FROM {$this->table} r
                JOIN customers c  ON r.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON r.merchant_id   = m.id
                LEFT JOIN stores s ON r.store_id     = s.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY
                    CASE r.status WHEN 'pending' THEN 0 ELSE 1 END,
                    r.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count matching reviews (same filters, no LIMIT). */
    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*)
                FROM {$this->table} r
                JOIN customers c  ON r.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON r.merchant_id   = m.id
                LEFT JOIN stores s ON r.store_id     = s.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Shared WHERE builder. */
    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['rating'])) {
            $sql .= " AND r.rating = ?";
            $params[] = (int)$filters['rating'];
        }
        if (!empty($filters['merchant_id'])) {
            $sql .= " AND r.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR r.review_text LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
    }

    /** Single review with full joined data. */
    public function findWithDetails($id) {
        $sql = "SELECT r.*,
                       c.name           AS customer_name,
                       c.id             AS customer_id,
                       u.phone          AS customer_phone,
                       u.email          AS customer_email,
                       m.business_name  AS merchant_name,
                       m.id             AS merchant_id,
                       s.store_name
                FROM {$this->table} r
                JOIN customers c  ON r.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON r.merchant_id   = m.id
                LEFT JOIN stores s ON r.store_id     = s.id
                WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Aggregate stats for the listing header. */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                          AS total,
                    SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END)          AS approved,
                    SUM(CASE WHEN r.status IN ('flagged','rejected') THEN 1 ELSE 0 END) AS flagged,
                    SUM(CASE WHEN r.status = 'pending'  THEN 1 ELSE 0 END)          AS pending,
                    ROUND(AVG(CASE WHEN r.status = 'approved' THEN r.rating END), 1) AS avg_rating,
                    SUM(CASE WHEN DATE(r.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                FROM {$this->table} r";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    // ─── FLAG (post-publish moderation) ─────────────────────────────────────

    public function flag($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = 'flagged', updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    // ─── RESTORE (unflag → back to approved) ─────────────────────────────────

    public function restore($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = 'approved', updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    // ─── APPROVE (legacy &mdash; kept for backward-compat) ─────────────────────────

    public function approve($id) {
        return $this->restore($id);
    }

    // ─── REJECT (legacy &mdash; kept for backward-compat, now means flag) ──────────

    public function reject($id) {
        return $this->flag($id);
    }

    // ─── BULK UPDATE ──────────────────────────────────────────────────────────

    public function bulkUpdateStatus(array $ids, $status) {
        if (empty($ids)) return false;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge($ids, [$status]);
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = ?, updated_at = NOW()
             WHERE id IN ({$placeholders})"
        );
        // Reorder: status first, then IDs
        $params = array_merge([$status], $ids);
        return $stmt->execute($params);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function deleteReview($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ─── MERCHANT LIST (for filter dropdown) ─────────────────────────────────

    public function getMerchantsWithReviews() {
        $sql = "SELECT DISTINCT m.id, m.business_name
                FROM merchants m
                JOIN {$this->table} r ON r.merchant_id = m.id
                ORDER BY m.business_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
