# 🚀 Simple Database Setup - FIXED!

## Import Instructions

1. **Open phpMyAdmin**: http://localhost/phpmyadmin
2. **Login**: Username: `root`, Password: (leave empty)
3. **Click "Import" tab** (don't create database manually)
4. **Choose file**: `database.sql`
5. **Click "Go"**

✅ **Now works even if tables exist!**

## What You Get

- ✅ Complete database with 8 tables
- ✅ Sample users (mentors and students)
- ✅ 15 skills across different categories
- ✅ User-skill relationships
- ✅ **Handles existing data gracefully**
- ✅ Ready to use immediately

## Test Accounts

**Admin**: admin@mentorconnect.com  
**Mentor**: john@mentorconnect.com  
**Student**: mike@student.com  
**Password for all**: password

## Database Configuration

Update `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mentorconnect');
```

## ✅ Fixed Issues

- Added `IF NOT EXISTS` to all table creation
- Added `INSERT IGNORE` to all data insertion
- Works even with existing partial database
- No more "table already exists" errors
