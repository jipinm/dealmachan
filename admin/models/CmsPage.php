<?php
class CmsPage extends Model {

    protected $table = 'cms_pages';

    private function ensureTable(): void {
        $sql = file_get_contents(ROOT_PATH . '/migrations/create_cms_pages.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
            if ($stmt) { try { $this->db->exec($stmt); } catch (Exception $e) {} }
        }
    }

    public function getAll(string $status = ''): array {
        $this->ensureTable();
        $sql    = "SELECT * FROM cms_pages";
        $params = [];
        if ($status) { $sql .= " WHERE status = ?"; $params[] = $status; }
        $sql .= " ORDER BY slug ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findBySlug(string $slug): ?array {
        $this->ensureTable();
        $stmt = $this->db->prepare("SELECT * FROM cms_pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create($data): int|false {
        $this->ensureTable();
        $stmt = $this->db->prepare(
            "INSERT INTO cms_pages (slug, title, content, meta_description, status, created_by_admin_id)
             VALUES (:slug, :title, :content, :meta, :status, :admin_id)"
        );
        $ok = $stmt->execute([
            ':slug'     => $data['slug'],
            ':title'    => $data['title'],
            ':content'  => $data['content'] ?? '',
            ':meta'     => $data['meta_description'] ?? '',
            ':status'   => $data['status'] ?? 'draft',
            ':admin_id' => $data['created_by_admin_id'] ?? null,
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }

    public function update($id, $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE cms_pages SET slug=:slug, title=:title, content=:content,
             meta_description=:meta, status=:status WHERE id=:id"
        );
        return $stmt->execute([
            ':slug'    => $data['slug'],
            ':title'   => $data['title'],
            ':content' => $data['content'] ?? '',
            ':meta'    => $data['meta_description'] ?? '',
            ':status'  => $data['status'] ?? 'draft',
            ':id'      => $id,
        ]);
    }

    public function delete($id): bool {
        $stmt = $this->db->prepare("DELETE FROM cms_pages WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
