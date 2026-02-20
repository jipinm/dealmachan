<?php
class City extends Model {
    protected $table = 'cities';

    /**
     * Get all cities with optional status filter
     */
    public function getAll($status = null) {
        if ($status) {
            $sql = "SELECT * FROM {$this->table} WHERE status = :status ORDER BY city_name ASC";
            return $this->query($sql, ['status' => $status]);
        }
        return $this->all('city_name ASC');
    }

    /**
     * Get all active cities (for dropdowns)
     */
    public function getActive() {
        return $this->getAll('active');
    }

    /**
     * Get city with counts of linked areas, merchants, customers
     */
    public function getCitiesWithStats() {
        $sql = "SELECT c.*,
                    (SELECT COUNT(*) FROM areas a WHERE a.city_id = c.id) AS area_count,
                    (SELECT COUNT(*) FROM merchants m JOIN stores s ON s.merchant_id = m.id WHERE s.city_id = c.id) AS merchant_count,
                    (SELECT COUNT(*) FROM admins ad WHERE ad.city_id = c.id) AS admin_count
                FROM {$this->table} c
                ORDER BY c.city_name ASC";
        return $this->query($sql);
    }

    /**
     * Check if city name is unique
     */
    public function nameExists($name, $excludeId = null) {
        return $this->exists('city_name', $name, $excludeId);
    }

    /**
     * Save city (create or update)
     */
    public function save($data, $id = null) {
        if ($id) {
            return $this->update($id, $data);
        }
        return $this->insert($data);
    }

    /**
     * Toggle status active <-> inactive
     */
    public function toggleStatus($id) {
        $city = $this->find($id);
        if (!$city) return false;
        $newStatus = $city['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if city can be deleted (no dependent areas)
     */
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as cnt FROM areas WHERE city_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch()['cnt'] == 0;
    }
}
