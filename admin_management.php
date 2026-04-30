<?php


require_once 'config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('index.php');
}

// Handle create new admin
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_admin') {
        $username = mysqli_real_escape_string($conn, trim($_POST['username']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'] ?? '';
        $error = '';

        // Validation
        if (strlen($username) < 3) {
            $error = "Username must be at least 3 characters long.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            // Check if username already exists
            $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
            if (mysqli_num_rows($check) > 0) {
                $error = "Username or email already exists.";
            } else {
                // Create admin account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, email, password, full_name, role) 
                          VALUES ('$username', '$email', '$hashed_password', '$full_name', 'admin')";

                if (mysqli_query($conn, $query)) {
                    $user_id = mysqli_insert_id($conn);
                    // Create user settings
                    mysqli_query($conn, "INSERT INTO user_settings (user_id, theme) VALUES ($user_id, 'light')");
                    // Log the action
                    logAction($_SESSION['user_id'], 'Admin Management', "Created new admin account: $username");

                    $success = "Admin account created successfully!";
                } else {
                    $error = "Error creating admin account. Please try again.";
                }
            }
        }
    }

    // Handle remove admin
    if ($_POST['action'] === 'remove_admin') {
        $admin_id = (int)$_POST['admin_id'];

        // Prevent removing the current logged-in admin
        if ($admin_id === $_SESSION['user_id']) {
            $error = "You cannot remove your own admin account.";
        } else {
            // Verify the user is actually an admin
            $admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, username FROM users WHERE id=$admin_id AND role='admin'"));
            if ($admin) {
                mysqli_query($conn, "DELETE FROM users WHERE id=$admin_id AND role='admin'");
                logAction($_SESSION['user_id'], 'Admin Management', "Removed admin account: {$admin['username']}");
                $success = "Admin account removed successfully!";
            } else {
                $error = "Admin account not found.";
            }
        }
    }
}

// Get all admin accounts
$admin_query = mysqli_query($conn, "SELECT id, username, email, full_name, created_at FROM users WHERE role='admin' ORDER BY created_at DESC");
$admins = [];
while ($admin = mysqli_fetch_assoc($admin_query)) {
    $admins[] = $admin;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Student Management System</title>
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
        background: #f7f9fc;
        color: #2d3748;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 40px;
    }

    .header h1 {
        font-size: 32px;
        color: #2d3748;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        color: #4299e1;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .back-link:hover {
        background: #ebf8ff;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    .card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 30px;
        border: 1px solid #e2e8f0;
    }

    .card h2 {
        font-size: 20px;
        margin-bottom: 24px;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card h2 i {
        color: #667eea;
        font-size: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #2d3748;
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
        transition: border-color 0.3s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
        font-family: 'Inter', sans-serif;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .btn-danger {
        background: #f56565;
        color: white;
        padding: 8px 12px;
        width: auto;
        font-size: 12px;
    }

    .btn-danger:hover {
        background: #e53e3e;
    }

    .alert {
        padding: 14px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .alert-success {
        background: #c6f6d5;
        color: #22543d;
        border: 1px solid #9ae6b4;
    }

    .alert-error {
        background: #fed7d7;
        color: #742a2a;
        border: 1px solid #fc8181;
    }

    .alert i {
        font-size: 18px;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }

    .admin-table thead {
        background: #f7f9fc;
        border-bottom: 2px solid #e2e8f0;
    }

    .admin-table th {
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #4a5568;
        font-size: 13px;
    }

    .admin-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
    }

    .admin-table tr:hover {
        background: #f7f9fc;
    }

    .admin-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        background: #bee3f8;
        color: #2c5282;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    .current-badge {
        background: #c6f6d5;
        color: #22543d;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #718096;
    }

    .empty-state i {
        font-size: 48px;
        color: #cbd5e0;
        margin-bottom: 16px;
    }

    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }

        .admin-table {
            font-size: 12px;
        }

        .admin-table th,
        .admin-table td {
            padding: 8px;
        }
    }
    </style>
</head>

<body class="auth-page">
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Admin Management</h1>
            <a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <div class="content-grid">
            <!-- Create New Admin Card -->
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Create New Admin</h2>

                <?php if (isset($error) && $_POST['action'] === 'create_admin'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <?php if (isset($success) && $_POST['action'] === 'create_admin'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_admin">

                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required placeholder="John Doe">
                    </div>

                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required placeholder="johndoe" minlength="3">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required placeholder="john@example.com">
                    </div>

                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="••••••"
                            minlength="6">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                            placeholder="••••••" minlength="6">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Admin Account
                    </button>
                </form>
            </div>

            <!-- Admin Accounts List Card -->
            <div class="card">
                <h2><i class="fas fa-users-cog"></i> Existing Admin Accounts</h2>

                <?php if (isset($error) && $_POST['action'] === 'remove_admin'): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endif; ?>

                <?php if (isset($success) && $_POST['action'] === 'remove_admin'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>

                <?php if (empty($admins)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No admin accounts found.</p>
                </div>
                <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($admin['full_name']); ?>
                                <?php if ($admin['id'] == $_SESSION['user_id']): ?>
                                <span class="admin-badge current-badge">You</span>
                                <?php else: ?>
                                <span class="admin-badge"><i class="fas fa-shield-alt"></i> Admin</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($admin['username']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                            <td>
                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="action" value="remove_admin">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to remove this admin account?');">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </form>
                                <?php else: ?>
                                <span style="color: #718096; font-size: 12px;">Current account</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Important Notes -->
        <div class="card" style="background: #f0f4ff; border: 1px solid #bee3f8;">
            <h2 style="color: #2c5282;"><i class="fas fa-info-circle"></i> Important Notes</h2>
            <ul style="margin-left: 20px; color: #2c5282; line-height: 1.8;">
                <li><strong>Security:</strong> Only existing admins can create new admin accounts from this page.</li>
                <li><strong>Username Restriction:</strong> Students cannot register with the username "admin" — it's
                    reserved for admin accounts only.</li>
                <li><strong>Self-Removal:</strong> You cannot remove your own admin account from this page (for safety).
                </li>
                <li><strong>Audit Trail:</strong> All admin account creation and removal actions are logged for security
                    purposes.</li>
                <li><strong>First Setup:</strong> The initial admin account is created through the dedicated setup page
                    on first-time installation.</li>
            </ul>
        </div>
    </div>
    <?php include 'footer.php'; ?>
</body>

</html>