# Admin Login Troubleshooting Guide

## Issue
Login not working with credentials:
- **Email:** admin@dealmachan.com
- **Password:** Admin@123

## Diagnostic Tools Implemented

### 1. Application Logger (`helpers/Logger.php`)
A comprehensive logging system that tracks all login attempts and system operations.

**Log Location:** `logs/app.log`

**Log Levels:**
- `INFO` - General information
- `DEBUG` - Detailed debugging information
- `WARNING` - Warning messages
- `ERROR` - Error messages
- `CRITICAL` - Critical errors

### 2. Enhanced Authentication Logging
The `core/Auth.php` file now includes detailed logging for:
- Login attempt initiation
- Database connection status
- User query results
- Password verification details
- Admin profile lookup
- Session creation
- All error conditions

### 3. Enhanced Controller Logging
The `controllers/AuthController.php` now logs:
- Request method validation
- CSRF token verification
- Form data validation
- Login result handling

### 4. Diagnostic Script (`public/diagnose-login.php`)
A comprehensive diagnostic tool that tests:
1. Database connection
2. User existence in database
3. User type verification
4. User status verification
5. Password hash verification
6. Admin profile existence
7. Full login simulation

## How to Use

### Step 1: Run the Diagnostic Script
1. Open your browser
2. Navigate to: `http://dealmachan-admin.local/diagnose-login.php`
3. Review the diagnostic report

### Step 2: Check Application Logs
1. Open the file: `e:\DealMachan\admin\logs\app.log`
2. Look for recent entries related to login attempts
3. Check for any ERROR or CRITICAL level messages

### Step 3: Attempt Login
1. Navigate to: `http://dealmachan-admin.local/`
2. Enter credentials:
   - Email: admin@dealmachan.com
   - Password: Admin@123
3. Click Login

### Step 4: Review Logs After Login Attempt
1. Check `logs/app.log` for detailed login flow
2. Look for the specific failure point

## Common Issues and Solutions

### Issue 1: Password Hash Mismatch
**Symptoms:** Password verification fails in diagnostic script

**Solution:**
```sql
-- Update the password hash in database
UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@dealmachan.com';
```

### Issue 2: User Type Not 'admin'
**Symptoms:** User found but type is not 'admin'

**Solution:**
```sql
UPDATE users 
SET user_type = 'admin'
WHERE email = 'admin@dealmachan.com';
```

### Issue 3: User Status Not 'active'
**Symptoms:** User found but status is not 'active'

**Solution:**
```sql
UPDATE users 
SET status = 'active'
WHERE email = 'admin@dealmachan.com';
```

### Issue 4: Admin Profile Not Found
**Symptoms:** User exists but no admin profile in admins table

**Solution:**
```sql
-- First, get the user_id
SELECT id FROM users WHERE email = 'admin@dealmachan.com';

-- Then insert admin profile (replace USER_ID with actual id)
INSERT INTO admins (user_id, admin_type, permissions_json, created_at)
VALUES (USER_ID, 'super_admin', '["all"]', NOW());
```

### Issue 5: Database Connection Failed
**Symptoms:** Cannot connect to database

**Check:**
1. MySQL/MariaDB service is running
2. Database credentials in `.env` file are correct
3. Database `deal_machan` exists

### Issue 6: Session Issues
**Symptoms:** Login appears successful but redirects back to login

**Solutions:**
1. Check if sessions are working:
   ```php
   <?php
   session_start();
   $_SESSION['test'] = 'working';
   echo $_SESSION['test'];
   ?>
   ```

2. Check session directory permissions
3. Clear browser cookies
4. Check `config/session.php` settings

### Issue 7: CSRF Token Issues
**Symptoms:** "Invalid security token" error

**Solutions:**
1. Clear browser cookies
2. Refresh the login page
3. Check if sessions are working properly

## Database Schema Reference

### Users Table
```sql
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `user_type` enum('admin','merchant','customer') NOT NULL,
  `status` enum('active','inactive','blocked','pending') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`)
);
```

### Admins Table
```sql
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `admin_type` enum('super_admin','city_admin','sales_admin','promoter_admin','partner_admin','club_admin') NOT NULL,
  `permissions_json` longtext,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_id` (`user_id`)
);
```

## Expected Database Data

### User Record
```sql
INSERT INTO users (id, email, password_hash, user_type, status) VALUES
(1, 'admin@dealmachan.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');
```

### Admin Record
```sql
INSERT INTO admins (id, user_id, admin_type, permissions_json) VALUES
(1, 1, 'super_admin', '["all"]');
```

## Log File Analysis

### Successful Login Flow
```
[TIMESTAMP] [INFO] Login attempt started | Context: {"email":"admin@dealmachan.com"}
[TIMESTAMP] [DEBUG] Database connection established
[TIMESTAMP] [DEBUG] User query executed | Context: {"email":"admin@dealmachan.com","user_found":"yes"}
[TIMESTAMP] [DEBUG] User details retrieved | Context: {"user_id":1,"email":"admin@dealmachan.com","user_type":"admin","status":"active"}
[TIMESTAMP] [DEBUG] Password verification | Context: {"verified":"yes"}
[TIMESTAMP] [DEBUG] Admin query executed | Context: {"user_id":1,"admin_found":"yes"}
[TIMESTAMP] [DEBUG] Admin details retrieved | Context: {"admin_id":1,"admin_type":"super_admin"}
[TIMESTAMP] [INFO] Session created successfully | Context: {"user_id":1,"admin_id":1}
[TIMESTAMP] [INFO] Login successful | Context: {"email":"admin@dealmachan.com","user_id":1,"admin_id":1}
```

### Failed Login - User Not Found
```
[TIMESTAMP] [INFO] Login attempt started | Context: {"email":"admin@dealmachan.com"}
[TIMESTAMP] [DEBUG] Database connection established
[TIMESTAMP] [DEBUG] User query executed | Context: {"email":"admin@dealmachan.com","user_found":"no"}
[TIMESTAMP] [WARNING] Login failed - User not found | Context: {"email":"admin@dealmachan.com"}
```

### Failed Login - Wrong Password
```
[TIMESTAMP] [INFO] Login attempt started | Context: {"email":"admin@dealmachan.com"}
[TIMESTAMP] [DEBUG] Database connection established
[TIMESTAMP] [DEBUG] User query executed | Context: {"email":"admin@dealmachan.com","user_found":"yes"}
[TIMESTAMP] [DEBUG] User details retrieved | Context: {...}
[TIMESTAMP] [DEBUG] Password verification | Context: {"verified":"no"}
[TIMESTAMP] [WARNING] Login failed - Invalid password | Context: {"email":"admin@dealmachan.com"}
```

## Next Steps

1. **Run the diagnostic script** to identify the exact issue
2. **Check the logs** at `logs/app.log` for detailed information
3. **Apply the appropriate fix** based on diagnostic results
4. **Test login again** and verify logs show successful authentication
5. **Review session handling** if login succeeds but doesn't maintain session

## Support

If issues persist after following this guide:
1. Check all log files in `logs/` directory
2. Verify PHP version compatibility (PHP 7.4+ recommended)
3. Check web server error logs
4. Verify file permissions on logs directory
5. Test with a fresh browser session (incognito mode)

## Files Modified

1. `helpers/Logger.php` - New logging utility
2. `core/Auth.php` - Enhanced with detailed logging
3. `controllers/AuthController.php` - Enhanced with detailed logging
4. `logs/app.log` - Application log file
5. `public/diagnose-login.php` - Diagnostic tool
6. `TROUBLESHOOTING.md` - This document
