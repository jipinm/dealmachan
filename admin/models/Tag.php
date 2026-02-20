<?php
class Tag extends Model {
    protected $table = 'tags';

    /**
     * Get all tags with parent info and counts
     */
    public function getAllWithDetails() {
        $sql = "SELECT t.*,
                    pt.tag_name AS parent_name,
                    (SELECT COUNT(*) FROM tags child WHERE child.parent_tag_id = t.id) AS child_count,
                    (SELECT COUNT(*) FROM merchant_tags mt WHERE mt.tag_id = t.id) AS merchant_count,
                    (SELECT COUNT(*) FROM coupon_tags ct WHERE ct.tag_id = t.id) AS coupon_count
                FROM {$this->table} t
                LEFT JOIN {$this->table} pt ON t.parent_tag_id = pt.id
                ORDER BY t.tag_category ASC, pt.tag_name ASC, t.tag_name ASC";
        return $this->query($sql);
    }

    /**
     * Get parent tags (categories) for dropdown
     */
    public function getParentTags() {
        $sql = "SELECT * FROM {$this->table} WHERE parent_tag_id IS NULL AND status = 'active' ORDER BY tag_name ASC";
        return $this->query($sql);
    }

    /**
     * Get tags by category (for dropdowns)
     */
    public function getByCategory($category, $activeOnly = true) {
        $sql = "SELECT * FROM {$this->table} WHERE tag_category = :cat";
        $params = ['cat' => $category];
        if ($activeOnly) {
            $sql .= " AND status = 'active'";
        }
        $sql .= " ORDER BY tag_name ASC";
        return $this->query($sql, $params);
    }

    /**
     * Save tag (create or update)
     */
    public function save($data, $id = null) {
        if ($id) {
            return $this->update($id, $data);
        }
        return $this->insert($data);
    }

    /**
     * Toggle status
     */
    public function toggleStatus($id) {
        $tag = $this->find($id);
        if (!$tag) return false;
        $newStatus = $tag['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if tag can be deleted (no children, no merchant/coupon links)
     */
    public function canDelete($id) {
        $checks = [
            "SELECT COUNT(*) as cnt FROM tags WHERE parent_tag_id = :id",
            "SELECT COUNT(*) as cnt FROM merchant_tags WHERE tag_id = :id",
            "SELECT COUNT(*) as cnt FROM coupon_tags WHERE tag_id = :id",
        ];
        foreach ($checks as $sql) {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            if ($stmt->fetch()['cnt'] > 0) return false;
        }
        return true;
    }
}
