<?php
/**
 * Removes what the plugin created: its three tables, its options, the cleanup event, its
 * capabilities on the roles and the export files. Define EXPORTS_REPORTS_UNINSTALL_KEEP_DATA in wp-config.php to uninstall the
 * code and keep the data.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin's own tables, removed on uninstall.

if ( defined( 'EXPORTS_REPORTS_UNINSTALL_KEEP_DATA' ) && EXPORTS_REPORTS_UNINSTALL_KEEP_DATA ) {
	return;
}

/**
 * Removes the tables, options, scheduled event and role capabilities of the current site.
 */
function exports_reports_uninstall_site() {
	/** @var wpdb $wpdb */ global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}exportsreports_groups" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}exportsreports_reports" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}exportsreports_log" );

	delete_option( 'exports_reports_version' );
	delete_option( 'exports_reports_token' );
	delete_option( 'exports_reports_token_hash' );
	delete_option( 'exports_reports_charset_errors' );
	delete_option( 'exports_reports_export_formats_off' );
	delete_option( 'exports_reports_export_formats' );
	delete_option( 'exports_reports_count_cache' );
	delete_option( 'exports_reports_count_cache_generation' );

	// The kept row counts (transients that would expire within the hour anyway).
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_exports_reports_count_%' OR option_name LIKE '_transient_timeout_exports_reports_count_%'" );

	wp_clear_scheduled_hook( 'exports_reports_cleanup' );

	// The capabilities the plugin put on the administrator role, and any per-group or per-report
	// capability a site gave a role.
	foreach ( wp_roles()->role_objects as $role ) {
		foreach ( array_keys( $role->capabilities ) as $cap ) {
			if ( 0 === strpos( $cap, 'exports_reports_' ) ) {
				$role->remove_cap( $cap );
			}
		}
	}
}

/**
 * Deletes the current site's export files, in the configured directory (the site's uploads folder by default)
 * and in the one the plugin used before 0.9.5, and each directory when nothing else is left in it.
 */
function exports_reports_uninstall_exports() {
	$directories = array( WP_CONTENT_DIR . '/exports' );

	if ( defined( 'EXPORTS_REPORTS_EXPORT_DIR' ) ) {
		$directories[] = EXPORTS_REPORTS_EXPORT_DIR;
	} elseif ( defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ) {
		$directories[] = WP_ADMIN_UI_EXPORT_DIR;
	} else {
		$uploads       = wp_get_upload_dir();
		$directories[] = $uploads['basedir'] . '/exports-and-reports';
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! WP_Filesystem() ) {
		return;
	}

	foreach ( array_unique( $directories ) as $directory ) {
		exports_reports_uninstall_export_dir( $directory );
	}
}

/**
 * Deletes the export files of one directory, and the directory when nothing else is left in it.
 *
 * @param string $directory The directory.
 */
function exports_reports_uninstall_export_dir( $directory ) {
	/** @var WP_Filesystem_Base $wp_filesystem */ global $wp_filesystem;

	if ( ! $wp_filesystem->is_dir( $directory ) ) {
		return;
	}

	// Export files only, in case the directory is shared.
	foreach ( (array) $wp_filesystem->dirlist( $directory, true ) as $file ) {
		if ( 'f' === $file['type'] && in_array( strtolower( (string) pathinfo( $file['name'], PATHINFO_EXTENSION ) ), array( 'csv', 'tsv', 'txt', 'xlsx', 'xml', 'json', 'pdf' ), true ) ) {
			$wp_filesystem->delete( $directory . '/' . $file['name'] );
		}
	}

	// The files that kept the directory from being served go with it when nothing else is left.
	$left = array_keys( (array) $wp_filesystem->dirlist( $directory, true ) );

	if ( array() === array_diff( $left, array( '.htaccess', 'index.php' ) ) ) {
		foreach ( $left as $file ) {
			$wp_filesystem->delete( $directory . '/' . $file );
		}

		$wp_filesystem->rmdir( $directory );
	}
}

if ( is_multisite() ) {
	$exports_reports_sites = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $exports_reports_sites as $exports_reports_site_id ) {
		switch_to_blog( $exports_reports_site_id );
		exports_reports_uninstall_site();
		exports_reports_uninstall_exports();
		restore_current_blog();
	}
} else {
	exports_reports_uninstall_site();
	exports_reports_uninstall_exports();
}
