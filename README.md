# Student Management System (SMS)

A comprehensive web-based Student Management System built with PHP and MySQL. This system enables administrators and students to manage academic information, results, and communications efficiently.

![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-green)
![License](https://img.shields.io/badge/License-MIT-yellow)

## Features

### 👨‍💼 Admin Dashboard

- Manage student records and accounts
- Add, edit, and delete student information
- Manage academic results and grades
- View system logs and user activities
- Handle mark appeals and claims
- Post announcements for students
- User activity tracking

### 👨‍🎓 Student Dashboard

- View personal academic results
- Track grades and performance
- Submit mark appeals/claims for review
- Receive in-system notifications
- Download report cards
- View announcements
- Manage profile settings
- Customize theme (light/dark mode)

### 🔐 Security Features

- Role-based access control (Admin/Student)
- Password hashing and encryption
- Secure password reset functionality
- Email verification
- Session management
- CSRF protection ready
- Secure credential handling via environment variables
- **Dedicated Admin Management** - Only authenticated admins can create new admin accounts
- **Reserved Username "admin"** - Cannot be used by students during registration
- **One-time Admin Setup** - Initial admin creation only possible on first installation
- **Audit Logging** - All admin actions are recorded for security compliance

### 📧 Communication

- Email notifications via SMTP
- Automated email sending for:
  - Account registration
  - Password reset
  - Result notifications
  - Appeal status updates

## System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher
- **Composer**: Latest version
- **Web Server**: Apache/Nginx with PHP support
- **Extensions**:
  - PHP MySQLi
  - PHP Mail (or SMTP access)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/Student_Like_MIS.git
cd Student_Like_MIS
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Setup

Copy the example environment file and configure it:

```bash
cp .env.example .env
```

Edit `.env` with your configuration:

```env
# Database
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password
DB_NAME=sms_db

# Email (Gmail SMTP)
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
```

For detailed setup instructions, see [ENV_SETUP.md](ENV_SETUP.md)

### 4. Create Database

Access MySQL and create the database:

```sql
CREATE DATABASE sms_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The application will automatically create all necessary tables on first run.

### 5. Access Application

```
http://localhost/Student_Like_MIS/
```

### 6. Create First Admin Account

On first access, you'll be automatically redirected to the **Admin Setup Page** (`admin_setup.php`).

**Fill in your admin details:**

- Full Name: Your name
- Username: Choose your admin username (any unique name)
- Email: Your email address
- Password: Create a strong password (min. 6 characters)

**After creation:**

- The setup page becomes inaccessible
- Only existing admins can create new admin/user accounts
- You can now login and manage the system

**Student Accounts:**

- Created by admin through the admin dashboard
- Students can also register themselves (optional)

## Project Structure

```
Student_Like_MIS/
├── admin_dashboard.php         # Admin control panel
├── student_dashboard.php       # Student portal
├── config.php                  # Database configuration & setup
├── mailer.php                  # Email handling (PHPMailer)
├── index.php                   # Login/Homepage
├── login_process.php           # Authentication handler
├── register.php                # Student registration
├── forgot_password.php         # Password recovery
├── reset_password.php          # Reset handler
├── logout.php                  # Session termination
├── manage_results.php          # Result management
├── save_theme.php              # Theme preference handler
├── .env                        # Environment variables (git-ignored)
├── .env.example                # Environment template
├── .gitignore                  # Git ignore rules
├── ENV_SETUP.md                # Detailed setup guide
├── README.md                   # This file
├── vendor/                     # Composer dependencies
│   ├── phpmailer/              # Email library
│   ├── vlucas/phpdotenv/       # Environment loader
│   └── ...
├── uploads/                    # User uploads (report cards)
│   └── report_cards/
└── composer.json               # Project dependencies
```

## Configuration

### Database Configuration

All database settings are managed through `.env`:

- `DB_HOST`: MySQL server hostname
- `DB_USER`: MySQL username
- `DB_PASS`: MySQL password
- `DB_NAME`: Database name
- `DB_PORT`: MySQL port (default: 3306)

### Email Configuration

SMTP settings for sending notifications:

- `SMTP_HOST`: SMTP server (e.g., smtp.gmail.com)
- `SMTP_PORT`: SMTP port (587 for TLS, 465 for SSL)
- `SMTP_USER`: Email address
- `SMTP_PASS`: App password (for Gmail)
- `SMTP_ENCRYPTION`: TLS or SSL

See [ENV_SETUP.md](ENV_SETUP.md) for detailed Gmail App Password setup.

## Database Schema

### Core Tables

**users**

- Student and admin accounts
- Password storage with hashing
- Account creation timestamps

**results**

- Academic results by subject and exam
- Grade tracking
- Term and year organization

**appeals**

- Student mark appeal requests
- Admin review status
- Appeal history

**announcements**

- Admin-posted announcements
- System-wide communications

**logs**

- User activity tracking
- Security audit trail
- IP address logging

**notifications**

- In-system notifications for students
- Read/unread status

**user_settings**

- Student preferences
- Theme selection (light/dark)
- Notification settings

## Usage

### For Administrators

1. **Login** with admin credentials
2. **Manage Students**: Add, edit, or remove student accounts
3. **Enter Results**: Upload/manage student academic results
4. **Review Appeals**: Process student mark appeals
5. **View Logs**: Monitor user activities and system events
6. **Post Announcements**: Communicate with students

### For Students

1. **Register** new account or login with existing credentials
2. **View Results**: Check grades and academic performance
3. **Submit Appeals**: Request mark review if needed
4. **Check Notifications**: View system messages and announcements
5. **Download Reports**: Get report cards
6. **Settings**: Customize theme and preferences

## Security Considerations

### Environment Variables

- **Never commit `.env`** with real credentials
- Always use `.env.example` as template
- Keep `.env` secure and private
- Use strong, unique passwords

### Best Practices

- Change default admin passwords immediately
- Enable HTTPS in production
- Keep PHP and dependencies updated
- Use strong database passwords
- Review security logs regularly
- Implement rate limiting on login
- Use prepared statements (ready to implement)
- Enable CORS headers in production

### SSL/TLS Certificates

Store SSL certificates in `.env` or secure location:

```env
SSL_CERT=/path/to/certificate.crt
SSL_KEY=/path/to/private.key
```

## Dependencies

### Composer Packages

- **phpmailer/phpmailer** (^7.0) - Email library with SMTP support
- **vlucas/phpdotenv** (^5.6) - Environment variable loader
- **graham-campbell/result-type** - Error handling
- **phpoption/phpoption** - Optional value handling
- **symfony/polyfill-\*** - PHP compatibility

Install dependencies:

```bash
composer install
```

Update dependencies:

```bash
composer update
```

## Troubleshooting

### Database Connection Errors

```
Error: Connection failed: Connection refused
```

- Verify MySQL is running
- Check credentials in `.env`
- Ensure database exists: `sms_db`

### Email Not Sending

```
Error: SMTP connection failed
```

- Verify SMTP credentials in `.env`
- For Gmail: Use App Password, not regular password
- Enable "Less secure app access" (if not using App Password)
- Check firewall/port 587 or 465 is open

### Permission Issues

```
Error: Can't write to uploads directory
```

- Ensure `/uploads/` folder has write permissions
- Run: `chmod 755 uploads/` (Linux/Mac)
- Check file ownership

### Session Issues

- Verify `/tmp/` directory is writable
- Check `session.save_path` in php.ini
- Clear browser cookies if persistent issues

## API Documentation

Core functions available:

### sendMail()

```php
sendMail(string $to, string $subject, string $htmlBody, string $plainBody = ''): bool|string
```

Send emails with proper SMTP configuration.

### buildEmailHtml()

```php
buildEmailHtml(string $recipientName, string $heading, string $bodyHtml, string $footerNote = ''): string
```

Generate formatted HTML emails.

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

## Future Enhancements

- [ ] API endpoints for mobile app
- [ ] Advanced analytics dashboard
- [ ] File upload for bulk student import
- [ ] SMS notifications
- [ ] Attendance tracking
- [ ] Assignment management
- [ ] Parent portal access
- [ ] Two-factor authentication
- [ ] Backup and restore functionality
- [ ] Multi-language support

## Performance Optimization

- [ ] Database indexing on frequently queried fields
- [ ] Query caching
- [ ] Session caching
- [ ] Lazy loading for large datasets
- [ ] Minified CSS/JavaScript

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Copyright

© 2026 **Elyse Joyeux**. All rights reserved.

**Author**: Elyse Joyeux  
**Version**: 1.0.0  
**Created**: April 2026

For detailed copyright information, see [COPYRIGHT.md](COPYRIGHT.md)

## Support & Contact

For issues, questions, or suggestions:

- Create an Issue on GitHub
- Email: support@example.com
- Documentation: [ENV_SETUP.md](ENV_SETUP.md)

## Changelog

### v1.0.0 (Current)

- Initial release
- User authentication system
- Student result management
- Mark appeal functionality
- In-system notifications
- Admin dashboard
- Student dashboard
- Theme customization
- Environment variable configuration

## Acknowledgments

- **PHPMailer** - Robust PHP email library
- **phpdotenv** - Environment variable management
- **Symfony** - Polyfill packages for PHP compatibility
- Contributors and testers

---

**Last Updated**: April 2026

For the latest documentation, visit the [project repository](https://github.com/Elyse-Joyeux/Student_Like_MIS)
