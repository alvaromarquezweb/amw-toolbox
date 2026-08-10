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
 * Curated because the admin bar object is not built on the settings screen,
 * so it cannot be discovered dynamically there.
 */
function amw_toolbox_adminbar_nodes() {
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
 * Dashboard widgets the user can hide (id => array( label, context )).
 * Curated for the same reason as the admin bar.
 */
function amw_toolbox_dashboard_widgets() {
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

		// Head & security
		'hide_wp_version'           => false,
		'remove_powered_by'         => false,
		'clean_head_tags'           => false,
		'disable_xmlrpc'            => false,
		'disallow_file_edit'        => false,
		'block_user_enumeration'    => false,
		'disable_app_passwords'     => false,
		'header_nosniff'            => false,
		'header_frame'              => false,
		'header_referrer'           => false,

		// Performance
		'heartbeat_mode'            => 'default', // default | slow | off
		'strip_version_query'       => false,
		'remove_block_css'          => false,
		'disable_dashicons'         => false,
		'disable_oembed'            => false,
		'disable_big_image_scaling' => false,
		'disable_emojis'            => false,
		'remove_jquery_migrate'     => false,
		'disabled_image_sizes'      => array(),
		'revisions_mode'            => 'default', // default | limit | disable
		'revisions_limit'           => 5,

		// Divi
		'fix_divi_viewport'   => false,

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
		'hide_wp_version',
		'remove_powered_by',
		'clean_head_tags',
		'disable_xmlrpc',
		'disallow_file_edit',
		'block_user_enumeration',
		'disable_app_passwords',
		'header_nosniff',
		'header_frame',
		'header_referrer',
		'strip_version_query',
		'remove_block_css',
		'disable_dashicons',
		'disable_oembed',
		'disable_big_image_scaling',
		'disable_emojis',
		'remove_jquery_migrate',
		'fix_divi_viewport',
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

	// Menu slugs (dynamic).
	$clean['hidden_menus'] = array();
	if ( ! empty( $input['hidden_menus'] ) && is_array( $input['hidden_menus'] ) ) {
		foreach ( $input['hidden_menus'] as $slug ) {
			$slug = sanitize_text_field( wp_unslash( $slug ) );
			if ( '' !== $slug && 'options-general.php' !== $slug ) {
				$clean['hidden_menus'][] = $slug;
			}
		}
	}

	// Admin bar nodes (whitelisted).
	$clean['hidden_adminbar'] = amw_toolbox_clean_list(
		$input['hidden_adminbar'] ?? array(),
		array_keys( amw_toolbox_adminbar_nodes() )
	);

	// Dashboard widgets (whitelisted).
	$clean['hidden_dashboard'] = amw_toolbox_clean_list(
		$input['hidden_dashboard'] ?? array(),
		array_keys( amw_toolbox_dashboard_widgets() )
	);

	// Heartbeat mode.
	$hb = isset( $input['heartbeat_mode'] ) ? sanitize_key( $input['heartbeat_mode'] ) : 'off';
	$clean['heartbeat_mode'] = in_array( $hb, array( 'default', 'slow', 'off' ), true ) ? $hb : 'off';

	// Post revisions: mode + limit.
	$rev = isset( $input['revisions_mode'] ) ? sanitize_key( $input['revisions_mode'] ) : 'default';
	$clean['revisions_mode']  = in_array( $rev, array( 'default', 'limit', 'disable' ), true ) ? $rev : 'default';
	$clean['revisions_limit'] = isset( $input['revisions_limit'] ) ? max( 1, absint( $input['revisions_limit'] ) ) : 5;

	// Image sizes to skip generating (dynamic list; keep exact names).
	$clean['disabled_image_sizes'] = array();
	if ( ! empty( $input['disabled_image_sizes'] ) && is_array( $input['disabled_image_sizes'] ) ) {
		foreach ( $input['disabled_image_sizes'] as $size ) {
			$size = sanitize_text_field( wp_unslash( $size ) );
			if ( '' !== $size ) {
				$clean['disabled_image_sizes'][] = $size;
			}
		}
	}

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

	return $clean;
}

/**
 * Keep only submitted values that exist in the given whitelist.
 */
function amw_toolbox_clean_list( $submitted, $allowed ) {
	$out = array();

	if ( ! empty( $submitted ) && is_array( $submitted ) ) {
		foreach ( $submitted as $value ) {
			$value = sanitize_text_field( wp_unslash( $value ) );
			if ( in_array( $value, $allowed, true ) ) {
				$out[] = $value;
			}
		}
	}

	return $out;
}