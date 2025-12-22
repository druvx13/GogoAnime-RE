<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// [GAP-002] Admin Delete Implementation
if (isset($_POST['delete_id'])) {
    // CSRF verified automatically in auth.php
    $id = intval($_POST['delete_id']);
    try {
        $conn->prepare("DELETE FROM episodes WHERE id = :id")->execute(['id' => $id]);
        $success = "Episode deleted successfully.";
    } catch(PDOException $e) {
        $error = "Error deleting episode: " . $e->getMessage();
    }
}

$stmt = $conn->query("
    SELECT episodes.*, anime.title as anime_title
    FROM episodes
    JOIN anime ON episodes.anime_id = anime.id
    ORDER BY episodes.created_at DESC
");
$episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Episode List</h2>
<?php if(isset($success)): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

<a href="episode_add.php" class="btn btn-primary mb-3">Add New</a>

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
            <td><?=$episode['id']?></td>
            <td><?=htmlspecialchars($episode['anime_title'])?></td>
            <td><?=htmlspecialchars($episode['episode_number'])?></td>
            <td><?=htmlspecialchars($episode['title'])?></td>
            <td><a href="<?=$episode['video_url']?>" target="_blank">View</a></td>
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

<?php require_once 'layout/footer.php'; ?>