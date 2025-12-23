<?php
require_once('./app/config/info.php');
require_once('./app/config/db.php');

$search = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>Search Result for "<?=htmlspecialchars($search)?>" - <?=$website_name?></title>
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
                            <div class="anime_name search-result">
                                <i class="icongec-search-result i_pos"></i>
                                <h2>Search Result for "<?=htmlspecialchars($search)?>"</h2>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                    <?php
                                    if (!empty($search)) {
                                        $stmt = $conn->prepare("SELECT * FROM anime WHERE title LIKE :keyword ORDER BY title ASC");
                                        $stmt->execute(['keyword' => "%$search%"]);
                                        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach($results as $anime) {
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
                                        if (empty($results)) {
                                            echo "<p>No results found.</p>";
                                        }
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