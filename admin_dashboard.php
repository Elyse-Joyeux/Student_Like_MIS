<?php

require_once 'config.php';
require_once 'mailer.php';
require_once 'mailer.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

//  Handle all POST actions 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Add user (admin sets username + email, student owns email — can't be changed by admin later)
    if (isset($_POST['add_user'])) {
        $username  = mysqli_real_escape_string($conn, trim($_POST['username']));
        $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $role      = $_POST['role'] === 'admin' ? 'admin' : 'student';
        $password  = password_hash('password123', PASSWORD_DEFAULT);

        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if (mysqli_num_rows($check) > 0) {
            redirect('admin_dashboard.php?error=Username+or+email+already+exists');
        }
        mysqli_query($conn, "INSERT INTO users (username, email, password, full_name, role) VALUES ('$username', '$email', '$password', '$full_name', '$role')");
        $new_id = mysqli_insert_id($conn);
        mysqli_query($conn, "INSERT INTO user_settings (user_id) VALUES ($new_id)");
        // Notify the new student
        addNotification($new_id, "Welcome to the Student Management System! Your account has been created. Your default password is <strong>password123</strong> — please change it immediately.");
        logAction($_SESSION['user_id'], 'Add User', "Added user: $username");
        redirect('admin_dashboard.php?msg=User+added+successfully&section=users');
    }

    // Edit user — admin may ONLY change full_name (not email, not username)
    if (isset($_POST['edit_user'])) {
        $id        = (int)$_POST['user_id'];
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));

        // Fetch old name for notification
        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM users WHERE id=$id"));
        mysqli_query($conn, "UPDATE users SET full_name='$full_name' WHERE id=$id AND role != 'admin'");

        if ($old && $old['full_name'] !== $full_name) {
            addNotification($id, "Your full name on record has been updated from <strong>" . htmlspecialchars($old['full_name']) . "</strong> to <strong>" . htmlspecialchars($full_name) . "</strong>. If this is unexpected, please contact your school administrator for clarification.");
        }
        logAction($_SESSION['user_id'], 'Edit User', "Edited name for user ID: $id");
        redirect('admin_dashboard.php?msg=User+updated+successfully&section=users');
    }

    // Delete user
    if (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role != 'admin'");
        logAction($_SESSION['user_id'], 'Delete User', "Deleted user ID: $id");
        redirect('admin_dashboard.php?msg=User+deleted&section=users');
    }

    // Post announcement
    if (isset($_POST['add_announcement'])) {
        $title   = mysqli_real_escape_string($conn, $_POST['title']);
        $content = mysqli_real_escape_string($conn, $_POST['content']);
        mysqli_query($conn, "INSERT INTO announcements (title, content, created_by) VALUES ('$title', '$content', {$_SESSION['user_id']})");
        // Notify all students
        $all_students = mysqli_query($conn, "SELECT id FROM users WHERE role='student'");
        while ($s = mysqli_fetch_assoc($all_students)) {
            addNotification($s['id'], "New announcement: <strong>" . htmlspecialchars($title) . "</strong>");
        }
        redirect('admin_dashboard.php?msg=Announcement+posted&section=announcements');
    }

    // Send direct email to student
    if (isset($_POST['send_email'])) {
        $student_id = (int)$_POST['student_id'];
        $subject    = trim($_POST['email_subject']);
        $body       = trim($_POST['email_body']);

        $student = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email, full_name FROM users WHERE id=$student_id AND role='student'"));
        if ($student) {
            // Build branded HTML email
            $bodyHtml = "
                <p>" . nl2br(htmlspecialchars($body)) . "</p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0;'>
                <p style='font-size:13px;color:#718096;'>This message was sent to you directly by a school administrator via the Student Management System.</p>
            ";
            $html = buildEmailHtml(
                $student['full_name'],
                htmlspecialchars($subject),
                $bodyHtml
            );

            $mailResult = sendMail($student['email'], $subject, $html);

            // Always create in-system notification so student sees it even if email is delayed
            $preview = mb_substr($body, 0, 100) . (mb_strlen($body) > 100 ? '…' : '');
            addNotification($student_id, "📧 <strong>Message from administrator:</strong> " . htmlspecialchars($subject) . " — " . htmlspecialchars($preview));

            $logNote = $mailResult === true ? 'delivered' : 'SMTP error: ' . $mailResult;
            logAction($_SESSION['user_id'], 'Email Student', "Student ID: $student_id — Subject: $subject — Email: $logNote");
            redirect('admin_dashboard.php?msg=Message+sent+to+' . urlencode($student['full_name']) . '&section=users');
        }
        redirect('admin_dashboard.php?error=Student+not+found&section=users');
    }

    // Upload report card
    if (isset($_POST['upload_report_card'])) {
        $student_id = (int)$_POST['rc_student_id'];
        $title      = mysqli_real_escape_string($conn, trim($_POST['rc_title']));

        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
            $ftype   = mime_content_type($_FILES['report_file']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                redirect('admin_dashboard.php?error=Only+PDF+or+image+files+allowed&section=report_cards');
            }
            $upload_dir = 'uploads/report_cards/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            $ext       = pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION);
            $safe_name = 'rc_' . $student_id . '_' . time() . '.' . $ext;
            $dest      = $upload_dir . $safe_name;

            if (move_uploaded_file($_FILES['report_file']['tmp_name'], $dest)) {
                $orig_name = mysqli_real_escape_string($conn, $_FILES['report_file']['name']);
                mysqli_query($conn, "INSERT INTO report_cards (student_id, title, file_path, file_name, uploaded_by) VALUES ($student_id, '$title', '$dest', '$orig_name', {$_SESSION['user_id']})");
                addNotification($student_id, "A report card has been uploaded for you: <strong>" . htmlspecialchars($title) . "</strong>. You can view it in your dashboard under <em>Report Cards</em>.");
                logAction($_SESSION['user_id'], 'Upload Report Card', "Uploaded report card for student ID: $student_id");
                redirect('admin_dashboard.php?msg=Report+card+uploaded&section=report_cards');
            }
        }
        redirect('admin_dashboard.php?error=Upload+failed&section=report_cards');
    }

    // Delete report card
    if (isset($_POST['delete_report_card'])) {
        $rc_id = (int)$_POST['rc_id'];
        $rc    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM report_cards WHERE id=$rc_id"));
        if ($rc) {
            @unlink($rc['file_path']);
            mysqli_query($conn, "DELETE FROM report_cards WHERE id=$rc_id");
            logAction($_SESSION['user_id'], 'Delete Report Card', "Deleted report card ID: $rc_id");
        }
        redirect('admin_dashboard.php?msg=Report+card+deleted&section=report_cards');
    }

    // Resolve appeal
    if (isset($_POST['resolve_appeal'])) {
        $appeal_id  = (int)$_POST['appeal_id'];
        $resolution = $_POST['resolution'] === 'approved' ? 'approved' : 'rejected';
        $admin_note = mysqli_real_escape_string($conn, trim($_POST['admin_note']));
        $new_marks  = isset($_POST['new_marks']) && is_numeric($_POST['new_marks']) ? (int)$_POST['new_marks'] : null;

        $appeal = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM appeals WHERE id=$appeal_id"));
        if ($appeal) {
            mysqli_query($conn, "UPDATE appeals SET status='$resolution', admin_note='$admin_note', resolved_at=NOW() WHERE id=$appeal_id");

            if ($resolution === 'approved' && $new_marks !== null) {
                $grade = $new_marks >= 80 ? 'A' : ($new_marks >= 70 ? 'B' : ($new_marks >= 60 ? 'C' : 'D'));
                mysqli_query($conn, "UPDATE results SET marks=$new_marks, grade='$grade' WHERE id={$appeal['result_id']}");
                addNotification($appeal['student_id'], "Your mark appeal has been <strong>approved</strong>. Your marks have been updated to <strong>$new_marks</strong> (Grade $grade). Admin note: " . htmlspecialchars($admin_note));
            } else {
                $label = $resolution === 'approved' ? 'approved' : 'rejected';
                addNotification($appeal['student_id'], "Your mark appeal has been <strong>$label</strong>. Admin note: " . htmlspecialchars($admin_note));
            }
            logAction($_SESSION['user_id'], 'Resolve Appeal', "Appeal ID $appeal_id resolved as $resolution");
        }
        redirect('admin_dashboard.php?msg=Appeal+resolved&section=appeals');
    }
}

//  Statistics ─
$total_students  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='student'"))['c'];
$total_results   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM results"))['c'];
$pending_appeals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM appeals WHERE status='pending'"))['c'];
$total_cards     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM report_cards"))['c'];

$users         = mysqli_query($conn, "SELECT * FROM users WHERE role='student' ORDER BY created_at DESC");
$announcements = mysqli_query($conn, "SELECT a.*, u.full_name FROM announcements a JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC LIMIT 5");
$appeals_list  = mysqli_query($conn, "SELECT ap.*, u.full_name, r.subject, r.marks, r.exam_type FROM appeals ap JOIN users u ON ap.student_id=u.id JOIN results r ON ap.result_id=r.id ORDER BY ap.created_at DESC");
$report_cards  = mysqli_query($conn, "SELECT rc.*, u.full_name FROM report_cards rc JOIN users u ON rc.student_id=u.id ORDER BY rc.created_at DESC");
$students_list = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name");

// Pre-fetch students for dropdowns (reusable)
function renderStudentOptions($conn, $selected = 0) {
    $q = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name");
    $out = '<option value="">— Select Student —</option>';
    while ($s = mysqli_fetch_assoc($q)) {
        $sel = $s['id'] == $selected ? 'selected' : '';
        $out .= '<option value="' . $s['id'] . '" ' . $sel . '>' . htmlspecialchars($s['full_name']) . '</option>';
    }
    return $out;
}

// Restore section after redirect
$restore_section = isset($_GET['section']) ? htmlspecialchars($_GET['section']) : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--bg-secondary); color: var(--text-primary); transition: all 0.3s; }
        :root {
            --bg-primary: #ffffff; --bg-secondary: #f7f9fc;
            --text-primary: #2d3748; --text-secondary: #718096;
            --border: #e2e8f0; --card-bg: #ffffff;
        }
        body.dark {
            --bg-primary: #1e2533; --bg-secondary: #141820;
            --text-primary: #e2e8f0; --text-secondary: #94a3b8;
            --border: #374151; --card-bg: #252d3d;
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
            transition: all 0.2s ease; font-size: 14px;
        }
        .sidebar nav a i { margin-right: 10px; width: 16px; }
        .sidebar nav a:hover { background: rgba(255,255,255,0.15); color: white; }
        .sidebar nav a.active { background: rgba(255,255,255,0.25); color: white; font-weight: 600; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .badge-pill {
            display: inline-block; background: #e53e3e; color: white;
            border-radius: 20px; padding: 1px 8px; font-size: 11px; margin-left: 6px;
        }
        .main-content { flex: 1; margin-left: 260px; padding: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--card-bg); padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stat-card h3 { font-size: 14px; color: var(--text-secondary); margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #667eea; }
        .card { background: var(--card-bg); border-radius: 15px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom: 20px; font-size: 18px; color: var(--text-primary); border-left: 4px solid #667eea; padding-left: 14px; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        table th { color: var(--text-secondary); font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        table td { color: var(--text-primary); font-size: 14px; }
        .btn { padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-size: 13px; margin: 2px; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #48bb78; color: white; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-warning { background: #ed8936; color: white; }
        .btn-info { background: #4299e1; color: white; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 500; color: var(--text-secondary); }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 10px; border: 1px solid var(--border);
            border-radius: 8px; background: var(--bg-primary); color: var(--text-primary); font-size: 14px;
        }
        .alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-error { background: #fed7d7; color: #c53030; border-color: #fc8181; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: 15px; width: 90%; max-width: 520px; border: 1px solid var(--border); max-height: 90vh; overflow-y: auto; }
        .modal-content h3 { color: var(--text-primary); margin-bottom: 20px; }
        .status-pending  { background: #fefcbf; color: #744210; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-approved { background: #c6f6d5; color: #276749; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .status-rejected { background: #fed7d7; color: #c53030; padding: 3px 10px; border-radius: 20px; font-size: 12px; }
        .readonly-field { background: var(--bg-secondary) !important; color: var(--text-secondary) !important; cursor: not-allowed; }
        .theme-toggle { position: fixed; bottom: 20px; right: 20px; background: var(--card-bg); border: 1px solid var(--border); border-radius: 50%; width: 48px; height: 48px; cursor: pointer; z-index: 100; font-size: 18px; color: var(--text-primary); box-shadow: 0 2px 12px rgba(0,0,0,0.15); transition: all 0.2s; }
        .theme-toggle:hover { transform: scale(1.1); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); transition: transform 0.3s; } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <h2><i class="fas fa-school"></i> SMS Admin</h2>
        <nav>
            <a href="#" onclick="showSection('dashboard')"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> Manage Students</a>
            <a href="#" onclick="showSection('results')"><i class="fas fa-chart-line"></i> Manage Results</a>
            <a href="#" onclick="showSection('appeals')"><i class="fas fa-gavel"></i> Appeals <?php if($pending_appeals > 0): ?><span class="badge-pill"><?php echo $pending_appeals; ?></span><?php endif; ?></a>
            <a href="#" onclick="showSection('report_cards')"><i class="fas fa-file-alt"></i> Report Cards</a>
            <a href="#" onclick="showSection('announcements')"><i class="fas fa-bullhorn"></i> Announcements</a>
            <a href="#" onclick="showSection('logs')"><i class="fas fa-history"></i> System Logs</a>
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
        <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!--  Dashboard  -->
        <div id="dashboard-section" style="display:none;">
            <h1 style="margin-bottom:25px;">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
            <div class="stats-grid">
                <div class="stat-card"><h3>Total Students</h3><div class="number"><?php echo $total_students; ?></div></div>
                <div class="stat-card"><h3>Total Results</h3><div class="number"><?php echo $total_results; ?></div></div>
                <div class="stat-card"><h3>Pending Appeals</h3><div class="number" style="color:#ed8936;"><?php echo $pending_appeals; ?></div></div>
                <div class="stat-card"><h3>Report Cards</h3><div class="number"><?php echo $total_cards; ?></div></div>
            </div>
            <div class="card">
                <h3>Recent Announcements</h3>
                <?php while($ann = mysqli_fetch_assoc($announcements)): ?>
                <div style="padding:10px 0; border-bottom:1px solid var(--border);">
                    <strong><?php echo htmlspecialchars($ann['title']); ?></strong>
                    <p style="font-size:13px; color:var(--text-secondary);">By <?php echo htmlspecialchars($ann['full_name']); ?> | <?php echo $ann['created_at']; ?></p>
                    <p style="margin-top:6px;"><?php echo htmlspecialchars(substr($ann['content'], 0, 120)); ?>...</p>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!--  Users  -->
        <div id="users-section" style="display:none;">
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <h3 style="margin-bottom:0;">Student Management</h3>
                    <button class="btn btn-primary" onclick="openModal('addUserModal')"><i class="fas fa-plus"></i> Add Student</button>
                </div>
                <table>
                    <thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $users = mysqli_query($conn, "SELECT * FROM users WHERE role='student' ORDER BY created_at DESC");
                        while($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="openEditUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>', '<?php echo addslashes($user['username']); ?>', '<?php echo addslashes($user['email']); ?>')">
                                    <i class="fas fa-edit"></i> Edit Name
                                </button>
                                <button class="btn btn-info" onclick="openEmailModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['full_name']); ?>', '<?php echo addslashes($user['email']); ?>')">
                                    <i class="fas fa-envelope"></i> Email
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this student?')">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!--  Results  -->
        <div id="results-section" style="display:none;">
            <div class="card">
                <h3>Add Result</h3>
                <form method="POST" action="manage_results.php">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="student_id" required><?php echo renderStudentOptions($conn); ?></select>
                    </div>
                    <div class="form-group"><label>Subject</label><input type="text" name="subject" placeholder="Subject" required></div>
                    <div class="form-group"><label>Marks (out of 100)</label><input type="number" name="marks" min="0" max="100" placeholder="Marks" required></div>
                    <div class="form-group">
                        <label>Exam Type</label>
                        <select name="exam_type"><option>Mid-Term</option><option>Final</option><option>Quiz</option></select>
                    </div>
                    <button type="submit" name="add_result" class="btn btn-primary">Add Result</button>
                </form>
            </div>
            <div class="card">
                <h3>All Results</h3>
                <table>
                    <thead><tr><th>Student</th><th>Subject</th><th>Marks</th><th>Grade</th><th>Exam</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php
                        $all_results = mysqli_query($conn, "SELECT r.*, u.full_name FROM results r JOIN users u ON r.student_id = u.id ORDER BY r.created_at DESC");
                        while($res = mysqli_fetch_assoc($all_results)):
                            $g = $res['marks'] >= 80 ? 'A' : ($res['marks'] >= 70 ? 'B' : ($res['marks'] >= 60 ? 'C' : 'D'));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($res['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($res['subject']); ?></td>
                            <td><?php echo $res['marks']; ?>/100</td>
                            <td><span class="status-<?php echo $g==='A'?'approved':($g==='D'?'rejected':'pending'); ?>" style="font-weight:600;"><?php echo $g; ?></span></td>
                            <td><?php echo htmlspecialchars($res['exam_type']); ?></td>
                            <td>
                                <form method="POST" action="manage_results.php" style="display:inline;" onsubmit="return confirm('Delete this result?')">
                                    <input type="hidden" name="result_id" value="<?php echo $res['id']; ?>">
                                    <button type="submit" name="delete_result" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!--  Appeals  -->
        <div id="appeals-section" style="display:none;">
            <div class="card">
                <h3>Mark Appeals / Claims</h3>
                <?php if(mysqli_num_rows($appeals_list) === 0): ?>
                    <p style="color:var(--text-secondary);">No appeals submitted yet.</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>Student</th><th>Subject</th><th>Current Marks</th><th>Exam</th><th>Reason</th><th>Submitted</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($ap = mysqli_fetch_assoc($appeals_list)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ap['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($ap['subject']); ?></td>
                            <td><?php echo $ap['marks']; ?>/100</td>
                            <td><?php echo htmlspecialchars($ap['exam_type']); ?></td>
                            <td style="max-width:200px; font-size:13px;"><?php echo htmlspecialchars(substr($ap['reason'], 0, 80)); ?>...</td>
                            <td style="font-size:13px;"><?php echo date('M j, Y', strtotime($ap['created_at'])); ?></td>
                            <td><span class="status-<?php echo $ap['status']; ?>"><?php echo ucfirst($ap['status']); ?></span></td>
                            <td>
                                <?php if($ap['status'] === 'pending'): ?>
                                <button class="btn btn-primary" onclick="openResolveModal(<?php echo $ap['id']; ?>, '<?php echo addslashes($ap['full_name']); ?>', '<?php echo addslashes($ap['subject']); ?>', <?php echo $ap['marks']; ?>, <?php echo $ap['result_id']; ?>, '<?php echo addslashes($ap['reason']); ?>')">
                                    <i class="fas fa-gavel"></i> Resolve
                                </button>
                                <?php else: ?>
                                <span style="font-size:12px; color:var(--text-secondary);"><?php echo $ap['admin_note'] ? htmlspecialchars(substr($ap['admin_note'], 0, 40)) : '—'; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!--  Report Cards ─ -->
        <div id="report_cards-section" style="display:none;">
            <div class="card">
                <h3>Upload Report Card</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Student</label>
                        <select name="rc_student_id" required><?php echo renderStudentOptions($conn); ?></select>
                    </div>
                    <div class="form-group">
                        <label>Title / Description</label>
                        <input type="text" name="rc_title" placeholder="e.g. Term 1 Report Card 2025" required>
                    </div>
                    <div class="form-group">
                        <label>File (PDF or Image)</label>
                        <input type="file" name="report_file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <button type="submit" name="upload_report_card" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                </form>
            </div>
            <div class="card">
                <h3>All Report Cards</h3>
                <?php if(mysqli_num_rows($report_cards) === 0): ?>
                    <p style="color:var(--text-secondary);">No report cards uploaded yet.</p>
                <?php else: ?>
                <table>
                    <thead><tr><th>Student</th><th>Title</th><th>File</th><th>Uploaded</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($rc = mysqli_fetch_assoc($report_cards)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($rc['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($rc['title']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($rc['file_path']); ?>" target="_blank" style="color:#667eea;"><i class="fas fa-file"></i> <?php echo htmlspecialchars($rc['file_name']); ?></a></td>
                            <td style="font-size:13px;"><?php echo date('M j, Y', strtotime($rc['created_at'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this report card?')">
                                    <input type="hidden" name="rc_id" value="<?php echo $rc['id']; ?>">
                                    <button type="submit" name="delete_report_card" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!--  Announcements  -->
        <div id="announcements-section" style="display:none;">
            <div class="card">
                <h3>Post Announcement</h3>
                <form method="POST">
                    <div class="form-group"><label>Title</label><input type="text" name="title" placeholder="Announcement title" required></div>
                    <div class="form-group"><label>Content</label><textarea name="content" rows="5" placeholder="Write announcement here..." required></textarea></div>
                    <button type="submit" name="add_announcement" class="btn btn-primary"><i class="fas fa-bullhorn"></i> Post to All Students</button>
                </form>
            </div>
        </div>

        <!--  Logs ─ -->
        <div id="logs-section" style="display:none;">
            <div class="card">
                <h3>System Activity Logs</h3>
                <table>
                    <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
                    <tbody>
                        <?php
                        $logs = mysqli_query($conn, "SELECT l.*, u.username FROM logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC LIMIT 50");
                        while($log = mysqli_fetch_assoc($logs)): ?>
                        <tr>
                            <td style="font-size:13px;"><?php echo $log['created_at']; ?></td>
                            <td><?php echo htmlspecialchars($log['username']); ?></td>
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

<!--  Modals  -->

<!-- Add User -->
<div id="addUserModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-user-plus"></i> Add New Student</h3>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:20px;">Default password will be <code>password123</code> — the student should change it on first login.</p>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required placeholder="Choose a username"></div>
            <div class="form-group"><label>Email (set by you, owned by student — cannot be changed later by admin)</label><input type="email" name="email" required placeholder="student@school.com"></div>
            <div class="form-group"><label>Full Name</label><input type="text" name="full_name" required placeholder="Student's full name"></div>
            <div class="form-group"><label>Role</label><select name="role"><option value="student">Student</option><option value="admin">Admin</option></select></div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                <button type="button" class="btn" onclick="closeModal('addUserModal')" style="background:var(--border);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User (name only) -->
<div id="editUserModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-user-edit"></i> Edit Student</h3>
        <p style="font-size:13px; color:#ed8936; margin-bottom:16px;"><i class="fas fa-info-circle"></i> You can only edit the student's <strong>full name</strong>. Email and username are owned by the student.</p>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="form-group">
                <label>Username (read-only)</label>
                <input type="text" id="edit_username_display" class="readonly-field" readonly>
            </div>
            <div class="form-group">
                <label>Email (read-only — set by student)</label>
                <input type="email" id="edit_email_display" class="readonly-field" readonly>
            </div>
            <div class="form-group">
                <label>Full Name <span style="color:#e53e3e;">*</span></label>
                <input type="text" name="full_name" id="edit_full_name" required placeholder="Student's full name">
            </div>
            <div style="background:#fffbeb; border:1px solid #f6e05e; border-radius:8px; padding:10px 14px; font-size:13px; color:#744210; margin-bottom:16px;">
                <i class="fas fa-bell"></i> The student will receive an in-system notification about this name change.
            </div>
            <div style="display:flex; gap:10px;">
                <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn" onclick="closeModal('editUserModal')" style="background:var(--border);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Send Email -->
<div id="emailModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-envelope"></i> Send Message to Student</h3>
        <p style="font-size:13px; color:var(--text-secondary); margin-bottom:16px;">This will send an email to <strong id="email_to_name"></strong> at <strong id="email_to_addr"></strong> and also create an in-system notification.</p>
        <form method="POST">
            <input type="hidden" name="student_id" id="email_student_id">
            <div class="form-group"><label>Subject</label><input type="text" name="email_subject" required placeholder="e.g. Regarding your attendance"></div>
            <div class="form-group"><label>Message</label><textarea name="email_body" rows="6" required placeholder="Write your message here..."></textarea></div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="submit" name="send_email" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                <button type="button" class="btn" onclick="closeModal('emailModal')" style="background:var(--border);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Resolve Appeal -->
<div id="resolveModal" class="modal">
    <div class="modal-content">
        <h3><i class="fas fa-gavel"></i> Resolve Appeal</h3>
        <div style="background:var(--bg-secondary); border-radius:10px; padding:14px; margin-bottom:20px; font-size:14px;">
            <p><strong>Student:</strong> <span id="ap_student"></span></p>
            <p><strong>Subject:</strong> <span id="ap_subject"></span></p>
            <p><strong>Current Marks:</strong> <span id="ap_marks"></span>/100</p>
            <p style="margin-top:8px;"><strong>Student's reason:</strong></p>
            <p id="ap_reason" style="color:var(--text-secondary); font-style:italic; font-size:13px; margin-top:4px;"></p>
        </div>
        <form method="POST">
            <input type="hidden" name="appeal_id" id="ap_id">
            <input type="hidden" name="result_id" id="ap_result_id">
            <div class="form-group">
                <label>Decision</label>
                <select name="resolution" id="ap_resolution" onchange="toggleNewMarks(this.value)">
                    <option value="rejected">Reject — marks remain unchanged</option>
                    <option value="approved">Approve — update marks</option>
                </select>
            </div>
            <div class="form-group" id="new_marks_group" style="display:none;">
                <label>New Marks (corrected value)</label>
                <input type="number" name="new_marks" id="ap_new_marks" min="0" max="100" placeholder="Enter corrected marks">
            </div>
            <div class="form-group">
                <label>Note to Student (required)</label>
                <textarea name="admin_note" rows="3" required placeholder="Explain your decision to the student..."></textarea>
            </div>
            <div style="display:flex; gap:10px; margin-top:16px;">
                <button type="submit" name="resolve_appeal" class="btn btn-primary">Submit Decision</button>
                <button type="button" class="btn" onclick="closeModal('resolveModal')" style="background:var(--border);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon" id="themeIconBtn"></i></button>

<script>
    const SECTIONS = ['dashboard','users','results','appeals','report_cards','announcements','logs'];

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
        localStorage.setItem('adminSection', section);
    }

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    function openEditUser(id, name, username, email) {
        document.getElementById('edit_user_id').value   = id;
        document.getElementById('edit_full_name').value = name;
        document.getElementById('edit_username_display').value = username;
        document.getElementById('edit_email_display').value    = email;
        openModal('editUserModal');
    }

    function openEmailModal(id, name, email) {
        document.getElementById('email_student_id').value = id;
        document.getElementById('email_to_name').textContent = name;
        document.getElementById('email_to_addr').textContent = email;
        openModal('emailModal');
    }

    function openResolveModal(id, student, subject, marks, resultId, reason) {
        document.getElementById('ap_id').value = id;
        document.getElementById('ap_result_id').value = resultId;
        document.getElementById('ap_student').textContent = student;
        document.getElementById('ap_subject').textContent = subject;
        document.getElementById('ap_marks').textContent = marks;
        document.getElementById('ap_reason').textContent = reason;
        document.getElementById('ap_resolution').value = 'rejected';
        document.getElementById('new_marks_group').style.display = 'none';
        openModal('resolveModal');
    }

    function toggleNewMarks(val) {
        document.getElementById('new_marks_group').style.display = val === 'approved' ? 'block' : 'none';
        if (val === 'approved') document.getElementById('ap_new_marks').required = true;
        else document.getElementById('ap_new_marks').required = false;
    }

    function applyTheme(theme) {
        const icon = document.getElementById('themeIcon');
        const label = document.getElementById('themeLabel');
        const iconBtn = document.getElementById('themeIconBtn');
        if (theme === 'dark') {
            document.body.classList.add('dark');
            if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
            if (iconBtn) { iconBtn.classList.remove('fa-moon'); iconBtn.classList.add('fa-sun'); }
            if (label) label.textContent = 'Light Mode';
        } else {
            document.body.classList.remove('dark');
            if (icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); }
            if (iconBtn) { iconBtn.classList.remove('fa-sun'); iconBtn.classList.add('fa-moon'); }
            if (label) label.textContent = 'Dark Mode';
        }
    }

    function toggleTheme() {
        const theme = document.body.classList.contains('dark') ? 'light' : 'dark';
        localStorage.setItem('theme', theme);
        applyTheme(theme);
        fetch('save_theme.php?theme=' + theme);
    }

    window.onclick = e => { if (e.target.classList.contains('modal')) e.target.style.display = 'none'; };

    applyTheme(localStorage.getItem('theme') || 'light');
    showSection(localStorage.getItem('adminSection') || '<?php echo $restore_section; ?>');
</script>
<?php include 'footer.php'; ?>
</body>
</html>