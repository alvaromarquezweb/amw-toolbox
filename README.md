# AMW Toolbox

A lightweight WordPress toolbox for **cleanup, hardening, performance optimization and client-site management**.

AMW Toolbox brings together a collection of optional WordPress tweaks that can normally require several snippets, plugins or manual configuration.

Every feature is **disabled by default**. The plugin does nothing until an option is explicitly enabled.

## Features

### Administration

Customize and simplify the WordPress administration area:

* Hide selected admin menu items
* Hide selected admin bar elements
* Hide dashboard widgets
* Hide admin notices from users who cannot manage options
* Hide the admin bar on the front end for non-administrators
* Hide the WordPress welcome panel
* Disable the block-based widgets editor
* Hide the default theme recommendation from Site Health

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
* Clean unnecessary tags from the `<head>`
* Disable XML-RPC
* Disable the built-in theme/plugin file editor
* Block public user enumeration
* Disable WordPress Application Passwords

> These options are intentionally independent. Enable only the protections that are appropriate for your website.

### Performance & Cleanup

Reduce unnecessary WordPress resources and behavior:

* Disable or slow down the Heartbeat API
* Limit or disable post revisions
* Remove Gutenberg/block editor CSS from the front end
* Disable Dashicons for logged-out visitors
* Disable oEmbed
* Disable automatic scaling of very large images
* Prevent selected intermediate image sizes from being generated
* Disable WordPress emojis
* Remove jQuery Migrate from the front end
* Remove `?ver=` query strings from CSS and JavaScript URLs

### Divi

Specific optimization for websites using **Elegant Themes Divi**:

* Replace Divi's viewport configuration with a standard responsive viewport that allows user zooming.

The Divi functionality is optional and has no effect when disabled.

### WooCommerce

When WooCommerce is active, AMW Toolbox provides additional options:

* Disable WooCommerce Analytics
* Disable WooCommerce promotional/admin ads
* Disable marketplace suggestions
* Disable WooCommerce usage tracking
* Hide the WooCommerce Extensions/Marketplace menu
* Load WooCommerce front-end styles only on store-related pages

WooCommerce options are only displayed when WooCommerce is installed and active.

## Philosophy

AMW Toolbox follows a simple principle:

> **Do not change anything unless the administrator explicitly enables it.**

The plugin is shipped with all options disabled.

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

Options are organized into logical sections so individual features can be enabled or disabled independently.

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

### Post revisions

Revision settings affect how WordPress stores revisions from that point forward. Existing revisions are not automatically deleted by this option.

## Uninstallation

AMW Toolbox includes an uninstall routine.

When the plugin is permanently deleted from WordPress, its stored configuration is removed from the database.

On multisite installations, the uninstall routine removes the plugin options from all sites in the network.

## Automatic Updates

AMW Toolbox uses the **Plugin Update Checker** library to check for updates from this GitHub repository.

The plugin tracks the `main` branch for updates.

## Requirements

* WordPress
* PHP version compatible with the installed WordPress version
* WooCommerce is optional
* Divi is optional

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
