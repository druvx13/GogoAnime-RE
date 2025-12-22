# Data Flow

**OFFICIAL DOCUMENTATION**

## 1. Overview
This document describes how data moves through the GogoAnime Clone System, from user input to database persistence and back to the display.

## 2. Public Read Flow (e.g., Viewing Anime Details)

1.  **User Request:** Browser requests `GET /category/naruto`.
2.  **Routing:** `.htaccess` rewrites to `anime-details.php?slug=naruto`.
3.  **Controller Input:** `anime-details.php` reads `$_GET['slug']`.
4.  **Database Query:**
    -   Connects via `app/config/db.php`.
    -   Executes `SELECT * FROM anime WHERE slug = ?`.
    -   Executes `SELECT * FROM genres ...` linked to the anime.
    -   Executes `SELECT * FROM episodes ...` linked to the anime.
5.  **Data Processing:** Results are fetched as associative arrays.
6.  **View Rendering:** Data is injected into HTML templates.
7.  **Output:** HTML response sent to browser.

## 3. Public Write Flow (e.g., Posting a Comment)

1.  **User Action:** User submits comment form on `streaming.php`.
2.  **Form Submission:** POST request to controller.
3.  **CSRF Check:** `csrf.php` validates the token.
4.  **Authentication Check:** System verifies `$_SESSION` for logged-in user.
5.  **Validation:** Input is sanitized (HTML tags stripped/encoded).
6.  **Persistence:**
    -   `INSERT INTO comments (user_id, episode_id, content) VALUES (...)`.
7.  **Feedback:** User is redirected back to the page with a success message.

## 4. Admin Write Flow (e.g., Adding an Episode)

1.  **Admin Action:** Admin submits "Add Episode" form.
2.  **Authentication:** `admin/auth.php` verifies `admin` role.
3.  **Input Processing:**
    -   Episode Number, Title, and Video Links are collected.
4.  **Persistence:**
    -   `INSERT INTO episodes ...`
    -   `INSERT INTO episode_videos ...` (for each provider link).
5.  **Result:** Database is updated; new episode appears on frontend immediately.

## 5. External Data Flow (Video Streaming)

1.  **Request:** User clicks "Play" on `streaming.php`.
2.  **Resolution:** The system retrieves the `video_url` for the selected provider from `episode_videos`.
3.  **Embedding:** The system generates an `<iframe>` pointing to the external provider (e.g., `https://doodstream.com/e/...`).
4.  **Streaming:** The user's browser connects directly to the external provider. **No video data passes through the GogoAnime server.**
