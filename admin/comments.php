<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->prepare("DELETE FROM comments WHERE id = :id")->execute(['id' => $id]);
    header("Location: comments.php");
    exit;
}

$stmt = $conn->query("
    SELECT c.*, u.name as user_name, e.episode_number, a.title as anime_title
    FROM comments c
    JOIN users u ON c.user_id = u.id
    JOIN episodes e ON c.episode_id = e.id
    JOIN anime a ON e.anime_id = a.id
    ORDER BY c.created_at DESC
");
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Comments Management</h2>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>User</th>
            <th>Anime / Ep</th>
            <th>Content</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($comments as $comment): ?>
        <tr>
            <td><?=$comment['created_at']?></td>
            <td><?=htmlspecialchars($comment['user_name'])?></td>
            <td><?=htmlspecialchars($comment['anime_title'])?> - Ep <?=$comment['episode_number']?></td>
            <td><?=htmlspecialchars($comment['content'])?></td>
            <td>
                <a href="?delete=<?=$comment['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>
