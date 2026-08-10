<?php
/**
 * Uninstall AMW Toolbox.
 * Deletes the plugin option so no residue is left in the database.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

if ( is_multisite() ) {
    $sites = get_sites( [ 'fields' => 'ids', 'number' => 0 ] );
    foreach ( $sites as $site_id ) {
        switch_to_blog( $site_id );
        delete_option( 'amw_toolbox_options' );
        restore_current_blog();
    }
} else {
    delete_option( 'amw_toolbox_options' );
}
