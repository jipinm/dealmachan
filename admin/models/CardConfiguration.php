<?php
class CardConfiguration extends Model {
    protected $table = 'card_configurations';

    // ─── LIST ─────────────────────────────────────────────────────────────────

    public function getAll(array $filters = []): array {
        $sql    = "SELECT cc.*, a.name AS created_by_name,
                          (SELECT COUNT(*) FROM cards c WHERE c.card_configuration_id = cc.id) AS cards_count
                   FROM {$this->table} cc
                   LEFT JOIN admins a ON a.id = cc.created_by_admin_id
                   WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND cc.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['classification'])) {
            $sql .= " AND cc.classification = ?";
            $params[] = $filters['classification'];
        }

        $sql .= " ORDER BY cc.classification, cc.name";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countAll(array $filters = []): int {
        $sql    = "SELECT COUNT(*) FROM {$this->table} cc WHERE 1=1";
        $params = [];
        if (!empty($filters['status'])) { $sql .= " AND cc.status = ?"; $params[] = $filters['status']; }
        if (!empty($filters['classification'])) { $sql .= " AND cc.classification = ?"; $params[] = $filters['classification']; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ─── SINGLE ───────────────────────────────────────────────────────────────

    public function findWithDetails(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT cc.*, a.name AS created_by_name
             FROM {$this->table} cc
             LEFT JOIN admins a ON a.id = cc.created_by_admin_id
             WHERE cc.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $row['sub_classifications'] = $this->getSubClassifications($id);
        $row['cities']             = $this->getCities($id);
        $row['partners']           = $this->getPartners($id);
        return $row;
    }

    // ─── SUB-CLASSIFICATIONS ──────────────────────────────────────────────────

    public function getSubClassifications(int $configId): array {
        $stmt = $this->db->prepare(
            "SELECT sc.id, sc.name
             FROM card_config_sub_class_map m
             JOIN card_sub_classifications sc ON sc.id = m.sub_class_id
             WHERE m.config_id = ?"
        );
        $stmt->execute([$configId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllSubClassifications(): array {
        return $this->db->query("SELECT id, name FROM card_sub_classifications ORDER BY name")
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── CITIES ───────────────────────────────────────────────────────────────

    public function getCities(int $configId): array {
        $stmt = $this->db->prepare(
            "SELECT ci.id, ci.city_name
             FROM card_config_cities cc
             JOIN cities ci ON ci.id = cc.city_id
             WHERE cc.config_id = ?
             ORDER BY ci.city_name"
        );
        $stmt->execute([$configId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── PARTNERS ─────────────────────────────────────────────────────────────

    public function getPartners(int $configId): array {
        $stmt = $this->db->prepare(
            "SELECT id, partner_type, partner_image, url, sort_order
             FROM card_config_partners
             WHERE config_id = ?
             ORDER BY partner_type DESC, sort_order ASC"
        );
        $stmt->execute([$configId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─── STATS ────────────────────────────────────────────────────────────────

    public function getStats(): array {
        $row = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'active') AS active,
                SUM(classification = 'silver')   AS silver,
                SUM(classification = 'gold')     AS gold,
                SUM(classification = 'platinum') AS platinum,
                SUM(classification = 'diamond')  AS diamond
             FROM {$this->table}"
        )->fetch(PDO::FETCH_ASSOC);
        return $row ?: [];
    }

    // ─── LINKED CHECK ─────────────────────────────────────────────────────────

    public function isLinkedToCards(int $configId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cards WHERE card_configuration_id = ?");
        $stmt->execute([$configId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // ─── CREATE ───────────────────────────────────────────────────────────────

    public function create($data): int {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table}
                (name, classification, features_html, price, monthly_maximum, max_live_coupons,
                 coupon_authorization, card_image_front, card_image_back, is_publicly_selectable,
                 validity_days, status, created_by_admin_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['classification'],
            $data['features_html']          ?? null,
            $data['price']                  ?? 0,
            $data['monthly_maximum']        ?: null,
            $data['max_live_coupons']       ?: null,
            $data['coupon_authorization']   ?? 1,
            $data['card_image_front']       ?? null,
            $data['card_image_back']        ?? null,
            $data['is_publicly_selectable'] ?? 0,
            $data['validity_days']          ?? 360,
            $data['status']                 ?? 'active',
            $data['created_by_admin_id']    ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    // ─── UPDATE ───────────────────────────────────────────────────────────────

    public function update($id, $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET
                name = ?, classification = ?, features_html = ?, price = ?,
                monthly_maximum = ?, max_live_coupons = ?, coupon_authorization = ?,
                is_publicly_selectable = ?, validity_days = ?, status = ?,
                card_image_front = COALESCE(?, card_image_front),
                card_image_back  = COALESCE(?, card_image_back)
             WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'],
            $data['classification'],
            $data['features_html']          ?? null,
            $data['price']                  ?? 0,
            $data['monthly_maximum']        ?: null,
            $data['max_live_coupons']       ?: null,
            $data['coupon_authorization']   ?? 1,
            $data['is_publicly_selectable'] ?? 0,
            $data['validity_days']          ?? 360,
            $data['status']                 ?? 'active',
            $data['card_image_front']       ?? null,
            $data['card_image_back']        ?? null,
            $id,
        ]);
    }

    // ─── SYNC HELPERS ─────────────────────────────────────────────────────────

    public function syncSubClasses(int $configId, array $subClassIds): void {
        $this->db->prepare("DELETE FROM card_config_sub_class_map WHERE config_id = ?")->execute([$configId]);
        if (empty($subClassIds)) return;
        $ins = $this->db->prepare("INSERT IGNORE INTO card_config_sub_class_map (config_id, sub_class_id) VALUES (?, ?)");
        foreach ($subClassIds as $scId) {
            $ins->execute([$configId, (int)$scId]);
        }
    }

    public function syncCities(int $configId, array $cityIds): void {
        $this->db->prepare("DELETE FROM card_config_cities WHERE config_id = ?")->execute([$configId]);
        if (empty($cityIds)) return;
        $ins = $this->db->prepare("INSERT IGNORE INTO card_config_cities (config_id, city_id) VALUES (?, ?)");
        foreach ($cityIds as $cId) {
            $ins->execute([$configId, (int)$cId]);
        }
    }

    public function syncPartners(int $configId, array $partners): void {
        $this->db->prepare("DELETE FROM card_config_partners WHERE config_id = ?")->execute([$configId]);
        if (empty($partners)) return;
        $ins = $this->db->prepare(
            "INSERT INTO card_config_partners (config_id, partner_type, partner_image, url, sort_order) VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($partners as $i => $p) {
            $ins->execute([$configId, $p['type'], $p['image'] ?? null, $p['url'] ?? null, $i]);
        }
    }
}
