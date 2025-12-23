<?php
/**
 * Genre Filter Page
 *
 * This page displays a paginated list of anime belonging to a specific genre.
 * It parses the URL slug to identify the genre and queries the database accordingly.
 *
 * @package    GogoAnime Clone
 * @subpackage Genre
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once('../app/config/info.php');
require_once('../app/config/db.php');

// Parse URL to get genre slug
$parts = parse_url($_SERVER['REQUEST_URI']);
$page_url = explode('/', $parts['path']);
$id = $page_url[count($page_url)-1];

// Normalize slug from URL
$genreSlug = str_replace("+", "-", $id);
$genreName = ucwords(str_replace(["+", "-"], " ", $id));

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 1. Get Genre ID from Slug
$genreStmt = $conn->prepare("SELECT id, name FROM genres WHERE slug = :slug");
$genreStmt->execute(['slug' => $genreSlug]);
$genreData = $genreStmt->fetch(PDO::FETCH_ASSOC);

if (!$genreData) {
    // If genre not found by slug, fallback to showing nothing or a "Not Found" message
    $genreId = 0;
    $totalAnime = 0;
    $totalPages = 0;
    $animeList = [];
} else {
    $genreId = $genreData['id'];
    $genreName = $genreData['name']; // Use verified name from DB

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
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

    <title>List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?>">
    <meta name="keywords" content="List genre Anime, Anime Movies">
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?>" />
    <meta property="og:description" content="List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?>">
    <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="twitter:card" content="summary" />
    <meta property="twitter:title" content="List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?>" />
    <meta property="twitter:description" content="List genre <?=htmlspecialchars($genreName)?> at <?=$website_name?>" />

    <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />

    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css?v=7.1" />

    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script>
        var base_url = '<?=$base_url?>/';
        var base_url_cdn_api = 'https://ajax.gogocdn.net/';
        var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
    </script>
    <?php require_once('../app/views/partials/advertisements/popup.html'); ?>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js?v=7.1"></script>
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
                                <h2>Genre: <?=htmlspecialchars($genreName)?></h2>
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
                                <?php
                                if (!empty($animeList)) {
                                    foreach($animeList as $anime)  {
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
                                        <p class="name"><a href="<?=$link?>" title="<?=$title?>"><?=$title?></a></p>
                                        <p class="released"><?=$date?></p>
                                    </li>
                                <?php
                                    }
                                } else {
                                    echo "<li><p>No anime found for this genre.</p></li>";
                                }
                                ?>
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
                                                <?php require_once('../app/views/partials/recentRelease.php'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="clr"></div>
                        <style type="text/css">
                            #scrollbar2 .viewport {
                                height: 1000px !important;
                            }
                        </style>
                        <?php require_once('../app/views/partials/sub-category.html');?>
                    </section>
                </section>
                <div class="clr"></div>
                <?php include('../app/views/partials/footer.php')?>
            </div>
        </div>
    </div>
    <div id="off_light"></div>
    <div class="clr"></div>
    <div class="mask"></div>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/combo.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/video.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jquery.tinyscrollbar.min.js"></script>
    <script>
        if (document.getElementById('scrollbar2')) {
            $('#scrollbar2').tinyscrollbar();
        }
    </script>
</body>
</html>
