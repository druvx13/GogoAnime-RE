<?php
/**
 * Admin Comment Management
 *
 * This page displays user comments posted on anime episodes.
 * Administrators can moderate the community by deleting inappropriate comments.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $conn->prepare("DELETE FROM comments WHERE id = :id")->execute(['id' => $id]);
        header("Location: comments.php");
        exit;
    } catch(PDOException $e) {
        $error = "Failed to delete comment.";
        error_log("Delete comment error: " . $e->getMessage());
    }
}

// Fetch Comments with details
try {
    $stmt = $conn->query("
        SELECT c.*, u.name as user_name, e.episode_number, a.title as anime_title
        FROM comments c
        JOIN users u ON c.user_id = u.id
        JOIN episodes e ON c.episode_id = e.id
        JOIN anime a ON e.anime_id = a.id
        ORDER BY c.created_at DESC
    ");
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $comments = [];
    $error = "Failed to load comments.";
}
?>

<h2>Comments Management</h2>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Anime / Ep</th>
                <th>Content</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($comments as $comment): ?>
            <tr>
                <td style="white-space:nowrap;"><?=htmlspecialchars($comment['created_at'])?></td>
                <td><?=htmlspecialchars($comment['user_name'])?></td>
                <td><?=htmlspecialchars($comment['anime_title'])?> - Ep <?=htmlspecialchars($comment['episode_number'])?></td>
                <td><?=htmlspecialchars($comment['content'])?></td>
                <td>
                    <a href="?delete=<?=$comment['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this comment?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
