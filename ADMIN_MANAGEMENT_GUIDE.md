# Admin Management - User Guide

## Overview

The **Admin Management** page is a dedicated interface for creating and managing admin accounts. It's accessible only to authenticated administrators and provides a secure way to add new admins to the system.

---

## Accessing Admin Management

### From Dashboard

1. Log in to your admin account
2. Click **"Admin Management"** in the left sidebar (at the bottom, below "System Logs")
3. You'll see the Admin Management page with two main sections

### Direct URL

- Visit: `http://localhost/Student_Like_MIS/admin_management.php`
- Must be logged in as admin (will redirect to login if not)

---

## Creating a New Admin Account

### Step-by-Step Instructions

1. **Fill in the "Create New Admin" Form** (left side):
   - **Full Name:** Administrator's real name (e.g., "Jane Smith")
   - **Username:** Unique username (e.g., "janesmith")
     - Must be 3+ characters
     - No spaces allowed
     - Must be unique (not already used)
   - **Email:** Administrator's email address
     - Must be valid email format
     - Must be unique (not already used)
   - **Password:** Strong password (minimum 6 characters)
     - Recommended: Mix of letters, numbers, and symbols
     - Example: `SecurePass@2026`
   - **Confirm Password:** Re-enter the password (must match exactly)

2. **Click "Create Admin Account"** button

3. **See Success Message**
   - Green success message appears
   - New admin appears in the "Existing Admin Accounts" table

4. **Share Credentials**
   - Share username and password with the new admin
   - Recommend they change their password on first login

---

## Existing Admin Accounts

### Viewing Admins

The right side shows all current admin accounts in a table with:

- **Name:** Administrator's full name
- **Username:** Username for login
- **Created:** Date the account was created
- **Action:** Options to manage the account

### Admin Status Indicators

- **"You"** badge (green): Your current account
- **"Admin"** badge (blue): Other admin accounts

---

## Removing Admin Accounts

### How to Remove an Admin

1. Find the admin in the "Existing Admin Accounts" table
2. Click the **"Remove"** button (trash icon)
3. Confirm deletion when prompted
4. Admin account is permanently deleted

### Important Notes

- ⚠️ **Cannot remove yourself:** You cannot delete your own account from this page
- If you need to be removed, ask another admin
- Removed admins cannot log in anymore
- All their data remains in the system (for audit purposes)
- Action is logged in System Logs

---

## Security Best Practices

### ✅ Do's

- ✅ Create strong passwords (8+ characters with mixed symbols)
- ✅ Use meaningful full names for identification
- ✅ Create admins only when needed
- ✅ Change default credentials on first login
- ✅ Review admin list regularly
- ✅ Check System Logs for admin activity

### ❌ Don'ts

- ❌ Don't share admin credentials via email
- ❌ Don't use simple passwords like "password123"
- ❌ Don't create "test" admin accounts and forget to remove them
- ❌ Don't give admin access to someone you're unsure about
- ❌ Don't use the same password across multiple admins
- ❌ Don't forget to log admin access in your records

---

## Troubleshooting

### Problem: "Username already exists"

- **Cause:** This username is already registered
- **Solution:** Try a different username (e.g., add numbers or initials)

### Problem: "Email already exists"

- **Cause:** This email is already associated with another account
- **Solution:** Use a different email address

### Problem: "Passwords do not match"

- **Cause:** Password and Confirm Password fields don't match
- **Solution:** Re-enter both fields carefully, ensuring they're identical

### Problem: Cannot access Admin Management

- **Cause:** You're not logged in as an admin
- **Solution:** Log in with admin credentials first

### Problem: Cannot remove an admin

- **Cause:** The admin you're trying to remove might be your own account
- **Solution:** Ask another admin to remove you if needed

---

## Audit Trail

### Viewing Admin Actions

All admin account creation and removal actions are automatically logged:

1. Go to **System Logs** in the dashboard
2. Search for admin-related actions:
   - "Created new admin account: [username]"
   - "Removed admin account: [username]"

### What's Recorded

- Who performed the action (current admin)
- What action was performed (create/remove)
- Which admin account was affected
- Exact timestamp of the action

---

## Best Practices for Your Organization

### Initial Setup

1. Create your first admin during installation
2. Add 1-2 backup admins immediately
3. Keep admin accounts secure

### Ongoing Management

1. Review admin list quarterly
2. Remove inactive admins promptly
3. Check logs monthly for any unusual activity
4. Update passwords periodically (encourage 30-90 days)

### Team Communication

1. Document who has admin access
2. Establish a procedure for requesting admin access
3. Set approval process for new admins
4. Plan succession (backup admins)

---

## FAQ

**Q: Can I change an admin's password?**  
A: Currently, no. Only the admin user can change their own password through their profile. You can remove and re-create if needed.

**Q: What happens to data when an admin is removed?**  
A: Data remains intact. Only the login account is deleted. All actions performed by that admin are still logged.

**Q: Can students see the Admin Management page?**  
A: No. Only authenticated admins can access this page. Students are redirected to login.

**Q: Is there a limit to how many admins I can create?**  
A: No technical limit, but consider your organization's needs (typically 2-5 admins per school).

**Q: What if I accidentally remove an admin?**  
A: You can recreate the account with the same username/email. Check your System Logs to see the removal action.

---

## Getting Help

- **General questions:** See [SETUP_GUIDE_NEW.md](SETUP_GUIDE_NEW.md)
- **Setup issues:** Check [ENV_SETUP.md](ENV_SETUP.md)
- **System logs:** Review detailed actions in System Logs dashboard section
- **Security concerns:** Check audit trail in System Logs

---

**Last Updated:** April 30, 2026  
**Version:** 1.0  
**For:** Student Management System v2.0
