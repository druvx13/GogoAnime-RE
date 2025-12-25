<?php
require_once 'auth.php';
require_once 'layout/header.php';

$file_path = __DIR__ . '/../app/views/partials/advertisements/popup.html';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    if (file_put_contents($file_path, $content) !== false) {
        $success = "Ads configuration updated successfully.";
    } else {
        $error = "Failed to update configuration. Check file permissions.";
    }
}

// Read current content
$current_content = '';
if (file_exists($file_path)) {
    $current_content = file_get_contents($file_path);
} else {
    // Attempt to create if not exists
    $dir = dirname($file_path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($file_path, '');
}
?>

<div class="container mt-4">
    <h2>Ads Configuration (popup.html)</h2>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label">HTML Content</label>
            <textarea name="content" class="form-control" rows="15" style="font-family: monospace;"><?=htmlspecialchars($current_content)?></textarea>
            <div class="form-text">This content will be included in the footer of the website.</div>
        </div>
        <button type="submit" class="btn btn-primary">Save Configuration</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>
