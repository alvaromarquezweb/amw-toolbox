<?php
/**
 * Plugin Name: AMW Toolbox
 * Plugin URI:  https://alvaromarquezweb.com
 * Description: Cleanup and hardening toolbox for WordPress: hides the WP fingerprint, disables comments, trims the &lt;head&gt;, tightens a few defaults and fixes the Divi viewport. Everything is toggleable from Settings &rarr; AMW Toolbox.
 * Version:     1.3.1
 * Author:      Álvaro Márquez Díaz
 * Author URI:  https://alvaromarquezweb.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: amw-toolbox
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// ── SELF-UPDATE FROM GITHUB ─────────────────────────────────────────
require plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$amwUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/alvaromarquezweb/amw-toolbox/',
	__FILE__,
	'amw-toolbox'
);
$amwUpdateChecker->setBranch( 'main' );
// Public repository: PUC needs no authentication (no setAuthentication).


// ── CONSTANTS ───────────────────────────────────────────────────────
// Single source of truth: read the version from the plugin header above, so the
// constant can never drift from it. To release, bump ONLY the header line.
define( 'AMW_TOOLBOX_VERSION',  get_file_data( __FILE__, [ 'Version' => 'Version' ] )['Version'] ?: '0.0.0' );
define( 'AMW_TOOLBOX_DIR',      plugin_dir_path( __FILE__ ) );
define( 'AMW_TOOLBOX_URL',      plugin_dir_url( __FILE__ ) );
define( 'AMW_TOOLBOX_BASENAME', plugin_basename( __FILE__ ) );


// ── TEXT DOMAIN ─────────────────────────────────────────────────────
add_action( 'init', 'amw_toolbox_load_textdomain' );

function amw_toolbox_load_textdomain() {
	load_plugin_textdomain(
		'amw-toolbox',
		false,
		dirname( AMW_TOOLBOX_BASENAME ) . '/languages'
	);
}


// ── MODULES ─────────────────────────────────────────────────────────
require_once AMW_TOOLBOX_DIR . 'includes/options.php'; // Defaults, storage, sanitising.
require_once AMW_TOOLBOX_DIR . 'includes/cleanup.php'; // The tweaks, each gated by its option.

if ( is_admin() ) {
	require_once AMW_TOOLBOX_DIR . 'includes/settings-page.php'; // Settings → AMW Toolbox.
}
