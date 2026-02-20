<?php
/**
 * Admin Setup Script
 * Creates/Updates the default super admin user for Deal Machan
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<!DOCTYPE html>";
    echo "<html><head><title>Deal Machan Admin Setup</title>";
    echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:50px auto;padding:20px;}</style>";
    echo "</head><body>";
    
    echo "<h2>Deal Machan Admin Setup & Fix</h2>";
    
    // Start transaction
    $pdo->beginTransaction();
    
    $email = 'admin@dealmachan.com';
    $password = 'Admin@123';
    $phone = '9999999999';
    
    echo "<h3>Step 1: Checking existing admin user...</h3>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        echo "<p>✅ Admin user found with ID: " . $existing_user['id'] . "</p>";
        
        // Test current password
        $current_verify = password_verify($password, $existing_user['password_hash']);
        echo "<p>Current password verification: " . ($current_verify ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        if (!$current_verify) {
            echo "<h3>Step 2: Updating password hash...</h3>";
            
            // Update with correct password hash
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, status = 'active' WHERE email = ?");
            $stmt->execute([$password_hash, $email]);
            
            echo "<p>✅ Password hash updated successfully!</p>";
            echo "<p>New hash: " . substr($password_hash, 0, 30) . "...</p>";
            
            // Verify new password
            $verify_new = password_verify($password, $password_hash);
            echo "<p>New password verification: " . ($verify_new ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
            
        } else {
            echo "<p>✅ Password is already correct!</p>";
        }
        
        // Check admin profile
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
        $stmt->execute([$existing_user['id']]);
        $admin_profile = $stmt->fetch();
        
        if ($admin_profile) {
            echo "<p>✅ Admin profile found with ID: " . $admin_profile['id'] . "</p>";
            echo "<p>Admin type: " . $admin_profile['admin_type'] . "</p>";
        } else {
            echo "<p>❌ Admin profile missing. Creating...</p>";
            
            // Create admin profile
            $stmt = $pdo->prepare("INSERT INTO admins (user_id, admin_type, permissions_json) VALUES (?, 'super_admin', ?)");
            $stmt->execute([$existing_user['id'], json_encode(['all'])]);
            
            echo "<p>✅ Admin profile created!</p>";
        }
        
    } else {
        echo "<h3>Step 2: Creating new admin user...</h3>";
        
        // Create new user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (email, phone, password_hash, user_type, status) VALUES (?, ?, ?, 'admin', 'active')");
        $stmt->execute([$email, $phone, $password_hash]);
        $user_id = $pdo->lastInsertId();
        
        // Create admin profile
        $stmt = $pdo->prepare("INSERT INTO admins (user_id, admin_type, permissions_json) VALUES (?, 'super_admin', ?)");
        $stmt->execute([$user_id, json_encode(['all'])]);
        
        echo "<p>✅ New admin user and profile created successfully!</p>";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "<h3>Step 3: Final Verification</h3>";
    
    // Final verification
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.password_hash, u.status, 
               a.id as admin_id, a.admin_type 
        FROM users u 
        LEFT JOIN admins a ON u.id = a.user_id 
        WHERE u.email = ?
    ");
    $stmt->execute([$email]);
    $final_check = $stmt->fetch();
    
    if ($final_check) {
        $final_verify = password_verify($password, $final_check['password_hash']);
        
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h4>✅ Setup Complete!</h4>";
        echo "<p><strong>User ID:</strong> " . $final_check['id'] . "</p>";
        echo "<p><strong>Admin ID:</strong> " . $final_check['admin_id'] . "</p>";
        echo "<p><strong>Email:</strong> " . $final_check['email'] . "</p>";
        echo "<p><strong>Status:</strong> " . $final_check['status'] . "</p>";
        echo "<p><strong>Admin Type:</strong> " . $final_check['admin_type'] . "</p>";
        echo "<p><strong>Password Verification:</strong> " . ($final_verify ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        echo "</div>";
        
        if ($final_verify) {
            echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4>🚀 Ready to Login!</h4>";
            echo "<p><strong>Login URL:</strong> <a href='./index.php' target='_blank'>Click here to login</a></p>";
            echo "<p><strong>Email:</strong> {$email}</p>";
            echo "<p><strong>Password:</strong> {$password}</p>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ Final verification failed. Something went wrong.</p>";
    }
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h4>❌ Error Occurred</h4>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
    echo "<h4>Troubleshooting Steps:</h4>";
    echo "<ul>";
    echo "<li>Check if the database server is running</li>";
    echo "<li>Verify database connection settings in admin/config/database.php</li>";
    echo "<li>Ensure the database 'deal_machan' exists</li>";
    echo "<li>Check if all required tables are created</li>";
    echo "</ul>";
}

echo "</body></html>";
?>