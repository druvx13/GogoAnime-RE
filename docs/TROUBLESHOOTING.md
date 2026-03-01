# Troubleshooting Guide

**OFFICIAL DOCUMENTATION**

## 1. Installation Issues

### "Internal Server Error" (500)
-   **Cause:** `.htaccess` syntax error or missing rewrite module.
-   **Solution:** Check Apache error logs. Ensure `mod_rewrite` is enabled.

### "Database Connection Failed"
-   **Cause:** Incorrect credentials in `app/config/config.local.php`.
-   **Solution:** Verify host, username, password, and database name.

## 2. Operational Issues

### 404 Not Found on Anime Pages
-   **Cause:** Rewrite rules not working.
-   **Solution:** Ensure `AllowOverride All` is set in Apache config for the directory.

### Images Not Loading
-   **Cause:** Incorrect permission or wrong path.
-   **Solution:** Check `assets/uploads` permissions (755). Check browser console for 404 paths.

### Video Player Empty/Error
-   **Cause:** External provider link is dead or blocked.
-   **Solution:** Update the episode video link in the Admin Panel.

### "Permission Denied" on Upload
-   **Cause:** Web server cannot write to `assets/uploads`.
-   **Solution:** `chown www-data assets/uploads`.

## 3. Debugging Mode
To see detailed PHP errors (Development Only):
1.  Open `.htaccess`.
2.  Change `php_value display_errors Off` to `On`.
3.  **Warning:** Revert immediately after diagnosis.
