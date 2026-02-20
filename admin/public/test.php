<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Database.php';

try {
    $db = Database::getInstance();
    echo "<h2>✅ Database Connection Successful!</h2>";
    
    $conn = $db->getConnection();
    $stmt = $conn->query("SELECT VERSION()");
    $version = $stmt->fetch();
    echo "<p>MySQL Version: " . $version['VERSION()'] . "</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM cities");
    $result = $stmt->fetch();
    echo "<p>Cities in database: " . $result['count'] . "</p>";
    
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p>You can now start developing Module 1.</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
