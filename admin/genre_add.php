<?php
/**
 * Admin Add Genre
 *
 * This page allows administrators to add a new genre to the database.
 * It automatically generates a URL-friendly slug based on the genre name.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = trim(filter_var($_POST['name'], FILTER_SANITIZE_STRING));
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

    if (!empty($name)) {
        try {
            // Check for duplicates
            $check = $conn->prepare("SELECT id FROM genres WHERE slug = :slug");
            $check->execute(['slug' => $slug]);
            if ($check->fetch()) {
                $error = "Genre already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (:name, :slug)");
                $stmt->execute(['name' => $name, 'slug' => $slug]);
                $success = "Genre added successfully!";
            }
        } catch(PDOException $e) {
            $error = "Error adding genre: " . $e->getMessage();
            error_log("Add genre error: " . $e->getMessage());
        }
    } else {
        $error = "Genre name is required.";
    }
}
?>

<h2>Add New Genre</h2>

<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

<form method="POST">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label for="name" class="form-label">Genre Name</label>
        <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Action, Romance">
    </div>
    <button type="submit" class="btn btn-primary">Save Genre</button>
</form>

<?php require_once 'layout/footer.php'; ?>
