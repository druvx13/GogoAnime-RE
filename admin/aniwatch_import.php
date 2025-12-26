<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Increase execution time for large imports
set_time_limit(300);

// --- HELPER FUNCTIONS ---

function getActiveAniwatchUrl() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT url FROM api_configs WHERE is_active = 1 AND type = 'aniwatch' LIMIT 1");
        $stmt->execute();
        if ($row = $stmt->fetch()) {
            return $row['url'];
        }
    } catch (PDOException $e) {
        // Fallback
    }
    return "http://localhost:4000/api/v2/hianime"; // Default fallback
}

function fetchAniwatch($endpoint) {
    // Basic rate limiting
    usleep(200000); // 200ms

    $base_url = getActiveAniwatchUrl();

    // Normalize base URL
    $base_url = rtrim($base_url, '/');

    // Auto-append path if missing, but only if it doesn't already end with it
    if (strpos($base_url, '/api/v2/hianime') === false) {
        $base_url .= '/api/v2/hianime';
    }

    // Ensure clean URL construction
    $url = $base_url . '/' . ltrim($endpoint, '/');

    // Debug log to help users troubleshoot
    // error_log("Aniwatch Fetch: $url");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AniwatchImporter/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code != 200 || $error) {
        error_log("Aniwatch API Error [$http_code]: $error - URL: $url");
        return ['error' => true, 'message' => "HTTP $http_code: $error", 'url' => $url];
    }
    $decoded = json_decode($data, true);
    if (!$decoded) {
        return ['error' => true, 'message' => "Invalid JSON Response", 'url' => $url];
    }
    return $decoded;
}

function downloadImage($url, $save_dir) {
    if (empty($url)) return '';
    if (!file_exists($save_dir)) mkdir($save_dir, 0777, true);

    $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    if (!$ext) $ext = 'jpg';

    $filename = time() . '_' . uniqid() . '.' . $ext;
    $save_path = $save_dir . $filename;

    $ch = curl_init($url);
    $fp = fopen($save_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    fclose($fp);

    if ($error || filesize($save_path) == 0) {
        if (file_exists($save_path)) unlink($save_path);
        return '';
    }

    return $filename;
}

// --- INITIALIZATION ---

$step = $_GET['step'] ?? 'search';
$keyword = $_GET['keyword'] ?? '';
$import_id = $_GET['id'] ?? '';
$msg = '';
$msg_type = '';

// Ensure Aniwatch Provider exists
$aniwatch_provider_id = 0;
try {
    $stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = 'Aniwatch-API'");
    $stmt->execute();
    if($row = $stmt->fetch()) {
        $aniwatch_provider_id = $row['id'];
    } else {
        $conn->exec("INSERT INTO video_providers (name, label, is_active) VALUES ('Aniwatch-API', 'Aniwatch', 1)");
        $aniwatch_provider_id = $conn->lastInsertId();
    }
} catch(PDOException $e) {
    $msg = "Database Error: " . $e->getMessage();
    $msg_type = "danger";
}

function getOrCreateProvider($serverName, $label) {
    global $conn;
    $name = 'Aniwatch - ' . ucfirst($serverName);
    try {
        $stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = ?");
        $stmt->execute([$name]);
        if($row = $stmt->fetch()) {
            return $row['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO video_providers (name, label, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$name, $label]);
            return $conn->lastInsertId();
        }
    } catch(PDOException $e) {
        return 0;
    }
}

$db_types = $conn->query("SELECT * FROM types")->fetchAll(PDO::FETCH_ASSOC);
$db_genres = $conn->query("SELECT * FROM genres")->fetchAll(PDO::FETCH_ASSOC);

// --- IMPORT LOGIC ---

if ($step === 'process_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ani_id = $_POST['ani_id'];

    // 1. Fetch Details: /anime/{id}
    $infoData = fetchAniwatch("/anime/" . urlencode($ani_id));

    // Check success or status 200
    $isSuccess = ($infoData && ((isset($infoData['success']) && $infoData['success']) || (isset($infoData['status']) && $infoData['status'] === 200)));

    if (!$isSuccess || !isset($infoData['data']['anime']['info'])) {
        $err = "Unknown error";
        if (isset($infoData['error'])) $err = $infoData['message'];
        else if (is_array($infoData)) $err = json_encode($infoData);

        $msg = "Failed to fetch anime details: " . $err;
        $msg_type = "danger";
        $step = 'search';
    } else {
        $animeInfo = $infoData['data']['anime']['info'];
        $moreInfo = $infoData['data']['anime']['moreInfo'] ?? [];

        $title = $animeInfo['name'];
        $synopsis = $animeInfo['description'];
        $poster_url = $animeInfo['poster'];
        $showType = $animeInfo['stats']['type'] ?? 'TV';
        $statusRaw = $moreInfo['status'] ?? 'Completed';
        $releaseDate = $moreInfo['aired'] ?? '';

        // 2. Duplicate Check
        $check = $conn->prepare("SELECT id FROM anime WHERE title = ?");
        $check->execute([$title]);
        if ($existing = $check->fetch()) {
            $msg = "Anime '$title' already exists. Import skipped.";
            $msg_type = "warning";
            $step = 'search';
        } else {
            // 3. Download Cover
            $local_image_name = downloadImage($poster_url, '../assets/uploads/covers/');
            $image_url = $local_image_name ? '/assets/uploads/covers/' . $local_image_name : '';

            // 4. Map Type
            $type_id = 1;
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
            // Map status
            $final_status = 'Completed';
            if (stripos($statusRaw, 'Currently') !== false) $final_status = 'Ongoing';
            if (stripos($statusRaw, 'Upcoming') !== false) $final_status = 'Upcoming';

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

                // 6. Map Genres
                $ani_genres = $moreInfo['genres'] ?? [];
                // API returns array of strings: ["Action", "Adventure"]
                foreach($ani_genres as $gName) {
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

                $conn->commit();

                // 7. Episodes & Streaming
                // Endpoint: /anime/{id}/episodes
                $epsData = fetchAniwatch("/anime/" . urlencode($ani_id) . "/episodes");
                $episodes = $epsData['data']['episodes'] ?? [];

                $imported_eps = 0;

                foreach($episodes as $ep) {
                    $ep_num = $ep['number'];
                    $ep_id = $ep['episodeId']; // e.g., "one-piece-100?ep=2142"

                    // Insert Episode
                    $estmt = $conn->prepare("INSERT INTO episodes (anime_id, episode_number, title, video_url) VALUES (?, ?, ?, ?)");
                    $estmt->execute([$new_anime_id, $ep_num, $ep['title'] ?? "Episode $ep_num", ""]);
                    $local_ep_id = $conn->lastInsertId();

                    // Fetch Servers: /episode/servers?animeEpisodeId={id}
                    // The docs say parameter is animeEpisodeId
                    $srvData = fetchAniwatch("/episode/servers?animeEpisodeId=" . urlencode($ep_id));

                    // The API returns { sub: [...], dub: [...], raw: [...] }
                    $allServers = $srvData['data']['sub'] ?? [];
                    $selectedServers = $_POST['servers'] ?? [];

                    // If user didn't select any (or came from old form), default to priority list
                    if (empty($selectedServers)) {
                        $selectedServers = ['vidstreaming', 'megacloud', 'hd-1', 'hd-2'];
                    }

                    foreach ($allServers as $server) {
                        $sName = $server['serverName'];

                        // Only process if in user's selection (case-insensitive check)
                        $isSelected = false;
                        foreach($selectedServers as $sel) {
                            if (strcasecmp($sel, $sName) === 0) {
                                $isSelected = true;
                                break;
                            }
                        }
                        if (!$isSelected) continue;

                        // Fetch Sources
                        $srcUrl = "/episode/sources?animeEpisodeId=" . urlencode($ep_id) . "&server=" . urlencode($sName) . "&category=sub";
                        $srcData = fetchAniwatch($srcUrl);

                        $isSrcSuccess = ($srcData && ((isset($srcData['success']) && $srcData['success']) || (isset($srcData['status']) && $srcData['status'] === 200)));

                        $videoUrl = '';
                        if ($isSrcSuccess && isset($srcData['data']['sources']) && is_array($srcData['data']['sources'])) {
                            foreach($srcData['data']['sources'] as $src) {
                                if (isset($src['url'])) {
                                    $videoUrl = $src['url'];
                                    break;
                                }
                            }
                        }

                        if ($videoUrl) {
                            $pid = getOrCreateProvider($sName, ucfirst($sName));
                            if ($pid) {
                                $vstmt = $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)");
                                $vstmt->execute([$local_ep_id, $pid, $videoUrl]);
                            }

                            // Also update the main episode video_url if it's the first valid one we found
                            // (Legacy compatibility for themes that only use episodes.video_url)
                            $conn->prepare("UPDATE episodes SET video_url = ? WHERE id = ? AND (video_url IS NULL OR video_url = '')")->execute([$videoUrl, $local_ep_id]);
                        }
                    }

                    $imported_eps++;
                    usleep(100000);
                }

                $msg = "Import Successful! Added '<strong>$title</strong>' with $imported_eps episodes.";
                $msg_type = "success";
                $step = 'search';

            } catch (Exception $e) {
                if ($conn->inTransaction()) $conn->rollBack();
                if ($local_image_name && file_exists('../assets/uploads/covers/' . $local_image_name)) {
                    unlink('../assets/uploads/covers/' . $local_image_name);
                }
                $msg = "Import failed: " . $e->getMessage();
                $msg_type = "danger";
                $step = 'search';
            }
        }
    }
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-file-import"></i> Aniwatch-API Importer</h2>
        <a href="api_config.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-cog"></i> API Config</a>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-<?=$msg_type?> alert-dismissible fade show">
            <?=$msg?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- TABS -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= ($step === 'search' && !$import_id) ? 'active' : '' ?>" href="?step=search">
                <i class="fas fa-search"></i> Search
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" onclick="document.getElementById('manual-import-row').scrollIntoView({behavior: 'smooth'}); return false;">
                <i class="fas fa-keyboard"></i> Manual Import
            </a>
        </li>
    </ul>

    <!-- SEARCH INTERFACE -->
    <?php if ($step === 'search'): ?>
        <div class="card p-4 shadow-sm mb-5">
            <form method="GET" action="aniwatch_import.php">
                <input type="hidden" name="step" value="search">
                <div class="input-group input-group-lg">
                    <input type="text" name="keyword" class="form-control" placeholder="Search anime... (e.g. Naruto)" value="<?=htmlspecialchars($keyword)?>" required>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php
        if ($keyword) {
            $data = fetchAniwatch("/search?q=" . urlencode($keyword));

            // Check for success OR status 200 (some API versions use status)
            $isSuccess = ($data && ((isset($data['success']) && $data['success']) || (isset($data['status']) && $data['status'] === 200)));

            if ($isSuccess) {
                $results = $data['data']['animes'] ?? [];

                if (!empty($results)) {
                    ?>
                    <h4 class="mb-3">Search Results for "<?=htmlspecialchars($keyword)?>"</h4>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        <?php foreach($results as $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div style="height: 300px; overflow: hidden; background: #000;">
                                    <img src="<?=$item['poster']?>" class="card-img-top" style="height: 100%; object-fit: cover; opacity: 0.9;" alt="<?=$item['name']?>">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-truncate" title="<?=$item['name']?>"><?=$item['name']?></h5>
                                    <div class="mb-3">
                                        <span class="badge bg-secondary"><?=$item['type'] ?? 'TV'?></span>
                                        <span class="badge bg-info text-dark">Ep: <?=$item['episodes']['sub'] ?? '?'?></span>
                                    </div>
                                    <a href="?step=preview&id=<?=urlencode($item['id'])?>" class="btn btn-outline-success mt-auto stretched-link">
                                        Select
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php
                } else {
                    echo '<div class="alert alert-info text-center p-5"><h4>No results found.</h4></div>';
                }
            } else {
                $errMsg = "Unknown Error";
                if (isset($data['error']) && $data['error']) {
                    $errMsg = $data['message'];
                } else if (is_array($data)) {
                    $errMsg = json_encode($data);
                }
                echo '<div class="alert alert-warning">API connection failed. Error: ' . htmlspecialchars($errMsg) . '</div>';
            }
        }
        ?>

        <hr class="my-5">

        <!-- Manual Import Section -->
        <div id="manual-import-row" class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-bolt"></i> Manual Import by ID
                    </div>
                    <div class="card-body">
                        <p class="card-text">If search fails, you can copy the ID from the Aniwatch URL and paste it here.</p>
                        <form method="GET" action="aniwatch_import.php" class="d-flex gap-2">
                            <input type="hidden" name="step" value="preview">
                            <input type="text" name="id" class="form-control" placeholder="e.g. one-piece-100" required>
                            <button type="submit" class="btn btn-info text-white">Preview</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <!-- PREVIEW INTERFACE -->
    <?php if ($step === 'preview' && $import_id): ?>
        <?php
        $infoData = fetchAniwatch("/anime/" . urlencode($import_id));
        $isSuccess = ($infoData && ((isset($infoData['success']) && $infoData['success']) || (isset($infoData['status']) && $infoData['status'] === 200)));

        if ($isSuccess) {
            $anime = $infoData['data']['anime']['info'] ?? null;
            $more = $infoData['data']['anime']['moreInfo'] ?? null;
            if ($anime) {
            ?>
            <div class="card shadow-lg mt-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Import Preview</h4>
                    <span class="badge bg-light text-dark"><?=$anime['stats']['type'] ?? 'TV'?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?=$anime['poster']?>" class="img-fluid rounded shadow mb-3" style="max-height: 400px;">
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-3"><?=$anime['name']?></h2>
                            <table class="table table-sm table-borderless">
                                <tr><th width="150">Status:</th><td><?=$more['status'] ?? 'Unknown'?></td></tr>
                                <tr><th>Aired:</th><td><?=$more['aired'] ?? 'N/A'?></td></tr>
                                <tr><th>Genres:</th><td><?=implode(', ', $more['genres'] ?? [])?></td></tr>
                                <tr><th>ID:</th><td><code><?=$anime['id']?></code></td></tr>
                            </table>

                            <div class="mb-4 p-3 bg-light rounded border">
                                <h5>Synopsis</h5>
                                <p class="mb-0 text-muted"><?=nl2br(htmlspecialchars($anime['description']))?></p>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Ready to import. This may take a while to fetch all episodes.
                            </div>

                            <?php
                            // Fetch first episode servers to populate checkboxes
                            $servers = [];
                            $epsData = fetchAniwatch("/anime/" . urlencode($import_id) . "/episodes");
                            $firstEp = $epsData['data']['episodes'][0] ?? null;

                            if ($firstEp) {
                                $srvData = fetchAniwatch("/episode/servers?animeEpisodeId=" . urlencode($firstEp['episodeId']));
                                $servers = $srvData['data']['sub'] ?? [];
                            }
                            ?>

                            <form method="POST" action="?step=process_import">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="ani_id" value="<?=htmlspecialchars($import_id)?>">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Select Video Sources (Servers) to Import:</label>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php if (!empty($servers)): ?>
                                            <?php foreach ($servers as $srv): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="servers[]" value="<?=htmlspecialchars($srv['serverName'])?>" id="srv_<?=htmlspecialchars($srv['serverName'])?>" checked>
                                                    <label class="form-check-label" for="srv_<?=htmlspecialchars($srv['serverName'])?>">
                                                        <?=htmlspecialchars(ucfirst($srv['serverName']))?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted small">No specific servers found for preview (will try auto-detection).</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-block">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-file-import"></i> Confirm Import
                                    </button>
                                    <a href="?step=search" class="btn btn-secondary btn-lg">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            } else {
                echo '<div class="alert alert-danger">Invalid data received.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Failed to fetch details.</div>';
        }
        ?>
    <?php endif; ?>

</div>
<?php require_once 'layout/footer.php'; ?>
