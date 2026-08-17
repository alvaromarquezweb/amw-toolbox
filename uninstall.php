<?php
/**
 * Uninstall AMW Toolbox.
 *
 * Deletes the plugin option so no residue is left in the database, UNLESS the
 * "keep settings on uninstall" option is enabled, in which case the settings are
 * preserved for a future reinstall.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete the option on one site, honouring the keep-on-uninstall preference.
 */
function amw_toolbox_uninstall_site() {
	$options = get_option( 'amw_toolbox_options' );

	if ( is_array( $options ) && ! empty( $options['keep_on_uninstall'] ) ) {
		return; // The user chose to keep their settings.
	}

	delete_option( 'amw_toolbox_options' );
}

if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );
	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		amw_toolbox_uninstall_site();
		restore_current_blog();
	}
} else {
	amw_toolbox_uninstall_site();
}
