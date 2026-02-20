<?php
/**
 * Deep Password Debug - Find the Root Cause
 */

// Define basic paths
$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';
define('CORE_PATH', ROOT_PATH . '/core');
require_once CORE_PATH . '/Database.php';

echo "<h1>Deep Password Debug</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    .pass { color: green; font-weight: bold; }
    .fail { color: red; font-weight: bold; }
    .code { background: #f4f4f4; padding: 10px; font-family: monospace; margin: 10px 0; }
    .section { background: #e8f5e9; padding: 15px; margin: 20px 0; border-left: 4px solid #4CAF50; }
    .error { background: #ffebee; border-left: 4px solid #f44336; }
</style>";

$testPassword = 'Admin@123';
$expectedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Get user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "<div class='section error'><h2>❌ User Not Found</h2></div>";
        exit;
    }
    
    echo "<div class='section'><h2>✓ User Found in Database</h2></div>";
    
    // Display user info
    echo "<h3>1. Database User Record</h3>";
    echo "<table>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$user['id']}</td></tr>";
    echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
    echo "<tr><td>User Type</td><td>{$user['user_type']}</td></tr>";
    echo "<tr><td>Status</td><td>{$user['status']}</td></tr>";
    echo "</table>";
    
    // Password hash analysis
    echo "<h3>2. Password Hash Analysis</h3>";
    $currentHash = $user['password_hash'];
    
    echo "<table>";
    echo "<tr><th>Property</th><th>Current DB Hash</th><th>Expected Hash</th><th>Match?</th></tr>";
    echo "<tr><td>Full Hash</td><td style='font-family:monospace;font-size:10px;'>{$currentHash}</td><td style='font-family:monospace;font-size:10px;'>{$expectedHash}</td><td class='" . ($currentHash === $expectedHash ? "pass'>✓ YES" : "fail'>✗ NO") . "</td></tr>";
    echo "<tr><td>Length</td><td>" . strlen($currentHash) . "</td><td>" . strlen($expectedHash) . "</td><td class='" . (strlen($currentHash) === strlen($expectedHash) ? "pass'>✓ SAME" : "fail'>✗ DIFFERENT") . "</td></tr>";
    
    $hashInfo = password_get_info($currentHash);
    echo "<tr><td>Algorithm</td><td>{$hashInfo['algoName']}</td><td>bcrypt</td><td class='" . ($hashInfo['algoName'] === 'bcrypt' ? "pass'>✓ SAME" : "fail'>✗ DIFFERENT") . "</td></tr>";
    echo "</table>";
    
    // Show first difference
    if ($currentHash !== $expectedHash) {
        echo "<h4>First Difference Found:</h4>";
        for ($i = 0; $i < max(strlen($currentHash), strlen($expectedHash)); $i++) {
            $c1 = isset($currentHash[$i]) ? $currentHash[$i] : '';
            $c2 = isset($expectedHash[$i]) ? $expectedHash[$i] : '';
            if ($c1 !== $c2) {
                echo "<div class='code'>";
                echo "Position: {$i}<br>";
                echo "Current DB: '" . htmlspecialchars($c1) . "' (ASCII: " . ord($c1) . ")<br>";
                echo "Expected: '" . htmlspecialchars($c2) . "' (ASCII: " . ord($c2) . ")<br>";
                echo "</div>";
                break;
            }
        }
    }
    
    // Password verification tests
    echo "<h3>3. Password Verification Tests</h3>";
    echo "<table>";
    echo "<tr><th>Test</th><th>Result</th><th>Details</th></tr>";
    
    // Test 1: Current hash with test password
    $test1 = password_verify($testPassword, $currentHash);
    echo "<tr><td>Current DB hash verifies '{$testPassword}'</td><td class='" . ($test1 ? "pass'>✓ PASS" : "fail'>✗ FAIL") . "</td><td>" . ($test1 ? "Password is correct!" : "Password doesn't match this hash") . "</td></tr>";
    
    // Test 2: Expected hash with test password
    $test2 = password_verify($testPassword, $expectedHash);
    echo "<tr><td>Expected hash verifies '{$testPassword}'</td><td class='" . ($test2 ? "pass'>✓ PASS" : "fail'>✗ FAIL") . "</td><td>" . ($test2 ? "Expected hash is valid" : "Expected hash is invalid") . "</td></tr>";
    
    // Test 3: Generate new hash and test
    $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
    $test3 = password_verify($testPassword, $newHash);
    echo "<tr><td>Newly generated hash verifies '{$testPassword}'</td><td class='" . ($test3 ? "pass'>✓ PASS" : "fail'>✗ FAIL") . "</td><td>Fresh hash: " . substr($newHash, 0, 30) . "...</td></tr>";
    
    echo "</table>";
    
    // Test different passwords
    echo "<h3>4. Testing Different Passwords with Current Hash</h3>";
    $testPasswords = [
        'Admin@123',
        'admin@123',
        'ADMIN@123',
        'Admin123',
        'password',
        'admin',
        'Admin@1234',
        'Admin@12'
    ];
    
    echo "<table>";
    echo "<tr><th>Password Tested</th><th>Verifies?</th></tr>";
    $foundMatch = false;
    foreach ($testPasswords as $testPwd) {
        $result = password_verify($testPwd, $currentHash);
        echo "<tr><td>{$testPwd}</td><td class='" . ($result ? "pass'>✓ MATCH" : "fail'>✗ NO MATCH") . "</td></tr>";
        if ($result && !$foundMatch) {
            $foundMatch = $testPwd;
        }
    }
    echo "</table>";
    
    if ($foundMatch) {
        echo "<div class='section'>";
        echo "<h3>🎉 FOUND IT!</h3>";
        echo "<p>The current password hash matches: <strong>{$foundMatch}</strong></p>";
        echo "<p>The password in the database is NOT 'Admin@123', it's '{$foundMatch}'</p>";
        echo "</div>";
    }
    
    // Root cause analysis
    echo "<h3>5. Root Cause Analysis</h3>";
    echo "<div class='section " . ($currentHash === $expectedHash ? "" : "error") . "'>";
    
    if ($currentHash === $expectedHash) {
        echo "<h4>✓ Hash is Correct</h4>";
        echo "<p>The password hash in the database IS correct for 'Admin@123'.</p>";
        echo "<p>If login still fails, the issue is elsewhere (session, CSRF, etc.)</p>";
    } else {
        echo "<h4>❌ Hash is WRONG</h4>";
        echo "<p><strong>The password hash in your database does NOT match 'Admin@123'</strong></p>";
        
        if ($foundMatch) {
            echo "<p>The current hash is for password: <strong>{$foundMatch}</strong></p>";
            echo "<p>Either:</p>";
            echo "<ul>";
            echo "<li>Use password: <strong>{$foundMatch}</strong> to login, OR</li>";
            echo "<li>Update the hash to use 'Admin@123'</li>";
            echo "</ul>";
        } else {
            echo "<p>The current hash doesn't match any common password.</p>";
            echo "<p>You MUST update the hash in the database.</p>";
        }
        
        echo "<h4>Fix Command:</h4>";
        echo "<div class='code'>";
        echo "UPDATE users<br>";
        echo "SET password_hash = '{$expectedHash}'<br>";
        echo "WHERE email = 'admin@dealmachan.com';<br>";
        echo "</div>";
        
        echo "<p><strong>Why the SQL update might not be working:</strong></p>";
        echo "<ul>";
        echo "<li>SQL not actually executing (check for errors)</li>";
        echo "<li>Wrong database selected</li>";
        echo "<li>Transaction not committed</li>";
        echo "<li>Caching issue (try restarting MySQL)</li>";
        echo "<li>Reading from a different database/server</li>";
        echo "</ul>";
    }
    echo "</div>";
    
    // Generate working SQL
    echo "<h3>6. Exact SQL to Fix This</h3>";
    echo "<div class='code'>";
    echo "-- Copy and paste this EXACT command:<br><br>";
    echo "USE deal_machan;<br>";
    echo "UPDATE users SET password_hash = '{$expectedHash}' WHERE id = {$user['id']};<br>";
    echo "SELECT 'UPDATED' as status, password_hash FROM users WHERE id = {$user['id']};<br>";
    echo "</div>";
    
    // Check database configuration
    echo "<h3>7. Database Configuration Check</h3>";
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    echo "<tr><td>DB Host</td><td>" . getenv('DB_HOST') . "</td></tr>";
    echo "<tr><td>DB Name</td><td>" . getenv('DB_NAME') . "</td></tr>";
    echo "<tr><td>DB User</td><td>" . getenv('DB_USER') . "</td></tr>";
    echo "<tr><td>Connected DB</td><td>" . $pdo->query("SELECT DATABASE()")->fetchColumn() . "</td></tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h2>❌ Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='diagnose-login.php'>Run Full Diagnostic</a> | <a href='index.php'>Go to Login</a></p>";
?>
