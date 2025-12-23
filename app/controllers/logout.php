<?php
/**
 * Logout Controller
 *
 * This script handles the user logout process. It destroys the session,
 * clears the 'remember_me' cookie, and redirects the user to the login page.
 *
 * @package    GogoAnime Clone
 * @subpackage Controllers
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

session_start();
require_once('../config/info.php');

// Destroy the session
session_destroy();

// Clear the remember_me cookie
setcookie('remember_me', '', time() - 3600, '/');

// Redirect to login page
header("Location: $base_url/login.html");
exit();
?>
