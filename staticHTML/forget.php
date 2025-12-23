<?php
/**
 * Forget Password Page
 *
 * This file renders the "Forget Password" page.
 * Currently, it serves as a template and does not contain active mail recovery logic.
 *
 * @package    GogoAnime Clone
 * @subpackage StaticHTML
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once('../app/config/info.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

        <title><?=$website_name?> | Forget</title>

        <meta name="robots" content="index, follow" />
        <meta name="description" content="Recover your account password.">
        <meta name="keywords" content="forget password, recover account, <?=$website_name?>">
        <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="og:site_name" content="<?=$website_name?>" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="<?=$website_name?> | Forget" />
        <meta property="og:description" content="Recover your account password.">
        <meta property="og:url" content="" />
        <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
        <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="twitter:card" content="summary" />
        <meta property="twitter:title" content="<?=$website_name?> | Forget" />
        <meta property="twitter:description" content="Recover your account password." />

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
    .myptag {
        color: red;
        text-align: center;
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
                    <h2>Forget password</h2>
                </div>
        <div class="content-login">
                <div class="form-login">
                <h1>Forget password</h1>
                    <p class="myptag">* This feature is currently disabled.<br>Please contact support for assistance.</p>
                    
                    <form method='post' action="">
                        <input type='email' name='email'  placeholder='Email'  required='true'  value=''>       
                        <button type='submit' onclick="alert('Feature disabled'); return false;">Request Mail</button>
                    </form>
                    <a class="link-forget" href="/login.html">Sign in</a>
                    <a class="link-sign" href="/register.html">Create new account</a>

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
