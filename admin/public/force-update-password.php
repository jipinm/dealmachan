<?php
/**
 * Force Update Password Hash
 * This script updates the password hash directly via PHP
 * to ensure it reaches the same database PHP is reading from
 */

$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';
define('CORE_PATH', ROOT_PATH . '/core');
define('HELPER_PATH', ROOT_PATH . '/helpers');
require_once CORE_PATH . '/Database.php';
require_once HELPER_PATH . '/Logger.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Update Password</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .step { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2196F3; }
        .success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .error { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .hash { font-family: monospace; font-size: 11px; word-break: break-all; background: #f4f4f4; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #45a049; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Force Update Password Hash</h1>
    <p>This script updates the password hash directly using the same database connection that PHP uses for authentication.</p>
    
<?php

$newHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$testPassword = 'Admin@123';

try {
    Logger::info("Force password update started");
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<div class='step'>";
    echo "<h3>Step 1: Database Connection</h3>";
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>Host</td><td>" . getenv('DB_HOST') . "</td></tr>";
    echo "<tr><td>Database</td><td>" . getenv('DB_NAME') . "</td></tr>";
    echo "<tr><td>User</td><td>" . getenv('DB_USER') . "</td></tr>";
    echo "<tr><td>Connected To</td><td>" . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</td></tr>";
    echo "</table>";
    echo "✅ Connected successfully";
    echo "</div>";
    
    // Get current state
    echo "<div class='step'>";
    echo "<h3>Step 2: Current State (BEFORE Update)</h3>";
    $stmt = $pdo->prepare("SELECT id, email, password_hash, user_type, status FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $userBefore = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userBefore) {
        echo "<div class='error'>❌ User not found!</div>";
        exit;
    }
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$userBefore['id']}</td></tr>";
    echo "<tr><td>Email</td><td>{$userBefore['email']}</td></tr>";
    echo "<tr><td>User Type</td><td>{$userBefore['user_type']}</td></tr>";
    echo "<tr><td>Status</td><td>{$userBefore['status']}</td></tr>";
    echo "</table>";
    
    echo "<strong>Current Password Hash:</strong>";
    echo "<div class='hash'>{$userBefore['password_hash']}</div>";
    
    // Test current hash
    $currentWorks = password_verify($testPassword, $userBefore['password_hash']);
    echo "<p>Current hash verifies '{$testPassword}': " . ($currentWorks ? "✅ YES" : "❌ NO") . "</p>";
    
    echo "</div>";
    
    // Perform update
    echo "<div class='step'>";
    echo "<h3>Step 3: Performing Update</h3>";
    
    Logger::info("Updating password hash", ['user_id' => $userBefore['id']]);
    
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE email = ?");
    $result = $stmt->execute([$newHash, 'admin@dealmachan.com']);
    $rowsAffected = $stmt->rowCount();
    
    echo "<table>";
    echo "<tr><th>Operation</th><th>Result</th></tr>";
    echo "<tr><td>Execute Result</td><td>" . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</td></tr>";
    echo "<tr><td>Rows Affected</td><td>{$rowsAffected}</td></tr>";
    echo "</table>";
    
    if (!$result || $rowsAffected === 0) {
        echo "<div class='error'>❌ Update failed or no rows affected!</div>";
        Logger::error("Password update failed", ['result' => $result, 'rows' => $rowsAffected]);
    } else {
        echo "<p>✅ Update executed successfully</p>";
        Logger::info("Password hash updated successfully", ['rows_affected' => $rowsAffected]);
    }
    
    echo "</div>";
    
    // Get new state
    echo "<div class='step'>";
    echo "<h3>Step 4: New State (AFTER Update)</h3>";
    
    $stmt = $pdo->prepare("SELECT id, email, password_hash, user_type, status FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $userAfter = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<strong>New Password Hash:</strong>";
    echo "<div class='hash'>{$userAfter['password_hash']}</div>";
    
    echo "</div>";
    
    // Verification
    echo "<div class='step'>";
    echo "<h3>Step 5: Verification</h3>";
    
    $hashMatches = ($userAfter['password_hash'] === $newHash);
    $passwordWorks = password_verify($testPassword, $userAfter['password_hash']);
    
    echo "<table>";
    echo "<tr><th>Check</th><th>Result</th></tr>";
    echo "<tr><td>Hash matches expected value</td><td>" . ($hashMatches ? "✅ YES" : "❌ NO") . "</td></tr>";
    echo "<tr><td>Hash verifies '{$testPassword}'</td><td>" . ($passwordWorks ? "✅ YES" : "❌ NO") . "</td></tr>";
    echo "<tr><td>Hash changed from before</td><td>" . ($userBefore['password_hash'] !== $userAfter['password_hash'] ? "✅ YES" : "❌ NO") . "</td></tr>";
    echo "</table>";
    
    echo "</div>";
    
    // Final result
    if ($hashMatches && $passwordWorks) {
        echo "<div class='step success'>";
        echo "<h2>🎉 SUCCESS!</h2>";
        echo "<p style='font-size: 18px;'><strong>Password has been updated successfully!</strong></p>";
        echo "<p>You can now login with:</p>";
        echo "<ul style='font-size: 16px;'>";
        echo "<li><strong>Email:</strong> admin@dealmachan.com</li>";
        echo "<li><strong>Password:</strong> Admin@123</li>";
        echo "</ul>";
        echo "<p><a href='index.php' class='btn'>Go to Login Page</a></p>";
        echo "</div>";
        
        Logger::info("Password update completed successfully");
        
    } elseif ($userBefore['password_hash'] === $userAfter['password_hash']) {
        echo "<div class='step error'>";
        echo "<h2>❌ UPDATE DID NOT TAKE EFFECT</h2>";
        echo "<p>The hash in the database did not change!</p>";
        echo "<p><strong>Possible causes:</strong></p>";
        echo "<ul>";
        echo "<li>Database user doesn't have UPDATE permission</li>";
        echo "<li>Table is locked</li>";
        echo "<li>Trigger preventing update</li>";
        echo "<li>Replication lag</li>";
        echo "</ul>";
        echo "<p><strong>Try:</strong></p>";
        echo "<ul>";
        echo "<li>Check MySQL user permissions</li>";
        echo "<li>Run UPDATE directly in MySQL command line</li>";
        echo "<li>Check MySQL error log</li>";
        echo "</ul>";
        echo "</div>";
        
        Logger::error("Password hash did not change after update");
        
    } else {
        echo "<div class='step warning'>";
        echo "<h2>⚠️ PARTIAL SUCCESS</h2>";
        echo "<p>The hash was updated but something is still wrong.</p>";
        echo "<p>Hash matches expected: " . ($hashMatches ? "YES" : "NO") . "</p>";
        echo "<p>Password verifies: " . ($passwordWorks ? "YES" : "NO") . "</p>";
        echo "</div>";
        
        Logger::warning("Password update partially successful", [
            'hash_matches' => $hashMatches,
            'password_works' => $passwordWorks
        ]);
    }
    
    // Comparison
    echo "<div class='step'>";
    echo "<h3>Hash Comparison</h3>";
    echo "<table>";
    echo "<tr><th>Version</th><th>Hash</th></tr>";
    echo "<tr><td>Before</td><td style='font-family:monospace;font-size:10px;'>{$userBefore['password_hash']}</td></tr>";
    echo "<tr><td>After</td><td style='font-family:monospace;font-size:10px;'>{$userAfter['password_hash']}</td></tr>";
    echo "<tr><td>Expected</td><td style='font-family:monospace;font-size:10px;'>{$newHash}</td></tr>";
    echo "</table>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<h2>❌ Error Occurred</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
    
    Logger::critical("Force password update failed", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

?>

<hr>
<p>
    <a href="find-password.php" class="btn">Find Working Password</a>
    <a href="debug-password.php" class="btn">Deep Debug</a>
    <a href="diagnose-login.php" class="btn">Full Diagnostic</a>
    <a href="index.php" class="btn">Go to Login</a>
</p>

<p><small>Check logs at: <code>e:\DealMachan\admin\logs\app.log</code></small></p>

</div>
</body>
</html>
