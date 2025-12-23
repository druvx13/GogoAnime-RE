<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch genres
$genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
$all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $synopsis = $_POST['synopsis'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $release_date = $_POST['release_date'];
    $language = $_POST['language'];
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Handle Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/uploads/covers/';
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;

        // Simple validation
        $check = getimagesize($_FILES['image']['tmp_name']);
        if($check !== false) {
             if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                 $image_url = '/assets/uploads/covers/' . $filename;
             } else {
                 $error = "Failed to upload image.";
             }
        } else {
            $error = "File is not an image.";
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
                $genre_insert->execute(['anime_id' => $anime_id, 'genre_id' => $gid]);
            }

            $conn->commit();
            $success = "Anime added successfully!";
        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error adding anime: " . $e->getMessage();
        }
    }
}
?>

<h2>Add New Anime</h2>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label>Title</label>
        <input type="text" name="title" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Synopsis</label>
        <textarea name="synopsis" class="form-control" rows="4"></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Type</label>
            <select name="type" class="form-control">
                <option value="TV">TV Series</option>
                <option value="Movie">Movie</option>
                <option value="OVA">OVA</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Release Date/Year</label>
            <input type="text" name="release_date" class="form-control" placeholder="e.g. 2023">
        </div>
        <div class="col-md-4 mb-3">
            <label>Language</label>
            <select name="language" class="form-control">
                <option value="Sub">Sub</option>
                <option value="Dub">Dub</option>
                <option value="Chinese">Chinese</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label>Genres</label>
        <div class="row">
            <?php foreach($all_genres as $g): ?>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="genres[]" value="<?=$g['id']?>" id="g<?=$g['id']?>">
                    <label class="form-check-label" for="g<?=$g['id']?>">
                        <?=$g['name']?>
                    </label>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="mb-3">
        <label>Cover Image</label>
        <input type="file" name="image" class="form-control" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary">Save Anime</button>
</form>

<?php require_once 'layout/footer.php'; ?>