<?php
/**
 * Admin User Management
 *
 * This page allows administrators to view all registered users and manage their roles.
 * Admins can promote regular users to admin status or demote them.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Handle Role Change
if (isset($_POST['change_role'])) {
    // CSRF Verified in auth.php middleware
    $uid = intval($_POST['user_id']);
    $new_role = $_POST['role'];

    // Validate role input
    if (in_array($new_role, ['user', 'admin'])) {
        try {
            $conn->prepare("UPDATE users SET role = :role WHERE id = :id")->execute(['role' => $new_role, 'id' => $uid]);
            $success = "User role updated successfully.";
        } catch (PDOException $e) {
            $error = "Failed to update role.";
            error_log("User role update failed: " . $e->getMessage());
        }
    }
}

// Fetch Users
try {
    $stmt = $conn->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    error_log("Failed to fetch users: " . $e->getMessage());
}
?>

<h2>User Management</h2>
<?php if(isset($success)): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>
<?php if(isset($error)): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>

<div class="table-responsive">
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
                <td><?=htmlspecialchars($user['id'])?></td>
                <td><?=htmlspecialchars($user['name'])?></td>
                <td><?=htmlspecialchars($user['email'])?></td>
                <td>
                    <span class="badge bg-<?=($user['role']=='admin')?'danger':'secondary'?>">
                        <?=htmlspecialchars($user['role'])?>
                    </span>
                </td>
                <td><?=htmlspecialchars($user['created_at'])?></td>
                <td>
                    <!-- Self-demotion protection can be added here -->
                    <form method="POST" style="display:inline-block">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="user_id" value="<?=$user['id']?>">
                        <input type="hidden" name="role" value="<?=($user['role']=='admin')?'user':'admin'?>">
                        <input type="hidden" name="change_role" value="1">
                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Are you sure you want to change this user\'s role?')">
                            <?=($user['role']=='admin')?'Demote':'Promote'?>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
