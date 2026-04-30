# Quick Start Guide

Welcome to the **Student Management System (SMS)**! Get up and running in just 3 steps.

---

## 🚀 Step 1: Prepare the Database

Ensure your `.env` file contains the correct database credentials:

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=your_mysql_password
DB_NAME=sms_db
```

> 💡 Not sure where `.env` is? Check the project root directory. If it doesn't exist, copy `.env.example` and fill in your values.

---

## 📖 Step 2: Open the Application

1. Start your web server (e.g., XAMPP, WAMP, or LAMP)
2. Open your browser and go to: **`http://localhost/Student_Like_MIS/`**
3. You'll see the **Admin Setup Page** (only shown on first-time installation)

---

## 👨‍💼 Step 3: Create Your First Admin Account

Fill out the form on the setup page:

| Field         | Example          |
| ------------- | ---------------- |
| **Full Name** | John Doe         |
| **Username**  | johndoe          |
| **Email**     | john@school.edu  |
| **Password**  | MySecure@Pass123 |

Click **"Create Admin Account"** → Done! ✅

> ⚠️ **Important:** This setup page automatically disappears after the first admin is created and cannot be accessed again. Only authenticated admins can create additional admin accounts through the **Admin Management** page.

---

## 🔐 After Setup

### Log In

- Use your username and password to log in

### Create Admin Accounts

- Click **Admin Management** in the dashboard sidebar
- Only existing admins can create new admin accounts
- The username "admin" is reserved and cannot be used by students

### Manage Students

- Use the **Manage Students** section to add, edit, or delete student accounts

---

## 📧 Email Setup (Optional)

To enable password reset and email notifications:

1. **Get a Gmail App Password:**
   - Go to: https://myaccount.google.com/apppasswords
   - Select "Mail" and "Windows Computer"
   - Copy the 16-character password

2. **Update your `.env` file:**

   ```env
   SMTP_USER=your_email@gmail.com
   SMTP_PASS=your_16_char_app_password
   SMTP_FROM_EMAIL=your_email@gmail.com
   ```

3. **Test it:** Try sending a test email to a student account

---

## 🔒 Security Highlights

✅ Admin setup is one-time only — Cannot be re-run after initial setup  
✅ Username "admin" is reserved for admins only — Students cannot use it  
✅ All passwords are hashed — Never stored in plain text  
✅ Admin account creation is protected — Only authenticated admins can create new admins  
✅ Action logging — All admin activities are recorded

---

## ❓ Need Help?

- **Troubleshooting:** See [ENV_SETUP.md](ENV_SETUP.md)
- **Database errors?** Check that MySQL is running
- **Connection issues?** Verify your `.env` credentials match your MySQL setup

---

**Generated:** April 2026 | **Author:** Elyse Joyeux  
**Version:** 2.0 | **Last Updated:** April 30, 2026
