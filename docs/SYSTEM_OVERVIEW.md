# System Overview

**OFFICIAL DOCUMENTATION**

## 1. Introduction
The GogoAnime Clone System is a web application designed for the distribution and consumption of anime content. It bridges the gap between content storage (external video providers) and content consumption (end-users), provided through a centralized management interface.

## 2. User Interface (Frontend)
The public-facing side of the application serves the end-users.

### 2.1 Core Features
-   **Homepage:** Displays latest releases, popular anime, and seasonal content.
-   **Search:** Allows users to query the database by title.
-   **Anime Details:** Provides comprehensive information including synopsis, genre, release date, and episode list.
-   **Streaming Player:** Embeds video players from third-party sources for episode playback.
-   **User Accounts:**
    -   Registration/Login.
    -   Bookmarks management.
    -   Comment submission.

### 2.2 Navigation Structure
-   **New Season:** `/new-season.php`
-   **Movies:** `/anime-movies.php`
-   **Popular:** `/popular.php`
-   **Genre List:** `/genre.php`

## 3. Administration Interface (Backend)
The protected administrative panel allows system operators to maintain the platform.

### 3.1 Core Features
-   **Anime Management:** Create, Read, Update, Delete (CRUD) operations for anime series and movies.
-   **Episode Management:** Add new episodes and link them to video sources.
-   **Genre Management:** Define and organize content categories.
-   **User Management:** View registered users and manage their access (promote/ban).
-   **Content Moderation:** Manage comments and messages.

### 3.2 Access Control
Access to the `/admin` directory is restricted to users with the `admin` role in the `users` database table.

## 4. Subsystems

### 4.1 Video Provider Subsystem
The system abstracts video hosting. The `video_providers` table defines supported platforms (e.g., Doodstream, StreamSB). The `episode_videos` table links specific episodes to these providers, allowing multiple mirrors for a single episode.

### 4.2 Categorization Subsystem
Content is organized via a flexible tagging system:
-   **Genres:** Core descriptors (Action, Romance, etc.).
-   **Types:** Format descriptors (TV Series, Movie, OVA).
-   **Status:** Release state (Ongoing, Completed).

## 5. External Dependencies
-   **Video Hosts:** The system relies entirely on external providers for content delivery.
-   **Database:** A relational database is required for all metadata persistence.
