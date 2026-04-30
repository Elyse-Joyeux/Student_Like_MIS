<?php

require_once 'config.php';

if (isLoggedIn() && isset($_GET['theme'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light'; // whitelist only
    $user_id = (int)$_SESSION['user_id'];
    $stmt = mysqli_prepare($conn, "UPDATE user_settings SET theme=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "si", $theme, $user_id);
    mysqli_stmt_execute($stmt);
}