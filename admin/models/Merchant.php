<?php
class Merchant extends Model {
    protected $table = 'merchants';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All merchants with joined user + label data.
     * Filters: status (user status), profile_status, subscription_status,
     *          is_premium, search (business_name / email / phone), city_id
     * Pagination: limit, offset
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT m.*,
                       u.email, u.phone, u.status, u.last_login,
                       u.created_at AS registered_at,
                       l.label_name,
                       (SELECT COUNT(*) FROM stores s WHERE s.merchant_id = m.id) AS store_count,
                       (SELECT COUNT(*) FROM coupons cp WHERE cp.merchant_id = m.id) AS coupon_count
                FROM {$this->table} m
                JOIN  users u  ON m.user_id  = u.id
                LEFT JOIN labels l ON m.label_id = l.id
                WHERE u.user_type = 'merchant'";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['profile_status'])) {
            $sql .= " AND m.profile_status = ?";
            $params[] = $filters['profile_status'];
        }
        if (!empty($filters['subscription_status'])) {
            $sql .= " AND m.subscription_status = ?";
            $params[] = $filters['subscription_status'];
        }
        if (isset($filters['is_premium']) && $filters['is_premium'] !== '') {
            $sql .= " AND m.is_premium = ?";
            $params[] = (int)$filters['is_premium'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (m.business_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR m.registration_number LIKE ? OR m.gst_number LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY m.priority_weight DESC, m.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count total matching merchants (same WHERE, no LIMIT).
     */
    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} m
                JOIN users u ON m.user_id = u.id
                WHERE u.user_type = 'merchant'";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['profile_status'])) {
            $sql .= " AND m.profile_status = ?";
            $params[] = $filters['profile_status'];
        }
        if (!empty($filters['subscription_status'])) {
            $sql .= " AND m.subscription_status = ?";
            $params[] = $filters['subscription_status'];
        }
        if (isset($filters['is_premium']) && $filters['is_premium'] !== '') {
            $sql .= " AND m.is_premium = ?";
            $params[] = (int)$filters['is_premium'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (m.business_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR m.registration_number LIKE ? OR m.gst_number LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like;
            $params[] = $like; $params[] = $like;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Single merchant with full details.
     */
    public function findWithDetails($id) {
        $sql = "SELECT m.*,
                       u.email, u.phone, u.status, u.last_login,
                       u.created_at AS registered_at,
                       l.label_name,
                       (SELECT COUNT(*) FROM stores s WHERE s.merchant_id = m.id) AS store_count,
                       (SELECT COUNT(*) FROM coupons cp WHERE cp.merchant_id = m.id) AS coupon_count,
                       (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.merchant_id = m.id AND r.status='approved') AS avg_rating,
                       (SELECT COUNT(*) FROM reviews r WHERE r.merchant_id = m.id AND r.status='approved') AS review_count
                FROM {$this->table} m
                JOIN  users u  ON m.user_id  = u.id
                LEFT JOIN labels l ON m.label_id = l.id
                WHERE m.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Aggregate stats for the listing page header cards.
     */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                        AS total,
                    SUM(CASE WHEN u.status='active'                 THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN m.profile_status='pending'        THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN m.profile_status='approved'       THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN m.is_premium=1                    THEN 1 ELSE 0 END) AS premium,
                    SUM(CASE WHEN m.subscription_status='active'    THEN 1 ELSE 0 END) AS subscribed,
                    SUM(CASE WHEN DATE(m.created_at) = CURDATE()    THEN 1 ELSE 0 END) AS today
                FROM {$this->table} m
                JOIN users u ON m.user_id = u.id
                WHERE u.user_type = 'merchant'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get all stores belonging to a merchant.
     */
    public function getStores($merchantId) {
        $sql = "SELECT s.*, c.city_name, a.area_name
                FROM stores s
                JOIN cities c ON s.city_id = c.id
                JOIN areas  a ON s.area_id = a.id
                WHERE s.merchant_id = ?
                ORDER BY s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$merchantId]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent reviews for a merchant.
     */
    public function getReviews($merchantId, $limit = 10) {
        $sql = "SELECT r.*, cu.name AS customer_name
                FROM reviews r
                JOIN customers cu ON r.customer_id = cu.id
                WHERE r.merchant_id = ?
                ORDER BY r.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$merchantId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get assigned labels for a merchant.
     */
    public function getMerchantLabels($merchantId) {
        $sql = "SELECT l.*
                FROM merchant_labels ml
                JOIN labels l ON ml.label_id = l.id
                WHERE ml.merchant_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$merchantId]);
        return $stmt->fetchAll();
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    /**
     * Create user + merchant rows inside a transaction.
     * $userData    : email, phone, password (plain), status
     * $merchantData: business_name, registration_number, gst_number, is_premium,
     *                subscription_status, subscription_expiry, profile_status,
     *                priority_weight, label_id
     */
    public function createWithUser($userData, $merchantData) {
        $this->db->beginTransaction();
        try {
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
            $userData['user_type']  = 'merchant';
            $userData['status']     = $userData['status'] ?? 'active';
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');

            $cols   = implode(', ', array_keys($userData));
            $places = implode(', ', array_fill(0, count($userData), '?'));
            $stmt   = $this->db->prepare("INSERT INTO users ({$cols}) VALUES ({$places})");
            $stmt->execute(array_values($userData));
            $userId = (int)$this->db->lastInsertId();

            $merchantData['user_id']    = $userId;
            $merchantData['created_at'] = date('Y-m-d H:i:s');
            $merchantData['updated_at'] = date('Y-m-d H:i:s');

            foreach (['label_id', 'subscription_expiry', 'registration_number', 'gst_number'] as $nullable) {
                if (isset($merchantData[$nullable]) && $merchantData[$nullable] === '') {
                    $merchantData[$nullable] = null;
                }
            }

            $cols   = implode(', ', array_keys($merchantData));
            $places = implode(', ', array_fill(0, count($merchantData), '?'));
            $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
            $stmt->execute(array_values($merchantData));
            $merchantId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $merchantId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    /**
     * Update both user and merchant rows.
     * Pass only the keys you want to change.
     */
    public function updateWithUser($id, $userData, $merchantData) {
        $existing = $this->find($id);
        if (!$existing) return false;

        $this->db->beginTransaction();
        try {
            if (!empty($userData)) {
                if (isset($userData['password'])) {
                    $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
                    unset($userData['password']);
                }
                $userData['updated_at'] = date('Y-m-d H:i:s');
                $sets     = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($userData)));
                $values   = array_values($userData);
                $values[] = $existing['user_id'];
                $this->db->prepare("UPDATE users SET {$sets} WHERE id = ?")->execute($values);
            }

            if (!empty($merchantData)) {
                foreach (['label_id', 'subscription_expiry', 'registration_number', 'gst_number'] as $nullable) {
                    if (isset($merchantData[$nullable]) && $merchantData[$nullable] === '') {
                        $merchantData[$nullable] = null;
                    }
                }
                $merchantData['updated_at'] = date('Y-m-d H:i:s');
                $sets     = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($merchantData)));
                $values   = array_values($merchantData);
                $values[] = $id;
                $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE id = ?")->execute($values);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function deleteWithUser($id) {
        $merchant = $this->find($id);
        if (!$merchant) return false;

        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$merchant['user_id']]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── STATUS / APPROVAL ────────────────────────────────────────────────────

    public function toggleStatus($id) {
        $merchant = $this->findWithDetails($id);
        if (!$merchant) return false;
        $newStatus = $merchant['status'] === 'active' ? 'blocked' : 'active';
        return $this->db->prepare("UPDATE users SET status=? WHERE id=?")
                        ->execute([$newStatus, $merchant['user_id']]);
    }

    public function updateProfileStatus($id, $status) {
        return $this->db->prepare("UPDATE {$this->table} SET profile_status=?, updated_at=NOW() WHERE id=?")
                        ->execute([$status, $id]);
    }

    // ─── UNIQUENESS HELPERS ───────────────────────────────────────────────────

    public function emailExists($email, $excludeUserId = null) {
        $sql    = "SELECT COUNT(*) FROM users WHERE email = ? AND user_type = 'merchant'";
        $params = [$email];
        if ($excludeUserId) { $sql .= " AND id != ?"; $params[] = $excludeUserId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function phoneExists($phone, $excludeUserId = null) {
        $sql    = "SELECT COUNT(*) FROM users WHERE phone = ? AND user_type = 'merchant'";
        $params = [$phone];
        if ($excludeUserId) { $sql .= " AND id != ?"; $params[] = $excludeUserId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }
}
