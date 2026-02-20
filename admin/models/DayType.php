<?php
class DayType extends Model {
    protected $table = 'day_types';

    /**
     * Get all day types
     */
    public function getAll() {
        return $this->all('day_type_name ASC');
    }

    /**
     * Check if day type name is unique
     */
    public function nameExists($name, $excludeId = null) {
        return $this->exists('day_type_name', $name, $excludeId);
    }

    /**
     * Save day type
     */
    public function save($data, $id = null) {
        if ($id) {
            return $this->update($id, $data);
        }
        return $this->insert($data);
    }

    /**
     * Check if day type can be deleted
     */
    public function canDelete($id) {
        // Could be linked to flash_discounts etc in future, for now always deletable
        return true;
    }
}
