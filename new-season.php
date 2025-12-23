<?php
/**
 * New Season Page
 *
 * This page displays a list of anime from the current or upcoming season.
 * It queries the database for anime with recent release dates.
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
    <title>New Season - <?=$website_name?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="Watch the latest anime seasons on <?=$website_name?>.">
    <meta name="keywords" content="new season, anime, latest anime, <?=$website_name?>">

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
                            <div class="anime_name new_season">
                                <i class="icongec-new_season i_pos"></i>
                                <h2>New Season</h2>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                    <?php
                                    try {
                                        // Fetch latest anime based on release date or ID
                                        // Ideally, this should filter by current date/season logic.
                                        // For this implementation, we take the most recently added anime.
                                        $stmt = $conn->query("SELECT * FROM anime ORDER BY release_date DESC LIMIT 20");
                                        $newSeason = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach($newSeason as $anime) {
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

                                        if (empty($newSeason)) {
                                            echo "<p class='text-center'>No new season anime found.</p>";
                                        }
                                    } catch (PDOException $e) {
                                        echo "<p>Error loading content.</p>";
                                        error_log("New Season query error: " . $e->getMessage());
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
