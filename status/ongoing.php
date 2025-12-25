<?php 
require_once('../app/config/info.php');
require_once('../app/config/db.php'); // Include DB connection
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// [GAP-001] Native MySQL Implementation for Ongoing Status
// Count total ongoing anime
$countStmt = $conn->query("SELECT COUNT(*) FROM anime WHERE status = 'Ongoing'");
$totalAnime = $countStmt->fetchColumn();
$totalPages = ceil($totalAnime / $limit);

// Fetch ongoing anime
$stmt = $conn->prepare("SELECT id, title, image_url, status FROM anime WHERE status = 'Ongoing' ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$ongoingList = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en-US">

<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico" />
        <title>List ongoing Anime at <?=$website_name?></title>
        <meta name="robots" content="index, follow" />
        <meta name="description" content="List ongoing Anime at <?=$website_name?>">
        <meta name="keywords" content="List ongoing Anime, Ongoing Anime">
        <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="og:site_name" content="<?=$website_name?>" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="List ongoing Anime at <?=$website_name?>" />
        <meta property="og:description" content="List ongoing Anime at <?=$website_name?>">
        <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
        <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
        <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="twitter:card" content="summary" />
        <meta property="twitter:title" content="List ongoing Anime at <?=$website_name?>" />
        <meta property="twitter:description" content="List ongoing Anime at <?=$website_name?>" />

        <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
        <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />



        <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />

        <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
        <?php require_once('../app/views/partials/advertisements/popup.html'); ?>
        <script>
                var base_url = 'https://' + document.domain + '/';
                var base_url_cdn_api = 'https://ajax.gogocdn.net/';
                var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
        </script>
        <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>
</head>

<body>
        <div class="clr"></div>
        <div id="wrapper_inside">
                <div id="wrapper">
                        <div id="wrapper_bg">
                                <?php require_once('../app/views/partials/header.php'); ?>
                                <section class="content">
                                        <section class="content_left">

                                                <div class="main_body">
                                                        <div class="anime_name anime_movies">
                                                                <i class="icongec-anime_movies i_pos"></i>
                                                                <h2>ONGOING ANIME</h2>
                                                                <div class="anime_name_pagination">
                                                                        <div class="pagination">
                                                                            <ul class='pagination-list'>
                                                                                <?php
                                                                                for ($i = 1; $i <= $totalPages; $i++) {
                                                                                    if ($i > 5 && $i != $totalPages && $i != $page) continue;
                                                                                    $active = ($i == $page) ? 'selected' : '';
                                                                                    echo "<li class='$active'><a href='?page=$i'>$i</a></li>";
                                                                                }
                                                                                ?>
                                                                            </ul>
                                                                        </div>
                                                                </div>
                                                        </div>
                                                        <div class="last_episodes">
                                                                <ul class="items">
                                                                <?php foreach($ongoingList as $ongoingA)  {
                                                                    $link = "/anime-details.php?id=" . $ongoingA['id'];
                                                                    $title = htmlspecialchars($ongoingA['title']);
                                                                    $img = htmlspecialchars($ongoingA['image_url']);
                                                                    $status = htmlspecialchars($ongoingA['status']);
                                                                ?>
                                                                        <li>
                                                                            <div class="img">
                                                                                <a href="<?=$link?>" title="<?=$title?>">
                                                                                    <img src="<?=$img?>" alt="<?=$title?>" />
                                                                                </a>
                                                                            </div>
                                                                            <p class="name"><a href="<?=$link?>" title="<?=$title?>"><?=$title?></a></p>
                                                                            <p class="released"><?=$status?></p>
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
                                                <?php require_once('../app/views/partials/sub-category.html'); ?>
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