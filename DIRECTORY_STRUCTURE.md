# Directory Structure

**OFFICIAL DOCUMENTATION**

## 1. Root Directory
The root directory contains the main entry points for the public-facing application and essential configuration files.

-   **`.htaccess`**: Web server configuration for URL rewriting and environment settings.
-   **`404.php`**: Error page for "File Not Found".
-   **`anime-details.php`**: Controller for displaying anime details (`/category/{slug}`).
-   **`anime-list.php`**: Controller for the full list of anime.
-   **`anime-list-az.php`**: Controller for alphabetical filtering.
-   **`anime-movies.php`**: Controller for listing movies.
-   **`app.json`**: Deployment metadata.
-   **`contact-us.php`**: Contact form handler.
-   **`database.sql`**: Database schema definition.
-   **`favicon.ico`**: Site icon.
-   **`genre.php`**: Controller for listing genres.
-   **`home.php`**: Homepage controller.
-   **`index.php`**: Default entry point (usually loads `home.php`).
-   **`new-season.php`**: Controller for seasonal content.
-   **`popular.php`**: Controller for popular content.
-   **`search.php`**: Search result handler.
-   **`streaming.php`**: Video player controller (`/{slug}-episode-{num}`).

## 2. Admin Directory (`admin/`)
Contains the administrative interface.

-   **`anime_add.php`**, **`anime_edit.php`**, **`anime_list.php`**: Anime management.
-   **`episode_add.php`**, **`episode_edit.php`**, **`episode_list.php`**: Episode management.
-   **`genre_add.php`**, **`genre_list.php`**: Genre management.
-   **`users.php`**: User management.
-   **`auth.php`**: Authentication check include file.
-   **`login.php`**, **`logout.php`**: Admin session handling.
-   **`comments.php`**: Comment moderation.
-   **`messages.php`**: Contact form message viewer.
-   **`layout/`**: Admin dashboard UI templates.

## 3. App Directory (`app/`)
Core application logic and configuration.

-   **`config/`**:
    -   `db.php`: Database connection.
    -   `csrf.php`: CSRF protection logic.
    -   `info.php`: Site information.
-   **`controllers/`**: Helper logic and business rules (if refactored from root).
-   **`views/`**: Reusable UI components (Header, Footer, Sidebar).

## 4. Assets Directory (`assets/`)
Static resources served directly to the client.

-   **`css/`**: Stylesheets.
-   **`js/`**: JavaScript files.
-   **`img/`**: Images (UI elements, backgrounds, logos).
-   **`fonts/`**: Web fonts.
-   **`uploads/`**: User or admin uploaded content.
-   **`readme-images/`**: Images used in documentation.

## 5. Genre Directory (`genre/`)
-   **`id.php`**: Handler for `/genre/{slug}` requests.

## 6. Sub-Category Directory (`sub-category/`)
-   **`id.php`**: Handler for `/sub-category/{slug}` requests.

## 7. Static HTML Directory (`staticHTML/`)
Contains PHP files that serve primarily static content or standalone forms.
-   `login.php`, `register.php`, `privacy.php`, `about-us.php`.

## 8. Status Directory (`status/`)
-   **`completed.php`**: Handler for completed anime lists.
-   **`ongoing.php`**: Handler for ongoing anime lists.
