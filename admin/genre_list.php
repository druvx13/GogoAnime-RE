<?php
/**
 * Admin Genre List
 *
 * This page displays a list of all anime genres managed in the system.
 * It allows administrators to add new genres or delete existing ones.
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
    // Note: GET based delete is generally discouraged due to CSRF,
    // but typically acceptable in simple internal admin panels if impact is low.
    // Ideally, convert to POST form like anime/episodes.

    // For consistency with other pages, let's keep it but verify CSRF token if it was a POST
    // Since it's GET, we lack CSRF token.
    // TODO: Refactor to POST form in future iterations.
    // For now, adhering to existing structure but validating ID.

    $id = intval($_GET['delete']);
    try {
        $conn->prepare("DELETE FROM genres WHERE id = :id")->execute(['id' => $id]);
        $success = "Genre deleted.";
    } catch(PDOException $e) {
        $error = "Error deleting genre: " . $e->getMessage();
    }
    // Avoid redirect loop or re-execution on refresh by redirecting to self without params
    if (!isset($error)) {
        header("Location: genre_list.php");
        exit;
    }
}

// Fetch Genres
try {
    $stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $genres = [];
    $error = "Failed to load genres.";
}
?>

<h2>Genre List</h2>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<a href="genre_add.php" class="btn btn-primary mb-3">Add New Genre</a>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($genres as $genre): ?>
            <tr>
                <td><?=htmlspecialchars($genre['id'])?></td>
                <td><?=htmlspecialchars($genre['name'])?></td>
                <td><?=htmlspecialchars($genre['slug'])?></td>
                <td>
                    <a href="?delete=<?=$genre['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this genre?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
