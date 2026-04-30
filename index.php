<?php


require_once 'config.php';

// If no admin exists, redirect to admin setup
if (!adminExists()) {
    redirect('admin_setup.php');
}

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin_dashboard.php');
    } else {
        redirect('student_dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Manrope', sans-serif;
        background:
            linear-gradient(122deg, #f4f2f0 0 50%, #171b22 50% 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        padding-bottom: 140px;
    }

    :root {
        --bg-primary: #ffffff;
        --bg-secondary: #f7f5f2;
        --text-primary: #2b2e42;
        --text-secondary: #7c8193;
        --border: #e7e2dd;
        --card-bg: #ffffff;
        --input-bg: #f7f5f2;
        --primary: #ff6473;
        --primary-dark: #e74f61;
        --accent: #45d6a6;
    }

    body.dark {
        --bg-primary: #171b22;
        --bg-secondary: #101319;
        --text-primary: #f7f5f2;
        --text-secondary: #aab1c0;
        --border: #343a47;
        --card-bg: #202631;
        --input-bg: #101319;
        --primary: #ff7b88;
        --primary-dark: #ff6473;
        --accent: #45d6a6;
    }

    .login-container {
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(17, 20, 29, 0.18);
        width: 100%;
        max-width: 450px;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .login-header {
        background: #171b22;
        border-bottom: 4px solid var(--primary);
        padding: 40px;
        text-align: center;
    }

    .login-header h1 {
        color: white;
        font-size: 28px;
        margin-bottom: 10px;
    }

    .login-header p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
    }

    .login-form {
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
        transition: all 0.3s;
    }

    .form-group input:focus {
        outline: none;
        background: var(--bg-primary);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 100, 115, 0.16);
    }

    .btn-login {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .btn-login:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    .register-link,
    .forgot-link {
        text-align: center;
        margin-top: 20px;
    }

    .register-link a,
    .forgot-link a {
        color: var(--primary);
        text-decoration: none;
        font-size: 14px;
        font-weight: 800;
    }

    .alert {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-error {
        background: #ffe1e5;
        color: #be2f3f;
        border: 1px solid #ff9aa4;
    }

    .alert-success {
        background: #dff8ed;
        color: #1f7a55;
        border: 1px solid #a7e8ca;
    }

    .theme-toggle {
        position: fixed;
        top: 20px;
        right: 20px;
        background: var(--card-bg);
        border: 1px solid var(--border);
        border-radius: 8px;
        width: 45px;
        height: 45px;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        color: var(--text-primary);
        font-size: 20px;
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

    @media (max-width: 520px) {
        body {
            align-items: flex-start;
            padding: 16px;
            padding-bottom: 150px;
        }

        .login-header,
        .login-form {
            padding: 28px 24px;
        }

        .login-header h1 {
            font-size: 24px;
        }
    }
    </style>
</head>

<body class="auth-page">
    <button class="theme-toggle" onclick="toggleTheme()" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-graduation-cap"></i> Student Management</h1>
            <p>Login to your account</p>
        </div>
        <div class="login-form">
            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <form method="POST" action="login_process.php">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username or Email</label>
                    <input type="text" name="username" required placeholder="Enter username or email">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <div class="pw-wrap">
                        <input type="password" name="password" id="login_password" required
                            placeholder="Enter password">
                        <span class="pw-eye" onclick="toggleLoginPw()"><i class="fas fa-eye"
                                id="login_eye_icon"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <div class="register-link">
                <a href="register.php">Don't have an account? Register</a>
            </div>
            <div class="forgot-link">
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script>
    function toggleLoginPw() {
        const input = document.getElementById('login_password');
        const icon = document.getElementById('login_eye_icon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }

    function toggleTheme() {
        document.body.classList.toggle('dark');
        const theme = document.body.classList.contains('dark') ? 'dark' : 'light';
        localStorage.setItem('theme', theme);
        const icon = document.querySelector('#themeToggle i');
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.body.classList.add('dark');
        const icon = document.querySelector('#themeToggle i');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>
