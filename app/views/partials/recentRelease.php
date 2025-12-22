                        <nav class="menu_recent">
                          <ul>
                          <?php
                            // [GAP-001] Native MySQL Implementation
                            // Query recent episodes joined with anime info
                            // Assuming $conn is available from the parent include (home.php etc usually include db.php)
                            if (!isset($conn)) {
                                require_once(__DIR__ . '/../../config/db.php');
                            }

                            $stmt = $conn->prepare("
                                SELECT e.id, e.episode_number, a.title, a.image_url
                                FROM episodes e
                                JOIN anime a ON e.anime_id = a.id
                                ORDER BY e.created_at DESC
                                LIMIT 10
                            ");
                            $stmt->execute();
                            $recentReleases = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach($recentReleases as $recentRelease)  {
                                $link = "/streaming.php?id=" . $recentRelease['id']; // Using standard routing
                                $title = htmlspecialchars($recentRelease['title']);
                                $img = htmlspecialchars($recentRelease['image_url']);
                                $epNum = htmlspecialchars($recentRelease['episode_number']);
                           ?>
                            <li>
                              <a href="<?=$link?>" title="<?=$title?>">
                                <div class="thumbnail-recent"
                                  style="background: url('<?=$img?>');"></div>
                                  <?=$title?>
                              </a>
                              <a href="<?=$link?>" title="<?=$title?>">
                                <p class="time_2">Episode <?=$epNum?></p>
                              </a>
                            </li>
                          <?php } ?>
                          </ul>
                        </nav>