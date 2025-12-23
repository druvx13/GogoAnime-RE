<?php
/**
 * Anime Details Page
 *
 * This page displays detailed information about a specific anime (`/anime-details.php?id=X`).
 * It includes:
 * 1. Metadata (Title, Synopsis, Type, Status, Genres, etc.).
 * 2. List of available episodes.
 * 3. Bookmark functionality (AJAX).
 * 4. Disqus comments integration.
 *
 * It also handles search queries if provided via `keyword` GET parameter, acting as a fallback search results page.
 *
 * @package    GogoAnime Clone
 * @subpackage Root
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once('./app/config/info.php');
require_once('./app/config/db.php');

// --- SEARCH LOGIC ---
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
// --- END SEARCH LOGIC ---

// --- ANIME DETAILS LOGIC ---
if (!$hasSearched) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Fetch Anime Info
    $stmt = $conn->prepare("SELECT * FROM anime WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $anime = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$anime) {
        // Simple error handling for invalid ID
        die("Anime not found.");
    }

    // Fetch Episodes
    $stmt = $conn->prepare("SELECT * FROM episodes WHERE anime_id = :anime_id ORDER BY episode_number ASC");
    $stmt->execute(['anime_id' => $id]);
    $episodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map DB fields for template usage
    $fetchDetails = [
        'name' => htmlspecialchars($anime['title']),
        'synopsis' => htmlspecialchars($anime['synopsis']),
        'imageUrl' => htmlspecialchars($anime['image_url']),
        'type' => htmlspecialchars($anime['type']),
        'released' => htmlspecialchars($anime['release_date']),
        'status' => htmlspecialchars($anime['status']),
        'othername' => '', // Placeholder for future use
        'language' => htmlspecialchars($anime['language'])
    ];
}
// --- END ANIME DETAILS LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico" />

  <title><?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "$fetchDetails[name] at $website_name"; } ?></title>

  <meta name="robots" content="index, follow" />
  <meta name="description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo substr($fetchDetails['synopsis'],0, 150) . ' ... at ' . $website_name; } ?>" />
  <meta name="keywords" content="<?php if ($hasSearched) { echo "Search, $searchQuery, $website_name, Anime"; } else { echo "$fetchDetails[name], anime, watch online"; } ?>" />

  <?php if (!$hasSearched): ?>
  <meta itemprop="image" content="<?=$fetchDetails['imageUrl']?>" />
  <meta property="og:site_name" content="<?=$website_name?>" />
  <meta property="og:locale" content="en_US" />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="<?=$fetchDetails['name']?> at <?=$website_name?>" />
  <meta property="og:description" content="<?=substr($fetchDetails['synopsis'],0, 150)?> ... at <?=$website_name?>">
  <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <meta property="og:image" content="<?=$fetchDetails['imageUrl']?>" />
  <meta property="og:image:secure_url" content="<?=$fetchDetails['imageUrl']?>" />
  <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
  <?php endif; ?>

  <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
  <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
  <script>
        var base_url = '<?=$base_url?>/';
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
                <div class="anime_name anime_list">
                    <i class="icongec-anime_list i_pos"></i>
                    <h2>Search Results for '<?=htmlspecialchars($searchQuery)?>'</h2>
                </div>
                <div class="anime_list_body">
                    <ul class="listing">
                        <?php foreach($searchResults as $anime_result): ?>
                            <li title='<?=htmlspecialchars($anime_result['title'])?>'>
                                <a href="/anime-details.php?id=<?=$anime_result['id']?>"><?=htmlspecialchars($anime_result['title'])?></a>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($searchResults)): ?>
                            <li><p>No anime found matching '<?=htmlspecialchars($searchQuery)?>'.</p></li>
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
                  <img src="<?=$fetchDetails['imageUrl']?>" alt="<?=$fetchDetails['name']?>">
                  <h1><?=$fetchDetails['name']?></h1>
                  <p class="type"><span>Type: </span><?=$fetchDetails['type']?></p>
                  <p class="type"><span>Plot Summary: </span><?=$fetchDetails['synopsis']?></p>
                  <p class="type"><span>Genre: </span> 
                    <?php
                        // Fetch Genres associated with this anime
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
                  <p class="type"><span>Language: </span><?=$fetchDetails['language']?></p>

                  <!-- Bookmark Feature -->
                  <div class="bookmark-section" style="margin-top: 15px;">
                      <?php if(isset($_SESSION['user_id'])):
                          // Check if already bookmarked
                          $bmStmt = $conn->prepare("SELECT id FROM bookmarks WHERE user_id = :uid AND anime_id = :aid");
                          $bmStmt->execute(['uid' => $_SESSION['user_id'], 'aid' => $id]);
                          $isBookmarked = $bmStmt->fetch();
                      ?>
                          <button id="btn-bookmark" class="btn btn-warning" onclick="toggleBookmark(<?=$id?>)" style="background:#ffc119; border:none; padding:8px 12px; cursor:pointer; color:#000; font-weight:bold;">
                              <?=$isBookmarked ? 'Remove Bookmark' : 'Bookmark this Anime'?>
                          </button>
                      <?php else: ?>
                          <p><a href="/login.html" style="color:#ffc119">Login to bookmark this anime</a></p>
                      <?php endif; ?>
                  </div>

                </div>
                <div class="clr"></div>
                <div class="anime_info_episodes">
                  <h2><?=$fetchDetails['name']?></h2>
                  <div class="anime_info_episodes_next">
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
                    $lang = htmlspecialchars($fetchDetails['language']);
                  ?>
                  <li>
                    <a href="/streaming.php?id=<?=$ep['id']?>" title="Episode <?=$ep['episode_number']?>">
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
                  <!-- Disqus container -->
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
            <style type="text/css">
              #scrollbar2 .viewport {
                height: 1000px !important;
              }
            </style>
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
            // REPLACE WITH YOUR DISQUS SHORTNAME
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
        var btn = $('#btn-bookmark');
        var action = btn.text().trim() === 'Bookmark this Anime' ? 'add' : 'remove';
        $.ajax({
            url: '/app/controllers/bookmark.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ anime_id: animeId, action: action }),
            success: function(response) {
                if (response.success) {
                    if (action === 'add') {
                        btn.text('Remove Bookmark');
                    } else {
                        btn.text('Bookmark this Anime');
                    }
                    alert(response.message);
                } else {
                    alert('Action failed: ' + response.message);
                }
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Unknown error';
                alert('Error: ' + msg);
            }
        });
    }
  </script>
  
  <script>
    if (document.getElementById('scrollbar2')) {
      $('#scrollbar2').tinyscrollbar();
    }
  </script>
</body>
</html>
