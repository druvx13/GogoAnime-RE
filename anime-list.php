<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

// --- NEW SEARCH LOGIC ---
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

// --- ORIGINAL PAGINATION LOGIC (ADAPTED FOR SEARCH) ---
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$char = isset($_GET['char']) ? $_GET['char'] : 'All';

// Only run original pagination logic if not searching
if (!$hasSearched) {
    $limit = 50;
    $offset = ($page - 1) * $limit;

    $whereClause = "1=1";
    $params = [];
    if ($char != 'All') {
        $whereClause = "title LIKE :char";
        $params[':char'] = $char . '%';
    }

    $stmt = $conn->prepare("SELECT * FROM anime WHERE $whereClause ORDER BY title ASC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $animeList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// --- END ORIGINAL LOGIC ---
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

    <!-- Updated Title based on search -->
    <title><?php if ($hasSearched) { echo "Search Results for '$searchQuery'"; } else { echo "Anime List - $website_name"; } ?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo "List All Anime at $website_name | Anime List"; } ?>">
    <meta name="keywords" content="<?php if ($hasSearched) { echo "Search, $searchQuery, $website_name, Anime"; } else { echo "List All Anime, $website_name, Anime List"; } ?>">
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?php if ($hasSearched) { echo "Search Results for '$searchQuery'"; } else { echo "Anime List - $website_name"; } ?>" />
    <meta property="og:description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo "List All Anime at $website_name | Anime List"; } ?>">
    <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

    <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <?php require_once('./app/views/partials/advertisements/popup.html'); ?>
    <script>
        var base_url = 'https://' . document.domain . '/';
        var base_url_cdn_api = 'https://ajax.gogocdn.net/';
        var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
    </script>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js  "></script>
</head>

<body>
    <div class="clr"></div>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>
                <section class="content">
                    <section class="content_left">

                        <div class="main_body">
                            <!-- Updated Header based on search -->
                            <div class="anime_name anime_list">
                                <i class="icongec-anime_list i_pos"></i>
                                <h2><?php if ($hasSearched) { echo "Search Results for '$searchQuery'"; } else { echo "ANIME LIST"; } ?></h2>
                                <!-- Pagination container only shows if not searching -->
                                <?php if (!$hasSearched): ?>
                                <div class="anime_name_pagination">
                                    <div class="pagination">
                                        <ul class='pagination-list'>
                                            <?php
                                            $countStmt = $conn->prepare("SELECT COUNT(*) FROM anime WHERE $whereClause");
                                            foreach ($params as $k => $v) {
                                                $countStmt->bindValue($k, $v);
                                            }
                                            $countStmt->execute();
                                            $total = $countStmt->fetchColumn();

                                            $totalPages = ceil($total / $limit);

                                            for ($i = 1; $i <= $totalPages; $i++) {
                                                 if ($i > 10 && $i != $totalPages && $i != $page) continue;
                                                 $active = ($i == $page) ? 'selected' : '';
                                                 echo "<li class='$active'><a href='?char=$char&page=$i'>$i</a></li>";
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Alphabet filter only shows if not searching -->
                            <?php if (!$hasSearched): ?>
                            <div class="list_search">
                                <ul>
                                    <li class="first-char">
                                        <a href="?char=All" class="<?= (!isset($_GET['char']) || $_GET['char'] == 'All') ? 'active' : '' ?>" rel="all">All</a>
                                    </li>
                                    <?php foreach (range('A', 'Z') as $char) { ?>
                                        <li class="first-char">
                                            <a href="?char=<?=$char?>" class="<?= (isset($_GET['char']) && $_GET['char'] == $char) ? 'active' : '' ?>" rel=""><?=$char?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                            
                            <div class="anime_list_body">
                                <ul class="listing">
                                    <?php
                                    // Display search results if searched, otherwise display paginated list
                                    if ($hasSearched) {
                                        // Display search results
                                        foreach($searchResults as $anime) {
                                            $link = "/anime-details.php?id=" . $anime['id'];
                                            $title = htmlspecialchars($anime['title']);
                                            echo "<li title='$title'><a href='$link'>$title</a></li>";
                                        }
                                        // Show message if no results found
                                        if (empty($searchResults)) {
                                            echo "<p>No anime found matching '$searchQuery'.</p>";
                                        }
                                    } else {
                                        // Display original paginated list
                                        foreach($animeList as $anime) {
                                            $link = "/anime-details.php?id=" . $anime['id'];
                                            $title = htmlspecialchars($anime['title']);
                                            echo "<li title='$title'><a href='$link'>$title</a></li>";
                                        }
                                    }
                                    ?>
                                </ul>
                                <div class="clr"></div>
                            </div>
                        </div>

                    </section>
                    <section class="content_right">
                    <div class="headnav_center"></div>

                        <div class="clr"></div>
                        <div class="main_body">
                            <div class="main_body_black">
                                <div class="anime_name ongoing">
                                    <i class="icongec-ongoing i_pos"></i>
                                    <h2>RECENT RELEASE</h2>
                                </div>
                                <div class="recent">
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
                                            <?php require_once('./app/views/partials/recentRelease.php'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- tao thanh cuon 1-->
                                </div>
                            </div>
                        </div>
                        <div class="clr"></div>
                        <div id="load_ads_2">
                            <div id="media.net sticky ad" style="display: inline-block">
                            </div>
                        </div>
                        <style type="text/css">
                            #load_ads_2 {
                                width: 300px;
                            }

                            #load_ads_2.sticky {
                                position: fixed;
                                top: 0;
                            }

                            #scrollbar2 .viewport {
                                height: 1000px !important;
                            }
                        </style>
                        <script>
                            var leftamt;
                            function scrollFunction() {
                                var scamt = (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
                                var element = document.getElementById("media.net sticky ad");
                                if (scamt > leftamt) {
                                    var leftPosition = element.getBoundingClientRect().left;
                                    element.className = element.className.replace(/(?:^|\s)fixclass(?!\S)/g, '');
                                    element.className += " fixclass";
                                    element.style.left = leftPosition + 'px';
                                }
                                else {
                                    element.className = element.className.replace(/(?:^|\s)fixclass(?!\S)/g, '');
                                }
                            }
                            function getElementTopLeft(id) {
                                var ele = document.getElementById(id);
                                var top = 0;
                                var left = 0;
                                while (ele.tagName != "BODY") {
                                    top += ele.offsetTop;
                                    left += ele.offsetLeft;
                                    ele = ele.offsetParent;
                                }
                                return { top: top, left: left };
                            }
                            function abcd() {
                                TopLeft = getElementTopLeft("media.net sticky ad");
                                leftamt = TopLeft.top;
                                //leftamt -= 10;
                            }
                            window.onload = abcd;
                            window.onscroll = scrollFunction;
                        </script>
                        <?php require_once('./app/views/partials/sub-category.html'); ?>
                    </section>
                </section>
                <div class="clr"></div>
                <footer>
                    <div class="menu_bottom">
                        <a href="/about-us.html">
                            <h3>Abouts us</h3>
                        </a>
                        <a href="/contact-us.html">
                            <h3>Contact us</h3>
                        </a>
                        <a href="/privacy.html">
                            <h3>Privacy</h3>
                        </a>
                    </div>
                    <div class="croll">
                        <div class="big"><i class="icongec-backtop"></i></div>
                        <div class="small"><i class="icongec-backtop_mb"></i></div>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    <div id="off_light"></div>
    <div class="clr"></div>
    <div class="mask"></div>
        <script type="text/javascript" src="<?=$base_url?>/assets/js/files/combo.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/video.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jquery.tinyscrollbar.min.js"></script>
    <?php include('./app/views/partials/footer.php')?> 

    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jqueryTooltip.js"></script>
    <script type="text/javascript">
        $(".listing li[title]").tooltip({ offset: [10, 200], effect: 'slide', predelay: 300 }).dynamic({ bottom: { direction: 'down', bounce: true } });
    </script>
    <style type="text/css">
        .hide {
            display: none;
        }

        .anime_list_body,
        .anime_list_body ul {
            width: 100%;
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
            -mos-box-sizing: border-box;
        }

        .anime_list_body ul li,
        .anime_list_body ul li a {
            white-space: nowrap;
            overflow: hidden;
            padding-right: 10px;
            display: block;
        }

        .anime_list_body {
            padding: 14px 18px;
        }

        .anime_list_body ul li a {
            line-height: 115%;
        }
    </style>

    <script>
        if (document.getElementById('scrollbar2')) {
            $('#scrollbar2').tinyscrollbar();
        }
    </script>
</body>

</html>