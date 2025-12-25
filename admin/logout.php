<?php
session_start();

$redirect_url = '/login.html'; // Default for normal users

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $redirect_url = '/admin/login.php';
}

// Clear session
session_destroy();

// Clear remember me cookie if it exists (for normal users)
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

// Redirect
header("Location: $redirect_url");
exit;
