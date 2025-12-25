<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $value = trim($_POST['value']);

    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO seasons (name, value) VALUES (:name, :value)");
            $stmt->execute(['name' => $name, 'value' => $value]);
            $success = "Season added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding season: " . $e->getMessage();
        }
    } else {
        $error = "Name is required.";
    }
}
?>

<h2>Add New Season</h2>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<form method="POST">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label>Season Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Value (ID for filter, optional)</label>
        <input type="text" name="value" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Save Season</button>
</form>

<?php require_once 'layout/footer.php'; ?>
