<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch all anime for the dropdown
$anime_stmt = $conn->query("SELECT id, title FROM anime ORDER BY title ASC");
$animes = $anime_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch active providers
$providersStmt = $conn->query("SELECT * FROM video_providers WHERE is_active = 1 ORDER BY id ASC");
$providers = $providersStmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anime_id = $_POST['anime_id'];
    $episode_number = $_POST['episode_number'];
    $title = $_POST['title'];
    $video_urls_input = $_POST['video_urls'] ?? [];

    $uploaded_video_url = '';
    $has_video = false;
    $video_inserts = []; // Array of [provider_id, url]

    // Validate we have at least one video source
    // Check file upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $has_video = true;
    }
    // Check other inputs
    foreach ($video_urls_input as $pid => $url) {
        if (!empty(trim($url))) {
            $has_video = true;
            break;
        }
    }

    if (!$has_video) {
        $error = "At least one video source (upload or URL) is required.";
    }

    if (empty($error)) {
        // Handle File Upload if present
        if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
            $upload_dir = '../assets/uploads/videos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $filename = time() . '_' . basename($_FILES['video']['name']);
            $target_file = $upload_dir . $filename;

            $allowed = array("mp4", "mkv", "webm", "avi");
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if(in_array(strtolower($ext), $allowed)) {
                 if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                     $uploaded_video_url = '/assets/uploads/videos/' . $filename;
                 } else {
                     $error = "Failed to upload video.";
                 }
            } else {
                $error = "Invalid video format.";
            }
        }

        if (empty($error)) {
            try {
                $conn->beginTransaction();

                // 1. Insert Episode
                // We keep video_url for legacy/backup, using the uploaded one or the first external one
                $legacy_url = $uploaded_video_url;
                if (empty($legacy_url)) {
                     foreach ($video_urls_input as $url) {
                         if (!empty($url)) {
                             $legacy_url = $url;
                             break;
                         }
                     }
                }

                $stmt = $conn->prepare("INSERT INTO episodes (anime_id, episode_number, title, video_url) VALUES (:anime_id, :episode_number, :title, :video_url)");
                $stmt->execute([
                    'anime_id' => $anime_id,
                    'episode_number' => $episode_number,
                    'title' => $title,
                    'video_url' => $legacy_url
                ]);
                $episode_id = $conn->lastInsertId();

                // 2. Insert Video Providers
                $insertVidParams = [];

                // Add uploaded video to the 'Local/Gogo' provider (assuming it exists)
                if ($uploaded_video_url) {
                    // Find Local provider ID
                    $localProv = null;
                    foreach($providers as $p) {
                        if (stripos($p['name'], 'Local') !== false || stripos($p['name'], 'Gogo') !== false) {
                            $localProv = $p;
                            break;
                        }
                    }
                    if ($localProv) {
                         $insertVidParams[] = [$episode_id, $localProv['id'], $uploaded_video_url];
                    }
                }

                // Add other providers
                foreach ($video_urls_input as $pid => $url) {
                    if (!empty(trim($url))) {
                        // Avoid duplicate if we somehow mapped the upload to this ID already (unlikely with this logic but good to be safe)
                        $is_duplicate = false;
                        foreach($insertVidParams as $param) {
                            if ($param[1] == $pid) {
                                $is_duplicate = true;
                                // Update the URL if it was set (e.g. override local upload? No, let's assume upload takes precedence if both provided for same ID)
                                break;
                            }
                        }
                        if (!$is_duplicate) {
                            $insertVidParams[] = [$episode_id, $pid, trim($url)];
                        }
                    }
                }

                $vidStmt = $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)");
                foreach ($insertVidParams as $params) {
                    $vidStmt->execute($params);
                }

                $conn->commit();
                $success = "Episode added successfully!";
            } catch(PDOException $e) {
                $conn->rollBack();
                $error = "Error adding episode: " . $e->getMessage();
            }
        }
    }
}
?>

<h2>Add New Episode</h2>

<?php if($error): ?><div class="alert alert-danger"><?=$error?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=$success?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label>Select Anime</label>
        <select name="anime_id" class="form-control" required>
            <option value="">-- Select Anime --</option>
            <?php foreach($animes as $anime): ?>
                <option value="<?=$anime['id']?>"><?=$anime['title']?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Episode Number</label>
        <input type="number" step="0.1" name="episode_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Episode Title (Optional)</label>
        <input type="text" name="title" class="form-control">
    </div>

    <hr>
    <h4>Video Sources</h4>

    <?php foreach($providers as $provider): ?>
        <div class="mb-3 border p-3 rounded">
            <strong><?=$provider['label']?></strong> (<?=$provider['name']?>)

            <?php
            // Check if this is the local provider
            $is_local = (stripos($provider['name'], 'Local') !== false || stripos($provider['name'], 'Gogo') !== false);
            ?>

            <?php if ($is_local): ?>
                <div class="mt-2">
                    <label>Upload File (mp4, mkv, webm, avi)</label>
                    <input type="file" name="video" class="form-control" accept="video/*">
                    <small class="text-muted">Or provide a direct URL below</small>
                </div>
            <?php endif; ?>

            <div class="mt-2">
                <label>Video URL / Iframe</label>
                <input type="text" name="video_urls[<?=$provider['id']?>]" class="form-control" placeholder="https://...">
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Upload Episode</button>
</form>

<?php require_once 'layout/footer.php'; ?>