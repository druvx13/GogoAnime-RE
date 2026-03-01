<?php
session_start();
require_once('./app/config/info.php');
require_once('./app/config/db.php');
require_once('./app/helpers/pagination_helper.php'); // Reuse helper

if (!isset($_SESSION['user_id'])) {
    header("Location: $base_url/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Count Bookmarks
$countStmt = $conn->prepare("SELECT COUNT(*) FROM bookmarks WHERE user_id = :uid");
$countStmt->execute(['uid' => $user_id]);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Fetch Bookmarks
$stmt = $conn->prepare("
    SELECT a.id, a.title, a.image_url, a.release_date
    FROM bookmarks b
    JOIN anime a ON b.anime_id = a.id
    WHERE b.user_id = :uid
    ORDER BY b.created_at DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$bookmarks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">
    <title>My Bookmarks - <?=$website_name?></title>
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/responsive.css" />
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script>
        var base_url = 'https://' + document.domain + '/';
        var base_url_cdn_api = 'https://ajax.gogocdn.net/';
        var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
    </script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>
    <?php require_once('./app/views/partials/advertisements/popup.html'); ?>
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
                            <div class="anime_name favorite">
                                <i class="icongec-favorite i_pos"></i>
                                <h2>My Bookmarks</h2>
                                <div class="anime_name_pagination">
                                    <div class="pagination">
                                        <ul class='pagination-list'>
                                            <?php echo PaginationHelper::render($page, $totalPages); ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="last_episodes">
                                <ul class="items">
                                <?php if (!empty($bookmarks)): ?>
                                    <?php foreach ($bookmarks as $anime): ?>
                                        <?php
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
                                            <p class="released">Released: <?=$date?></p>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="padding: 20px;">You haven't bookmarked any anime yet.</p>
                                <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </section>
                    <section class="content_right">
                        <?php require_once('./app/views/partials/sub-category.html'); ?>
                    </section>
                </section>
                <div class="clr"></div>
                <?php include('./app/views/partials/footer.php'); ?>
            </div>
        </div>
    </div>
    <div id="off_light"></div>
    <div class="clr"></div>
    <div class="mask"></div>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/combo.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/video.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jquery.tinyscrollbar.min.js"></script>
</body>
</html>
