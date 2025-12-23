<?php
/**
 * Admin Episode List
 *
 * This page displays a list of all anime episodes.
 * It allows administrators to add, edit, or delete episodes.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Episode Deletion
if (isset($_POST['delete_id'])) {
    // CSRF verification is handled automatically in auth.php
    $id = intval($_POST['delete_id']);
    try {
        $conn->prepare("DELETE FROM episodes WHERE id = :id")->execute(['id' => $id]);
        $success = "Episode deleted successfully.";
    } catch(PDOException $e) {
        $error = "Error deleting episode: " . $e->getMessage();
        error_log("Delete episode error: " . $e->getMessage());
    }
}

// Fetch all episodes with anime titles
try {
    $stmt = $conn->query("
        SELECT episodes.*, anime.title as anime_title
        FROM episodes
        JOIN anime ON episodes.anime_id = anime.id
        ORDER BY episodes.created_at DESC
    ");
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $episodes = [];
    $error = "Failed to load episodes.";
    error_log("Load episodes error: " . $e->getMessage());
}
?>

<h2>Episode List</h2>
<?php if(isset($success)): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<a href="episode_add.php" class="btn btn-primary mb-3">Add New Episode</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Anime</th>
                <th>Ep #</th>
                <th>Title</th>
                <th>Video</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($episodes as $episode): ?>
            <tr>
                <td><?=htmlspecialchars($episode['id'])?></td>
                <td><?=htmlspecialchars($episode['anime_title'])?></td>
                <td><?=htmlspecialchars($episode['episode_number'])?></td>
                <td><?=htmlspecialchars($episode['title'])?></td>
                <td><a href="<?=htmlspecialchars($episode['video_url'])?>" target="_blank">View</a></td>
                <td>
                    <a href="episode_edit.php?id=<?=$episode['id']?>" class="btn btn-sm btn-info">Edit</a>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this episode?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="delete_id" value="<?=$episode['id']?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
