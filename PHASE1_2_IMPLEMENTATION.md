# PHASE 1 & 2 IMPLEMENTATION GUIDE

**Date:** 2026-06-17  
**Status:** ✅ COMPLETED

---

## What Was Done

### PHASE 1: Database Schema Updates ✅

Created comprehensive migration script: `scripts/migration_phase1.sql`

**Changes Applied:**
- ✅ Added `role` field to `tbluser` (user, seller, admin)
- ✅ Added `created_at` timestamp to `tbluser`
- ✅ Added `brand` and `stock` fields to `tblclothes`
- ✅ Added foreign key constraints for referential integrity
- ✅ Added unique constraints on `email` and `username`
- ✅ Created `tblorderline` table (order items)
- ✅ Created `tblcart` table (shopping cart)
- ✅ Created `tblseller` table (seller profiles)
- ✅ Added order management fields (`order_date`, `status`, `payment_status`)
- ✅ Added performance indexes

### PHASE 2: Authentication System Security ✅

**File: `pages/login.php`**
- ✅ Removed plaintext password fallback for users
- ✅ Removed plaintext password fallback for admins
- ✅ Now uses ONLY `password_verify()` for validation
- Old: `($user["password"] === $password) || password_verify($password, $user["password"])`
- New: `password_verify($password, $user["password"])`

**File: `pages/register.php`**
- ✅ Changed new user status from `'active'` to `'pending'`
- New users now require admin verification before access
- Aligns with POE rubric requirement for user verification workflow

---

## How to Apply These Changes

### Step 1: Apply Database Migration

**Option A: Using phpMyAdmin (Recommended for beginners)**

1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Select database: `ClothingStore`
3. Click "SQL" tab at top
4. Copy all content from `scripts/migration_phase1.sql`
5. Paste into the SQL editor
6. Click "Go" to execute

**Option B: Using MySQL Command Line**

```bash
cd c:\Users\Clari\Documents\GitHub\Pastimes
mysql -u root -p ClothingStore < scripts/migration_phase1.sql
```

**Option C: Verify Changes in PHP**

Run a quick verification script to check the tables were created:

```php
<?php
include("config/DBConn.php");

// Check new tables exist
$tables = ["tblorderline", "tblcart", "tblseller"];
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✓ Table $table exists\n";
    } else {
        echo "✗ Table $table NOT found\n";
    }
}

// Check tbluser has role column
$result = mysqli_query($conn, "SHOW COLUMNS FROM tbluser LIKE 'role'");
if (mysqli_num_rows($result) > 0) {
    echo "✓ tbluser.role column exists\n";
} else {
    echo "✗ tbluser.role column NOT found\n";
}
?>
```

### Step 2: Test Authentication Changes

**Test 1: Login with existing user (will fail - needs rehashing)**

1. Go to: http://localhost/Pastimes/pages/login.php
2. Try existing test account: `john123 / 123456`
3. **Expected Result:** ❌ FAILS (password is still plaintext in DB)
4. **Why:** New code uses `password_verify()`, but DB still has plaintext

**Test 2: Register new user**

1. Go to: http://localhost/Pastimes/pages/register.php
2. Register new account: `testuser / Test@1234!`
3. Click "Register"
4. **Expected Result:** ✅ Redirects to login with "Account created successfully" message
5. Try logging in immediately
6. **Expected Result:** ❌ FAILS with "Account not verified"
7. **Why:** Status is now `'pending'` instead of `'active'`

---

## Database Rehashing Required

### Issue: Existing Users Can't Login

**Problem:** Old users have plaintext passwords. New code only accepts hashed passwords.

**Solution: Run password rehashing script**

Create `scripts/rehash_passwords.php`:

```php
<?php
include("../config/DBConn.php");

// Get all users with plaintext passwords
$result = mysqli_query($conn, "SELECT user_id, password FROM tbluser WHERE password NOT LIKE '$2y$10$%'");

if (mysqli_num_rows($result) > 0) {
    echo "Found " . mysqli_num_rows($result) . " users to rehash\n";
    
    while ($row = mysqli_fetch_assoc($result)) {
        $userId = $row["user_id"];
        $plainPassword = $row["password"];
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $updateStmt = mysqli_prepare($conn, "UPDATE tbluser SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $userId);
        
        if (mysqli_stmt_execute($updateStmt)) {
            echo "✓ User #$userId rehashed\n";
        } else {
            echo "✗ User #$userId FAILED\n";
        }
        mysqli_stmt_close($updateStmt);
    }
    
    echo "Done! All passwords are now hashed.\n";
} else {
    echo "No plaintext passwords found.\n";
}

mysqli_close($conn);
?>
```

**Run it:**
- Browse to: http://localhost/Pastimes/scripts/rehash_passwords.php
- Or run from command line: `php scripts/rehash_passwords.php`

---

## Verification Checklist

- [ ] Database migration applied (check phpMyAdmin for new tables)
- [ ] `tbluser` has `role` and `created_at` columns
- [ ] `tblclothes` has `brand` and `stock` columns
- [ ] `tblorderline`, `tblcart`, `tblseller` tables exist
- [ ] login.php no longer accepts plaintext passwords
- [ ] New registrations set status to `'pending'`
- [ ] Test user cannot login until admin verifies them
- [ ] Passwords rehashed for existing users
- [ ] Test admin login still works (after rehashing tbladmin passwords)

---

## What's Next (PHASE 3)

After these database and auth fixes are applied:

1. **Test existing test data:**
   - Rehash tbladmin passwords: `admin123 / admin123` → hashed
   - Verify all seed users work

2. **Begin shopping cart implementation:**
   - Create `pages/add_to_cart.php`
   - Create `pages/cart_api.php` (AJAX endpoints)
   - Modify `pages/checkout.php` to use new cart backend

3. **Begin order processing:**
   - Create `pages/process_checkout.php`
   - Implement order creation from cart
   - Implement orderline insertion
   - Implement stock decrement

---

## Important Notes

⚠️ **Admin Login Issue:**
- `tbladmin` table still has plaintext passwords
- After rehashing `tbluser`, also rehash `tbladmin`:

```php
$result = mysqli_query($conn, "SELECT admin_id, username, password FROM tbladmin");
while ($row = mysqli_fetch_assoc($result)) {
    $hashedPassword = password_hash($row["password"], PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "UPDATE tbladmin SET password = ? WHERE admin_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $row["admin_id"]);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
```

⚠️ **Backward Compatibility:**
- Old plaintext passwords no longer work
- Users must be rehashed or reset password
- Consider adding "Forgot Password" feature

✅ **Security Improvement:**
- Plaintext password acceptance removed
- User verification workflow implemented
- Database now enforces referential integrity
- New tables ready for cart/order system

---

## Files Modified

- ✅ `pages/login.php` - Security fix (plaintext passwords removed)
- ✅ `pages/register.php` - UX fix (new users pending verification)
- ✅ `scripts/migration_phase1.sql` - NEW (database schema updates)

## Time Estimate

- Database migration: 5 minutes (run SQL)
- Password rehashing: 5 minutes (run script)
- Testing: 15 minutes
- **Total: ~25 minutes**

---

**Status:** Ready for Phase 3 (Shopping Cart Implementation)
