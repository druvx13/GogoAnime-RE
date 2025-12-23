<?php 
require_once('./app/config/info.php');
require_once('./app/config/db.php');

// --- NEW SEARCH LOGIC (Same as animelist.php and streaming.php) ---
$searchQuery = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$searchResults = [];
$hasSearched = false;

if ($searchQuery !== '') {
    $hasSearched = true;
    $searchTerm = '%' . $searchQuery . '%';
    $searchStmt = $conn->prepare("SELECT id, title FROM anime WHERE title LIKE :title ORDER BY title ASC");
    $searchStmt->bindValue(':title', $searchTerm, PDO::PARAM_STR);
    $searchStmt->execute();
    $searchResults = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
}
// --- END NEW SEARCH LOGIC ---

// --- ORIGINAL ANIME DETAILS LOGIC (Only runs if not searching) ---
if (!$hasSearched) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $stmt = $conn->prepare("SELECT * FROM anime WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$anime) {
        die("Anime not found.");
    }
    $stmt = $conn->prepare("SELECT * FROM episodes WHERE anime_id = :anime_id ORDER BY episode_number ASC");
    $stmt->execute(['anime_id' => $id]);
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Map DB fields to template variables to minimize change
    $fetchDetails = [
        'name' => htmlspecialchars($anime['title']),
        'synopsis' => htmlspecialchars($anime['synopsis']),
        'imageUrl' => htmlspecialchars($anime['image_url']),
        'type' => htmlspecialchars($anime['type']),
        'released' => htmlspecialchars($anime['release_date']),
        'status' => htmlspecialchars($anime['status']),
        'othername' => '', // Add this column if needed in future
        'genres' => 'Action, Adventure' // Placeholder or fetch from related table
    ];
}
// --- END ORIGINAL LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico" />

  <!-- Updated Title based on search -->
  <title><?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "$fetchDetails[name] at $website_name"; } ?></title>

  <meta name="robots" content="index, follow" />
  <meta name="description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo substr($fetchDetails['synopsis'],0, 150) . ' ... at ' . $website_name; } ?>" />
  <meta name="keywords" content="<?php if ($hasSearched) { echo "Search, $searchQuery, $website_name, Anime"; } else { echo "$fetchDetails[name], $fetchDetails[othername]"; } ?>" />

  <?php if (!$hasSearched): ?>
  <meta itemprop="image" content="<?=$fetchDetails['imageUrl']?>" />
  <meta property="og:site_name" content="<?=$website_name?>" />
  <meta property="og:locale" content="en_US" />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="<?=$fetchDetails['name']?> at <?=$website_name?>" />
  <meta property="og:description" content="<?=substr($fetchDetails['synopsis'],0, 150)?> ... at <?=$website_name?>"">
  <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <meta property="og:image" content="<?=$fetchDetails['imageUrl']?>" />
  <meta property="og:image:secure_url" content="<?=$fetchDetails['imageUrl']?>" />
  <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <?php endif; ?>

  <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
  <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
  <script>
        var base_url = 'https://' . document.domain . '/';
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
        <?php require_once('./app/views/partials/header.php'); ?>
        <section class="content">
          <section class="content_left">
            <?php if ($hasSearched): ?>
            <!-- Display Search Results -->
            <div class="main_body">
                <div class="anime_name anime_list"> <!-- Reusing anime_list class for consistency -->
                    <i class="icongec-anime_list i_pos"></i> <!-- Reusing icon for consistency -->
                    <h2>Search Results for '<?=$searchQuery?>'</h2>
                </div>
                <div class="anime_list_body">
                    <ul class="listing">
                        <?php foreach($searchResults as $anime_result): ?>
                            <li title='<?=htmlspecialchars($anime_result['title'])?>'>
                                <a href="/anime-details.php?id=<?=$anime_result['id']?>"><?=htmlspecialchars($anime_result['title'])?></a>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($searchResults)): ?>
                            <li><p>No anime found matching '<?=$searchQuery?>'.</p></li>
                        <?php endif; ?>
                    </ul>
                    <div class="clr"></div>
                </div>
            </div>
            <?php else: ?>
            <!-- Display Original Anime Details Content -->
            <div class="main_body">
              <div class="anime_name anime_info">
                <i class="icongec-anime_info i_pos"></i>
                <h2>ANIME INFO</h2>
              </div>
              <div class="anime_info_body">
                <div class="anime_info_body_bg">
                  <img src="<?=$fetchDetails['imageUrl']?>">
                  <h1><?=$fetchDetails['name']?></h1>
                  <p class="type"><span>Type: </span><?=$fetchDetails['type']?></p>
                  <p class="type"><span>Plot Summary: </span><?=$fetchDetails['synopsis']?></p>
                  <p class="type"><span>Genre: </span> 
<?php 
    $genreStmt = $conn->prepare("SELECT g.name, g.slug FROM genres g JOIN anime_genre ag ON g.id = ag.genre_id WHERE ag.anime_id = :aid");
    $genreStmt->execute(['aid' => $id]);
    $genres = $genreStmt->fetchAll(PDO::FETCH_ASSOC);
    $genreLinks = [];
    foreach($genres as $g) {
        $genreLinks[] = "<a href='genre.php?slug={$g['slug']}' title='{$g['name']}'>{$g['name']}</a>";
    }
    echo implode(', ', $genreLinks);
?>
</p>
                  <p class="type"><span>Released: </span><?=$fetchDetails['released']?></p>
                  <p class="type"><span>Episodes: </span><?=count($episodes)?></p>
                  <p class="type"><span>Status: </span>
                    <a href="<?php if ($fetchDetails['status'] == 'Completed') {echo "/status/completed"; } else {echo "/status/ongoing";} ?>" title="<?=$fetchDetails['status']?> Anime"><?=$fetchDetails['status']?></a>
                  </p>
                  <p class="type"><span>Other name: </span><?=$fetchDetails['othername']?></p>

                  <!-- [GAP-003] Bookmark Feature -->
                 <!--- <div class="bookmark-section" style="margin-top: 15px;">
                      <?php if(isset($_SESSION['user_id'])):
                          $bmStmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid");
                          $bmStmt->execute(['uid' => $_SESSION['user_id'], 'aid' => $id]);
                          $isBookmarked = $bmStmt->fetch();
                      ?>
                          <button id="btn-bookmark" class="btn btn-warning" onclick="toggleBookmark(<?=$id?>)">
                              <?=$isBookmarked ? 'Remove Bookmark' : 'Bookmark this Anime'?>
                          </button>
                      <?php else: ?>
                          <p><a href="/login.html" style="color:#ffc119">Login to bookmark this anime</a></p>
                      <?php endif; ?>
                  </div> --->

                </div>
                <div class="clr"></div>
                <div class="anime_info_episodes">
                  <h2><?=$fetchDetails['name']?></h2>
                  <div class="anime_info_episodes_next">
                    <!-- Example: 1-100 -->
                </div>
                </div>
              </div>
              
              <!-- Related Episodes Section -->
              <div class="anime_video_body">
                <div class="anime_name episode_video">
                  <i class="icongec-episode_video i_pos"></i>
                  <h2>Related Episodes</h2>
                </div>
                <ul id="episode_related">
                  <?php foreach($episodes as $ep): 
                    $activeClass = (isset($_GET['ep']) && $_GET['ep'] == $ep['episode_number']) ? 'active' : '';
                    $lang = htmlspecialchars($anime['language'] ?? 'SUB');
                  ?>
                  <li>
                    <a href="/streaming.php?id=<?=$ep['id']?>" class="<?=$activeClass?>" title="Episode <?=$ep['episode_number']?>">
                      <div class="name">EP <?=$ep['episode_number']?></div>
                      <div class="cate"><?=$lang?></div>
                    </a>
                  </li>
                  <?php endforeach; ?>
                </ul>
              </div>
              
              <div class="clr"></div>
              <div class="clr"></div>
              <div class="clr"></div>
              <div class="clr"></div>
              <div class="anime_info_body">
                <div class="anime_video_body_comment">
                  <div class="anime_video_body_comment_name">
                    <div class="btm-center">
                      <div id="showCommentsButton" class="specialButton" style="display:block;" onclick="toggleComments('show')">
                        <span class="txt">Show Comments</span>
                      </div>
                      <div id="hideCommentsButton" class="specialButton" style="display:none;" onclick="toggleComments('hide')">
                        <span class="txt">Hide Comments</span>
                      </div>
                    </div>
                  </div>
                  <div id="disqus_thread" style="display:none;"></div>
                </div>
              </div>
            </div>
            <?php endif; ?>
            <div class="clr"></div>
          </section>
          <section class="content_right">
          <div class="headnav_center"></div>
            <div class="clr"></div>
            <div class="main_body">
              <div class="main_body_black">
                <div class="anime_name ongoing">
                  <i class="icongec-ongoing i_pos"></i>
                  <h2>RECENT RELEASE</h2>
                </div>
                <div class="recent">
                  <div id="scrollbar2">
                    <div class="scrollbar">
                      <div class="track">
                        <div class="thumb">
                          <div class="end"></div>
                        </div>
                      </div>
                    </div>
                    <div class="viewport">
                      <div class="overview">
                        <?php require_once('./app/views/partials/recentRelease.php'); ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="clr"></div>
            <div id="load_ads_2">
              <div id="media.net sticky ad" style="display: inline-block">
              </div>
            </div>
            <style type="text/css">
              #load_ads_2 {
                width: 300px;
              }
              #load_ads_2.sticky {
                position: fixed;
                top: 0;
              }
              #scrollbar2 .viewport {
                height: 1000px !important;
              }
            </style>
            <script>
              var leftamt;
              function scrollFunction() {
                var scamt = (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
                var element = document.getElementById("media.net sticky ad");
                if (scamt > leftamt) {
                  var leftPosition = element.getBoundingClientRect().left;
                  element.className = element.className.replace(/(?:^|\s)fixclass(?!\S)/g, '');
                  element.className += " fixclass";
                  element.style.left = leftPosition + 'px';
                }
                else {
                  element.className = element.className.replace(/(?:^|\s)fixclass(?!\S)/g, '');
                }
              }
              function getElementTopLeft(id) {
                var ele = document.getElementById(id);
                var top = 0;
                var left = 0;
                while (ele.tagName != "BODY") {
                  top += ele.offsetTop;
                  left += ele.offsetLeft;
                  ele = ele.offsetParent;
                }
                return { top: top, left: left };
              }
              function abcd() {
                TopLeft = getElementTopLeft("media.net sticky ad");
                leftamt = TopLeft.top;
              }
              window.onload = abcd;
              window.onscroll = scrollFunction;
            </script>
            <?php require_once('./app/views/partials/sub-category.html'); ?>
          </section>
        </section>
        <div class="clr"></div>
        <footer>
          <div class="menu_bottom">
            <a href="/about-us.html">
              <h3>Abouts us</h3>
            </a>
            <a href="/contact-us.html">
              <h3>Contact us</h3>
            </a>
            <a href="/privacy.html">
              <h3>Privacy</h3>
            </a>
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
  
  <?php include('./app/views/partials/footer.php'); ?>
  
  <?php if (!$hasSearched): ?>
  <script>
    function toggleComments(action) {
      var disqusThread = document.getElementById('disqus_thread');
      var showButton = document.getElementById('showCommentsButton');
      var hideButton = document.getElementById('hideCommentsButton');
      
      if (action === 'show') {
        disqusThread.style.display = 'block';
        showButton.style.display = 'none';
        hideButton.style.display = 'block';
        
        // Load Disqus only once when first shown
        if (!window.disqusLoaded) {
          var disqus_config = function () {
            this.page.url = '<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>';
            this.page.identifier = 'anime-<?=$id?>';
          };
          
          (function() {
            var d = document, s = d.createElement('script');
            s.src = 'https://YOUR-DISQUS-SHORTNAME.disqus.com/embed.js';
            s.setAttribute('data-timestamp', +new Date());
            (d.head || d.body).appendChild(s);
            window.disqusLoaded = true;
          })();
        }
      } else if (action === 'hide') {
        disqusThread.style.display = 'none';
        showButton.style.display = 'block';
        hideButton.style.display = 'none';
      }
    }
  </script>
  <?php endif; ?>
  
  <script>
    function toggleBookmark(animeId) {
        var action = $('#btn-bookmark').text().trim() === 'Bookmark this Anime' ? 'add' : 'remove';
        $.ajax({
            url: '/app/controllers/bookmark.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ anime_id: animeId, action: action }),
            success: function(response) {
                if (action === 'add') {
                    $('#btn-bookmark').text('Remove Bookmark');
                } else {
                    $('#btn-bookmark').text('Bookmark this Anime');
                }
                alert(response.message);
            },
            error: function(xhr) {
                alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
            }
        });
    }

    if(document.getElementById('episode_page')){
      var ep_start = $('#episode_page a.active').attr('ep_start');
      var ep_end = $('#episode_page a.active').attr('ep_end');
      var id = $("input#movie_id").val();
      var default_ep = $("input#default_ep").val();
      var alias = $("input#alias_anime").val();
      loadListEpisode('#episode_page a.active',ep_start,ep_end,id,default_ep,alias);
    }
  </script>
  
  <script>
    if (document.getElementById('scrollbar2')) {
      $('#scrollbar2').tinyscrollbar();
    }
  </script>
  
  <!-- Only load Disqus count if not searching -->
  <?php if (!$hasSearched): ?>
  <script id="dsq-count-scr" src="//YOUR-DISQUS-SHORTNAME.disqus.com/count.js" async></script>
  <?php endif; ?>
</body>
</html>