<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch all genres
$genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
$all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo "<div class='alert alert-danger'>Invalid Anime ID</div>";
    require_once 'layout/footer.php';
    exit;
}

// Fetch existing anime data
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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    // Optional: Allow updating slug? Usually risky for SEO, but let's allow it if title changes significantly or logic requires.
    // For now, let's keep slug as is or update it only if explicitly requested. I'll stick to updating fields.
    $synopsis = $_POST['synopsis'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $release_date = $_POST['release_date'];
    $language = $_POST['language'];
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    // Handle Image Upload
    $image_url = $anime['image_url']; // Default to existing
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
                $genre_insert->execute(['anime_id' => $id, 'genre_id' => $gid]);
            }

            $conn->commit();
            $success = "Anime updated successfully!";

            // Refresh data
            $stmt->execute(['id' => $id]);
            $anime = $stmt->fetch(PDO::FETCH_ASSOC);
            $ag_stmt->execute(['aid' => $id]);
            $current_genres = $ag_stmt->fetchAll(PDO::FETCH_COLUMN);

        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error updating anime: " . $e->getMessage();
        }
    }
}
?>

<h2>Edit Anime: <?=htmlspecialchars($anime['title'])?></h2>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label>Title</label>
        <input type="text" name="title" class="form-control" value="<?=htmlspecialchars($anime['title'])?>" required>
    </div>
    <div class="mb-3">
        <label>Synopsis</label>
        <textarea name="synopsis" class="form-control" rows="4"><?=htmlspecialchars($anime['synopsis'])?></textarea>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label>Type</label>
            <select name="type" class="form-control">
                <option value="TV" <?=($anime['type']=='TV')?'selected':''?>>TV Series</option>
                <option value="Movie" <?=($anime['type']=='Movie')?'selected':''?>>Movie</option>
                <option value="OVA" <?=($anime['type']=='OVA')?'selected':''?>>OVA</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="Ongoing" <?=($anime['status']=='Ongoing')?'selected':''?>>Ongoing</option>
                <option value="Completed" <?=($anime['status']=='Completed')?'selected':''?>>Completed</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Release Date/Year</label>
            <input type="text" name="release_date" class="form-control" value="<?=htmlspecialchars($anime['release_date'])?>" placeholder="e.g. 2023">
        </div>
        <div class="col-md-4 mb-3">
            <label>Language</label>
            <select name="language" class="form-control">
                <option value="Sub" <?=($anime['language']=='Sub')?'selected':''?>>Sub</option>
                <option value="Dub" <?=($anime['language']=='Dub')?'selected':''?>>Dub</option>
                <option value="Chinese" <?=($anime['language']=='Chinese')?'selected':''?>>Chinese</option>
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label>Genres</label>
        <div class="row">
            <?php foreach($all_genres as $g): ?>
            <div class="col-md-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="genres[]" value="<?=$g['id']?>" id="g<?=$g['id']?>" <?=(in_array($g['id'], $current_genres))?'checked':''?>>
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
        <?php if($anime['image_url']): ?>
            <div class="mb-2"><img src="<?=$anime['image_url']?>" width="100"></div>
        <?php endif; ?>
        <input type="file" name="image" class="form-control" accept="image/*">
        <small>Leave empty to keep current image</small>
    </div>
    <button type="submit" class="btn btn-primary">Update Anime</button>
</form>

<?php require_once 'layout/footer.php'; ?>