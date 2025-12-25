# `assets/js/main.js` Documentation

This document provides a comprehensive overview of the functionality found in `assets/js/main.js`. This file serves as the core JavaScript logic for the frontend of the website, handling user interface interactions, navigation, and state management.

## 1. Overview

The `main.js` file is responsible for:
- **UI Interaction:** Managing menus, search bars, tabs, and modals.
- **Event Handling:** Attaching listeners to DOM elements (clicks, hovers, form submissions).
- **Cleanup:** Modernizing legacy inline event handlers to ensure consistent behavior across different page templates.
- **Stubbing:** Providing lightweight replacements for legacy AJAX functions (e.g., `loadTopViews`) to maintain UI functionality without external dependencies.

It relies on **jQuery** and executes primarily within a `$(document).ready()` block to ensure the DOM is fully loaded before interactions are bound.

---

## 2. Initialization & Cleanup

Upon loading, the script immediately performs cleanup tasks to prevent conflicts between legacy inline HTML attributes and modern event listeners.

```javascript
// Remove conflicting inline handlers
$('.menu_mobile').removeAttr('onclick');
$('.search-iph a').removeAttr('onclick');
$('.hide_search').removeAttr('onclick');
```
*   **Purpose:** The PHP templates (`header.php`) contain legacy `onclick` attributes that toggle visibility directly. These were removed programmatically to allow `main.js` to control the logic (e.g., adding overlays, locking body scroll) without race conditions or double-toggling.

---

## 3. UI Interactions

### 3.1. Mobile Menu
*   **Trigger:** Click on `.menu_mobile` (hamburger icon).
*   **Behavior:**
    *   Toggles the visibility of the side navigation (`nav.menu_top`).
    *   Displays a dark overlay (`#off_light`).
    *   Locks the body scroll (`overflow: hidden`) to prevent background scrolling while the menu is open.
*   **Closing:** Clicking the overlay (`#off_light`) closes the menu and restores scrolling.

### 3.2. Search Toggle (Mobile/Responsive)
*   **Trigger:** Click on `.search-iph` (magnifying glass icon).
*   **Behavior:**
    *   Hides the trigger icon.
    *   Hides the site logo (`img.logo`) to make space.
    *   Shows the search form (`#search-form`).
    *   Shows the close button (`.hide_search`).
*   **Closing:** Clicking `.hide_search` reverses this process, hiding the form and restoring the logo and search icon.

### 3.3. Tab Switching
The script handles several tabbed interfaces used for displaying content (e.g., "Day/Week/Month" top views, or "Recent Sub/Dub" lists).

*   **Ads Tabs (`.nav-tabs.ads`):** Switches between different ad/content blocks.
*   **Intro Tabs (`.nav-tabs.intro`):** Switches between "Recent" and other introductory content. Special logic exists for `recent_sub` to show/hide extra navigation (`.datagrild_nav`).
*   **Data Grid Nav (`.datagrild_nav`):** Changes the layout class (e.g., vertical vs horizontal) for the episode list.

### 3.4. Login Form & Popups
*   **Submit Prevention:** Prevents double-submission on login forms by disabling the submit button for 3 seconds after clicking.
*   **Popup Validation:** Validates email format and password presence in `.login-popup`. Adds error styling to invalid fields and clears it on keypress.
*   **Modal Closing:** Clicking the mask (`.mask`) fades out any open modals (`.modal-close`).

### 3.5. Scroll to Top
*   **Trigger:** `.croll img` or `.croll i`.
*   **Behavior:** Smoothly animates the page scroll to the top (`scrollTop: 0`).

---

## 4. Global Functions

The file defines several global functions that are called by inline HTML or other scripts.

### `loadTopViews(obj, id)`
*   **Purpose:** Handles the "Day / Week / Month" tab switching in the sidebar.
*   **Behavior:**
    *   Previously, this fetched external analytics data.
    *   **Current State:** It acts as a UI stub. It switches the active tab class and toggles the visibility of the corresponding content blocks (`#load_topivews.views1`, etc.) which are now pre-rendered or static.

### `validateEmail(email)`
*   **Purpose:** Utility regex to validate email formats.
*   **Returns:** `true` if valid, `false` otherwise.

### `freload()`
*   **Purpose:** Reloads the current page. Used in error states (e.g., "Reload if video doesn't play").

### `closePoup()`
*   **Purpose:** Global helper to close generic popups/modals.

### `disabled(obj)`
*   **Purpose:** Utility to temporarily disable a button (for 1 second) to prevent rapid-fire clicks.

---

## 5. Layout & Miscellaneous

*   **Ads Positioning:** dynamically calculates `left` and `right` positions for side ads (`#left-side-ads`, `#right-side-ads`) based on window width, centering them around the main wrapper. Hides them if the screen is too narrow (< 1000px).
*   **Menu Highlighting:**
    *   **Active State:** Automatically adds the `active` class to the menu item corresponding to the current URL.
    *   **Hover Effects:** Adds a `seleted` class on mouseover for visual feedback.
*   **Chat:** Toggles the chat group body when the header is clicked.

## 6. Deprecation Notes

This version of `main.js` replaces an older, obfuscated version. Significant changes include:
*   **Removal of External AJAX:** Legacy functions like `LoadFilm` and `LoadFilmOngoing` (which fetched content from `cdn.gogocdn.net`) have been removed. The application now relies on server-side PHP pagination and rendering (e.g., in `home.php` and `anime-details.php`).
*   **Removal of Bookmark AJAX:** The legacy `ajaxBookmark` functions were removed. Bookmarking is now handled by specific page-level scripts or updated controllers (e.g., `toggleBookmark` in `anime-details.php`).

---
