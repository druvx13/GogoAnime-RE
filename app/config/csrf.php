<?php
/**
 * CSRF Protection
 *
 * This file handles Cross-Site Request Forgery (CSRF) protection mechanisms.
 * It includes functions to generate, verify, and output CSRF tokens.
 *
 * @package    GogoAnime Clone
 * @subpackage Configuration
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates a CSRF token and stores it in the session.
 *
 * If a token already exists in the session, it returns the existing one.
 * Otherwise, it generates a new cryptographically secure token.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token from a POST request.
 *
 * It checks if the token provided in the POST data matches the one stored in the session.
 *
 * @return bool True if the token is valid, False otherwise.
 */
function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
    return true; // Non-POST requests don't need CSRF verification
}

/**
 * Helper to render the hidden input field with the CSRF token.
 *
 * This function echoes an HTML input element.
 *
 * @return void
 */
function csrf_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
