# Inventory Management System - Setup & Testing Guide

## ✅ SETUP COMPLETE

Authentication system has been implemented with:

- ✅ Login form with validation
- ✅ PDO prepared statements (secure SQL)
- ✅ Password verification using password_hash/password_verify
- ✅ Session management
- ✅ Route protection middleware
- ✅ Logout functionality
- ✅ Protected dashboard

---

## 🔧 DATABASE SETUP (REQUIRED)

Before testing, you MUST set up the database:

### Option 1: Using phpMyAdmin

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Go to "SQL" tab
3. Copy and paste content from `DATABASE_SETUP.sql`
4. Click "Go"

### Option 2: Using MySQL Command Line

```bash
mysql -u root -p < DATABASE_SETUP.sql
```

### What gets created:

- Database: `smart_inventory`
- Table: `users` with admin account
  - Username: `admin`
  - Password: `admin123`
- Table: `products` (ready for future use)

---

## 🚀 RUNNING THE SYSTEM

1. Place project in your web root (e.g., `htdocs/` for XAMPP)
2. Start Apache and MySQL
3. Visit: `http://localhost/Inventory_Management/index.php`

---

## 🧪 AUTHENTICATION TESTING CHECKLIST

### Test 1: ❌ Wrong Password

**Action:**

- Go to login page
- Enter: `admin` / `wrongpassword`
- Click Login

**Expected Result:**

- ❌ Error message appears: "Invalid username or password"
- ❌ NOT redirected to dashboard

**Status:** ✅ PASS / ❌ FAIL

---

### Test 2: ✅ Correct Password

**Action:**

- Go to login page
- Enter: `admin` / `admin123`
- Click Login

**Expected Result:**

- ✅ Dashboard loads
- ✅ Shows "Welcome, admin"
- ✅ Menu appears with "Manage Products" and "Add Product"

**Status:** ✅ PASS / ❌ FAIL

---

### Test 3: ✅ Route Protection

**Action:**

- Without logging in, directly visit:
  - `http://localhost/Inventory_Management/dashboard.php`
  - `http://localhost/Inventory_Management/products/list.php`

**Expected Result:**

- ✅ Redirects to login page automatically
- ✅ Does NOT load the page

**Status:** ✅ PASS / ❌ FAIL

---

### Test 4: ✅ Logout

**Action:**

- Login successfully
- Click "Logout" button
- Use browser back button
- Refresh page

**Expected Result:**

- ✅ Redirected to login page
- ✅ Back button does NOT access dashboard
- ✅ Session destroyed

**Status:** ✅ PASS / ❌ FAIL

---

### Test 5: ✅ Empty Fields

**Action:**

- Go to login page
- Leave username/password empty
- Click Login

**Expected Result:**

- ❌ Error message: "Please fill all fields"
- ❌ No database query runs

**Status:** ✅ PASS / ❌ FAIL

---

## 📋 TROUBLESHOOTING

### Problem: "Database connection failed"

**Solution:**

- Check `config/db.php` settings match your MySQL credentials
- Ensure database `smart_inventory` exists
- Verify MySQL is running

### Problem: "Invalid username or password" always shows

**Solution:**

- Verify database was set up correctly
- Check admin user exists: In phpMyAdmin, browse `users` table
- Make sure you're using `admin123` as password

### Problem: Redirect doesn't work after login

**Solution:**

- Check session is enabled in PHP
- Ensure `headers()` not already sent
- Verify `dashboard.php` includes middleware correctly

---

## 🔒 SECURITY NOTES

✅ Using prepared statements (prevents SQL injection)
✅ Using password_hash/password_verify (secure hashing)
✅ Session-based authentication (not stored in URL)
✅ XSS protection with htmlspecialchars()
✅ Middleware route protection (access control)

---

## ✅ ALL TESTS PASSED?

If all 5 tests pass, authentication is complete and working!

Next steps:

- Implement product CRUD operations
- Add database records
- Enhance UI as needed
