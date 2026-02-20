<?php
/**
 * Check Current Password Hash
 * This shows what password hash is currently in the database
 */

// Define basic paths
$rootPath = dirname(__DIR__);
$configPath = $rootPath . '/config';

// Load environment and constants
require_once $configPath . '/env.php';
require_once $configPath . '/constants.php';

// Define paths
define('CORE_PATH', ROOT_PATH . '/core');

// Load required classes
require_once CORE_PATH . '/Database.php';

echo "<h1>Password Hash Check</h1>";
echo "<hr>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    $stmt = $pdo->prepare("SELECT id, email, password_hash, user_type, status FROM users WHERE email = ?");
    $stmt->execute(['admin@dealmachan.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<h2>Current Database Values</h2>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>User ID</td><td>{$user['id']}</td></tr>";
        echo "<tr><td>Email</td><td>{$user['email']}</td></tr>";
        echo "<tr><td>User Type</td><td>{$user['user_type']}</td></tr>";
        echo "<tr><td>Status</td><td>{$user['status']}</td></tr>";
        echo "<tr><td>Password Hash</td><td style='font-family: monospace; font-size: 11px;'>{$user['password_hash']}</td></tr>";
        echo "<tr><td>Hash Length</td><td>" . strlen($user['password_hash']) . "</td></tr>";
        echo "</table>";
        
        echo "<h2>Expected Values</h2>";
        $expectedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Password</td><td>Admin@123</td></tr>";
        echo "<tr><td>Expected Hash</td><td style='font-family: monospace; font-size: 11px;'>{$expectedHash}</td></tr>";
        echo "<tr><td>Hash Length</td><td>" . strlen($expectedHash) . "</td></tr>";
        echo "</table>";
        
        echo "<h2>Comparison</h2>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
        echo "<tr><th>Check</th><th>Result</th></tr>";
        
        $hashMatch = ($user['password_hash'] === $expectedHash);
        echo "<tr><td>Hash Match</td><td style='color: " . ($hashMatch ? 'green' : 'red') . "; font-weight: bold;'>";
        echo $hashMatch ? "✓ MATCH" : "✗ NO MATCH";
        echo "</td></tr>";
        
        // Test password verification with current hash
        $testPassword = 'Admin@123';
        $currentVerify = password_verify($testPassword, $user['password_hash']);
        echo "<tr><td>Current Hash Verifies 'Admin@123'</td><td style='color: " . ($currentVerify ? 'green' : 'red') . "; font-weight: bold;'>";
        echo $currentVerify ? "✓ YES" : "✗ NO";
        echo "</td></tr>";
        
        // Test password verification with expected hash
        $expectedVerify = password_verify($testPassword, $expectedHash);
        echo "<tr><td>Expected Hash Verifies 'Admin@123'</td><td style='color: " . ($expectedVerify ? 'green' : 'red') . "; font-weight: bold;'>";
        echo $expectedVerify ? "✓ YES" : "✗ NO";
        echo "</td></tr>";
        
        echo "</table>";
        
        echo "<h2>Character-by-Character Comparison</h2>";
        echo "<pre style='background: #f5f5f5; padding: 10px; font-family: monospace; font-size: 12px;'>";
        echo "Position | Current | Expected | Match\n";
        echo "---------|---------|----------|------\n";
        
        $maxLen = max(strlen($user['password_hash']), strlen($expectedHash));
        $firstDiff = -1;
        
        for ($i = 0; $i < $maxLen; $i++) {
            $current = isset($user['password_hash'][$i]) ? $user['password_hash'][$i] : '(end)';
            $expected = isset($expectedHash[$i]) ? $expectedHash[$i] : '(end)';
            $match = ($current === $expected) ? '✓' : '✗';
            
            if ($match === '✗' && $firstDiff === -1) {
                $firstDiff = $i;
            }
            
            // Only show first 10 chars and around differences
            if ($i < 10 || ($firstDiff !== -1 && abs($i - $firstDiff) < 5) || $i >= $maxLen - 5) {
                printf("%8d | %7s | %8s | %5s\n", $i, $current, $expected, $match);
            } elseif ($i === 10 && $firstDiff > 15) {
                echo "   ...   |   ...   |   ...    |  ...\n";
            }
        }
        echo "</pre>";
        
        if (!$hashMatch) {
            echo "<h2 style='color: red;'>⚠️ Action Required</h2>";
            echo "<p style='font-size: 16px;'><strong>The password hash in the database is WRONG.</strong></p>";
            echo "<p>Run this SQL command to fix it:</p>";
            echo "<pre style='background: #ffe6e6; padding: 15px; border: 2px solid red;'>";
            echo "UPDATE users \n";
            echo "SET password_hash = '{$expectedHash}'\n";
            echo "WHERE email = 'admin@dealmachan.com';\n";
            echo "</pre>";
            echo "<p>Or run the file: <strong>fix-password-simple.sql</strong></p>";
        } else {
            echo "<h2 style='color: green;'>✓ Password Hash is Correct</h2>";
            echo "<p>The password hash matches. If login still fails, check other issues.</p>";
        }
        
    } else {
        echo "<p style='color: red;'>User not found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='diagnose-login.php'>Run Full Diagnostic</a> | <a href='index.php'>Go to Login</a></p>";
?>
