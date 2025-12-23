<?php
/**
 * Admin Edit Episode
 *
 * This page allows administrators to update an existing episode.
 * Users can change the anime association, episode number, title, and manage
 * video sources (add new URLs, upload new files, update existing links).
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch all anime
try {
    $anime_stmt = $conn->query("SELECT id, title FROM anime ORDER BY title ASC");
    $animes = $anime_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $animes = [];
    error_log("Failed to fetch anime list: " . $e->getMessage());
}

// Fetch active providers
try {
    $providersStmt = $conn->query("SELECT * FROM video_providers WHERE is_active = 1 ORDER BY id ASC");
    $providers = $providersStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $providers = [];
    error_log("Failed to fetch providers: " . $e->getMessage());
}

$error = '';
$success = '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo "<div class='alert alert-danger'>Invalid Episode ID</div>";
    require_once 'layout/footer.php';
    exit;
}

// Fetch existing episode data
try {
    $stmt = $conn->prepare("SELECT * FROM episodes WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $episode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$episode) {
        echo "<div class='alert alert-danger'>Episode not found</div>";
        require_once 'layout/footer.php';
        exit;
    }

    // Fetch existing videos for this episode
    $videosStmt = $conn->prepare("SELECT * FROM episode_videos WHERE episode_id = :id");
    $videosStmt->execute(['id' => $id]);
    $existing_videos = [];
    while($row = $videosStmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_videos[$row['provider_id']] = $row['video_url'];
    }
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once 'layout/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize Inputs
    $anime_id = (int)$_POST['anime_id'];
    $episode_number = (float)$_POST['episode_number'];
    $title = filter_var($_POST['title'], FILTER_SANITIZE_STRING);
    $video_urls_input = $_POST['video_urls'] ?? [];

    $uploaded_video_url = '';

    // Handle Video Upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $upload_dir = '../assets/uploads/videos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '', basename($_FILES['video']['name']));
        $target_file = $upload_dir . $filename;

        // Simple validation for video types
        $allowed = array("mp4", "mkv", "webm", "avi");
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if(in_array(strtolower($ext), $allowed)) {
             if (move_uploaded_file($_FILES['video']['tmp_name'], $target_file)) {
                 $uploaded_video_url = '/assets/uploads/videos/' . $filename;
             } else {
                 $error = "Failed to upload video file.";
             }
        } else {
            $error = "Invalid video format. Allowed: mp4, mkv, webm, avi.";
        }
    }

    if (empty($error)) {
        try {
            $conn->beginTransaction();

            // 1. Update Episode Info
            // Update legacy video_url if a new upload happened, or if we want to sync it with one of the providers
            $legacy_url = $episode['video_url'];
            if ($uploaded_video_url) {
                $legacy_url = $uploaded_video_url;
            } elseif (empty($legacy_url) && !empty($video_urls_input)) {
                 // If no legacy url, take first available
                 foreach($video_urls_input as $u) { if($u) { $legacy_url = $u; break; } }
            }

            $stmt = $conn->prepare("UPDATE episodes SET anime_id = :anime_id, episode_number = :episode_number, title = :title, video_url = :video_url WHERE id = :id");
            $stmt->execute([
                'anime_id' => $anime_id,
                'episode_number' => $episode_number,
                'title' => $title,
                'video_url' => $legacy_url,
                'id' => $id
            ]);

            // 2. Update Video Providers

            // Handle uploaded file -> Local provider linkage
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
                    $video_urls_input[$localProv['id']] = $uploaded_video_url;
                }
            }

            // Sync all providers
            foreach ($providers as $provider) {
                $pid = $provider['id'];
                $url = isset($video_urls_input[$pid]) ? trim($video_urls_input[$pid]) : '';

                if (empty($url)) {
                    // Remove if empty
                    $conn->prepare("DELETE FROM episode_videos WHERE episode_id = ? AND provider_id = ?")->execute([$id, $pid]);
                } else {
                    // Check if exists
                    $check = $conn->prepare("SELECT id FROM episode_videos WHERE episode_id = ? AND provider_id = ?");
                    $check->execute([$id, $pid]);
                    if ($check->fetch()) {
                        // Update
                        $conn->prepare("UPDATE episode_videos SET video_url = ? WHERE episode_id = ? AND provider_id = ?")->execute([$url, $id, $pid]);
                    } else {
                        // Insert
                        $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)")->execute([$id, $pid, $url]);
                    }
                }
            }

            $conn->commit();
            $success = "Episode updated successfully!";

            // Refresh Data
            $stmt = $conn->prepare("SELECT * FROM episodes WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $episode = $stmt->fetch(PDO::FETCH_ASSOC);

            $videosStmt = $conn->prepare("SELECT * FROM episode_videos WHERE episode_id = :id");
            $videosStmt->execute(['id' => $id]);
            $existing_videos = [];
            while($row = $videosStmt->fetch(PDO::FETCH_ASSOC)) {
                $existing_videos[$row['provider_id']] = $row['video_url'];
            }

        } catch(PDOException $e) {
            $conn->rollBack();
            $error = "Error updating episode: " . $e->getMessage();
            error_log("Update episode error: " . $e->getMessage());
        }
    }
}
?>

<h2>Edit Episode</h2>

<?php if($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if($success): ?><div class="alert alert-success"><?=htmlspecialchars($success)?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <?php csrf_field(); ?>
    <div class="mb-3">
        <label for="anime_id" class="form-label">Select Anime</label>
        <select id="anime_id" name="anime_id" class="form-select" required>
            <option value="">-- Select Anime --</option>
            <?php foreach($animes as $anime): ?>
                <option value="<?=$anime['id']?>" <?=($episode['anime_id'] == $anime['id']) ? 'selected' : ''?>><?=htmlspecialchars($anime['title'])?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="episode_number" class="form-label">Episode Number</label>
        <input type="number" step="0.1" id="episode_number" name="episode_number" class="form-control" value="<?=htmlspecialchars($episode['episode_number'])?>" required>
    </div>
    <div class="mb-3">
        <label for="title" class="form-label">Episode Title (Optional)</label>
        <input type="text" id="title" name="title" class="form-control" value="<?=htmlspecialchars($episode['title'])?>">
    </div>

    <hr>
    <h4>Video Sources</h4>

    <?php foreach($providers as $provider): ?>
        <div class="mb-3 border p-3 rounded">
            <strong><?=htmlspecialchars($provider['label'])?></strong> (<?=htmlspecialchars($provider['name'])?>)

            <?php
            $is_local = (stripos($provider['name'], 'Local') !== false || stripos($provider['name'], 'Gogo') !== false);
            $current_url = $existing_videos[$provider['id']] ?? '';
            ?>

            <?php if ($is_local): ?>
                <div class="mt-2">
                    <label class="form-label">Upload New File</label>
                    <input type="file" name="video" class="form-control" accept="video/*">
                    <?php if($current_url): ?>
                        <small class="text-success">Current: <a href="<?=htmlspecialchars($current_url)?>" target="_blank">View Video</a></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mt-2">
                <label class="form-label">Video URL / Iframe</label>
                <input type="text" name="video_urls[<?=$provider['id']?>]" class="form-control" value="<?=htmlspecialchars($current_url)?>" placeholder="https://...">
            </div>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="btn btn-primary">Update Episode</button>
</form>

<?php require_once 'layout/footer.php'; ?>
