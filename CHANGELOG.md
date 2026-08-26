# Changelog
All notable changes to AMW Toolbox are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project follows [Semantic Versioning](https://semver.org/).

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

[1.3.1]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.1
[1.3.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.3.0
[1.2.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.2.0
[1.1.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.1.0
[1.0.0]: https://github.com/alvaromarquezweb/amw-toolbox/releases/tag/v1.0.0
