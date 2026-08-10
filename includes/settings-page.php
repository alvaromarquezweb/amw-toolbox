<?php
/**
 * AMW Toolbox — Settings page (Settings → AMW Toolbox).
 * Native wp-admin form controls, brand header, and the tab show/hide.
 * All hook callbacks are named functions (amw_toolbox_*).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'amw_toolbox_register_settings_page' );
add_action( 'admin_init', 'amw_toolbox_register_setting' );
add_action( 'admin_enqueue_scripts', 'amw_toolbox_enqueue_assets' );
add_action( 'admin_menu', 'amw_toolbox_snapshot_admin_menu', 9998 );
add_action( 'wp_ajax_amw_toolbox_purge_revisions', 'amw_toolbox_ajax_purge_revisions' );
add_filter( 'plugin_action_links_' . AMW_TOOLBOX_BASENAME, 'amw_toolbox_settings_link' );

/**
 * Add a "Settings" link to the plugin's row on the Plugins screen.
 */
function amw_toolbox_settings_link( $links ) {
	$url  = admin_url( 'options-general.php?page=amw-toolbox' );
	$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'amw-toolbox' ) . '</a>';
	array_unshift( $links, $link );
	return $links;
}

/**
 * Register the settings page under the Settings menu.
 */
function amw_toolbox_register_settings_page() {
	add_options_page(
		__( 'AMW Toolbox', 'amw-toolbox' ),
		__( 'AMW Toolbox', 'amw-toolbox' ),
		'manage_options',
		'amw-toolbox',
		'amw_toolbox_render_settings'
	);
}

/**
 * Register the single option array + its sanitiser.
 */
function amw_toolbox_register_setting() {
	register_setting(
		'amw_toolbox_group',
		AMW_TOOLBOX_OPTION,
		array( 'sanitize_callback' => 'amw_toolbox_sanitize' )
	);
}

/**
 * Load the panel's CSS/JS only on our own screen.
 */
function amw_toolbox_enqueue_assets( $hook ) {
	if ( 'settings_page_amw-toolbox' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'amw-toolbox-admin',
		AMW_TOOLBOX_URL . 'assets/css/admin.css',
		array(),
		filemtime( AMW_TOOLBOX_DIR . 'assets/css/admin.css' )
	);

	wp_enqueue_script(
		'amw-toolbox-admin',
		AMW_TOOLBOX_URL . 'assets/js/admin.js',
		array(),
		filemtime( AMW_TOOLBOX_DIR . 'assets/js/admin.js' ),
		true
	);

	wp_localize_script( 'amw-toolbox-admin', 'amwToolbox', array(
		'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
		'purgeNonce' => wp_create_nonce( 'amw_toolbox_purge' ),
		'i18n'       => array(
			'deleting'     => __( 'Deleting…', 'amw-toolbox' ),
			'confirmLabel' => __( 'Delete revisions', 'amw-toolbox' ),
			'deletedTpl'   => __( 'Deleted %d revisions', 'amw-toolbox' ),
			'error'        => __( 'Something went wrong. Please try again.', 'amw-toolbox' ),
		),
	) );
}

/**
 * Snapshot the full admin menu on our own screen, before the removals (priority
 * 9999) run, so the settings checklist can list hidden items and re-enable them.
 */
function amw_toolbox_snapshot_admin_menu() {
	if ( isset( $_GET['page'] ) && 'amw-toolbox' === sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
		amw_toolbox_menu_snapshot( isset( $GLOBALS['menu'] ) ? $GLOBALS['menu'] : array() );
	}
}

/**
 * Store / retrieve the pre-removal menu snapshot.
 */
function amw_toolbox_menu_snapshot( $set = null ) {
	static $snapshot = array();

	if ( null !== $set ) {
		$snapshot = $set;
	}

	return $snapshot;
}

/**
 * AJAX: purge every stored post revision. Manual, admin-only, nonce-protected.
 */
function amw_toolbox_ajax_purge_revisions() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
	}

	check_ajax_referer( 'amw_toolbox_purge', 'nonce' );

	global $wpdb;
	$ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'revision'" );

	$deleted = 0;
	foreach ( $ids as $id ) {
		if ( wp_delete_post_revision( (int) $id ) ) {
			$deleted++;
		}
	}

	wp_send_json_success( array( 'deleted' => $deleted ) );
}

/**
 * Count the post revisions currently stored in the database.
 */
function amw_toolbox_count_revisions() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'" );
}

/**
 * Top-level admin menu items to offer in the settings list (slug => label).
 * Uses the pre-removal snapshot so hidden items still appear and can be re-enabled.
 */
function amw_toolbox_get_admin_menus() {
	$menu = amw_toolbox_menu_snapshot();

	if ( empty( $menu ) && isset( $GLOBALS['menu'] ) ) {
		$menu = $GLOBALS['menu'];
	}

	$items = array();

	if ( empty( $menu ) || ! is_array( $menu ) ) {
		return $items;
	}

	foreach ( $menu as $entry ) {
		if ( empty( $entry[2] ) ) {
			continue;
		}

		$slug = $entry[2];

		// Skip separators and the Settings menu (it holds this panel).
		if ( 0 === strpos( $slug, 'separator' ) || 'options-general.php' === $slug ) {
			continue;
		}

		// Drop count bubbles (e.g. pending comments/updates) from the label.
		$label = isset( $entry[0] ) ? preg_replace( '/<span[^>]*>.*?<\/span>/', '', $entry[0] ) : '';
		$label = trim( wp_strip_all_tags( $label ) );

		$items[ $slug ] = ( '' !== $label ) ? $label : $slug;
	}

	return $items;
}

/**
 * Registered image sizes to offer in the settings list (name => "name (WxH)").
 */
function amw_toolbox_get_image_sizes() {
	$items = array();

	if ( function_exists( 'wp_get_registered_image_subsizes' ) ) {
		foreach ( wp_get_registered_image_subsizes() as $name => $data ) {
			$w = isset( $data['width'] ) ? (int) $data['width'] : 0;
			$h = isset( $data['height'] ) ? (int) $data['height'] : 0;
			$items[ $name ] = sprintf( '%s (%d×%d)', $name, $w, $h );
		}
	} else {
		foreach ( get_intermediate_image_sizes() as $name ) {
			$items[ $name ] = $name;
		}
	}

	return $items;
}

/**
 * One native form-table row with a single boolean checkbox.
 */
function amw_toolbox_bool_row( $options, $key, $th_label, $checkbox_label, $description = '' ) {
	$name = AMW_TOOLBOX_OPTION . '[' . $key . ']';
	?>
	<tr>
		<th scope="row"><?php echo esc_html( $th_label ); ?></th>
		<td>
			<label>
				<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="1" <?php checked( ! empty( $options[ $key ] ) ); ?>>
				<?php echo esc_html( $checkbox_label ); ?>
			</label>
			<?php if ( $description ) : ?>
				<p class="description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/**
 * A heading + form-table section holding a fieldset of "hide this item"
 * checkboxes ($items is value => label; a checked box means the item is hidden).
 */
function amw_toolbox_hide_section( $heading, $intro, $group, $items, $hidden, $th_label = '' ) {
	$name = AMW_TOOLBOX_OPTION . '[' . $group . '][]';

	if ( '' === $th_label ) {
		$th_label = __( 'Hide', 'amw-toolbox' );
	}
	?>
	<h2><?php echo esc_html( $heading ); ?></h2>
	<?php if ( $intro ) : ?>
		<p class="description" style="max-width:640px;"><?php echo esc_html( $intro ); ?></p>
	<?php endif; ?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php echo esc_html( $th_label ); ?></th>
			<td>
				<fieldset>
					<?php foreach ( $items as $value => $label ) : ?>
						<label>
							<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php checked( is_array( $hidden ) && in_array( $value, $hidden, true ) ); ?>>
							<?php echo esc_html( $label ); ?>
						</label><br>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Render the settings page.
 */
function amw_toolbox_render_settings() {
	$o = amw_toolbox_get_options();

	// Dashboard widgets as value => label.
	$dashboard_items = array();
	foreach ( amw_toolbox_dashboard_widgets() as $id => $data ) {
		$dashboard_items[ $id ] = $data[0];
	}

	$rev_count = amw_toolbox_count_revisions();

	$woo_active = class_exists( 'WooCommerce' );
	?>
	<div class="wrap amw-settings">

		<div class="amw-admin-header">
			<a class="amw-brand-chip" href="https://alvaromarquezweb.com" target="_blank" rel="noopener noreferrer" aria-label="alvaromarquezweb.com">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 156 198" style="height:34px; width:auto; display:block;" aria-hidden="true" focusable="false"><path d="M90,0h-24L0,129.119995v68.880005l18-18v-24l24,24,27.120003-27.119995v45.119995l44.879997-44.880005,42,44.880005v-68.880005L90,0ZM78,24l27.120003,72s-8.872002,3.120003-26.879997,3.120003-27.120003-3.120003-27.120003-3.120003l26.879997-72ZM138,156l-24-26.880005-27.120003,26.880005v-44.880005l-44.880001,44.880005-24-24,30-30s11.916,3.120003,30.000004,3.120003,30-3.120003,30-3.120003l30,30v24Z" style="fill:#f7f7f7;fill-rule:evenodd"/></svg>
			</a>
			<div class="amw-brand-meta">
				<h1>
					<?php esc_html_e( 'AMW Toolbox', 'amw-toolbox' ); ?>
					<span class="amw-ver">v<?php echo esc_html( AMW_TOOLBOX_VERSION ); ?></span>
				</h1>
				<p>
					<a href="https://alvaromarquezweb.com" target="_blank" rel="noopener noreferrer">alvaromarquezweb.com</a>
					<span class="amw-dot"> &middot; </span>
					<a href="https://buymeacoffee.com/alvaromarquezweb" target="_blank" rel="noopener noreferrer">&#9749; <?php esc_html_e( 'Buy me a coffee', 'amw-toolbox' ); ?></a>
				</p>
			</div>
		</div>

		<nav class="nav-tab-wrapper amw-tabs" role="tablist" aria-label="<?php esc_attr_e( 'Settings sections', 'amw-toolbox' ); ?>">
			<a href="#" class="nav-tab nav-tab-active" data-amw-tab="admin"       id="amw-tab-admin"       role="tab" aria-controls="amw-panel-admin"       aria-selected="true"  tabindex="0"><?php esc_html_e( 'Admin Area', 'amw-toolbox' ); ?></a>
			<a href="#" class="nav-tab"                data-amw-tab="head"        id="amw-tab-head"        role="tab" aria-controls="amw-panel-head"        aria-selected="false" tabindex="-1"><?php esc_html_e( 'Head & Security', 'amw-toolbox' ); ?></a>
			<a href="#" class="nav-tab"                data-amw-tab="performance" id="amw-tab-performance" role="tab" aria-controls="amw-panel-performance" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Performance', 'amw-toolbox' ); ?></a>
			<a href="#" class="nav-tab"                data-amw-tab="divi"        id="amw-tab-divi"        role="tab" aria-controls="amw-panel-divi"        aria-selected="false" tabindex="-1"><?php esc_html_e( 'Divi', 'amw-toolbox' ); ?></a>
			<?php if ( $woo_active ) : ?>
			<a href="#" class="nav-tab"                data-amw-tab="woocommerce" id="amw-tab-woocommerce" role="tab" aria-controls="amw-panel-woocommerce" aria-selected="false" tabindex="-1"><?php esc_html_e( 'WooCommerce', 'amw-toolbox' ); ?></a>
			<?php endif; ?>
		</nav>

		<form method="post" action="options.php">
			<?php settings_fields( 'amw_toolbox_group' ); ?>

			<div class="amw-tab-panel amw-active" data-amw-panel="admin" id="amw-panel-admin" role="tabpanel" aria-labelledby="amw-tab-admin" tabindex="0">
				<?php
				amw_toolbox_hide_section(
					__( 'Admin menu items', 'amw-toolbox' ),
					__( 'Check the top-level menus you want to hide from the sidebar. This list reflects the menus present on this site. Settings stays visible so you can always reach this panel.', 'amw-toolbox' ),
					'hidden_menus',
					amw_toolbox_get_admin_menus(),
					$o['hidden_menus']
				);

				amw_toolbox_hide_section(
					__( 'Admin bar', 'amw-toolbox' ),
					__( 'Check the top bar items you want to hide.', 'amw-toolbox' ),
					'hidden_adminbar',
					amw_toolbox_adminbar_nodes(),
					$o['hidden_adminbar']
				);

				amw_toolbox_hide_section(
					__( 'Dashboard widgets', 'amw-toolbox' ),
					__( 'Check the dashboard widgets you want to hide.', 'amw-toolbox' ),
					'hidden_dashboard',
					$dashboard_items,
					$o['hidden_dashboard']
				);
				?>

				<h2><?php esc_html_e( 'Comments', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					amw_toolbox_bool_row( $o, 'disable_comments', __( 'Comments', 'amw-toolbox' ), __( 'Disable comments completely', 'amw-toolbox' ), __( 'Removes comment support, its menu, and closes comments on the front end.', 'amw-toolbox' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Admin experience', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					amw_toolbox_bool_row( $o, 'hide_admin_bar_front', __( 'Front admin bar', 'amw-toolbox' ), __( 'Hide the admin bar on the front end', 'amw-toolbox' ), __( 'Hides the top toolbar on the site for everyone except administrators.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'hide_welcome_panel', __( 'Welcome panel', 'amw-toolbox' ), __( 'Hide the dashboard welcome panel', 'amw-toolbox' ), __( 'Removes the "Welcome to WordPress" box from the dashboard.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'hide_notices_for_clients', __( 'Admin notices', 'amw-toolbox' ), __( 'Hide admin notices for non-administrators', 'amw-toolbox' ), __( 'A cleaner admin for clients: users who cannot manage options stop seeing plugin and theme notices.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_block_widgets', __( 'Block widgets', 'amw-toolbox' ), __( 'Disable the block-based widgets screen', 'amw-toolbox' ), __( 'Restores the classic widgets screen instead of the block editor.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'hide_default_theme_notice', __( 'Default theme check', 'amw-toolbox' ), __( 'Hide the "default theme available" Site Health check', 'amw-toolbox' ), __( 'Removes the Site Health recommendation to keep a default (Twenty*) theme installed as a fallback.', 'amw-toolbox' ) );
					?>
				</table>
			</div>

			<div class="amw-tab-panel" data-amw-panel="head" id="amw-panel-head" role="tabpanel" aria-labelledby="amw-tab-head" tabindex="0">
				<h2><?php esc_html_e( 'Head & Security', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					amw_toolbox_bool_row( $o, 'hide_wp_version', __( 'WordPress version', 'amw-toolbox' ), __( 'Hide the WordPress version', 'amw-toolbox' ), __( 'Removes the version from the head, RSS feeds and HTTP headers.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'remove_powered_by', __( 'X-Powered-By', 'amw-toolbox' ), __( 'Remove the X-Powered-By header', 'amw-toolbox' ), __( 'Hides the PHP fingerprint sent in the response headers.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'clean_head_tags', __( '<head> cleanup', 'amw-toolbox' ), __( 'Clean up the <head>', 'amw-toolbox' ), __( 'Removes RSD, WLW, shortlinks and feed links.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_xmlrpc', __( 'XML-RPC', 'amw-toolbox' ), __( 'Disable XML-RPC', 'amw-toolbox' ), __( 'Blocks a common attack vector. Leave off if you use the WP app or Jetpack.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disallow_file_edit', __( 'File editor', 'amw-toolbox' ), __( 'Disable the theme/plugin file editor', 'amw-toolbox' ), __( 'Defines DISALLOW_FILE_EDIT so code cannot be edited from the admin.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'block_user_enumeration', __( 'User enumeration', 'amw-toolbox' ), __( 'Block user enumeration', 'amw-toolbox' ), __( 'Blocks ?author=N scans and the public REST users endpoints for logged-out visitors.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_app_passwords', __( 'Application Passwords', 'amw-toolbox' ), __( 'Disable Application Passwords', 'amw-toolbox' ), __( 'Turns off the WordPress Application Passwords feature.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'header_nosniff', __( 'X-Content-Type-Options', 'amw-toolbox' ), __( 'Send the nosniff header', 'amw-toolbox' ), __( 'Stops browsers from MIME-sniffing responses away from the declared content type.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'header_frame', __( 'X-Frame-Options', 'amw-toolbox' ), __( 'Send SAMEORIGIN', 'amw-toolbox' ), __( 'Blocks other sites from framing yours (clickjacking). Leave off if you embed this site elsewhere.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'header_referrer', __( 'Referrer-Policy', 'amw-toolbox' ), __( 'Send strict-origin-when-cross-origin', 'amw-toolbox' ), __( 'Sends only the origin as the referrer when navigating to other sites.', 'amw-toolbox' ) );
					?>
				</table>
			</div>

			<div class="amw-tab-panel" data-amw-panel="performance" id="amw-panel-performance" role="tabpanel" aria-labelledby="amw-tab-performance" tabindex="0">
				<h2><?php esc_html_e( 'Performance', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Heartbeat API', 'amw-toolbox' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( AMW_TOOLBOX_OPTION . '[heartbeat_mode]' ); ?>">
								<option value="default" <?php selected( $o['heartbeat_mode'], 'default' ); ?>><?php esc_html_e( 'Leave as default', 'amw-toolbox' ); ?></option>
								<option value="slow" <?php selected( $o['heartbeat_mode'], 'slow' ); ?>><?php esc_html_e( 'Slow down (~60s)', 'amw-toolbox' ); ?></option>
								<option value="off" <?php selected( $o['heartbeat_mode'], 'off' ); ?>><?php esc_html_e( 'Disable completely', 'amw-toolbox' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Controls the periodic AJAX pings. "Disable" also turns off autosave and post locking.', 'amw-toolbox' ); ?></p>
						</td>
					</tr>
					<?php
					amw_toolbox_bool_row( $o, 'remove_block_css', __( 'Block editor CSS', 'amw-toolbox' ), __( 'Remove the front-end block (Gutenberg) CSS', 'amw-toolbox' ), __( 'Dequeues wp-block-library, global styles and classic theme styles on the front. Leave off if any page uses core blocks.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_dashicons', __( 'Dashicons', 'amw-toolbox' ), __( 'Disable Dashicons on the front end', 'amw-toolbox' ), __( 'Removes the Dashicons CSS for logged-out visitors. Kept for logged-in users (admin bar).', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_oembed', __( 'oEmbed', 'amw-toolbox' ), __( 'Disable oEmbed', 'amw-toolbox' ), __( 'Removes the discovery links and the front-end wp-embed.js. Leave off if you embed external content in posts.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_big_image_scaling', __( 'Large image scaling', 'amw-toolbox' ), __( 'Disable automatic scaling of big images', 'amw-toolbox' ), __( 'Stops WordPress from creating a downscaled "-scaled" copy of uploads above ~2560px.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'strip_version_query', __( 'Version query string', 'amw-toolbox' ), __( 'Strip the ?ver query string', 'amw-toolbox' ), __( 'Removes ?ver from front-end and login CSS/JS (also hides the version hint there). Note: it also busts browser caches.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'disable_emojis', __( 'Emojis', 'amw-toolbox' ), __( 'Disable emojis', 'amw-toolbox' ), __( 'Removes the emoji script, styles, TinyMCE plugin, feed/email filters and the s.w.org dns-prefetch.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'remove_jquery_migrate', __( 'jQuery Migrate', 'amw-toolbox' ), __( 'Remove jQuery Migrate', 'amw-toolbox' ), __( 'Drops jquery-migrate from the front-end jQuery. WARNING: can break older themes or plugins that rely on deprecated jQuery, so test the front end.', 'amw-toolbox' ) );
					?>
				</table>

				<h2><?php esc_html_e( 'Post revisions', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'New revisions', 'amw-toolbox' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( AMW_TOOLBOX_OPTION . '[revisions_mode]' ); ?>" id="amw-revisions-mode">
								<option value="default" <?php selected( $o['revisions_mode'], 'default' ); ?>><?php esc_html_e( 'Leave as default', 'amw-toolbox' ); ?></option>
								<option value="limit" <?php selected( $o['revisions_mode'], 'limit' ); ?>><?php esc_html_e( 'Keep only the latest…', 'amw-toolbox' ); ?></option>
								<option value="disable" <?php selected( $o['revisions_mode'], 'disable' ); ?>><?php esc_html_e( 'Disable revisions', 'amw-toolbox' ); ?></option>
							</select>
							<input type="number" min="1" step="1" class="small-text" id="amw-revisions-limit" name="<?php echo esc_attr( AMW_TOOLBOX_OPTION . '[revisions_limit]' ); ?>" value="<?php echo esc_attr( $o['revisions_limit'] ); ?>">
							<p class="description"><?php esc_html_e( 'How many revisions WordPress keeps per post from now on. Uses the WP_POST_REVISIONS constant, and is skipped if it is already defined in wp-config.php.', 'amw-toolbox' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Existing revisions', 'amw-toolbox' ); ?></th>
						<td>
							<button type="button" class="button" id="amw-purge-revisions">
								<?php esc_html_e( 'Delete existing revisions', 'amw-toolbox' ); ?>
								(<span id="amw-revision-count"><?php echo (int) $rev_count; ?></span>)
							</button>
							<p class="description"><?php esc_html_e( 'Permanently removes revisions already stored in the database. Manual and irreversible; make sure you have a backup.', 'amw-toolbox' ); ?></p>
						</td>
					</tr>
				</table>

				<?php
				amw_toolbox_hide_section(
					__( 'Image sizes', 'amw-toolbox' ),
					__( 'Check the image sizes you do NOT want WordPress to generate on new uploads. Existing images are not affected. Note: thumbnail and medium are used by the admin and most themes; leave them unless you are sure.', 'amw-toolbox' ),
					'disabled_image_sizes',
					amw_toolbox_get_image_sizes(),
					$o['disabled_image_sizes'],
					__( 'Skip', 'amw-toolbox' )
				);
				?>
			</div>

			<div class="amw-tab-panel" data-amw-panel="divi" id="amw-panel-divi" role="tabpanel" aria-labelledby="amw-tab-divi" tabindex="0">
				<h2><?php esc_html_e( 'Divi', 'amw-toolbox' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php
					amw_toolbox_bool_row( $o, 'fix_divi_viewport', __( 'Viewport', 'amw-toolbox' ), __( 'Fix the Divi viewport (allow zoom)', 'amw-toolbox' ), __( 'Replaces Divi\'s viewport tag so users can pinch-to-zoom.', 'amw-toolbox' ) );
					?>
				</table>
			</div>

			<?php if ( $woo_active ) : ?>
			<div class="amw-tab-panel" data-amw-panel="woocommerce" id="amw-panel-woocommerce" role="tabpanel" aria-labelledby="amw-tab-woocommerce" tabindex="0">
				<h2><?php esc_html_e( 'WooCommerce', 'amw-toolbox' ); ?></h2>
				<p class="description" style="max-width:640px;"><?php esc_html_e( 'These options only appear and act while WooCommerce is active.', 'amw-toolbox' ); ?></p>
				<table class="form-table" role="presentation">
					<?php
					amw_toolbox_bool_row( $o, 'wc_disable_analytics', __( 'Analytics', 'amw-toolbox' ), __( 'Disable WooCommerce Analytics', 'amw-toolbox' ), __( 'Removes the Analytics feature and its menu.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'wc_disable_ads', __( 'Marketplace ads', 'amw-toolbox' ), __( 'Hide marketing and promotion notices', 'amw-toolbox' ), __( 'Removes the admin inbox promotions and the payment/extension suggestions.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'wc_disable_marketplace_suggestions', __( 'Marketplace suggestions', 'amw-toolbox' ), __( 'Hide Marketplace suggestions and product ads', 'amw-toolbox' ), __( 'Removes the in-admin extension and marketplace suggestions.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'wc_disable_tracker', __( 'Usage tracking', 'amw-toolbox' ), __( 'Disable the WooCommerce tracker', 'amw-toolbox' ), __( 'Forces WooCommerce usage tracking off.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'wc_hide_marketplace_menu', __( 'Extensions menu', 'amw-toolbox' ), __( 'Hide the WooCommerce Extensions (Marketplace) submenu', 'amw-toolbox' ), __( 'Removes WooCommerce -> Extensions from the admin menu.', 'amw-toolbox' ) );
					amw_toolbox_bool_row( $o, 'wc_conditional_styles', __( 'Store styles', 'amw-toolbox' ), __( 'Load WooCommerce styles only on store pages', 'amw-toolbox' ), __( 'Dequeues WooCommerce CSS everywhere except shop, cart, checkout and account. Leave off if you use Woo shortcodes or blocks on other pages.', 'amw-toolbox' ) );
					?>
				</table>
			</div>
			<?php endif; ?>

			<div class="amw-submit"><?php submit_button(); ?></div>
		</form>

		<div id="amw-purge-modal" class="amw-modal" hidden>
			<div class="amw-modal-box" role="dialog" aria-modal="true" aria-labelledby="amw-purge-title">
				<h2 id="amw-purge-title"><?php esc_html_e( 'Delete all post revisions?', 'amw-toolbox' ); ?></h2>
				<p>
					<?php
					printf(
						/* translators: %s: number of revisions */
						esc_html__( 'This will permanently delete %s revisions. This cannot be undone.', 'amw-toolbox' ),
						'<strong id="amw-purge-count">0</strong>'
					);
					?>
				</p>
				<p class="amw-modal-actions">
					<button type="button" class="button" id="amw-purge-cancel"><?php esc_html_e( 'Cancel', 'amw-toolbox' ); ?></button>
					<button type="button" class="button button-primary" id="amw-purge-confirm"><?php esc_html_e( 'Delete revisions', 'amw-toolbox' ); ?></button>
				</p>
			</div>
		</div>
	</div>
	<?php
}