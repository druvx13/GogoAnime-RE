![GogoAnime Clone Logo](logos/(HIGH-RES)GogoAnime-RE.png)

# GogoAnime Clone System 

**OFFICIAL REPOSITORY DOCUMENTATION**

## 1. Project Identification
- **Project Name:** GogoAnime Clone System
- **Repository Type:** Anime Streaming Web Application
- **Version:** 1.0.0
- **Status:** Maintenance / Active Development

## 2. Purpose and Scope
The GogoAnime Clone System is a comprehensive web-based platform designed to facilitate the cataloging, management, and streaming of anime content. It provides a dual-interface system:
1.  **Public Frontend:** Allows users to browse, search, watch anime episodes, and manage personal bookmarks.
2.  **Administrative Backend:** Enables operators to manage the content library (anime, episodes, genres), user accounts, and system configurations.

The scope of this repository includes the full source code for the application logic, database schema, and frontend assets.

## 3. System Summary
The system is built on a standard **LAMP/LEMP stack** (Linux, Apache/Nginx, MySQL, PHP). It utilizes a custom PHP framework structure with a mix of root-level routing and MVC-style organization.

Key components include:
-   **Content Management System (CMS):** tailored for anime metadata and video links.
-   **User Management:** Registration, login, and role-based access control (User vs. Admin).
-   **Streaming Interface:** Integration with third-party video providers.
-   **Search & Discovery:** Filtering by genre, status, and alphabetical order.

## 4. Key Capabilities
-   **Anime Cataloging:** Support for detailed metadata (synopsis, release date, status, type).
-   **Episode Management:** Multi-provider support for video hosting (e.g., Gogoanime, StreamSB).
-   **User Engagement:** Comments, bookmarks, and contact forms.
-   **Categorization:** Robust genre and sub-category filing systems.
-   **SEO Friendly:** URL rewriting for human-readable links.

## 5. Technology Stack
-   **Language:** PHP (7.x / 8.x compatible)
-   **Database:** MySQL (Primary) or SQLite (Supported) via PDO
-   **Server:** Apache (requires `mod_rewrite`)
-   **Frontend:** HTML5, CSS3, JavaScript
-   **Styling:** Custom CSS assets

## 6. High-Level Architecture
The application follows a page-controller pattern where specific URL endpoints map directly to PHP scripts, which then leverage shared components.

```ascii
[User Browser]
      |
      v
[Web Server (Apache)] -> [.htaccess Rules]
      |
      +---> [Public Interface] (Root *.php)
      |         |
      |         +--> [Controllers] (app/controllers/)
      |         +--> [Views/Layouts] (app/views/)
      |
      +---> [Admin Interface] (admin/*.php)
      |         |
      |         +--> [Auth Check]
      |         +--> [CRUD Operations]
      |
      v
[Database Layer (PDO)]
      |
      v
[MySQL / SQLite]
```

## 7. Directory Overview
-   **`admin/`**: Administrative panel scripts and logic.
-   **`app/`**: Core application logic, configuration, and views.
-   **`assets/`**: Static resources (images, CSS, JS).
-   **`genre/`**: Genre-specific routing handlers.
-   **`staticHTML/`**: Static page templates (Login, Privacy, etc.).
-   **`status/`**: Handlers for anime status filtering (Ongoing/Completed).
-   **`sub-category/`**: Sub-category routing logic.
-   **`Root`**: Primary public-facing entry points (`index.php`, `anime-details.php`, etc.).

## 8. Installation & Setup Summary
1.  **Clone Repository:** Download the source code.
2.  **Database Setup:** Import `database.sql` into a MySQL database.
3.  **Configuration:**
    -   Copy `app/config/db.php` (or create `app/config/config.local.php`).
    -   Update database credentials (`host`, `dbname`, `username`, `password`).
4.  **Server Config:** Ensure Apache `mod_rewrite` is enabled. Point document root to the repository root.
5.  **Permissions:** Ensure `assets/uploads` is writable.

*See [INSTALLATION.md](INSTALLATION.md) for detailed instructions.*

## 9. Configuration Overview
The system is configured primarily through PHP files located in `app/config/`.
-   **`db.php`**: Database connection settings.
-   **`info.php`**: Site-wide metadata.
-   **`app.json`**: Deployment metadata.

*See [CONFIGURATION.md](CONFIGURATION.md) for details.*

## 10. Administration Overview
Access the admin panel via `/admin/`. Default credentials must be set directly in the database or via the registration of the first admin user (see implementation details). Capabilities include adding anime, managing episodes, and moderating users.

*See [ADMIN_GUIDE.md](ADMIN_GUIDE.md) for the operator manual.*

## 11. Security Model Summary
-   **Authentication:** Session-based login with hashed passwords.
-   **Authorization:** Role-based checks (Admin vs. Standard User).
-   **Input Handling:** PDO prepared statements are used for database interaction to prevent SQL injection (verify in code reviews).
-   **CSRF Protection:** Implemented in critical forms (see `app/config/csrf.php`).

*See [SECURITY.md](SECURITY.md) for a full security audit and guidelines.*

## 12. Operational Notes
-   **Video Hosting:** The system does not host video files directly; it embeds links from external providers.
-   **Traffic:** Heavy traffic is offloaded to video providers; local load is primarily database reads.

## 13. Maintenance Expectations
-   **Regular Backups:** Database dumps should be performed weekly.
-   **Link Verification:** External video links may expire; regular audits are required.
-   **Logs:** Monitor web server logs for PHP errors.

## 14. Limitations and Assumptions
-   **Assumption:** The server environment supports `.htaccess` rewrites.
-   **Limitation:** No built-in video transcoding or hosting.
-   **Limitation:** User registration does not include email verification by default (requires configuration).

## 15. Licensing Summary
This software is proprietary and confidential unless explicitly open-sourced under a specific license file.

*See [LICENSE](LICENSE) for legal terms.*

## 16. References
-   [Architecture Document](ARCHITECTURE.md)
-   [System Overview](SYSTEM_OVERVIEW.md)
-   [Directory Structure](DIRECTORY_STRUCTURE.md)
-   [Documentation Index](DOCUMENTATION_INDEX.md)
