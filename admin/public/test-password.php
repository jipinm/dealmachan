<?php
// Test password verification script

$password = 'Admin@123';
$stored_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password verification:\n";
echo "Password: " . $password . "\n";
echo "Stored Hash: " . $stored_hash . "\n";
echo "Verification Result: " . (password_verify($password, $stored_hash) ? 'SUCCESS' : 'FAILED') . "\n";

// Generate a new hash for comparison
$new_hash = password_hash($password, PASSWORD_DEFAULT);
echo "New Hash: " . $new_hash . "\n";
echo "New Hash Verification: " . (password_verify($password, $new_hash) ? 'SUCCESS' : 'FAILED') . "\n";
?>