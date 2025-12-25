<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

if (!isset($_GET['id'])) {
    header("Location: provider_list.php");
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM video_providers WHERE id = :id");
$stmt->execute(['id' => $id]);
$provider = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$provider) {
    die("Provider not found.");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $label = trim($_POST['label']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || empty($label)) {
        $error = "Name and Label are required.";
    } else {
        $stmt = $conn->prepare("UPDATE video_providers SET name = :name, label = :label, is_active = :is_active WHERE id = :id");
        if ($stmt->execute(['name' => $name, 'label' => $label, 'is_active' => $is_active, 'id' => $id])) {
            $success = "Video Source updated successfully.";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM video_providers WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Failed to update video source.";
        }
    }
}
?>

<div class="container mt-4">
    <h2>Edit Video Source</h2>
    <a href="provider_list.php" class="btn btn-secondary mb-3">Back to List</a>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label">Name (Internal)</label>
            <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($provider['name'])?>" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Label (Display Name)</label>
            <input type="text" name="label" class="form-control" value="<?=htmlspecialchars($provider['label'])?>" required>
        </div>
        <div class="mb-3 form-check">
            <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck" <?=$provider['is_active'] ? 'checked' : ''?>>
            <label class="form-check-label" for="activeCheck">Active</label>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>
