<?php
require_once CORE_PATH . '/Model.php';

class SalesRegistry extends Model {
    protected $table = 'sales_registry';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 25));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT sr.*,
                       m.business_name,
                       s.store_name,
                       c.name          AS customer_name,
                       u.phone         AS customer_phone,
                       cp.title        AS coupon_title
                FROM {$this->table} sr
                JOIN merchants m  ON sr.merchant_id  = m.id
                JOIN stores   s  ON sr.store_id      = s.id
                LEFT JOIN customers c  ON sr.customer_id  = c.id
                LEFT JOIN users     u  ON c.user_id        = u.id
                LEFT JOIN coupons   cp ON sr.coupon_used   = cp.id
                {$where}
                ORDER BY sr.transaction_date DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} sr
                JOIN merchants m  ON sr.merchant_id  = m.id
                JOIN stores   s  ON sr.store_id      = s.id
                LEFT JOIN customers c  ON sr.customer_id  = c.id
                LEFT JOIN users     u  ON c.user_id        = u.id
                LEFT JOIN coupons   cp ON sr.coupon_used   = cp.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['merchant_id'])) {
            $conds[]  = "sr.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['store_id'])) {
            $conds[]  = "sr.store_id = ?";
            $params[] = (int)$filters['store_id'];
        }
        if (!empty($filters['payment_method'])) {
            $conds[]  = "sr.payment_method = ?";
            $params[] = $filters['payment_method'];
        }
        if (!empty($filters['date_from'])) {
            $conds[]  = "DATE(sr.transaction_date) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conds[]  = "DATE(sr.transaction_date) <= ?";
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $conds[]  = "(m.business_name LIKE ? OR s.store_name LIKE ? OR c.name LIKE ?)";
            $like     = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like);
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── DETAIL ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT sr.*,
                       m.business_name,
                       s.store_name,
                       c.name          AS customer_name,
                       u.phone         AS customer_phone,
                       u.email         AS customer_email,
                       cp.title        AS coupon_title,
                       cp.coupon_code  AS coupon_code
                FROM {$this->table} sr
                JOIN merchants m  ON sr.merchant_id  = m.id
                JOIN stores   s  ON sr.store_id      = s.id
                LEFT JOIN customers c  ON sr.customer_id  = c.id
                LEFT JOIN users     u  ON c.user_id        = u.id
                LEFT JOIN coupons   cp ON sr.coupon_used   = cp.id
                WHERE sr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT
                    COUNT(*)                                                   AS total_transactions,
                    COALESCE(SUM(sr.transaction_amount), 0)                    AS total_gmv,
                    COALESCE(SUM(sr.discount_amount), 0)                       AS total_discount,
                    COALESCE(AVG(sr.transaction_amount), 0)                    AS avg_transaction,
                    SUM(CASE WHEN sr.coupon_used IS NOT NULL THEN 1 ELSE 0 END) AS coupon_transactions,
                    SUM(CASE WHEN sr.payment_method = 'cash'   THEN 1 ELSE 0 END) AS cash_count,
                    SUM(CASE WHEN sr.payment_method = 'upi'    THEN 1 ELSE 0 END) AS upi_count,
                    SUM(CASE WHEN sr.payment_method = 'card'   THEN 1 ELSE 0 END) AS card_count,
                    SUM(CASE WHEN sr.payment_method = 'wallet' THEN 1 ELSE 0 END) AS wallet_count
                FROM {$this->table} sr
                JOIN merchants m  ON sr.merchant_id = m.id
                JOIN stores   s  ON sr.store_id     = s.id
                LEFT JOIN customers c ON sr.customer_id = c.id
                LEFT JOIN users     u ON c.user_id      = u.id
                LEFT JOIN coupons  cp ON sr.coupon_used = cp.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /** For CSV export &mdash; no limit/offset applied. */
    public function getForExport($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT sr.id, sr.transaction_date, sr.transaction_amount, sr.discount_amount,
                       sr.payment_method,
                       m.business_name, s.store_name,
                       c.name AS customer_name, u.phone AS customer_phone,
                       cp.title AS coupon_title, cp.coupon_code AS coupon_code
                FROM {$this->table} sr
                JOIN merchants m  ON sr.merchant_id  = m.id
                JOIN stores   s  ON sr.store_id      = s.id
                LEFT JOIN customers c  ON sr.customer_id  = c.id
                LEFT JOIN users     u  ON c.user_id        = u.id
                LEFT JOIN coupons   cp ON sr.coupon_used   = cp.id
                {$where}
                ORDER BY sr.transaction_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
