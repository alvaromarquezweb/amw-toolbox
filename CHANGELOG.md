# Changelog
All notable changes to AMW Toolbox are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/).

## [1.4.2] - 2026-10-25
### Added
* Admin experience: option to block the dashboard for logged-in users without editing permission. When enabled, users who cannot `edit_posts` (subscribers and custom roles such as a bookings manager) are redirected out of `/wp-admin` to the front page, including the profile screen. Administrators and editors are unaffected. AJAX (`admin-ajax.php`), `admin-post.php` form handlers and WP-Cron are never blocked, and logged-out visitors still reach the login screen. Off by default. The redirect target can be changed per role with the `amw_toolbox_dashboard_redirect` filter.

## [1.4.1] - 2026-10-13
### Added
* Divi: option to disable the Divi "Projects" (portfolio) post type and its categories/tags when unused, removing it from the admin and its public URLs. Off by default; enable only on sites that do not use the Divi portfolio.
* Overlap notes: options that a detected builder also handles (Divi emojis and jQuery Migrate; Elementor usage tracking and default styles) now show a short note recommending where to manage each, so the same thing is not toggled in two places.

## [1.4.0] - 2026-10-11
### Added
* WPBakery detection: the WPBakery tab now appears when WPBakery Page Builder is active (no options of its own yet; it is ready for future WPBakery-specific features). WPBakery is also shown in the detected-frameworks badge.

### Changed
* The "Plugin" and "Tools" tabs are now a single "Tools" tab, grouping the plugin's own options and the import/export/reset/transients actions in one place.

### Removed
* Divi: the "Fix Divi's deprecated Maps API call" option has been removed.

## [1.3.5] - 2026-10-10
### Fixed
* "Remove the ?ver query string" no longer breaks front-end page builders. It is now skipped while the Divi Visual Builder (and Elementor or WPBakery) is editing or previewing, so the builder keeps loading normally.

## [1.3.4] - 2026-10-03
### Added
* The admin bar and dashboard widget lists are now detected dynamically, like the admin menu list, so they show the items that actually exist on each site (including those added by other plugins). Dashboard widgets are learned the first time you open the Dashboard.

## [1.3.3] - 2026-10-03
### Added
* SVG uploads: optional, administrators-only SVG support in the media library (enables the file type without sanitizing), with a clear security warning on the setting.

## [1.3.2] - 2026-09-28
### Added
* Elementor: option to disable Google Fonts, so Elementor stops loading fonts from Google on the front end.
* New "Plugin" tab grouping AMW Toolbox's own options.

### Changed
* "Keep settings on uninstall" moved from the Admin tab to the new Plugin tab.
* The "Import settings" button now appears only once a file has been selected.

### Fixed
* The admin menu list no longer includes items the current user cannot access, removing ghost entries (such as Links) that were never actually shown.

## [1.3.1] - 2026-08-26
### Added
* Divi: optional fix for the deprecated google.maps.event.addDomListener used by Divi's Map modules, so maps keep working without the deprecation error. Off by default, in the Divi tab.

## [1.3.0] - 2026-08-26
### Added
* XML-RPC hardening: keep XML-RPC on but remove its most-abused methods (pingbacks and system.multicall) and the X-Pingback header. A finer alternative to disabling XML-RPC entirely.
* Permissions-Policy header: optionally send a restrictive policy that disables camera, microphone and geolocation across the whole site.
* Custom admin footer: replace the "Thank you for creating with WordPress" text with your own (basic HTML and links allowed), or leave it blank.

### Removed
* Generic login error message option, now handled by AMW Simple Login.

## [1.2.0] - 2026-08-21
### Added
* Generic login errors: optionally replace the login failure message with a neutral one, so it no longer reveals whether the username or the password was wrong.
* HSTS header: optionally send Strict-Transport-Security (one year, including subdomains). Only sent over HTTPS; enable only on sites fully served over HTTPS.
* Option to disable the periodic admin email verification screen.
* Option to disable remote block patterns, so WordPress stops fetching patterns from the wp.org directory.
* Auto-empty trash: choose how many days trashed items are kept before being permanently deleted (uses EMPTY_TRASH_DAYS; 0 disables the trash).
* Delete expired transients: a one-click tool to clear expired transient rows from the options table.
* All new options are disabled by default.

## [1.1.0] - 2026-08-17
### Added
* Import and export settings as a JSON file, with imported values validated against the known options.
* Reset all settings to their defaults in one click.
* Option to keep settings on uninstall, for a future reinstall.
* Elementor tab (shown only when Elementor is active) with options to disable its usage tracking and its default colors and fonts.
* Detected-frameworks badge and an active-optimizations counter in the settings header.

### Changed
* The Divi tab now appears only when Divi is active, matching the WooCommerce tab. Framework toggles are preserved when their tab is hidden.

## [1.0.0] - 2026-08-10
### Added
* WordPress administration cleanup and customization options.
* Comment, trackback and pingback management.
* WordPress security and hardening options.
* WordPress performance and frontend cleanup options.
* Divi-specific optimization options.
* WooCommerce cleanup and optimization options.
* Centralized settings page under **Settings → AMW Toolbox**.
* Spanish translation support.
* GitHub-based automatic update support.
* Complete uninstall routine.
* Multisite uninstall support.
* All features disabled by default to prevent unexpected changes to existing installations.

[1.4.2]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.4.2
[1.4.1]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.4.1
[1.4.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.4.0
[1.3.5]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.5
[1.3.4]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.4
[1.3.3]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.3
[1.3.2]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.2
[1.3.1]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.1
[1.3.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.0
[1.2.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.2.0
[1.1.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.1.0
[1.0.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.0.0
