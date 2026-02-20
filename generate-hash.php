<?php
// Generate proper password hash for Admin@123
$password = 'Admin@123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Password: " . $password . "\n";
echo "Generated Hash: " . $hash . "\n";

// Test verification
$verify = password_verify($password, $hash);
echo "Verification: " . ($verify ? 'SUCCESS' : 'FAILED') . "\n";
?>