<?php
/**
 * AMW Toolbox — Cleanup: every tweak, each one gated behind its own option.
 *
 * All hook callbacks are named functions (amw_toolbox_*) rather than closures,
 * so they can be located, debugged and removed with remove_action()/remove_filter().
 * Callbacks read the (request-cached) options via amw_toolbox_get_options().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$amw_toolbox_o = amw_toolbox_get_options();


/* ═══════════════════════════════════════════════════════════════════════════
   1. ADMIN AREA — hide selected items
   ═══════════════════════════════════════════════════════════════════════════ */

if ( ! empty( $amw_toolbox_o['hidden_menus'] ) ) {
	add_action( 'admin_menu', 'amw_toolbox_hide_admin_menus', 9999 );
}

if ( ! empty( $amw_toolbox_o['hidden_adminbar'] ) ) {
	add_action( 'wp_before_admin_bar_render', 'amw_toolbox_hide_admin_bar' );
}

if ( ! empty( $amw_toolbox_o['hidden_dashboard'] ) ) {
	add_action( 'wp_dashboard_setup', 'amw_toolbox_hide_dashboard_widgets' );
}

if ( $amw_toolbox_o['hide_notices_for_clients'] ) {
	add_action( 'admin_head', 'amw_toolbox_hide_admin_notices', 1 );
}

if ( $amw_toolbox_o['hide_admin_bar_front'] ) {
	add_filter( 'show_admin_bar', 'amw_toolbox_admin_bar_front' );
}

if ( $amw_toolbox_o['hide_welcome_panel'] ) {
	add_action( 'admin_init', 'amw_toolbox_hide_welcome_panel' );
}

if ( $amw_toolbox_o['disable_block_widgets'] ) {
	add_filter( 'use_widgets_block_editor', '__return_false' );
	add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
}

if ( $amw_toolbox_o['hide_default_theme_notice'] ) {
	add_filter( 'site_status_tests', 'amw_toolbox_hide_default_theme_notice' );
}

function amw_toolbox_hide_admin_menus() {
	foreach ( amw_toolbox_get_options()['hidden_menus'] as $slug ) {
		remove_menu_page( $slug );
	}
}

function amw_toolbox_hide_admin_bar() {
	global $wp_admin_bar;
	foreach ( amw_toolbox_get_options()['hidden_adminbar'] as $node ) {
		$wp_admin_bar->remove_node( $node );
	}
}

function amw_toolbox_hide_dashboard_widgets() {
	$map = amw_toolbox_dashboard_widgets();
	foreach ( amw_toolbox_get_options()['hidden_dashboard'] as $id ) {
		$context = isset( $map[ $id ][1] ) ? $map[ $id ][1] : 'normal';
		remove_meta_box( $id, 'dashboard', $context );
	}
}

// Hide every admin notice for users who cannot manage options (i.e. clients).
function amw_toolbox_hide_admin_notices() {
	if ( current_user_can( 'manage_options' ) ) {
		return;
	}
	remove_all_actions( 'admin_notices' );
	remove_all_actions( 'all_admin_notices' );
}

// Hide the front-end admin bar for everyone except administrators.
function amw_toolbox_admin_bar_front( $show ) {
	return current_user_can( 'manage_options' ) ? $show : false;
}

// Remove the "Welcome to WordPress" panel from the dashboard.
function amw_toolbox_hide_welcome_panel() {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}

// Remove the Site Health "have a default theme available" recommendation by
// dropping the core theme_version test (Tools -> Site Health).
function amw_toolbox_hide_default_theme_notice( $tests ) {
	unset( $tests['direct']['theme_version'] );
	return $tests;
}


/* ═══════════════════════════════════════════════════════════════════════════
   2. DISABLE COMMENTS
   ═══════════════════════════════════════════════════════════════════════════ */

if ( $amw_toolbox_o['disable_comments'] ) {
	add_action( 'admin_init', 'amw_toolbox_disable_comments_admin' );
	add_action( 'admin_menu', 'amw_toolbox_remove_comments_menu' );
	add_filter( 'comments_open',  '__return_false',       20 );
	add_filter( 'pings_open',     '__return_false',       20 );
	add_filter( 'comments_array', '__return_empty_array', 10 );
}

function amw_toolbox_disable_comments_admin() {
	global $pagenow;

	// Redirect any attempt to open the comments screen.
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}

	// Remove comment and ping support from every post type.
	foreach ( get_post_types() as $type ) {
		if ( post_type_supports( $type, 'comments' ) ) {
			remove_post_type_support( $type, 'comments' );
			remove_post_type_support( $type, 'trackbacks' );
		}
	}
}

function amw_toolbox_remove_comments_menu() {
	remove_menu_page( 'edit-comments.php' );
}


/* ═══════════════════════════════════════════════════════════════════════════
   3. HEAD, HTTP HEADERS & HARDENING
   ═══════════════════════════════════════════════════════════════════════════ */

// Hide the WordPress version from the <head>, RSS feeds and HTTP headers.
if ( $amw_toolbox_o['hide_wp_version'] ) {
	add_filter( 'the_generator', '__return_empty_string' );
	remove_action( 'wp_head', 'wp_generator' );
}

// A single send_headers callback handles the X-Powered-By removal and the
// optional security headers, so send_headers is only hooked once.
if ( $amw_toolbox_o['remove_powered_by'] || $amw_toolbox_o['header_nosniff'] || $amw_toolbox_o['header_frame'] || $amw_toolbox_o['header_referrer'] ) {
	add_action( 'send_headers', 'amw_toolbox_http_headers' );
}

function amw_toolbox_http_headers() {
	if ( headers_sent() ) {
		return;
	}

	$o = amw_toolbox_get_options();

	if ( $o['remove_powered_by'] ) {
		header_remove( 'X-Powered-By' );
	}
	if ( $o['header_nosniff'] ) {
		header( 'X-Content-Type-Options: nosniff' );
	}
	if ( $o['header_frame'] ) {
		header( 'X-Frame-Options: SAMEORIGIN' );
	}
	if ( $o['header_referrer'] ) {
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	}
}

// Remove unnecessary tags WordPress adds to the <head> by default.
if ( $amw_toolbox_o['clean_head_tags'] ) {
	remove_action( 'wp_head', 'rsd_link' );                            // RSD
	remove_action( 'wp_head', 'wlwmanifest_link' );                    // Windows Live Writer
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );                // Shortlink
	remove_action( 'wp_head', 'feed_links', 2 );                       // Feeds (posts)
	remove_action( 'wp_head', 'feed_links_extra', 3 );                 // Feeds (extra)
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 ); // Prev/Next
}

// Disable XML-RPC.
if ( $amw_toolbox_o['disable_xmlrpc'] ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
}

// Prevent editing themes/plugins from the admin. wp-config.php is the canonical
// place for this constant; it is defined here only as a convenience and only if
// it is not already defined, so a wp-config setting always wins.
if ( $amw_toolbox_o['disallow_file_edit'] && ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

// Block user enumeration: ?author=N scans and the public REST users endpoints.
if ( $amw_toolbox_o['block_user_enumeration'] ) {
	add_action( 'init', 'amw_toolbox_block_author_scan' );
	add_filter( 'rest_endpoints', 'amw_toolbox_block_rest_user_enum' );
}

function amw_toolbox_block_author_scan() {
	if ( is_admin() || ! isset( $_GET['author'] ) ) {
		return;
	}
	$author = sanitize_text_field( wp_unslash( $_GET['author'] ) );
	if ( '' !== $author && ctype_digit( $author ) ) {
		wp_safe_redirect( home_url(), 301 );
		exit;
	}
}

function amw_toolbox_block_rest_user_enum( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	unset( $endpoints['/wp/v2/users'] );
	unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	return $endpoints;
}

// Disable Application Passwords.
if ( $amw_toolbox_o['disable_app_passwords'] ) {
	add_filter( 'wp_is_application_passwords_available', '__return_false' );
}


/* ═══════════════════════════════════════════════════════════════════════════
   4. PERFORMANCE
   ═══════════════════════════════════════════════════════════════════════════ */

// Heartbeat API: disable completely or slow down to ~60s.
// Disabling also turns off autosave and post locking while editing.
if ( 'off' === $amw_toolbox_o['heartbeat_mode'] ) {
	add_action( 'init', 'amw_toolbox_deregister_heartbeat', 1 );
} elseif ( 'slow' === $amw_toolbox_o['heartbeat_mode'] ) {
	add_filter( 'heartbeat_settings', 'amw_toolbox_slow_heartbeat' );
}

function amw_toolbox_deregister_heartbeat() {
	wp_deregister_script( 'heartbeat' );
}

function amw_toolbox_slow_heartbeat( $settings ) {
	$settings['interval'] = 60;
	return $settings;
}

// Post revisions: cap or disable how many WordPress keeps from now on.
// wp-config.php is the canonical place; defined here only if not already set.
if ( ! defined( 'WP_POST_REVISIONS' ) ) {
	if ( 'disable' === $amw_toolbox_o['revisions_mode'] ) {
		define( 'WP_POST_REVISIONS', false );
	} elseif ( 'limit' === $amw_toolbox_o['revisions_mode'] ) {
		define( 'WP_POST_REVISIONS', max( 0, (int) $amw_toolbox_o['revisions_limit'] ) );
	}
}

// Remove the front-end block editor (Gutenberg) CSS.
if ( $amw_toolbox_o['remove_block_css'] ) {
	add_action( 'wp_enqueue_scripts', 'amw_toolbox_dequeue_block_css', 100 );
}

function amw_toolbox_dequeue_block_css() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}

// Disable Dashicons on the front for logged-out visitors (kept for the admin bar
// when logged in).
if ( $amw_toolbox_o['disable_dashicons'] ) {
	add_action( 'wp_enqueue_scripts', 'amw_toolbox_dequeue_dashicons', 100 );
}

function amw_toolbox_dequeue_dashicons() {
	if ( is_user_logged_in() ) {
		return;
	}
	wp_dequeue_style( 'dashicons' );
	wp_deregister_style( 'dashicons' );
}

// Disable oEmbed: drop the discovery links, stop auto-discovery and remove the
// front-end wp-embed.js.
if ( $amw_toolbox_o['disable_oembed'] ) {
	add_action( 'init', 'amw_toolbox_disable_oembed', 9999 );
	add_action( 'wp_footer', 'amw_toolbox_dequeue_wp_embed' );
}

function amw_toolbox_disable_oembed() {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	add_filter( 'embed_oembed_discover', '__return_false' );
}

function amw_toolbox_dequeue_wp_embed() {
	wp_deregister_script( 'wp-embed' );
}

// Disable WordPress's automatic scaling of very large image uploads (the
// "-scaled" versions created above big_image_size_threshold).
if ( $amw_toolbox_o['disable_big_image_scaling'] ) {
	add_filter( 'big_image_size_threshold', '__return_false' );
}

// Skip generating the chosen intermediate image sizes on upload (existing
// images are not affected).
if ( ! empty( $amw_toolbox_o['disabled_image_sizes'] ) ) {
	add_filter( 'intermediate_image_sizes_advanced', 'amw_toolbox_disable_image_sizes' );
}

function amw_toolbox_disable_image_sizes( $sizes ) {
	foreach ( amw_toolbox_get_options()['disabled_image_sizes'] as $name ) {
		unset( $sizes[ $name ] );
	}
	return $sizes;
}

// Disable emojis everywhere: front + admin scripts/styles, the TinyMCE plugin,
// the feed/email filters, the SVG URL and the s.w.org dns-prefetch.
if ( $amw_toolbox_o['disable_emojis'] ) {
	add_action( 'init', 'amw_toolbox_disable_emojis' );
}

// Remove jQuery Migrate from the front-end jQuery bundle.
if ( $amw_toolbox_o['remove_jquery_migrate'] ) {
	add_action( 'wp_default_scripts', 'amw_toolbox_remove_jquery_migrate' );
}

function amw_toolbox_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'amw_toolbox_disable_emojis_tinymce' );
	add_filter( 'wp_resource_hints', 'amw_toolbox_disable_emojis_dns_prefetch', 10, 2 );
	add_filter( 'emoji_svg_url', '__return_false' );
}

function amw_toolbox_disable_emojis_tinymce( $plugins ) {
	return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
}

function amw_toolbox_disable_emojis_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		foreach ( $urls as $key => $url ) {
			if ( is_string( $url ) && false !== strpos( $url, 's.w.org/images/core/emoji/' ) ) {
				unset( $urls[ $key ] );
			}
		}
	}
	return $urls;
}

function amw_toolbox_remove_jquery_migrate( $scripts ) {
	if ( is_admin() ) {
		return;
	}
	if ( isset( $scripts->registered['jquery'] ) ) {
		$jquery = $scripts->registered['jquery'];
		if ( ! empty( $jquery->deps ) ) {
			$jquery->deps = array_diff( $jquery->deps, array( 'jquery-migrate' ) );
		}
	}
}

// Strip the version query string (?ver=) from front-end scripts and styles.
if ( $amw_toolbox_o['strip_version_query'] ) {
	add_action( 'init', 'amw_toolbox_register_version_filters' );
}

function amw_toolbox_register_version_filters() {
	if ( ! is_admin() ) {
		add_filter( 'script_loader_src', 'amw_toolbox_strip_version_query', 15 );
		add_filter( 'style_loader_src',  'amw_toolbox_strip_version_query', 15 );
	}
}

function amw_toolbox_strip_version_query( $src ) {
	return $src ? remove_query_arg( 'ver', $src ) : '';
}


/* ═══════════════════════════════════════════════════════════════════════════
   5. DIVI
   ═══════════════════════════════════════════════════════════════════════════ */

// Replace Divi's viewport tag with one that allows zooming.
if ( $amw_toolbox_o['fix_divi_viewport'] ) {
	add_action( 'wp_head', 'amw_toolbox_fix_divi_viewport', 1 );
}

function amw_toolbox_fix_divi_viewport() {
	remove_action( 'wp_head', 'et_add_viewport_meta' );
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
}


/* ═══════════════════════════════════════════════════════════════════════════
   6. WOOCOMMERCE  (filters no-op when WooCommerce is inactive; callbacks that
      call Woo functions guard with function_exists)
   ═══════════════════════════════════════════════════════════════════════════ */

// Disable WooCommerce Analytics only (leaves the rest of WC Admin intact).
if ( $amw_toolbox_o['wc_disable_analytics'] ) {
	add_filter( 'woocommerce_admin_features', 'amw_toolbox_wc_disable_analytics' );
}

// Remove WooCommerce's promotional ads: admin inbox notes, payment and extension
// suggestions.
if ( $amw_toolbox_o['wc_disable_ads'] ) {
	add_filter( 'woocommerce_admin_features', 'amw_toolbox_wc_disable_ads' );
}

// Hide the in-admin Marketplace suggestions / product ads.
if ( $amw_toolbox_o['wc_disable_marketplace_suggestions'] ) {
	add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );
}

// Force WooCommerce usage tracking off.
if ( $amw_toolbox_o['wc_disable_tracker'] ) {
	add_filter( 'pre_option_woocommerce_allow_tracking', 'amw_toolbox_wc_disable_tracking' );
}

// Remove the WooCommerce → Extensions (Marketplace) submenu.
if ( $amw_toolbox_o['wc_hide_marketplace_menu'] ) {
	add_action( 'admin_menu', 'amw_toolbox_wc_hide_marketplace_menu', 999 );
}

// Dequeue the WooCommerce front-end styles on non-store pages.
if ( $amw_toolbox_o['wc_conditional_styles'] ) {
	add_action( 'wp_enqueue_scripts', 'amw_toolbox_wc_conditional_styles', 99 );
}

function amw_toolbox_wc_disable_tracking() {
	return 'no';
}

function amw_toolbox_wc_disable_analytics( $features ) {
	return array_values( array_diff( (array) $features, array( 'analytics' ) ) );
}

function amw_toolbox_wc_disable_ads( $features ) {
	$remove = array( 'remote-inbox-notifications', 'payment-gateway-suggestions', 'remote-free-extensions' );
	return array_values( array_diff( (array) $features, $remove ) );
}

function amw_toolbox_wc_hide_marketplace_menu() {
	remove_submenu_page( 'woocommerce', 'wc-addons' );
}

function amw_toolbox_wc_conditional_styles() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	// Keep the styles on the actual store pages.
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return;
	}
	wp_dequeue_style( 'woocommerce-layout' );
	wp_dequeue_style( 'woocommerce-smallscreen' );
	wp_dequeue_style( 'woocommerce-general' );
	wp_dequeue_style( 'wc-blocks-style' );
}