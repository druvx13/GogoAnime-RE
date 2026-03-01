# Maintenance Guide

**OFFICIAL DOCUMENTATION**

## 1. Routine Tasks

### 1.1 Weekly
-   **Database Backup:** Export a full dump of the MySQL database.
    ```bash
    mysqldump -u [user] -p [dbname] > backup_$(date +%F).sql
    ```
-   **Log Review:** Check web server error logs for repeated PHP errors or 404s.

### 1.2 Monthly
-   **Link Verification:** Randomly sample anime episodes to ensure external video links are still active. Video providers often delete inactive files.
-   **Disk Usage:** Monitor disk space, specifically the `assets/uploads` directory.

### 1.3 Quarterly
-   **Software Updates:** Update server packages (OS, PHP, MySQL, Apache/Nginx).
-   **Password Rotation:** Rotate database and admin passwords.

## 2. Database Maintenance
-   **Optimization:** Run `OPTIMIZE TABLE` on tables with frequent updates (e.g., `views`, `users`).
-   **Cleanup:** Remove unverified users or spam comments if they accumulate.

## 3. Asset Maintenance
-   **Orphaned Images:** Identify and delete images in `assets/uploads` that are no longer linked to any anime in the database.
