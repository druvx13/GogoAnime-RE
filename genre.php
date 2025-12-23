<?php
/**
 * Genre Page
 *
 * This page displays a list of anime belonging to a specific genre.
 * The genre is identified by the 'slug' GET parameter.
 *
 * @package    GogoAnime Clone
 * @subpackage Root
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once('./app/config/info.php');
require_once('./app/config/db.php');

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Fetch Genre Info
$stmt = $conn->prepare("SELECT * FROM genres WHERE slug = :slug");
$stmt->execute(['slug' => $slug]);
$genre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$genre) {
    // Simple 404 or error handling
    header("HTTP/1.0 404 Not Found");
    echo "Genre not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">
    <title><?=htmlspecialchars($genre['name'])?> Anime - <?=$website_name?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="Watch <?=htmlspecialchars($genre['name'])?> anime on <?=$website_name?>.">
    <meta name="keywords" content="<?=htmlspecialchars($genre['name'])?> anime, <?=$website_name?>">

    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js"></script>
</head>
<body>
    <div id="wrapper_inside">
        <div id="wrapper">
            <div id="wrapper_bg">
                <?php require_once('./app/views/partials/header.php'); ?>
                <section class="content">
                    <section class="content_left">
                        <div class="main_body">
                            <div class="anime_name genre">
                                <i class="icongec-genre i_pos"></i>
                                <h2><?=htmlspecialchars($genre['name'])?> Anime</h2>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                    <?php
                                    try {
                                        // Fetch anime in this genre
                                        $animeStmt = $conn->prepare("
                                            SELECT a.*
                                            FROM anime a
                                            JOIN anime_genre ag ON a.id = ag.anime_id
                                            WHERE ag.genre_id = :gid
                                            ORDER BY a.created_at DESC
                                            LIMIT 20
                                        ");
                                        $animeStmt->execute(['gid' => $genre['id']]);
                                        $animes = $animeStmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach($animes as $anime) {
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

                                        if (empty($animes)) {
                                            echo "<p class='text-center'>No anime found in this genre.</p>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<p>Error loading genre content.</p>";
                                        error_log("Genre query error: " . $e->getMessage());
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </section>
                    <section class="content_right">
                        <?php require_once('./app/views/partials/sub-category.html'); ?>
                    </section>
                </section>
                <?php include('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
</body>
</html>
