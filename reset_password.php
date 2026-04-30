<?php

require_once 'config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Verify token
$query = mysqli_query($conn, "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
$user = mysqli_fetch_assoc($query);

if (!$user && $_SERVER['REQUEST_METHOD'] != 'POST') {
    $error = "Invalid or expired reset link";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";
    } else {
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $query = mysqli_query($conn, "SELECT id FROM users WHERE reset_token = '$token' AND reset_expires > NOW()");
        if ($user = mysqli_fetch_assoc($query)) {
            mysqli_query($conn, "UPDATE users SET password = '$new_hashed', reset_token = NULL, reset_expires = NULL WHERE id = {$user['id']}");
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
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        .reset-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .form-group { margin-bottom: 20px; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 10px; cursor: pointer; }
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .alert-error { background: #fed7d7; color: #c53030; }
        .alert-success { background: #c6f6d5; color: #276749; }
    </style>
</head>
<body class="auth-page">
<div class="reset-container">
    <h2>Reset Password</h2>
    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if(!$error && !$success): ?>
        <form method="POST" onsubmit="return validatePasswords()">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="password" id="password" placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Repeat your password" required>
                <small id="match-msg" style="font-size: 12px; margin-top: 4px; display: block;"></small>
            </div>
            <button type="submit">Reset Password</button>
        </form>
        <script>
            document.getElementById('confirm_password').addEventListener('input', function() {
                const msg = document.getElementById('match-msg');
                if (this.value === document.getElementById('password').value) {
                    msg.style.color = '#276749'; msg.textContent = '✓ Passwords match';
                } else {
                    msg.style.color = '#c53030'; msg.textContent = '✗ Passwords do not match';
                }
            });
            function validatePasswords() {
                const p = document.getElementById('password').value;
                const c = document.getElementById('confirm_password').value;
                if (p.length < 6) { alert('Password must be at least 6 characters.'); return false; }
                if (p !== c) { alert('Passwords do not match.'); return false; }
                return true;
            }
        </script>
    <?php endif; ?>
</div>
<?php include 'footer.php'; ?>
</body>
</html>