<?php
/**
 * Find What Password Actually Works
 * This script tests the actual hash in the database against various passwords
 */

$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';
define('CORE_PATH', ROOT_PATH . '/core');
require_once CORE_PATH . '/Database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Find Working Password</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .hash-box { background: #f9f9f9; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0; font-family: monospace; word-break: break-all; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f9f9f9; }
        .match { background: #4CAF50 !important; color: white; font-weight: bold; }
        .no-match { color: #999; }
        .alert { padding: 15px; margin: 20px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .sql-box { background: #fff3cd; padding: 15px; border: 2px solid #ffc107; margin: 20px 0; }
        .sql-box pre { margin: 0; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Find Working Password</h1>
    
<?php
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='alert alert-danger'>❌ User not found in database!</div>";
        exit;
    }
    
    $currentHash = $user['password_hash'];
    $expectedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo "<h2>Current Database State</h2>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>User ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>User Type</td><td>{$user['user_type']}</td></tr>";
    echo "<tr><td>Status</td><td>{$user['status']}</td></tr>";
    echo "</table>";
    
    echo "<h2>Password Hash Comparison</h2>";
    echo "<div class='hash-box'>";
    echo "<strong>Current Hash in DB:</strong><br>";
    echo $currentHash;
    echo "</div>";
    
    echo "<div class='hash-box'>";
    echo "<strong>Expected Hash for 'Admin@123':</strong><br>";
    echo $expectedHash;
    echo "</div>";
    
    $hashMatch = ($currentHash === $expectedHash);
    if ($hashMatch) {
        echo "<div class='alert alert-success'>✅ Hashes MATCH! The database has the correct hash for 'Admin@123'</div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Hashes DO NOT MATCH! The database has a different password hash.</div>";
    }
    
    // Test a comprehensive list of passwords
    echo "<h2>Testing Passwords Against Current Hash</h2>";
    echo "<p>Testing various password combinations to find which one works...</p>";
    
    $testPasswords = [
        // Expected
        'Admin@123',
        
        // Variations
        'admin@123',
        'ADMIN@123',
        'Admin123',
        'admin123',
        'ADMIN123',
        'Admin@1234',
        'Admin@12',
        
        // Common defaults
        'password',
        'Password',
        'PASSWORD',
        'password123',
        'Password123',
        'admin',
        'Admin',
        'ADMIN',
        'administrator',
        'Administrator',
        
        // Empty/simple
        '',
        '123456',
        '12345678',
        'qwerty',
        
        // Laravel default test password
        'password',
        
        // The hash from test-password.php
        'password', // This is what Laravel's default test hash is for
    ];
    
    echo "<table>";
    echo "<tr><th>#</th><th>Password Tested</th><th>Result</th></tr>";
    
    $workingPassword = null;
    $count = 0;
    
    foreach ($testPasswords as $testPwd) {
        $count++;
        $result = password_verify($testPwd, $currentHash);
        
        $rowClass = $result ? 'match' : '';
        $resultText = $result ? '✅ MATCH!' : '❌ No match';
        
        echo "<tr class='{$rowClass}'>";
        echo "<td>{$count}</td>";
        echo "<td><strong>" . htmlspecialchars($testPwd === '' ? '(empty string)' : $testPwd) . "</strong></td>";
        echo "<td>{$resultText}</td>";
        echo "</tr>";
        
        if ($result && !$workingPassword) {
            $workingPassword = $testPwd;
        }
    }
    
    echo "</table>";
    
    // Results
    echo "<h2>🎯 Results</h2>";
    
    if ($workingPassword !== null) {
        echo "<div class='alert alert-success'>";
        echo "<h3>✅ FOUND IT!</h3>";
        echo "<p style='font-size: 18px;'>The password that works with the current database hash is:</p>";
        echo "<p style='font-size: 24px; font-weight: bold; background: white; padding: 15px; border-radius: 4px; text-align: center;'>";
        echo htmlspecialchars($workingPassword === '' ? '(empty string)' : $workingPassword);
        echo "</p>";
        echo "<p><strong>You can login with:</strong></p>";
        echo "<ul>";
        echo "<li>Email: admin@dealmachan.com</li>";
        echo "<li>Password: " . htmlspecialchars($workingPassword) . "</li>";
        echo "</ul>";
        echo "</div>";
        
        if ($workingPassword !== 'Admin@123') {
            echo "<div class='alert alert-info'>";
            echo "<h3>💡 To Change Password to 'Admin@123'</h3>";
            echo "<p>Run this SQL command:</p>";
            echo "<div class='sql-box'><pre>";
            echo "UPDATE users\n";
            echo "SET password_hash = '{$expectedHash}'\n";
            echo "WHERE email = 'admin@dealmachan.com';";
            echo "</pre></div>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='alert alert-danger'>";
        echo "<h3>❌ No Match Found</h3>";
        echo "<p>None of the tested passwords match the current hash in the database.</p>";
        echo "<p><strong>This means:</strong></p>";
        echo "<ul>";
        echo "<li>The password is something else entirely, OR</li>";
        echo "<li>The hash in the database is corrupted/invalid</li>";
        echo "</ul>";
        echo "<p><strong>SOLUTION: Update the hash to use 'Admin@123'</strong></p>";
        echo "<div class='sql-box'><pre>";
        echo "UPDATE users\n";
        echo "SET password_hash = '{$expectedHash}'\n";
        echo "WHERE email = 'admin@dealmachan.com';";
        echo "</pre></div>";
        echo "</div>";
    }
    
    // Additional verification
    echo "<h2>🔬 Additional Verification</h2>";
    echo "<table>";
    echo "<tr><th>Test</th><th>Result</th></tr>";
    
    // Test if expected hash works with Admin@123
    $test1 = password_verify('Admin@123', $expectedHash);
    echo "<tr><td>Expected hash verifies 'Admin@123'</td><td>" . ($test1 ? '✅ YES' : '❌ NO') . "</td></tr>";
    
    // Test if we can generate a working hash
    $newHash = password_hash('Admin@123', PASSWORD_DEFAULT);
    $test2 = password_verify('Admin@123', $newHash);
    echo "<tr><td>Newly generated hash verifies 'Admin@123'</td><td>" . ($test2 ? '✅ YES' : '❌ NO') . "</td></tr>";
    
    // Check hash info
    $hashInfo = password_get_info($currentHash);
    echo "<tr><td>Current hash algorithm</td><td>{$hashInfo['algoName']}</td></tr>";
    echo "<tr><td>Current hash valid</td><td>" . ($hashInfo['algoName'] !== 'unknown' ? '✅ YES' : '❌ NO') . "</td></tr>";
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<hr>
<p>
    <a href="debug-password.php" class="btn">Deep Debug</a>
    <a href="diagnose-login.php" class="btn">Full Diagnostic</a>
    <a href="index.php" class="btn">Go to Login</a>
</p>

</div>
</body>
</html>
