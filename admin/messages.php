<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM contacts WHERE id = :id")->execute(['id' => $id]);
    header("Location: messages.php");
    exit;
}

$stmt = $conn->query("SELECT * FROM contacts ORDER BY created_at DESC");
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Contact Messages</h2>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($messages as $msg): ?>
        <tr>
            <td><?=$msg['created_at']?></td>
            <td><?=htmlspecialchars($msg['name'])?></td>
            <td><?=htmlspecialchars($msg['email'])?></td>
            <td><?=htmlspecialchars($msg['subject'])?></td>
            <td><?=nl2br(htmlspecialchars($msg['message']))?></td>
            <td>
                <a href="?delete=<?=$msg['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
