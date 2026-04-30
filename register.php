<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $raw_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Prevent students from registering with reserved "admin" username
    if (strtolower(trim($username)) === 'admin') {
        $error = "The username 'admin' is reserved and cannot be used.";
    } elseif (strlen($raw_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($raw_password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        // Check if user exists
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Username or email already exists";
        } else {
            $query = "INSERT INTO users (username, email, password, full_name, role) VALUES ('$username', '$email', '$password', '$full_name', 'student')";
            if (mysqli_query($conn, $query)) {
                $user_id = mysqli_insert_id($conn);
                mysqli_query($conn, "INSERT INTO user_settings (user_id) VALUES ($user_id)");
                redirect('index.php?success=Registration successful! Please login.');
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Register - Student Management System</title>
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
        --bg-secondary: #f8fafc;
        --text-primary: #1f2937;
        --text-secondary: #64748b;
        --border: #dbe4ee;
        --input-bg: #f8fafc;
        --primary: #1d4ed8;
        --primary-dark: #1e3a8a;
        --accent: #0f766e;
    }

    body.dark {
        --bg-primary: #1a202c;
        --text-primary: #f7fafc;
        --text-secondary: #cbd5e1;
        --border: #4a5568;
        --input-bg: #4a5568;
        --primary: #60a5fa;
        --primary-dark: #3b82f6;
        --accent: #2dd4bf;
    }

    .register-container {
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
        width: 100%;
        max-width: 500px;
        overflow: hidden;
    }

    .register-header {
        background: linear-gradient(135deg, var(--primary-dark), var(--accent));
        padding: 30px;
        text-align: center;
    }

    .register-header h1 {
        color: white;
        font-size: 24px;
    }

    .register-form {
        padding: 40px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-primary);
        font-weight: 500;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 13px 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
        font-size: 14px;
        background: var(--input-bg);
        color: var(--text-primary);
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .form-group input:focus {
        outline: none;
        background: var(--bg-primary);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.12);
    }

    .pw-wrap {
        position: relative;
    }

    .pw-wrap input {
        padding-right: 44px;
    }

    .pw-eye {
        position: absolute;
        right: 13px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #a0aec0;
        font-size: 15px;
        user-select: none;
    }

    .pw-eye:hover {
        color: var(--primary);
    }

    .pw-match-msg {
        font-size: 12px;
        margin-top: 5px;
        display: block;
        min-height: 16px;
    }

    .btn-register {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.2s, transform 0.2s;
    }

    .btn-register:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .login-link {
        text-align: center;
        margin-top: 20px;
    }

    .login-link a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 600;
    }

    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-error {
        background: #fed7d7;
        color: #c53030;
        border: 1px solid #fc8181;
    }

    .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 8px;
        width: 45px;
        height: 45px;
        cursor: pointer;
        font-size: 20px;
        color: var(--text-primary);
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12);
    }

    @media (max-width: 540px) {
        body {
            align-items: flex-start;
            padding: 16px;
            padding-bottom: 150px;
        }

        .register-header,
        .register-form {
            padding: 28px 24px;
        }
    }
    </style>
</head>

<body class="auth-page">
    <button class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-moon"></i>
    </button>

    <div class="register-container">
        <div class="register-header">
            <h1><i class="fas fa-user-plus"></i> Create Account</h1>
            <p style="color: rgba(255,255,255,0.9);">Join our school community</p>
        </div>
        <div class="register-form">
            <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" name="username" required placeholder="Choose a username">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user-circle"></i> Full Name</label>
                    <input type="text" name="full_name" required placeholder="Enter your full name">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="reg_password" required
                            placeholder="At least 6 characters">
                        <span class="pw-eye" onclick="togglePw('reg_password', this)"><i class="fas fa-eye"></i></span>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="confirm_password" id="reg_confirm" required
                            placeholder="Repeat your password">
                        <span class="pw-eye" onclick="togglePw('reg_confirm', this)"><i class="fas fa-eye"></i></span>
                    </div>
                    <small class="pw-match-msg" id="reg-match-msg"></small>
                </div>
                <button type="submit" class="btn-register"><i class="fas fa-check-circle"></i> Register</button>
            </form>
            <div class="login-link">
                <a href="index.php">Already have an account? Login</a>
            </div>
        </div>
    </div>

    <script>
    function toggleTheme() {
        document.body.classList.toggle('dark');
        const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        const icon = document.querySelector('.theme-toggle i');
        icon.classList.toggle('fa-moon', theme === 'light');
        icon.classList.toggle('fa-sun', theme === 'dark');
    }
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
        const icon = document.querySelector('.theme-toggle i');
        if (icon) {
            icon.classList.replace('fa-moon', 'fa-sun');
        }
    }

    function togglePw(fieldId, el) {
        const input = document.getElementById(fieldId);
        const icon = el.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    document.getElementById('reg_confirm').addEventListener('input', function() {
        const msg = document.getElementById('reg-match-msg');
        if (this.value === document.getElementById('reg_password').value) {
            msg.style.color = '#276749';
            msg.textContent = '✓ Passwords match';
        } else {
            msg.style.color = '#c53030';
            msg.textContent = '✗ Passwords do not match';
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const pw = document.getElementById('reg_password').value;
        const cp = document.getElementById('reg_confirm').value;
        if (pw.length < 6) {
            e.preventDefault();
            alert('Password must be at least 6 characters.');
            return;
        }
        if (pw !== cp) {
            e.preventDefault();
            alert('Passwords do not match.');
        }
    });
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>
