<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

// Helper function to keep filter state
function is_checked($key, $value, $default = false) {
    if (!isset($_GET[$key])) {
        return $default ? 'checked' : '';
    }
    if (is_array($_GET[$key])) {
        return in_array($value, $_GET[$key]) ? 'checked' : '';
    }
    return $_GET[$key] == $value ? 'checked' : '';
}

// Fetch Filters Data from DB
try {
    $genre_stmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
    $all_genres = $genre_stmt->fetchAll(PDO::FETCH_ASSOC);

    $country_stmt = $conn->query("SELECT * FROM countries ORDER BY name ASC");
    $all_countries = $country_stmt->fetchAll(PDO::FETCH_ASSOC);

    $season_stmt = $conn->query("SELECT * FROM seasons ORDER BY name ASC");
    $all_seasons = $season_stmt->fetchAll(PDO::FETCH_ASSOC);

    $type_stmt = $conn->query("SELECT * FROM types ORDER BY name ASC");
    $all_types = $type_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error or use defaults
    $all_genres = [];
    $all_countries = [];
    $all_seasons = [];
    $all_types = [];
}

// Build Query
$where = [];
$params = [];
$joins = [];

// Keyword
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (!empty($keyword)) {
    $where[] = "anime.title LIKE :keyword";
    $params[':keyword'] = "%$keyword%";
}

// Genres
if (isset($_GET['genre']) && is_array($_GET['genre'])) {
    $genres = $_GET['genre'];
    foreach ($genres as $index => $genreSlug) {
        $alias = "g" . $index;
        $joins[] = "JOIN anime_genre AS ag$index ON anime.id = ag$index.anime_id JOIN genres AS $alias ON ag$index.genre_id = $alias.id";
        $where[] = "$alias.slug = :genre_$index";
        $params[":genre_$index"] = $genreSlug;
    }
}

// Country (Now DB supported)
if (isset($_GET['country']) && is_array($_GET['country'])) {
    $countries = $_GET['country'];
    $countryClauses = [];
    foreach ($countries as $index => $cVal) {
        $countryClauses[] = "anime.country_id IN (SELECT id FROM countries WHERE value = :country_$index OR id = :country_$index)";
        $params[":country_$index"] = $cVal;
    }
    if (!empty($countryClauses)) {
        $where[] = "(" . implode(" OR ", $countryClauses) . ")";
    }
}

// Season (Now DB supported)
if (isset($_GET['season']) && is_array($_GET['season'])) {
    $seasons = $_GET['season'];
    $seasonClauses = [];
    foreach ($seasons as $index => $sVal) {
        $seasonClauses[] = "anime.season_id IN (SELECT id FROM seasons WHERE value = :season_$index OR id = :season_$index)";
        $params[":season_$index"] = $sVal;
    }
    if (!empty($seasonClauses)) {
        $where[] = "(" . implode(" OR ", $seasonClauses) . ")";
    }
}

// Year (Still relevant)
if (isset($_GET['year']) && is_array($_GET['year'])) {
    $years = $_GET['year'];
    $yearClauses = [];
    foreach ($years as $index => $y) {
        $yearClauses[] = "anime.release_date LIKE :year_$index";
        $params[":year_$index"] = "%$y%";
    }
    if (!empty($yearClauses)) {
        $where[] = "(" . implode(" OR ", $yearClauses) . ")";
    }
}

// Language
if (isset($_GET['language']) && is_array($_GET['language'])) {
    $langs = $_GET['language'];
    $langClauses = [];
    foreach ($langs as $index => $l) {
        $val = ucfirst($l);
        $langClauses[] = "anime.language = :lang_$index";
        $params[":lang_$index"] = $val;
    }
    if (!empty($langClauses)) {
        $where[] = "(" . implode(" OR ", $langClauses) . ")";
    }
}

// Type (DB supported via type_id or type string)
if (isset($_GET['type']) && is_array($_GET['type'])) {
    $types = $_GET['type'];
    $typeClauses = [];
    foreach ($types as $index => $tVal) {
         // Filter by type_id (matching value or id)
         $typeClauses[] = "anime.type_id IN (SELECT id FROM types WHERE value = :type_$index OR id = :type_$index)";
         $params[":type_$index"] = $tVal;
    }
    if (!empty($typeClauses)) {
        $where[] = "(" . implode(" OR ", $typeClauses) . ")";
    }
}

// Status
if (isset($_GET['status']) && is_array($_GET['status'])) {
    $statuses = $_GET['status'];
    $statusClauses = [];
    foreach ($statuses as $index => $s) {
        $statusClauses[] = "anime.status = :status_$index";
        $params[":status_$index"] = $s;
    }
    if (!empty($statusClauses)) {
        $where[] = "(" . implode(" OR ", $statusClauses) . ")";
    }
}

// Sort
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title_az';
$orderBy = "ORDER BY anime.title ASC";
if ($sort == 'recently_updated') {
    $orderBy = "ORDER BY anime.created_at DESC";
} elseif ($sort == 'recently_added') {
    $orderBy = "ORDER BY anime.created_at DESC";
} elseif ($sort == 'release_date') {
    $orderBy = "ORDER BY anime.release_date DESC";
}

// Pagination Logic
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Construct Query
$baseSql = "FROM anime " . implode(" ", $joins);
if (!empty($where)) {
    $baseSql .= " WHERE " . implode(" AND ", $where);
}

// Count Total
$countSql = "SELECT COUNT(DISTINCT anime.id) " . $baseSql;
try {
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalItems = $countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $limit);
} catch (PDOException $e) {
    $totalItems = 0;
    $totalPages = 0;
}

// Fetch Results
$sql = "SELECT DISTINCT anime.* " . $baseSql . " " . $orderBy . " LIMIT :limit OFFSET :offset";
try {
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $results = [];
    // error_log($e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Result - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>
    <style>
        .filters .dropdown-toggle { cursor: pointer; }
    </style>
</head>
<body>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>
                <section class="content">
                    <section class="content_left">
                        <div class="main_body">
                            <div class="anime_name search-result">
                                <i class="icongec-search-result i_pos"></i>
                                <h2>Search Result</h2>
                                <div class="anime_name_pagination">
                                    <div class="pagination">
                                        <ul class='pagination-list'>
                                            <?php
                                            require_once('./app/helpers/pagination_helper.php');
                                            $queryParams = $_GET;
                                            unset($queryParams['page']);
                                            echo PaginationHelper::render($page, $totalPages, $queryParams);
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="last_episodes">
                                <div class="search_ads">
                                    <form class="filters" action="<?=$base_url?>/search.php" autocomplete="off">
                                        <div class="filter"><input type="text" class="form-control" placeholder="Search..." name="keyword" value="<?=htmlspecialchars($keyword)?>" /></div>

                                        <!-- Genre -->
                                        <div class="filter">
                                            <div class="dropdown cls_genre">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select genre" data-label-placement="true">Select genre</span></button>
                                                <ul class="dropdown-menu lg c4">
                                                    <?php foreach($all_genres as $g): ?>
                                                        <li title="<?=htmlspecialchars($g['name'])?>"><input type="checkbox" id="genre-<?=htmlspecialchars($g['slug'])?>" name="genre[]" value="<?=htmlspecialchars($g['slug'])?>" <?=is_checked('genre', $g['slug'])?> /> <label for="genre-<?=htmlspecialchars($g['slug'])?>"><?=htmlspecialchars($g['name'])?></label></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Country -->
                                        <div class="filter">
                                            <div class="dropdown cls_country">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select country" data-label-placement="true">Select country</span></button>
                                                <ul class="dropdown-menu c1">
                                                    <?php foreach($all_countries as $c): ?>
                                                        <li title="<?=htmlspecialchars($c['name'])?>"><input type="checkbox" id="country-<?=$c['id']?>" name="country[]" value="<?=htmlspecialchars($c['value'] ? $c['value'] : $c['id'])?>" <?=is_checked('country', $c['value'] ? $c['value'] : $c['id'])?> /> <label for="country-<?=$c['id']?>"><?=htmlspecialchars($c['name'])?></label></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Season -->
                                        <div class="filter">
                                            <div class="dropdown cls_season">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select season" data-label-placement="true">Select season</span></button>
                                                <ul class="dropdown-menu c1">
                                                    <?php foreach($all_seasons as $s): ?>
                                                        <li><input type="checkbox" id="season-<?=$s['id']?>" name="season[]" value="<?=htmlspecialchars($s['value'] ? $s['value'] : $s['id'])?>" <?=is_checked('season', $s['value'] ? $s['value'] : $s['id'])?> /> <label for="season-<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></label></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Year -->
                                        <div class="filter">
                                            <div class="dropdown cls_year">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select year" data-label-placement="true">Select year</span></button>
                                                <ul class="dropdown-menu md c3">
                                                    <?php
                                                    for ($y = 2025; $y >= 2000; $y--) {
                                                        $chk = is_checked('year', (string)$y);
                                                        echo "<li><input type='checkbox' id='year-$y' name='year[]' value='$y' $chk /> <label for='year-$y'>$y</label></li>";
                                                    }
                                                    ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Language -->
                                        <div class="filter">
                                            <div class="dropdown cls_lang">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select language" data-label-placement="true">Select language</span></button>
                                                <ul class="dropdown-menu c1">
                                                    <li><input type="checkbox" id="language-sub" name="language[]" value="sub" <?=is_checked('language', 'sub')?> /> <label for="language-sub">Sub</label></li>
                                                    <li><input type="checkbox" id="language-dub" name="language[]" value="dub" <?=is_checked('language', 'dub')?> /> <label for="language-dub">Dub</label></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Type -->
                                        <div class="filter">
                                            <div class="dropdown cls_type">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select type" data-label-placement="true">Select type</span></button>
                                                <ul class="dropdown-menu c1">
                                                     <?php foreach($all_types as $t): ?>
                                                        <li><input type="checkbox" id="type-<?=$t['id']?>" name="type[]" value="<?=htmlspecialchars($t['value'] ? $t['value'] : $t['id'])?>" <?=is_checked('type', $t['value'] ? $t['value'] : $t['id'])?> /> <label for="type-<?=$t['id']?>"><?=htmlspecialchars($t['name'])?></label></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div class="filter">
                                            <div class="dropdown cls_status">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Select status" data-label-placement="true">Select status</span></button>
                                                <ul class="dropdown-menu c1">
                                                    <li><input type="checkbox" id="status-upcoming" name="status[]" value="Upcoming" <?=is_checked('status', 'Upcoming')?> /> <label for="status-upcoming">Not Yet Aired</label></li>
                                                    <li><input type="checkbox" id="status-ongoing" name="status[]" value="Ongoing" <?=is_checked('status', 'Ongoing')?> /> <label for="status-ongoing">Ongoing</label></li>
                                                    <li><input type="checkbox" id="status-completed" name="status[]" value="Completed" <?=is_checked('status', 'Completed')?> /> <label for="status-completed">Completed</label></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <!-- Sort -->
                                        <div class="filter">
                                            <div class="dropdown cls_sort">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown"><span class="value" data-placeholder="Sort by" data-label-placement="true">Sort by</span></button>
                                                <ul class="dropdown-menu c1">
                                                    <li><input type="radio" id="sort-title_az" name="sort" value="title_az" <?=is_checked('sort', 'title_az', true)?> /> <label for="sort-title_az">Name A-Z</label></li>
                                                    <li><input type="radio" id="sort-recently_updated" name="sort" value="recently_updated" <?=is_checked('sort', 'recently_updated')?> /> <label for="sort-recently_updated">Recently updated</label></li>
                                                    <li><input type="radio" id="sort-recently_added" name="sort" value="recently_added" <?=is_checked('sort', 'recently_added')?> /> <label for="sort-recently_added">Recently added</label></li>
                                                    <li><input type="radio" id="sort-release_date" name="sort" value="release_date" <?=is_checked('sort', 'release_date')?> /> <label for="sort-release_date">Release date</label></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="submit filter w-auto">
                                            <button class="btn btn-primary d-flex align-items-center">
                                                <span class="ml-1">Filter</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <ul class="items">
                                    <?php
                                    if (!empty($results)) {
                                        foreach($results as $anime) {
                                            $link = "/anime-details.php?id=" . $anime['id'];
                                            $title = htmlspecialchars($anime['title']);
                                            $img = htmlspecialchars($anime['image_url']);
                                            $date = htmlspecialchars($anime['release_date']);
                                            echo "<li>
                                                <div class='img'>
                                                    <a href='$link' title='$title'>
                                                        <img src='$img' alt='$title' />
                                                    </a>
                                                </div>
                                                <p class='name'><a href='$link' title='$title'>$title</a></p>
                                                <p class='released'>Released: $date</p>
                                            </li>";
                                        }
                                    } else {
                                        echo "<p>No results found.</p>";
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </section>
                </section>
                <?php include('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            // Toggle dropdowns
            $('.dropdown-toggle').click(function(e) {
                e.stopPropagation();
                // Close other dropdowns
                $('.dropdown-menu').not($(this).next('.dropdown-menu')).removeClass('show');
                // Toggle current
                $(this).next('.dropdown-menu').toggleClass('show');
            });

            // Close dropdowns on click outside
            $(document).click(function() {
                $('.dropdown-menu').removeClass('show');
            });

            // Prevent closing when clicking inside dropdown menu
            $('.dropdown-menu').click(function(e) {
                e.stopPropagation();
            });
        });
    </script>
</body>
</html>
