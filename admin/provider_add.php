<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($label)) {
        $error = "Name and Label are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO video_providers (name, label, is_active) VALUES (:name, :label, :is_active)");
        if ($stmt->execute(['name' => $name, 'label' => $label, 'is_active' => $is_active])) {
            $success = "Video Source added successfully.";
        } else {
            $error = "Failed to add video source.";
        }
    }
}
?>

<div class="container mt-4">
    <h2>Add Video Source</h2>
    <a href="provider_list.php" class="btn btn-secondary mb-3">Back to List</a>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label">Name (Internal)</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Gogo Server">
        </div>
        <div class="mb-3">
            <label class="form-label">Label (Display Name)</label>
            <input type="text" name="label" class="form-control" required placeholder="e.g. Gogo">
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" checked>
            <label class="form-check-label" for="activeCheck">Active</label>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>
