# ![GogoAnime Clone Logo](../assets/img/logo.png)

# Administration Guide

**OFFICIAL DOCUMENTATION**

## 1. Accessing the Admin Panel
Navigate to `https://your-domain.com/admin`.
Log in with your administrator credentials.

## 2. Dashboard Overview
The dashboard provides a quick summary of the system status:
-   Total Anime
-   Total Episodes
-   Total Users
-   Recent Comments

## 3. Managing Content

### 3.1 Adding Anime
1.  Click **Anime > Add New**.
2.  Fill in the metadata:
    -   **Title:** Official title.
    -   **Slug:** URL-friendly ID (auto-generated usually).
    -   **Synopsis:** Description.
    -   **Type:** TV, Movie, etc.
    -   **Status:** Ongoing/Completed.
    -   **Image:** Upload cover art.
3.  Click **Save**.

### 3.2 Adding Episodes
1.  Click **Episodes > Add New**.
2.  Select the **Anime** from the dropdown.
3.  Enter the **Episode Number**.
4.  Add **Video Links**:
    -   Select Provider (e.g., Gogoanime).
    -   Paste the embed URL.
5.  Click **Save**.

### 3.3 Managing Genres
1.  Click **Genres**.
2.  Add new genres or edit existing ones.
3.  Ensure slugs are unique.

## 4. User Management
1.  Click **Users**.
2.  View the list of registered users.
3.  **Actions:**
    -   **Edit:** Change email or role.
    -   **Ban:** Revoke access (if implemented).
    -   **Delete:** Permanently remove the user.

## 5. System Settings
Configuration of video providers and other system-wide settings is handled here.
-   **Video Providers:** Enable/Disable specific streaming sources.
