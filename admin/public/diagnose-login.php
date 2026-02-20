<?php
/**
 * Login Diagnostic Script
 * This script helps diagnose login issues by testing database connection,
 * user existence, and password verification
 */

// Define basic paths
$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

// Load environment and constants
require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';

// Define paths
define('CORE_PATH', ROOT_PATH . '/core');
define('HELPER_PATH', ROOT_PATH . '/helpers');

// Load required classes
require_once CORE_PATH . '/Database.php';
require_once HELPER_PATH . '/Logger.php';

// Test credentials
$testEmail = 'admin@dealmachan.com';
$testPassword = 'Admin@123';

echo "<h1>Login Diagnostic Report</h1>";
echo "<p>Testing login for: <strong>{$testEmail}</strong></p>";
echo "<hr>";

Logger::info("=== DIAGNOSTIC SCRIPT STARTED ===");

// Test 1: Database Connection
echo "<h2>Test 1: Database Connection</h2>";
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    Logger::info("Database connection successful");
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    Logger::error("Database connection failed", ['error' => $e->getMessage()]);
    die();
}

// Test 2: Check if user exists
echo "<h2>Test 2: User Existence Check</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p style='color: green;'>✓ User found in database</p>";
        echo "<pre>";
        echo "User ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "User Type: " . $user['user_type'] . "\n";
        echo "Status: " . $user['status'] . "\n";
        echo "Password Hash: " . substr($user['password_hash'], 0, 30) . "...\n";
        echo "Password Hash Length: " . strlen($user['password_hash']) . "\n";
        echo "Created At: " . $user['created_at'] . "\n";
        echo "Last Login: " . ($user['last_login'] ?? 'Never') . "\n";
        echo "</pre>";
        
        Logger::info("User found", [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'user_type' => $user['user_type'],
            'status' => $user['status']
        ]);
    } else {
        echo "<p style='color: red;'>✗ User not found in database</p>";
        Logger::warning("User not found", ['email' => $testEmail]);
        die();
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking user: " . htmlspecialchars($e->getMessage()) . "</p>";
    Logger::error("Error checking user", ['error' => $e->getMessage()]);
    die();
}

// Test 3: Check user type
echo "<h2>Test 3: User Type Check</h2>";
if ($user['user_type'] === 'admin') {
    echo "<p style='color: green;'>✓ User type is 'admin'</p>";
    Logger::info("User type is admin");
} else {
    echo "<p style='color: red;'>✗ User type is '{$user['user_type']}' (expected 'admin')</p>";
    Logger::warning("User type mismatch", ['expected' => 'admin', 'actual' => $user['user_type']]);
}

// Test 4: Check user status
echo "<h2>Test 4: User Status Check</h2>";
if ($user['status'] === 'active') {
    echo "<p style='color: green;'>✓ User status is 'active'</p>";
    Logger::info("User status is active");
} else {
    echo "<p style='color: red;'>✗ User status is '{$user['status']}' (expected 'active')</p>";
    Logger::warning("User status not active", ['status' => $user['status']]);
}

// Test 5: Password verification
echo "<h2>Test 5: Password Verification</h2>";
$passwordVerified = password_verify($testPassword, $user['password_hash']);
if ($passwordVerified) {
    echo "<p style='color: green;'>✓ Password verification successful</p>";
    Logger::info("Password verified successfully");
} else {
    echo "<p style='color: red;'>✗ Password verification failed</p>";
    echo "<p>Testing with stored hash from database...</p>";
    
    // Get hash info
    $hashInfo = password_get_info($user['password_hash']);
    echo "<pre>";
    echo "Hash Algorithm: " . $hashInfo['algoName'] . "\n";
    echo "Hash Options: " . json_encode($hashInfo['options']) . "\n";
    echo "</pre>";
    
    // Try generating a new hash
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    echo "<p>Generated new hash for comparison: " . substr($newHash, 0, 30) . "...</p>";
    echo "<p>New hash verification: " . (password_verify($testPassword, $newHash) ? 'SUCCESS' : 'FAILED') . "</p>";
    
    Logger::warning("Password verification failed", [
        'hash_algorithm' => $hashInfo['algoName'],
        'stored_hash_length' => strlen($user['password_hash'])
    ]);
}

// Test 6: Check admin profile
echo "<h2>Test 6: Admin Profile Check</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p style='color: green;'>✓ Admin profile found</p>";
        echo "<pre>";
        echo "Admin ID: " . $admin['id'] . "\n";
        echo "User ID: " . $admin['user_id'] . "\n";
        echo "Admin Type: " . $admin['admin_type'] . "\n";
        echo "City ID: " . ($admin['city_id'] ?? 'NULL') . "\n";
        echo "Permissions: " . ($admin['permissions_json'] ?? 'NULL') . "\n";
        echo "Created At: " . $admin['created_at'] . "\n";
        echo "</pre>";
        
        Logger::info("Admin profile found", [
            'admin_id' => $admin['id'],
            'admin_type' => $admin['admin_type']
        ]);
    } else {
        echo "<p style='color: red;'>✗ Admin profile not found</p>";
        Logger::error("Admin profile not found", ['user_id' => $user['id']]);
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error checking admin profile: " . htmlspecialchars($e->getMessage()) . "</p>";
    Logger::error("Error checking admin profile", ['error' => $e->getMessage()]);
}

// Test 7: Full login simulation
echo "<h2>Test 7: Full Login Simulation</h2>";
$allTestsPassed = true;

if (!$user) {
    echo "<p style='color: red;'>✗ Cannot simulate login - user not found</p>";
    $allTestsPassed = false;
} elseif ($user['user_type'] !== 'admin') {
    echo "<p style='color: red;'>✗ Cannot simulate login - user type is not admin</p>";
    $allTestsPassed = false;
} elseif ($user['status'] !== 'active') {
    echo "<p style='color: red;'>✗ Cannot simulate login - user status is not active</p>";
    $allTestsPassed = false;
} elseif (!$passwordVerified) {
    echo "<p style='color: red;'>✗ Cannot simulate login - password verification failed</p>";
    $allTestsPassed = false;
} elseif (!$admin) {
    echo "<p style='color: red;'>✗ Cannot simulate login - admin profile not found</p>";
    $allTestsPassed = false;
} else {
    echo "<p style='color: green;'>✓ All checks passed - Login should work!</p>";
    Logger::info("All diagnostic checks passed");
}

echo "<hr>";
echo "<h2>Summary</h2>";
if ($allTestsPassed) {
    echo "<p style='color: green; font-weight: bold;'>✓ All tests passed! Login should work with the provided credentials.</p>";
    echo "<p>If login still fails, check:</p>";
    echo "<ul>";
    echo "<li>Session configuration</li>";
    echo "<li>CSRF token generation</li>";
    echo "<li>Browser cookies</li>";
    echo "<li>Server logs in logs/app.log</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Some tests failed. Please review the issues above.</p>";
}

echo "<hr>";
echo "<p><strong>Log file:</strong> " . ROOT_PATH . "/logs/app.log</p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";

Logger::info("=== DIAGNOSTIC SCRIPT COMPLETED ===");
?>
