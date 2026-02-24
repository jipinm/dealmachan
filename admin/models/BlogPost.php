<?php
require_once CORE_PATH . '/Model.php';

class BlogPost extends Model {
    protected $table = 'blog_posts';

    // ─── LIST ────────────────────────────────────────────────────────────────

    public function getAllWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $limit  = max(1, (int)($filters['limit']  ?? 20));
        $offset = max(0, (int)($filters['offset'] ?? 0));

        $sql = "SELECT bp.id, bp.title, bp.slug, bp.featured_image, bp.status, bp.published_at, bp.created_at,
                       LEFT(bp.content, 200) AS excerpt,
                       adm.name AS author_name
                FROM {$this->table} bp
                JOIN admins adm ON bp.author_id = adm.id
                {$where}
                ORDER BY CASE bp.status WHEN 'draft' THEN 0 WHEN 'published' THEN 1 ELSE 2 END,
                         bp.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countWithDetails($filters = []) {
        [$where, $params] = $this->_buildWhere($filters);
        $sql = "SELECT COUNT(*) FROM {$this->table} bp
                JOIN admins adm ON bp.author_id = adm.id
                {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function _buildWhere($filters) {
        $conds  = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conds[]  = "bp.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['author_id'])) {
            $conds[]  = "bp.author_id = ?";
            $params[] = (int)$filters['author_id'];
        }
        if (!empty($filters['search'])) {
            $conds[]  = "(bp.title LIKE ? OR bp.slug LIKE ?)";
            $like     = '%' . $filters['search'] . '%';
            array_push($params, $like, $like);
        }

        $where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';
        return [$where, $params];
    }

    // ─── DETAIL ──────────────────────────────────────────────────────────────

    public function findWithDetails($id) {
        $sql = "SELECT bp.*, adm.name AS author_name
                FROM {$this->table} bp
                JOIN admins adm ON bp.author_id = adm.id
                WHERE bp.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ─── STATS ───────────────────────────────────────────────────────────────

    public function getStats() {
        $sql = "SELECT
                    COUNT(*)                                                                AS total,
                    SUM(CASE WHEN bp.status = 'draft'     THEN 1 ELSE 0 END)               AS draft,
                    SUM(CASE WHEN bp.status = 'published' THEN 1 ELSE 0 END)               AS published,
                    SUM(CASE WHEN bp.status = 'archived'  THEN 1 ELSE 0 END)               AS archived,
                    SUM(CASE WHEN DATE(bp.created_at) = CURDATE()             THEN 1 ELSE 0 END) AS today
                FROM {$this->table} bp";
        return $this->db->query($sql)->fetch();
    }

    // ─── SLUG ────────────────────────────────────────────────────────────────

    public function generateSlug($title, $excludeId = null) {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $title), '-'));
        $slug = $base;
        $i    = 1;
        do {
            $sql = "SELECT id FROM {$this->table} WHERE slug = ?" . ($excludeId ? " AND id != {$excludeId}" : '');
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $exists = $stmt->fetch();
            if ($exists) { $slug = $base . '-' . $i++; }
        } while ($exists);
        return $slug;
    }

    // ─── WRITE ───────────────────────────────────────────────────────────────

    public function createPost($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $cols   = implode(', ', array_keys($data));
        $places = ':' . implode(', :', array_keys($data));
        $stmt   = $this->db->prepare("INSERT INTO {$this->table} ({$cols}) VALUES ({$places})");
        return $stmt->execute($data) ? $this->db->lastInsertId() : false;
    }

    public function updatePost($id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $set  = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $vals = array_values($data);
        $vals[] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");
        return $stmt->execute($vals);
    }

    public function publish($id) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = 'published', published_at = IFNULL(published_at, NOW()), updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$id]);
    }

    public function setStatus($id, $status) {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$status, $id]);
    }

    public function deletePost($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
