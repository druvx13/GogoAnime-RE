<?php
/**
 * Login Page
 *
 * This file handles user authentication. It accepts email and password credentials,
 * verifies them against the database, and establishes a user session.
 * It also supports "Remember Me" functionality via cookies.
 *
 * @package    GogoAnime Clone
 * @subpackage StaticHTML
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

// Start the session at the very beginning if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../app/config/info.php');
require_once('../app/config/db.php');

// If user is already logged in, redirect to user page
if (isset($_SESSION['user_id'])) {
    header("Location: $base_url/user.html");
    exit();
}

// Ensure no output is sent before headers
ob_start();

$error = '';
$success = '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? 1 : 0;

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            
            // Handle Remember Me
            if ($remember) {
                $token = bin2hex(random_bytes(16));
                $stmt = $conn->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
                $stmt->execute(['token' => $token, 'id' => $user['id']]);
                // Cookie expires in 30 days
                setcookie('remember_me', $token, time() + (86400 * 30), "/");
            }
            
            // Clear output buffer and redirect
            ob_end_clean();
            header("Location: $base_url/user.html");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } catch(PDOException $e) {
        $error = "An error occurred during login. Please try again.";
        // Log detailed error for admin
        error_log("Login error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">
    <title><?=$website_name?> | Login</title>
    <meta name="robots" content="index, follow" />
    <meta name="description" content="Login to your account.">
    <meta name="keywords" content="login, sign in, <?=$website_name?>">
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?=$website_name?> | Login" />
    <meta property="og:description" content="Login to your account.">
    <meta property="og:url" content="" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="twitter:card" content="summary" />
    <meta property="twitter:title" content="<?=$website_name?> | Login" />
    <meta property="twitter:description" content="Login to your account." />
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
    <style>
        .message { text-align: center; padding: 10px; margin-bottom: 15px; }
        .error { color: red; background: #ffe6e6; }
        .success { color: green; background: #e6ffe6; }
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
                        <div class="anime_name login">
                            <i class="icongec-logins i_pos"></i>
                            <h2>Login</h2>
                        </div>
                        <div class="content-login">
                            <div class="form-login">
                                <h1>Log in to Gogoanime</h1>
                                <?php if($error): ?>
                                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                                <?php endif; ?>
                                <?php if($success): ?>
                                    <div class="message success"><?php echo htmlspecialchars($success); ?></div>
                                <?php endif; ?>
                                <a href="javascript:void(0)" class="btn-google" style="opacity: 0.5; cursor: not-allowed;">
                                    <span><img src="https://gogoanime3.co/img/google.png" alt="google" /></span>
                                    Log in with Google (Disabled)
                                </a>
                                <form method="post" action="<?=$base_url?>/login.html">
                                    <input type="email" name="email" placeholder="Email" required="required" value="">
                                    <input type="password" name="password" placeholder="Password" required="required">
                                    <div style="margin-top: 10px; color: #ccc;">
                                        <input type="checkbox" name="remember" value="1" style="width: auto;"> Remember me
                                    </div>
                                    <button type="submit">Sign in</button>
                                </form>
                                <a class="link-forget" href="/forget.html">Forgot password?</a>
                                <a class="link-sign" href="/register.html">Sign up</a>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="content_right">
                    <div class="clr"></div>
                    <div class="banner_center">
                        <div class="anime_name guide">
                            <i class="icongec-guide i_pos"></i>
                            <h2>Guidelines</h2>
                        </div>
                        <div class="content_items">
                            As a member you can:<br /><br />
                            - Bookmark anime: This feature will auto notify you when the anime have new episode.<br />
                            - Various options: Such as Default HD, disable auto play, etc….<br />
                            - Updated: You will be updated all the extra feature automatically when it publish.<br /><br />
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
<?php
ob_end_flush();
?>
