<?php
class MysteryShoppingTask extends Model {
    protected $table = 'mystery_shopping_tasks';

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        $sql = "SELECT mst.*,
                       c.name              AS shopper_name,
                       u.phone             AS shopper_phone,
                       m.business_name     AS merchant_name,
                       s.store_name        AS store_name,
                       a.name              AS assigned_by_name
                FROM {$this->table} mst
                JOIN  customers c  ON mst.customer_id          = c.id
                JOIN  users     u  ON c.user_id                 = u.id
                JOIN  merchants m  ON mst.merchant_id           = m.id
                LEFT JOIN stores s ON mst.store_id              = s.id
                LEFT JOIN admins a ON mst.assigned_by_admin_id  = a.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY mst.assigned_at DESC";
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} mst
                JOIN  customers c  ON mst.customer_id         = c.id
                JOIN  users     u  ON c.user_id                = u.id
                JOIN  merchants m  ON mst.merchant_id          = m.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND mst.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['payment_status'])) {
            $sql .= " AND mst.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['merchant_id'])) {
            $sql .= " AND mst.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.phone LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
        }
    }

    // ─── SINGLE ───────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $stmt = $this->db->prepare(
            "SELECT mst.*,
                    c.name              AS shopper_name,
                    u.phone             AS shopper_phone,
                    u.email             AS shopper_email,
                    m.business_name     AS merchant_name,
                    s.store_name        AS store_name,
                    s.address           AS store_address,
                    a.name              AS assigned_by_name
             FROM {$this->table} mst
             JOIN  customers c  ON mst.customer_id         = c.id
             JOIN  users     u  ON c.user_id                = u.id
             JOIN  merchants m  ON mst.merchant_id          = m.id
             LEFT JOIN stores s ON mst.store_id             = s.id
             LEFT JOIN admins a ON mst.assigned_by_admin_id = a.id
             WHERE mst.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ─── STATS ────────────────────────────────────────────────────────────────

    public function getStats() {
        return $this->db->query(
            "SELECT
               COUNT(*)                                AS total,
               SUM(status = 'assigned')                AS assigned,
               SUM(status = 'in_progress')             AS in_progress,
               SUM(status = 'completed')               AS completed,
               SUM(status = 'verified')                AS verified,
               SUM(status = 'rejected')                AS rejected,
               SUM(payment_status = 'pending' AND status = 'verified') AS payments_pending,
               COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN payment_amount END), 0) AS total_paid,
               SUM(DATE(assigned_at) = CURDATE())      AS assigned_today
             FROM {$this->table}"
        )->fetch();
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    public function createTask($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
             (customer_id, merchant_id, store_id, task_description, checklist_json,
              assigned_by_admin_id, payment_amount)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['customer_id'],
            $data['merchant_id'],
            $data['store_id'] ?: null,
            $data['task_description'],
            $data['checklist_json'] ?: null,
            $data['assigned_by_admin_id'],
            $data['payment_amount'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    // ─── STATUS ───────────────────────────────────────────────────────────────

    public function updateStatus($id, $status, $notes = '') {
        $extra = '';
        $params = [$status];

        if (in_array($status, ['completed', 'verified', 'rejected'])) {
            if ($status === 'completed') {
                $extra = ', completed_at = NOW()';
            } elseif ($status === 'verified') {
                $extra = ', verified_at = NOW()';
            }
            // Store notes/report in report_json if provided
            if ($notes) {
                $existing = $this->db->prepare("SELECT report_json FROM {$this->table} WHERE id = ?");
                $existing->execute([$id]);
                $row = $existing->fetch();
                $report = json_decode($row['report_json'] ?? '{}', true) ?: [];
                $report['admin_notes'] = $notes;
                $extra .= ', report_json = ?';
                $params[] = json_encode($report, JSON_UNESCAPED_UNICODE);
            }
        }

        $params[] = $id;
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = ? {$extra} WHERE id = ?"
        );
        return $stmt->execute($params);
    }

    public function markPaymentPaid($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET payment_status = 'paid' WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    // ─── DROPDOWNS ────────────────────────────────────────────────────────────

    public function getMerchantList() {
        return $this->db->query(
            "SELECT id, business_name FROM merchants WHERE profile_status = 'approved' ORDER BY business_name"
        )->fetchAll();
    }

    public function getStoresForMerchant($merchantId) {
        $stmt = $this->db->prepare(
            "SELECT id, store_name, address FROM stores WHERE merchant_id = ? AND status = 'active' ORDER BY store_name"
        );
        $stmt->execute([$merchantId]);
        return $stmt->fetchAll();
    }

    public function getShopperList() {
        return $this->db->query(
            "SELECT c.id, c.name, u.phone
             FROM customers c
             JOIN users u ON c.user_id = u.id
             WHERE u.status = 'active'
             ORDER BY c.name"
        )->fetchAll();
    }
}
