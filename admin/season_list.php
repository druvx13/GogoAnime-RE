<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM seasons WHERE id = :id")->execute(['id' => $id]);
    header("Location: season_list.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM seasons ORDER BY name ASC");
$seasons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Season List</h2>
<a href="season_add.php" class="btn btn-primary mb-3">Add New</a>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Value</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($seasons as $s): ?>
        <tr>
            <td><?=$s['id']?></td>
            <td><?=htmlspecialchars($s['name'])?></td>
            <td><?=htmlspecialchars($s['value'])?></td>
            <td>
                <a href="?delete=<?=$s['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
