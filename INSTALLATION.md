# Installation Guide

**OFFICIAL DOCUMENTATION**

## 1. Prerequisites
Before installing the GogoAnime Clone System, ensure your environment meets the following requirements:

-   **Operating System:** Linux (Ubuntu 20.04+, CentOS 7+), Windows, or macOS.
-   **Web Server:** Apache HTTP Server (recommended) or Nginx.
-   **PHP:** Version 7.4 or 8.x.
    -   Extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`.
-   **Database:** MySQL 5.7+ / MariaDB 10.x or SQLite 3.
-   **Git:** For cloning the repository.

## 2. Installation Steps

### 2.1 Clone the Repository
Clone the codebase to your web server's document root or a subdirectory.
```bash
git clone https://github.com/shashankktiwariii/gogoanime-clone.git
cd gogoanime-clone
```

### 2.2 Database Setup
1.  **Create Database:** Log in to your MySQL server and create a new database.
    ```sql
    CREATE DATABASE gogoanime;
    ```
2.  **Import Schema:** Import the provided SQL dump.
    ```bash
    mysql -u [username] -p gogoanime < database.sql
    ```

### 2.3 Configuration
1.  Navigate to the configuration directory: `app/config/`.
2.  Create a local configuration file named `config.local.php`.
    ```php
    <?php
    $db_host = "localhost";
    $db_name = "gogoanime";
    $db_user = "your_username";
    $db_pass = "your_password";
    $db_driver = "mysql"; // or 'sqlite'
    ?>
    ```
    *Note: Do not modify `db.php` directly if possible, to avoid conflicts during updates.*

### 2.4 Server Configuration (Apache)
Ensure that `mod_rewrite` is enabled. The `.htaccess` file included in the root directory handles URL routing.
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```
Verify that your VirtualHost configuration allows overrides:
```apache
<Directory /var/www/html/gogoanime-clone>
    AllowOverride All
</Directory>
```

### 2.5 File Permissions
Ensure the web server user (e.g., `www-data`) has write access to the uploads directory.
```bash
chmod -R 755 assets/uploads
chown -R www-data:www-data assets/uploads
```

## 3. Post-Installation Verification
1.  Navigate to your site's URL (e.g., `http://localhost/`).
2.  Verify the homepage loads without errors.
3.  Navigate to `/admin` and attempt to log in.
    -   *Note: You may need to manually insert an admin user into the `users` table if one does not exist.*

## 4. Troubleshooting Installation
-   **404 Errors on Links:** Verify `.htaccess` is being read and `mod_rewrite` is active.
-   **Database Connection Error:** Check `app/config/config.local.php` credentials.
