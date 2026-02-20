-- ============================================
-- Fix Admin Login Issues
-- ============================================
-- This script fixes common admin login issues
-- Run this in your MySQL/MariaDB client
-- ============================================

USE deal_machan;

-- Step 1: Check current state
SELECT '=== CURRENT USER STATE ===' as '';
SELECT 
    id,
    email,
    user_type,
    status,
    SUBSTRING(password_hash, 1, 30) as password_hash_preview,
    LENGTH(password_hash) as hash_length,
    created_at,
    last_login
FROM users 
WHERE email = 'admin@dealmachan.com';

-- Step 2: Check admin profile
SELECT '=== CURRENT ADMIN PROFILE ===' as '';
SELECT 
    a.id as admin_id,
    a.user_id,
    a.admin_type,
    a.permissions_json,
    a.city_id,
    a.created_at
FROM admins a
JOIN users u ON a.user_id = u.id
WHERE u.email = 'admin@dealmachan.com';

-- Step 3: Fix user record (if needed)
-- This ensures the user exists with correct credentials
INSERT INTO users (id, email, phone, password_hash, user_type, status, created_at)
VALUES (
    1,
    'admin@dealmachan.com',
    '9999999999',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active',
    NOW()
)
ON DUPLICATE KEY UPDATE
    password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    user_type = 'admin',
    status = 'active',
    updated_at = NOW();

-- Step 4: Fix admin profile (if needed)
-- This ensures the admin profile exists
INSERT INTO admins (id, user_id, admin_type, permissions_json, created_at)
VALUES (
    1,
    1,
    'super_admin',
    '["all"]',
    NOW()
)
ON DUPLICATE KEY UPDATE
    admin_type = 'super_admin',
    permissions_json = '["all"]',
    updated_at = NOW();

-- Step 5: Verify the fix
SELECT '=== VERIFICATION - USER ===' as '';
SELECT 
    id,
    email,
    user_type,
    status,
    CASE 
        WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN '✓ CORRECT'
        ELSE '✗ WRONG'
    END as password_status,
    CASE 
        WHEN user_type = 'admin' THEN '✓ CORRECT'
        ELSE '✗ WRONG'
    END as user_type_status,
    CASE 
        WHEN status = 'active' THEN '✓ CORRECT'
        ELSE '✗ WRONG'
    END as status_check
FROM users 
WHERE email = 'admin@dealmachan.com';

SELECT '=== VERIFICATION - ADMIN ===' as '';
SELECT 
    a.id as admin_id,
    a.user_id,
    a.admin_type,
    CASE 
        WHEN a.admin_type = 'super_admin' THEN '✓ CORRECT'
        ELSE '✗ WRONG'
    END as admin_type_status,
    a.permissions_json,
    u.email
FROM admins a
JOIN users u ON a.user_id = u.id
WHERE u.email = 'admin@dealmachan.com';

-- Step 6: Show summary
SELECT '=== SUMMARY ===' as '';
SELECT 
    CASE 
        WHEN (
            SELECT COUNT(*) 
            FROM users 
            WHERE email = 'admin@dealmachan.com' 
            AND user_type = 'admin' 
            AND status = 'active'
            AND password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
        ) = 1 
        AND (
            SELECT COUNT(*) 
            FROM admins a
            JOIN users u ON a.user_id = u.id
            WHERE u.email = 'admin@dealmachan.com'
            AND a.admin_type = 'super_admin'
        ) = 1
        THEN '✓ ALL CHECKS PASSED - Login should work now!'
        ELSE '✗ Some issues remain - Check the verification results above'
    END as final_status;

-- ============================================
-- CREDENTIALS TO USE:
-- Email: admin@dealmachan.com
-- Password: Admin@123
-- ============================================

-- ============================================
-- NOTES:
-- 1. The password hash corresponds to: Admin@123
-- 2. After running this script, try logging in
-- 3. Check logs/app.log for detailed login flow
-- 4. Run diagnose-login.php for web-based diagnostics
-- ============================================
