<?php
class Area extends Model {
    protected $table = 'areas';

    /**
     * Get all areas with their city name
     */
    public function getAllWithCity($cityId = null) {
        $sql = "SELECT a.*, c.city_name
                FROM {$this->table} a
                LEFT JOIN cities c ON a.city_id = c.id";
        $params = [];
        if ($cityId) {
            $sql .= " WHERE a.city_id = :city_id";
            $params['city_id'] = $cityId;
        }
        $sql .= " ORDER BY c.city_name ASC, a.area_name ASC";
        return $this->query($sql, $params);
    }

    /**
     * Get areas with location counts
     */
    public function getAreasWithStats($cityId = null) {
        $sql = "SELECT a.*, c.city_name,
                    (SELECT COUNT(*) FROM locations l WHERE l.area_id = a.id) AS location_count
                FROM {$this->table} a
                LEFT JOIN cities c ON a.city_id = c.id";
        $params = [];
        if ($cityId) {
            $sql .= " WHERE a.city_id = :city_id";
            $params['city_id'] = $cityId;
        }
        $sql .= " ORDER BY c.city_name ASC, a.area_name ASC";
        return $this->query($sql, $params);
    }

    /**
     * Get active areas by city (for dropdowns)
     */
    public function getByCity($cityId) {
        $sql = "SELECT * FROM {$this->table} WHERE city_id = :city_id AND status = 'active' ORDER BY area_name ASC";
        return $this->query($sql, ['city_id' => $cityId]);
    }

    /**
     * Check name uniqueness within a city
     */
    public function nameExistsInCity($name, $cityId, $excludeId = null) {
        $sql = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE area_name = :name AND city_id = :city_id";
        $params = ['name' => $name, 'city_id' => $cityId];
        if ($excludeId) {
            $sql .= " AND id != :excludeId";
            $params['excludeId'] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['cnt'] > 0;
    }

    /**
     * Save area (create or update)
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
        $area = $this->find($id);
        if (!$area) return false;
        $newStatus = $area['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if area can be deleted (no dependent locations or stores)
     */
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as cnt FROM locations WHERE area_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch()['cnt'] == 0;
    }
}
