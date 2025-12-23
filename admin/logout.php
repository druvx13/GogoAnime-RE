<?php
/**
 * Admin Logout
 *
 * This script destroys the current session and redirects the user
 * back to the admin login page.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

session_start();
session_destroy();
header('Location: /admin/login.php');
exit;
?>
