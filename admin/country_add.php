<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $value = trim($_POST['value']);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!empty($name)) {
        try {
            $stmt = $conn->prepare("INSERT INTO countries (name, slug, value) VALUES (:name, :slug, :value)");
            $stmt->execute(['name' => $name, 'slug' => $slug, 'value' => $value]);
            $success = "Country added successfully!";
        } catch(PDOException $e) {
            $error = "Error adding country: " . $e->getMessage();
        }
    } else {
        $error = "Name is required.";
    }
}
?>

<h2>Add New Country</h2>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<form method="POST">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label>Country Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Value (ID for filter, optional)</label>
        <input type="text" name="value" class="form-control">
    </div>
    <button type="submit" class="btn btn-primary">Save Country</button>
</form>

<?php require_once 'layout/footer.php'; ?>
