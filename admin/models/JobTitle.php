<?php
class JobTitle extends Model {
    protected $table = 'job_titles';

    /**
     * Get all job titles with profession name and customer usage count
     */
    public function getAllWithStats() {
        $sql = "SELECT jt.*,
                    p.profession_name,
                    (SELECT COUNT(*) FROM customers c WHERE c.job_title_id = jt.id) AS customer_count
                FROM {$this->table} jt
                JOIN professions p ON jt.profession_id = p.id
                ORDER BY p.profession_name ASC, jt.job_title_name ASC";
        return $this->query($sql);
    }

    /**
     * Check if a job title name already exists within the same profession
     */
    public function nameExists($name, $professionId, $excludeId = null) {
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE job_title_name = ? AND profession_id = ?";
        $params = [$name, $professionId];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Toggle active / inactive
     */
    public function toggleStatus($id) {
        $rec = $this->find($id);
        if (!$rec) return false;
        $new = $rec['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $new]);
    }

    /**
     * Check if job title can be safely deleted (no customers linked)
     */
    public function canDelete($id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM customers WHERE job_title_id = ?");
        $stmt->execute([$id]);
        return (int)$stmt->fetchColumn() === 0;
    }
}
