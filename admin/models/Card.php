<?php
class Card extends Model {
    protected $table = 'cards';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * Paginated list with assigned entity names.
     * Filters: status, card_variant, is_preprinted, assigned_to, search
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT c.*,
                       cust.name       AS customer_name,
                       cu.phone        AS customer_phone,
                       m.business_name AS merchant_name,
                       a.name          AS admin_name
                FROM {$this->table} c
                LEFT JOIN customers  cust ON c.assigned_to_customer_id = cust.id
                LEFT JOIN users      cu   ON cust.user_id = cu.id
                LEFT JOIN merchants  m    ON c.assigned_to_merchant_id  = m.id
                LEFT JOIN admins     a    ON c.assigned_to_admin_id     = a.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $sql .= " ORDER BY c.generated_at DESC";
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
        $sql = "SELECT COUNT(*) FROM {$this->table} c
                LEFT JOIN customers  cust ON c.assigned_to_customer_id = cust.id
                LEFT JOIN users      cu   ON cust.user_id = cu.id
                LEFT JOIN merchants  m    ON c.assigned_to_merchant_id  = m.id
                LEFT JOIN admins     a    ON c.assigned_to_admin_id     = a.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['card_variant'])) {
            $sql .= " AND c.card_variant = ?";
            $params[] = $filters['card_variant'];
        }
        if (isset($filters['is_preprinted']) && $filters['is_preprinted'] !== '') {
            $sql .= " AND c.is_preprinted = ?";
            $params[] = (int)$filters['is_preprinted'];
        }
        if (!empty($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'customer') {
                $sql .= " AND c.assigned_to_customer_id IS NOT NULL";
            } elseif ($filters['assigned_to'] === 'merchant') {
                $sql .= " AND c.assigned_to_merchant_id IS NOT NULL";
            } elseif ($filters['assigned_to'] === 'unassigned') {
                $sql .= " AND c.assigned_to_customer_id IS NULL AND c.assigned_to_merchant_id IS NULL AND c.assigned_to_admin_id IS NULL";
            }
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (c.card_number LIKE ? OR cust.name LIKE ? OR cu.phone LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }
    }

    /** Single card with all joined names. */
    public function findWithDetails($id) {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    cust.name        AS customer_name,
                    cu.phone         AS customer_phone,
                    cu.email         AS customer_email,
                    m.business_name  AS merchant_name,
                    a.name           AS admin_name
             FROM {$this->table} c
             LEFT JOIN customers  cust ON c.assigned_to_customer_id = cust.id
             LEFT JOIN users      cu   ON cust.user_id = cu.id
             LEFT JOIN merchants  m    ON c.assigned_to_merchant_id  = m.id
             LEFT JOIN admins     a    ON c.assigned_to_admin_id     = a.id
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /** Find by card number. */
    public function findByNumber($number) {
        $stmt = $this->db->prepare(
            "SELECT c.*,
                    cust.name        AS customer_name,
                    cu.phone         AS customer_phone,
                    m.business_name  AS merchant_name
             FROM {$this->table} c
             LEFT JOIN customers  cust ON c.assigned_to_customer_id = cust.id
             LEFT JOIN users      cu   ON cust.user_id = cu.id
             LEFT JOIN merchants  m    ON c.assigned_to_merchant_id  = m.id
             WHERE c.card_number = ?"
        );
        $stmt->execute([strtoupper($number)]);
        return $stmt->fetch() ?: null;
    }

    /** Dashboard stats. */
    public function getStats() {
        $row = $this->db->query(
            "SELECT
               COUNT(*)                                     AS total,
               SUM(status = 'available')                    AS available,
               SUM(status = 'assigned')                     AS assigned,
               SUM(status = 'activated')                    AS activated,
               SUM(status = 'blocked')                      AS blocked,
               SUM(is_preprinted = 1)                       AS preprinted,
               SUM(DATE(generated_at) = CURDATE())          AS today,
               SUM(assigned_to_customer_id IS NOT NULL)     AS assigned_customers,
               SUM(assigned_to_merchant_id IS NOT NULL)     AS assigned_merchants
             FROM {$this->table}"
        )->fetch();
        return $row;
    }

    /** Distinct variants present in the table + config defaults. */
    public function getVariants() {
        $stmt = $this->db->query(
            "SELECT DISTINCT card_variant FROM {$this->table} ORDER BY card_variant"
        );
        $db = array_column($stmt->fetchAll(\PDO::FETCH_NUM), 0);
        $defaults = ['standard', 'premium', 'gold', 'corporate', 'student'];
        return array_unique(array_merge($defaults, $db));
    }

    /** Available cards for assignment dropdown. */
    public function getAvailable($limit = 100) {
        $stmt = $this->db->prepare(
            "SELECT id, card_number, card_variant FROM {$this->table}
             WHERE status = 'available'
             ORDER BY generated_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ─── GENERATE ─────────────────────────────────────────────────────────────

    /**
     * Generate one or more cards.
     * $defaults: card_variant, is_preprinted, card_number (for single)
     * $count:    1 = individual (use provided card_number), >1 = bulk (auto-generate)
     * Returns count of cards actually inserted.
     */
    public function generateCards(array $defaults, int $count = 1): int {
        $inserted = 0;
        $variant  = strtolower($defaults['card_variant'] ?? 'standard');
        $prefix   = strtoupper(substr($variant, 0, 3));
        $preprint = (int)($defaults['is_preprinted'] ?? 0);
        $params   = $defaults['parameters_json'] ?? null;

        for ($i = 0; $i < $count; $i++) {
            if ($count === 1 && !empty($defaults['card_number'])) {
                $num = strtoupper(trim($defaults['card_number']));
            } else {
                $num = $this->generateUniqueNumber($prefix);
            }

            try {
                $stmt = $this->db->prepare(
                    "INSERT INTO {$this->table}
                     (card_number, card_variant, is_preprinted, parameters_json, status, generated_at, created_at)
                     VALUES (?, ?, ?, ?, 'available', NOW(), NOW())"
                );
                $stmt->execute([$num, $variant, $preprint, $params]);
                $inserted++;
            } catch (\PDOException $e) {
                if (str_contains($e->getMessage(), '1062')) continue; // duplicate, skip
                throw $e;
            }
        }
        return $inserted;
    }

    private function generateUniqueNumber(string $prefix): string {
        do {
            $num = 'DM' . $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while ($this->cardNumberExists($num));
        return $num;
    }

    public function cardNumberExists(string $number, ?int $excludeId = null): bool {
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE card_number = ?";
        $params = [strtoupper($number)];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        return $this->dbFetchCount($sql, $params) > 0;
    }

    // cleaner version used internally
    private function dbFetchCount(string $sql, array $params): int {
        $s = $this->db->prepare($sql);
        $s->execute($params);
        return (int)$s->fetchColumn();
    }

    // ─── ASSIGNMENT ───────────────────────────────────────────────────────────

    /** Assign a card to a customer and mark as 'assigned'. */
    public function assignToCustomer(int $cardId, int $customerId): void {
        $this->db->prepare(
            "UPDATE {$this->table}
             SET assigned_to_customer_id = ?,
                 assigned_to_merchant_id = NULL,
                 assigned_to_admin_id    = NULL,
                 status = 'assigned',
                 updated_at = NOW()
             WHERE id = ?"
        )->execute([$customerId, $cardId]);
    }

    /** Assign a card to a merchant. */
    public function assignToMerchant(int $cardId, int $merchantId): void {
        $this->db->prepare(
            "UPDATE {$this->table}
             SET assigned_to_merchant_id  = ?,
                 assigned_to_customer_id  = NULL,
                 assigned_to_admin_id     = NULL,
                 status = 'assigned',
                 updated_at = NOW()
             WHERE id = ?"
        )->execute([$merchantId, $cardId]);
    }

    // ─── STATUS ───────────────────────────────────────────────────────────────

    /** Activate a card (assigned → activated). */
    public function activate(int $id): void {
        $this->db->prepare(
            "UPDATE {$this->table} SET status = 'activated', activated_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    /** Toggle block/unblock. */
    public function toggleBlock(int $id): void {
        $this->db->prepare(
            "UPDATE {$this->table}
             SET status = IF(status = 'blocked', IF(activated_at IS NULL, IF(assigned_to_customer_id IS NULL AND assigned_to_merchant_id IS NULL, 'available', 'assigned'), 'activated'), 'blocked'),
                 updated_at = NOW()
             WHERE id = ?"
        )->execute([$id]);
    }

    /** Delete an available card. Returns false if not available. */
    public function deleteCard(int $id): bool {
        $stmt = $this->db->prepare("SELECT status FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $card = $stmt->fetch();
        if (!$card || $card['status'] !== 'available') return false;
        $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
        return true;
    }

    /** Unassign and reset a card back to available. */
    public function unassign(int $id): void {
        $this->db->prepare(
            "UPDATE {$this->table}
             SET assigned_to_customer_id = NULL,
                 assigned_to_merchant_id = NULL,
                 assigned_to_admin_id    = NULL,
                 status      = 'available',
                 activated_at = NULL,
                 updated_at  = NOW()
             WHERE id = ?"
        )->execute([$id]);
    }
}
