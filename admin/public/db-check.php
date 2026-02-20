<?php
// Database verification script
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>Database Connection Test</h2>";
    echo "<p>✅ Database connection successful</p>";
    
    // Check if users table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $user_count = $stmt->fetch()['count'];
    echo "<p>Users table count: {$user_count}</p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id, email, password_hash, user_type, status FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $admin_user = $stmt->fetch();
    
    if ($admin_user) {
        echo "<h3>Admin User Found:</h3>";
        echo "<ul>";
        echo "<li>ID: " . $admin_user['id'] . "</li>";
        echo "<li>Email: " . $admin_user['email'] . "</li>";
        echo "<li>User Type: " . $admin_user['user_type'] . "</li>";
        echo "<li>Status: " . $admin_user['status'] . "</li>";
        echo "<li>Password Hash: " . substr($admin_user['password_hash'], 0, 20) . "...</li>";
        echo "</ul>";
        
    // Test password verification with multiple possible passwords
    $passwords_to_test = ['Admin@123', 'password', 'admin123', 'admin'];
    echo "<h4>Password Verification Tests:</h4>";
    
    foreach ($passwords_to_test as $test_password) {
        $verify_result = password_verify($test_password, $admin_user['password_hash']);
        echo "<p><strong>'{$test_password}':</strong> " . ($verify_result ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        if ($verify_result) {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>🎉 CORRECT PASSWORD FOUND: {$test_password}</strong>";
            echo "</div>";
            break;
        }
    }        // Check admin profile
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
        $stmt->execute([$admin_user['id']]);
        $admin_profile = $stmt->fetch();
        
        if ($admin_profile) {
            echo "<h3>Admin Profile Found:</h3>";
            echo "<ul>";
            echo "<li>Admin ID: " . $admin_profile['id'] . "</li>";
            echo "<li>Admin Type: " . $admin_profile['admin_type'] . "</li>";
            echo "<li>Name: " . ($admin_profile['name'] ?? 'Not set') . "</li>";
            echo "</ul>";
        } else {
            echo "<p>❌ Admin profile not found</p>";
        }
        
    } else {
        echo "<p>❌ Admin user not found in database</p>";
        
        // Let's see what users exist
        $stmt = $pdo->query("SELECT id, email, user_type FROM users LIMIT 5");
        $users = $stmt->fetchAll();
        
        if ($users) {
            echo "<h3>Existing Users:</h3>";
            echo "<ul>";
            foreach ($users as $user) {
                echo "<li>ID: {$user['id']}, Email: {$user['email']}, Type: {$user['user_type']}</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>