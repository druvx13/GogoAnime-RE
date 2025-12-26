<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Increase execution time for large imports
set_time_limit(300);

// --- HELPER FUNCTIONS ---

function getActiveApiUrl() {
    global $conn;
    // Auto-create table if missing (Self-healing in case visited before config page)
    try {
        $stmt = $conn->query("SELECT url FROM api_configs WHERE is_active = 1 LIMIT 1");
        if ($row = $stmt->fetch()) {
            return $row['url'];
        }
    } catch (PDOException $e) {
        // Table doesn't exist? Use default
    }
    return "https://anime-api-snowy.vercel.app/api";
}

function fetchZen($endpoint) {
    // Basic rate limiting to be polite
    usleep(200000); // 200ms

    $base_url = getActiveApiUrl();
    // Ensure no double slash issues
    $url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    // User-Agent is important for some APIs
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZenImporter/1.0');
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code != 200) return null;
    return json_decode($data, true);
}

function downloadImage($url, $save_dir) {
    if (empty($url)) return '';

    // Ensure directory exists
    if (!file_exists($save_dir)) mkdir($save_dir, 0777, true);

    $filename = time() . '_' . basename(parse_url($url, PHP_URL_PATH));
    // Fallback if no extension or clean name
    if (!pathinfo($filename, PATHINFO_EXTENSION)) $filename .= '.jpg';

    $save_path = $save_dir . $filename;

    $ch = curl_init($url);
    $fp = fopen($save_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    // If download failed or file is empty, cleanup
    if ($error || filesize($save_path) == 0) {
        if (file_exists($save_path)) unlink($save_path);
        return '';
    }

    return $filename;
}

function mapStatus($zenStatus) {
    $s = strtolower($zenStatus);
    if (strpos($s, 'finished') !== false) return 'Completed';
    if (strpos($s, 'currently') !== false) return 'Ongoing';
    if (strpos($s, 'upcoming') !== false) return 'Upcoming';
    return 'Completed'; // Default
}

// --- INITIALIZATION ---

$step = $_GET['step'] ?? 'search';
$keyword = $_GET['keyword'] ?? '';
$import_id = $_GET['id'] ?? '';
$msg = '';
$msg_type = '';

// Ensure Zen-API Provider exists
$zen_provider_id = 0;
$stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = 'Zen-API'");
$stmt->execute();
if($row = $stmt->fetch()) {
    $zen_provider_id = $row['id'];
} else {
    $conn->exec("INSERT INTO video_providers (name, label, is_active) VALUES ('Zen-API', 'Zen-Stream', 1)");
    $zen_provider_id = $conn->lastInsertId();
}

// Fetch DB Types for mapping
$db_types = $conn->query("SELECT * FROM types")->fetchAll(PDO::FETCH_ASSOC);
// Fetch DB Genres for mapping
$db_genres = $conn->query("SELECT * FROM genres")->fetchAll(PDO::FETCH_ASSOC);

// --- IMPORT LOGIC ---

if ($step === 'process_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $zen_id = $_POST['zen_id'];

    // 1. Fetch Details
    $info = fetchZen("/info?id=" . urlencode($zen_id));

    if (!$info || !isset($info['success']) || !$info['success']) {
        $msg = "Failed to fetch anime details from Zen-API.";
        $msg_type = "danger";
        $step = 'search';
    } else {
        $data = $info['results']['data'] ?? [];
        if (empty($data)) {
             $msg = "Invalid data received from Zen-API.";
             $msg_type = "danger";
             $step = 'search';
        } else {
            $title = $data['title'];
            $synopsis = $data['animeInfo']['Overview'] ?? '';
            $poster_url = $data['poster'];
            $showType = $data['showType'] ?? 'TV';
            $statusRaw = $data['animeInfo']['Status'] ?? '';
            $releaseDate = $data['animeInfo']['Aired'] ?? '';

            // 2. Duplicate Check
            $check = $conn->prepare("SELECT id FROM anime WHERE title = ?");
            $check->execute([$title]);
            if ($existing = $check->fetch()) {
                $msg = "Anime '$title' already exists (ID: {$existing['id']}). Import skipped to prevent duplicates.";
                $msg_type = "warning";
                $step = 'search';
            } else {
                // 3. Download Cover
                // Move download logic here, but still before transaction for simplicity,
                // but implement cleanup on failure.
                $local_image_name = downloadImage($poster_url, '../assets/uploads/covers/');
                $image_url = $local_image_name ? '/assets/uploads/covers/' . $local_image_name : '';

                // 4. Map Type
                $type_id = 1; // Default
                $type_name = 'TV';
                foreach($db_types as $t) {
                    if (stripos($t['name'], $showType) !== false) {
                        $type_id = $t['id'];
                        $type_name = $t['name'];
                        break;
                    }
                }

                // 5. Insert Anime
                $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
                $final_status = mapStatus($statusRaw);

                try {
                    $conn->beginTransaction();

                    $stmt = $conn->prepare("INSERT INTO anime (title, slug, synopsis, type, type_id, status, release_date, image_url, language) VALUES (:title, :slug, :synopsis, :type, :type_id, :status, :release_date, :image_url, 'Sub')");
                    $stmt->execute([
                        'title' => $title,
                        'slug' => $slug,
                        'synopsis' => $synopsis,
                        'type' => $type_name,
                        'type_id' => $type_id,
                        'status' => $final_status,
                        'release_date' => $releaseDate,
                        'image_url' => $image_url
                    ]);
                    $new_anime_id = $conn->lastInsertId();

                    // 6. Map and Insert Genres
                    $zen_genres = $data['animeInfo']['Genres'] ?? [];
                    if (is_array($zen_genres)) {
                        foreach($zen_genres as $g) {
                            $gName = is_array($g) ? ($g['name'] ?? '') : $g;
                            if (!$gName) continue;

                            $gid = null;
                            foreach($db_genres as $dbg) {
                                if (strcasecmp($dbg['name'], $gName) === 0) {
                                    $gid = $dbg['id'];
                                    break;
                                }
                            }

                            if (!$gid) {
                                $gs = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $gName)));
                                $cstmt = $conn->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
                                $cstmt->execute([$gName, $gs]);
                                $gid = $conn->lastInsertId();
                                $db_genres[] = ['id' => $gid, 'name' => $gName];
                            }

                            $conn->prepare("INSERT INTO anime_genre (anime_id, genre_id) VALUES (?, ?)")->execute([$new_anime_id, $gid]);
                        }
                    }

                    $conn->commit();

                    // 7. Episodes & Streaming
                    // Note: We commit the anime first so we don't lose it if episode fetching fails/times out.
                    // This is safer for partial imports on long lists.

                    $eps_data = fetchZen("/episodes/" . urlencode($zen_id));
                    $episodes = $eps_data['results']['episodes'] ?? [];

                    $imported_eps = 0;

                    foreach ($episodes as $ep) {
                        $ep_num = $ep['episode_no'];
                        $ep_zen_id = $ep['id'];

                        // Insert Episode
                        $estmt = $conn->prepare("INSERT INTO episodes (anime_id, episode_number, title, video_url) VALUES (?, ?, ?, ?)");
                        $estmt->execute([$new_anime_id, $ep_num, "Episode $ep_num", ""]);
                        $local_ep_id = $conn->lastInsertId();

                        // Fetch Stream Link
                        $srvs = fetchZen("/servers/" . urlencode($ep_zen_id));
                        $server_list = $srvs['results'] ?? [];

                        $chosen_server = null;
                        if (is_array($server_list)) {
                            foreach($server_list as $s) {
                                 if (stripos($s['serverName'], 'vidstreaming') !== false) { $chosen_server = $s; break; }
                                 if (stripos($s['serverName'], 'gogostream') !== false) { $chosen_server = $s; break; }
                            }
                            if (!$chosen_server && !empty($server_list)) $chosen_server = $server_list[0];
                        }

                        if ($chosen_server) {
                            $stream_url = "/stream?id=" . urlencode($ep_zen_id) . "&server=" . urlencode($chosen_server['serverName']) . "&type=sub";
                            $stream_data = fetchZen($stream_url);

                            $final_link = '';
                            if (isset($stream_data['results']['streamingLink']) && is_array($stream_data['results']['streamingLink'])) {
                                $links = $stream_data['results']['streamingLink'];
                                 if (isset($links[0]['link']['file'])) {
                                     $final_link = $links[0]['link']['file'];
                                 }
                            }

                            if ($final_link) {
                                $vstmt = $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)");
                                $vstmt->execute([$local_ep_id, $zen_provider_id, $final_link]);
                                $conn->prepare("UPDATE episodes SET video_url = ? WHERE id = ?")->execute([$final_link, $local_ep_id]);
                            }
                        }

                        $imported_eps++;

                        // Small delay to prevent API flooding during loop
                        usleep(100000); // 100ms
                    }

                    $msg = "Import Successful! Added '$title' with $imported_eps episodes.";
                    $msg_type = "success";
                    $step = 'search';

                } catch (Exception $e) {
                    if ($conn->inTransaction()) $conn->rollBack();
                    // Cleanup image if transaction failed
                    if ($local_image_name && file_exists('../assets/uploads/covers/' . $local_image_name)) {
                        unlink('../assets/uploads/covers/' . $local_image_name);
                    }
                    $msg = "Error during import: " . $e->getMessage();
                    $msg_type = "danger";
                    $step = 'search';
                }
            }
        }
    }
}

?>

<div class="container mt-4">
    <h2><i class="fas fa-cloud-download-alt"></i> Import Anime from Zen-API</h2>

    <?php if($msg): ?>
        <div class="alert alert-<?=$msg_type?>"><?=$msg?></div>
    <?php endif; ?>

    <!-- SEARCH INTERFACE -->
    <?php if ($step === 'search'): ?>
        <div class="card p-4">
            <form method="GET">
                <div class="input-group">
                    <input type="text" name="keyword" class="form-control" placeholder="Search anime by title..." value="<?=htmlspecialchars($keyword)?>" required>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>

        <?php
        if ($keyword) {
            $data = fetchZen("/search?keyword=" . urlencode($keyword));
            if ($data && isset($data['success']) && $data['success']) {
                $results = $data['results'] ?? [];
                if (!empty($results)) {
                ?>
                <div class="mt-4">
                    <h4>Search Results for "<?=htmlspecialchars($keyword)?>"</h4>
                    <table class="table table-bordered table-hover mt-3">
                        <thead class="table-dark">
                            <tr>
                                <th width="100">Image</th>
                                <th>Title</th>
                                <th>Original Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $item): ?>
                            <tr>
                                <td><img src="<?=$item['poster']?>" width="60" class="rounded"></td>
                                <td>
                                    <strong><?=$item['title']?></strong><br>
                                    <small class="text-muted">ID: <?=$item['id']?></small>
                                </td>
                                <td><?=$item['jname'] ?? '-'?></td>
                                <td>
                                    <a href="?step=preview&id=<?=urlencode($item['id'])?>" class="btn btn-sm btn-success">
                                        Preview & Import
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php
                } else {
                    echo '<div class="alert alert-info mt-3">No results found.</div>';
                }
            } else {
                echo '<div class="alert alert-warning mt-3">No results found or API error.</div>';
            }
        }
        ?>
    <?php endif; ?>

    <!-- PREVIEW INTERFACE -->
    <?php if ($step === 'preview' && $import_id): ?>
        <?php
        $info = fetchZen("/info?id=" . urlencode($import_id));
        if ($info && isset($info['success']) && $info['success']) {
            $data = $info['results']['data'] ?? null;
            if ($data) {
            ?>
            <div class="card mt-3">
                <div class="card-header bg-primary text-white">
                    Import Preview
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <img src="<?=$data['poster']?>" class="img-fluid rounded shadow">
                        </div>
                        <div class="col-md-9">
                            <h3><?=$data['title']?></h3>
                            <p><strong>Original Name:</strong> <?=$data['jname']?></p>
                            <p><strong>Type:</strong> <?=$data['showType']?></p>
                            <p><strong>Status:</strong> <?=$data['animeInfo']['Status']?></p>
                            <p><strong>Aired:</strong> <?=$data['animeInfo']['Aired']?></p>
                            <div class="mb-3">
                                <strong>Synopsis:</strong><br>
                                <?=nl2br(htmlspecialchars($data['animeInfo']['Overview']))?>
                            </div>

                            <form method="POST" action="?step=process_import">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="zen_id" value="<?=htmlspecialchars($import_id)?>">
                                <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('This will import metadata and all episodes. It may take some time. Continue?')">
                                    <i class="fas fa-check"></i> Import Into Database
                                </button>
                                <a href="?step=search" class="btn btn-secondary btn-lg">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            } else {
                echo '<div class="alert alert-danger">Invalid data structure.</div>';
                echo '<a href="?step=search" class="btn btn-secondary">Back</a>';
            }
        } else {
            echo '<div class="alert alert-danger">Failed to load details.</div>';
            echo '<a href="?step=search" class="btn btn-secondary">Back</a>';
        }
        ?>
    <?php endif; ?>

</div>

<?php require_once 'layout/footer.php'; ?>
