<?php
class Label extends Model {
    protected $table = 'labels';

    /**
     * Get all labels with merchant usage count
     */
    public function getAllWithStats() {
        $sql = "SELECT l.*,
                    (SELECT COUNT(*) FROM merchant_labels ml WHERE ml.label_id = l.id) AS merchant_count
                FROM {$this->table} l
                ORDER BY l.priority_weight DESC, l.label_name ASC";
        return $this->query($sql);
    }

    /**
     * Get all active labels (for dropdowns/assignment)
     */
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY priority_weight DESC, label_name ASC";
        return $this->query($sql);
    }

    /**
     * Check if label name is unique
     */
    public function nameExists($name, $excludeId = null) {
        return $this->exists('label_name', $name, $excludeId);
    }

    /**
     * Save label (create or update)
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
        $label = $this->find($id);
        if (!$label) return false;
        $newStatus = $label['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if label can be deleted (no assigned merchants)
     */
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as cnt FROM merchant_labels WHERE label_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch()['cnt'] == 0;
    }
}
