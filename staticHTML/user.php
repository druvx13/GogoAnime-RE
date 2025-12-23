<?php
/**
 * User Profile Page
 *
 * This file renders the logged-in user's profile page. It allows users to:
 * 1. View their account information (Name, Email, Member Since).
 * 2. Update their display name.
 * 3. View their list of bookmarked anime.
 *
 * @package    GogoAnime Clone
 * @subpackage StaticHTML
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../app/config/info.php');
require_once('../app/config/db.php');

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: $base_url/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
try {
    $stmt = $conn->prepare("SELECT name, email, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Handle edge case where session exists but user DB record is gone
        session_destroy();
        header("Location: $base_url/login.html");
        exit();
    }
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

$error = '';
$success = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
        try {
            $stmt = $conn->prepare("UPDATE users SET name = :name WHERE id = :id");
            $stmt->execute(['name' => $name, 'id' => $user_id]);
            $_SESSION['user_name'] = $name;
            $success = "Profile updated successfully!";
            $user['name'] = $name;
        } catch(PDOException $e) {
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Fetch Bookmarks
try {
    $bmStmt = $conn->prepare("
        SELECT a.id, a.title, a.image_url
        FROM bookmarks b
        JOIN anime a ON b.anime_id = a.id
        WHERE b.user_id = :uid
        ORDER BY b.created_at DESC
    ");
    $bmStmt->execute(['uid' => $user_id]);
    $bookmarks = $bmStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $bookmarks = [];
    error_log("Error fetching bookmarks: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">
    <title><?=$website_name?> | User Profile</title>
    <meta name="robots" content="index, follow" />
    <meta name="description" content="Manage your user profile and bookmarks.">
    <meta name="keywords" content="profile, bookmarks, account, <?=$website_name?>">
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?=$website_name?> | User Profile" />
    <meta property="og:description" content="Manage your user profile and bookmarks.">
    <meta property="og:url" content="" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="twitter:card" content="summary" />
    <meta property="twitter:title" content="<?=$website_name?> | User Profile" />
    <meta property="twitter:description" content="Manage your user profile and bookmarks." />
    <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/user_auth.css" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/user.css" />
    <?php require_once('../app/views/partials/advertisements/popup.html'); ?>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script>
        var base_url = '<?=$base_url?>/';
    </script>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js?v=6.9"></script>
    <style>
        .message { text-align: center; padding: 10px; margin-bottom: 15px; }
        .error { color: red; background: #ffe6e6; }
        .success { color: green; background: #e6ffe6; }
        .profile-form { max-width: 400px; margin: 20px auto; }
        .profile-form input[type="text"] { width: 100%; padding: 8px; margin: 5px 0; }
        .profile-form button { width: 100%; padding: 10px; background: #00a651; color: white; border: none; cursor: pointer; }
        .profile-info { margin: 20px 0; }

        /* Bookmarks Styles */
        .bookmark-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .bookmark-item {
            text-align: center;
        }
        .bookmark-item img {
            width: 100%;
            height: auto;
            border-radius: 4px;
        }
        .bookmark-item a {
            color: #fff;
            font-size: 12px;
            text-decoration: none;
            display: block;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<div class="clr"></div>
<div id="wrapper_inside">
    <div id="wrapper">
        <div id="wrapper_bg">
            <?php require('../app/views/partials/header.php'); ?>
            <section class="content">
                <section class="content_left">
                    <div class="main_body">
                        <div class="anime_name reg">
                            <i class="icongec-reg i_pos"></i>
                            <h2>User Profile</h2>
                        </div>
                        <div class="content-login">
                            <div class="form-login">
                                <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
                                <?php if($error): ?>
                                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if($success): ?>
                                    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                                <?php endif; ?>
                                <div class="profile-info">
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                <form method="post" class="profile-form">
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    <button type="submit" name="update_profile">Update Profile</button>
                                </form>
                            </div>
                        </div>

                        <!-- Display Bookmarks -->
                        <div class="main_body">
                            <div class="anime_name favorite">
                                <i class="icongec-favorite i_pos"></i>
                                <h2>My Bookmarks</h2>
                            </div>
                            <div style="padding: 20px;">
                                <?php if(empty($bookmarks)): ?>
                                    <p style="color: #ccc;">You haven't bookmarked any anime yet.</p>
                                <?php else: ?>
                                    <div class="bookmark-grid">
                                        <?php foreach($bookmarks as $bm): ?>
                                            <div class="bookmark-item">
                                                <a href="/anime-details.php?id=<?=$bm['id']?>">
                                                    <img src="<?=$bm['image_url']?>" alt="<?=htmlspecialchars($bm['title'])?>">
                                                    <?=htmlspecialchars($bm['title'])?>
                                                </a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="content_right">
                    <div class="clr"></div>
                    <div class="banner_center">
                        <div class="anime_name guide">
                            <i class="icongec-guide i_pos"></i>
                            <h2>Features</h2>
                        </div>
                        <div class="content_items">
                            Your membership includes:<br /><br />
                            - Bookmark your favorite anime<br />
                            - Get notifications for new episodes<br />
                            - Customize your viewing preferences<br />
                            - Access to member-only features<br /><br />
                        </div>
                    </div>
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
</body>
</html>
