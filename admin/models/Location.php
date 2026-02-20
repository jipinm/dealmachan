<?php
class Location extends Model {
    protected $table = 'locations';

    /**
     * Get all locations with area and city names
     */
    public function getAllWithHierarchy($cityId = null, $areaId = null) {
        $sql = "SELECT l.*, a.area_name, c.city_name, c.id AS city_id
                FROM {$this->table} l
                LEFT JOIN areas a ON l.area_id = a.id
                LEFT JOIN cities c ON a.city_id = c.id
                WHERE 1=1";
        $params = [];
        if ($cityId) {
            $sql .= " AND c.id = :city_id";
            $params['city_id'] = $cityId;
        }
        if ($areaId) {
            $sql .= " AND l.area_id = :area_id";
            $params['area_id'] = $areaId;
        }
        $sql .= " ORDER BY c.city_name ASC, a.area_name ASC, l.location_name ASC";
        return $this->query($sql, $params);
    }

    /**
     * Get active locations by area (for dropdowns)
     */
    public function getByArea($areaId) {
        $sql = "SELECT * FROM {$this->table} WHERE area_id = :area_id AND status = 'active' ORDER BY location_name ASC";
        return $this->query($sql, ['area_id' => $areaId]);
    }

    /**
     * Save location (create or update)
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
        $loc = $this->find($id);
        if (!$loc) return false;
        $newStatus = $loc['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }

    /**
     * Check if location can be deleted (no linked stores)
     */
    public function canDelete($id) {
        $sql = "SELECT COUNT(*) as cnt FROM stores WHERE location_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch()['cnt'] == 0;
    }
}
