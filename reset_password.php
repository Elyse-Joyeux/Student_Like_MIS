<?php

require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
$user = null;
if ($token !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($query);
}

if (!$user && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $error = "Invalid or expired reset link";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } else {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW() LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);
        if ($user = mysqli_fetch_assoc($query)) {
            $update = mysqli_prepare($conn, "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            mysqli_stmt_bind_param($update, "si", $new_hashed, $user['id']);
            mysqli_stmt_execute($update);
            $success = "Password reset successful! <a href='index.php'>Login here</a>";
        } else {
            $error = "Invalid or expired reset link. Please request a new one.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --surface: #ffffff;
        --surface-soft: #f7f5f2;
        --text: #2b2e42;
        --muted: #7c8193;
        --border: #e7e2dd;
        --primary: #ff6473;
        --primary-dark: #e74f61;
        --accent: #45d6a6;
    }

    body {
        font-family: 'Manrope', sans-serif;
        background: linear-gradient(122deg, #f4f2f0 0 50%, #171b22 50% 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        padding-bottom: 140px;
        color: var(--text);
    }

    .reset-container {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(17, 20, 29, 0.18);
        width: 100%;
        max-width: 480px;
        overflow: hidden;
    }

    .reset-header {
        background: #171b22;
        border-bottom: 4px solid var(--primary);
        color: white;
        padding: 30px 34px;
    }

    .brand-mark {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.24);
        margin-bottom: 16px;
        font-size: 20px;
    }

    .reset-header h2 {
        font-size: 24px;
        margin-bottom: 8px;
    }

    .reset-header p {
        color: rgba(255, 255, 255, 0.84);
        font-size: 14px;
        line-height: 1.6;
    }

    .reset-body {
        padding: 34px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 7px;
        color: var(--text);
        font-size: 13px;
        font-weight: 600;
    }

    input {
        width: 100%;
        padding: 13px 15px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--surface-soft);
        color: var(--text);
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    input:focus {
        outline: none;
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 100, 115, 0.16);
    }

    button {
        width: 100%;
        padding: 13px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 15px;
        font-weight: 700;
        transition: background 0.2s, transform 0.2s;
    }

    button:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .alert {
        padding: 13px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        line-height: 1.5;
    }

    .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .alert-success {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .back-link {
        margin-top: 20px;
        text-align: center;
    }

    .back-link a {
        color: var(--primary);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .back-link a:hover {
        text-decoration: underline;
    }

    @media (max-width: 520px) {
        body {
            align-items: flex-start;
            padding: 16px;
            padding-bottom: 150px;
        }

        .reset-header,
        .reset-body {
            padding: 24px;
        }
    }
    </style>
</head>

<body class="auth-page">
    <div class="reset-container">
        <div class="reset-header">
            <div class="brand-mark"><i class="fas fa-shield-alt"></i></div>
            <h2>Reset Password</h2>
            <p>Secure your Student Management System account with a new password.</p>
        </div>
        <div class="reset-body">
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (!$error && !$success): ?>
        <form method="POST" onsubmit="return validatePasswords()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" id="password" placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat your password"
                    required>
                <small id="match-msg" style="font-size: 12px; margin-top: 4px; display: block;"></small>
            </div>
            <button type="submit"><i class="fas fa-key" style="margin-right:8px;"></i>Reset Password</button>
        </form>
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back to Login</a>
        </div>
        <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const msg = document.getElementById('match-msg');
            if (this.value === document.getElementById('password').value) {
                msg.style.color = '#1f7a55';
                msg.textContent = '✓ Passwords match';
            } else {
                msg.style.color = '#be2f3f';
                msg.textContent = '✗ Passwords do not match';
            }
        });

        function validatePasswords() {
            const p = document.getElementById('password').value;
            const c = document.getElementById('confirm_password').value;
            if (p.length < 6) {
                alert('Password must be at least 6 characters.');
                return false;
            }
            if (p !== c) {
                alert('Passwords do not match.');
                return false;
            }
            return true;
        }
        </script>
        <?php endif; ?>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>
