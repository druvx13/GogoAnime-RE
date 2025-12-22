<?php
session_start();
require_once('../app/config/db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple CSRF check for AJAX (optional but good practice)
    // For now, assuming session auth is enough for this P1 feature,
    // but ideally we pass CSRF token in headers.
    // Sticking to basic auth check as per constraints.

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
            $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, anime_id) VALUES (:uid, :aid)");
            $stmt->execute(['uid' => $user_id, 'aid' => $anime_id]);
            echo json_encode(['success' => true, 'message' => 'Bookmarked']);
        } else {
            $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = :uid AND anime_id = :aid");
            $stmt->execute(['uid' => $user_id, 'aid' => $anime_id]);
            echo json_encode(['success' => true, 'message' => 'Removed']);
        }
    } catch(PDOException $e) {
        // likely duplicate entry for add
        if ($e->getCode() == 23000) {
             echo json_encode(['success' => true, 'message' => 'Already bookmarked']);
        } else {
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => 'Database error']);
        }
    }
}
?>