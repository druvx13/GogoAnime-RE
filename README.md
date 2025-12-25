![GogoAnime Clone Logo](logos/(HIGH-RES)GogoAnime-RE.png)

# 🎌 GogoAnime Clone System — GogoAnime-RE
**OFFICIAL REPOSITORY DOCUMENTATION**  
A standalone vanilla PHP CMS for building anime streaming platforms with external video embedding.

---

## 📌 Table of Contents
1. [Project Identification](#1-project-identification)
2. [Purpose and Scope](#2-purpose-and-scope)
3. [System Summary](#3-system-summary)
4. [Key Capabilities](#4-key-capabilities)
5. [Technology Stack](#5-technology-stack)
6. [High-Level Architecture](#6-high-level-architecture)
7. [Directory Overview](#7-directory-overview)
8. [Installation & Setup Summary](#8-installation--setup-summary)
9. [🚀 Quick Setup (Recommended for Non-Technical Users)](#-quick-setup-recommended-for-non-technical-users)
10. [Configuration Overview](#10-configuration-overview)
11. [Administration Overview](#11-administration-overview)
12. [Security Model Summary](#12-security-model-summary)
13. [Operational Notes](#13-operational-notes)
14. [Maintenance Expectations](#14-maintenance-expectations)
15. [Limitations & Assumptions](#15-limitations-and-assumptions)
16. [License](#16-license)
17. [References](#17-references)

---

## 1. Project Identification
- **Project Name:** GogoAnime Clone System (GogoAnime-RE)
- **Repository Type:** Anime Streaming Web Application
- **Version:** 1.0.0
- **Status:** Active Development & Maintenance

---

## 2. Purpose and Scope
A comprehensive CMS-based streaming interface for listing anime titles, episodes, details, and embedded video players — with a full admin panel for content management.

---

## 3. System Summary
Designed for **LAMP/LEMP stack**: Linux, Apache/Nginx, MySQL, PHP  
Uses lightweight structure — no heavy dependencies.

---

## 4. Key Capabilities
- Anime Catalog & Metadata
- Multiple Episode Providers
- Bookmarks & Comments
- Genre / Status Filtering
- SEO-friendly URLs
- Full Admin CMS

---

## 5. Technology Stack
| Component | Technology |
|----------|------------|
| Language | PHP 7+ / PHP 8+ |
| Database | MySQL (Primary) / SQLite (Compatible PDO) |
| Server | Apache + `mod_rewrite` |
| Frontend | HTML5, CSS3, JavaScript |

---

## 6. High-Level Architecture
```ascii
[User Browsers]
      ↓
[Apache + .htaccess]
      ↓
 Frontend (Public UI) ---- Admin Panel (CRUD)
      ↓                           ↓
           [Database Layer → MySQL]
````

---

## 7. Directory Overview

* `/admin/` → Full CMS backend
* `/app/` → Core logic and configs
* `/assets/` → CSS/JS/Images
* `/genre/`, `/status/`, `/sub-category/` → Routing handlers
* `/staticHTML/` → Login & static pages

---

## 8. Installation & Setup Summary

1. Clone / download the repository
2. Create a MySQL database & import `database.sql` (root directory)
3. Update database credentials in:

   ```
   app/config/db.php
   ```
4. Ensure Apache `mod_rewrite` is enabled
5. Point Document Root to repository
6. Set writable permissions for `assets/uploads/`

Full install guide → *INSTALLATION.md*

---

## 🚀 Quick Setup (Recommended for Non-Technical Users)

> ⭐ This is the **fastest** way to get your site running!

1. **Delete** this file:

   ```
   app/config/config.local.php
   ```
2. **Open and edit**:

   ```
   app/config/db.php
   ```

   ➜ Add only your MySQL details
   (`host`, `dbname`, `username`, `password`)
3. **Import**:

   ```
   database.sql
   ```

   into your database
4. Upload the project to your hosting
5. Visit your admin login **directly**:

   ```
   https://www.yourweb.com/admin/login.php
   ```

**Default Admin Login**

* Email: **[admin@gogoanime.com](mailto:admin@gogoanime.com)**
* Password: **admin123**
>[!IMPORTANT]
> (MUST CHANGE password after login into admin panel from Users->Edit).

🎉 Voilà! Admin Panel is live — Add anime & enjoy your own streaming site!

---

## 10. Configuration Overview

Config is handled via:

* `app/config/db.php` — DB credentials
* `app/config/info.php` — Website metadata
* `app/app.json` — Deployment info

More detail → *CONFIGURATION.md*

---

## 11. Administration Overview

The Admin CMS supports:

* Anime / Genre / Episode Management
* User & Comment moderation
* SEO metadata editing

Access: `/admin/login.php`
Docs → *ADMIN_GUIDE.md*

---

## 12. Security Model Summary

* Hashed passwords (DB)
* Role-based Access Control
* SQL injection protection (PDO prepared statements)
* CSRF token checks in sensitive forms

---

## 13. Operational Notes

* Does *not* host video files — only embeds external links
* Ensure link freshness for optimal streaming

---

## 14. Maintenance Expectations

* Weekly DB backups recommended
* Audit episode links regularly
* Monitor PHP error logs

---

## 15. Limitations and Assumptions

| Area          | Note                           |
| ------------- | ------------------------------ |
| Video Hosting | No local hosting / transcoding |
| Emails        | No built-in verification       |
| Rewrites      | Requires `.htaccess` support   |

---

## 16. License

This software is **proprietary** unless specified otherwise in `LICENSE`.

---

## 17. References

* *ARCHITECTURE.md*
* *SYSTEM_OVERVIEW.md*
* *DIRECTORY_STRUCTURE.md*
* *DOCUMENTATION_INDEX.md*

---

### 💬 Need help customizing or installing?

Open an issue — contributions and improvements are welcome!

---
