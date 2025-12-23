<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// [GAP-002] Admin Delete Implementation
if (isset($_POST['delete_id'])) {
    // CSRF verified automatically in auth.php
    $id = intval($_POST['delete_id']);
    try {
        $conn->prepare("DELETE FROM anime WHERE id = :id")->execute(['id' => $id]);
        $success = "Anime deleted successfully.";
    } catch(PDOException $e) {
        $error = "Error deleting anime: " . $e->getMessage();
    }
}

$stmt = $conn->query("SELECT * FROM anime ORDER BY created_at DESC");
$animes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Anime List</h2>
<?php if(isset($success)): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>

<a href="anime_add.php" class="btn btn-primary mb-3">Add New</a>

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
            <td><?=$anime['id']?></td>
            <td><img src="<?=$anime['image_url']?>" width="50"></td>
            <td><?=htmlspecialchars($anime['title'])?></td>
            <td><?=htmlspecialchars($anime['type'])?></td>
            <td><?=htmlspecialchars($anime['status'])?></td>
            <td>
                <a href="anime_edit.php?id=<?=$anime['id']?>" class="btn btn-sm btn-info">Edit</a>
                <form method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this anime?');">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="delete_id" value="<?=$anime['id']?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>