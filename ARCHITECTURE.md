# Architecture

**OFFICIAL DOCUMENTATION**

## 1. Architectural Pattern
The system employs a **Page-Controller** architecture mixed with elements of **MVC (Model-View-Controller)**.

-   **Routing:** Handled by the web server via `.htaccess`, mapping friendly URLs to specific PHP files in the root directory.
-   **Controllers:** Root PHP files (e.g., `anime-details.php`) act as entry points, handling input and orchestrating logic.
-   **Models:** Logic for database interaction is often embedded or handled via helper functions/classes in `app/`.
-   **Views:** Presentation logic is located in `app/views/` or embedded within the page scripts.

## 2. Request Lifecycle

1.  **Incoming Request:** The web server receives a request (e.g., `GET /category/naruto`).
2.  **Rewrite Rule:** `.htaccess` intercepts the request and rewrites it to `anime-details.php?slug=naruto` (conceptual translation).
3.  **Initialization:** The script initializes the environment, connecting to the database via `app/config/db.php`.
4.  **Data Retrieval:** The script queries the `anime` table using the provided slug.
5.  **View Rendering:** The script includes header and footer templates and outputs the HTML with the retrieved data.
6.  **Response:** The fully rendered HTML page is sent to the client.

## 3. Database Architecture
The application uses a relational database model.

### 3.1 Entity Relationships
-   **Anime (1) <-> (N) Episodes:** One anime has many episodes.
-   **Anime (N) <-> (N) Genres:** Many-to-Many relationship via `anime_genre` pivot table.
-   **Episode (1) <-> (N) Episode_Videos:** One episode has many video sources (mirrors).
-   **User (1) <-> (N) Bookmarks:** Users can bookmark many anime.

### 3.2 Persistence Layer
Data access is managed through PHP Data Objects (PDO), ensuring abstraction over the specific database driver (MySQL/SQLite).

## 4. Security Architecture

### 4.1 Authentication
-   Session-based authentication.
-   Passwords stored using strong hashing algorithms (implementation dependent, standard PHP `password_hash` recommended).

### 4.2 Authorization
-   Role-based checks perform gating on sensitive actions.
-   `admin/auth.php` serves as a gatekeeper for administrative pages.

### 4.3 Input Sanitization
-   Use of PDO Prepared Statements protects against SQL Injection.
-   Output encoding is required to prevent XSS (Cross-Site Scripting).

## 5. Deployment Architecture
The system is designed for a single-server deployment but can be scaled:
-   **Web Tier:** Stateless PHP execution allows for horizontal scaling behind a load balancer.
-   **Data Tier:** Centralized database server.
-   **Asset Tier:** Static assets can be offloaded to a CDN.
