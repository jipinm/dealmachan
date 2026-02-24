<?php
class Customer extends Model {
    protected $table = 'customers';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * Get all customers with joined user, profession and city data.
     * Supports filters: status, customer_type, search (name/email/phone).
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT c.*,
                       u.email, u.phone, u.status, u.last_login,
                       u.created_at AS registered_at,
                       p.profession_name,
                       ref.name AS referrer_name
                FROM {$this->table} c
                JOIN  users u  ON c.user_id  = u.id
                LEFT JOIN professions p   ON c.profession_id  = p.id
                LEFT JOIN customers   ref ON c.referred_by    = ref.id
                WHERE u.user_type = 'customer'";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_type'])) {
            $sql .= " AND c.customer_type = ?";
            $params[] = $filters['customer_type'];
        }
        if (!empty($filters['registration_type'])) {
            $sql .= " AND c.registration_type = ?";
            $params[] = $filters['registration_type'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR c.referral_code LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

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

    /**
     * Count total matching customers (same filters, no LIMIT).
     */
    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} c
                JOIN  users u  ON c.user_id  = u.id
                WHERE u.user_type = 'customer'";

        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND u.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['customer_type'])) {
            $sql .= " AND c.customer_type = ?";
            $params[] = $filters['customer_type'];
        }
        if (!empty($filters['registration_type'])) {
            $sql .= " AND c.registration_type = ?";
            $params[] = $filters['registration_type'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR c.referral_code LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find a single customer with full detail (user + profession + referrer).
     */
    public function findWithDetails($id) {
        $sql = "SELECT c.*,
                       u.email, u.phone, u.status, u.last_login,
                       u.created_at AS registered_at,
                       p.profession_name,
                       ref.name AS referrer_name,
                       ref.referral_code AS referrer_code
                FROM {$this->table} c
                JOIN  users u  ON c.user_id  = u.id
                LEFT JOIN professions p   ON c.profession_id  = p.id
                LEFT JOIN customers   ref ON c.referred_by    = ref.id
                WHERE c.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get coupon redemption history for a customer.
     */
    public function getRedemptions($customerId, $limit = 20) {
        $sql = "SELECT cr.*, cp.title AS coupon_title, cp.discount_value, cp.discount_type
                FROM coupon_redemptions cr
                JOIN coupons cp ON cr.coupon_id = cp.id
                WHERE cr.customer_id = ?
                ORDER BY cr.redeemed_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get transaction analytics from sales_registry for a customer.
     */
    public function getTransactionAnalytics($customerId) {
        $sql = "SELECT
                    COUNT(*)                                   AS total_transactions,
                    COALESCE(SUM(sr.transaction_amount), 0)    AS total_spent,
                    COALESCE(AVG(sr.transaction_amount), 0)    AS avg_transaction,
                    COALESCE(SUM(sr.discount_amount), 0)       AS total_discount,
                    MIN(sr.transaction_date)                   AS first_transaction,
                    MAX(sr.transaction_date)                   AS last_transaction,
                    COUNT(DISTINCT sr.merchant_id)              AS merchant_count,
                    COUNT(DISTINCT sr.store_id)                 AS store_count
                FROM sales_registry sr
                WHERE sr.customer_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId]);
        return $stmt->fetch();
    }

    /**
     * Get recent transactions from sales_registry for a customer.
     */
    public function getRecentTransactions($customerId, $limit = 10) {
        $sql = "SELECT sr.*, m.business_name AS merchant_name, s.store_name
                FROM sales_registry sr
                JOIN merchants m ON sr.merchant_id = m.id
                JOIN stores   s ON sr.store_id    = s.id
                WHERE sr.customer_id = ?
                ORDER BY sr.transaction_date DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get store coupons gifted to a customer.
     */
    public function getStoreCoupons($customerId, $limit = 10) {
        $sql = "SELECT sc.*, m.business_name AS merchant_name, s.store_name
                FROM store_coupons sc
                JOIN merchants m ON sc.merchant_id = m.id
                JOIN stores   s ON sc.store_id    = s.id
                WHERE sc.gifted_to_customer_id = ?
                ORDER BY sc.gifted_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Aggregate statistics for the listing page header.
     */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN u.status='active'   THEN 1 ELSE 0 END) AS active,
                    SUM(CASE WHEN u.status='blocked'  THEN 1 ELSE 0 END) AS blocked,
                    SUM(CASE WHEN c.customer_type='premium'   THEN 1 ELSE 0 END) AS premium,
                    SUM(CASE WHEN c.customer_type='dealmaker' THEN 1 ELSE 0 END) AS dealmakers,
                    SUM(CASE WHEN DATE(c.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                WHERE u.user_type = 'customer'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    /**
     * Create customer + associated user in a transaction.
     * $userData  : keys → email, phone, password (plain), status
     * $customerData : keys → name, date_of_birth, gender, profession_id,
     *                        customer_type, registration_type, …
     */
    public function createWithUser($userData, $customerData) {
        $this->db->beginTransaction();
        try {
            // 1. Create user row
            $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
            unset($userData['password']);
            $userData['user_type']  = 'customer';
            $userData['status']     = $userData['status'] ?? 'active';
            $userData['created_at'] = date('Y-m-d H:i:s');
            $userData['updated_at'] = date('Y-m-d H:i:s');

            $cols   = implode(', ', array_keys($userData));
            $places = implode(', ', array_fill(0, count($userData), '?'));
            $stmt   = $this->db->prepare("INSERT INTO users ({$cols}) VALUES ({$places})");
            $stmt->execute(array_values($userData));
            $userId = (int)$this->db->lastInsertId();

            // 2. Generate referral code
            $referralCode = 'REF' . strtoupper(substr(md5($userId . time()), 0, 8));

            // 3. Create customer row
            $customerData['user_id']            = $userId;
            $customerData['referral_code']       = $referralCode;
            $customerData['registration_type']   = $customerData['registration_type'] ?? 'admin_registration';
            $customerData['customer_type']       = $customerData['customer_type']      ?? 'standard';
            $customerData['subscription_status'] = $customerData['subscription_status'] ?? 'none';
            $customerData['created_at']          = date('Y-m-d H:i:s');
            $customerData['updated_at']          = date('Y-m-d H:i:s');

            // Remove empty optional FK fields to avoid FK errors
            foreach (['profession_id', 'job_title_id', 'referred_by', 'card_id',
                      'created_by_admin_id', 'created_by_merchant_id'] as $nullable) {
                if (empty($customerData[$nullable])) {
                    unset($customerData[$nullable]);
                }
            }
            if (empty($customerData['date_of_birth'])) {
                unset($customerData['date_of_birth']);
            }

            $cols   = implode(', ', array_keys($customerData));
            $places = implode(', ', array_fill(0, count($customerData), '?'));
            $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
            $stmt->execute(array_values($customerData));
            $customerId = (int)$this->db->lastInsertId();

            $this->db->commit();
            return $customerId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    /**
     * Update customer + associated user in a transaction.
     */
    public function updateWithUser($customerId, $userData, $customerData) {
        $this->db->beginTransaction();
        try {
            $customer = $this->find($customerId);
            if (!$customer) throw new Exception('Customer not found.');

            // Update user
            if (!empty($userData)) {
                if (!empty($userData['password'])) {
                    $userData['password_hash'] = password_hash($userData['password'], PASSWORD_DEFAULT);
                }
                unset($userData['password']);
                $userData['updated_at'] = date('Y-m-d H:i:s');

                $sets  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($userData)));
                $vals  = array_values($userData);
                $vals[] = $customer['user_id'];
                $this->db->prepare("UPDATE users SET {$sets} WHERE id = ?")->execute($vals);
            }

            // Update customer
            if (!empty($customerData)) {
                // Nullify empty optional FK fields
                foreach (['profession_id', 'job_title_id', 'referred_by', 'card_id'] as $nullable) {
                    if (isset($customerData[$nullable]) && $customerData[$nullable] === '') {
                        $customerData[$nullable] = null;
                    }
                }
                if (isset($customerData['date_of_birth']) && $customerData['date_of_birth'] === '') {
                    $customerData['date_of_birth'] = null;
                }
                $customerData['updated_at'] = date('Y-m-d H:i:s');

                $sets  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($customerData)));
                $vals  = array_values($customerData);
                $vals[] = $customerId;
                $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE id = ?")->execute($vals);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    /**
     * Delete customer + user row (cascades via FK, but we also delete user explicitly).
     */
    public function deleteWithUser($customerId) {
        $customer = $this->find($customerId);
        if (!$customer) throw new Exception('Customer not found.');

        $this->db->beginTransaction();
        try {
            $this->delete($customerId);
            $this->db->prepare("DELETE FROM users WHERE id = ?")->execute([$customer['user_id']]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ─── STATUS / TYPE ────────────────────────────────────────────────────────

    /**
     * Toggle user status: active ↔ blocked.
     */
    public function toggleStatus($customerId) {
        $customer = $this->findWithDetails($customerId);
        if (!$customer) return 'Customer not found.';

        $newStatus = ($customer['status'] === 'active') ? 'blocked' : 'active';
        $this->db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?")
                 ->execute([$newStatus, $customer['user_id']]);
        return $newStatus;
    }

    // ─── VALIDATION HELPERS ───────────────────────────────────────────────────

    public function emailExists($email, $excludeUserId = null) {
        if ($excludeUserId) {
            $sql  = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $excludeUserId]);
        } else {
            $sql  = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    public function phoneExists($phone, $excludeUserId = null) {
        if (!$phone) return false;
        if ($excludeUserId) {
            $sql  = "SELECT COUNT(*) FROM users WHERE phone = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$phone, $excludeUserId]);
        } else {
            $sql  = "SELECT COUNT(*) FROM users WHERE phone = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$phone]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }

    // ─── PROFILE SUB-VIEW DATA ───────────────────────────────────────────────

    public function getGrievances($customerId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT g.*, m.business_name AS merchant_name
             FROM grievances g
             JOIN merchants m ON g.merchant_id = m.id
             WHERE g.customer_id = ?
             ORDER BY g.created_at DESC LIMIT ?"
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviews($customerId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT r.*, m.business_name AS merchant_name, s.store_name
             FROM reviews r
             JOIN merchants m ON r.merchant_id = m.id
             LEFT JOIN stores s ON r.store_id = s.id
             WHERE r.customer_id = ?
             ORDER BY r.created_at DESC LIMIT ?"
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReferrals($customerId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT r.*,
                    CONCAT(uc.name) AS referee_name,
                    uc_phone.phone AS referee_phone
             FROM referrals r
             JOIN customers uc ON r.referee_customer_id = uc.id
             JOIN users uc_phone ON uc.user_id = uc_phone.id
             WHERE r.referrer_customer_id = ?
             ORDER BY r.created_at DESC LIMIT ?"
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSavedCoupons($customerId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT cs.*, c.coupon_title, c.coupon_code, c.discount_type, c.discount_value,
                    m.business_name AS merchant_name
             FROM coupon_subscriptions cs
             JOIN coupons c ON cs.coupon_id = c.id
             JOIN merchants m ON c.merchant_id = m.id
             WHERE cs.customer_id = ?
             ORDER BY cs.subscribed_at DESC LIMIT ?"
        );
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFavouriteMerchants($customerId) {
        $stmt = $this->db->prepare(
            "SELECT cfm.*, m.business_name, m.profile_status, m.subscription_status
             FROM customer_favourite_merchants cfm
             JOIN merchants m ON cfm.merchant_id = m.id
             WHERE cfm.customer_id = ?
             ORDER BY cfm.saved_at DESC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getImportantDays($customerId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM customer_important_days
             WHERE customer_id = ?
             ORDER BY event_month ASC, event_day ASC"
        );
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
