<?php
// Test the admin application setup
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/functions.php';

echo "<h2>Deal Machan Admin - Setup Test</h2>";
echo "<hr>";

try {
    // Test database connection
    echo "<h3>✅ Testing Database Connection...</h3>";
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->query("SELECT VERSION()");
    $version = $stmt->fetch();
    echo "<p><strong>MySQL Version:</strong> " . $version['VERSION()'] . "</p>";
    
    // Test if users table exists and has data
    echo "<h3>✅ Testing Users Table...</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p><strong>Total Users:</strong> " . $result['count'] . "</p>";
    
    // Test admin users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'");
    $result = $stmt->fetch();
    echo "<p><strong>Admin Users:</strong> " . $result['count'] . "</p>";
    
    // Test default admin account
    echo "<h3>✅ Testing Default Admin Account...</h3>";
    $stmt = $pdo->prepare("SELECT u.*, a.name, a.admin_type FROM users u LEFT JOIN admins a ON u.id = a.user_id WHERE u.email = ? AND u.user_type = 'admin'");
    $stmt->execute(['admin@dealmachan.com']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p><strong>✅ Default Admin Found:</strong></p>";
        echo "<ul>";
        echo "<li><strong>Email:</strong> " . $admin['email'] . "</li>";
        echo "<li><strong>Name:</strong> " . ($admin['name'] ?: 'Not set') . "</li>";
        echo "<li><strong>Admin Type:</strong> " . ($admin['admin_type'] ?: 'Not set') . "</li>";
        echo "<li><strong>Status:</strong> " . $admin['status'] . "</li>";
        echo "</ul>";
        
        // Test password
        echo "<h3>✅ Testing Password Hash...</h3>";
        if (password_verify('Admin@123', $admin['password'])) {
            echo "<p><strong>✅ Password verification successful!</strong></p>";
            echo "<p>You can login with: admin@dealmachan.com / Admin@123</p>";
        } else {
            echo "<p><strong>❌ Password verification failed!</strong></p>";
        }
    } else {
        echo "<p><strong>❌ Default admin account not found!</strong></p>";
    }
    
    // Test tables existence
    echo "<h3>✅ Testing Required Tables...</h3>";
    $required_tables = ['users', 'admins', 'cities', 'customers', 'merchants'];
    $stmt = $pdo->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($required_tables as $table) {
        if (in_array($table, $existing_tables)) {
            echo "<p>✅ Table '{$table}' exists</p>";
        } else {
            echo "<p>❌ Table '{$table}' missing</p>";
        }
    }
    
    // Test configuration
    echo "<h3>✅ Configuration Test...</h3>";
    echo "<p><strong>Base URL:</strong> " . BASE_URL . "</p>";
    echo "<p><strong>Environment:</strong> " . ENVIRONMENT . "</p>";
    echo "<p><strong>Session Timeout:</strong> " . SESSION_TIMEOUT . " seconds</p>";
    
    echo "<hr>";
    echo "<h3 style='color: green;'>🎉 Setup Complete!</h3>";
    echo "<p>Your Deal Machan Admin application is ready to use.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Visit: <a href='" . BASE_URL . "'>http://dealmachan-admin.local/</a></li>";
    echo "<li>Login with: admin@dealmachan.com / Admin@123</li>";
    echo "<li>Change the default password after first login</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>Database connection settings in .env file</li>";
    echo "<li>MySQL server is running</li>";
    echo "<li>Database 'deal_machan' exists and is populated</li>";
    echo "</ul>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h2, h3 { color: #333; }
p { margin: 5px 0; }
ul, ol { margin: 10px 0 10px 20px; }
hr { margin: 20px 0; }
</style>