# Configuration

**OFFICIAL DOCUMENTATION**

## 1. Overview
The GogoAnime Clone System uses a file-based configuration approach. Settings are primarily located in the `app/config/` directory.

## 2. Configuration Files

### 2.1 `app/config/db.php`
Handles the database connection logic.
-   **Role:** Establishes the PDO connection.
-   **Behavior:** Loads defaults, then checks for `config.local.php` to override.
-   **Variables:**
    -   `$host`: Database server hostname (Default: `localhost`).
    -   `$dbname`: Database name.
    -   `$username`: Database user.
    -   `$password`: Database password.
    -   `$driver`: Database driver (`mysql` or `sqlite`).

### 2.2 `app/config/config.local.php` (Optional)
This file is not committed to version control and is intended for environment-specific secrets.
-   **Variables:**
    -   `$db_host`: Overrides `$host`.
    -   `$db_name`: Overrides `$dbname`.
    -   `$db_user`: Overrides `$username`.
    -   `$db_pass`: Overrides `$password`.
    -   `$db_driver`: Overrides `$driver`.

### 2.3 `app/config/info.php`
Contains site-wide metadata and constants.
-   **Variables:**
    -   `$site_name`: The display name of the website.
    -   `$base_url`: The root URL of the installation.

### 2.4 `app/config/csrf.php`
Manages Cross-Site Request Forgery (CSRF) tokens.
-   **Role:** Generates and validates tokens for form submissions.

## 3. Environment Variables
The application does not natively load `.env` files. Environment variables must be set at the server level or defined within `config.local.php`.

## 4. Web Server Configuration (`.htaccess`)
The `.htaccess` file controls URL rewriting and PHP environment settings.
-   **`php_value display_errors Off`**: Hides errors in production.
-   **`RewriteEngine on`**: Enables URL rewriting.
-   **Routes:** Maps friendly URLs to PHP scripts.

## 5. Security Sensitive Values
The following values must be protected and never committed to public repositories:
-   Database Passwords (`$db_pass`).
-   API Keys (if added in future).
-   Session Secrets.
