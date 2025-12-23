<?php require_once('./app/config/info.php'); ?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

    <title>Watch anime online, English anime online - <?=$website_name?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description"
        content="Watch anime online in English. You can watch free series and movies online and English subtitle.">
    <meta name="keywords"
        content="gogoanime,watch anime, anime online, free anime, english anime, sites to watch anime">
    <meta itemprop="image" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="<?=$website_name?> | Watch anime online, English anime online HD" />
    <meta property="og:description"
        content="Watch anime online in English. You can watch free series and movies online and English subtitle.">
    <meta property="og:url" content="" />
    <meta property="og:image" content="<?=$base_url?>/assets/img/logo.png" />
    <meta property="og:image:secure_url" content="<?=$base_url?>/assets/img/logo.png" />

    <meta property="twitter:card" content="summary" />
    <meta property="twitter:title" content="<?=$website_name?> | Watch anime online, English anime online HD" />
    <meta property="twitter:description"
        content="Watch anime online in English. You can watch free series and movies online and English subtitle." />

    <link rel="canonical" href="<?=$base_url?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?>" />



    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />

    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script>
        var base_url = 'http://' + document.domain + '/';
        var base_url_cdn_api = 'https://ajax.gogocdn.net/';
        var api_anclytic = 'https://ajax.gogocdn.net/anclytic-ajax.html';
    </script>
    <script type="text/javascript" src="https://cdn.gogocdn.net/files/gogo/js/main.js"></script>

    <?php require_once('./app/views/partials/advertisements/popup.html'); ?>
    
</head>


  <body>
    <div class="clr"></div>
    <div id="wrapper_inside">
      <div id="wrapper">
        <div id="wrapper_bg">
          <header>
  <div class="menu_top_link">

    <div class="link_face intro">
      <a class="btn twitter" href="https://twitter.com/anime_around" target="_blank" data-url=""></a>
      <a class="btn reddit" href="https://www.reddit.com/r/AroundAnimeTV/" target="_blank" data-url=""></a>
      <a class="btn facebook" href="https://www.facebook.com/groups/409309663623039" target="_blank"></a>
      <a class="btn discord" style="margin-right:5px;" href="https://discord.gg/kyVfcGuCCQ" target="_blank" data-url=""></a>
      <a class="btn telegram" style="margin-right:5px;" href="https://t.me/joinchat/W4lYQ-RGOQ05MmI9" target="_blank" data-url=""></a>
    </div>
                    
    <div class="submenu_intro">
	    <a href="https://gogotaku.info/login.html" target="_blank">Request</a>
	    <span>|</span>
      <a href="/contact-us.html">Contact us</a>
      <span>|</span>
      <a href="https://gogotaku.info" target="_blank">Gogotaku</a>
    </div>          
  </div>
  <div class="clr"></div>

  <!-- banner -->
  <section class="headnav">
    <div style="text-align:center;margin-bottom:20px;">
      <a href="/home"><img src="<?=$base_url?>/assets/img/logo.svg" class="l-logo" alt="gogoanime - Watch Anime Online" /></a>
    </div>
    <div style="width:100%;font-family: Tahoma, Geneva, sans-serif;font-size: 14px;text-transform: uppercase;text-align:center;">
      <!-- menu top -->
      <nav>
        <ul>
          <li style="display:inline-block;margin:0px 18px;">
            <a style="display: block;color: #fff;padding: 0 0 14px 0px;transition: all 0s linear 0s;webkit-transition: all 0s linear 0s;moz-transition: all 0s linear 0s;o-transition: all 0s linear 0s;" href="/home" title="Home" class="home">Home</a>
          </li>
          <li style="display:inline-block;margin:0px 18px;">
            <a style="display: block;color: #fff;padding: 0 0 14px 0px;transition: all 0s linear 0s;webkit-transition: all 0s linear 0s;moz-transition: all 0s linear 0s;o-transition: all 0s linear 0s;" href="/anime-list" title="Anime list" class="list">Anime list</a>
          </li>
          <li style="display:inline-block;margin:0px 18px;">
            <a style="display: block;color: #fff;padding: 0 0 14px 0px;transition: all 0s linear 0s;webkit-transition: all 0s linear 0s;moz-transition: all 0s linear 0s;o-transition: all 0s linear 0s;" href="/new-season" title="New season" class="series">New season</a>
          </li>
          <li style="display:inline-block;margin:0px 18px;">
            <a style="display: block;color: #fff;padding: 0 0 14px 0px;transition: all 0s linear 0s;webkit-transition: all 0s linear 0s;moz-transition: all 0s linear 0s;o-transition: all 0s linear 0s;" href="/anime-movies" title="Movies" class="movie">Movies</a>
          </li>
          <li style="display:inline-block;margin:0px 18px;">
            <a style="display: block;color: #fff;padding: 0 0 14px 0px;transition: all 0s linear 0s;webkit-transition: all 0s linear 0s;moz-transition: all 0s linear 0s;o-transition: all 0s linear 0s;" href="/popular" title="Popular" class="popular">Popular</a>
          </li>
        </ul>	
      </nav>
      <!-- /menu top -->
    </div>
    <div class="form" style="padding-bottom:20px;width:100%;">
        <form style="max-width:600px;margin:0 auto;position:relative;text-align:left;" onsubmit="" id="search-form" action="<?=$base_url?>/search" method="get">
          <div class="row">
            <input placeholder="search" name="keyword" id="keyword" type="text" value="" autocomplete="off">            
            <input class="btngui" value="" type="button" name="" onclick="do_search();">
            <input id="key_pres" name="key_pres" value="" type="hidden" />
            <input id="link_alias" name="link_alias" value="" type="hidden" />
            <input id="keyword_search_replace" name="keyword_search_replace" value="" type="hidden" />
          </div>
          <div class="hide_search hide"><i class="icongec-muiten"></i></div>
          <div id="header_search_autocomplete"></div>
          <div class="loader"></div>
        </form>           
        <div class="clr"></div>
        <div class="search-iph"><a href="javascript:void(0)"><i class="icongec-search-mb"></i></a></div>
      </div>
      <div style="text-align:center;color:#00a651;text-transform:none;">Make sure to bookmark the domain <a style="color:#ffc119;" href="https://gogotaku.info/" target="_blank">gogotaku.info</a> to stay updated.</div>
  </section>
  <!-- /banner -->
</header>


  <div class="main_body">
    <div style="color:#FFF;padding:18px;">
      <h1 style="text-transform:uppercase;font-size:20px;"><?=$website_name?> - Anime Streaming Platform</h1>
      <p>The <?=$website_name?> platform provides a user interface for accessing and viewing anime content. This service is provided on an "as-is" and "as available" basis, without warranties of any kind.</p>
      <p>By accessing this website, you acknowledge and agree to our <a href="/terms.html" style="color:#ffc119;">Terms of Service</a> and <a href="/privacy.html" style="color:#ffc119;">Privacy Policy</a>.</p>

      <h6 style="font-size:18px;">Platform Scope & Content Disclaimer</h6>
      <p><?=$website_name?> functions as a content indexing and embedded video playback service. All video content is hosted by third-party providers. <?=$website_name?> does not host, upload, or control the video content displayed. We expressly disclaim all liability regarding the availability, accuracy, or legality of content hosted on third-party servers.</p>

      <h6 style="font-size:18px;">Service Availability & Limitation of Liability</h6>
      <p>While we aim to maintain platform functionality, we do not guarantee uninterrupted access or error-free operation. Users agree that the use of this service is at their sole risk. To the fullest extent permitted by law, <?=$website_name?> and its operators shall not be liable for any direct, indirect, incidental, or consequential damages arising from the use or inability to use the service.</p>

      <h6 style="font-size:18px;">User Responsibility</h6>
      <p>Users are responsible for ensuring their use of this platform complies with all applicable local laws and regulations. Any interaction with third-party advertisements or external links is solely between the user and the third party.</p>

      <h6 style="font-size:18px;">Copyright & IP Compliance</h6>
      <p><?=$website_name?> respects intellectual property rights. If you believe your copyright has been infringed by content accessible through our platform, please contact us via our designated contact channels for review.</p>

      <p>For full legal details, please refer to our <a href="/terms.html" style="color:#ffc119;">Terms of Service</a>.</p>
    </div>
  </div>

  <div style="text-align:center;margin:20px 0;">
    <a href="<?=$base_url?>/home" style="background:#000;color:#ffc119;font-size:18px;border:1px solid #fff;padding:10px 25px;border-radius:20px;">GO TO HOMEPAGE</a>
  </div>
                                                
     
          <div class="clr"></div>
<footer>
  <div class="menu_bottom">
    <a href="/about-us.html"><h3>About Us</h3></a>
    <a href="/contact-us.html"><h3>Contact Us</h3></a>
    <a href="/privacy.html"><h3>Privacy</h3></a>
    <a href="/terms.html"><h3>Terms of Service</h3></a>
  </div>
  <div class="croll">
    <div class="big"><i class="icongec-backtop"></i></div>
    <div class="small"><i class="icongec-backtop_mb"></i></div>
  </div>
</footer>
        </div>
      </div>
    </div>
    <div id="off_light"></div>
    <div class="clr"></div>
    <div class="mask"></div>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/combo.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/video.js"></script>
    <script type="text/javascript" src="<?=$base_url?>/assets/js/files/jquery.tinyscrollbar.min.js"></script>
    <?php include('./app/views/partials/footer.php')?>
    <script>
      if(document.getElementById('scrollbar2')){
        $('#scrollbar2').tinyscrollbar();
      }
    </script>
  </body>
</html>