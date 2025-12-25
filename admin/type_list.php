<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM types WHERE id = :id")->execute(['id' => $id]);
    header("Location: type_list.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM types ORDER BY name ASC");
$types = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Type List</h2>
<a href="type_add.php" class="btn btn-primary mb-3">Add New</a>

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
        <?php foreach($types as $t): ?>
        <tr>
            <td><?=$t['id']?></td>
            <td><?=htmlspecialchars($t['name'])?></td>
            <td><?=htmlspecialchars($t['value'])?></td>
            <td>
                <a href="?delete=<?=$t['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
