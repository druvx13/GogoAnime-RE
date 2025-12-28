<?php
/**
 * Bookmark Controller
 *
 * This script handles AJAX requests for adding or removing anime from a user's bookmarks.
 * It expects a JSON payload and returns a JSON response.
 *
 * @package    GogoAnime Clone
 * @subpackage Controllers
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

session_start();
require_once('../config/db.php');
require_once('../config/csrf.php');

// Set response type to JSON
header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF Token
    // Check header first (common for AJAX), then POST body
    $headers = getallheaders();
    $token = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : (isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');

    // Normalize headers keys if needed (server dependent), but assuming standard environment for now
    if (!$token && isset($headers['X-Csrf-Token'])) $token = $headers['X-Csrf-Token'];

    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF Token']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $anime_id = isset($input['anime_id']) ? (int)$input['anime_id'] : 0;
    $action = isset($input['action']) ? $input['action'] : 'add';
    $user_id = $_SESSION['user_id'];

    if (!$anime_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid Anime ID']);
        exit;
    }

    try {
        if ($action === 'add') {
            // Add bookmark
            $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, anime_id) VALUES (:uid, :aid)");
            $stmt->execute(['uid' => $user_id, 'aid' => $anime_id]);
            echo json_encode(['success' => true, 'message' => 'Bookmarked']);
        } else {
            // Remove bookmark
            $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = :uid AND anime_id = :aid");
            $stmt->execute(['uid' => $user_id, 'aid' => $anime_id]);
            echo json_encode(['success' => true, 'message' => 'Removed']);
        }
    } catch(PDOException $e) {
        // Handle duplicate entry error (Code 23000)
        if ($e->getCode() == 23000) {
             echo json_encode(['success' => true, 'message' => 'Already bookmarked']);
        } else {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
?>
