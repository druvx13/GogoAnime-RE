<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Auto-create table if not exists
try {
    $conn->query("SELECT 1 FROM api_configs LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE IF NOT EXISTS `api_configs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `url` varchar(255) NOT NULL,
      `is_active` tinyint(1) DEFAULT 0,
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

    // Seed default
    $conn->exec("INSERT INTO api_configs (name, url, is_active) VALUES ('Zen-API Main', 'https://anime-api-snowy.vercel.app/api', 1)");
}

$error = '';
$success = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $name = trim($_POST['name']);
            $url = rtrim(trim($_POST['url']), '/'); // Remove trailing slash

            if ($name && $url) {
                $stmt = $conn->prepare("INSERT INTO api_configs (name, url, is_active) VALUES (?, ?, 0)");
                $stmt->execute([$name, $url]);
                $success = "API URL added.";
            } else {
                $error = "Name and URL are required.";
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id'];
            // Don't delete if it's the only one or active?
            // Let's just allow delete but check if active.
            $stmt = $conn->prepare("DELETE FROM api_configs WHERE id = ?");
            $stmt->execute([$id]);
            $success = "API URL deleted.";
        } elseif ($_POST['action'] === 'activate') {
            $id = $_POST['id'];
            $conn->exec("UPDATE api_configs SET is_active = 0"); // Deactivate all
            $stmt = $conn->prepare("UPDATE api_configs SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Active API updated.";
        }
    }
}

// Fetch APIs
$apis = $conn->query("SELECT * FROM api_configs ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container mt-4">
    <h2>API Configuration</h2>
    <p>Manage the API endpoints used for the Zen-Importer.</p>

    <?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
    <?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">Add New API URL</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <div class="col-md-4">
                    <input type="text" name="name" class="form-control" placeholder="Name (e.g. Mirror 1)" required>
                </div>
                <div class="col-md-6">
                    <input type="url" name="url" class="form-control" placeholder="https://api.example.com/api" required>
                    <small class="text-muted">Include <code>/api</code> if required by the endpoint structure.</small>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Status</th>
                <th>Name</th>
                <th>URL</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($apis as $api): ?>
            <tr class="<?=$api['is_active'] ? 'table-success' : ''?>">
                <td class="text-center">
                    <?php if($api['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?=htmlspecialchars($api['name'])?></td>
                <td><?=htmlspecialchars($api['url'])?></td>
                <td>
                    <?php if(!$api['is_active']): ?>
                        <form method="POST" style="display:inline;">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="activate">
                            <input type="hidden" name="id" value="<?=$api['id']?>">
                            <button type="submit" class="btn btn-sm btn-success">Select</button>
                        </form>
                        <form method="POST" style="display:inline;">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?=$api['id']?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this API?')">Delete</button>
                        </form>
                    <?php else: ?>
                        <span class="text-muted">Cannot delete active</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
