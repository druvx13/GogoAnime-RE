<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';
require_once 'import_helpers.php';

// Increase execution time for large imports
set_time_limit(0);

// --- HELPER FUNCTIONS ---

function getActiveHianimeUrl() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT url FROM api_configs WHERE is_active = 1 AND type = 'hianime' LIMIT 1");
        $stmt->execute();
        if ($row = $stmt->fetch()) {
            return $row['url'];
        }
    } catch (PDOException $e) {
        // Fallback
    }
    return "https://hianime-api-js0d.onrender.com/api/v1";
}

function fetchHianime($endpoint) {
    // Basic rate limiting - Increased to avoid 429
    usleep(500000); // 500ms initial delay

    $base_url = getActiveHianimeUrl();
    $base_url = rtrim($base_url, '/');
    if (strpos($base_url, '/api/v1') === false) {
        $base_url .= '/api/v1';
    }

    $url = $base_url . '/' . ltrim($endpoint, '/');
    $retries = 3;
    $attempt = 0;

    while ($attempt < $retries) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'HianimeImporter/1.1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Success Case
        if ($http_code == 200 && !$error) {
            $decoded = json_decode($data, true);
            if ($decoded) {
                return $decoded;
            }
        }

        // Handle 429 (Too Many Requests) specifically
        if ($http_code == 429) {
            $sleepTime = ($attempt + 1) * 2; // Backoff: 2s, 4s, 6s
            error_log("Hianime API 429 Limit Hit. Retrying in {$sleepTime}s... URL: $url");
            sleep($sleepTime);
        } else {
            // Other errors, short wait
            usleep(500000);
        }

        $attempt++;
    }

    error_log("Hianime API Failed after $retries attempts. Last Code: $http_code - URL: $url");
    return ['error' => true, 'message' => "HTTP $http_code: $error"];
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

// Ensure Hianime Provider exists
$hianime_provider_id = 0;
try {
    $stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = 'Hianime-API'");
    $stmt->execute();
    if($row = $stmt->fetch()) {
        $hianime_provider_id = $row['id'];
    } else {
        $conn->exec("INSERT INTO video_providers (name, label, is_active) VALUES ('Hianime-API', 'Hianime', 1)");
        $hianime_provider_id = $conn->lastInsertId();
    }
} catch(PDOException $e) {
    $msg = "Database Error: " . $e->getMessage();
    $msg_type = "danger";
}

$db_types = $conn->query("SELECT * FROM types")->fetchAll(PDO::FETCH_ASSOC);
$db_genres = $conn->query("SELECT * FROM genres")->fetchAll(PDO::FETCH_ASSOC);

// --- IMPORT LOGIC ---

if ($step === 'process_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $hi_id = $_POST['hi_id'];
    $import_type = $_POST['import_type'] ?? 'sub';
    $animeData = isset($_POST['anime_data']) ? json_decode(htmlspecialchars_decode($_POST['anime_data']), true) : null;
    $videos = $_POST['videos'] ?? [];

    // Fetch info if not passed
    if (!$animeData) {
        $infoData = fetchHianime("/anime/" . urlencode($hi_id));
        if ($infoData && isset($infoData['success']) && $infoData['success']) {
            $animeData = $infoData['data']['anime'] ?? $infoData['data'];
        }
    }

    if (empty($animeData)) {
        $msg = "Failed to fetch anime details or invalid data.";
        $msg_type = "danger";
        $step = 'search';
    } else {
        // Map fields based on confirmed API structure
        // The object has: title, synopsis, type, status, aired.from, etc.
        $info = $animeData['info'] ?? $animeData;

        $title = $info['title'] ?? $info['name'] ?? 'Unknown';
        $synopsis = $info['synopsis'] ?? $info['description'] ?? '';
        $poster_url = $info['poster'] ?? '';
        $showType = $info['type'] ?? 'TV';
        $statusRaw = $info['status'] ?? 'Completed';

        $releaseDate = '';
        if (isset($info['aired']) && is_array($info['aired'])) {
            $releaseDate = $info['aired']['from'] ?? '';
        } elseif (isset($info['aired'])) {
            $releaseDate = $info['aired'];
        }

        // 2. Duplicate Check & Upsert Logic
        $check = $conn->prepare("SELECT id FROM anime WHERE title = ?");
        $check->execute([$title]);
        $existing = $check->fetch();

        $final_anime_id = 0;
        $action_msg = "";

        try {
            $conn->beginTransaction();

            if ($existing) {
                $final_anime_id = $existing['id'];
                $action_msg = "Updated existing anime";
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
                $final_status = 'Completed';
                if (stripos($statusRaw, 'Ongoing') !== false || stripos($statusRaw, 'Currently') !== false) $final_status = 'Ongoing';
                if (stripos($statusRaw, 'Upcoming') !== false) $final_status = 'Upcoming';

                $stmt = $conn->prepare("INSERT INTO anime (title, slug, synopsis, type, type_id, status, release_date, image_url, language) VALUES (:title, :slug, :synopsis, :type, :type_id, :status, :release_date, :image_url, :language)");
                $stmt->execute([
                    'title' => $title,
                    'language' => ucfirst($import_type),
                    'slug' => $slug,
                    'synopsis' => $synopsis,
                    'type' => $type_name,
                    'type_id' => $type_id,
                    'status' => $final_status,
                    'release_date' => $releaseDate,
                    'image_url' => $image_url
                ]);
                $final_anime_id = $conn->lastInsertId();

                // 6. Map Genres
                $hi_genres = $info['genres'] ?? [];
                foreach($hi_genres as $gName) {
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
                    $conn->prepare("INSERT INTO anime_genre (anime_id, genre_id) VALUES (?, ?)")->execute([$final_anime_id, $gid]);
                }
                $action_msg = "Created new anime";
            }

            // 7. Insert/Update Episodes
            $imported_eps = 0;

            if (!empty($videos)) {
                foreach ($videos as $ep_num => $video_sources) {
                    $local_ep_id = 0;

                    // Check if episode exists
                    $ep_check = $conn->prepare("SELECT id, video_url FROM episodes WHERE anime_id = ? AND episode_number = ?");
                    $ep_check->execute([$final_anime_id, $ep_num]);
                    $ep_row = $ep_check->fetch();

                    if ($ep_row) {
                        $local_ep_id = $ep_row['id'];
                    } else {
                        $ep_title = "Episode $ep_num";
                        $estmt = $conn->prepare("INSERT INTO episodes (anime_id, episode_number, title, video_url) VALUES (?, ?, ?, ?)");
                        $estmt->execute([$final_anime_id, $ep_num, $ep_title, ""]);
                        $local_ep_id = $conn->lastInsertId();
                    }

                    $first_video_url = '';

                    foreach($video_sources as $json_src) {
                        $src = json_decode(htmlspecialchars_decode($json_src), true);
                        if ($src && isset($src['server']) && isset($src['link'])) {
                            $pLabel = 'Hianime - ' . ucfirst($src['server']);
                            $provider_id = getOrCreateProvider($conn, $pLabel, $pLabel);

                            // Check duplication of source
                            $vcheck = $conn->prepare("SELECT id FROM episode_videos WHERE episode_id = ? AND provider_id = ?");
                            $vcheck->execute([$local_ep_id, $provider_id]);

                            if ($vcheck->fetch()) {
                                // Update existing source
                                $vstmt = $conn->prepare("UPDATE episode_videos SET video_url = ? WHERE episode_id = ? AND provider_id = ?");
                                $vstmt->execute([$src['link'], $local_ep_id, $provider_id]);
                            } else {
                                // Insert new source
                                $vstmt = $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)");
                                $vstmt->execute([$local_ep_id, $provider_id, $src['link']]);
                            }

                            if (!$first_video_url) $first_video_url = $src['link'];
                        }
                    }

                    // Update main episode link if empty or we have a new one (prioritize if needed, but for now just ensure it has one)
                    if ($first_video_url) {
                        // If existing episode had no URL, or we want to overwrite?
                        // Let's overwrite if existing is empty
                        if (!$ep_row || empty($ep_row['video_url'])) {
                            $conn->prepare("UPDATE episodes SET video_url = ? WHERE id = ?")->execute([$first_video_url, $local_ep_id]);
                        }
                    }

                    $imported_eps++;
                }
            }

            $conn->commit();
            $msg = "$action_msg '<strong>$title</strong>'. Processed $imported_eps episodes.";
            $msg_type = "success";
            $step = 'search';

        } catch (Exception $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            if (isset($local_image_name) && file_exists('../assets/uploads/covers/' . $local_image_name)) {
                unlink('../assets/uploads/covers/' . $local_image_name);
            }
            $msg = "Import failed: " . $e->getMessage();
            $msg_type = "danger";
            $step = 'search';
        }
    }
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-file-import"></i> Hianime-API Importer</h2>
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
            <form method="GET" action="hianime_import.php">
                <input type="hidden" name="step" value="search">
                <div class="input-group input-group-lg">
                    <input type="text" name="keyword" class="form-control" placeholder="Search anime... (e.g. One Piece)" value="<?=htmlspecialchars($keyword)?>" required>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php
        if ($keyword) {
            $data = fetchHianime("/search?keyword=" . urlencode($keyword));

            // API returns: data: { response: [...] } or data: { animes: [...] } ?
            // Based on curl: data.data.response is the array of animes
            if ($data && isset($data['success']) && $data['success']) {
                $results = $data['data']['animes'] ?? $data['data']['response'] ?? [];

                if (!empty($results)) {
                    ?>
                    <h4 class="mb-3">Search Results for "<?=htmlspecialchars($keyword)?>"</h4>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        <?php foreach($results as $item): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div style="height: 300px; overflow: hidden; background: #000;">
                                    <img src="<?=$item['poster']?>" class="card-img-top" style="height: 100%; object-fit: cover; opacity: 0.9;" alt="<?=$item['title']?>">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-truncate" title="<?=$item['title']?>"><?=$item['title']?></h5>
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
                $errMsg = isset($data['message']) ? $data['message'] : "Unknown Error";
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
                        <p class="card-text">If search fails, you can copy the ID from the URL and paste it here.</p>
                        <form method="GET" action="hianime_import.php" class="d-flex gap-2">
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
        $infoData = fetchHianime("/anime/" . urlencode($import_id));

        if ($infoData && isset($infoData['success']) && $infoData['success']) {
            $anime = $infoData['data']['anime']['info'] ?? $infoData['data']['anime'] ?? $infoData['data'] ?? null;
            // Curl shows: { success: true, data: { title: "...", ... } } -> So it's data directly

            if ($anime) {
                // Determine release date string
                $aired = 'N/A';
                if (isset($anime['aired']['from'])) {
                     $aired = $anime['aired']['from'];
                     if (isset($anime['aired']['to'])) $aired .= ' to ' . $anime['aired']['to'];
                } elseif (isset($anime['aired'])) {
                     $aired = $anime['aired'];
                }

            ?>
            <div class="card shadow-lg mt-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Import Preview</h4>
                    <span class="badge bg-light text-dark"><?=$anime['type'] ?? 'TV'?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?=$anime['poster']?>" class="img-fluid rounded shadow mb-3" style="max-height: 400px;">
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-3"><?=$anime['name'] ?? $anime['title']?></h2>
                            <table class="table table-sm table-borderless">
                                <tr><th width="150">Status:</th><td><?=$anime['status'] ?? 'Unknown'?></td></tr>
                                <tr><th>Aired:</th><td><?=$aired?></td></tr>
                                <tr><th>Genres:</th><td><?=implode(', ', $anime['genres'] ?? [])?></td></tr>
                                <tr><th>ID:</th><td><code><?=$anime['id']?></code></td></tr>
                            </table>

                            <div class="mb-4 p-3 bg-light rounded border">
                                <h5>Synopsis</h5>
                                <p class="mb-0 text-muted"><?=nl2br(htmlspecialchars($anime['description'] ?? $anime['synopsis'] ?? ''))?></p>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Ready to scan for streaming links. This process may take time.
                            </div>

                            <form method="POST" action="?step=scan">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="hi_id" value="<?=htmlspecialchars($import_id)?>">

                                <h5 class="mt-4"><i class="fas fa-filter"></i> Scan Options</h5>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Import Type</label>
                                        <select name="import_type" class="form-select">
                                            <option value="sub">Subtitled</option>
                                            <option value="dub">Dubbed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Episode Range (Start)</label>
                                        <input type="number" name="ep_start" class="form-control" value="1" min="1">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Episode Range (End)</label>
                                        <input type="number" name="ep_end" class="form-control" placeholder="Leave empty for all">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-block">
                                    <button type="submit" class="btn btn-primary btn-lg px-5">
                                        <i class="fas fa-satellite-dish"></i> Scan for Links
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
                echo '<div class="alert alert-danger">Invalid data received from API. Structure mismatch.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Failed to fetch details. API Error.</div>';
        }
        ?>
    <?php endif; ?>

    <!-- SCAN INTERFACE -->
    <?php if ($step === 'scan' && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php
        $hi_id = $_POST['hi_id'];
        $import_type = $_POST['import_type'] ?? 'sub';
        $ep_start = max(1, intval($_POST['ep_start'] ?? 1));
        $ep_end = intval($_POST['ep_end'] ?? 0);

        // Fetch context
        $infoData = fetchHianime("/anime/" . urlencode($hi_id));
        $anime = $infoData['data']['anime']['info'] ?? $infoData['data']['anime'] ?? $infoData['data'] ?? [];
        $title = $anime['name'] ?? $anime['title'] ?? 'Unknown Anime';

        // Fetch Episodes
        // Verified endpoint: /episodes/{id}
        $epsData = fetchHianime("/episodes/" . urlencode($hi_id));
        // Verified response: data: [{ ... }, ...] (Direct array of episodes)
        $episodes = $epsData['data']['episodes'] ?? $epsData['data'] ?? [];

        // Filter
        $filtered_eps = [];
        foreach($episodes as $ep) {
            $num = intval($ep['number'] ?? $ep['episodeNumber']);
            if ($num >= $ep_start && ($ep_end === 0 || $num <= $ep_end)) {
                $filtered_eps[] = $ep;
            }
        }
        ?>

        <div class="card shadow mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Scan Results: <?=htmlspecialchars($title)?> <span class="badge bg-info"><?=strtoupper($import_type)?></span></h4>
                <span class="badge bg-secondary"><?=count($filtered_eps)?> Episodes</span>
            </div>
            <div class="card-body">
                <form method="POST" action="?step=process_import">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="hi_id" value="<?=htmlspecialchars($hi_id)?>">
                    <input type="hidden" name="import_type" value="<?=htmlspecialchars($import_type)?>">
                    <input type="hidden" name="anime_data" value="<?=htmlspecialchars(json_encode($anime))?>">

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Ep #</th>
                                    <th>Available Streams</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($filtered_eps as $ep):
                                    $ep_num = $ep['number'] ?? $ep['episodeNumber'];
                                    $ep_id = $ep['episodeId'] ?? $ep['id'];

                                    // Fetch Servers
                                    // Verified endpoint: /servers/{id} or /servers/{episodeId}
                                    // My test curl to /servers?id=... FAILED
                                    // My test curl to /servers/id SUCCEEDED! -> /servers/naruto-677::ep=12352

                                    $srvData = fetchHianime("/servers/" . urlencode($ep_id));

                                    // Valid response: data: { sub: [...], dub: [...] }
                                    $server_list = [];
                                    if (isset($srvData['data'][$import_type])) {
                                        $server_list = $srvData['data'][$import_type];
                                    }
                                ?>
                                <tr>
                                    <td class="align-middle text-center fw-bold fs-5"><?=$ep_num?></td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php
                                            if (!empty($server_list)) {
                                                foreach($server_list as $server) {
                                                    $sName = $server['name'] ?? $server['serverName']; // API uses 'name'

                                                    // Fetch Link
                                                    // Verified endpoint: /stream?id={episodeId}&server={server}&type={sub|dub}
                                                    $srcUrl = "/stream?id=" . urlencode($ep_id) . "&server=" . urlencode($sName) . "&type=" . urlencode($import_type);
                                                    $srcData = fetchHianime($srcUrl);

                                                    $final_link = '';
                                                    // Verified response: data: { link: { file: "..." } }
                                                    if (isset($srcData['data']['link']['file'])) {
                                                        $final_link = $srcData['data']['link']['file'];
                                                    } elseif (isset($srcData['data']['sources'][0]['url'])) {
                                                        $final_link = $srcData['data']['sources'][0]['url'];
                                                    }

                                                    if ($final_link) {
                                                        $status = checkUrlStatus($final_link);
                                                        $color = getStatusColor($status);
                                                        $uniq = "vid_" . $ep_num . "_" . md5($sName);
                                                        ?>
                                                        <div class="border p-2 rounded bg-light" style="max-width: 300px;">
                                                            <div class="form-check">
                                                                <input class="form-check-input server-chk" type="checkbox" name="videos[<?=$ep_num?>][]" value="<?=htmlspecialchars(json_encode(['server'=>$sName, 'link'=>$final_link]))?>" id="<?=$uniq?>" checked>
                                                                <label class="form-check-label fw-bold" for="<?=$uniq?>">
                                                                    <?=htmlspecialchars(ucfirst($sName))?>
                                                                </label>
                                                            </div>
                                                            <div class="mt-1 small text-truncate text-muted" title="<?=htmlspecialchars($final_link)?>">
                                                                <i class="fas fa-link"></i> <?=htmlspecialchars($final_link)?>
                                                            </div>
                                                            <div class="mt-1">
                                                                <span class="badge bg-<?=$color?>">HTTP <?=$status?></span>
                                                            </div>
                                                        </div>
                                                        <?php
                                                    }
                                                }
                                            } else {
                                                echo '<span class="text-muted">No servers found for '.htmlspecialchars($import_type).'.</span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="sticky-bottom bg-white border-top p-3 d-flex justify-content-between align-items-center shadow">
                        <div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.server-chk').forEach(el => el.checked = true);">Select All</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelectorAll('.server-chk').forEach(el => el.checked = false);">Deselect All</button>
                        </div>
                        <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-file-import"></i> Finalize Import</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once 'layout/footer.php'; ?>
