<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { height: 100vh; position: fixed; top: 0; left: 0; width: 250px; background-color: #343a40; padding-top: 20px; overflow-y: auto; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 18px; color: #818181; display: block; }
        .sidebar a:hover { color: #f1f1f1; background-color: #495057; }
        .main-content { margin-left: 250px; padding: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-white text-center">GogoAnime Admin</h4>
        <hr class="text-white">
        <a href="/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/admin/anime_list.php"><i class="fas fa-film"></i> Anime List</a>
        <a href="/admin/anime_add.php"><i class="fas fa-plus"></i> Add Anime</a>
        <a href="/admin/episode_list.php"><i class="fas fa-video"></i> Episode List</a>
        <a href="/admin/episode_add.php"><i class="fas fa-upload"></i> Add Episode</a>
        <a href="/admin/genre_list.php"><i class="fas fa-tags"></i> Genres</a>
        <a href="/admin/country_list.php"><i class="fas fa-globe"></i> Countries</a>
        <a href="/admin/season_list.php"><i class="fas fa-cloud-sun"></i> Seasons</a>
        <a href="/admin/type_list.php"><i class="fas fa-list"></i> Types</a>
        <a href="/admin/request_list.php"><i class="fas fa-clipboard-list"></i> Requests</a>
        <a href="/admin/users.php"><i class="fas fa-users"></i> Users</a>
        <!-- Comments handled by Disqus -->
        <a href="/admin/messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="/admin/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="main-content">
