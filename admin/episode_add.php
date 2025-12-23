<?php
/**
 * Admin Add Episode
 *
 * This page allows administrators to add a new episode for an existing anime.
 * It supports multi-provider video support, including local file uploads and external URLs.
 * It populates both the 'episodes' table and the 'episode_videos' join table.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch all anime for the dropdown selection
try {
    $anime_stmt = $conn->query("SELECT id, title FROM anime ORDER BY title ASC");
    $animes = $anime_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $animes = [];
    error_log("Failed to fetch anime list: " . $e->getMessage());
}

// Fetch active video providers
try {
    $providersStmt = $conn->query("SELECT * FROM video_providers WHERE is_active = 1 ORDER BY id ASC");
    $providers = $providersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $providers = [];
    error_log("Failed to fetch providers: " . $e->getMessage());
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $anime_id = (int)$_POST['anime_id'];
    $episode_number = (float)$_POST['episode_number'];
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $video_urls_input = $_POST['video_urls'] ?? [];

    $uploaded_video_url = '';
    $has_video = false;
    $video_inserts = []; // Array of [provider_id, url]

    // Check for video file upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $has_video = true;
    }
    // Check for provided URLs
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
        // Handle Video File Upload
        if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
            $upload_dir = '../assets/uploads/videos/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            // Sanitize filename
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['video']['name']));
            $target_file = $upload_dir . $filename;

            $allowed = array("mp4", "mkv", "webm", "avi");
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if(in_array(strtolower($ext), $allowed)) {
                 if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                     $uploaded_video_url = '/assets/uploads/videos/' . $filename;
                 } else {
                     $error = "Failed to move uploaded video file.";
                 }
            } else {
                $error = "Invalid video format. Allowed: mp4, mkv, webm, avi.";
            }
        }

        if (empty($error)) {
            try {
                $conn->beginTransaction();

                // 1. Insert Episode Record
                // Use uploaded URL or first external URL as the 'primary' legacy URL
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

                // 2. Insert Video Provider Links
                $insertVidParams = [];

                // Link uploaded video to Local/Gogo provider
                if ($uploaded_video_url) {
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

                // Link other provider URLs
                foreach ($video_urls_input as $pid => $url) {
                    if (!empty(trim($url))) {
                        $is_duplicate = false;
                        foreach($insertVidParams as $param) {
                            if ($param[1] == $pid) {
                                $is_duplicate = true;
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
                error_log("Add episode error: " . $e->getMessage());
            }
        }
    }
}
?>

<h2>Add New Episode</h2>

<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label for="anime_id" class="form-label">Select Anime</label>
        <select id="anime_id" name="anime_id" class="form-select" required>
            <option value="">-- Select Anime --</option>
            <?php foreach($animes as $anime): ?>
                <option value="<?=$anime['id']?>"><?=htmlspecialchars($anime['title'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="episode_number" class="form-label">Episode Number</label>
        <input type="number" step="0.1" id="episode_number" name="episode_number" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Episode Title (Optional)</label>
        <input type="text" id="title" name="title" class="form-control">
    </div>

    <hr>
    <h4>Video Sources</h4>

    <?php foreach($providers as $provider): ?>
        <div class="mb-3 border p-3 rounded">
            <strong><?=htmlspecialchars($provider['label'])?></strong> (<?=htmlspecialchars($provider['name'])?>)

            <?php
            // Check if this is the local provider to show upload field
            $is_local = (stripos($provider['name'], 'Local') !== false || stripos($provider['name'], 'Gogo') !== false);
            ?>

            <?php if ($is_local): ?>
                <div class="mt-2">
                    <label class="form-label">Upload File (mp4, mkv, webm, avi)</label>
                    <input type="file" name="video" class="form-control" accept="video/*">
                    <small class="text-muted">Or provide a direct URL below</small>
                </div>
            <?php endif; ?>

            <div class="mt-2">
                <label class="form-label">Video URL / Iframe</label>
                <input type="text" name="video_urls[<?=$provider['id']?>]" class="form-control" placeholder="https://...">
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Upload Episode</button>
</form>

<?php require_once 'layout/footer.php'; ?>
