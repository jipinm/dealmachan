<?php
class ContactEnquiry extends Model {

    protected $table = 'contact_enquiries';

    private function ensureTable(): void {
        $sql = file_get_contents(ROOT_PATH . '/migrations/create_contact_enquiries.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt) { try { $this->db->exec($stmt); } catch (Exception $e) {} }
        }
    }

    public function getAll(string $status = '', int $page = 1, int $perPage = 30): array {
        $this->ensureTable();
        $where  = $status ? "WHERE status = ?" : "";
        $params = $status ? [$status] : [];
        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT * FROM contact_enquiries $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
        $stmt   = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countByStatus($status = ''): int {
        $this->ensureTable();
        $where  = $status ? "WHERE status = ?" : "";
        $params = $status ? [$status] : [];
        $stmt   = $this->db->prepare("SELECT COUNT(*) FROM contact_enquiries $where");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM contact_enquiries WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function markRead(int $id): bool {
        $stmt = $this->db->prepare("UPDATE contact_enquiries SET status = 'read' WHERE id = ? AND status = 'new'");
        return $stmt->execute([$id]);
    }

    public function updateStatus(int $id, string $status, string $notes = ''): bool {
        $stmt = $this->db->prepare("UPDATE contact_enquiries SET status = ?, admin_notes = ? WHERE id = ?");
        return $stmt->execute([$status, $notes, $id]);
    }

    public function getStatusCounts(): array {
        $rows = $this->db->query(
            "SELECT status, COUNT(*) AS cnt FROM contact_enquiries GROUP BY status"
        )->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) { $map[$r['status']] = (int)$r['cnt']; }
        return $map;
    }
}
