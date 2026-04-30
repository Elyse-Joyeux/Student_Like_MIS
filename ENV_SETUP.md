# Environment Setup Guide

## Overview

This project uses environment variables to manage sensitive configuration data like database credentials and email settings. This approach keeps secrets out of version control.

## Setup Instructions

### 1. Initial Setup

After cloning the repository, create your `.env` file from the template:

```bash
cp .env.example .env
```

### 2. Configure Environment Variables

Edit the `.env` file with your specific configuration:

#### Database Configuration

```env
DB_HOST=localhost          # MySQL host
DB_PORT=3306              # MySQL port
DB_USER=root              # MySQL username
DB_PASS=your_password     # MySQL password
DB_NAME=sms_db            # Database name
DB_CHARSET=utf8mb4        # Character set
```

#### Email (SMTP) Configuration

```env
SMTP_HOST=smtp.gmail.com           # SMTP server
SMTP_PORT=587                      # Port (587 for TLS, 465 for SSL)
SMTP_ENCRYPTION=tls                # Encryption type: tls or ssl
SMTP_USER=your_email@gmail.com     # Email address
SMTP_PASS=your_app_password        # App password (not regular password)
SMTP_FROM_EMAIL=your_email@gmail.com
SMTP_FROM_NAME=Student Management System
```

#### Other Configuration

```env
APP_NAME=Student Management System
APP_ENV=development                # development or production
APP_DEBUG=true                     # Debug mode (false in production)
APP_URL=http://localhost           # Application URL
SESSION_LIFETIME=3600              # Session lifetime in seconds
TIMEZONE=UTC                       # Server timezone
```

### 3. Gmail App Password Setup (Required for Gmail)

If using Gmail for sending emails:

1. Enable 2-Step Verification in Google Account
2. Go to: https://myaccount.google.com/apppasswords
3. Select "Mail" and "Windows Computer" (or your device)
4. Generate and copy the 16-character password
5. Paste it in `.env` as `SMTP_PASS`

### 4. Security Notes

⚠️ **IMPORTANT:**

- Never commit the `.env` file to git (it's already in `.gitignore`)
- Always use `.env.example` as a template for new environments
- Keep your `.env` file confidential and secure
- In production, set `APP_DEBUG=false`
- Consider using a password manager for storing credentials

### 5. Environment Variables in Code

The application loads environment variables using the `phpdotenv` package:

```php
// In config.php and mailer.php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Access variables
$host = $_ENV['DB_HOST'] ?? 'localhost';  // With fallback
```

### 6. Troubleshooting

**Database Connection Error:**

- Verify `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` in `.env`
- Ensure MySQL server is running
- Check database user permissions

**Email Not Sending:**

- Verify Gmail App Password (not regular password)
- Ensure 2-Step Verification is enabled
- Check `SMTP_USER` and `SMTP_FROM_EMAIL` match
- Verify `SMTP_PORT` and `SMTP_ENCRYPTION` are correct

**Environment Variables Not Loading:**

- Verify `.env` file exists in project root
- Ensure proper file permissions
- Check for syntax errors in `.env`

## File Locations

- `.env` - Your local environment configuration (git-ignored)
- `.env.example` - Template with required variables documentation
- `.gitignore` - Ensures `.env` is not committed
- `config.php` - Loads environment variables for database
- `mailer.php` - Loads environment variables for email
