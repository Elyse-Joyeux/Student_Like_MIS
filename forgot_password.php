<?php

require_once 'config.php';
require_once 'mailer.php';

$message = '';
$error   = '';
$notice  = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id, username, full_name FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $query = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($query)) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update = mysqli_prepare($conn, "UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            mysqli_stmt_bind_param($update, "ssi", $token, $expires, $user['id']);
            mysqli_stmt_execute($update);

            $app_url = rtrim($_ENV['APP_URL'] ?? '', '/');
            $script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
            $app_path = trim(parse_url($app_url, PHP_URL_PATH) ?? '', '/');
            $base_url = $app_url !== ''
                ? $app_url . ($app_path === '' ? ($script_dir === '/' ? '' : $script_dir) : '')
                : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                    . ($script_dir === '/' ? '' : $script_dir));
            $reset_link = $base_url . '/reset_password.php?token=' . urlencode($token);

            //  Send branded email via SMTP 
            $bodyHtml = "
            <p>We received a request to reset your password. Click the button below to choose a new one.</p>
            <div style='text-align:center;margin:30px 0;'>
                <a href='" . htmlspecialchars($reset_link) . "'
                   style='background:#171b22;color:#fff;padding:14px 32px;
                          border-radius:10px;text-decoration:none;font-weight:600;font-size:15px;
                          display:inline-block;'>Reset My Password</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='background:#f4f6fb;padding:12px;border-radius:8px;word-break:break-all;font-size:13px;'>
                <a href='" . htmlspecialchars($reset_link) . "' style='color:#ff6473;'>" . htmlspecialchars($reset_link) . "</a>
            </p>
            <p style='color:#f05261;font-size:13px;'><strong>⏰ This link expires in 1 hour.</strong></p>
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
            $notice = "If that email exists in our system, a reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Student Management</title>
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
        background: linear-gradient(122deg, #f4f2f0 0 50%, #171b22 50% 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        padding-bottom: 140px;
    }

    :root {
        --bg-primary: #ffffff;
        --bg-soft: #f7f5f2;
        --text-primary: #2b2e42;
        --text-secondary: #7c8193;
        --border: #e7e2dd;
        --primary: #ff6473;
        --primary-dark: #e74f61;
        --accent: #45d6a6;
    }

    body.dark {
        --bg-primary: #111827;
        --bg-soft: #2b2e42;
        --text-primary: #f7f5f2;
        --text-secondary: #cbd5e1;
        --border: #343a47;
        --primary: #ff7b88;
        --primary-dark: #ff6473;
        --accent: #45d6a6;
    }

    .container {
        background: var(--bg-primary);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 24px 70px rgba(17, 20, 29, 0.18);
        width: 100%;
        max-width: 480px;
        overflow: hidden;
        padding: 0;
    }

    .logo {
        background: #171b22;
        border-bottom: 4px solid var(--primary);
        color: white;
        padding: 30px 34px;
        margin-bottom: 0;
    }

    .logo i {
        width: 44px;
        height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.24);
        font-size: 20px;
        color: white;
    }

    h2 {
        color: var(--text-primary);
        font-size: 24px;
        margin-bottom: 8px;
    }

    p.sub {
        color: var(--text-secondary);
        font-size: 14px;
        margin-bottom: 28px;
        line-height: 1.6;
    }

    .container > h2,
    .container > .sub,
    .container > form,
    .container > .alert,
    .container > .divider,
    .container > .back-link {
        margin-left: 34px;
        margin-right: 34px;
    }

    .container > h2 {
        margin-top: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 7px;
        font-size: 13px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .form-group input {
        width: 100%;
        padding: 13px 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
        background: var(--bg-soft);
        color: var(--text-primary);
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    }

    .form-group input:focus {
        outline: none;
        background: var(--bg-primary);
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(255, 100, 115, 0.16);
    }

    .btn {
        width: 100%;
        padding: 13px;
        font-size: 15px;
        font-weight: 600;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
    }

    .btn:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

    .alert {
        padding: 16px 18px;
        border-radius: 8px;
        margin-bottom: 24px;
    }

    .alert-success {
        background: #f0fff4;
        border: 1.5px solid #a7e8ca;
        color: #1f7a55;
    }

    .alert-error {
        background: #fff5f5;
        border: 1.5px solid #ff9aa4;
        color: #be2f3f;
    }

    .alert-warning {
        background: #fffff0;
        border: 1.5px solid #f6e05e;
        color: #8a5b11;
    }

    .link-box {
        background: var(--bg-soft);
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 12px 14px;
        margin: 12px 0 0;
        word-break: break-all;
        font-size: 12px;
        line-height: 1.5;
    }

    .link-box a {
        color: var(--primary);
    }

    .copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 10px;
        padding: 7px 16px;
        background: var(--accent);
        color: white;
        border: none;
        border-radius: 7px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
    }

    .back-link {
        text-align: center;
        margin-top: 24px;
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

    .divider {
        border: none;
        border-top: 1px solid var(--border);
        margin: 24px 0;
    }

    @media (max-width: 520px) {
        body {
            align-items: flex-start;
            padding: 16px;
            padding-bottom: 150px;
        }

        .logo,
        .container > h2,
        .container > .sub,
        .container > form,
        .container > .alert,
        .container > .divider,
        .container > .back-link {
            margin-left: 24px;
            margin-right: 24px;
        }

        .logo {
            margin-left: 0;
            margin-right: 0;
            padding: 24px;
        }
    }
    </style>
</head>

<body class="auth-page">
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
                <br><em style="color:#8a5b11;">(SMTP issue — use the link below directly)</em>
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
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <?php if ($notice): ?>
        <div class="alert alert-success">
            <!-- Intentionally success-looking to avoid user enumeration -->
            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($notice); ?>
        </div>
        <?php endif; ?>

        <?php if (!$message): ?>
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-envelope" style="margin-right:6px;"></i>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required autofocus>
            </div>
            <button type="submit" class="btn"><i class="fas fa-paper-plane" style="margin-right:8px;"></i>Send Reset
                Link</button>
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
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i> Copy Reset Link';
            }, 2500);
        }).catch(() => {
            // Fallback for browsers that block clipboard
            const ta = document.createElement('textarea');
            ta.value = link;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-copy"></i> Copy Reset Link';
            }, 2500);
        });
    }

    // Theme
    if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');
    </script>
    <?php include 'footer.php'; ?>
</body>

</html>
