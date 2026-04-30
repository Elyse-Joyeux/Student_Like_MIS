<?php

/**
 * Logout Handler
 * 
 * Author: Elyse Joyeux
 * Version: 1.0.0
 * © 2026 Elyse Joyeux. All rights reserved.
 */

require_once 'config.php';

if (isLoggedIn()) {
    logAction($_SESSION['user_id'], 'Logout', 'User logged out');
}

session_destroy();
redirect('index.php');
?>