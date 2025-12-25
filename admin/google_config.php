<?php
require_once 'auth.php';
require_once 'layout/header.php';

$file_path = __DIR__ . '/../app/config/google_auth.json';
$error = '';
$success = '';

// Initialize default config if not exists
$default_config = [
    'enabled' => false,
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = [
        'enabled' => isset($_POST['enabled']),
        'client_id' => trim($_POST['client_id']),
        'client_secret' => trim($_POST['client_secret']),
        'redirect_uri' => trim($_POST['redirect_uri'])
    ];

    if (file_put_contents($file_path, json_encode($config, JSON_PRETTY_PRINT)) !== false) {
        $success = "Google Auth configuration updated successfully.";
    } else {
        $error = "Failed to update configuration. Check file permissions.";
    }
}

// Read current content
if (file_exists($file_path)) {
    $current_config = json_decode(file_get_contents($file_path), true);
    // Merge with defaults to ensure all keys exist
    $current_config = array_merge($default_config, $current_config);
} else {
    $current_config = $default_config;
}
?>

<div class="container mt-4">
    <h2>Google Auth Configuration</h2>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <form method="POST">
        <?php csrf_field(); ?>

        <div class="mb-3 form-check">
            <input type="checkbox" name="enabled" class="form-check-input" id="enabledCheck" <?=$current_config['enabled'] ? 'checked' : ''?>>
            <label class="form-check-label" for="enabledCheck">Enable Google Login</label>
        </div>

        <div class="mb-3">
            <label class="form-label">Client ID</label>
            <input type="text" name="client_id" class="form-control" value="<?=htmlspecialchars($current_config['client_id'])?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Client Secret</label>
            <input type="text" name="client_secret" class="form-control" value="<?=htmlspecialchars($current_config['client_secret'])?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Redirect URI</label>
            <input type="text" name="redirect_uri" class="form-control" value="<?=htmlspecialchars($current_config['redirect_uri'])?>">
        </div>

        <button type="submit" class="btn btn-primary">Save Configuration</button>
    </form>
</div>

<?php require_once 'layout/footer.php'; ?>
