<?php
/**
 * Recent Releases Partial
 *
 * This partial renders the list of recently released anime episodes.
 * It queries the database for the latest episodes and displays them as a list with thumbnails.
 *
 * @package    GogoAnime Clone
 * @subpackage Views/Partials
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */
?>
                        <nav class="menu_recent">
                          <ul>
                          <?php
                            // Ensure database connection is available
                            if (!isset($conn)) {
                                require_once(__DIR__ . '/../../config/db.php');
                            }

                            // Query recent episodes joined with anime info
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
