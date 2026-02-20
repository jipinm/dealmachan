<?php
class Admin extends Model {
    protected $table = 'admins';
    
    /**
     * Find admin by user ID
     */
    public function findByUserId($user_id) {
        return $this->where('user_id', $user_id)->first();
    }
    
    /**
     * Find admin by ID with user details
     */
    public function findWithUser($id) {
        $sql = "SELECT a.*, u.email, u.phone, u.status, u.created_at as user_created_at, u.last_login,
                       c.city_name
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN cities c ON a.city_id = c.id
                WHERE a.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get all admins with user details
     */
    public function getAllWithUsers($admin_type = null, $city_id = null) {
        $sql = "SELECT a.*, u.email, u.phone, u.status, u.created_at as user_created_at, u.last_login,
                       c.city_name
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN cities c ON a.city_id = c.id
                WHERE 1=1";
        
        $params = [];
        
        if ($admin_type) {
            $sql .= " AND a.admin_type = ?";
            $params[] = $admin_type;
        }
        
        if ($city_id) {
            $sql .= " AND a.city_id = ?";
            $params[] = $city_id;
        }
        
        $sql .= " ORDER BY a.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create admin with user
     */
    public function createAdminWithUser($userData, $adminData) {
        $this->db->beginTransaction();
        
        try {
            // Create user first
            $userModel = new User();
            $userId = $userModel->createUser($userData);
            
            // Create admin
            $adminData['user_id'] = $userId;
            $adminData['created_at'] = date('Y-m-d H:i:s');
            $adminData['updated_at'] = date('Y-m-d H:i:s');
            
            $adminId = $this->create($adminData);
            
            $this->db->commit();
            
            return $adminId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Update admin with user
     */
    public function updateAdminWithUser($adminId, $userData, $adminData) {
        $this->db->beginTransaction();
        
        try {
            // Get admin to find user_id
            $admin = $this->find($adminId);
            if (!$admin) {
                throw new Exception('Admin not found');
            }
            
            // Update user
            if (!empty($userData)) {
                $userModel = new User();
                $userModel->updateUser($admin['user_id'], $userData);
            }
            
            // Update admin
            if (!empty($adminData)) {
                $adminData['updated_at'] = date('Y-m-d H:i:s');
                $this->update($adminId, $adminData);
            }
            
            $this->db->commit();
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get admins by type
     */
    public function getByType($admin_type) {
        return $this->where('admin_type', $admin_type)->get();
    }
    
    /**
     * Get admins by city
     */
    public function getByCity($city_id) {
        return $this->where('city_id', $city_id)->get();
    }
    
    /**
     * Get admin statistics
     */
    public function getStatistics() {
        $sql = "SELECT 
                    admin_type,
                    COUNT(*) as count,
                    SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as active_count
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                GROUP BY admin_type
                ORDER BY admin_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Count total active super admins (to prevent deleting / blocking the last one)
     */
    public function countSuperAdmins() {
        $sql = "SELECT COUNT(*) FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.admin_type = 'super_admin' AND u.status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Check if an admin can be safely deleted
     * Returns true if deletable, or an error string if not
     */
    public function canDelete($adminId, $currentAdminId) {
        if ((int)$adminId === (int)$currentAdminId) {
            return 'You cannot delete your own account.';
        }
        $admin = $this->find($adminId);
        if (!$admin) {
            return 'Admin not found.';
        }
        if ($admin['admin_type'] === 'super_admin' && $this->countSuperAdmins() <= 1) {
            return 'Cannot delete the last active Super Admin.';
        }
        return true;
    }

    /**
     * Toggle the user status (active ↔ inactive) for an admin
     */
    public function toggleStatus($adminId, $currentAdminId) {
        if ((int)$adminId === (int)$currentAdminId) {
            return 'You cannot change your own status.';
        }
        $admin = $this->findWithUser($adminId);
        if (!$admin) {
            return 'Admin not found.';
        }
        if ($admin['admin_type'] === 'super_admin'
            && $admin['status'] === 'active'
            && $this->countSuperAdmins() <= 1) {
            return 'Cannot deactivate the last active Super Admin.';
        }
        $newStatus = ($admin['status'] === 'active') ? 'inactive' : 'active';
        $sql = "UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$newStatus, $admin['user_id']]);
        return $newStatus;
    }

    /**
     * Delete admin record + associated user record in a transaction
     */
    public function deleteWithUser($adminId) {
        $admin = $this->find($adminId);
        if (!$admin) {
            throw new Exception('Admin not found.');
        }
        $this->db->beginTransaction();
        try {
            $this->delete($adminId);
            $sql = "DELETE FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$admin['user_id']]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Reset an admin's password
     */
    public function resetPassword($adminId, $newPassword) {
        $admin = $this->find($adminId);
        if (!$admin) {
            throw new Exception('Admin not found.');
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql  = "UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hash, $admin['user_id']]);
    }

    /**
     * Check if email already exists (optionally excluding a user_id)
     */
    public function emailExists($email, $excludeUserId = null) {
        if ($excludeUserId) {
            $sql  = "SELECT COUNT(*) FROM users WHERE email = ? AND id != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email, $excludeUserId]);
        } else {
            $sql  = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
        }
        return (int)$stmt->fetchColumn() > 0;
    }
}
?>