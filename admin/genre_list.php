<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM genres WHERE id = :id")->execute(['id' => $id]);
    header("Location: genre_list.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
$genres = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Genre List</h2>
<a href="genre_add.php" class="btn btn-primary mb-3">Add New</a>

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
            <td><?=$genre['id']?></td>
            <td><?=$genre['name']?></td>
            <td><?=$genre['slug']?></td>
            <td>
                <a href="?delete=<?=$genre['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
