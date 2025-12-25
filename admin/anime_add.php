<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch genres
$genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
$all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Countries
$country_stmt = $conn->query("SELECT * FROM countries ORDER BY name ASC");
$all_countries = $country_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Seasons
$season_stmt = $conn->query("SELECT * FROM seasons ORDER BY name ASC");
$all_seasons = $season_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Types
$type_stmt = $conn->query("SELECT * FROM types ORDER BY name ASC");
$all_types = $type_stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    $synopsis = $_POST['synopsis'];

    // Legacy type column (update to use type_id or keep string?)
    // I will use type_id. But anime table has 'type' varchar. I should update it to store name or value, or just rely on type_id.
    // For backward compatibility with existing frontend code (which I assume uses 'type' column), I will store the Type Name in 'type' column, and Type ID in 'type_id'.
    $type_id = $_POST['type_id'];
    // Find type name
    $type_name = 'TV';
    foreach($all_types as $t) { if($t['id'] == $type_id) $type_name = $t['name']; }

    $status = $_POST['status'];
    $release_date = $_POST['release_date'];
    $language = $_POST['language'];
    $selected_genres = isset($_POST['genres']) ? $_POST['genres'] : [];

    $country_id = !empty($_POST['country_id']) ? $_POST['country_id'] : null;
    $season_id = !empty($_POST['season_id']) ? $_POST['season_id'] : null;

    // Handle Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../assets/uploads/covers/';
        // Ensure dir exists
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

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
            $stmt = $conn->prepare("INSERT INTO anime (title, slug, synopsis, type, type_id, status, release_date, image_url, language, country_id, season_id) VALUES (:title, :slug, :synopsis, :type, :type_id, :status, :release_date, :image_url, :language, :country_id, :season_id)");
            $stmt->execute([
                'title' => $title,
                'slug' => $slug,
                'synopsis' => $synopsis,
                'type' => $type_name,
                'type_id' => $type_id,
                'status' => $status,
                'release_date' => $release_date,
                'image_url' => $image_url,
                'language' => $language,
                'country_id' => $country_id,
                'season_id' => $season_id
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
            <select name="type_id" class="form-control">
                <?php foreach($all_types as $t): ?>
                    <option value="<?=$t['id']?>"><?=$t['name']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                <option value="Ongoing">Ongoing</option>
                <option value="Completed">Completed</option>
                <option value="Upcoming">Upcoming</option>
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
        <div class="col-md-4 mb-3">
            <label>Country</label>
            <select name="country_id" class="form-control">
                <option value="">Select Country</option>
                <?php foreach($all_countries as $c): ?>
                    <option value="<?=$c['id']?>"><?=$c['name']?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label>Season</label>
            <select name="season_id" class="form-control">
                <option value="">Select Season</option>
                <?php foreach($all_seasons as $s): ?>
                    <option value="<?=$s['id']?>"><?=$s['name']?></option>
                <?php endforeach; ?>
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
