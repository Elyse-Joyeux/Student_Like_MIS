<?php


require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        redirect('index.php?error=Username+and+password+are+required');
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['full_name'] = $row['full_name'];

            logAction($row['id'], 'Login', 'User logged in successfully');

            if ($row['role'] == 'admin') {
                redirect('admin_dashboard.php');
            } else {
                redirect('student_dashboard.php');
            }
        } else {
            redirect('index.php?error=Invalid password');
        }
    } else {
        redirect('index.php?error=User not found');
    }
}

redirect('index.php');
