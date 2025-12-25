<?php require_once('../app/config/info.php'); ?>
<!DOCTYPE html>
<html lang="en-US">

<head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

        <title><?=$website_name?> | About Us</title>

        <meta name="robots" content="index, follow" />
        <meta name="description" content="Legal and operational information about <?=$website_name?>.">
        <meta name="keywords" content="about us, legal, disclaimer, <?=$website_name?>">
        <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="og:site_name" content="<?=$website_name?>" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="<?=$website_name?> | About Us" />
        <meta property="og:description" content="Legal and operational information about <?=$website_name?>.">
        <meta property="og:url" content="" />
        <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
        <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

        <meta property="twitter:card" content="summary" />
        <meta property="twitter:title" content="<?=$website_name?> | About Us" />
        <meta property="twitter:description" content="Legal and operational information about <?=$website_name?>." />

        <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
        <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />

        <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
        <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
        <script type="text/javascript" src="<?=$base_url?>/assets/js/main.js"></script>
</head>

<body>
        <div class="clr"></div>
        <div id="wrapper_inside">
                <div id="wrapper">
                        <div id="wrapper_bg">
                           <?php require('../app/views/partials/header.php'); ?>
                                <section class="content">
                                        <section class="content_left" style="width: 100%;">

                                                <div class="main_body">
                                                        <div class="anime_name ongoing">
                                                                <div class="anime_name_img_ongoing"></div>
                                                                <h2>About Us</h2>
                                                        </div>
                                                        <div class="p_content" style="padding: 20px; line-height: 1.6; color: #aaa;">
                                                            <p style="margin-bottom: 15px;">
                                                                <strong><?=$website_name?></strong> operates as a specialized content indexing and discovery platform.
                                                                Our mission is to provide an efficient, organized, and user-friendly interface for locating publicly available anime content across the web.
                                                            </p>

                                                            <h3 style="color: #ffc119; margin-top: 20px; margin-bottom: 10px; font-size: 16px;">Operational Model</h3>
                                                            <p style="margin-bottom: 15px;">
                                                                We function strictly as a search engine and directory.
                                                                <strong><?=$website_name?> does not host, upload, or store any video files on its servers.</strong>
                                                                All content displayed is provided by non-affiliated third parties.
                                                                Our system automatically crawls and indexes content found on public domains, similar to how major search engines operate.
                                                            </p>

                                                            <h3 style="color: #ffc119; margin-top: 20px; margin-bottom: 10px; font-size: 16px;">Commitment to Rights</h3>
                                                            <p style="margin-bottom: 15px;">
                                                                We respect the intellectual property rights of others and expect our users to do the same.
                                                                While we do not control the underlying content, we are committed to maintaining a compliant platform.
                                                                Rights holders who believe their content has been improperly indexed may contact us for removal in accordance with our copyright policies.
                                                            </p>

                                                            <h3 style="color: #ffc119; margin-top: 20px; margin-bottom: 10px; font-size: 16px;">User Privacy & Security</h3>
                                                            <p style="margin-bottom: 15px;">
                                                                We prioritize user privacy by minimizing data collection and ensuring transparency in our operations.
                                                                Our platform is designed to be used without requiring extensive personal information.
                                                                Please review our <a href="/privacy.html" style="color: #ffc119;">Privacy Policy</a> and <a href="/terms.html" style="color: #ffc119;">Terms of Service</a> to understand your rights and obligations while using this service.
                                                            </p>

                                                            <p style="margin-top: 30px; font-size: 13px; color: #888; border-top: 1px solid #333; padding-top: 10px;">
                                                                For official inquiries, please refer to our <a href="/contact-us.html" style="color: #888;">Contact page</a>.
                                                            </p>
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
</body>

</html>
