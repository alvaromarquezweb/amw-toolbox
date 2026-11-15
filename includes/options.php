<?php
/**
 * AMW Toolbox — Options: defaults, storage and sanitising.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AMW_TOOLBOX_OPTION = 'amw_toolbox_options';

/**
 * Admin bar nodes the user can hide (id => label).
 * Fallback labels, used before the live admin bar has been captured and to give
 * nice names to known core nodes. The live list is detected dynamically.
 */
function amw_toolbox_adminbar_known() {
	return array(
		'wp-logo'        => __( 'WordPress logo', 'amw-toolbox' ),
		'site-name'      => __( 'Site name', 'amw-toolbox' ),
		'comments'       => __( 'Comments bubble', 'amw-toolbox' ),
		'new-content'    => __( '"+ New" button', 'amw-toolbox' ),
		'updates'        => __( 'Updates', 'amw-toolbox' ),
		'search'         => __( 'Front-end search', 'amw-toolbox' ),
		'about'          => __( 'About WordPress', 'amw-toolbox' ),
		'wporg'          => __( 'WordPress.org', 'amw-toolbox' ),
		'documentation'  => __( 'Documentation', 'amw-toolbox' ),
		'support-forums' => __( 'Support forums', 'amw-toolbox' ),
		'feedback'       => __( 'Send feedback', 'amw-toolbox' ),
	);
}

/**
 * Dashboard widgets fallback labels (id => array( label, context )). Used before
 * the dashboard has been visited and captured. The live list is learned there.
 */
function amw_toolbox_dashboard_known() {
	return array(
		'dashboard_right_now'       => array( __( 'At a Glance', 'amw-toolbox' ), 'normal' ),
		'dashboard_activity'        => array( __( 'Activity', 'amw-toolbox' ), 'normal' ),
		'dashboard_recent_comments' => array( __( 'Recent Comments', 'amw-toolbox' ), 'normal' ),
		'dashboard_quick_press'     => array( __( 'Quick Draft', 'amw-toolbox' ), 'side' ),
		'dashboard_primary'         => array( __( 'WordPress News & Events', 'amw-toolbox' ), 'side' ),
		'e-dashboard-overview'      => array( __( 'Elementor Overview', 'amw-toolbox' ), 'normal' ),
		'et_pb_dashboard_widget'    => array( __( 'Divi', 'amw-toolbox' ), 'normal' ),
	);
}

/**
 * Default state: everything OFF / no-op. The plugin ships inert and only acts on
 * what the user explicitly enables, so activating it never changes a site by itself.
 */
function amw_toolbox_defaults() {
	return array(
		// Everything is OFF by default: installing the plugin changes nothing
		// until you opt in to each feature, per site.

		// Hide lists (arrays of slugs/ids) — empty = nothing hidden.
		'hidden_menus'        => array(),
		'hidden_adminbar'     => array(),
		'hidden_dashboard'    => array(),

		// Comments & admin experience
		'disable_comments'          => false,
		'hide_notices_for_clients'  => false,
		'hide_admin_bar_front'      => false,
		'hide_welcome_panel'        => false,
		'disable_block_widgets'     => false,
		'hide_default_theme_notice' => false,
		'keep_on_uninstall'         => false,
		'disable_admin_email_check' => false,
		'custom_admin_footer'       => false,
		'admin_footer_text'         => '',
		'block_dashboard_non_editors' => false,
		'enable_duplication'        => false,
		'enable_year_shortcode'     => false,
		'enable_firma_shortcode'    => false,
		'firma_autor'               => 'Álvaro Márquez',
		'firma_url'                 => 'https://alvaromarquezweb.com',
		'firma_corazon'             => '#b33a2b',
		'firma_pastilla'            => 'clara', // clara | oscura | personalizada
		'firma_pastilla_bg'         => '#ffffff', // fondo de la pastilla personalizada
		'firma_pastilla_bg_hover'   => '#f0f0f0', // fondo al pasar el raton (personalizada)

		// Head & security
		'hide_wp_version'           => false,
		'remove_powered_by'         => false,
		'clean_head_tags'           => false,
		'disable_feeds'             => false,
		'disable_xmlrpc'            => false,
		'xmlrpc_harden'             => false,
		'disallow_file_edit'        => false,
		'block_user_enumeration'    => false,
		'disable_app_passwords'     => false,
		'disable_anon_rest'         => false,
		'header_nosniff'            => false,
		'header_frame'              => false,
		'header_referrer'           => false,
		'header_hsts'               => false,
		'header_permissions_policy' => false,

		// Performance
		'heartbeat_mode'            => 'default', // default | slow | off
		'strip_version_query'       => false,
		'remove_block_css'          => false,
		'disable_dashicons'         => false,
		'disable_oembed'            => false,
		'disable_big_image_scaling' => false,
		'disable_emojis'            => false,
		'remove_jquery_migrate'     => false,
		'disable_remote_block_patterns' => false,
		'disabled_image_sizes'      => array(),
		'revisions_mode'            => 'default', // default | limit | disable
		'revisions_limit'           => 5,
		'empty_trash_mode'          => 'default', // default | days
		'empty_trash_days'          => 30,

		// Divi
		'fix_divi_viewport'     => false,
		'disable_divi_projects' => false,

		// WooCommerce (only applied when WooCommerce is active)
		'wc_disable_analytics'               => false,
		'wc_disable_ads'                     => false,
		'wc_disable_marketplace_suggestions' => false,
		'wc_disable_tracker'                 => false,
		'wc_hide_marketplace_menu'           => false,
		'wc_conditional_styles'              => false,
	);
}

/**
 * Every boolean feature key (used by the sanitiser and the settings page).
 */
function amw_toolbox_bool_keys() {
	return array(
		'disable_comments',
		'hide_notices_for_clients',
		'hide_admin_bar_front',
		'hide_welcome_panel',
		'disable_block_widgets',
		'hide_default_theme_notice',
		'keep_on_uninstall',
		'disable_admin_email_check',
		'custom_admin_footer',
		'block_dashboard_non_editors',
		'enable_duplication',
		'enable_year_shortcode',
		'enable_firma_shortcode',
		'hide_wp_version',
		'remove_powered_by',
		'clean_head_tags',
		'disable_feeds',
		'disable_xmlrpc',
		'xmlrpc_harden',
		'disallow_file_edit',
		'block_user_enumeration',
		'disable_app_passwords',
		'disable_anon_rest',
		'header_nosniff',
		'header_frame',
		'header_referrer',
		'header_hsts',
		'header_permissions_policy',
		'strip_version_query',
		'remove_block_css',
		'disable_dashicons',
		'disable_oembed',
		'disable_big_image_scaling',
		'disable_emojis',
		'remove_jquery_migrate',
		'disable_remote_block_patterns',
	);
}

/**
 * WooCommerce feature keys (only surfaced when WooCommerce is active).
 */
function amw_toolbox_woo_keys() {
	return array(
		'wc_disable_analytics',
		'wc_disable_ads',
		'wc_disable_marketplace_suggestions',
		'wc_disable_tracker',
		'wc_hide_marketplace_menu',
		'wc_conditional_styles',
	);
}

/**
 * Divi feature keys (only surfaced when Divi is active).
 */
function amw_toolbox_divi_keys() {
	return array(
		'fix_divi_viewport',
		'disable_divi_projects',
	);
}

/**
 * Is Divi active? True for the Divi or Extra theme and for the Divi Builder plugin.
 * Reliable on admin/front once the theme is loaded (not at plugin-load time).
 */
function amw_toolbox_is_divi_active() {
	return defined( 'ET_CORE_VERSION' ) || function_exists( 'et_setup_theme' );
}

/**
 * Is Yoast SEO active? Used only to show overlap notes next to the options Yoast
 * also handles under SEO -> Advanced -> Crawl optimization.
 */
function amw_toolbox_is_yoast_active() {
	return defined( 'WPSEO_VERSION' );
}

/**
 * Is Shield Security (wp-simple-firewall) active? Used only to show an overlap
 * note next to the XML-RPC options, which Shield can also handle.
 */
function amw_toolbox_is_shield_active() {
	return class_exists( 'ICWP_WPSF_Shield_Security' );
}

/**
 * How many optimisations are currently enabled (for the panel summary).
 * Counts toggled booleans plus non-empty hide lists and non-default selects.
 * The keep_on_uninstall preference is not counted as an optimisation.
 */
function amw_toolbox_active_count() {
	$o     = amw_toolbox_get_options();
	$count = 0;

	$bools = array_merge(
		amw_toolbox_bool_keys(),
		amw_toolbox_woo_keys(),
		amw_toolbox_divi_keys()
	);
	foreach ( $bools as $key ) {
		if ( 'keep_on_uninstall' === $key ) {
			continue;
		}
		if ( ! empty( $o[ $key ] ) ) {
			$count++;
		}
	}

	foreach ( array( 'hidden_menus', 'hidden_adminbar', 'hidden_dashboard', 'disabled_image_sizes' ) as $key ) {
		if ( ! empty( $o[ $key ] ) ) {
			$count++;
		}
	}

	if ( 'default' !== $o['heartbeat_mode'] ) {
		$count++;
	}
	if ( 'default' !== $o['revisions_mode'] ) {
		$count++;
	}
	if ( 'default' !== $o['empty_trash_mode'] ) {
		$count++;
	}

	return $count;
}

/**
 * Saved options merged over the defaults. Cached for the whole request.
 */
function amw_toolbox_get_options() {
	static $options = null;

	if ( null === $options ) {
		$saved   = get_option( AMW_TOOLBOX_OPTION, array() );
		$options = wp_parse_args( is_array( $saved ) ? $saved : array(), amw_toolbox_defaults() );
	}

	return $options;
}

/**
 * Whether a given boolean feature is enabled.
 */
function amw_toolbox_is_on( $key ) {
	$options = amw_toolbox_get_options();
	return ! empty( $options[ $key ] );
}

/**
 * Sanitise on save.
 * - Boolean features → strict booleans (unchecked boxes are absent → false).
 * - Hide lists → arrays of clean slugs. Admin bar and dashboard are whitelisted
 *   against their known maps; menu slugs are dynamic, so they are only cleaned.
 *   The Settings menu is never hideable (it holds this very panel).
 */
function amw_toolbox_sanitize( $input ) {
	$clean = array();

	foreach ( amw_toolbox_bool_keys() as $key ) {
		$clean[ $key ] = ! empty( $input[ $key ] );
	}

	// Menu slugs (dynamic). The Settings menu is never hideable (it holds this panel).
	$clean['hidden_menus'] = amw_toolbox_sanitize_slug_list( isset( $input['hidden_menus'] ) ? $input['hidden_menus'] : array(), array( 'options-general.php' ) );

	// Admin bar nodes: detected dynamically, so we only sanitize the ids (used for
	// remove_node, a no-op for unknown ids, and printed with esc_attr).
	$clean['hidden_adminbar'] = amw_toolbox_sanitize_slug_list( isset( $input['hidden_adminbar'] ) ? $input['hidden_adminbar'] : array() );

	// Dashboard widgets: same approach (remove_meta_box is a no-op for unknown ids).
	$clean['hidden_dashboard'] = amw_toolbox_sanitize_slug_list( isset( $input['hidden_dashboard'] ) ? $input['hidden_dashboard'] : array() );

	// Heartbeat mode.
	$hb = isset( $input['heartbeat_mode'] ) ? sanitize_key( $input['heartbeat_mode'] ) : 'default';
	$clean['heartbeat_mode'] = in_array( $hb, array( 'default', 'slow', 'off' ), true ) ? $hb : 'default';

	// Post revisions: mode + limit.
	$rev = isset( $input['revisions_mode'] ) ? sanitize_key( $input['revisions_mode'] ) : 'default';
	$clean['revisions_mode']  = in_array( $rev, array( 'default', 'limit', 'disable' ), true ) ? $rev : 'default';
	$clean['revisions_limit'] = isset( $input['revisions_limit'] ) ? max( 1, absint( $input['revisions_limit'] ) ) : 5;

	// Trash auto-empty: mode + days.
	$trash = isset( $input['empty_trash_mode'] ) ? sanitize_key( $input['empty_trash_mode'] ) : 'default';
	$clean['empty_trash_mode'] = in_array( $trash, array( 'default', 'days' ), true ) ? $trash : 'default';
	$clean['empty_trash_days'] = isset( $input['empty_trash_days'] ) ? max( 0, absint( $input['empty_trash_days'] ) ) : 30;

	// Custom admin footer text (allow safe HTML, e.g. a link).
	$clean['admin_footer_text'] = isset( $input['admin_footer_text'] ) ? wp_kses_post( wp_unslash( $input['admin_footer_text'] ) ) : '';

	// Firma del pie: creador, enlace y color del corazon.
	$clean['firma_autor'] = isset( $input['firma_autor'] ) ? sanitize_text_field( wp_unslash( $input['firma_autor'] ) ) : '';
	$clean['firma_url']   = isset( $input['firma_url'] ) ? esc_url_raw( wp_unslash( $input['firma_url'] ) ) : '';
	$firma_hex = isset( $input['firma_corazon'] ) ? sanitize_text_field( wp_unslash( $input['firma_corazon'] ) ) : '';
	if ( '' !== $firma_hex && '#' !== substr( $firma_hex, 0, 1 ) ) {
		$firma_hex = '#' . $firma_hex;
	}
	// sanitize_hex_color devuelve '' si el hex no es valido; al pintar se usa el color por defecto.
	$clean['firma_corazon'] = sanitize_hex_color( $firma_hex );
	// Estilo de la pastilla: clara, oscura o personalizada (con colores propios).
	$pastilla = isset( $input['firma_pastilla'] ) ? sanitize_key( $input['firma_pastilla'] ) : 'clara';
	$clean['firma_pastilla'] = in_array( $pastilla, array( 'clara', 'oscura', 'personalizada' ), true ) ? $pastilla : 'clara';
	// Colores de la pastilla personalizada (hex). Vacio si el valor no es valido.
	$bg = isset( $input['firma_pastilla_bg'] ) ? sanitize_text_field( wp_unslash( $input['firma_pastilla_bg'] ) ) : '';
	if ( '' !== $bg && '#' !== substr( $bg, 0, 1 ) ) {
		$bg = '#' . $bg;
	}
	$clean['firma_pastilla_bg'] = sanitize_hex_color( $bg );
	$bgh = isset( $input['firma_pastilla_bg_hover'] ) ? sanitize_text_field( wp_unslash( $input['firma_pastilla_bg_hover'] ) ) : '';
	if ( '' !== $bgh && '#' !== substr( $bgh, 0, 1 ) ) {
		$bgh = '#' . $bgh;
	}
	$clean['firma_pastilla_bg_hover'] = sanitize_hex_color( $bgh );

	// Image sizes to skip generating (dynamic list; keep exact names).
	$clean['disabled_image_sizes'] = amw_toolbox_sanitize_slug_list( isset( $input['disabled_image_sizes'] ) ? $input['disabled_image_sizes'] : array() );

	// WooCommerce toggles: only editable when WooCommerce is active. Otherwise
	// preserve whatever was saved, so they are not wiped while Woo is off (the
	// Woo tab, and therefore its checkboxes, are not rendered when Woo is off).
	$woo_active = class_exists( 'WooCommerce' );
	$existing   = get_option( AMW_TOOLBOX_OPTION, array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	foreach ( amw_toolbox_woo_keys() as $key ) {
		$clean[ $key ] = $woo_active ? ! empty( $input[ $key ] ) : ! empty( $existing[ $key ] );
	}

	// Divi toggles: only editable (tab rendered) when Divi is active. Preserve
	// otherwise, so they are not wiped while Divi is off.
	$divi_active = amw_toolbox_is_divi_active();
	foreach ( amw_toolbox_divi_keys() as $key ) {
		$clean[ $key ] = $divi_active ? ! empty( $input[ $key ] ) : ! empty( $existing[ $key ] );
	}

	return $clean;
}

/**
 * Sanea una lista de slugs/ids enviada: unslash + sanitize_text_field, descarta
 * vacios y cualquier valor en $exclude. Reemplaza el saneado repetido de las
 * listas dinamicas (menus, barra de admin, widgets del escritorio, tamanos).
 */
function amw_toolbox_sanitize_slug_list( $input, $exclude = array() ) {
	$out = array();

	if ( ! empty( $input ) && is_array( $input ) ) {
		foreach ( $input as $value ) {
			$value = sanitize_text_field( wp_unslash( $value ) );
			if ( '' !== $value && ! in_array( $value, $exclude, true ) ) {
				$out[] = $value;
			}
		}
	}

	return $out;
}
