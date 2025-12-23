<?php
/**
 * Admin Layout Header
 *
 * This file contains the HTML head, sidebar navigation, and opening tags for the admin layout.
 * It is included at the top of every admin page.
 *
 * @package    GogoAnime Clone
 * @subpackage Admin/Layout
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - GogoAnime</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { height: 100vh; position: fixed; top: 0; left: 0; width: 250px; background-color: #343a40; padding-top: 20px; overflow-y: auto; }
        .sidebar a { padding: 10px 15px; text-decoration: none; font-size: 16px; color: #adb5bd; display: block; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #495057; }
        .sidebar i { width: 25px; text-align: center; margin-right: 5px; }
        .main-content { margin-left: 250px; padding: 20px; }
        .sidebar-header { text-align: center; padding-bottom: 20px; }
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h4 class="text-white">GogoAnime Admin</h4>
        </div>
        <a href="/admin/index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="/admin/anime_list.php"><i class="fas fa-film"></i> Anime List</a>
        <a href="/admin/anime_add.php"><i class="fas fa-plus"></i> Add Anime</a>
        <a href="/admin/episode_list.php"><i class="fas fa-video"></i> Episode List</a>
        <a href="/admin/episode_add.php"><i class="fas fa-upload"></i> Add Episode</a>
        <a href="/admin/genre_list.php"><i class="fas fa-tags"></i> Genres</a>
        <a href="/admin/users.php"><i class="fas fa-users"></i> Users</a>
        <a href="/admin/comments.php"><i class="fas fa-comments"></i> Comments</a>
        <a href="/admin/messages.php"><i class="fas fa-envelope"></i> Messages</a>
        <a href="/admin/logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    <div class="main-content">
