<?php
/**
 * Admin Anime List
 *
 * This page displays a paginated list of all anime in the database.
 * It provides options to add new anime, edit existing entries, or delete them.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Deletion
if (isset($_POST['delete_id'])) {
    // CSRF verification is handled automatically in auth.php
    $id = intval($_POST['delete_id']);
    try {
        // Prepare deletion statement
        $stmt = $conn->prepare("DELETE FROM anime WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $success = "Anime deleted successfully.";
    } catch(PDOException $e) {
        $error = "Error deleting anime: " . $e->getMessage();
        error_log("Delete anime failed: " . $e->getMessage());
    }
}

// Fetch all anime
try {
    $stmt = $conn->query("SELECT * FROM anime ORDER BY created_at DESC");
    $animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $animes = [];
    $error = "Failed to load anime list.";
    error_log("Load anime list failed: " . $e->getMessage());
}
?>

<h2>Anime List</h2>
<?php if(isset($success)): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<a href="anime_add.php" class="btn btn-primary mb-3">Add New Anime</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Title</th>
                <th>Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($animes as $anime): ?>
            <tr>
                <td><?=htmlspecialchars($anime['id'])?></td>
                <td><img src="<?=htmlspecialchars($anime['image_url'])?>" width="50" alt="Thumbnail"></td>
                <td><?=htmlspecialchars($anime['title'])?></td>
                <td><?=htmlspecialchars($anime['type'])?></td>
                <td><?=htmlspecialchars($anime['status'])?></td>
                <td>
                    <a href="anime_edit.php?id=<?=$anime['id']?>" class="btn btn-sm btn-info">Edit</a>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this anime? This action cannot be undone.');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="delete_id" value="<?=$anime['id']?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
