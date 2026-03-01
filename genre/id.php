<?php 
require_once('../app/config/info.php');
require_once('../app/config/db.php'); // Include DB

$parts=parse_url($_SERVER['REQUEST_URI']); 
$page_url=explode('/', $parts['path']);
$id = $page_url[count($page_url)-1];
//$id = "slice+of+life";
$genreSlug = str_replace("+", "-", $id); // Assuming URL structure matches DB slug
$genreName = str_replace(["+", "-"], " ", $id);
$genreName = ucwords($genreName);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// [GAP-001] Native MySQL Implementation for Genre
// 1. Get Genre ID from Slug
$genreStmt = $conn->prepare("SELECT id, name FROM genres WHERE slug = :slug");
$genreStmt->execute(['slug' => $genreSlug]);
$genreData = $genreStmt->fetch(PDO::FETCH_ASSOC);

if (!$genreData) {
    // Fallback or 404 behavior - keeping it simple for now as per constraints
    $genreId = 0;
} else {
    $genreId = $genreData['id'];
    $genreName = $genreData['name']; // Use DB name for accuracy
}

// 2. Count Total Anime in Genre
$countStmt = $conn->prepare("
    SELECT COUNT(*)
    FROM anime a
    JOIN anime_genre ag ON a.id = ag.anime_id
    WHERE ag.genre_id = :gid
");
$countStmt->execute(['gid' => $genreId]);
$totalAnime = $countStmt->fetchColumn();
$totalPages = ceil($totalAnime / $limit);

// 3. Fetch Anime in Genre
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.image_url, a.release_date
    FROM anime a
    JOIN anime_genre ag ON a.id = ag.anime_id
    WHERE ag.genre_id = :gid
    ORDER BY a.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':gid', $genreId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$animeList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en-US">

<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">


        <title>List genre <?=$genreName?> at <?=$website_name?></title>

        <meta name="robots" content="index, follow" />
        <meta name="description" content="List genre <?=$genreName?> at <?=$website_name?>">
        <meta name="keywords" content="List genre Anime, Anime Movies">
        <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="og:site_name" content="<?=$website_name?>" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="List genre <?=$genreName?> at <?=$website_name?>" />
        <meta property="og:description" content="List genre <?=$genreName?> at <?=$website_name?>">
        <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
        <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
        <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="twitter:card" content="summary" />
        <meta property="twitter:title" content="List genre <?=$genreName?> at <?=$website_name?>" />
        <meta property="twitter:description" content="List genre <?=$genreName?> at <?=$website_name?>" />

        <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
        <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />



        <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css?v=7.1" />
        <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />

        <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
        <script>
                var base_url = 'https://' + document.domain + '/';
                var base_url_cdn_api = 'https://ajax.gogocdn.net/';
                var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
        </script>
        <?php require_once('../app/views/partials/advertisements/popup.html'); ?>
        <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>
</head>

<body>
        <div class="clr"></div>
        <div id="wrapper_inside">
                <div id="wrapper">
                        <div id="wrapper_bg">
                                <?php require_once('../app/views/partials/header.php');?>
                                <section class="content">
                                        <section class="content_left">

                                                <div class="main_body">
                                                        <div class="anime_name anime_movies">
                                                                <i class="icongec-anime_movies i_pos"></i>
                                                                <h2>genre <?=$genreName?></h2>
                                                                <div class="anime_name_pagination">
                                                                        <div class="pagination">
                                                                            <ul class='pagination-list'>
                                                                                <?php
                                                                                require_once('../app/helpers/pagination_helper.php');
                                                                                echo PaginationHelper::render($page, $totalPages, []);
                                                                                ?>
                                                                            </ul>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        <div class="last_episodes">
                                                                <ul class="items">
                                                                <?php foreach($animeList as $anime)  {
                                                                    $link = "/anime-details.php?id=" . $anime['id'];
                                                                    $title = htmlspecialchars($anime['title']);
                                                                    $img = htmlspecialchars($anime['image_url']);
                                                                    $date = htmlspecialchars($anime['release_date']);
                                                                ?>
                                                                        <li>

                                                                                <div class="img">
                                                                                        <a href="<?=$link?>" title="<?=$title?>">
                                                                                                <img src="<?=$img?>" alt="<?=$title?>" />
                                                                                        </a>
                                                                                </div>
                                                                                <p class="name"><a href="<?=$link?>" title="<?=$title?>"><?=$title?></a>
                                                                                </p>
                                                                                <p class="released"><?=$date?></p>
                                                                        </li>
                                                                <?php } ?>
                                                                </ul>
                                                        </div>
                                                </div>

                                        </section>
                                        <section class="content_right">
                                        

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
                                                                                                        <div
                                                                                                                class="end">
                                                                                                        </div>
                                                                                                </div>
                                                                                        </div>
                                                                                </div>
                                                                                <div class="viewport">
                                                                                        <div class="overview">
                                                                                                <?php require_once('../app/views/partials/recentRelease.php'); ?>
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
                                                <?php require_once('../app/views/partials/sub-category.html');?>
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
        <?php include('../app/views/partials/footer.php')?>
        <script>
                if (document.getElementById('scrollbar2')) {
                        $('#scrollbar2').tinyscrollbar();
                }
        </script>

</body>

</html>
