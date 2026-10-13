# AMW Toolbox

A lightweight WordPress toolbox for **cleanup, hardening, performance optimization and client-site management**.

AMW Toolbox brings together a collection of optional WordPress tweaks that would normally require several snippets, plugins or manual configuration.

Every feature is **disabled by default**. The plugin does nothing until an option is explicitly enabled, and the whole settings panel is available to administrators only.

## Features

### Administration

Customize and simplify the WordPress administration area:

* Hide selected admin menu items (the list is detected from the real menu, and items the current user cannot access are left out)
* Hide selected admin bar elements (detected from the live admin bar)
* Hide dashboard widgets (the list is learned from your own Dashboard, so widgets added by other plugins appear too)
* Hide admin notices from users who cannot manage options
* Hide the admin bar on the front end for non-administrators
* Hide the WordPress welcome panel
* Disable the block-based widgets editor
* Hide the default theme recommendation from Site Health
* Disable the periodic "Is this admin email still correct?" verification screen
* Replace the admin footer text ("Thank you for creating with WordPress") with your own
* Allow SVG uploads for administrators only (see the security note below)

### Comments

* Completely disable WordPress comments
* Disable trackbacks and pingbacks
* Remove the Comments menu from the administration
* Prevent access to the comments management screen

### Security & Hardening

Optional security and privacy-related tweaks:

* Hide the WordPress version
* Remove the `X-Powered-By` header
* Add `X-Content-Type-Options: nosniff`
* Add `X-Frame-Options: SAMEORIGIN`
* Add a restrictive `Referrer-Policy`
* Add `Strict-Transport-Security` (HSTS), sent only over HTTPS
* Add a restrictive `Permissions-Policy` (disables camera, microphone and geolocation site-wide)
* Clean unnecessary tags from the `<head>`
* Disable XML-RPC
* Harden XML-RPC without disabling it, by removing the most-abused methods (pingbacks and `system.multicall`) and the `X-Pingback` header
* Disable the built-in theme/plugin file editor
* Block public user enumeration
* Disable WordPress Application Passwords

> These options are intentionally independent. Enable only the protections that are appropriate for your website.

### Performance & Cleanup

Reduce unnecessary WordPress resources and behavior:

* Disable or slow down the Heartbeat API
* Limit or disable post revisions, with a manual tool to delete revisions already stored
* Choose how many days trashed items are kept before being permanently deleted
* Remove Gutenberg/block editor CSS from the front end
* Disable Dashicons for logged-out visitors
* Disable oEmbed
* Disable automatic scaling of very large images
* Prevent selected intermediate image sizes from being generated
* Disable WordPress emojis
* Remove jQuery Migrate from the front end
* Disable remote block patterns (stops WordPress fetching patterns from the wp.org directory)
* Remove `?ver=` query strings from CSS and JavaScript URLs (left alone while a front-end page builder is editing, so the builder keeps working)

### Divi

Specific options for websites using **Elegant Themes Divi** (shown only when Divi is active):

* Replace Divi's viewport configuration with a standard responsive viewport that allows user zooming
* Disable the Divi "Projects" (portfolio) post type and its categories/tags when unused, removing it from the admin and its public URLs

### WooCommerce

When WooCommerce is active, AMW Toolbox provides additional options:

* Disable WooCommerce Analytics
* Disable WooCommerce promotional/admin ads
* Disable marketplace suggestions
* Disable WooCommerce usage tracking
* Hide the WooCommerce Extensions/Marketplace menu
* Load WooCommerce front-end styles only on store-related pages

### Elementor

When Elementor is active, AMW Toolbox provides additional options:

* Disable Elementor usage tracking
* Disable Elementor's default colors and fonts, so the theme controls styling
* Disable Elementor's Google Fonts on the front end (better privacy and fewer external calls)

### WPBakery

The WPBakery tab appears when WPBakery Page Builder is active. It currently has no options of its own and is in place so WPBakery-specific features have a home in a future version.

> Framework options (Divi, WooCommerce, Elementor, WPBakery) are only displayed when the matching framework is installed and active. The settings header shows which frameworks were detected and how many optimizations are currently enabled.

### Tools

* Export the current configuration as a JSON file
* Import a configuration from a JSON file (validated against the known options before saving)
* Reset all settings to their defaults
* Delete expired transients left in the options table
* Keep settings on uninstall (so a future reinstall starts from your configuration)

### Overlap notes

When an option is also handled natively by a detected page builder (for example disabling emojis or managing jQuery Migrate in Divi, or usage tracking and default styles in Elementor), AMW Toolbox shows a short note recommending where to manage it, so the same thing is not toggled in two places.

## Philosophy

AMW Toolbox follows a simple principle:

> **Do not change anything unless the administrator explicitly enables it.**

The plugin ships with all options disabled, and it avoids duplicating what a page builder or theme already does well, pointing you to the better place instead.

This makes it suitable for development environments, client websites and custom WordPress installations where unnecessary functionality needs to be removed without adding a large collection of plugins.

## Installation

### From GitHub

1. Download or clone the repository.
2. Upload the `amw-toolbox` folder to:

```text
/wp-content/plugins/
```

3. Activate **AMW Toolbox** from **Plugins → Installed Plugins**.
4. Go to:

```text
Settings → AMW Toolbox
```

5. Enable the features you need.

### Using Git

```bash
git clone https://github.com/alvaromarquezweb/amw-toolbox.git
```

Then place the plugin directory inside:

```text
wp-content/plugins/
```

## Configuration

After activation, the plugin adds its configuration panel under:

**Settings → AMW Toolbox**

Options are organized into tabs so individual features can be enabled or disabled independently. The panel and all of its actions are restricted to administrators.

The plugin stores its configuration in the WordPress option:

```text
amw_toolbox_options
```

## Important considerations

Some options can affect website functionality and should be tested before enabling them on a production website.

### Heartbeat

Disabling the Heartbeat API also disables functionality such as:

* Autosave
* Post locking
* Other features that depend on Heartbeat requests

### jQuery Migrate

Removing jQuery Migrate can break older themes or plugins that rely on deprecated jQuery functionality.

### Block editor CSS

Removing WordPress block CSS can affect pages or content that use Gutenberg/core blocks.

### WooCommerce styles

Conditional WooCommerce styles should be tested carefully on custom pages, especially when WooCommerce components are used outside standard shop, product, cart, checkout or account pages.

### Application Passwords

Disabling Application Passwords can affect integrations and external applications that use the WordPress REST API with this authentication method.

### Post revisions and trash

Revision and trash settings affect how WordPress behaves from that point forward. Existing revisions are not deleted automatically by the revision setting; use the manual tool for that.

### HSTS

Enable `Strict-Transport-Security` only on sites served fully over HTTPS. A wrong setup can make the site unreachable in browsers for a while. The header is only sent on HTTPS requests.

### SVG uploads

This option permits SVG and SVGZ uploads for administrators only. It fixes WordPress's file-type check but does **not** sanitize the file contents, so only enable it if you upload SVGs from sources you trust.

### Divi Projects

Disabling the Divi Projects post type removes it and its public URLs. Enable it only on sites that do not use the Divi portfolio.

## Uninstallation

AMW Toolbox includes an uninstall routine.

When the plugin is permanently deleted from WordPress, its stored configuration is removed from the database, unless "Keep settings on uninstall" is enabled.

On multisite installations, the uninstall routine removes the plugin options from all sites in the network.

## Automatic Updates

AMW Toolbox uses the **Plugin Update Checker** library to check for updates from this GitHub repository.

The plugin tracks the `main` branch for updates.

## Requirements

* WordPress
* PHP version compatible with the installed WordPress version
* WooCommerce, Divi, Elementor and WPBakery are all optional

## Compatibility

AMW Toolbox is designed to work as a collection of independent WordPress tweaks.

Because some options modify WordPress core behavior, front-end assets or third-party plugin functionality, compatibility depends on the configuration enabled for each website.

Always test configuration changes before applying them to a production site.

## License

AMW Toolbox is licensed under the **GNU General Public License v2.0 or later**.

See [`LICENSE`](LICENSE) for the complete license text.

## Author

**Álvaro Márquez Díaz**

* Website: https://alvaromarquezweb.com
* GitHub: https://github.com/alvaromarquezweb

---

### Disclaimer

AMW Toolbox is intended for administrators and developers who understand the effects of the options they enable.

The plugin provides configuration switches for potentially disruptive WordPress changes. The administrator is responsible for testing and validating the selected configuration on each website.
