<?php
class Grievance extends Model {
    protected $table = 'grievances';

    // ─── STATUS & PRIORITY ENUMS ──────────────────────────────────────────────

    public static $statuses  = ['open', 'in_progress', 'resolved', 'closed'];
    public static $priorities = ['low', 'medium', 'high', 'urgent'];

    // ─── FETCH ────────────────────────────────────────────────────────────────

    /**
     * All grievances with joined customer, merchant, store names.
     * Filters: status, priority, merchant_id, search (subject/customer name)
     */
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT g.*,
                       c.name            AS customer_name,
                       u.phone           AS customer_phone,
                       u.email           AS customer_email,
                       m.business_name   AS merchant_name,
                       s.store_name
                FROM {$this->table} g
                JOIN customers c  ON g.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON g.merchant_id   = m.id
                LEFT JOIN stores s ON g.store_id     = s.id
                WHERE 1=1";

        $params = [];
        $this->applyFilters($sql, $params, $filters);

        $sql .= " ORDER BY
                    CASE g.priority
                        WHEN 'urgent'    THEN 1
                        WHEN 'high'      THEN 2
                        WHEN 'medium'    THEN 3
                        WHEN 'low'       THEN 4
                        ELSE 5
                    END,
                    g.created_at DESC";

        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$filters['limit'];
            $params[] = (int)($filters['offset'] ?? 0);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Count matching grievances (same filters, no LIMIT). */
    public function countWithDetails($filters = []) {
        $sql = "SELECT COUNT(*)
                FROM {$this->table} g
                JOIN customers c  ON g.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON g.merchant_id   = m.id
                LEFT JOIN stores s ON g.store_id     = s.id
                WHERE 1=1";
        $params = [];
        $this->applyFilters($sql, $params, $filters);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /** Shared WHERE builder. */
    private function applyFilters(&$sql, &$params, $filters) {
        if (!empty($filters['status'])) {
            $sql .= " AND g.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND g.priority = ?";
            $params[] = $filters['priority'];
        }
        if (!empty($filters['merchant_id'])) {
            $sql .= " AND g.merchant_id = ?";
            $params[] = (int)$filters['merchant_id'];
        }
        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $sql .= " AND (g.subject LIKE ? OR c.name LIKE ? OR m.business_name LIKE ?)";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
    }

    /** Single grievance with full joined data. */
    public function findWithDetails($id) {
        $sql = "SELECT g.*,
                       c.name            AS customer_name,
                       c.id              AS customer_id,
                       u.phone           AS customer_phone,
                       u.email           AS customer_email,
                       m.business_name   AS merchant_name,
                       m.id              AS merchant_id,
                       s.store_name
                FROM {$this->table} g
                JOIN customers c  ON g.customer_id  = c.id
                JOIN users u      ON c.user_id       = u.id
                JOIN merchants m  ON g.merchant_id   = m.id
                LEFT JOIN stores s ON g.store_id     = s.id
                WHERE g.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /** Aggregate stats for the listing header. */
    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                                  AS total,
                    SUM(CASE WHEN g.status = 'open'        THEN 1 ELSE 0 END)               AS open,
                    SUM(CASE WHEN g.status = 'in_progress' THEN 1 ELSE 0 END)               AS in_progress,
                    SUM(CASE WHEN g.status = 'resolved'    THEN 1 ELSE 0 END)               AS resolved,
                    SUM(CASE WHEN g.status = 'closed'      THEN 1 ELSE 0 END)               AS closed,
                    SUM(CASE WHEN g.priority IN ('high','urgent') AND g.status NOT IN ('resolved','closed') THEN 1 ELSE 0 END) AS high_priority_open,
                    SUM(CASE WHEN DATE(g.created_at) = CURDATE() THEN 1 ELSE 0 END)         AS today
                FROM {$this->table} g";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    // ─── UPDATE STATUS ────────────────────────────────────────────────────────

    /**
     * Change status; auto-set resolved_at when marking resolved/closed.
     */
    public function updateStatus($id, $status, $resolutionNotes = null) {
        $sql    = "UPDATE {$this->table}
                   SET status = ?,
                       resolution_notes = COALESCE(?, resolution_notes),
                       resolved_at      = CASE
                                              WHEN ? IN ('resolved','closed') AND resolved_at IS NULL THEN NOW()
                                              ELSE resolved_at
                                          END
                   WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $resolutionNotes, $status, $id]);
    }

    // ─── UPDATE PRIORITY ──────────────────────────────────────────────────────

    public function updatePriority($id, $priority) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET priority = ? WHERE id = ?"
        );
        return $stmt->execute([$priority, $id]);
    }

    // ─── UPDATE RESOLUTION NOTES ──────────────────────────────────────────────

    public function addNote($id, $notes) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET resolution_notes = ? WHERE id = ?"
        );
        return $stmt->execute([$notes, $id]);
    }

    // ─── MERCHANT LIST (for filter dropdown) ─────────────────────────────────

    public function getMerchantsWithGrievances() {
        $sql = "SELECT DISTINCT m.id, m.business_name
                FROM merchants m
                JOIN {$this->table} g ON g.merchant_id = m.id
                ORDER BY m.business_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
