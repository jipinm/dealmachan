<?php
require_once CORE_PATH . '/Model.php';

class Referral extends Model {
    protected $table = 'referrals';

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 25));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT r.*,
                       rfr.name         AS referrer_name,
                       rfr.referral_code AS referrer_code,
                       urfr.phone       AS referrer_phone,
                       rfe.name         AS referee_name,
                       urfe.phone       AS referee_phone
                FROM {$this->table} r
                JOIN customers rfr ON r.referrer_customer_id = rfr.id
                JOIN users     urfr ON rfr.user_id           = urfr.id
                JOIN customers rfe ON r.referee_customer_id  = rfe.id
                JOIN users     urfe ON rfe.user_id           = urfe.id
                {$where}
                ORDER BY r.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} r
                JOIN customers rfr ON r.referrer_customer_id = rfr.id
                JOIN users     urfr ON rfr.user_id           = urfr.id
                JOIN customers rfe ON r.referee_customer_id  = rfe.id
                JOIN users     urfe ON rfe.user_id           = urfe.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conds[] = 'r.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['reward_given'])) {
            $conds[] = 'r.reward_given = ?';
            $params[] = (int)$filters['reward_given'];
        }
        if (!empty($filters['referrer_id'])) {
            $conds[] = 'r.referrer_customer_id = ?';
            $params[] = (int)$filters['referrer_id'];
        }
        if (!empty($filters['date_from'])) {
            $conds[] = 'DATE(r.created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conds[] = 'DATE(r.created_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $conds[] = '(rfr.name LIKE ? OR rfe.name LIKE ? OR urfr.phone LIKE ? OR r.referral_code LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            array_push($params, $s, $s, $s, $s);
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── SINGLE ────────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT r.*,
                       rfr.name          AS referrer_name,
                       rfr.referral_code AS referrer_code,
                       urfr.phone        AS referrer_phone,
                       rfe.name          AS referee_name,
                       urfe.phone        AS referee_phone
                FROM {$this->table} r
                JOIN customers rfr ON r.referrer_customer_id = rfr.id
                JOIN users     urfr ON rfr.user_id           = urfr.id
                JOIN customers rfe ON r.referee_customer_id  = rfe.id
                JOIN users     urfe ON rfe.user_id           = urfe.id
                WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ─── STATS ─────────────────────────────────────────────────────────────────

    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                AS total,
                    SUM(status = 'pending')                 AS pending,
                    SUM(status = 'completed')               AS completed,
                    SUM(status = 'rewarded')                AS rewarded,
                    SUM(reward_given = 1)                   AS reward_given_count,
                    COALESCE(SUM(reward_amount),0)          AS total_reward_pool,
                    COALESCE(SUM(CASE WHEN reward_given=1 THEN reward_amount END),0) AS rewards_paid
                FROM {$this->table}";
        $stmt = $this->db->query($sql);
        return $stmt->fetch();
    }

    // Top referrers (by conversion count)
    public function getTopReferrers($limit = 10) {
        $sql = "SELECT rfr.id, rfr.name, rfr.referral_code,
                       COUNT(r.id)                     AS total_referrals,
                       SUM(r.status = 'completed')     AS completed,
                       SUM(r.reward_given = 1)         AS rewards,
                       COALESCE(SUM(CASE WHEN r.reward_given=1 THEN r.reward_amount END), 0) AS earned
                FROM {$this->table} r
                JOIN customers rfr ON r.referrer_customer_id = rfr.id
                GROUP BY rfr.id, rfr.name, rfr.referral_code
                ORDER BY total_referrals DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // Per-customer referral history (used in customer profile tab)
    public function getByReferrer($customerId) {
        $sql = "SELECT r.*,
                       rfe.name      AS referee_name,
                       urfe.phone    AS referee_phone
                FROM {$this->table} r
                JOIN customers rfe ON r.referee_customer_id = rfe.id
                JOIN users     urfe ON rfe.user_id          = urfe.id
                WHERE r.referrer_customer_id = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }

    // ─── WRITES ────────────────────────────────────────────────────────────────

    public function markRewardGiven($id) {
        $sql = "UPDATE {$this->table} SET reward_given = 1, status = 'rewarded' WHERE id = ? AND reward_given = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function overrideStatus($id, $status) {
        $allowed = ['pending', 'completed', 'rewarded'];
        if (!in_array($status, $allowed)) return false;
        $sql = "UPDATE {$this->table} SET status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
}
