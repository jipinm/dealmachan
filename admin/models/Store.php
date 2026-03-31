<?php
class Store extends Model {
    protected $table = 'stores';

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All stores for a merchant, joined with city + area names.
     */
    public function getByMerchant($merchantId) {
        $sql = "SELECT s.*, c.city_name, a.area_name, l.location_name
                FROM {$this->table} s
                JOIN cities c ON s.city_id = c.id
                LEFT JOIN areas  a ON s.area_id = a.id
                LEFT JOIN locations l ON s.location_id = l.id
                WHERE s.merchant_id = ?
                ORDER BY s.status DESC, s.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$merchantId]);
        return $stmt->fetchAll();
    }

    /**
     * Single store with joined names.
     */
    public function findWithDetails($id) {
        $sql = "SELECT s.*, c.city_name, a.area_name, l.location_name,
                       m.business_name
                FROM {$this->table} s
                JOIN cities c ON s.city_id = c.id
                JOIN areas  a ON s.area_id = a.id
                LEFT JOIN locations l ON s.location_id = l.id
                JOIN merchants m ON s.merchant_id = m.id
                WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Count of stores for a merchant.
     */
    public function countByMerchant($merchantId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE merchant_id = ?");
        $stmt->execute([$merchantId]);
        return (int)$stmt->fetchColumn();
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    /**
     * Insert a new store row.
     * Handles nullable fields and JSON opening_hours.
     */
    public function createStore($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');

        foreach (['location_id', 'phone', 'email', 'latitude', 'longitude',
                  'opening_hours', 'description'] as $nullable) {
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

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function updateStore($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');

        foreach (['location_id', 'phone', 'email', 'latitude', 'longitude',
                  'opening_hours', 'description'] as $nullable) {
            if (isset($data[$nullable]) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        $sets   = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        return $this->db->prepare("UPDATE {$this->table} SET {$sets} WHERE id = ?")->execute($values);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────

    public function deleteStore($id) {
        return $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?")->execute([$id]);
    }

    // ─── TOGGLE ───────────────────────────────────────────────────────────────

    public function toggleStatus($id) {
        $store = $this->find($id);
        if (!$store) return false;
        $newStatus = $store['status'] === 'active' ? 'inactive' : 'active';
        return $this->db->prepare("UPDATE {$this->table} SET status=?, updated_at=NOW() WHERE id=?")
                        ->execute([$newStatus, $id]);
    }

    // ─── GLOBAL LIST (all stores across all merchants) ────────────────────────

    public function getAllWithDetails(array $filters = []): array {
        $sql = "SELECT s.*,
                       c.city_name, a.area_name,
                       m.business_name AS merchant_name,
                       m.id            AS merchant_id
                FROM {$this->table} s
                JOIN cities c    ON s.city_id     = c.id
                LEFT JOIN areas  a ON s.area_id   = a.id
                JOIN merchants m ON s.merchant_id = m.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['merchant_id'])) {
            $sql .= " AND s.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['city_id'])) {
            $sql .= " AND s.city_id = ?";
            $params[] = (int)$filters['city_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (s.store_name LIKE ? OR m.business_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY s.status DESC, s.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countAll(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM {$this->table} s
                JOIN merchants m ON s.merchant_id = m.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['merchant_id'])) {
            $sql .= " AND s.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['city_id'])) {
            $sql .= " AND s.city_id = ?";
            $params[] = (int)$filters['city_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (s.store_name LIKE ? OR m.business_name LIKE ? OR s.phone LIKE ? OR s.email LIKE ?)";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getStats(): array {
        $sql = "SELECT
                    COUNT(*)                                                          AS total,
                    SUM(CASE WHEN s.status = 'active'   THEN 1 ELSE 0 END)          AS active,
                    SUM(CASE WHEN s.status = 'inactive' THEN 1 ELSE 0 END)          AS inactive,
                    SUM(CASE WHEN s.booking_enabled = 1 THEN 1 ELSE 0 END)          AS booking_enabled,
                    SUM(CASE WHEN DATE(s.created_at) = CURDATE() THEN 1 ELSE 0 END) AS today
                FROM {$this->table} s";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}
