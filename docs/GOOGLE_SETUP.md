# Google Authentication Setup Guide

This guide explains how to set up "Log in with Google" for your GogoAnime website.

## Prerequisites

- You must have access to the **Google Cloud Console** (https://console.cloud.google.com/).
- Your website must be accessible via **HTTPS** (Google requires HTTPS for production OAuth).
- You need access to the Admin Panel of your website.

## Step 1: Create a Google Cloud Project

1.  Go to the [Google Cloud Console](https://console.cloud.google.com/).
2.  Click on the project dropdown at the top left and select **"New Project"**.
3.  Enter a project name (e.g., "GogoAnime Auth") and click **"Create"**.
4.  Select the newly created project.

## Step 2: Configure OAuth Consent Screen

1.  In the left sidebar, navigate to **APIs & Services** > **OAuth consent screen**.
2.  Select **"External"** as the User Type and click **"Create"**.
3.  Fill in the **App Information**:
    -   **App name**: The name users will see (e.g., "GogoAnime").
    -   **User support email**: Your admin email.
    -   **Developer contact information**: Your admin email.
4.  Click **"Save and Continue"**.
5.  (Optional) You can skip the "Scopes" section or add `.../auth/userinfo.email` and `.../auth/userinfo.profile`. The default scopes are usually sufficient.
6.  Click **"Save and Continue"** until finished.

## Step 3: Create Credentials (OAuth Client ID)

1.  In the left sidebar, navigate to **APIs & Services** > **Credentials**.
2.  Click **"Create Credentials"** at the top and select **"OAuth client ID"**.
3.  **Application type**: Select **"Web application"**.
4.  **Name**: Enter a name (e.g., "GogoAnime Web Client").
5.  **Authorized JavaScript origins**:
    -   Add your website's base URL (e.g., `https://yourdomain.com`).
6.  **Authorized redirect URIs**:
    -   This is the most critical step. You must add the path to the callback script.
    -   Format: `https://yourdomain.com/google_callback.php`
    -   *Replace `yourdomain.com` with your actual domain name.*
7.  Click **"Create"**.
8.  A popup will appear with your **Client ID** and **Client Secret**. Copy these safely.

## Step 4: Configure Admin Panel

1.  Log in to your website's Admin Panel.
2.  Navigate to **"Google Auth"** in the sidebar (or go to `/admin/google_config.php`).
3.  **Enable Google Login**: Check the box.
4.  **Client ID**: Paste the Client ID from Step 3.
5.  **Client Secret**: Paste the Client Secret from Step 3.
6.  **Redirect URI**: Enter the exact URL you put in "Authorized redirect URIs" (e.g., `https://yourdomain.com/google_callback.php`).
7.  Click **"Save Configuration"**.

## Step 5: Verify

1.  Log out of the Admin Panel.
2.  Go to the **Login** page (`/login.html`).
3.  You should see the **"Log in with Google"** button.
4.  Click it to test the flow. It should redirect you to Google, ask for permission, and then redirect back to your site, logging you in.

## Troubleshooting

-   **Redirect URI Mismatch**: If you see `Error 400: redirect_uri_mismatch`, ensure the URI in the Admin Panel matches *exactly* what is in the Google Cloud Console (including http vs https and trailing slashes).
-   **File Permissions**: Ensure `app/config/google_auth.json` is writable by the web server.
