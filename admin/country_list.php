<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM countries WHERE id = :id")->execute(['id' => $id]);
    header("Location: country_list.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM countries ORDER BY name ASC");
$countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Country List</h2>
<a href="country_add.php" class="btn btn-primary mb-3">Add New</a>

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
        <?php foreach($countries as $c): ?>
        <tr>
            <td><?=$c['id']?></td>
            <td><?=htmlspecialchars($c['name'])?></td>
            <td><?=htmlspecialchars($c['value'])?></td>
            <td>
                <a href="?delete=<?=$c['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
