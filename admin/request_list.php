<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM requests WHERE id = :id")->execute(['id' => $id]);
    header("Location: request_list.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM requests ORDER BY created_at DESC");
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>User Requests</h2>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Link</th>
            <th>Message</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($requests as $r): ?>
        <tr>
            <td><?=$r['id']?></td>
            <td><?=htmlspecialchars($r['title'])?></td>
            <td>
                <?php if($r['link']): ?>
                    <a href="<?=htmlspecialchars($r['link'])?>" target="_blank">Link</a>
                <?php endif; ?>
            </td>
            <td><?=nl2br(htmlspecialchars($r['message']))?></td>
            <td><?=$r['created_at']?></td>
            <td>
                <a href="?delete=<?=$r['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
