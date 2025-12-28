<?php
declare(strict_types=1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Optional: Enable error reporting during development (remove in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
?>

<header>
    <div class="menu_top_link">
        <div class="user_auth">
            <ul class="auth">
                <li class="user">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <i class="icongec-login"></i>
                        Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?> |
                        <a href="/user.html">Profile</a> |
                        <a href="/bookmark.php"><img src="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/assets/img/bookmart-manage.png" alt="Bookmarks" style="vertical-align: middle;"></a> |
                        <a href="/app/controllers/logout.php">Logout</a>
                    <?php else: ?>
                        <i class="icongec-login"></i>
                        <a href="/login.html" title="login">Login</a> <a class="fix">|</a>
                        <a href="/register.html" title="Sign up" class="reg">Sign up</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
        <div class="link_face intro">
            <a class="btn twitter hidden-phone" href="https://twitter.com/anime_around" target="_blank" data-url=""></a>
            <a class="btn reddit hidden-phone" href="https://www.reddit.com/r/AroundAnimeTV/" target="_blank" data-url=""></a>
            <a class="btn facebook hidden-phone" href="https://www.facebook.com/groups/409309663623039" target="_blank"></a>
            <a class="btn discord hidden-phone" style="margin-right:5px;" href="https://discord.gg/gogo" target="_blank" data-url=""></a>
            <a class="btn telegram hidden-phone" style="margin-right:5px;" href="https://t.me/joinchat/W4lYQ-RGOQ05MmI9" target="_blank" data-url=""></a>
        </div>
        <div class="submenu_intro">
            <a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/request">Request</a>
            <span>|</span>
            <a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/contact-us.html">Contact us</a>
            <span>|</span>
            <a href="https://gogotaku.info" target="_blank">Gogotaku</a>
        </div>
    </div>
    <div class="clr"></div>

    <!-- banner -->
    <section class="headnav">
        <div class="page_menu_items show">
            <a href="javascript:void(0)" class="menu_mobile" onclick="$('.menu_top').toggle();">
                <i class="icongec-menu-show"></i>
            </a>
        </div>
        <div class="headnav_left">
            <a href="/"><img src="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/assets/img/logo.png" class="logo show ads-evt" alt="gogoanime - Watch Anime Online" /></a>
        </div>
        <div class="headnav_menu">
            <nav class="menu_top">
                <ul>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="show-mobile"><a href="/user.html">Profile</a></li>
                        <li class="show-mobile"><a href="/bookmark.php" style="background-image: url('<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/assets/img/bookmart-manage.png'); background-position: 16px center; background-repeat: no-repeat; background-size: 20px;">Bookmarks</a></li>
                        <li class="show-mobile"><a href="/app/controllers/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li class="show-mobile"><a href="/login.html">Login</a></li>
                        <li class="show-mobile"><a href="/register.html">Sign up</a></li>
                    <?php endif; ?>
                    <li class="home"><a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/home" title="Home" class="home ads-evt">Home</a></li>
                    <li class="list"><a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/anime-list" title="Anime list" class="list ads-evt">Anime list</a></li>
                    <li class="seri"><a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/new-season" title="New season" class="series ads-evt">New season</a></li>
                    <li class="movies"><a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/anime-movies" title="Movies" class="movie ads-evt">Movies</a></li>
                    <li class="movies"><a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/popular" title="Popular" class="popular ads-evt">Popular</a></li>
                    <li class="movies show_mobis">
                        <a href="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/request" title="Request" class="popular online">Request</a>
                    </li>

                    <li class="movie genre hide">
                        <a href="javascript:void(0)" class="genre">Genre</a>
                        <ul>
                            <?php
                            // Determine the path to db.php.
                            // If this header is included from a file in root (most cases), __DIR__ is app/views/partials
                            // So we need to go up to app/config/db.php
                            $dbPath = __DIR__ . '/../../config/db.php';
                            if (file_exists($dbPath)) {
                                require_once $dbPath;
                            } else {
                                // Fallback: try finding it relative to where the script is executed if needed, or assume it's already included
                                // But usually require_once is safe if already included.
                            }

                            if (isset($conn)) {
                                try {
                                    $genreStmt = $conn->query("SELECT name, slug FROM genres ORDER BY name ASC");
                                    $genres = $genreStmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($genres as $genre) {
                                        echo '<li class="">';
                                        echo '<a href="' . htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8') . '/genre/' . htmlspecialchars($genre['slug'], ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($genre['name'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($genre['name'], ENT_QUOTES, 'UTF-8') . '</a>';
                                        echo '</li>';
                                    }
                                } catch (PDOException $e) {
                                    // Silently fail or log error
                                    error_log("Error fetching genres: " . $e->getMessage());
                                }
                            }
                            ?>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="headnav_right">
            <div class="form">
                <form onsubmit="return true;" id="search-form" action="<?= htmlspecialchars($base_url ?? '', ENT_QUOTES, 'UTF-8'); ?>/search" method="get">
                    <div class="row">
                        <input placeholder="search" name="keyword" id="keyword" type="text" value="" autocomplete="off">
                        <input class="btngui" value="" type="submit" name="">
                    </div>
                    <div class="hide_search hide" onclick="$('#search-form').hide(); $('.search-iph').show();"><i class="icongec-muiten"></i></div>
                </form>
                <div class="clr"></div>
                <div class="search-iph"><a href="javascript:void(0)" onclick="$('#search-form').show(); $(this).parent().hide();"><i class="icongec-search-mb"></i></a></div>
            </div>
            <div class="clr"></div>
        </div>
    </section>

    <style>
        @media only screen and (max-width: 768px) {
            .show-mobile { display: block !important; border-bottom: 1px solid #333; }
            .show-mobile a { display: block; padding: 10px 15px; color: #fff; }
        }
        @media only screen and (min-width: 769px) {
            .show-mobile { display: none !important; }
        }
    </style>
</header>
