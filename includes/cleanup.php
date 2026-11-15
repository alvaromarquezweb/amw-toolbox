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

// Learn which dashboard widgets exist (they are only registered on the Dashboard,
// never on the settings screen), before any hiding runs, and store them.
add_action( 'wp_dashboard_setup', 'amw_toolbox_snapshot_dashboard_widgets', 9998 );

if ( ! empty( $amw_toolbox_o['hidden_dashboard'] ) ) {
	add_action( 'wp_dashboard_setup', 'amw_toolbox_hide_dashboard_widgets', 9999 );
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
	$contexts = array( 'normal', 'side', 'column3', 'column4', 'advanced' );
	foreach ( amw_toolbox_get_options()['hidden_dashboard'] as $id ) {
		foreach ( $contexts as $context ) {
			remove_meta_box( $id, 'dashboard', $context );
		}
	}
}

/**
 * Capture every dashboard widget registered on this site (before any hiding) and
 * store it, so the settings panel can list the real widgets, including those from
 * other plugins. Runs on each admin Dashboard visit; writes only when it changes.
 */
function amw_toolbox_snapshot_dashboard_widgets() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wp_meta_boxes;
	if ( empty( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
		return;
	}

	$seen = array();
	foreach ( $wp_meta_boxes['dashboard'] as $priorities ) {
		if ( ! is_array( $priorities ) ) {
			continue;
		}
		foreach ( $priorities as $boxes ) {
			if ( ! is_array( $boxes ) ) {
				continue;
			}
			foreach ( $boxes as $id => $box ) {
				if ( empty( $id ) || empty( $box ) ) {
					continue;
				}
				$title        = isset( $box['title'] ) ? trim( wp_strip_all_tags( $box['title'] ) ) : '';
				$seen[ $id ] = ( '' !== $title ) ? $title : $id;
			}
		}
	}

	if ( ! empty( $seen ) && get_option( 'amw_toolbox_dashboard_seen' ) !== $seen ) {
		update_option( 'amw_toolbox_dashboard_seen', $seen, false );
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
if ( $amw_toolbox_o['remove_powered_by'] || $amw_toolbox_o['header_nosniff'] || $amw_toolbox_o['header_frame'] || $amw_toolbox_o['header_referrer'] || $amw_toolbox_o['header_hsts'] || $amw_toolbox_o['header_permissions_policy'] ) {
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
	if ( $o['header_hsts'] && is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
	if ( $o['header_permissions_policy'] ) {
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()' );
	}
}

// Stop the periodic "Is this admin email still correct?" verification screen.
if ( $amw_toolbox_o['disable_admin_email_check'] ) {
	add_filter( 'admin_email_check_interval', '__return_zero' );
}

// Replace the admin footer text ("Thank you for creating with WordPress").
if ( $amw_toolbox_o['custom_admin_footer'] ) {
	add_filter( 'admin_footer_text', 'amw_toolbox_custom_admin_footer' );
}

// Block the dashboard for logged-in users without editing permission.
if ( $amw_toolbox_o['block_dashboard_non_editors'] ) {
	add_action( 'admin_init', 'amw_toolbox_block_dashboard_non_editors' );
}

function amw_toolbox_custom_admin_footer() {
	$o = amw_toolbox_get_options();
	return $o['admin_footer_text'];
}

/**
 * Redirect logged-in users who cannot edit content away from /wp-admin.
 *
 * Runs on admin_init. Anyone without the `edit_posts` capability (subscribers
 * and custom roles such as a bookings manager) is sent out of the dashboard,
 * including the profile screen (profile.php), so an administrator must reset
 * those users' passwords for them. Administrators and editors are untouched,
 * and logged-out visitors are left alone (WordPress already sends them to the
 * login screen).
 *
 * Deliberate exceptions, never redirected:
 * - admin-ajax.php (wp_doing_ajax): front-end plugins talk to the admin over
 *   admin-ajax. AMW Reservas, for example, renews its booking-panel nonce there;
 *   blocking it would surface a false "session expired".
 * - admin-post.php: front-end form handlers post here.
 * - WP-Cron (wp_doing_cron): scheduled tasks run in an admin context.
 *
 * The redirect target passes through the `amw_toolbox_dashboard_redirect`
 * filter, so another plugin can send a particular role somewhere specific
 * (e.g. AMW Reservas routing its bookings manager to its own panel):
 *
 *     add_filter( 'amw_toolbox_dashboard_redirect', function ( $url, $user ) {
 *         return in_array( 'amw_reservas_manager', (array) $user->roles, true )
 *             ? admin_url( 'admin.php?page=amw-reservas' ) // or a front-end URL
 *             : $url;
 *     }, 10, 2 );
 *
 * @param string  $url  Default redirect URL.
 * @param WP_User $user The current user.
 */
function amw_toolbox_block_dashboard_non_editors() {
	if ( wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	global $pagenow;
	if ( 'admin-post.php' === $pagenow ) {
		return;
	}

	if ( ! is_user_logged_in() || current_user_can( 'edit_posts' ) ) {
		return;
	}

	$redirect = apply_filters( 'amw_toolbox_dashboard_redirect', home_url( '/' ), wp_get_current_user() );
	wp_safe_redirect( $redirect );
	exit;
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

// Disable all RSS/Atom feeds: redirect every feed endpoint to the home page
// and drop the feed discovery links from the head (independent of head cleanup).
if ( $amw_toolbox_o['disable_feeds'] ) {
	add_action( 'init', 'amw_toolbox_disable_feeds_init' );
}

function amw_toolbox_disable_feeds_init() {
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );

	foreach ( array( 'do_feed', 'do_feed_rdf', 'do_feed_rss', 'do_feed_rss2', 'do_feed_atom', 'do_feed_rss2_comments', 'do_feed_atom_comments' ) as $feed_hook ) {
		add_action( $feed_hook, 'amw_toolbox_redirect_feed', 1 );
	}
}

function amw_toolbox_redirect_feed() {
	wp_safe_redirect( home_url( '/' ), 301 );
	exit;
}

// Disable XML-RPC.
if ( $amw_toolbox_o['disable_xmlrpc'] ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
}

// Harden XML-RPC without turning it off: drop the most-abused methods (pingbacks
// and system.multicall) and the X-Pingback header, leaving the rest working.
if ( $amw_toolbox_o['xmlrpc_harden'] ) {
	add_filter( 'xmlrpc_methods', 'amw_toolbox_harden_xmlrpc_methods' );
	add_filter( 'wp_headers', 'amw_toolbox_remove_xpingback_header' );
}

function amw_toolbox_harden_xmlrpc_methods( $methods ) {
	unset( $methods['pingback.ping'] );
	unset( $methods['pingback.extensions.getPingbacks'] );
	unset( $methods['system.multicall'] );
	return $methods;
}

function amw_toolbox_remove_xpingback_header( $headers ) {
	unset( $headers['X-Pingback'] );
	return $headers;
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

// Bloquear la REST API para visitantes no autenticados.
if ( $amw_toolbox_o['disable_anon_rest'] ) {
	add_filter( 'rest_authentication_errors', 'amw_toolbox_block_anon_rest' );
}

/**
 * Devuelve un error 401 en las peticiones REST de visitantes no logueados.
 *
 * Respeta cualquier error de autenticacion previo y deja pasar a los usuarios
 * con sesion. Algunas rutas quedan siempre permitidas para no romper funciones
 * del front: por defecto las de oEmbed (incrustaciones). Esa lista se puede
 * ampliar con el filtro amw_toolbox_rest_anon_allowed_routes, por ejemplo:
 *
 *     add_filter( 'amw_toolbox_rest_anon_allowed_routes', function ( $rutas ) {
 *         $rutas[] = '/contact-form-7/'; // dejar pasar tu plugin de formularios
 *         return $rutas;
 *     } );
 *
 * @param mixed $result Resultado de autenticacion acumulado (null si aun nada).
 * @return mixed
 */
function amw_toolbox_block_anon_rest( $result ) {
	// Si ya hay un error (u otra respuesta) de autenticacion, no lo pisamos.
	if ( ! empty( $result ) ) {
		return $result;
	}
	if ( is_user_logged_in() ) {
		return $result;
	}

	// Ruta REST actual (por query var, con REQUEST_URI como respaldo).
	$route = '';
	if ( isset( $GLOBALS['wp'] ) && isset( $GLOBALS['wp']->query_vars['rest_route'] ) ) {
		$route = (string) $GLOBALS['wp']->query_vars['rest_route'];
	} elseif ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
		$route = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
	}

	// Rutas siempre permitidas (ampliables por filtro).
	$permitidas = apply_filters( 'amw_toolbox_rest_anon_allowed_routes', array( '/oembed/' ) );
	foreach ( (array) $permitidas as $prefijo ) {
		if ( '' !== $prefijo && false !== strpos( $route, $prefijo ) ) {
			return $result;
		}
	}

	return new WP_Error(
		'amw_rest_forbidden',
		__( 'The REST API is restricted to logged-in users on this site.', 'amw-toolbox' ),
		array( 'status' => 401 )
	);
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

// Trash auto-empty interval (days). wp-config.php wins if already defined.
if ( ! defined( 'EMPTY_TRASH_DAYS' ) && 'days' === $amw_toolbox_o['empty_trash_mode'] ) {
	define( 'EMPTY_TRASH_DAYS', max( 0, (int) $amw_toolbox_o['empty_trash_days'] ) );
}

// Stop WordPress fetching block patterns from the wp.org pattern directory.
if ( $amw_toolbox_o['disable_remote_block_patterns'] ) {
	add_filter( 'should_load_remote_block_patterns', '__return_false' );
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
	// Skip in wp-admin, and while a front-end page builder is editing or
	// previewing: those load versioned assets the builder relies on, and
	// stripping the version breaks the builder (e.g. Divi's Visual Builder).
	if ( is_admin() || amw_toolbox_is_builder_context() ) {
		return;
	}
	add_filter( 'script_loader_src', 'amw_toolbox_strip_version_query', 15 );
	add_filter( 'style_loader_src',  'amw_toolbox_strip_version_query', 15 );
}

/**
 * Is a front-end page builder currently editing or previewing? When it is, the
 * version query must be left alone so the builder keeps working.
 */
function amw_toolbox_is_builder_context() {
	// Divi Visual Builder.
	if ( isset( $_GET['et_fb'] ) ) {
		return true;
	}
	if ( function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled() ) {
		return true;
	}
	// Elementor editor / preview.
	if ( isset( $_GET['elementor-preview'] ) || ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) ) {
		return true;
	}
	// WPBakery front-end editor.
	if ( isset( $_GET['vc_editable'] ) || ( isset( $_GET['vc_action'] ) && 'vc_inline' === $_GET['vc_action'] ) ) {
		return true;
	}
	return false;
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
	// Guarded here (not at plugin-load) because the theme loads after plugins;
	// on wp_head this is reliable, and it avoids emitting a stray viewport tag
	// on non-Divi sites.
	if ( ! amw_toolbox_is_divi_active() ) {
		return;
	}
	remove_action( 'wp_head', 'et_add_viewport_meta' );
	echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
}

// Disable Divi's "Projects" (portfolio) post type and its taxonomies when they
// are not used, removing them from the admin and their public URLs. Runs after
// Divi has registered them (priority 99 on init).
if ( $amw_toolbox_o['disable_divi_projects'] ) {
	add_action( 'init', 'amw_toolbox_disable_divi_projects', 99 );
}

function amw_toolbox_disable_divi_projects() {
	if ( ! amw_toolbox_is_divi_active() ) {
		return;
	}
	if ( post_type_exists( 'project' ) ) {
		unregister_post_type( 'project' );
	}
	foreach ( array( 'project_category', 'project_tag' ) as $tax ) {
		if ( taxonomy_exists( $tax ) ) {
			unregister_taxonomy( $tax );
		}
	}
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

/* ═══════════════════════════════════════════════════════════════════════════
   7. DUPLICATE CONTENT  (a "Duplicate" row action for posts, pages and CPTs)
   ═══════════════════════════════════════════════════════════════════════════ */

// Add a "Duplicate" link to the row actions of posts, pages and custom post types.
if ( $amw_toolbox_o['enable_duplication'] ) {
	add_filter( 'post_row_actions', 'amw_toolbox_duplicate_row_link', 10, 2 );
	add_filter( 'page_row_actions', 'amw_toolbox_duplicate_row_link', 10, 2 );
	add_action( 'admin_action_amw_toolbox_duplicate_post', 'amw_toolbox_duplicate_post' );
}

/**
 * Add the "Duplicate" action to a row, for users who can create that post type.
 *
 * @param array   $actions Row action links.
 * @param WP_Post $post    The post the row represents.
 * @return array
 */
function amw_toolbox_duplicate_row_link( $actions, $post ) {
	$type = get_post_type_object( $post->post_type );
	if ( null === $type || ! current_user_can( $type->cap->create_posts ) ) {
		return $actions;
	}

	$url = wp_nonce_url(
		add_query_arg(
			array(
				'action'  => 'amw_toolbox_duplicate_post',
				'post_id' => $post->ID,
			),
			admin_url( 'admin.php' )
		),
		'amw_toolbox_duplicate_post_' . $post->ID,
		'amw_toolbox_duplicate_nonce'
	);

	$actions['amw_toolbox_duplicate'] = sprintf(
		'<a href="%s" rel="permalink">%s</a>',
		esc_url( $url ),
		esc_html__( 'Duplicate', 'amw-toolbox' )
	);

	return $actions;
}

/**
 * Create a draft copy of a post and send the user to edit it.
 *
 * wp_insert_post() and add_post_meta() unslash the data they receive, so the
 * content, excerpt, title and every meta value are passed through wp_slash()
 * first. This is what keeps Divi (and other builders that store shortcodes or
 * JSON in post_content and post meta) from being corrupted on duplication,
 * which plain duplicate snippets get wrong.
 */
function amw_toolbox_duplicate_post() {
	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	if ( ! $post_id ) {
		wp_die( esc_html__( 'No item ID was provided for duplication.', 'amw-toolbox' ) );
	}

	if ( ! isset( $_GET['amw_toolbox_duplicate_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_GET['amw_toolbox_duplicate_nonce'] ), 'amw_toolbox_duplicate_post_' . $post_id ) ) {
		wp_die( esc_html__( 'The link you followed has expired. Please try again.', 'amw-toolbox' ) );
	}

	$post = get_post( $post_id );
	if ( ! $post ) {
		wp_die( esc_html__( 'The item to duplicate could not be loaded.', 'amw-toolbox' ) );
	}

	$type = get_post_type_object( $post->post_type );
	if ( null === $type || ! current_user_can( $type->cap->create_posts ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to duplicate this item.', 'amw-toolbox' ) );
	}

	$author   = wp_get_current_user();
	$new_post = array(
		'comment_status' => $post->comment_status,
		'ping_status'    => $post->ping_status,
		'menu_order'     => $post->menu_order,
		'post_author'    => $author->ID,
		'post_content'   => wp_slash( $post->post_content ),
		'post_excerpt'   => wp_slash( $post->post_excerpt ),
		'post_parent'    => $post->post_parent,
		'post_password'  => $post->post_password,
		'post_status'    => 'draft',
		/* translators: %s: original item title. */
		'post_title'     => wp_slash( sprintf( __( '%s (copy)', 'amw-toolbox' ), $post->post_title ) ),
		'post_type'      => $post->post_type,
		'to_ping'        => $post->to_ping,
	);

	$new_id = wp_insert_post( $new_post );
	if ( is_wp_error( $new_id ) || ! $new_id ) {
		wp_die( esc_html__( 'The item could not be duplicated.', 'amw-toolbox' ) );
	}

	// Copy taxonomy terms.
	foreach ( get_object_taxonomies( $post->post_type ) as $taxonomy ) {
		$terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
		if ( ! is_wp_error( $terms ) ) {
			wp_set_object_terms( $new_id, $terms, $taxonomy );
		}
	}

	// Copy custom fields, skipping internals. Slash values so add_post_meta()
	// stores them intact (e.g. Divi's _et_pb_* meta).
	$skip = array( '_wp_old_slug', '_edit_lock', '_edit_last' );
	foreach ( get_post_meta( $post_id ) as $key => $values ) {
		if ( in_array( $key, $skip, true ) ) {
			continue;
		}
		foreach ( $values as $value ) {
			add_post_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'action' => 'edit',
				'post'   => $new_id,
			),
			admin_url( 'post.php' )
		)
	);
	exit;
}

/* ═══════════════════════════════════════════════════════════════════════════
   8. CURRENT-YEAR SHORTCODE  ([amw_year], ideal for a self-updating footer)
   ═══════════════════════════════════════════════════════════════════════════ */

// Register the current-year shortcode late, so the [year] alias can check
// whether another plugin already claimed that name.
if ( $amw_toolbox_o['enable_year_shortcode'] ) {
	add_action( 'init', 'amw_toolbox_register_year_shortcode', 99 );
}

function amw_toolbox_register_year_shortcode() {
	add_shortcode( 'amw_year', 'amw_toolbox_year_shortcode' );
	// Convenience alias, only when nothing else uses [year].
	if ( ! shortcode_exists( 'year' ) ) {
		add_shortcode( 'year', 'amw_toolbox_year_shortcode' );
	}
}

/**
 * Print the current year (site timezone). With a start year in the past it
 * prints a range, e.g. [amw_year start="2020"] -> "2020–2026".
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function amw_toolbox_year_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'start' => '' ), $atts, 'amw_year' );
	$current = wp_date( 'Y' );
	$start   = preg_replace( '/[^0-9]/', '', (string) $atts['start'] );

	if ( '' !== $start && (int) $start > 0 && (int) $start < (int) $current ) {
		return esc_html( $start . '–' . $current );
	}

	return esc_html( $current );
}


/* ═══════════════════════════════════════════════════════════════════════════
   9. SHORTCODE DE FIRMA  ([amw_firma], credito centralizado del pie de pagina)
   ═══════════════════════════════════════════════════════════════════════════ */

// Registramos el shortcode y dejamos el estilo preparado. El CSS no se encola
// aqui: solo se encolara cuando una pagina pinte de verdad [amw_firma], para no
// cargarlo en todo el sitio.
if ( $amw_toolbox_o['enable_firma_shortcode'] ) {
	add_action( 'init', 'amw_toolbox_register_firma_shortcode', 99 );
	add_action( 'wp_enqueue_scripts', 'amw_toolbox_register_firma_style' );
}

function amw_toolbox_register_firma_shortcode() {
	add_shortcode( 'amw_firma', 'amw_toolbox_firma_shortcode' );
}

// Dejamos la hoja de estilos registrada (que no encolada), lista para encolarla
// bajo demanda desde el propio shortcode.
function amw_toolbox_register_firma_style() {
	wp_register_style(
		'amw-firma',
		AMW_TOOLBOX_URL . 'assets/css/firma.css',
		array(),
		AMW_TOOLBOX_VERSION
	);
}

/**
 * Valores por defecto del credito, centralizados en un unico punto.
 *
 * Se pasan por el filtro amw_firma_args para poder cambiar el autor, la URL, el
 * color del corazon o los textos desde un solo sitio (por ejemplo en el
 * functions.php del tema hijo) sin tocar el plugin.
 *
 * @return array
 */
function amw_toolbox_firma_args() {
	$o = amw_toolbox_get_options();

	// El autor, la URL y el color salen del panel; si estan vacios o no son
	// validos, caemos a los valores por defecto. El filtro amw_firma_args queda
	// por encima para poder sobrescribirlo todo desde codigo.
	$autor   = ( '' !== trim( (string) $o['firma_autor'] ) ) ? $o['firma_autor'] : 'Álvaro Márquez';
	$url     = ( '' !== trim( (string) $o['firma_url'] ) ) ? $o['firma_url'] : 'https://alvaromarquezweb.com';
	$corazon = ( '' !== trim( (string) $o['firma_corazon'] ) ) ? $o['firma_corazon'] : '#b33a2b';

	$defaults = array(
		'autor'    => $autor,                         // Nombre del enlace (panel).
		'url'      => $url,                           // Destino del enlace (panel).
		'corazon'  => $corazon,                       // Color del corazon (panel).
		'hecho'    => 'Hecho con',                    // Texto antes del corazon.
		'por'      => 'por',                          // Texto entre el corazon y el enlace.
		'derechos' => 'Todos los derechos reservados.', // Cierre de la primera linea.
	);

	return apply_filters( 'amw_firma_args', $defaults );
}

/**
 * Pinta la firma del pie de pagina.
 *
 * Ejemplos de uso:
 *   [amw_firma]
 *   [amw_firma cliente="El Pizzaloriano" align="center"]
 *   [amw_firma color_corazon="#0a7"]
 *
 * @param array $atts Atributos del shortcode.
 * @return string HTML de la firma.
 */
function amw_toolbox_firma_shortcode( $atts ) {
	$args = amw_toolbox_firma_args();

	$atts = shortcode_atts(
		array(
			'cliente'       => get_bloginfo( 'name' ), // Por defecto, el nombre del sitio.
			'align'         => 'right',                 // right, left o center.
			'color_corazon' => $args['corazon'],
		),
		$atts,
		'amw_firma'
	);

	// Ahora sabemos que la pagina usa la firma: encolamos el CSS (si se registro
	// en wp_enqueue_scripts, WordPress lo imprime en el pie).
	if ( ! wp_style_is( 'amw-firma', 'registered' ) ) {
		amw_toolbox_register_firma_style();
	}
	wp_enqueue_style( 'amw-firma' );

	// Alineacion: solo admitimos left, center o right; cualquier otra cae a right.
	$align = in_array( $atts['align'], array( 'left', 'center', 'right' ), true ) ? $atts['align'] : 'right';

	// El anio es dinamico y reutiliza la logica de [amw_year] (sin duplicar codigo).
	// Esa funcion ya devuelve el valor escapado con esc_html.
	$anio = amw_toolbox_year_shortcode( array() );

	// Cliente saneado como texto plano.
	$cliente = sanitize_text_field( $atts['cliente'] );

	// Color del corazon: admitimos con o sin '#', y si no es un hex valido
	// volvemos al color por defecto.
	$corazon = (string) $atts['color_corazon'];
	if ( '' !== $corazon && '#' !== substr( $corazon, 0, 1 ) ) {
		$corazon = '#' . $corazon;
	}
	$corazon = sanitize_hex_color( $corazon );
	if ( empty( $corazon ) ) {
		$corazon = $args['corazon'];
	}

	// Primera linea: (c) anio cliente. Todos los derechos reservados.
	$linea_copyright = sprintf(
		'© %1$s %2$s. %3$s',
		$anio, // Ya escapado por [amw_year].
		esc_html( $cliente ),
		esc_html( $args['derechos'] )
	);

	// Estilo de la pastilla: 'clara' u 'oscura' usan un fondo fijo del CSS;
	// 'personalizada' usa los colores elegidos en el panel, inyectados como
	// variables CSS en el propio elemento.
	$opciones    = amw_toolbox_get_options();
	$badge_clase = 'amw-firma__badge';
	$badge_style = '';
	if ( 'oscura' === $opciones['firma_pastilla'] ) {
		$badge_clase .= ' amw-firma__badge--oscura';
	} elseif ( 'personalizada' === $opciones['firma_pastilla'] ) {
		$badge_clase .= ' amw-firma__badge--personalizada';
		$vars = array();
		if ( ! empty( $opciones['firma_pastilla_bg'] ) ) {
			$vars[] = '--amw-firma-badge-bg:' . $opciones['firma_pastilla_bg'];
		}
		if ( ! empty( $opciones['firma_pastilla_bg_hover'] ) ) {
			$vars[] = '--amw-firma-badge-bg-hover:' . $opciones['firma_pastilla_bg_hover'];
		}
		if ( ! empty( $vars ) ) {
			$badge_style = ' style="' . esc_attr( implode( ';', $vars ) ) . '"';
		}
	} else {
		$badge_clase .= ' amw-firma__badge--clara';
	}

	// Credito: Hecho con (corazon) por + pastilla con el logo y el nombre, enlazada
	// a la web del autor. El SVG del logo es markup de confianza del propio plugin.
	$credito = sprintf(
		'%1$s <span class="amw-firma__heart" aria-hidden="true">♥</span> %2$s <a class="%6$s"%7$s href="%3$s" target="_blank" rel="noopener">%4$s<span class="amw-firma__name">%5$s</span></a>',
		esc_html( $args['hecho'] ),
		esc_html( $args['por'] ),
		esc_url( $args['url'] ),
		amw_toolbox_firma_logo(),
		esc_html( $args['autor'] ),
		esc_attr( $badge_clase ),
		$badge_style
	);

	// Clases: base + modificador de alineacion solo cuando NO es la de por defecto
	// (right), para que la firma por defecto salga como <p class="amw-firma">.
	$clases = 'amw-firma';
	if ( 'right' !== $align ) {
		$clases .= ' amw-firma--' . $align;
	}

	// Color: solo escribimos la variable CSS si difiere del color por defecto
	// (#b33a2b), que el CSS ya aplica con su valor de reserva.
	$style = '';
	if ( 0 !== strcasecmp( $corazon, '#b33a2b' ) ) {
		$style = ' style="--amw-firma-heart:' . esc_attr( $corazon ) . '"';
	}

	// La firma es un contenedor flexible: con sitio va en una linea y, si no cabe,
	// se parte sola en dos. Por eso no hay <br>.
	return sprintf(
		'<p class="%1$s"%2$s><span class="amw-firma__copy">%3$s</span><span class="amw-firma__credito">%4$s</span></p>',
		esc_attr( $clases ),
		$style,
		$linea_copyright,
		$credito
	);
}

/**
 * Logo de la firma. Por defecto, el logo de Alvaro Marquez (AMW): un SVG inline
 * monocromo que hereda el color del texto (fill=currentColor), asi encaja en
 * footers claros y oscuros.
 *
 * Se pasa por el filtro amw_firma_logo para que cualquiera que use el plugin
 * pueda poner su propio logo, o una cadena vacia para no mostrar ninguno. Por
 * ejemplo, desde el functions.php del tema hijo:
 *
 *     add_filter( 'amw_firma_logo', function () {
 *         return '<svg class="amw-firma__logo" fill="currentColor" viewBox="...">...</svg>';
 *     } );
 *
 * Idealmente el SVG lleva class="amw-firma__logo" y fill="currentColor" para que
 * herede el color y el tamano. El valor es markup de confianza (lo define quien
 * tiene acceso al codigo), por eso se imprime tal cual.
 *
 * @return string
 */
function amw_toolbox_firma_logo() {
	$logo = '<svg class="amw-firma__logo" viewBox="0 0 156 198" fill="currentColor" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><path d="M90,0h-24L0,129.119995v68.880005l18-18v-24l24,24,27.120003-27.119995v45.119995l44.879997-44.880005,42,44.880005v-68.880005L90,0ZM78,24l27.120003,72s-8.872002,3.120003-26.879997,3.120003-27.120003-3.120003-27.120003-3.120003l26.879997-72ZM138,156l-24-26.880005-27.120003,26.880005v-44.880005l-44.880001,44.880005-24-24,30-30s11.916,3.120003,30.000004,3.120003,30-3.120003,30-3.120003l30,30v24Z"/></svg>';

	return apply_filters( 'amw_firma_logo', $logo );
}
