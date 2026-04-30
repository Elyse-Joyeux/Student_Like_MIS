# Implementation Testing Checklist

## ✅ Registration Tests

### Test 1: Block "admin" username on registration

- [ ] Go to registration page
- [ ] Try to register with username: `admin`
- [ ] Expected: Error message "The username 'admin' is reserved and cannot be used."
- [ ] Try with username: `admin123` (should work)
- [ ] Try with username: `ADMIN` (should be blocked - case-insensitive)
- [ ] Register successfully with a different username

---

## ✅ Admin Management Page Tests

### Test 2: Access control - students cannot access

- [ ] Log in as a student
- [ ] Try to access: `http://localhost/Student_Like_MIS/admin_management.php`
- [ ] Expected: Redirected back to `index.php`

### Test 3: Access control - admins can access

- [ ] Log in as admin (first admin from setup)
- [ ] Go to admin dashboard
- [ ] Verify "Admin Management" link appears in sidebar
- [ ] Click on it → Should open admin_management.php
- [ ] Verify you can see the "Create New Admin" form and existing admins list

### Test 4: Create new admin account

- [ ] From Admin Management page, fill in:
  - Full Name: `Test Admin`
  - Username: `testadmin`
  - Email: `testadmin@test.com`
  - Password: `TestPass123`
  - Confirm Password: `TestPass123`
- [ ] Click "Create Admin Account"
- [ ] Expected: Success message appears
- [ ] New admin should appear in the "Existing Admin Accounts" table
- [ ] Try to log in with new admin credentials

### Test 5: Cannot remove own admin account

- [ ] From Admin Management page, look for "Remove" button
- [ ] Expected: No remove button for current user (marked as "You")
- [ ] Try to access remove function via browser dev tools (should fail safely)

### Test 6: Remove admin account

- [ ] As first admin, remove the "testadmin" account
- [ ] Click "Remove" button
- [ ] Confirm when prompted
- [ ] Expected: Success message, account removed from list
- [ ] Try logging in with removed admin credentials (should fail)

---

## ✅ Setup Page Tests

### Test 7: Setup page accessibility (after first admin exists)

- [ ] Log out from admin account
- [ ] Try to access: `http://localhost/Student_Like_MIS/admin_setup.php`
- [ ] Expected: Redirected to `index.php` (setup page inaccessible)

### Test 8: Admin dashboard navigation

- [ ] Log in as admin
- [ ] Verify "Admin Management" link in sidebar
- [ ] Check sidebar has this order:
  1. Dashboard
  2. Manage Students
  3. Manage Results
  4. Appeals
  5. Report Cards
  6. Announcements
  7. System Logs
  8. **Admin Management** (new separator and icon)

---

## ✅ Audit Logging Tests

### Test 9: Actions are logged

- [ ] Create a new admin account
- [ ] Go to System Logs section in admin dashboard
- [ ] Search for the action "Created new admin account"
- [ ] Expected: Action appears with timestamp and details

### Test 10: Admin removal is logged

- [ ] Remove an admin account
- [ ] Go to System Logs
- [ ] Search for "Removed admin account"
- [ ] Expected: Action appears with username of removed admin

---

## ✅ Documentation Tests

### Test 11: Documentation is accurate

- [ ] Read SETUP_GUIDE_NEW.md
- [ ] Follow the 3-step setup guide
- [ ] Verify all steps work as documented
- [ ] Check email setup instructions (optional section)

### Test 12: README is updated

- [ ] Open README.md
- [ ] Verify Security Features section mentions:
  - Dedicated Admin Management
  - Reserved Username "admin"
  - One-time Admin Setup
  - Audit Logging

---

## 🐛 Edge Cases to Test

### Test 13: SQL injection prevention

- [ ] Try username with SQL: `' OR '1'='1`
- [ ] Expected: Handled safely, account not created

### Test 14: Case-insensitive "admin" check

- [ ] Try registering with: `Admin`, `ADMIN`, `aDmIn`
- [ ] Expected: All blocked with same error message

### Test 15: Duplicate username/email prevention

- [ ] Create admin with email: `test@example.com`
- [ ] Try to register student with same email
- [ ] Expected: Error "Username or email already exists"

### Test 16: Password validation

- [ ] Try creating admin with password < 6 characters
- [ ] Expected: Error message about minimum length
- [ ] Try mismatched password confirmation
- [ ] Expected: Error about passwords not matching

---

## 📋 Sign-Off

- **Tested By:** **********\_**********
- **Date:** **********\_**********
- **All Tests Passed:** ☐ Yes ☐ No

If any tests failed, please document the issue and file a bug report.

---

**Testing Framework:** Manual Testing  
**Version:** 1.0  
**Last Updated:** April 30, 2026
