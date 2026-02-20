-- ============================================
-- Simple Password Fix for Admin Login
-- ============================================
-- This script simply updates the password hash
-- Run this in your MySQL/MariaDB client
-- ============================================

USE deal_machan;

-- Show current password hash
SELECT 
    '=== BEFORE UPDATE ===' as step,
    id,
    email,
    SUBSTRING(password_hash, 1, 40) as current_hash_preview,
    LENGTH(password_hash) as hash_length
FROM users 
WHERE email = 'admin@dealmachan.com';

-- Update the password hash to the correct one for 'Admin@123'
UPDATE users 
SET 
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    user_type = 'admin',
    status = 'active',
    updated_at = NOW()
WHERE email = 'admin@dealmachan.com';

-- Show updated password hash
SELECT 
    '=== AFTER UPDATE ===' as step,
    id,
    email,
    SUBSTRING(password_hash, 1, 40) as new_hash_preview,
    LENGTH(password_hash) as hash_length,
    user_type,
    status
FROM users 
WHERE email = 'admin@dealmachan.com';

-- Verify the password hash is correct
SELECT 
    '=== VERIFICATION ===' as step,
    email,
    CASE 
        WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN '✓ PASSWORD HASH IS CORRECT - Login should work!'
        ELSE '✗ PASSWORD HASH IS STILL WRONG'
    END as verification_result,
    CASE 
        WHEN user_type = 'admin' THEN '✓ User type correct'
        ELSE '✗ User type wrong'
    END as user_type_check,
    CASE 
        WHEN status = 'active' THEN '✓ Status correct'
        ELSE '✗ Status wrong'
    END as status_check
FROM users 
WHERE email = 'admin@dealmachan.com';

-- ============================================
-- After running this script:
-- 1. Refresh: http://dealmachan-admin.local/diagnose-login.php
-- 2. Login with:
--    Email: admin@dealmachan.com
--    Password: Admin@123
-- ============================================
