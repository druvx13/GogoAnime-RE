# Deployment Guide

**OFFICIAL DOCUMENTATION**

## 1. Overview
This document outlines the procedures for deploying the GogoAnime Clone System to a production environment.

## 2. Production Environment Requirements
-   **Security:** SSL/TLS Certificate (HTTPS) is mandatory for protecting user logins.
-   **Performance:** PHP Opcache should be enabled.
-   **Database:** A managed database service (e.g., AWS RDS) is recommended for scalability.

## 3. Deployment Strategy

### 3.1 Manual Deployment (Standard)
1.  **Prepare Server:** Install LAMP/LEMP stack.
2.  **Transfer Code:** Use `git clone` or `rsync` to move files to the server.
3.  **Secure Config:** Create `app/config/config.local.php` with production credentials.
4.  **Set Permissions:** Ensure web server write access only to `assets/uploads`.
5.  **Disable Debugging:** Verify `display_errors` is `Off` in `.htaccess` or `php.ini`.

### 3.2 Heroku Deployment
The repository includes an `app.json` compatible with Heroku.
1.  **Connect Repo:** Connect GitHub repository to Heroku.
2.  **Provision Database:** Attach ClearDB or JawsDB MySQL add-on.
3.  **Environment Vars:** Heroku config vars must be mapped to PHP variables (requires modifying `db.php` to read `getenv()`). *Note: Current codebase reads variables from PHP files; modification required for native Env Var support.*
4.  **Deploy:** Push to Heroku master.

## 4. Post-Deployment Checklist
-   [ ] **SSL:** Verify HTTPS is active and redirects HTTP.
-   [ ] **Assets:** Verify images and CSS load correctly (check `base_url`).
-   [ ] **Database:** Confirm connection and initial data presence.
-   [ ] **Admin Access:** Verify admin login works.
-   [ ] **Permissions:** Verify file uploads work (if applicable).

## 5. Rollback Procedure
In case of critical failure:
1.  **Revert Code:** `git checkout [previous_tag]`
2.  **Revert Database:** Restore from the latest pre-deployment backup.
3.  **Clear Cache:** Restart PHP-FPM / Apache.
