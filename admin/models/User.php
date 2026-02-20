<?php
class User extends Model {
    protected $table = 'users';
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        return $this->where('email', $email)->first();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        return $this->find($id);
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        // Hash password if provided
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Remove password from update if empty
            unset($data['password']);
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->update($id, $data);
    }
    
    /**
     * Update last login time
     */
    public function updateLastLogin($id) {
        return $this->update($id, [
            'last_login' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Get all admin users
     */
    public function getAdminUsers() {
        return $this->where('user_type', 'admin')->get();
    }
    
    /**
     * Get active admin users
     */
    public function getActiveAdminUsers() {
        return $this->where('user_type', 'admin')
                   ->where('status', 'active')
                   ->get();
    }
    
    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $query = $this->where('email', $email);
        
        if ($excludeId) {
            $query = $query->where('id', '!=', $excludeId);
        }
        
        return $query->count() > 0;
    }
    
    /**
     * Verify password
     */
    public function verifyPassword($id, $password) {
        $user = $this->find($id);
        
        if (!$user) {
            return false;
        }
        
        return password_verify($password, $user['password']);
    }
    
    /**
     * Change password
     */
    public function changePassword($id, $newPassword) {
        return $this->update($id, [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Block/Unblock user
     */
    public function updateStatus($id, $status) {
        return $this->update($id, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
}
?>