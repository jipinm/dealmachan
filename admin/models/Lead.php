<?php
class Lead extends Model {

    protected $table = 'merchant_leads';

    public function getAll(string $status = '', int $page = 1, int $perPage = 30): array {
        $where  = $status ? "WHERE ml.status = ?" : "";
        $params = $status ? [$status] : [];
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT ml.*,
                       a.name AS assigned_admin_name
                FROM merchant_leads ml
                LEFT JOIN admins a ON ml.assigned_to_admin_id = a.id
                $where
                ORDER BY ml.created_at DESC
                LIMIT $perPage OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStatus($status = ''): int {
        $where  = $status ? "WHERE status = ?" : "";
        $params = $status ? [$status] : [];
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM merchant_leads $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT ml.*, a.name AS assigned_admin_name
             FROM merchant_leads ml
             LEFT JOIN admins a ON ml.assigned_to_admin_id = a.id
             WHERE ml.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function updateStatus(int $id, string $status, string $notes = ''): bool {
        $stmt = $this->db->prepare(
            "UPDATE merchant_leads SET status = ?, notes = CONCAT(IFNULL(notes,''), ?) WHERE id = ?"
        );
        $noteAppend = $notes ? "\n[" . date('Y-m-d H:i') . "] $notes" : '';
        return $stmt->execute([$status, $noteAppend, $id]);
    }

    public function assign(int $id, int $adminId): bool {
        $stmt = $this->db->prepare("UPDATE merchant_leads SET assigned_to_admin_id = ? WHERE id = ?");
        return $stmt->execute([$adminId, $id]);
    }

    public function getStatusCounts(): array {
        $rows = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM merchant_leads GROUP BY status"
        )->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) { $map[$r['status']] = (int)$r['cnt']; }
        return $map;
    }
}
