<?php 
require_once('./app/config/info.php');
require_once('./app/config/db.php');

// --- NEW SEARCH LOGIC (Same as other pages) ---
$searchQuery = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$searchResults = [];
$hasSearched = false;

if ($searchQuery !== '') {
    $hasSearched = true;
    $searchTerm = '%' . $searchQuery . '%';
    $searchStmt = $conn->prepare("SELECT id, title FROM anime WHERE title LIKE :title ORDER BY title ASC");
    $searchStmt->bindValue(':title', $searchTerm, PDO::PARAM_STR);
    $searchStmt->execute();
    $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
}
// --- END NEW SEARCH LOGIC ---
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico" />

    <!-- Updated Title based on search -->
    <title><?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "Watch anime online, English anime online - $website_name"; } ?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo "Watch anime online in English. You can watch free series and movies online and English subtitle."; } ?>" />
    <meta name="keywords" content="<?php if ($hasSearched) { echo "Search, $searchQuery, $website_name, Anime"; } else { echo "gogoanime,watch anime, anime online, free anime, english anime, sites to watch anime"; } ?>" />
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "$website_name | Watch anime online, English anime online HD"; } ?>" />
    <meta property="og:description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo "Watch anime online in English. You can watch free series and movies online and English subtitle."; } ?>" />
    <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="twitter:card" content="summary" />
    <meta property="twitter:title" content="<?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "$website_name | Watch anime online, English anime online HD"; } ?>" />
    <meta property="twitter:description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo "Watch anime online in English. You can watch free series and movies online and English subtitle."; } ?>" />

    <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />

    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />

    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script>
        var base_url = 'http://' . document.domain . '/';
        var base_url_cdn_api = 'https://ajax.gogocdn.net/';
        var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
    </script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>

    <?php require_once('./app/views/partials/advertisements/popup.html'); ?>
    
</head>

<body>
    <div class="clr"></div>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>
                
                <?php if ($hasSearched): ?>
                <!-- Display Search Results -->
                <section class="content">
                    <section class="content_left">
                        <div class="main_body">
                            <div class="anime_name anime_list"> <!-- Reusing anime_list class for consistency -->
                                <i class="icongec-anime_list i_pos"></i> <!-- Reusing icon for consistency -->
                                <h2>Search Results for '<?=$searchQuery?>'</h2>
                            </div>
                            <div class="anime_list_body">
                                <ul class="listing">
                                    <?php foreach($searchResults as $anime_result): ?>
                                        <li title='<?=htmlspecialchars($anime_result['title'])?>'>
                                            <a href="/anime-details.php?id=<?=$anime_result['id']?>"><?=htmlspecialchars($anime_result['title'])?></a>
                                        </li>
                                    <?php endforeach; ?>
                                    <?php if (empty($searchResults)): ?>
                                        <li><p>No anime found matching '<?=$searchQuery?>'.</p></li>
                                    <?php endif; ?>
                                </ul>
                                <div class="clr"></div>
                            </div>
                        </div>
                    </section>
                    <section class="content_right">
                        <?php require_once('./app/views/partials/sub-category.html'); ?>
                    </section>
                </section>
                <?php else: ?>
                <!-- Display Original Home Content -->
                <section class="content">
                    <section class="content_left">

                        <h1 class="seohiden">Gogoanime | Watch anime online, English anime online HD</h1>
                        <!-- Recent Release--->
                        <div class="main_body">
                            <div id="load_recent_release">
                                <input type="hidden" id="type" name="type" value="1" />
                                <div class="anime_name recent_release">
                                    <i class="icongec-recent_release i_pos"></i>
                                    <?php
                                    // --- MODIFIED LOGIC FOR DEFAULT (ALL LANGUAGES) ---
                                    // Determine the type from the URL, default to 0 (All) if not set
                                    $type = isset($_GET['type']) ? (int)$_GET['type'] : 0; 
                                    $subClass = ($type == 1) ? 'active' : '';
                                    $dubClass = ($type == 2) ? 'active' : '';
                                    $chiClass = ($type == 3) ? 'active' : '';
                                    $allClass = ($type == 0) ? 'active' : ''; // New class for "All"
                                    ?>
                                    <h2>
                                        <a href="?type=0" class="dub <?=$allClass?>">Recent Release (All)</a> <!-- New link for All -->
                                        <span style="padding:0 10px; color:#010101;">|</span>
                                        <a href="?type=1" class="dub <?=$subClass?>">SUB</a>
                                        <span style="padding:0 10px; color:#010101;">|</span>
                                        <a href="?type=2" class="dub <?=$dubClass?>">DUB</a>
                                        <span class="chinese" style="padding:0 10px; color:#010101;">|</span>
                                        <a href="?type=3" class="dub chinese <?=$chiClass?>">Chinese</a>
                                    </h2>
                                    <div class="anime_name_pagination intro">
                                        <div class="pagination recent">
                                            <ul class='pagination-list'>
                                                <?php
                                                // Pagination Logic
                                                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                                                if ($page < 1) $page = 1;
                                                $limit = 10;
                                                $offset = ($page - 1) * $limit;

                                                // Count total recent releases (episodes)
                                                require_once('./app/config/db.php');

                                                // --- MODIFIED LOGIC FOR DEFAULT (ALL LANGUAGES) ---
                                                // Determine the language condition based on the selected type
                                                $langConditionCount = "1=1"; // Default to show all if type is 0 or invalid
                                                if ($type == 1) {
                                                    $langConditionCount = "a.language = 'Sub'";
                                                } elseif ($type == 2) {
                                                    $langConditionCount = "a.language = 'Dub'";
                                                } elseif ($type == 3) {
                                                    $langConditionCount = "a.language = 'Chinese'";
                                                }
                                                // For $type == 0 or any other value, $langConditionCount remains "1=1"

                                                $countStmt = $conn->query("
                                                    SELECT COUNT(*)
                                                    FROM episodes e
                                                    JOIN anime a ON e.anime_id = a.id
                                                    WHERE $langConditionCount
                                                ");
                                                $totalEpisodes = $countStmt->fetchColumn();
                                                $totalPages = ceil($totalEpisodes / $limit);

                                                require_once('./app/helpers/pagination_helper.php');
                                                echo PaginationHelper::render($page, $totalPages, ['type' => $type]);
                                                ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="last_episodes loaddub">
                                    <ul class="items">
                                        <?php
                                          // --- MODIFIED LOGIC FOR DEFAULT (ALL LANGUAGES) ---
                                          // Fetch recent releases (episodes) from DB with pagination and language filter
                                          $langCondition = "1=1"; // Default to show all if type is 0 or invalid
                                          if ($type == 1) {
                                              $langCondition = "a.language = 'Sub'";
                                          } elseif ($type == 2) {
                                              $langCondition = "a.language = 'Dub'";
                                          } elseif ($type == 3) {
                                              $langCondition = "a.language = 'Chinese'";
                                          }
                                          // For $type == 0 or any other value, $langCondition remains "1=1"

                                          $stmt = $conn->prepare("
                                              SELECT e.id, e.episode_number, e.title as ep_title, a.title as anime_title, a.image_url, a.language
                                              FROM episodes e
                                              JOIN anime a ON e.anime_id = a.id
                                              WHERE $langCondition
                                              ORDER BY e.created_at DESC
                                              LIMIT :limit OFFSET :offset
                                          ");
                                          $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                                          $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                                          $stmt->execute();
                                          $recentReleases = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                          foreach($recentReleases as $recentRelease)  {
                                              $animeName = htmlspecialchars($recentRelease['anime_title']);
                                              $imgUrl = htmlspecialchars($recentRelease['image_url']);
                                              $episodeNum = htmlspecialchars($recentRelease['episode_number']);
                                              $episodeLink = "/streaming.php?id=" . $recentRelease['id']; // Using ID for simplicity
                                        ?>
                                        <li>
                                            <div class="img">
                                                <a href="<?=$episodeLink?>"
                                                    title="<?=$animeName?>">
                                                    <img src="<?=$imgUrl?>"
                                                        alt="<?=$animeName?>" />
                                                    <?php
                                                        $langClass = 'ic-SUB';
                                                        if (isset($recentRelease['language'])) {
                                                            if ($recentRelease['language'] == 'Dub') $langClass = 'ic-DUB';
                                                            if ($recentRelease['language'] == 'Chinese') $langClass = 'ic-RAW'; // Assuming RAW icon for Chinese
                                                        }
                                                    ?>
                                                    <div class="type <?=$langClass?>"></div>
                                                </a>
                                            </div>
                                            <p class="name"><a href="<?=$episodeLink?>"
                                                    title="<?=$animeName?>"><?=$animeName?></a></p>
                                            <p class="episode">Episode <?=$episodeNum?></p>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="clr"></div>
                        <!--/ Recent Release--->
                        <!-- Featured Comedy / Romance Anime--->
                        <div class="main_body">
                            <div id="load_popular_ongoing">

                            </div>
                        </div>
                        <div class="clr"></div> <!-- /Featured Comedy / Romance Anime--->

                        <!-- Recently Added Series--->
                        <div class="main_body none">
                            <div class="anime_name added_series">
                                <i class="icongec-added_series i_pos"></i>
                                <h2>Recently Added Series</h2>
                            </div>
                            <div class="added_series_body final">
                                <ul class="listing">
                                <?php
                                    // Fetch recently added series
                                    $stmt = $conn->query("SELECT id, title, slug FROM anime ORDER BY created_at DESC LIMIT 20");
                                    $recentlyAddedList = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach($recentlyAddedList as $recentlyAdded)  {
                                        $link = "/anime-details.php?id=" . $recentlyAdded['id'];
                                ?>
                                    <li>
                                        <a href="<?=$link?>" title="<?=htmlspecialchars($recentlyAdded['title'])?>"><?=htmlspecialchars($recentlyAdded['title'])?></a>
                                    </li>
                                <?php } ?>
                                </ul>
                            </div>
                        </div>
                        <!-- / Recently Added Series--->

                    </section>
                    <section class="content_right">
			<div class="headnav_center"></div>
                        <div class="main_body">
                            <div class="main_body_black">
                                <div class="anime_name anime_info">
                                    <i class="icongec-anime_info i_pos"></i>
                                    <div class="topview">
                                        <div class="tab">
                                            <div class="tab_icon one1" onclick="loadTopViews(this, 1)">Day</div>
                                            <div class="tab_icon one2" onclick="loadTopViews(this, 2)">Week</div>
                                            <div class="tab_icon one3" onclick="loadTopViews(this, 3)">Month</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="topview" id="load-anclytic">
                                    <div class="clr"></div>
                                    <div class="movies_show">
                                        <div id="laoding">
                                            <div class="loaders"></div>
                                        </div>
                                        <div id="load_topivews" class="views1"></div>
                                        <div id="load_topivews" class="views2"></div>
                                        <div id="load_topivews" class="views3"></div>
                                    </div>
                                    <div class="clr"></div>
                                </div>
                            </div>
                        </div>
                        <div class="clr"></div>

                        <div class="clr"></div>
                        <div class="main_body">
                            <div class="main_body_black">
                                <div class="anime_name ongoing">
                                    <i class="icongec-ongoing i_pos"></i>
                                    <h2>ongoing series</h2>
                                </div>
                                <div class="series">
                                    <!-- begon -->
                                    <div id="scrollbar2">
                                        <div class="scrollbar">
                                            <div class="track">
                                                <div class="thumb">
                                                    <div class="end"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="viewport">
                                            <div class="overview">
                                                <nav class="menu_series cron">
                                                    <ul>
                                                    <?php
                                                      // Fetch ongoing series
                                                      $stmt = $conn->query("SELECT id, title FROM anime WHERE status = 'Ongoing' ORDER BY title ASC LIMIT 20");
                                                      $ongoingSeriesList = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                                      foreach($ongoingSeriesList as $ongoingSeries)  {
                                                          $link = "/anime-details.php?id=" . $ongoingSeries['id'];
                                                     ?>
                                                        <li>
                                                           <a href="<?=$link?>"
                                                           title="<?=htmlspecialchars($ongoingSeries['title'])?>"><?=htmlspecialchars($ongoingSeries['title'])?></a>
                                                        </li>
                                                     <?php } ?>
                                                    </ul>
                                                </nav>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- tao thanh cuon 1-->
                                </div>
                            </div>
                        </div>
                        <style type="text/css">
                            #scrollbar2 .viewport {
                                height: 600px !important;
                            }
                        </style>
                        <div class="main_body">
                            <div class="main_body_black">
                                <div class="anime_name genre">
                                    <i class="icongec-genre i_pos"></i>
                                    <h2>Genres</h2>
                                </div>
                                <div class="recent">
                                    <nav class="menu_series genre right">
                                        <ul>
                                        <?php
                                          $genreStmt = $conn->query("SELECT * FROM genres ORDER BY name ASC");
                                          $genres = $genreStmt->fetchAll(PDO::FETCH_ASSOC);
                                          foreach($genres as $genre) {
                                              echo "<li><a href='genre.php?slug={$genre['slug']}' title='{$genre['name']}'>{$genre['name']}</a></li>";
                                          }
                                        ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                        <?php require_once('./app/views/partials/sub-category.html'); ?>
                    </section>
                </section>
                <?php endif; ?>
                <div class="clr"></div>
                <?php require_once('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
    <div id="off_light"></div>
    <div class="clr"></div>
    <div class="mask"></div>
        <script type="text/javascript" src="<?=$base_url?>/assets/js/files/combo.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/video.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jquery.tinyscrollbar.min.js"></script>
<script type="text/javascript">
$(document).ready(function () {
  $('.btn-notice').click(function (e) {
    $('.bg-notice').hide();
    $(this).hide();
  });
});
</script>
<style type="text/css">
  @media only screen and (min-width: 387px) {
    .btn-notice {bottom:36px;}  
  }
  @media only screen and (max-width: 386px) {
    .btn-notice {bottom: 52px;}
  }
</style>
<!---<div class="bg-notice" style="position:fixed;z-index:9999;background:#ffc119;bottom:0;text-align:center;color:#000;width:100%;padding:10px 0;font-weight:600;">We moved site to <a href="<?=$base_url?>" title="<?=$base_url?>" alt="Gogoanime"><?=$base_url?></a>. Please bookmark new site. Thank you!</div><div class="btn-notice" style="position:fixed;z-index:9999;background:#00a651;color:#fff;cursor:pointer;right:0;padding:3px 8px;">x</div>--->

    <script>
        if (document.getElementById('scrollbar2')) {
            $('#scrollbar2').tinyscrollbar();
        }
    </script>
</body>

</html>
