<?php 
require_once('./app/config/info.php');
require_once('./app/config/db.php');

// --- NEW SEARCH LOGIC (Same as animelist.php) ---
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

// --- ORIGINAL EPISODE FETCHING LOGIC (Only runs if not searching) ---
if (!$hasSearched) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Fetch current episode + anime info
    $stmt = $conn->prepare("
        SELECT e.*, a.title as anime_title, a.image_url, a.id as anime_id
        FROM episodes e
        JOIN anime a ON e.anime_id = a.id
        WHERE e.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $episode = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$episode) {
        die("Episode not found.");
    }

    $ep_num = $episode['episode_number'];
    $animeNameWithEP = $episode['anime_title'] . ' Episode ' . $ep_num;
    $anime_id = $episode['anime_id'];

    // --- Video Providers Logic ---
    $videosStmt = $conn->prepare("
        SELECT ev.*, vp.label, vp.name
        FROM episode_videos ev
        JOIN video_providers vp ON ev.provider_id = vp.id
        WHERE ev.episode_id = :episode_id AND vp.is_active = 1
        ORDER BY vp.id ASC
    ");
    $videosStmt->execute(['episode_id' => $id]);
    $availableVideos = $videosStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback to legacy video_url if no providers found
    if (empty($availableVideos) && !empty($episode['video_url'])) {
        $availableVideos[] = [
            'provider_id' => 0,
            'label' => 'Default Server',
            'name' => 'Default',
            'video_url' => $episode['video_url']
        ];
    }

    $selectedVideo = $availableVideos[0] ?? null;
    if (isset($_GET['server'])) {
        foreach ($availableVideos as $v) {
            if ($v['provider_id'] == $_GET['server']) {
                $selectedVideo = $v;
                break;
            }
        }
    }

    $m3u8_url = $selectedVideo ? $selectedVideo['video_url'] : '';

    // --- Previous / Next Logic ---
    $current_ep_num = $episode['episode_number'];

    // Previous Episode
    $prevStmt = $conn->prepare("
        SELECT id, episode_number
        FROM episodes
        WHERE anime_id = :anime_id AND episode_number < :current_ep
        ORDER BY episode_number DESC
        LIMIT 1
    ");
    $prevStmt->execute(['anime_id' => $anime_id, 'current_ep' => $current_ep_num]);
    $prevEp = $prevStmt->fetch(PDO::FETCH_ASSOC);

    // Next Episode
    $nextStmt = $conn->prepare("
        SELECT id, episode_number
        FROM episodes
        WHERE anime_id = :anime_id AND episode_number > :current_ep
        ORDER BY episode_number ASC
        LIMIT 1
    ");
    $nextStmt->execute(['anime_id' => $anime_id, 'current_ep' => $current_ep_num]);
    $nextEp = $nextStmt->fetch(PDO::FETCH_ASSOC);

    // Build anime data array with working prev/next
    $anime = [
        'animeNameWithEP' => htmlspecialchars($animeNameWithEP),
        'ep_num' => htmlspecialchars($ep_num),
        'anime_info' => 'category-slug',
        'ep_download' => $m3u8_url,
        'prevEpLink' => $prevEp ? 'streaming.php?id=' . $prevEp['id'] : '#',
        'nextEpLink' => $nextEp ? 'streaming.php?id=' . $nextEp['id'] : '#',
        'prevEpText' => $prevEp ? 'Episode ' . $prevEp['episode_number'] : 'First Episode',
        'nextEpText' => $nextEp ? 'Episode ' . $nextEp['episode_number'] : 'Last Episode',
        'video' => $m3u8_url,
        'gogoserver' => $m3u8_url
    ];

    $fetchDetails = [
        'synopsis' => 'Episode ' . $ep_num . ' of ' . htmlspecialchars($episode['anime_title']),
        'name' => htmlspecialchars($episode['anime_title']),
        'imageUrl' => htmlspecialchars($episode['image_url']),
        'type' => 'TV'
    ];
}
// --- END ORIGINAL LOGIC ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="shortcut icon" href="<?=$base_url?>/assets/img/favicon.ico">

    <!-- Updated Title based on search -->
    <title><?php if ($hasSearched) { echo "Search Results for '$searchQuery' - $website_name"; } else { echo "Watch $anime[animeNameWithEP] at $website_name"; } ?></title>

    <meta name="robots" content="index, follow" />
    <meta name="description" content="<?php if ($hasSearched) { echo "Search results for '$searchQuery' at $website_name"; } else { echo substr($fetchDetails['synopsis'],0, 150) . ' ... at ' . $website_name; } ?>" />
    <meta name="keywords" content="<?php if ($hasSearched) { echo "Search, $searchQuery, $website_name, Anime"; } else { echo "$anime[animeNameWithEP], $fetchDetails[name], Ep $anime[ep_num] ,English, Subbed"; } ?>" />

    <?php if (!$hasSearched): ?>
    <meta itemprop="image" content="<?=$fetchDetails['imageUrl']?>" />
    <meta property="og:site_name" content="<?=$website_name?>" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="Watch <?=$anime['animeNameWithEP']?> at <?=$website_name?>" />
    <meta property="og:description" content="<?=substr($fetchDetails['synopsis'],0, 150)?> ... at <?=$website_name?>">
    <meta property="og:url" content="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <meta property="og:image" content="<?=$fetchDetails['imageUrl']?>" />
    <meta property="og:image:secure_url" content="<?=$fetchDetails['imageUrl']?>" />
    <?php endif; ?>

    <link rel="canonical" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="alternate" hreflang="en-us" href="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>" />
    <link rel="stylesheet" type="text/css" href="<?=$base_url?>/assets/css/style.css" />
    <link href="<?=$base_url?>/assets/css/videojs/video-js.min.css" rel="stylesheet">
    <script type="text/javascript" src="<?=$base_url?>/assets/js/libraries/jquery.js"></script>
    <script src="<?=$base_url?>/assets/js/videojs/video.min.js"></script>
    <script src="<?=$base_url?>/assets/js/videojs/videojs-contrib-hls.min.js"></script>
    <script src="<?=$base_url?>/assets/js/videojs/videojs-contrib-quality-levels.min.js"></script>
    <script src="<?=$base_url?>/assets/js/videojs/videojs-hls-quality-selector.min.js"></script>
    <script>
        var base_url = 'https://' . document.domain . '/';
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
                        <!-- Display Original Streaming Content -->
                        <div class="main_body">
                            <div class="anime_name anime_video">
                                <i class="icongec-anime_video i_pos"></i>
                                <div class="title_name">
                                    <h2><?=$anime['animeNameWithEP']?></h2>
                                </div>
                                <div class="link_face"><a class="btn facebook hidden-phone" href="javascript:;"
                                        onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent('<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>') + '', 'facebook-share-dialog', 'width=626,height=436');return false;">
                                    </a>
                                    <a class="btn twitter hidden-phone" href="https://twitter.com/share" target="_blank"
                                        data-url="<?=$base_url?><?php echo $_SERVER['REQUEST_URI'] ?>"></a>
                                </div>
                            </div>
                            <div class="anime_video_body">
                                <h1><?=$anime['animeNameWithEP']?> at <?=$website_name?></h1>
                                <div class="anime_video_body_cate">
                                    <span>Category:</span> <?=$fetchDetails['type']?>
                                    <div class="anime-info">
                                        <span>Anime info:</span>
                                            <a href="/anime-details.php?id=<?=$episode['anime_id']?>"
                                            title="<?=$fetchDetails['name']?>"><?=$fetchDetails['name']?></a>
                                    </div>
                                    &nbsp;
                                    <div class="anime_video_note_watch">
                                        Please, <a onclick="freload()" href="javascript:void(0)">reload page</a> if you
                                        can't watch the video
                                    </div>

                                    <!-- Server Selection -->
                                    <div class="server-selection" style="margin: 10px 0;">
                                        <?php if (count($availableVideos) > 1): ?>
                                            <div class="anime_muti_link">
                                                <ul>
                                                    <?php foreach ($availableVideos as $video): ?>
                                                        <?php $active = ($selectedVideo['provider_id'] == $video['provider_id']) ? 'active' : ''; ?>
                                                        <li class="<?=$active?>">
                                                            <a href="?id=<?=$id?>&server=<?=$video['provider_id']?>">
                                                                <?= htmlspecialchars($video['label']) ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                            <style>
                                                .anime_muti_link ul li { display: inline-block; margin-right: 5px; }
                                                .anime_muti_link ul li a { display: block; padding: 5px 10px; background: #333; color: #fff; text-decoration: none; border-radius: 3px; }
                                                .anime_muti_link ul li.active a { background: #f5c518; color: #000; }
                                            </style>
                                        <?php endif; ?>
                                    </div>

                                    <div style="max-height:300px;overflow:hidden;">
                                    </div>
                                    <div class="download-anime">
                                        <div class="anime_video_note_watch">
                                            <div class="anime_video_body_report" style="top:7px;">
                                                <!---<a class="report-ajax" href="javascript:void(0)">Report this
                                                    Episode!</a> --->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="favorites_book">
                                        <ul>
                                            <li class="dowloads"><a href="<?=$anime['ep_download']?>" target="_blank"><i
                                                        class="icongec-dowload"></i><span>Download</span></a></li>
                                            <!---<li class="favorites"><i class="icongec-fa-heart"></i><span>Add to
                                                    Favorites</span></li>-->
                                        </ul>
                                    </div>
                                </div>
                                <div class="clr"></div>
                                <div class="anime_video_body_watch">
                                    <div id="load_anime">
                                        <div class="anime_video_body_watch_items load">
                                             <div class="play-video" style="position:relative; padding-bottom:56.25%; height:0; overflow:hidden; background:black;">
                                                <?php
                                                if ($selectedVideo) {
                                                    $url = $selectedVideo['video_url'];
                                                    $ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                                                    $is_m3u8 = ($ext === 'm3u8') || (strpos($url, '.m3u8') !== false);
                                                    $is_direct = in_array($ext, ['mp4','mkv','webm']) || $is_m3u8 || (strpos($url, '/assets/uploads/') !== false);

                                                    if ($is_direct) {
                                                        // Use Video.js for direct video files, especially M3U8
                                                        ?>
                                                        <video id="my-video" class="video-js vjs-default-skin vjs-big-play-centered" controls preload="auto" style="position:absolute; top:0; left:0; width:100%; height:100%;" data-setup='{"fluid": true}'>
                                                            <source src="<?=htmlspecialchars($url)?>" type="<?=$is_m3u8 ? 'application/x-mpegURL' : 'video/mp4'?>">
                                                            <p class="vjs-no-js">
                                                                To view this video please enable JavaScript, and consider upgrading to a web browser that
                                                                <a href="https://videojs.com/html5-video-support/" target="_blank">supports HTML5 video</a>
                                                            </p>
                                                        </video>
                                                        <script>
                                                            var player = videojs('my-video');
                                                            player.hlsQualitySelector({ displayCurrentQuality: true });
                                                        </script>
                                                        <?php
                                                    } else {
                                                        echo "<iframe src='".htmlspecialchars($url)."' style='position:absolute; top:0; left:0; width:100%; height:100%; border:none;' allowfullscreen></iframe>";
                                                    }
                                                } else {
                                                    echo "<div style='color:white; text-align:center; padding-top:20%;'>Video not available</div>";
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="anime_video_body_episodes">
                                    <div class="anime_video_body_episodes_l">
                                        <a href='<?=$anime['prevEpLink']?>'><?=$anime['prevEpText']?></a>
                                    </div>
                                    <div class="anime_video_body_episodes_r">
                                        <a href='<?=$anime['nextEpLink']?>'><?=$anime['nextEpText']?></a>
                                    </div>
                                </div>
                                <div class="clr"></div>
                                <!-- Related Episodes Sidebar (Small Blocks) -->
                                <div class="anime_video_body">
                                    <div class="anime_name episode_video">
                                        <i class="icongec-episode_video i_pos"></i>
                                        <h2>Related Episodes</h2>
                                    </div>
                                    <ul id="episode_related">
                                        <?php
                                        $relatedStmt = $conn->prepare("SELECT id, episode_number FROM episodes WHERE anime_id = :anime_id ORDER BY episode_number ASC");
                                        $relatedStmt->execute(['anime_id' => $episode['anime_id']]);
                                        while($relEp = $relatedStmt->fetch(PDO::FETCH_ASSOC)) {
                                            $activeClass = ($relEp['id'] == $id) ? 'active' : '';
                                            $lang = htmlspecialchars($episode['language'] ?? 'SUB');
                                            echo "<li>
                                                <a href='streaming.php?id={$relEp['id']}' class='$activeClass'>
                                                    <div class='name'>EP {$relEp['episode_number']}</div>
                                                    <div class='cate'>$lang</div>
                                                </a>
                                            </li>";
                                        }
                                        ?>
                                    </ul>
                                </div>
                                <div class="clr"></div>
                                <div class="clr"></div>
                                <div class="clr"></div>
                                <div class="clr"></div>
                                <!-- Updated Comments Section -->
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
                        <!-- DUPLICATE REMOVED HERE -->
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
                                var scamt = (document.documentElement.scrollTop ? document.documentElement.scrollTop :
                                    document.body.scrollTop);
                                var element = document.getElementById("media.net sticky ad");
                                if (scamt > leftamt) {
                                    var leftPosition = element.getBoundingClientRect().left;
                                    element.className = element.className.replace(/(?:^|\s)fixclass(?!\S)/g, '');
                                    element.className += " fixclass";
                                    element.style.left = leftPosition + 'px';
                                } else {
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
                                return {
                                    top: top,
                                    left: left
                                };
                            }
                            function abcd() {
                                TopLeft = getElementTopLeft("media.net sticky ad");
                                leftamt = TopLeft.top;
                                //leftamt -= 10;
                            }
                            window.onload = abcd;
                            window.onscroll = scrollFunction;
                        </script>
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
                this.page.identifier = 'episode-<?=$id?>';
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
        if (document.getElementById('scrollbar2')) {
            $('#scrollbar2').tinyscrollbar();
        }
    </script>
    <!-- Don't forget to update YOUR-DISQUS-SHORTNAME -->
    <script id="dsq-count-scr" src="//YOUR-DISQUS-SHORTNAME.disqus.com/count.js" async></script>
</body>
</html>