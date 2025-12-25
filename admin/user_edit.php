<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

if (!isset($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check email uniqueness (ignore current user)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $id]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already exists.";
        } else {
            $sql = "UPDATE users SET name = :name, email = :email, role = :role";
            $params = ['name' => $name, 'email' => $email, 'role' => $role, 'id' => $id];

            if (!empty($password)) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = :id";

            $stmt = $conn->prepare($sql);
            if ($stmt->execute($params)) {
                $success = "User updated successfully.";
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update user.";
            }
        }
    }
}
?>

<div class="container mt-4">
    <h2>Edit User</h2>
    <a href="users.php" class="btn btn-secondary mb-3">Back to List</a>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['name'])?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email'])?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">New Password (leave blank to keep current)</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-control">
                <option value="user" <?=$user['role']=='user'?'selected':''?>>User</option>
                <option value="admin" <?=$user['role']=='admin'?'selected':''?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>
