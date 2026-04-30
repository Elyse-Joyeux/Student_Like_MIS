<?php

require_once 'config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    $query = mysqli_query($conn, "SELECT id, username FROM users WHERE email = '$email'");
    
    if ($user = mysqli_fetch_assoc($query)) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        mysqli_query($conn, "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE id = {$user['id']}");
        
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . urlencode($token);

        // Send real email
        $to      = $email;
        $subject = 'Password Reset Request';
        $body    = "Hello {$user['username']},\r\n\r\n"
                 . "You requested a password reset. Click the link below to reset your password:\r\n\r\n"
                 . $reset_link . "\r\n\r\n"
                 . "This link will expire in 1 hour.\r\n\r\n"
                 . "If you did not request this, please ignore this email.\r\n\r\n"
                 . "Regards,\r\nStudent Management System";
        $headers = "From: SMS No-Reply <no-reply@" . $_SERVER['HTTP_HOST'] . ">\r\n"
                 . "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n"
                 . "Content-Type: text/plain; charset=UTF-8\r\n"
                 . "X-Mailer: PHP/" . phpversion();
        @mail($to, $subject, $body, $headers);

        $message = $reset_link;
    } else {
        $error = "Email not found in our system";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        :root { --bg-primary: #ffffff; --text-primary: #2d3748; --border: #e2e8f0; }
        body.dark { --bg-primary: #1a202c; --text-primary: #f7fafc; --border: #4a5568; }
        .forgot-container {
            background: var(--bg-primary); border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 450px; padding: 40px;
        }
        .forgot-container h2 { color: var(--text-primary); margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group input {
            width: 100%; padding: 12px; border: 1px solid var(--border);
            border-radius: 10px; background: var(--bg-primary); color: var(--text-primary); font-size: 14px;
        }
        .btn-reset {
            width: 100%; padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 10px; cursor: pointer; font-size: 15px; font-weight: 600;
        }
        .alert { padding: 14px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #c6f6d5; color: #276749; }
        .alert-error { background: #fed7d7; color: #c53030; }
        .reset-link-box {
            background: #f0fff4; border: 1px solid #9ae6b4; border-radius: 8px;
            padding: 12px; margin-top: 12px; word-break: break-all; font-size: 12px;
        }
        .reset-link-box a { color: #276749; }
        .copy-btn {
            margin-top: 10px; padding: 7px 16px;
            background: #276749; color: white; border: none; border-radius: 6px;
            cursor: pointer; font-size: 13px;
        }
    </style>
</head>
<body>
<div class="forgot-container">
    <h2><i class="fas fa-key"></i> Forgot Password</h2>
    <p style="color: #718096; margin-bottom: 20px;">Enter your email to receive a password reset link.</p>

    <?php if($message): ?>
        <div class="alert alert-success">
            <p><strong><i class="fas fa-check-circle"></i> Reset link sent!</strong></p>
            <p style="margin-top:6px; font-size:13px;">A reset email has been sent. You may also copy the link below directly:</p>
            <div class="reset-link-box">
                <a href="<?php echo htmlspecialchars($message); ?>"><?php echo htmlspecialchars($message); ?></a>
            </div>
            <button class="copy-btn" onclick="copyLink(this)">
                <i class="fas fa-copy"></i> Copy Reset Link
            </button>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="email" name="email" placeholder="Enter your email address" required>
        </div>
        <button type="submit" class="btn-reset"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
    </form>
    <div style="text-align: center; margin-top: 20px;">
        <a href="index.php" style="color: #667eea; font-size:14px;"><i class="fas fa-arrow-left"></i> Back to Login</a>
    </div>
</div>
<script>
    function copyLink(btn) {
        const link = <?php echo json_encode($message ?: ''); ?>;
        navigator.clipboard.writeText(link).then(() => {
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Reset Link'; }, 2000);
        });
    }
</script>
</body>
</html>