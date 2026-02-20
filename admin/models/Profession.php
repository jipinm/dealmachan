<?php
class Profession extends Model {
    protected $table = 'professions';

    /**
     * Get all professions with customer usage count
     */
    public function getAllWithStats() {
        $sql = "SELECT p.*,
                    (SELECT COUNT(*) FROM customers c WHERE c.profession_id = p.id) AS customer_count
                FROM {$this->table} p
                ORDER BY p.profession_name ASC";
        return $this->query($sql);
    }

    /**
     * Get all active professions (for dropdowns)
     */
    public function getActive() {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY profession_name ASC";
        return $this->query($sql);
    }

    /**
     * Check if profession name is unique
     */
    public function nameExists($name, $excludeId = null) {
        return $this->exists('profession_name', $name, $excludeId);
    }

    /**
     * Save profession
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
        $rec = $this->find($id);
        if (!$rec) return false;
        $newStatus = $rec['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if profession can be deleted
     */
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as cnt FROM customers WHERE profession_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch()['cnt'] == 0;
    }
}
