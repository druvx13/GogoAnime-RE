# Directory Structure

**OFFICIAL DOCUMENTATION**

## Repository Root
```
GogoAnime-RE/
├── .github/               # CI/CD workflows
├── .htaccess              # Apache URL rewriting & environment settings
├── 404.php                # Custom 404 error page
├── anime-details.php      # Anime detail page (/category/{slug})
├── anime-list.php         # Full anime list
├── anime-list-az.php      # Alphabetical anime filter
├── anime-movies.php       # Movies listing
├── bookmark.php           # User bookmark management
├── contact-us.php         # Contact form handler
├── database.sql           # Database schema
├── genre.php              # Genre listing
├── google_callback.php    # Google OAuth callback
├── home.php               # Homepage
├── index.php              # Site entry / landing page
├── new-season.php         # Seasonal content listing
├── popular.php            # Popular anime listing
├── request.php            # Anime request form
├── search.php             # Search results
├── streaming.php          # Video player ({slug}-episode-{num})
├── CHANGELOG.md           # Version history
├── LICENSE                # License
├── README.md              # Project overview & quick-start
│
├── admin/                 # Admin CMS backend
│   ├── layout/            # Admin header/footer templates
│   ├── anime_add.php      # Add anime
│   ├── anime_edit.php     # Edit anime
│   ├── anime_list.php     # List anime
│   ├── episode_add.php    # Add episode
│   ├── episode_edit.php   # Edit episode
│   ├── episode_list.php   # List episodes
│   ├── genre_add.php      # Add genre
│   ├── genre_list.php     # List genres
│   ├── country_add.php    # Add country
│   ├── country_list.php   # List countries
│   ├── season_add.php     # Add season
│   ├── season_list.php    # List seasons
│   ├── type_add.php       # Add type
│   ├── type_list.php      # List types
│   ├── provider_add.php   # Add video provider
│   ├── provider_edit.php  # Edit video provider
│   ├── provider_list.php  # List video providers
│   ├── users.php          # User management
│   ├── user_add.php       # Add user
│   ├── user_edit.php      # Edit user
│   ├── messages.php       # Contact form messages
│   ├── request_list.php   # Anime request list
│   ├── ads_config.php     # Ad configuration
│   ├── google_config.php  # Google OAuth config
│   ├── comments.php       # Comment moderation
│   ├── auth.php           # Admin auth guard (include)
│   ├── login.php          # Admin login
│   └── logout.php         # Admin logout
│
├── app/                   # Core application logic
│   ├── config/
│   │   ├── db.php         # Database connection (PDO)
│   │   ├── csrf.php       # CSRF token utilities
│   │   ├── info.php       # Site-wide settings ($base_url, $website_name)
│   │   └── config.local.php  # Local overrides (not committed)
│   ├── controllers/
│   │   ├── bookmark.php   # Bookmark AJAX endpoint
│   │   └── logout.php     # User logout handler
│   ├── helpers/
│   │   └── pagination_helper.php  # Pagination renderer class
│   └── views/
│       └── partials/
│           ├── header.php          # Site header (nav, search)
│           ├── footer.php          # Site footer
│           ├── recentRelease.php   # Recent releases widget
│           ├── genre.html          # Genre list widget
│           ├── sidebar_genre.htm   # Sidebar genre block
│           ├── sub-category.html   # Sub-category links
│           └── advertisements/
│               └── popup.html      # Ad popup partial
│
├── assets/                # Web-accessible static resources
│   ├── css/
│   │   ├── style.css       # Main stylesheet
│   │   ├── user.css        # User profile styles
│   │   ├── user_auth.css   # Login/register styles
│   │   └── responsive.css  # Responsive/mobile overrides
│   ├── js/
│   │   ├── main.js         # Core UI interactions
│   │   ├── streaming.js    # Streaming page logic
│   │   ├── category.js     # Category page logic
│   │   ├── user.js         # User page logic
│   │   ├── libraries/
│   │   │   └── jquery.js   # jQuery 3.7.1
│   │   └── files/
│   │       ├── combo.js
│   │       ├── video.js
│   │       ├── jquery.tinyscrollbar.min.js
│   │       └── jqueryTooltip.js
│   ├── img/
│   │   ├── logos/          # Project logos
│   │   ├── icon/           # SVG icons
│   │   ├── bg/             # Background images
│   │   ├── favicon.ico
│   │   └── logo.png / logo.svg
│   ├── fonts/              # Web fonts (MyriadPro)
│   ├── uploads/
│   │   ├── covers/         # Anime cover images
│   │   └── videos/         # Video file placeholder
│   └── readme-images/      # Screenshots used in README
│
├── docs/                  # All project documentation
│   ├── screenshots/        # App screenshots
│   ├── DOCUMENTATION_INDEX.md
│   ├── ARCHITECTURE.md
│   ├── SYSTEM_OVERVIEW.md
│   ├── INSTALLATION.md
│   ├── CONFIGURATION.md
│   ├── DEPLOYMENT.md
│   ├── ADMIN_GUIDE.md
│   ├── MAINTENANCE.md
│   ├── TROUBLESHOOTING.md
│   ├── SECURITY.md
│   ├── SECURITY_DISCLOSURE.md
│   ├── DIRECTORY_STRUCTURE.md
│   ├── DATA_FLOW.md
│   ├── GLOSSARY.md
│   ├── NOTICE.md
│   └── VERSIONING_POLICY.md
│
├── genre/
│   └── id.php             # /genre/{slug} handler
│
├── pages/                 # User-facing static/auth pages
│   ├── login.php          # Login page (/login.html)
│   ├── register.php       # Registration (/register.html)
│   ├── forget.php         # Password recovery (/forget.html)
│   ├── about-us.php       # About page (/about-us.html)
│   ├── contact-us.php     # Contact page (/contact-us.html)
│   ├── privacy.php        # Privacy policy (/privacy.html)
│   ├── terms.php          # Terms of service (/terms.html)
│   └── user.php           # User profile (/user.html)
│
├── status/
│   ├── completed.php      # Completed anime list
│   └── ongoing.php        # Ongoing anime list
│
└── sub-category/
    └── id.php             # /sub-category/{slug} handler
```

## URL Routing
Apache `.htaccess` maps clean URLs to PHP files:

| Clean URL | PHP File |
|-----------|----------|
| `/home` | `home.php` |
| `/search?keyword=X` | `search.php` |
| `/category/{slug}` | `anime-details.php` |
| `/{slug}-episode-{n}` | `streaming.php` |
| `/genre/{slug}` | `genre/id.php` |
| `/sub-category/{slug}` | `sub-category/id.php` |
| `/anime-list-{char}` | `anime-list-az.php` |
| `/login.html` | `pages/login.php` |
| `/register.html` | `pages/register.php` |
| `/user.html` | `pages/user.php` |
| `/terms.html` | `pages/terms.php` |
| `/privacy.html` | `pages/privacy.php` |
| `/about-us.html` | `pages/about-us.php` |
| `/contact-us.html` | `pages/contact-us.php` |
| `/forget.html` | `pages/forget.php` |
