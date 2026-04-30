<?php

/**
 * Admin Setup Page - First Time Configuration
 * 
 * This page is only accessible when NO admin account exists.
 * After the first admin is created, this page is no longer accessible.
 * 
 * Author: Elyse Joyeux
 * Version: 1.0.0
 * © 2026 Elyse Joyeux. All rights reserved.
 */

require_once 'config.php';

// Check if admin already exists
$admin_check = mysqli_query($conn, "SELECT id FROM users WHERE role='admin' LIMIT 1");
$admin_exists = mysqli_num_rows($admin_check) > 0;

// If admin exists, redirect to login
if ($admin_exists) {
    redirect('index.php');
}

// If user submits the admin creation form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    $error = '';

    // Validation
    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters long.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if username already exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email already exists.";
        } else {
            // Create admin account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, email, password, full_name, role) 
                      VALUES ('$username', '$email', '$hashed_password', '$full_name', 'admin')";
            
            if (mysqli_query($conn, $query)) {
                $user_id = mysqli_insert_id($conn);
                // Create user settings
                mysqli_query($conn, "INSERT INTO user_settings (user_id, theme) VALUES ($user_id, 'light')");
                // Log the action
                logAction($user_id, 'Admin Setup', 'Initial admin account created');
                
                $_SESSION['success'] = "Admin account created successfully! Redirecting to login...";
                header("refresh:2;url=index.php?success=Admin account created! Please login.");
                $success = true;
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Student Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            padding-bottom: 140px;
        }

        :root {
            --bg-primary: #ffffff;
            --text-primary: #2d3748;
            --border: #e2e8f0;
            --input-bg: #ffffff;
        }

        body.dark {
            --bg-primary: #1a202c;
            --text-primary: #f7fafc;
            --border: #4a5568;
            --input-bg: #4a5568;
        }

        .setup-container {
            background: var(--bg-primary);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 550px;
            overflow: hidden;
        }

        .setup-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .setup-header h1 {
            font-size: 28px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .setup-header p {
            font-size: 14px;
            opacity: 0.95;
            line-height: 1.5;
        }

        .setup-content {
            padding: 40px;
        }

        .setup-content h2 {
            color: var(--text-primary);
            font-size: 18px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 13px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            background: var(--input-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .alert-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .alert-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }

        .alert i {
            margin-top: 2px;
        }

        .info-box {
            background: #efefff;
            border: 1px solid #d0d0ff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #2d3748;
            line-height: 1.6;
        }

        .info-box i {
            color: #667eea;
            margin-right: 8px;
        }

        .btn {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .password-requirements {
            background: #f7fafc;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            margin-top: 8px;
            font-size: 12px;
            color: var(--text-primary);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
        }

        .requirement i {
            width: 16px;
            text-align: center;
            color: #9ca3af;
        }

        .requirement.met i {
            color: #22c55e;
        }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 20px 0;
        }
    </style>
</head>
<body class="auth-page">
<div class="setup-container">
    <div class="setup-header">
        <h1>
            <i class="fas fa-crown"></i> Initial Admin Setup
        </h1>
        <p>Welcome! Create your first administrator account to get started.</p>
    </div>

    <div class="setup-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong>Success!</strong><br>
                    Your admin account has been created. Redirecting to login page...
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <strong>Important:</strong> This account will be the system administrator. After creation, this setup page will no longer be accessible.
        </div>

        <form method="POST">
            <h2><i class="fas fa-user-tie"></i> Administrator Information</h2>

            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    placeholder="e.g., System Administrator" 
                    required
                    value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="e.g., admin" 
                    required
                    minlength="3"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                >
                <small style="color: #718096;">Minimum 3 characters. Use only letters, numbers, underscore.</small>
            </div>

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="admin@yourdomain.com" 
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                >
            </div>

            <div class="divider"></div>

            <h2><i class="fas fa-lock"></i> Security</h2>

            <div class="form-group">
                <label for="password">Password *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter a strong password" 
                    required
                    minlength="6"
                    onkeyup="checkPasswordStrength(this.value)"
                >
                <div class="password-requirements">
                    <div class="requirement" id="req-length">
                        <i class="fas fa-circle-notch"></i>
                        <span>At least 6 characters</span>
                    </div>
                    <div class="requirement" id="req-upper">
                        <i class="fas fa-circle-notch"></i>
                        <span>Uppercase letter (optional)</span>
                    </div>
                    <div class="requirement" id="req-number">
                        <i class="fas fa-circle-notch"></i>
                        <span>Number (optional)</span>
                    </div>
                    <div class="requirement" id="req-special">
                        <i class="fas fa-circle-notch"></i>
                        <span>Special character (optional)</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    placeholder="Re-enter your password" 
                    required
                    minlength="6"
                >
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check"></i> Create Admin Account
            </button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    function checkPasswordStrength(pwd) {
        document.getElementById('req-length').classList.toggle('met', pwd.length >= 6);
        document.getElementById('req-upper').classList.toggle('met', /[A-Z]/.test(pwd));
        document.getElementById('req-number').classList.toggle('met', /[0-9]/.test(pwd));
        document.getElementById('req-special').classList.toggle('met', /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd));
    }

    document.querySelector('form').addEventListener('submit', function(e) {
        const pw = document.getElementById('password').value;
        const cp = document.getElementById('confirm_password').value;
        if (pw !== cp) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
    });

    if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');
</script>
</body>
</html>
