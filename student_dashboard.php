<?php

require_once 'config.php';

if (!isLoggedIn() || isAdmin()) {
    redirect('index.php');
}

$user_id = $_SESSION['user_id'];

// Get user settings
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id"));

// Update settings
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $theme = $_POST['theme'] === 'dark' ? 'dark' : 'light';
    $notifications = isset($_POST['notifications']) ? 1 : 0;
    mysqli_query($conn, "UPDATE user_settings SET theme='$theme', notifications=$notifications WHERE user_id=$user_id");
    redirect('student_dashboard.php?msg=Settings+updated&section=settings');
}

// Update profile (student can update their own name & email — NOT username)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));

    // Check email not taken by someone else
    $dup = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM users WHERE email='$email' AND id != $user_id"));
    if ($dup) {
        $error = "That email is already used by another account.";
    } else {
        mysqli_query($conn, "UPDATE users SET full_name='$full_name', email='$email' WHERE id=$user_id");
        $_SESSION['full_name'] = $full_name;
        redirect('student_dashboard.php?msg=Profile+updated&section=profile');
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'] ?? '';
    $user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$user_id"));
    if (!password_verify($current, $user['password'])) {
        $error = "Current password is incorrect";
    } elseif (strlen($new) < 6) {
        $error = "New password must be at least 6 characters";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match";
    } else {
        $new_hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$new_hash' WHERE id=$user_id");
        redirect('student_dashboard.php?msg=Password+changed+successfully&section=profile');
    }
}

// Submit mark appeal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_appeal'])) {
    $result_id = (int)$_POST['result_id'];
    $reason    = mysqli_real_escape_string($conn, trim($_POST['reason']));

    // Verify result belongs to this student
    $owns = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM results WHERE id=$result_id AND student_id=$user_id"));
    if ($owns && strlen($reason) >= 10) {
        // Check no pending appeal already
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM appeals WHERE result_id=$result_id AND student_id=$user_id AND status='pending'"));
        if ($existing) {
            $error = "You already have a pending appeal for this result.";
        } else {
            mysqli_query($conn, "INSERT INTO appeals (student_id, result_id, reason) VALUES ($user_id, $result_id, '$reason')");
            logAction($user_id, 'Appeal Submitted', "Appeal for result ID: $result_id");
            redirect('student_dashboard.php?msg=Appeal+submitted+successfully&section=results');
        }
    } else {
        $error = "Invalid appeal. Please provide a clear reason (at least 10 characters).";
    }
}

// Mark notifications as read
if (isset($_GET['mark_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
    redirect('student_dashboard.php?section=notifications');
}

// Counts
$unread_count = countUnreadNotifications($user_id);

// Get results
$results = mysqli_query($conn, "SELECT * FROM results WHERE student_id=$user_id ORDER BY year DESC, term DESC, created_at DESC");

// Get appeals
$my_appeals = mysqli_query($conn, "SELECT ap.*, r.subject, r.marks, r.exam_type FROM appeals ap JOIN results r ON ap.result_id=r.id WHERE ap.student_id=$user_id ORDER BY ap.created_at DESC");

// Get notifications
$notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 30");

// Get announcements
$announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");

// Get report cards
$report_cards = mysqli_query($conn, "SELECT * FROM report_cards WHERE student_id=$user_id ORDER BY created_at DESC");

// Get logs
$logs = mysqli_query($conn, "SELECT * FROM logs WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 20");

// Get user info (with fresh query for profile display)
$user_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$user_id"));

// Restore section after redirect
$restore_section = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-secondary); color: var(--text-primary); transition: all 0.3s; }
        :root {
            --bg-primary: #ffffff; --bg-secondary: #f7f9fc;
            --text-primary: #2d3748; --text-secondary: #718096;
            --border: #e2e8f0; --card-bg: #ffffff; --input-bg: #ffffff;
        }
        body.dark {
            --bg-primary: #1e2533; --bg-secondary: #141820;
            --text-primary: #e2e8f0; --text-secondary: #94a3b8;
            --border: #374151; --card-bg: #252d3d; --input-bg: #1e2533;
        }
        .container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 260px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px 20px 100px 20px;
            position: fixed; height: 100vh; overflow-y: auto;
        }
        .sidebar h2 { font-size: 20px; margin-bottom: 30px; text-align: center; }
        .sidebar nav a {
            display: block; padding: 12px 15px; color: rgba(255,255,255,0.8);
            text-decoration: none; border-radius: 10px; margin-bottom: 8px;
            transition: all 0.2s; font-size: 14px;
        }
        .sidebar nav a i { margin-right: 10px; width: 16px; }
        .sidebar nav a:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar nav a.active { background: rgba(255,255,255,0.25); color: white; font-weight: 600; }
        .badge-pill { display: inline-block; background: #e53e3e; color: white; border-radius: 20px; padding: 1px 8px; font-size: 11px; margin-left: 6px; }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .card { background: var(--card-bg); border-radius: 15px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom: 20px; font-size: 18px; border-left: 4px solid #667eea; padding-left: 15px; color: var(--text-primary); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; border-radius: 15px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        table th { color: var(--text-secondary); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        table td { color: var(--text-primary); font-size: 14px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-warning { background: #ed8936; color: white; }
        .btn-sm { padding: 6px 12px; font-size: 13px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 14px; font-weight: 500; color: var(--text-secondary); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border);
            border-radius: 8px; background: var(--input-bg); color: var(--text-primary);
        }
        .grade-A { color: #48bb78; font-weight: bold; }
        .grade-B { color: #4299e1; font-weight: bold; }
        .grade-C { color: #ed8936; font-weight: bold; }
        .grade-D { color: #e53e3e; font-weight: bold; }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-error { background: #fed7d7; color: #c53030; border-color: #fc8181; }
        .alert-info { background: #ebf8ff; color: #2b6cb0; border: 1px solid #bee3f8; }
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 42px; }
        .pw-eye { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); font-size: 15px; }
        .pw-eye:hover { color: #667eea; }
        .info-notice { background: #ebf4ff; border: 1px solid #bee3f8; color: #2b6cb0; border-radius: 10px; padding: 12px 16px; font-size: 13px; margin-top: 12px; display: flex; align-items: flex-start; gap: 10px; }
        body.dark .info-notice { background: #1a2a3a; border-color: #2b4c6f; color: #90cdf4; }
        .notif-item { padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .notif-item.unread { background: #ebf8ff; border-left: 3px solid #4299e1; }
        body.dark .notif-item.unread { background: #1a2d3d; }
        .notif-time { font-size: 12px; color: var(--text-secondary); margin-top: 4px; }
        .status-pending  { background: #fefcbf; color: #744210; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-approved { background: #c6f6d5; color: #276749; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-rejected { background: #fed7d7; color: #c53030; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 15px; width: 90%; max-width: 500px; border: 1px solid var(--border); }
        .modal-content h3 { color: var(--text-primary); margin-bottom: 16px; }
        .theme-toggle { position: fixed; bottom: 20px; right: 20px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 50%; width: 48px; height: 48px; cursor: pointer; z-index: 100; font-size: 18px; color: var(--text-primary); box-shadow: 0 2px 12px rgba(0,0,0,0.15); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2><i class="fas fa-user-graduate"></i> Student Portal</h2>
        <nav>
            <a href="#" onclick="showSection('dashboard')"><i class="fas fa-home"></i> Dashboard</a>
            <a href="#" onclick="showSection('results')"><i class="fas fa-chart-line"></i> My Results</a>
            <a href="#" onclick="showSection('report_cards')"><i class="fas fa-file-alt"></i> Report Cards</a>
            <a href="#" onclick="showSection('notifications')"><i class="fas fa-bell"></i> Notifications <?php if($unread_count > 0): ?><span class="badge-pill"><?php echo $unread_count; ?></span><?php endif; ?></a>
            <a href="#" onclick="showSection('announcements')"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="#" onclick="showSection('profile')"><i class="fas fa-user"></i> My Profile</a>
            <a href="#" onclick="showSection('settings')"><i class="fas fa-cog"></i> Settings</a>
            <a href="#" onclick="showSection('logs')"><i class="fas fa-history"></i> My Logs</a>
        </nav>
        <div style="position: absolute; bottom: 20px; left: 20px; right: 20px;">
            <button onclick="toggleTheme()" style="width:100%; padding:11px 15px; background:rgba(255,255,255,0.15); border:1px solid rgba(255,255,255,0.25); border-radius:10px; color:white; cursor:pointer; font-size:14px; display:flex; align-items:center; gap:10px;">
                <i class="fas fa-moon" id="themeIcon"></i> <span id="themeLabel">Toggle Theme</span>
            </button>
            <a href="logout.php" style="display:block; margin-top:8px; padding:11px 15px; background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15); border-radius:10px; color:rgba(255,255,255,0.85); text-decoration:none; font-size:14px;">
                <i class="fas fa-sign-out-alt" style="margin-right:10px;"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <?php if(isset($_GET['msg'])): ?>
            <div class="alert"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- ── Dashboard ───────────────────────────────────────── -->
        <div id="dashboard-section" style="display:none;">
            <h1 style="margin-bottom:25px;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>

            <?php if($unread_count > 0): ?>
            <div class="alert alert-info" style="cursor:pointer;" onclick="showSection('notifications')">
                <i class="fas fa-bell"></i> You have <strong><?php echo $unread_count; ?> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></strong>. <a href="#" onclick="showSection('notifications'); return false;" style="color:#2b6cb0;">View now →</a>
            </div>
            <?php endif; ?>

            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-chart-line fa-2x" style="margin-bottom:10px;"></i>
                    <h3>Results</h3>
                    <h2><?php echo mysqli_num_rows($results); ?></h2>
                </div>
                <div class="stat-card">
                    <i class="fas fa-file-alt fa-2x" style="margin-bottom:10px;"></i>
                    <h3>Report Cards</h3>
                    <h2><?php echo mysqli_num_rows($report_cards); ?></h2>
                </div>
                <div class="stat-card">
                    <i class="fas fa-bell fa-2x" style="margin-bottom:10px;"></i>
                    <h3>Notifications</h3>
                    <h2><?php echo $unread_count; ?> unread</h2>
                </div>
            </div>

            <div class="card">
                <h3>Latest Results</h3>
                <?php
                $latest = mysqli_query($conn, "SELECT * FROM results WHERE student_id=$user_id ORDER BY created_at DESC LIMIT 5");
                if(mysqli_num_rows($latest) > 0): ?>
                <table>
                    <thead><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Grade</th></tr></thead>
                    <tbody>
                        <?php while($res = mysqli_fetch_assoc($latest)):
                            $g = $res['marks'] >= 80 ? 'A' : ($res['marks'] >= 70 ? 'B' : ($res['marks'] >= 60 ? 'C' : 'D')); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($res['subject']); ?></td>
                            <td><?php echo htmlspecialchars($res['exam_type']); ?></td>
                            <td><?php echo $res['marks']; ?>/100</td>
                            <td class="grade-<?php echo $g; ?>"><?php echo $g; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:var(--text-secondary);">No results available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Results + Appeals ────────────────────────────────── -->
        <div id="results-section" style="display:none;">
            <div class="card">
                <h3>All My Results</h3>
                <?php
                $results = mysqli_query($conn, "SELECT * FROM results WHERE student_id=$user_id ORDER BY year DESC, term DESC, created_at DESC");
                if(mysqli_num_rows($results) > 0): ?>
                <table>
                    <thead><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Grade</th><th>Term</th><th>Year</th><th>Appeal</th></tr></thead>
                    <tbody>
                        <?php while($res = mysqli_fetch_assoc($results)):
                            $g = $res['marks'] >= 80 ? 'A' : ($res['marks'] >= 70 ? 'B' : ($res['marks'] >= 60 ? 'C' : 'D'));
                            // Check if appeal exists
                            $ap_check = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM appeals WHERE result_id={$res['id']} AND student_id=$user_id ORDER BY created_at DESC LIMIT 1"));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($res['subject']); ?></td>
                            <td><?php echo htmlspecialchars($res['exam_type']); ?></td>
                            <td><?php echo $res['marks']; ?>/100</td>
                            <td class="grade-<?php echo $g; ?>"><?php echo $g; ?></td>
                            <td><?php echo $res['term']; ?></td>
                            <td><?php echo $res['year']; ?></td>
                            <td>
                                <?php if($ap_check): ?>
                                    <span class="status-<?php echo $ap_check['status']; ?>"><?php echo ucfirst($ap_check['status']); ?></span>
                                <?php else: ?>
                                    <button class="btn btn-warning btn-sm" onclick="openAppealModal(<?php echo $res['id']; ?>, '<?php echo addslashes($res['subject']); ?>', <?php echo $res['marks']; ?>)">
                                        <i class="fas fa-flag"></i> Appeal
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:var(--text-secondary);">No results found. Contact your teacher.</p>
                <?php endif; ?>
            </div>

            <!-- My Appeals History -->
            <div class="card">
                <h3>My Appeal History</h3>
                <?php
                $my_appeals = mysqli_query($conn, "SELECT ap.*, r.subject, r.marks, r.exam_type FROM appeals ap JOIN results r ON ap.result_id=r.id WHERE ap.student_id=$user_id ORDER BY ap.created_at DESC");
                if(mysqli_num_rows($my_appeals) > 0): ?>
                <table>
                    <thead><tr><th>Subject</th><th>Exam</th><th>Marks</th><th>Status</th><th>Admin Note</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php while($ap = mysqli_fetch_assoc($my_appeals)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ap['subject']); ?></td>
                            <td><?php echo htmlspecialchars($ap['exam_type']); ?></td>
                            <td><?php echo $ap['marks']; ?>/100</td>
                            <td><span class="status-<?php echo $ap['status']; ?>"><?php echo ucfirst($ap['status']); ?></span></td>
                            <td style="font-size:13px; color:var(--text-secondary);"><?php echo $ap['admin_note'] ? htmlspecialchars($ap['admin_note']) : '—'; ?></td>
                            <td style="font-size:13px;"><?php echo date('M j, Y', strtotime($ap['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="color:var(--text-secondary);">You have not submitted any appeals yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Report Cards ─────────────────────────────────────── -->
        <div id="report_cards-section" style="display:none;">
            <div class="card">
                <h3>My Report Cards</h3>
                <?php
                $report_cards = mysqli_query($conn, "SELECT * FROM report_cards WHERE student_id=$user_id ORDER BY created_at DESC");
                if(mysqli_num_rows($report_cards) > 0): ?>
                <table>
                    <thead><tr><th>Title</th><th>File</th><th>Uploaded</th><th>Download</th></tr></thead>
                    <tbody>
                        <?php while($rc = mysqli_fetch_assoc($report_cards)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rc['title']); ?></strong></td>
                            <td style="font-size:13px; color:var(--text-secondary);"><?php echo htmlspecialchars($rc['file_name']); ?></td>
                            <td style="font-size:13px;"><?php echo date('F j, Y', strtotime($rc['created_at'])); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($rc['file_path']); ?>" target="_blank" class="btn btn-primary btn-sm">
                                    <i class="fas fa-download"></i> View / Download
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div style="text-align:center; padding:40px; color:var(--text-secondary);">
                    <i class="fas fa-file-alt fa-3x" style="margin-bottom:16px; opacity:0.4;"></i>
                    <p>No report cards have been uploaded for you yet.</p>
                    <p style="font-size:13px; margin-top:8px;">Your school administrator will upload them here when available.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Notifications ─────────────────────────────────────── -->
        <div id="notifications-section" style="display:none;">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin-bottom:0;">Notifications</h3>
                    <?php if($unread_count > 0): ?>
                    <a href="student_dashboard.php?mark_read=1" class="btn btn-primary btn-sm"><i class="fas fa-check-double"></i> Mark All Read</a>
                    <?php endif; ?>
                </div>
                <?php
                $notifications = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 30");
                if(mysqli_num_rows($notifications) > 0):
                    while($notif = mysqli_fetch_assoc($notifications)): ?>
                <div class="notif-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                    <div><?php echo $notif['message']; ?></div>
                    <div class="notif-time"><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></div>
                </div>
                <?php endwhile; else: ?>
                <p style="color:var(--text-secondary); text-align:center; padding:30px;">No notifications yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Announcements ─────────────────────────────────────── -->
        <div id="announcements-section" style="display:none;">
            <div class="card">
                <h3>School Announcements</h3>
                <?php
                $announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC");
                while($ann = mysqli_fetch_assoc($announcements)): ?>
                <div style="padding: 15px 0; border-bottom: 1px solid var(--border);">
                    <h4><?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p style="font-size:12px; color:var(--text-secondary); margin:4px 0;">Posted by <?php echo htmlspecialchars($ann['full_name']); ?> on <?php echo date('F j, Y', strtotime($ann['created_at'])); ?></p>
                    <p style="margin-top: 10px; font-size:14px;"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- ── Profile ───────────────────────────────────────────── -->
        <div id="profile-section" style="display:none;">
            <div class="card">
                <h3>My Profile</h3>

                <?php
                // Check for admin-changed name notification
                $name_change_notif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM notifications WHERE user_id=$user_id AND message LIKE '%full name on record has been updated%' AND is_read=0 ORDER BY created_at DESC LIMIT 1"));
                if ($name_change_notif): ?>
                <div class="alert alert-info" style="margin-bottom:20px;">
                    <i class="fas fa-info-circle"></i> Your name on record was recently changed by the administrator. If this was unexpected, please <strong>contact your school administrator for clarification</strong>.
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email (your own — admin cannot change this)</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Username (cannot be changed)</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_info['username']); ?>" disabled style="background:var(--bg-secondary); color:var(--text-secondary); cursor:not-allowed;">
                        <div class="info-notice" style="margin-top:8px;">
                            <i class="fas fa-info-circle" style="flex-shrink:0;"></i>
                            <span>Your username cannot be changed. If it was changed by your institution, you can still log in using your email address. Contact your school administrator for clarification.</span>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="card">
                <h3>Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="current_password" id="cur_pw" required placeholder="Enter current password">
                            <span class="pw-eye" onclick="togglePw('cur_pw', this)"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" id="new_pw" required placeholder="Enter new password">
                            <span class="pw-eye" onclick="togglePw('new_pw', this)"><i class="fas fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" id="confirm_pw" required placeholder="Repeat new password">
                            <span class="pw-eye" onclick="togglePw('confirm_pw', this)"><i class="fas fa-eye"></i></span>
                        </div>
                        <small id="pw-match-msg" style="font-size:12px; margin-top:5px; display:block;"></small>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary" onclick="return checkPwMatch()">Change Password</button>
                </form>
            </div>
        </div>

        <!-- ── Settings ──────────────────────────────────────────── -->
        <div id="settings-section" style="display:none;">
            <div class="card">
                <h3>Application Settings</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Theme Preference</label>
                        <select name="theme" id="themeSelect" onchange="showThemeNotice(this)">
                            <option value="light" <?php echo ($settings['theme'] == 'light') ? 'selected' : ''; ?>>☀️ Light Mode</option>
                            <option value="dark"  <?php echo ($settings['theme'] == 'dark')  ? 'selected' : ''; ?>>🌙 Dark Mode</option>
                        </select>
                        <div class="info-notice" id="themeNotice" style="display:none;">
                            <i class="fas fa-info-circle" style="flex-shrink:0;"></i>
                            <span>This preference will be applied on your next login. Use the toggle button on the sidebar for an immediate change.</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><input type="checkbox" name="notifications" value="1" <?php echo $settings['notifications'] ? 'checked' : ''; ?>> Enable Email Notifications</label>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>

        <!-- ── Logs ──────────────────────────────────────────────── -->
        <div id="logs-section" style="display:none;">
            <div class="card">
                <h3>My Activity Logs</h3>
                <table>
                    <thead><tr><th>Time</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
                    <tbody>
                        <?php while($log = mysqli_fetch_assoc($logs)): ?>
                        <tr>
                            <td style="font-size:13px;"><?php echo $log['created_at']; ?></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td style="font-size:13px;"><?php echo htmlspecialchars($log['details']); ?></td>
                            <td style="font-size:13px;"><?php echo $log['ip_address']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── Appeal Modal ──────────────────────────────────────────────────────── -->
<div id="appealModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-flag"></i> Appeal / Claim Wrong Marks</h3>
        <div style="background:var(--bg-secondary); border-radius:10px; padding:14px; margin-bottom:16px; font-size:14px;">
            <p>Subject: <strong id="ap_modal_subject"></strong></p>
            <p>Current Marks: <strong id="ap_modal_marks"></strong>/100</p>
        </div>
        <form method="POST">
            <input type="hidden" name="result_id" id="ap_modal_result_id">
            <div class="form-group">
                <label>Reason for Appeal <span style="color:#e53e3e;">*</span></label>
                <textarea name="reason" rows="5" required minlength="10" placeholder="Clearly explain why you believe the marks are incorrect. Provide as much detail as possible (e.g. what you submitted, the correct answer, any evidence)."></textarea>
            </div>
            <div style="background:#fffbeb; border:1px solid #f6e05e; border-radius:8px; padding:10px 14px; font-size:13px; color:#744210; margin-bottom:16px;">
                <i class="fas fa-info-circle"></i> Your appeal will be reviewed by the administrator. You will receive a notification once a decision is made.
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="submit_appeal" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit Appeal</button>
                <button type="button" class="btn" onclick="closeModal('appealModal')" style="background:var(--border);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIconBtn"></i></button>

<script>
    const SECTIONS = ['dashboard','results','report_cards','notifications','announcements','profile','settings','logs'];

    function showSection(section) {
        SECTIONS.forEach(s => {
            const el = document.getElementById(s + '-section');
            if (el) el.style.display = 'none';
        });
        const target = document.getElementById(section + '-section');
        if (target) target.style.display = 'block';
        document.querySelectorAll('.sidebar nav a').forEach(a => a.classList.remove('active'));
        const link = document.querySelector(`.sidebar nav a[onclick="showSection('${section}')"]`);
        if (link) link.classList.add('active');
        localStorage.setItem('studentSection', section);
    }

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openAppealModal(resultId, subject, marks) {
        document.getElementById('ap_modal_result_id').value = resultId;
        document.getElementById('ap_modal_subject').textContent = subject;
        document.getElementById('ap_modal_marks').textContent = marks;
        openModal('appealModal');
    }

    function applyTheme(theme) {
        const icon    = document.getElementById('themeIcon');
        const label   = document.getElementById('themeLabel');
        const iconBtn = document.getElementById('themeIconBtn');
        if (theme === 'dark') {
            document.body.classList.add('dark');
            [icon, iconBtn].forEach(i => { if(i){ i.classList.remove('fa-moon'); i.classList.add('fa-sun'); }});
            if (label) label.textContent = 'Light Mode';
        } else {
            document.body.classList.remove('dark');
            [icon, iconBtn].forEach(i => { if(i){ i.classList.remove('fa-sun'); i.classList.add('fa-moon'); }});
            if (label) label.textContent = 'Dark Mode';
        }
    }

    function toggleTheme() {
        const theme = document.body.classList.contains('dark') ? 'light' : 'dark';
        localStorage.setItem('theme', theme);
        applyTheme(theme);
        fetch('save_theme.php?theme=' + theme);
    }

    function togglePw(fieldId, el) {
        const input = document.getElementById(fieldId);
        const icon = el.querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
        else { input.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
    }

    function checkPwMatch() {
        const np = document.getElementById('new_pw').value;
        const cp = document.getElementById('confirm_pw').value;
        if (np !== cp) { document.getElementById('pw-match-msg').style.color='#c53030'; document.getElementById('pw-match-msg').textContent='✗ Passwords do not match'; return false; }
        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cp = document.getElementById('confirm_pw');
        const np = document.getElementById('new_pw');
        if (cp && np) {
            cp.addEventListener('input', function() {
                const msg = document.getElementById('pw-match-msg');
                if (this.value === np.value) { msg.style.color='#276749'; msg.textContent='✓ Passwords match'; }
                else { msg.style.color='#c53030'; msg.textContent='✗ Passwords do not match'; }
            });
        }
    });

    function showThemeNotice(select) {
        const saved = '<?php echo $settings["theme"] ?? "light"; ?>';
        const notice = document.getElementById('themeNotice');
        if (notice) notice.style.display = (select.value !== saved) ? 'flex' : 'none';
    }

    window.onclick = e => { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    applyTheme(localStorage.getItem('theme') || '<?php echo $settings["theme"] ?? "light"; ?>');
    showSection(localStorage.getItem('studentSection') || '<?php echo $restore_section; ?>');
</script>
</body>
</html>