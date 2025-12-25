<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Role Change
if (isset($_POST['change_role'])) {
    // CSRF Verified in auth.php
    $uid = intval($_POST['user_id']);
    $new_role = $_POST['role'];
    if (in_array($new_role, ['user', 'admin'])) {
        $conn->prepare("UPDATE users SET role = :role WHERE id = :id")->execute(['role' => $new_role, 'id' => $uid]);
        $success = "User role updated.";
    }
}

// Fetch Users
$stmt = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>User Management</h2>
    <a href="user_add.php" class="btn btn-primary">Add New User</a>
</div>

<?php if(isset($success)): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Joined</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($users as $user): ?>
        <tr>
            <td><?=$user['id']?></td>
            <td><?=htmlspecialchars($user['name'])?></td>
            <td><?=htmlspecialchars($user['email'])?></td>
            <td>
                <span class="badge bg-<?=($user['role']=='admin')?'danger':'secondary'?>">
                    <?=$user['role']?>
                </span>
            </td>
            <td><?=$user['created_at']?></td>
            <td>
                <a href="user_edit.php?id=<?=$user['id']?>" class="btn btn-sm btn-info">Edit</a>
                <form method="POST" style="display:inline-block">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?=$user['id']?>">
                    <input type="hidden" name="role" value="<?=($user['role']=='admin')?'user':'admin'?>">
                    <input type="hidden" name="change_role" value="1">
                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Change role?')">
                        <?=($user['role']=='admin')?'Demote':'Promote'?>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php require_once 'layout/footer.php'; ?>