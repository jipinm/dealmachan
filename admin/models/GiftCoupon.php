<?php
require_once CORE_PATH . '/Model.php';

class GiftCoupon extends Model {
    protected $table = 'gift_coupons';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1,  (int)($filters['limit']  ?? 25));
        $offset = max(0,  (int)($filters['offset'] ?? 0));

        $sql = "SELECT gc.*,
                       cp.title        AS coupon_title,
                       cp.coupon_code  AS coupon_code,
                       c.name          AS customer_name,
                       u.phone         AS customer_phone,
                       a.name          AS gifted_by
                FROM {$this->table} gc
                JOIN coupons   cp ON gc.coupon_id   = cp.id
                JOIN customers c  ON gc.customer_id = c.id
                JOIN users     u  ON c.user_id      = u.id
                JOIN admins    a  ON gc.admin_id    = a.id
                {$where}
                ORDER BY gc.gifted_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} gc
                JOIN coupons   cp ON gc.coupon_id   = cp.id
                JOIN customers c  ON gc.customer_id = c.id
                JOIN users     u  ON c.user_id      = u.id
                JOIN admins    a  ON gc.admin_id    = a.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['customer_id'])) {
            $conds[] = 'gc.customer_id = ?';
            $params[] = $filters['customer_id'];
        }
        if (!empty($filters['coupon_id'])) {
            $conds[] = 'gc.coupon_id = ?';
            $params[] = $filters['coupon_id'];
        }
        if (!empty($filters['acceptance_status'])) {
            if ($filters['acceptance_status'] === 'null') {
                $conds[] = 'gc.acceptance_status IS NULL';
            } else {
                $conds[] = 'gc.acceptance_status = ?';
                $params[] = $filters['acceptance_status'];
            }
        }
        if (!empty($filters['date_from'])) {
            $conds[] = 'DATE(gc.gifted_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conds[] = 'DATE(gc.gifted_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $conds[] = '(c.name LIKE ? OR cp.title LIKE ? OR cp.coupon_code LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$like, $like, $like, $like]);
        }
        // Expiry alerts
        if (!empty($filters['expiring_soon'])) {
            $conds[] = 'gc.expires_at IS NOT NULL AND gc.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)';
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── SINGLE ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT gc.*,
                       cp.title        AS coupon_title,
                       cp.coupon_code  AS coupon_code,
                       cp.discount_type, cp.discount_value, cp.valid_until,
                       c.name          AS customer_name,
                       u.phone         AS customer_phone,
                       u.email         AS customer_email,
                       a.name          AS gifted_by
                FROM {$this->table} gc
                JOIN coupons   cp ON gc.coupon_id   = cp.id
                JOIN customers c  ON gc.customer_id = c.id
                JOIN users     u  ON c.user_id      = u.id
                JOIN admins    a  ON gc.admin_id    = a.id
                WHERE gc.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats() {
        $row = $this->db->query("SELECT
            COUNT(*)                                                            AS total,
            SUM(requires_acceptance = 0 OR acceptance_status = 'accepted')      AS gifted,
            SUM(acceptance_status = 'pending')                                  AS pending,
            SUM(acceptance_status = 'accepted')                                 AS accepted,
            SUM(acceptance_status = 'rejected')                                 AS rejected,
            SUM(expires_at IS NOT NULL AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY) AND (acceptance_status IS NULL OR acceptance_status = 'pending')) AS expiring_soon
            FROM {$this->table}")->fetch();
        return $row ?: [];
    }

    // ─── WRITE ───────────────────────────────────────────────────────────────

    public function createGift($data) {
        $sql = "INSERT INTO {$this->table}
                    (admin_id, customer_id, coupon_id, requires_acceptance, expires_at)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['admin_id'],
            $data['customer_id'],
            $data['coupon_id'],
            $data['requires_acceptance'] ? 1 : 0,
            $data['expires_at'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function revoke($id) {
        // Only revoke if still pending or no acceptance required
        $sql = "DELETE FROM {$this->table}
                WHERE id = ? AND (acceptance_status IS NULL OR acceptance_status = 'pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    // ─── HELPERS FOR DROPDOWNS ───────────────────────────────────────────────

    public function getActiveCoupons() {
        $sql = "SELECT id, title, coupon_code FROM coupons
                WHERE status = 'active'
                ORDER BY title ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getCustomerList() {
        $sql = "SELECT c.id, c.name, u.phone
                FROM customers c JOIN users u ON c.user_id = u.id
                ORDER BY c.name ASC";
        return $this->db->query($sql)->fetchAll();
    }
}
