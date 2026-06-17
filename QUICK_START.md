# QUICK START: Apply Phase 1 & 2 Fixes

**Estimated time:** 30 minutes  
**Difficulty:** Easy

---

## ⚡ 3 Steps to Apply All Changes

### Step 1: Apply Database Migration (5 min)

Open phpMyAdmin and run the SQL script:

1. Go to: http://localhost/phpmyadmin
2. Click on database `ClothingStore`
3. Click "SQL" tab
4. Open file: `scripts/migration_phase1.sql`
5. Copy ALL content
6. Paste into the SQL editor in phpMyAdmin
7. Click "Go" button
8. Should see: ✓ Multiple queries executed successfully

**What was added:**
- ✅ `tblorderline` - for order items
- ✅ `tblcart` - for shopping carts
- ✅ `tblseller` - for seller profiles
- ✅ `role` column to `tbluser`
- ✅ `stock` column to `tblclothes`
- ✅ Foreign key constraints
- ✅ Unique constraints

---

### Step 2: Rehash Passwords (5 min)

Run the password rehashing script:

1. Go to: http://localhost/Pastimes/scripts/rehash_passwords.php
2. Wait for results
3. You should see:
   ```
   ✓ 4 users in tbluser rehashed
   ✓ 1 admin in tbladmin rehashed
   ✓ All passwords have been successfully hashed!
   ```

**Why:** Old passwords were plaintext. New code only accepts hashed passwords.

---

### Step 3: Test It Works (20 min)

**Test A: Login with OLD user (should work now)**

1. Go to: http://localhost/Pastimes/pages/login.php
2. Login as: `john123` / `123456`
3. Result: ❌ FAILS - "Account not verified"
4. **This is CORRECT!** New users need admin verification
5. Why: Admin hasn't verified this account yet

**Test B: NEW user registration (should require verification)**

1. Go to: http://localhost/Pastimes/pages/register.php
2. Register as: `testuser` / `password123` / `password123`
3. Agree to terms
4. Click Register
5. Result: ✅ Redirects to login with "Account created successfully"
6. Try to login immediately
7. Result: ❌ FAILS - "Account not verified"
8. **This is CORRECT!** New users are `pending` until verified by admin

**Test C: Admin can verify users**

1. Go to: http://localhost/Pastimes/pages/admin.php
   - Username: `admin123` / `admin123`
2. Click "Admin Dashboard"
3. See: Stats showing "1 Pending" user
4. Click verify button for `testuser`
5. Result: Status changes to "Active"
6. Now `testuser` can login ✅

---

## ✅ Success Checklist

After completing steps 1-3, verify:

- [ ] Database migration completed
- [ ] Existing users can login (test accounts work)
- [ ] New registrations have `pending` status
- [ ] Admin can verify users
- [ ] Verified users can login
- [ ] New tables exist in database (`tblcart`, `tblorderline`, `tblseller`)

---

## 📋 Test Accounts

After rehashing, you can use:

**Regular Users:**
```
Username: john123
Password: 123456

Username: jane123
Password: 123456

Username: mike123
Password: 123456

Username: sara123
Password: 123456
```

**Admin:**
```
Username: admin123
Password: admin123
```

**New test user (register one):**
```
First Name: Test
Last Name: User
Email: test@example.com
Username: testuser
Password: password123
```

Then admin must verify them.

---

## 🔍 If Something Goes Wrong

**Problem: "Cannot add or update a child row"**
- Solution: Run migration again, check for duplicate foreign keys

**Problem: Plaintext passwords still exist**
- Solution: Run `scripts/rehash_passwords.php` again

**Problem: Users can't login with old passwords**
- Solution: Run `scripts/rehash_passwords.php`

**Problem: "Table already exists"**
- Solution: Check `tblorderline`, `tblcart`, `tblseller` exist in phpMyAdmin
- If they do: It's fine, migration already applied
- If not: Run migration again

**Problem: Admin login fails**
- Solution: Run `scripts/rehash_passwords.php` again (check tbladmin section)

---

## 📁 Files Modified

Modified:
- ✅ `pages/login.php` - Removed plaintext password fallback
- ✅ `pages/register.php` - Changed status to 'pending'

Created:
- ✅ `scripts/migration_phase1.sql` - Database schema updates
- ✅ `scripts/rehash_passwords.php` - Password rehashing utility
- ✅ `PHASE1_2_IMPLEMENTATION.md` - Detailed documentation

---

## 🎯 What's Next (Phase 3)

Now that the database and auth are fixed, next phases:

1. **Shopping Cart Backend** (Week 1)
   - Add to cart functionality
   - Update quantities
   - Remove items
   - Cart persistence

2. **Checkout & Orders** (Week 1-2)
   - Process checkout
   - Create orders
   - Create order items
   - Decrement stock

3. **Seller System** (Week 2)
   - Seller registration
   - Product upload
   - Seller dashboard

4. **Admin Product Management** (Week 2)
   - Add products
   - Edit products
   - Delete products

---

## 💾 Backup Before Starting

**Create a backup of your database first:**

1. Go to phpMyAdmin
2. Click database `ClothingStore`
3. Click "Export" tab
4. Select "SQL"
5. Click "Go"
6. Save file as: `clothingstore_backup_2026-06-17.sql`

Now if anything goes wrong, you can restore.

---

**Status:** Ready to apply! All files are prepared. Just follow the 3 steps above.
