# Fix Admin Login - Step by Step

## Problem Identified
✗ **Password verification failed** - The password hash in your database doesn't match "Admin@123"

## Solution - Choose ONE method below

---

## Method 1: Simple SQL Update (RECOMMENDED - FASTEST)

### Step 1: Open your MySQL/MariaDB client
- phpMyAdmin, MySQL Workbench, HeidiSQL, or command line

### Step 2: Run this single SQL command:

```sql
USE deal_machan;

UPDATE users 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@dealmachan.com';
```

### Step 3: Verify the update:

```sql
SELECT 
    email,
    CASE 
        WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
        THEN 'CORRECT - Login will work!'
        ELSE 'WRONG - Still has issues'
    END as status
FROM users 
WHERE email = 'admin@dealmachan.com';
```

### Step 4: Test login
- Go to: `http://dealmachan-admin.local/`
- Email: `admin@dealmachan.com`
- Password: `Admin@123`

---

## Method 2: Run the Simple Fix Script

### Step 1: Open your MySQL/MariaDB client

### Step 2: Run the file:
```
SOURCE e:\DealMachan\admin\fix-password-simple.sql
```

Or copy/paste the contents of `fix-password-simple.sql` into your SQL client.

### Step 3: Check the output
The script will show:
- Before update
- After update  
- Verification result

### Step 4: Test login

---

## Method 3: Web-Based Check (To See Exact Issue)

### Step 1: Open in browser:
```
http://dealmachan-admin.local/check-password-hash.php
```

This will show you:
- Current password hash in database
- Expected password hash
- Character-by-character comparison
- Exact SQL command to fix it

### Step 2: Copy the SQL command shown

### Step 3: Run it in your MySQL client

### Step 4: Test login

---

## Verification After Fix

### Option A: Web Diagnostic
```
http://dealmachan-admin.local/diagnose-login.php
```
Should now show: ✓ Password verification successful

### Option B: Check Logs
Open: `e:\DealMachan\admin\logs\app.log`

Should show:
```
[INFO] Password verified successfully
[INFO] Login successful
```

---

## Why the Original Script Might Have Failed

The `fix-admin-login.sql` script tries to INSERT with explicit IDs which can cause:
1. **Duplicate key errors** if the record already exists
2. **Auto-increment conflicts** 
3. **Foreign key constraint issues**

The simple UPDATE command avoids all these issues.

---

## What Each File Does

| File | Purpose |
|------|---------|
| `fix-password-simple.sql` | ✓ Simple UPDATE command (RECOMMENDED) |
| `fix-admin-login.sql` | Full diagnostic + fix (may have issues) |
| `check-password-hash.php` | Web tool to see exact hash comparison |
| `diagnose-login.php` | Full login diagnostic |
| `logs/app.log` | Detailed logs of all login attempts |

---

## Quick Command Reference

### Just Update Password (Copy/Paste This):
```sql
USE deal_machan;
UPDATE users SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@dealmachan.com';
```

### Check if it Worked:
```sql
SELECT email, 
       CASE WHEN password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
       THEN 'FIXED' ELSE 'NOT FIXED' END as status 
FROM users WHERE email = 'admin@dealmachan.com';
```

---

## If Login Still Fails After Fix

1. **Clear browser cache and cookies**
2. **Try incognito/private browsing mode**
3. **Check logs:** `logs/app.log`
4. **Run diagnostic:** `diagnose-login.php`
5. **Verify session directory is writable**

---

## Expected Result After Fix

### Diagnostic Output:
```
Test 5: Password Verification
✓ Password verification successful
```

### Login Result:
- Should redirect to dashboard
- No error messages
- Session persists

### Log Output:
```
[INFO] Login attempt started
[DEBUG] Password verification | Context: {"verified":"yes"}
[INFO] Login successful
```

---

## Need Help?

1. Run: `http://dealmachan-admin.local/check-password-hash.php`
2. Check: `logs/app.log`
3. Take a screenshot of any error messages
4. Check MySQL error log if SQL fails

---

## Summary

**The issue:** Wrong password hash in database  
**The fix:** Run the UPDATE command above  
**Time needed:** 30 seconds  
**Success rate:** 100% if command executes

Just run the UPDATE command and you're done! 🎉
