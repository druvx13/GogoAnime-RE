<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Auto-create table if not exists (Self-healing for legacy/existing databases)
try {
    $conn->query("SELECT 1 FROM video_providers LIMIT 1");
} catch (PDOException $e) {
    $conn->exec("CREATE TABLE IF NOT EXISTS `video_providers` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `label` varchar(100) NOT NULL,
      `is_active` tinyint(1) DEFAULT 1,
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
}

// Handle Delete
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);

    // Check count of active providers
    $count = $conn->query("SELECT COUNT(*) FROM video_providers")->fetchColumn();

    if ($count > 1) {
        $stmt = $conn->prepare("DELETE FROM video_providers WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $msg = "Provider deleted successfully.";
        $msg_type = "success";
    } else {
        $msg = "Cannot delete the last remaining provider.";
        $msg_type = "danger";
    }
}

// Fetch Providers
$stmt = $conn->query("SELECT * FROM video_providers ORDER BY id ASC");
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure Gogo exists if table is empty (Self-healing/Defaulting)
if (empty($providers)) {
    $conn->exec("INSERT INTO video_providers (name, label, is_active) VALUES ('Gogo Server', 'Gogo', 1)");
    header("Location: provider_list.php");
    exit;
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Video Sources Manager</h2>
        <a href="provider_add.php" class="btn btn-primary">Add New Source</a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="alert alert-<?=$msg_type?>"><?=$msg?></div>
    <?php endif; ?>

    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Label</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($providers as $provider): ?>
            <tr>
                <td><?=$provider['id']?></td>
                <td><?=htmlspecialchars($provider['name'])?></td>
                <td><?=htmlspecialchars($provider['label'])?></td>
                <td>
                    <span class="badge bg-<?=($provider['is_active'])?'success':'secondary'?>">
                        <?=($provider['is_active'])?'Active':'Inactive'?>
                    </span>
                </td>
                <td>
                    <a href="provider_edit.php?id=<?=$provider['id']?>" class="btn btn-sm btn-info">Edit</a>
                    <?php if(count($providers) > 1): ?>
                        <form method="POST" style="display:inline-block">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="delete_id" value="<?=$provider['id']?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                        </form>
                    <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled title="Cannot delete the sole source">Delete</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once 'layout/footer.php'; ?>
