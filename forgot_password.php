<?php

require_once 'config.php';
require_once 'mailer.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));

    $query = mysqli_query($conn, "SELECT id, username, full_name FROM users WHERE email = '$email'");

    if ($user = mysqli_fetch_assoc($query)) {
        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        mysqli_query($conn, "UPDATE users SET reset_token='$token', reset_expires='$expires' WHERE id={$user['id']}");

        $reset_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . dirname($_SERVER['PHP_SELF'])
                    . '/reset_password.php?token=' . urlencode($token);

        //  Send branded email via SMTP 
        $bodyHtml = "
            <p>We received a request to reset your password. Click the button below to choose a new one.</p>
            <div style='text-align:center;margin:30px 0;'>
                <a href='" . htmlspecialchars($reset_link) . "'
                   style='background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:14px 32px;
                          border-radius:10px;text-decoration:none;font-weight:600;font-size:15px;
                          display:inline-block;'>Reset My Password</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='background:#f4f6fb;padding:12px;border-radius:8px;word-break:break-all;font-size:13px;'>
                <a href='" . htmlspecialchars($reset_link) . "' style='color:#667eea;'>" . htmlspecialchars($reset_link) . "</a>
            </p>
            <p style='color:#e53e3e;font-size:13px;'><strong>⏰ This link expires in 1 hour.</strong></p>
            <p>If you did not request a password reset, you can safely ignore this email — your password will not change.</p>
        ";
        $html = buildEmailHtml(
            $user['full_name'],
            'Password Reset Request',
            $bodyHtml,
            'This is an automated message from the Student Management System. Please do not reply to this email.'
        );

        $result = sendMail($email, 'Password Reset — Student Management System', $html);

        if ($result === true) {
            $message = $reset_link;
        } else {
            // SMTP failed — still show the link locally so the user isn't locked out
            // Log the error for the admin
            error_log("Password reset SMTP error for $email: $result");
            $message = $reset_link;
            $smtp_error = $result; // shown only when SMTP fails
        }
    } else {
        // Intentionally vague to avoid user enumeration
        $error = "If that email exists in our system, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Student Management</title>
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
        .container {
            background: var(--bg-primary); border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); width: 100%; max-width: 480px; padding: 44px;
        }
        .logo { text-align:center; margin-bottom:28px; }
        .logo i { font-size: 40px; color: #667eea; }
        h2 { color: var(--text-primary); font-size: 22px; margin-bottom: 8px; }
        p.sub { color: #718096; font-size: 14px; margin-bottom: 28px; line-height: 1.6; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display:block; margin-bottom:7px; font-size:13px; font-weight:600; color:var(--text-primary); }
        .form-group input {
            width: 100%; padding: 13px 16px; border: 1.5px solid var(--border);
            border-radius: 10px; background: var(--bg-primary); color: var(--text-primary);
            font-size: 14px; transition: border-color 0.2s;
        }
        .form-group input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.12); }
        .btn {
            width: 100%; padding: 13px; font-size: 15px; font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 10px; cursor: pointer; transition: transform 0.15s;
        }
        .btn:hover { transform: translateY(-1px); }
        .alert { padding: 16px 18px; border-radius: 12px; margin-bottom: 24px; }
        .alert-success { background: #f0fff4; border: 1.5px solid #9ae6b4; color: #276749; }
        .alert-error   { background: #fff5f5; border: 1.5px solid #fc8181; color: #c53030; }
        .alert-warning { background: #fffff0; border: 1.5px solid #f6e05e; color: #744210; }
        .link-box {
            background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 8px;
            padding: 12px 14px; margin: 12px 0 0; word-break: break-all; font-size: 12px; line-height: 1.5;
        }
        .link-box a { color: #667eea; }
        .copy-btn {
            display:inline-flex; align-items:center; gap:6px; margin-top:10px;
            padding: 7px 16px; background: #276749; color: white;
            border: none; border-radius: 7px; cursor: pointer; font-size: 13px; font-weight:500;
        }
        .back-link { text-align: center; margin-top: 24px; }
        .back-link a { color: #667eea; font-size: 14px; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
        .divider { border: none; border-top: 1px solid var(--border); margin: 24px 0; }
    </style>
</head>
<body>
<div class="container">
    <div class="logo"><i class="fas fa-key"></i></div>
    <h2>Forgot your password?</h2>
    <p class="sub">Enter your registered email address and we'll send you a secure link to reset your password.</p>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <strong><i class="fas fa-check-circle"></i> Reset link sent!</strong>
            <p style="font-size:13px;margin-top:6px;">
                An email has been sent to your address with a reset button.
                <?php if (!empty($smtp_error)): ?>
                    <br><em style="color:#744210;">(SMTP issue — use the link below directly)</em>
                <?php endif; ?>
            </p>
            <div class="link-box">
                <a href="<?php echo htmlspecialchars($message); ?>"><?php echo htmlspecialchars($message); ?></a>
            </div>
            <button class="copy-btn" onclick="copyLink(this)">
                <i class="fas fa-copy"></i> Copy Reset Link
            </button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-success">
            <!-- Intentionally success-looking to avoid user enumeration -->
            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!$message): ?>
    <form method="POST">
        <div class="form-group">
            <label><i class="fas fa-envelope" style="margin-right:6px;"></i>Email Address</label>
            <input type="email" name="email" placeholder="you@example.com" required autofocus>
        </div>
        <button type="submit" class="btn"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Send Reset Link</button>
    </form>
    <?php endif; ?>

    <hr class="divider">
    <div class="back-link">
        <a href="index.php"><i class="fas fa-arrow-left" style="margin-right:6px;"></i>Back to Login</a>
    </div>
</div>

<script>
    function copyLink(btn) {
        const link = <?php echo json_encode($message ?: ''); ?>;
        navigator.clipboard.writeText(link).then(() => {
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Reset Link'; }, 2500);
        }).catch(() => {
            // Fallback for browsers that block clipboard
            const ta = document.createElement('textarea');
            ta.value = link; document.body.appendChild(ta); ta.select();
            document.execCommand('copy'); document.body.removeChild(ta);
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy Reset Link'; }, 2500);
        });
    }

    // Theme
    if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');
</script>
</body>
</html>