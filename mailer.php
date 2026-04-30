<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables from .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// SMTP configuration from environment variables
define('SMTP_HOST',     $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT',     $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER',     $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS',     $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM',     $_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['SMTP_USER'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Student Management System');

// Determine encryption type from environment
$encryption = strtolower($_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('SMTP_SECURE', $encryption === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS);


function sendMail(string $to, string $subject, string $htmlBody, string $plainBody = ''): bool|string
{
  $mail = new PHPMailer(true);
  try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Recipients
    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($to);
    $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $plainBody ?: strip_tags($htmlBody);

    $mail->send();
    return true;
  } catch (Exception $e) {
    return $mail->ErrorInfo;
  }
}

/**
 * Builds a clean, branded HTML email.
 * Returns the full HTML string ready to pass to sendMail().
 */
function buildEmailHtml(string $recipientName, string $heading, string $bodyHtml, string $footerNote = ''): string
{
  $appName = htmlspecialchars(SMTP_FROM_NAME);
  $year    = date('Y');
  $footer  = $footerNote ? "<p style='color:#999;font-size:12px;margin-top:20px;'>{$footerNote}</p>" : '';
  return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Inter,Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6fb;padding:40px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#667eea,#764ba2);padding:32px 40px;text-align:center;">
            <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;">🎓 {$appName}</h1>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px 40px;">
            <h2 style="color:#2d3748;margin:0 0 8px 0;font-size:20px;">{$heading}</h2>
            <p style="color:#4a5568;font-size:15px;margin:0 0 20px 0;">Dear <strong>{$recipientName}</strong>,</p>
            <div style="color:#4a5568;font-size:15px;line-height:1.7;">{$bodyHtml}</div>
            {$footer}
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f7f9fc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
            <p style="color:#a0aec0;font-size:12px;margin:0;">&copy; {$year} {$appName}. All rights reserved.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}