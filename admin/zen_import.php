<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Increase execution time for large imports
set_time_limit(300);

// --- HELPER FUNCTIONS ---

function getActiveApiUrl() {
    global $conn;
    // Auto-create table if missing (Self-healing)
    try {
        $stmt = $conn->query("SELECT url FROM api_configs WHERE is_active = 1 AND type = 'zen' LIMIT 1");
        if ($row = $stmt->fetch()) {
            return $row['url'];
        }
    } catch (PDOException $e) {
        // Table doesn't exist? Use default
    }
    return "https://anime-api-snowy.vercel.app/api";
}

function fetchZen($endpoint) {
    // Basic rate limiting
    usleep(200000); // 200ms

    $base_url = getActiveApiUrl();
    // Ensure clean URL construction
    $url = rtrim($base_url, '/') . '/' . ltrim($endpoint, '/');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZenImporter/1.1');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Add timeout
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($http_code != 200 || $error) {
        error_log("ZenAPI Error [$http_code]: $error - URL: $url");
        return null;
    }
    return json_decode($data, true);
}

function downloadImage($url, $save_dir) {
    if (empty($url)) return '';
    if (!file_exists($save_dir)) mkdir($save_dir, 0777, true);

    // Try to get extension from URL, default to jpg
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

function mapStatus($zenStatus) {
    $s = strtolower((string)$zenStatus);
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
try {
    $stmt = $conn->prepare("SELECT id FROM video_providers WHERE name = 'Zen-API'");
    $stmt->execute();
    if($row = $stmt->fetch()) {
        $zen_provider_id = $row['id'];
    } else {
        $conn->exec("INSERT INTO video_providers (name, label, is_active) VALUES ('Zen-API', 'Zen-Stream', 1)");
        $zen_provider_id = $conn->lastInsertId();
    }
} catch(PDOException $e) {
    // Handle case where table might not exist yet (though unlikely if provider_list exists)
    $msg = "Database Error: " . $e->getMessage();
    $msg_type = "danger";
}

// Fetch DB Types & Genres
$db_types = $conn->query("SELECT * FROM types")->fetchAll(PDO::FETCH_ASSOC);
$db_genres = $conn->query("SELECT * FROM genres")->fetchAll(PDO::FETCH_ASSOC);

// --- IMPORT LOGIC ---

if ($step === 'process_import' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $zen_id = $_POST['zen_id'];

    // 1. Fetch Details
    $info = fetchZen("/info?id=" . urlencode($zen_id));

    if (!$info || !isset($info['success']) || !$info['success']) {
        $msg = "Failed to fetch anime details from Zen-API. Please check the ID or API status.";
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
            // Handle Description/Overview
            $synopsis = '';
            if (isset($data['animeInfo']['Overview'])) {
                $synopsis = $data['animeInfo']['Overview'];
            } elseif (isset($data['description'])) {
                $synopsis = $data['description'];
            }

            $poster_url = $data['poster'];
            $showType = $data['showType'] ?? 'TV';
            $statusRaw = $data['animeInfo']['Status'] ?? '';
            $releaseDate = $data['animeInfo']['Aired'] ?? '';

            // 2. Duplicate Check
            $check = $conn->prepare("SELECT id FROM anime WHERE title = ?");
            $check->execute([$title]);
            if ($existing = $check->fetch()) {
                $msg = "Anime '$title' already exists (ID: {$existing['id']}). Import skipped.";
                $msg_type = "warning";
                $step = 'search';
            } else {
                // 3. Download Cover
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
                            // Handle both ["Action", "Comedy"] and [{"name": "Action"}, ...]
                            $gName = '';
                            if (is_array($g)) {
                                $gName = $g['name'] ?? '';
                            } else {
                                $gName = (string)$g;
                            }

                            if (!$gName) continue;

                            $gid = null;
                            // Check existing
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
                                // Add to local cache
                                $db_genres[] = ['id' => $gid, 'name' => $gName];
                            }

                            $conn->prepare("INSERT INTO anime_genre (anime_id, genre_id) VALUES (?, ?)")->execute([$new_anime_id, $gid]);
                        }
                    }

                    $conn->commit();

                    // 7. Episodes & Streaming
                    $eps_data = fetchZen("/episodes/" . urlencode($zen_id));

                    // Handle variable response structure for episodes
                    $episodes = [];
                    if (isset($eps_data['results']['episodes'])) {
                        $episodes = $eps_data['results']['episodes'];
                    } elseif (isset($eps_data['results']) && is_array($eps_data['results'])) {
                        // Check if it's the mixed array format [ "totalEpisodes": x, "episodes": [...] ] (unlikely valid JSON but possible in some PHP decodes of malformed JSON)
                        // Or just an array of results? No, episodes is usually a list.
                        // Safe fallback:
                         if (isset($eps_data['results'][0]['episodes'])) {
                             $episodes = $eps_data['results'][0]['episodes']; // if array of objects
                         } else {
                             // Maybe results IS the array of episodes?
                             // No, docs say results.episodes
                         }
                    }

                    $imported_eps = 0;

                    if (!empty($episodes)) {
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

                            $final_link = '';

                            if (is_array($server_list)) {
                                // Prioritize known good servers
                                usort($server_list, function($a, $b) {
                                    $prio = ['vidstreaming', 'gogostream', 'hd-1', 'megacloud'];
                                    $aScore = 999;
                                    $bScore = 999;
                                    foreach($prio as $idx => $name) {
                                        if (stripos($a['serverName'], $name) !== false) { $aScore = $idx; break; }
                                    }
                                    foreach($prio as $idx => $name) {
                                        if (stripos($b['serverName'], $name) !== false) { $bScore = $idx; break; }
                                    }
                                    return $aScore <=> $bScore;
                                });

                                foreach ($server_list as $server) {
                                    // Construct the ID format as expected by Zen-API: anime-slug?ep=episode-id
                                    $stream_id_param = $zen_id . "?ep=" . $ep_zen_id;
                                    $stream_url = "/stream?id=" . urlencode($stream_id_param) . "&server=" . urlencode($server['serverName']) . "&type=sub";
                                    $stream_data = fetchZen($stream_url);

                                    $res = $stream_data['results'] ?? [];

                                    // Format 1: streamingLink[0].link.file
                                    if (isset($res['streamingLink']) && is_array($res['streamingLink'])) {
                                         if (isset($res['streamingLink'][0]['link']['file'])) {
                                             $final_link = $res['streamingLink'][0]['link']['file'];
                                         }
                                    }

                                    // Format 2: sources[0].file
                                    if (!$final_link && isset($res['sources']) && is_array($res['sources'])) {
                                        if (isset($res['sources'][0]['file'])) {
                                            $final_link = $res['sources'][0]['file'];
                                        }
                                        if (!$final_link && isset($res['sources'][0]['url'])) {
                                            $final_link = $res['sources'][0]['url'];
                                        }
                                    }

                                    // Format 3: link object or string
                                    if (!$final_link && isset($res['link'])) {
                                        if (is_array($res['link']) && isset($res['link']['file'])) {
                                            $final_link = $res['link']['file'];
                                        } elseif (is_string($res['link'])) {
                                            $final_link = $res['link'];
                                        }
                                    }

                                    if ($final_link) break; // Found a link
                                }
                            }

                            if ($final_link) {
                                $vstmt = $conn->prepare("INSERT INTO episode_videos (episode_id, provider_id, video_url) VALUES (?, ?, ?)");
                                $vstmt->execute([$local_ep_id, $zen_provider_id, $final_link]);
                                $conn->prepare("UPDATE episodes SET video_url = ? WHERE id = ?")->execute([$final_link, $local_ep_id]);
                            }

                            $imported_eps++;
                            usleep(100000); // 100ms delay
                        }
                    }

                    $msg = "Import Successful! Added '<strong>$title</strong>' with $imported_eps episodes.";
                    $msg_type = "success";
                    $step = 'search';

                } catch (Exception $e) {
                    if ($conn->inTransaction()) $conn->rollBack();
                    // Cleanup image
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
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-file-import"></i> Zen-API Importer</h2>
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
            <form method="GET" action="zen_import.php">
                <input type="hidden" name="step" value="search">
                <div class="input-group input-group-lg">
                    <input type="text" name="keyword" class="form-control" placeholder="Search anime by title (e.g., One Piece)" value="<?=htmlspecialchars($keyword)?>" required>
                    <button type="submit" class="btn btn-primary px-4"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php
        if ($keyword) {
            $data = fetchZen("/search?keyword=" . urlencode($keyword));

            if ($data && isset($data['success']) && $data['success']) {
                $results = $data['results'] ?? [];

                // Fix for nested data structure in search results
                if (isset($results['data']) && is_array($results['data'])) {
                    $results = $results['data'];
                }

                if (!empty($results)) {
                    ?>
                    <h4 class="mb-3">Search Results for "<?=htmlspecialchars($keyword)?>"</h4>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                        <?php foreach($results as $item):
                            // Extract extra info from tvInfo if available
                            $info = $item['tvInfo'] ?? [];
                            $type = $info['showType'] ?? $item['type'] ?? 'TV';
                            $duration = $info['duration'] ?? '';
                            $release = $info['releaseDate'] ?? '';
                        ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm">
                                <div style="height: 300px; overflow: hidden; background: #000;">
                                    <img src="<?=$item['poster']?>" class="card-img-top" style="height: 100%; object-fit: cover; opacity: 0.9;" alt="<?=$item['title']?>">
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title text-truncate" title="<?=$item['title']?>"><?=$item['title']?></h5>
                                    <p class="card-text small text-muted mb-2">
                                        <?=$item['jname'] ?? ''?>
                                    </p>
                                    <div class="mb-3">
                                        <span class="badge bg-secondary"><?=$type?></span>
                                        <?php if($release): ?><span class="badge bg-info text-dark"><?=$release?></span><?php endif; ?>
                                        <?php if($duration): ?><span class="badge bg-light text-dark border"><?=$duration?></span><?php endif; ?>
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
                    echo '<div class="alert alert-info text-center p-5"><h4>No results found.</h4><p>Try a different keyword.</p></div>';
                }
            } else {
                echo '<div class="alert alert-warning">API connection failed or returned no data. Check API Config.</div>';
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
                        <p class="card-text">If search fails, you can copy the ID from the Zen/Anipaca URL and paste it here.</p>
                        <form method="GET" action="zen_import.php" class="d-flex gap-2">
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
        $info = fetchZen("/info?id=" . urlencode($import_id));
        if ($info && isset($info['success']) && $info['success']) {
            $data = $info['results']['data'] ?? null;
            if ($data) {
                // Pre-process genres
                $gList = [];
                if (isset($data['animeInfo']['Genres']) && is_array($data['animeInfo']['Genres'])) {
                    foreach($data['animeInfo']['Genres'] as $g) {
                        $gList[] = is_array($g) ? ($g['name'] ?? '') : $g;
                    }
                }
            ?>
            <div class="card shadow-lg mt-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Import Preview</h4>
                    <span class="badge bg-light text-dark"><?=$data['showType'] ?? 'TV'?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <img src="<?=$data['poster']?>" class="img-fluid rounded shadow mb-3" style="max-height: 400px;">
                        </div>
                        <div class="col-md-9">
                            <h2 class="mb-3"><?=$data['title']?></h2>
                            <table class="table table-sm table-borderless">
                                <tr><th width="150">Original Name:</th><td><?=$data['jname'] ?? 'N/A'?></td></tr>
                                <tr><th>Status:</th><td><span class="badge bg-warning text-dark"><?=$data['animeInfo']['Status'] ?? 'Unknown'?></span></td></tr>
                                <tr><th>Aired:</th><td><?=$data['animeInfo']['Aired'] ?? 'N/A'?></td></tr>
                                <tr><th>Genres:</th><td><?=implode(', ', array_filter($gList))?></td></tr>
                                <tr><th>Zen ID:</th><td><code><?=$data['id']?></code></td></tr>
                            </table>

                            <div class="mb-4 p-3 bg-light rounded border">
                                <h5>Synopsis</h5>
                                <p class="mb-0 text-muted">
                                    <?=nl2br(htmlspecialchars($data['animeInfo']['Overview'] ?? $data['description'] ?? 'No synopsis available.'))?>
                                </p>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                This process will import the anime details, cover image, genres, and all available episodes with streaming links.
                                <strong>This may take several minutes. Do not close the tab.</strong>
                            </div>

                            <form method="POST" action="?step=process_import">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="zen_id" value="<?=htmlspecialchars($import_id)?>">
                                <div class="d-grid gap-2 d-md-block">
                                    <button type="submit" class="btn btn-success btn-lg px-5">
                                        <i class="fas fa-file-import"></i> Confirm Import
                                    </button>
                                    <a href="?step=search&keyword=<?=urlencode($keyword)?>" class="btn btn-secondary btn-lg">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            } else {
                echo '<div class="alert alert-danger">API returned invalid data structure.</div>';
                echo '<a href="?step=search" class="btn btn-secondary">Back</a>';
            }
        } else {
            echo '<div class="alert alert-danger">Failed to load details from API. ID might be invalid.</div>';
            echo '<a href="?step=search" class="btn btn-secondary">Back</a>';
        }
        ?>
    <?php endif; ?>

</div>

<style>
    .card-img-top { transition: transform 0.3s ease; }
    .card:hover .card-img-top { transform: scale(1.05); }
    .stretched-link::after { position: absolute; top: 0; right: 0; bottom: 0; left: 0; z-index: 1; content: ""; }
</style>

<?php require_once 'layout/footer.php'; ?>
