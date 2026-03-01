<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>New Season - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
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
                                    // Assuming "New Season" means recently released anime or specific status
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
</body>
</html>