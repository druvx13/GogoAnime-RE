<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$stmt = $conn->prepare("SELECT * FROM genres WHERE slug = :slug");
$stmt->execute(['slug' => $slug]);
$genre = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$genre) {
    die("Genre not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=$genre['name']?> Anime - <?=$website_name?></title>
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
                            <div class="anime_name genre">
                                <i class="icongec-genre i_pos"></i>
                                <h2><?=$genre['name']?> Anime</h2>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                    <?php
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
                                        echo "<li>
                                            <div class='img'>
                                                <a href='$link' title='{$anime['title']}'>
                                                    <img src='{$anime['image_url']}' alt='{$anime['title']}' />
                                                </a>
                                            </div>
                                            <p class='name'><a href='$link' title='{$anime['title']}'>{$anime['title']}</a></p>
                                            <p class='released'>Released: {$anime['release_date']}</p>
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
