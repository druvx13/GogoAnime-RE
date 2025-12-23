<?php
/**
 * Admin Dashboard
 *
 * This is the landing page for the administrative backend.
 * It displays key statistics about the platform, including total counts for
 * anime, episodes, and registered users.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch system statistics
try {
    $animeCount = $conn->query("SELECT COUNT(*) FROM anime")->fetchColumn();
    $episodeCount = $conn->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
    $userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) {
    // Handle database errors gracefully on dashboard
    $animeCount = $episodeCount = $userCount = "Error";
    error_log("Dashboard stats error: " . $e->getMessage());
}
?>

<h2>Dashboard</h2>
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Total Anime</div>
            <div class="card-body">
                <h5 class="card-title"><?=$animeCount?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Total Episodes</div>
            <div class="card-body">
                <h5 class="card-title"><?=$episodeCount?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-warning mb-3">
            <div class="card-header">Total Users</div>
            <div class="card-body">
                <h5 class="card-title"><?=$userCount?></h5>
            </div>
        </div>
    </div>
</div>

<?php require_once 'layout/footer.php'; ?>
