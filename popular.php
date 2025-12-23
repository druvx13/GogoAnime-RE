<?php
/**
 * Popular Anime Page
 *
 * This page displays a list of anime sorted by popularity (views).
 * It queries the database for the most viewed anime entries.
 *
 * @package    GogoAnime Clone
 * @subpackage Root
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once('./app/config/info.php');
require_once('./app/config/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">
    <title>Popular Anime - <?=$website_name?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="Browse the most popular anime on <?=$website_name?>.">
    <meta name="keywords" content="popular anime, top rated anime, <?=$website_name?>">

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
                            <div class="anime_name popular">
                                <i class="icongec-popular i_pos"></i>
                                <h2>Popular Anime</h2>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                    <?php
                                    try {
                                        // Fetch popular anime (assumes 'views' column exists, otherwise fallback to id)
                                        // Using try-catch to be safe if 'views' column is missing in schema.
                                        $stmt = $conn->query("SELECT * FROM anime ORDER BY views DESC LIMIT 20");
                                        $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach($popular as $anime) {
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

                                        if (empty($popular)) {
                                            echo "<p class='text-center'>No popular anime found.</p>";
                                        }
                                    } catch (PDOException $e) {
                                        // Fallback if views column doesn't exist
                                         $stmt = $conn->query("SELECT * FROM anime ORDER BY id ASC LIMIT 20");
                                         $popular = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                         foreach($popular as $anime) {
                                            $link = "/anime-details.php?id=" . $anime['id'];
                                            $title = htmlspecialchars($anime['title']);
                                            $img = htmlspecialchars($anime['image_url']);
                                            echo "<li><div class='img'><a href='$link'><img src='$img'></a></div><p class='name'><a href='$link'>$title</a></p></li>";
                                         }
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
