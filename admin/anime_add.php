<?php
/**
 * Admin Add Anime
 *
 * This page provides a form to add a new anime to the database.
 * It handles input validation, file uploading for the cover image, and
 * inserting records into the 'anime' and 'anime_genre' tables.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch genres for the selection list
try {
    $genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
    $all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_genres = [];
    error_log("Failed to fetch genres: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input sanitization
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $synopsis = filter_var($_POST['synopsis'], FILTER_SANITIZE_STRING);
    $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $release_date = filter_var($_POST['release_date'], FILTER_SANITIZE_STRING);
    $language = filter_var($_POST['language'], FILTER_SANITIZE_STRING);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Handle Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/uploads/covers/';

        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['image']['name']));
        $target_file = $upload_dir . $filename;

        // Validate image file type
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $check = getimagesize($_FILES['image']['tmp_name']);
        if($check !== false && in_array($imageFileType, $allowed_types)) {
             if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                 $image_url = '/assets/uploads/covers/' . $filename;
             } else {
                 $error = "Failed to move uploaded file.";
             }
        } else {
            $error = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.";
        }
    }

    if (empty($error)) {
        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO anime (title, slug, synopsis, type, status, release_date, image_url, language) VALUES (:title, :slug, :synopsis, :type, :status, :release_date, :image_url, :language)");
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'synopsis' => $synopsis,
                'type' => $type,
                'status' => $status,
                'release_date' => $release_date,
                'image_url' => $image_url,
                'language' => $language
            ]);
            $anime_id = $conn->lastInsertId();

            // Add Genres
            $genre_insert = $conn->prepare("INSERT INTO anime_genre (anime_id, genre_id) VALUES (:anime_id, :genre_id)");
            foreach($selected_genres as $gid) {
                $genre_insert->execute(['anime_id' => $anime_id, 'genre_id' => (int)$gid]);
            }

            $conn->commit();
            $success = "Anime added successfully!";
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error adding anime to database: " . $e->getMessage();
            error_log("Add anime error: " . $e->getMessage());
        }
    }
}
?>

<h2>Add New Anime</h2>

<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="synopsis" class="form-label">Synopsis</label>
        <textarea id="synopsis" name="synopsis" class="form-control" rows="4"></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="type" class="form-label">Type</label>
            <select id="type" name="type" class="form-select">
                <option value="TV">TV Series</option>
                <option value="Movie">Movie</option>
                <option value="OVA">OVA</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="release_date" class="form-label">Release Date/Year</label>
            <input type="text" id="release_date" name="release_date" class="form-control" placeholder="e.g. 2023">
        </div>
        <div class="col-md-4 mb-3">
            <label for="language" class="form-label">Language</label>
            <select id="language" name="language" class="form-select">
                <option value="Sub">Sub</option>
                <option value="Dub">Dub</option>
                <option value="Chinese">Chinese</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Genres</label>
        <div class="row">
            <?php foreach($all_genres as $g): ?>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="genres[]" value="<?=$g['id']?>" id="g<?=$g['id']?>">
                    <label class="form-check-label" for="g<?=$g['id']?>">
                        <?=htmlspecialchars($g['name'])?>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-3">
        <label for="image" class="form-label">Cover Image</label>
        <input type="file" id="image" name="image" class="form-control" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary">Save Anime</button>
</form>

<?php require_once 'layout/footer.php'; ?>
