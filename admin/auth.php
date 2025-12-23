<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../app/config/csrf.php';

// Automatically verify CSRF on all POST requests in Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        die("CSRF Verification Failed. Please refresh the page and try again.");
    }
}
?>