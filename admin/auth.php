<?php
/**
 * Admin Authentication Middleware
 *
 * This file acts as a middleware for the administrative panel.
 * It checks if the user is logged in and has the 'admin' role.
 * If not, it redirects to the login page.
 * It also strictly enforces CSRF verification for all POST requests within the admin area.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

session_start();

// Check for valid session and admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/login.php');
    exit;
}

require_once __DIR__ . '/../app/config/csrf.php';

// Automatically verify CSRF on all POST requests in Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        // Log the security event
        error_log("CSRF Verification Failed in Admin Panel for User ID: " . $_SESSION['user_id']);
        die("CSRF Verification Failed. Please refresh the page and try again.");
    }
}
?>
