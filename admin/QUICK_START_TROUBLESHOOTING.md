# Quick Start - Login Troubleshooting

## Problem
Login not working with:
- **Email:** admin@dealmachan.com
- **Password:** Admin@123

## Solution - Follow These Steps

### Step 1: Run Diagnostic Tool (IMPORTANT - DO THIS FIRST!)

Open your web browser and navigate to:
```
http://dealmachan-admin.local/diagnose-login.php
```

This will show you exactly what's wrong with the login.

### Step 2: Check the Log File

After running the diagnostic, open this file to see detailed logs:
```
e:\DealMachan\admin\logs\app.log
```

### Step 3: Try to Login

1. Go to: `http://dealmachan-admin.local/`
2. Enter:
   - Email: `admin@dealmachan.com`
   - Password: `Admin@123`
3. Click Login

### Step 4: Check Logs Again

Open `e:\DealMachan\admin\logs\app.log` again to see what happened during your login attempt.

## What the Logs Will Tell You

The logs will show you exactly where the login fails:

### If User Not Found:
```
[WARNING] Login failed - User not found
```
**Fix:** The email doesn't exist in the database. Check the database.

### If Wrong Password:
```
[WARNING] Login failed - Invalid password
```
**Fix:** The password hash in the database doesn't match. Run this SQL:
```sql
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@dealmachan.com';
```

### If Account Inactive:
```
[WARNING] Login failed - Account inactive
```
**Fix:** Run this SQL:
```sql
UPDATE users SET status = 'active' WHERE email = 'admin@dealmachan.com';
```

### If Admin Profile Not Found:
```
[ERROR] Login failed - Admin profile not found
```
**Fix:** Run this SQL (replace USER_ID with the actual user id):
```sql
-- First get the user_id
SELECT id FROM users WHERE email = 'admin@dealmachan.com';

-- Then insert admin record (replace 1 with actual user_id if different)
INSERT INTO admins (user_id, admin_type, permissions_json, created_at)
VALUES (1, 'super_admin', '["all"]', NOW())
ON DUPLICATE KEY UPDATE admin_type = 'super_admin';
```

## Files Created for Troubleshooting

1. **Logger System:** `admin/helpers/Logger.php`
   - Logs all application events

2. **Log File:** `admin/logs/app.log`
   - Contains all login attempts and errors

3. **Diagnostic Tool:** `admin/public/diagnose-login.php`
   - Web-based diagnostic tool to test login

4. **Enhanced Auth:** `admin/core/Auth.php`
   - Now includes detailed logging

5. **Enhanced Controller:** `admin/controllers/AuthController.php`
   - Now includes detailed logging

## Quick Database Check

Run these SQL queries to verify your data:

```sql
-- Check if user exists
SELECT * FROM users WHERE email = 'admin@dealmachan.com';

-- Check if admin profile exists
SELECT a.*, u.email, u.status 
FROM admins a 
JOIN users u ON a.user_id = u.id 
WHERE u.email = 'admin@dealmachan.com';

-- Check password hash
SELECT 
    email, 
    user_type, 
    status, 
    password_hash,
    LENGTH(password_hash) as hash_length
FROM users 
WHERE email = 'admin@dealmachan.com';
```

Expected results:
- User should exist with `user_type = 'admin'` and `status = 'active'`
- Admin profile should exist with `admin_type = 'super_admin'`
- Password hash should be: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`

## Most Common Issues

### 1. Password Hash is Wrong
The hash in the database doesn't match the password "Admin@123".

**Quick Fix:**
```sql
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@dealmachan.com';
```

### 2. Admin Profile Missing
User exists but no admin record.

**Quick Fix:**
```sql
INSERT INTO admins (user_id, admin_type, permissions_json, created_at)
SELECT id, 'super_admin', '["all"]', NOW()
FROM users 
WHERE email = 'admin@dealmachan.com'
ON DUPLICATE KEY UPDATE admin_type = 'super_admin';
```

### 3. Database Connection Issue
Can't connect to database.

**Check:**
- Is MySQL/MariaDB running?
- Check `.env` file for correct database credentials
- Database name should be: `deal_machan`

## Testing Without Browser

If you can't access via browser, you can check the database directly:

```sql
-- Test the password hash
SELECT 
    CASE 
        WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN 'Password hash is CORRECT'
        ELSE 'Password hash is WRONG'
    END as password_status,
    email,
    user_type,
    status
FROM users 
WHERE email = 'admin@dealmachan.com';
```

## Need More Help?

1. Check the full troubleshooting guide: `TROUBLESHOOTING.md`
2. Review the log file: `logs/app.log`
3. Run the diagnostic tool: `http://dealmachan-admin.local/diagnose-login.php`

## Summary

The logging system will now tell you EXACTLY why login is failing. Just:
1. Try to login
2. Check `logs/app.log`
3. Apply the fix based on the error message
4. Try again

All login attempts are now logged with complete details!
