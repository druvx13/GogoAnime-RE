<?php
require_once 'auth.php';
require_once '../app/config/db.php';
require_once 'layout/header.php';

// Fetch stats
$animeCount = $conn->query("SELECT COUNT(*) FROM anime")->fetchColumn();
$episodeCount = $conn->query("SELECT COUNT(*) FROM episodes")->fetchColumn();
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
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
