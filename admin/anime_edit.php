<?php
/**
 * Admin Edit Anime
 *
 * This page allows administrators to update details of an existing anime.
 * It pre-fills the form with current data (title, synopsis, genres, etc.) and
 * handles updating the database, including genre associations and cover image.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch all genres for selection
try {
    $genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
    $all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $all_genres = [];
    error_log("Failed to fetch genres: " . $e->getMessage());
}

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo "<div class='alert alert-danger'>Invalid Anime ID</div>";
    require_once 'layout/footer.php';
    exit;
}

// Fetch existing anime data
try {
    $stmt = $conn->prepare("SELECT * FROM anime WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$anime) {
        echo "<div class='alert alert-danger'>Anime not found</div>";
        require_once 'layout/footer.php';
        exit;
    }

    // Fetch existing genres
    $ag_stmt = $conn->prepare("SELECT genre_id FROM anime_genre WHERE anime_id = :aid");
    $ag_stmt->execute(['aid' => $id]);
    $current_genres = $ag_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once 'layout/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize Inputs
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $synopsis = filter_var($_POST['synopsis'], FILTER_SANITIZE_STRING);
    $type = filter_var($_POST['type'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $release_date = filter_var($_POST['release_date'], FILTER_SANITIZE_STRING);
    $language = filter_var($_POST['language'], FILTER_SANITIZE_STRING);
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Handle Image Upload
    $image_url = $anime['image_url']; // Default to existing
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/uploads/covers/';

        // Ensure directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['image']['name']));
        $target_file = $upload_dir . $filename;

        // Validate image
        $check = getimagesize($_FILES['image']['tmp_name']);
        if($check !== false) {
             if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                 $image_url = '/assets/uploads/covers/' . $filename;
             } else {
                 $error = "Failed to move uploaded file.";
             }
        } else {
            $error = "File is not an image.";
        }
    }

    if (empty($error)) {
        try {
            $conn->beginTransaction();
            $updateStmt = $conn->prepare("UPDATE anime SET title = :title, synopsis = :synopsis, type = :type, status = :status, release_date = :release_date, image_url = :image_url, language = :language WHERE id = :id");
            $updateStmt->execute([
                'title' => $title,
                'synopsis' => $synopsis,
                'type' => $type,
                'status' => $status,
                'release_date' => $release_date,
                'image_url' => $image_url,
                'language' => $language,
                'id' => $id
            ]);

            // Update Genres: Delete all and re-insert
            $conn->prepare("DELETE FROM anime_genre WHERE anime_id = :id")->execute(['id' => $id]);
            $genre_insert = $conn->prepare("INSERT INTO anime_genre (anime_id, genre_id) VALUES (:anime_id, :genre_id)");
            foreach($selected_genres as $gid) {
                $genre_insert->execute(['anime_id' => $id, 'genre_id' => (int)$gid]);
            }

            $conn->commit();
            $success = "Anime updated successfully!";

            // Refresh data for display
            $stmt->execute(['id' => $id]);
            $anime = $stmt->fetch(PDO::FETCH_ASSOC);
            $ag_stmt->execute(['aid' => $id]);
            $current_genres = $ag_stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error updating anime: " . $e->getMessage();
            error_log("Update anime error: " . $e->getMessage());
        }
    }
}
?>

<h2>Edit Anime: <?=htmlspecialchars($anime['title'])?></h2>

<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label for="title" class="form-label">Title</label>
        <input type="text" id="title" name="title" class="form-control" value="<?=htmlspecialchars($anime['title'])?>" required>
    </div>
    <div class="mb-3">
        <label for="synopsis" class="form-label">Synopsis</label>
        <textarea id="synopsis" name="synopsis" class="form-control" rows="4"><?=htmlspecialchars($anime['synopsis'])?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="type" class="form-label">Type</label>
            <select id="type" name="type" class="form-select">
                <option value="TV" <?=($anime['type']=='TV')?'selected':''?>>TV Series</option>
                <option value="Movie" <?=($anime['type']=='Movie')?'selected':''?>>Movie</option>
                <option value="OVA" <?=($anime['type']=='OVA')?'selected':''?>>OVA</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="Ongoing" <?=($anime['status']=='Ongoing')?'selected':''?>>Ongoing</option>
                <option value="Completed" <?=($anime['status']=='Completed')?'selected':''?>>Completed</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="release_date" class="form-label">Release Date/Year</label>
            <input type="text" id="release_date" name="release_date" class="form-control" value="<?=htmlspecialchars($anime['release_date'])?>" placeholder="e.g. 2023">
        </div>
        <div class="col-md-4 mb-3">
            <label for="language" class="form-label">Language</label>
            <select id="language" name="language" class="form-select">
                <option value="Sub" <?=($anime['language']=='Sub')?'selected':''?>>Sub</option>
                <option value="Dub" <?=($anime['language']=='Dub')?'selected':''?>>Dub</option>
                <option value="Chinese" <?=($anime['language']=='Chinese')?'selected':''?>>Chinese</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Genres</label>
        <div class="row">
            <?php foreach($all_genres as $g): ?>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="genres[]" value="<?=$g['id']?>" id="g<?=$g['id']?>" <?=(in_array($g['id'], $current_genres))?'checked':''?>>
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
        <?php if($anime['image_url']): ?>
            <div class="mb-2"><img src="<?=htmlspecialchars($anime['image_url'])?>" width="100" alt="Cover"></div>
        <?php endif; ?>
        <input type="file" id="image" name="image" class="form-control" accept="image/*">
        <small class="text-muted">Leave empty to keep current image</small>
    </div>
    <button type="submit" class="btn btn-primary">Update Anime</button>
</form>

<?php require_once 'layout/footer.php'; ?>
