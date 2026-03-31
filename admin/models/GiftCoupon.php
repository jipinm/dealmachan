<?php
require_once CORE_PATH . '/Model.php';

class GiftCoupon extends Model {
    protected $table = 'gift_coupons';

    private $hasBatchTable = null;
    private $hasBatchColumn = null;

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
        $requiresAcceptance = !empty($data['requires_acceptance']) ? 1 : 0;
        $acceptanceStatus   = $requiresAcceptance ? 'pending' : 'accepted';
        $acceptedAt         = $requiresAcceptance ? null : date('Y-m-d H:i:s');

        $sql = "INSERT INTO {$this->table}
                    (admin_id, customer_id, coupon_id, requires_acceptance, acceptance_status, accepted_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['admin_id'],
            $data['customer_id'],
            $data['coupon_id'],
            $requiresAcceptance,
            $acceptanceStatus,
            $acceptedAt,
            $data['expires_at'] ?: null,
        ]);
        return $this->db->lastInsertId();
    }

    public function createGiftBatch($data) {
        if (!$this->supportsGiftBatches()) {
            return null;
        }

        $sql = "INSERT INTO gift_coupon_batches
                    (admin_id, coupon_id, filter_criteria, total_recipients, requires_acceptance, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$data['admin_id'],
            (int)$data['coupon_id'],
            $data['filter_criteria'] ?? null,
            (int)$data['total_recipients'],
            !empty($data['requires_acceptance']) ? 1 : 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function createBulkGifts($adminId, $couponId, array $customerIds, $requiresAcceptance, $batchId = null, $expiresAt = null) {
        if (empty($customerIds)) {
            return 0;
        }

        $requiresAcceptance = $requiresAcceptance ? 1 : 0;
        $acceptanceStatus   = $requiresAcceptance ? 'pending' : 'accepted';
        $acceptedAt         = $requiresAcceptance ? null : date('Y-m-d H:i:s');

        $columns = ['admin_id', 'customer_id', 'coupon_id', 'requires_acceptance', 'acceptance_status', 'gifted_at', 'accepted_at', 'expires_at'];
        $values  = ['?', '?', '?', '?', '?', 'NOW()', '?', '?'];

        if ($batchId && $this->supportsGiftBatches()) {
            $columns[] = 'batch_id';
            $values[]  = '?';
        }

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($customerIds as $customerId) {
            $params = [
                (int)$adminId,
                (int)$customerId,
                (int)$couponId,
                $requiresAcceptance,
                $acceptanceStatus,
                $acceptedAt,
                $expiresAt ?: null,
            ];

            if ($batchId && $this->supportsGiftBatches()) {
                $params[] = (int)$batchId;
            }

            $stmt->execute($params);
            $inserted++;
        }

        return $inserted;
    }

    public function createCustomerNotifications(array $customerIds, array $data) {
        if (empty($customerIds)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO notifications (user_id, user_type, notification_type, title, message, action_url)
             VALUES (?, 'customer', ?, ?, ?, ?)"
        );

        $inserted = 0;
        foreach ($customerIds as $customerId) {
            $stmt->execute([
                (int)$customerId,
                $data['notification_type'] ?? 'info',
                $data['title'],
                $data['message'],
                $data['action_url'] ?? null,
            ]);
            $inserted++;
        }

        return $inserted;
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
        $sql = "SELECT id, title, coupon_code
                FROM coupons
                WHERE status = 'active'
                  AND approval_status = 'approved'
                ORDER BY title ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getCouponById($couponId) {
        $stmt = $this->db->prepare(
            "SELECT id, title, coupon_code, status, approval_status
             FROM coupons
             WHERE id = ?
             LIMIT 1"
        );
        $stmt->execute([(int)$couponId]);
        return $stmt->fetch() ?: null;
    }

    public function getCustomerList() {
        $sql = "SELECT c.id, c.name, u.phone
                FROM customers c JOIN users u ON c.user_id = u.id
                ORDER BY c.name ASC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getActiveProfessions() {
        return $this->db->query(
            "SELECT id, profession_name
             FROM professions
             WHERE status = 'active'
             ORDER BY profession_name ASC"
        )->fetchAll();
    }

    public function getActiveCities() {
        return $this->db->query(
            "SELECT id, city_name
             FROM cities
             WHERE status = 'active'
             ORDER BY city_name ASC"
        )->fetchAll();
    }

    public function getActiveAreas() {
        return $this->db->query(
            "SELECT id, city_id, area_name
             FROM areas
             WHERE status = 'active'
             ORDER BY area_name ASC"
        )->fetchAll();
    }

    public function getClubSubClassifications() {
        return $this->db->query(
            "SELECT id, name
             FROM card_sub_classifications
             WHERE LOWER(name) LIKE '%club%'
             ORDER BY name ASC"
        )->fetchAll();
    }

    public function countRecipientsByFilters($filters = []) {
        [$whereSql, $params] = $this->buildRecipientFilterClause($filters);

        $sql = "SELECT COUNT(DISTINCT cu.id) AS cnt
                FROM customers cu
                JOIN users u ON u.id = cu.user_id
                {$whereSql}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    public function getRecipientsByFilters($filters = [], $limit = 0) {
        [$whereSql, $params] = $this->buildRecipientFilterClause($filters);

        $limitSql = '';
        if ($limit > 0) {
            $limitSql = ' LIMIT ' . (int)$limit;
        }

        $sql = "SELECT DISTINCT cu.id, cu.name, cu.gender, cu.city_id, cu.area_id,
                       cu.profession_id, cu.date_of_birth,
                       u.email, u.phone
                FROM customers cu
                JOIN users u ON u.id = cu.user_id
                {$whereSql}
                ORDER BY cu.name ASC{$limitSql}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function buildRecipientFilterClause($filters = []) {
        $conds = [
            "u.user_type = 'customer'",
            "u.status = 'active'",
        ];
        $params = [];

        if (!empty($filters['card_segment'])) {
            $conds[] = "EXISTS (
                SELECT 1
                FROM cards ca
                JOIN card_configurations cc ON cc.id = ca.card_configuration_id
                WHERE ca.assigned_to_customer_id = cu.id
                  AND ca.status = 'activated'
                  AND cc.classification = ?
            )";
            $params[] = $filters['card_segment'];
        }

        if (!empty($filters['club_ids']) && is_array($filters['club_ids'])) {
            $clubIds = array_values(array_filter(array_map('intval', $filters['club_ids'])));
            if (!empty($clubIds)) {
                $ph = implode(',', array_fill(0, count($clubIds), '?'));
                $conds[] = "EXISTS (
                    SELECT 1
                    FROM cards ca2
                    JOIN card_config_sub_class_map csm ON csm.config_id = ca2.card_configuration_id
                    WHERE ca2.assigned_to_customer_id = cu.id
                      AND ca2.status = 'activated'
                      AND csm.sub_class_id IN ({$ph})
                )";
                foreach ($clubIds as $clubId) {
                    $params[] = $clubId;
                }
            }
        }

        if (!empty($filters['profession_ids']) && is_array($filters['profession_ids'])) {
            $professionIds = array_values(array_filter(array_map('intval', $filters['profession_ids'])));
            if (!empty($professionIds)) {
                $ph = implode(',', array_fill(0, count($professionIds), '?'));
                $conds[] = "cu.profession_id IN ({$ph})";
                foreach ($professionIds as $pid) {
                    $params[] = $pid;
                }
            }
        }

        if (!empty($filters['birth_month'])) {
            $month = (int)$filters['birth_month'];
            if ($month >= 1 && $month <= 12) {
                $conds[] = 'cu.date_of_birth IS NOT NULL AND MONTH(cu.date_of_birth) = ?';
                $params[] = $month;
            }
        }

        if (!empty($filters['city_id'])) {
            $conds[] = 'cu.city_id = ?';
            $params[] = (int)$filters['city_id'];
        }

        if (!empty($filters['area_id'])) {
            $conds[] = 'cu.area_id = ?';
            $params[] = (int)$filters['area_id'];
        }

        if (!empty($filters['gender']) && in_array($filters['gender'], ['male', 'female'], true)) {
            $conds[] = 'cu.gender = ?';
            $params[] = $filters['gender'];
        }

        $whereSql = 'WHERE ' . implode(' AND ', $conds);
        return [$whereSql, $params];
    }

    private function supportsGiftBatches() {
        if ($this->hasBatchTable === null) {
            $stmt = $this->db->prepare("SHOW TABLES LIKE 'gift_coupon_batches'");
            $stmt->execute();
            $this->hasBatchTable = (bool)$stmt->fetchColumn();
        }

        if (!$this->hasBatchTable) {
            return false;
        }

        if ($this->hasBatchColumn === null) {
            $stmt = $this->db->prepare("SHOW COLUMNS FROM gift_coupons LIKE 'batch_id'");
            $stmt->execute();
            $this->hasBatchColumn = (bool)$stmt->fetchColumn();
        }

        return $this->hasBatchColumn;
    }
}
