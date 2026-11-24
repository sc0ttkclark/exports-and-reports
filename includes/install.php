<?php
/**
 * Installation and upgrade: the tables from the schema, the character set conversion, the version check on admin requests, the move of the export directory, deactivation.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

/**
 * Creates the plugin's tables from includes/schema.sql, or brings them up to date through dbDelta() on an
 * upgrade (the example group and reports only when the tables are empty); records the version and schedules the daily
 * cleanup.
 *
 * @param bool $upgrade Whether the tables exist already.
 */
function exports_reports_sql_install( $upgrade = false ) {
	/** @var wpdb $wpdb */ global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	/** @var WP_Filesystem_Base|null $wp_filesystem */ global $wp_filesystem;

	WP_Filesystem();

	$sql = $wp_filesystem->get_contents( EXPORTS_REPORTS_DIR . 'includes/schema.sql' );

	// The dump carries the three-byte utf8 the tables were first written with; the site's own
	// character set and collation take its place (utf8mb4 on any current install).
	$charset_collate = $wpdb->get_charset_collate();

	if ( '' !== $charset_collate ) {
		$sql = str_replace( 'DEFAULT CHARSET=utf8', $charset_collate, $sql );
	}

	$sql = explode( ";\n", str_replace( array( "\r", 'wp_' ), array( "\n", $wpdb->prefix ), $sql ) );

	// The example group and reports go in on a first install only, and only when there is nothing of the
	// site's own in the tables (the version option may be missing while groups and reports are not).
	$seed = null;

	foreach ( $sql as $query ) {
		$query = trim( $query );

		if ( empty( $query ) ) {
			continue;
		}

		if ( 0 !== stripos( $query, 'CREATE TABLE' ) ) {
			if ( $upgrade ) {
				continue;
			}

			if ( null === $seed ) {
				$seed = exports_reports_tables_are_empty();
			}

			if ( ! $seed ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- a statement of the plugin's own schema file.
			$wpdb->query( $query );

			continue;
		}

		require_once ABSPATH . '/wp-admin/includes/upgrade.php';

		dbDelta( $query );
	}

	if ( $upgrade ) {
		exports_reports_convert_tables_charset();
		exports_reports_move_legacy_export_dir();

		// The files that keep the export directory from being served, brought up to date when their rules changed.
		if ( is_dir( EXPORTS_REPORTS_EXPORT_DIR ) ) {
			require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';
			Exports_Reports_Admin_UI::protect_export_dir();
		}
	}

	delete_option( 'exports_reports_version' );
	add_option( 'exports_reports_version', EXPORTS_REPORTS_VERSION );

	exports_reports_schedule_cleanup();
}

/**
 * Whether the groups and the reports tables hold no rows: the examples are written only then.
 *
 * @since 1.2.1
 *
 * @return bool
 */
function exports_reports_tables_are_empty() {
	/** @var wpdb $wpdb */ global $wpdb;

	$groups  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->exports_reports_groups}" );
	$reports = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->exports_reports_reports}" );

	return 0 === $groups && 0 === $reports && '' === (string) $wpdb->last_error;
}

/**
 * Converts the plugin's tables to the site's character set and collation when they differ.
 *
 * Installs before 0.9.5 created the tables in three-byte utf8 whatever the site used (the
 * replacement in the installer never matched the dump), so a report name or filter value outside
 * the Basic Multilingual Plane could not be stored. dbDelta() does not change the character set of
 * an existing table, so the upgrade converts them.
 *
 * @since 0.9.5
 *
 * @return string[] The tables converted.
 */
function exports_reports_convert_tables_charset() {
	/** @var wpdb $wpdb */ global $wpdb;

	// Only towards utf8mb4, the superset every earlier WordPress character set converts into without loss;
	// a site on another character set keeps its tables as they are.
	if ( 'utf8mb4' !== $wpdb->charset || empty( $wpdb->collate ) ) {
		return array();
	}

	$converted = array();
	$failed    = array();

	foreach ( array( 'groups', 'reports', 'log' ) as $suffix ) {
		$table = $wpdb->prefix . EXPORTS_REPORTS_TBL . $suffix;

		$current = $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );

		if ( empty( $current ) || $current === $wpdb->collate ) {
			continue;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- the plugin's own table; character set and collation are wpdb's, not request data.
		$wpdb->query( "ALTER TABLE `{$table}` CONVERT TO CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}" );

		if ( '' === $wpdb->last_error ) {
			$converted[] = $table;
		} else {
			$failed[ $table ] = $wpdb->last_error;
		}
	}

	// A table that did not convert is reported on the plugin's pages until it does (the next upgrade retries).
	if ( array() === $failed ) {
		delete_option( 'exports_reports_charset_errors' );
	} else {
		update_option( 'exports_reports_charset_errors', $failed, false );
	}

	return $converted;
}

/**
 * Shows the character set conversion errors, if any, on the plugin's pages.
 */
function exports_reports_charset_errors_notice() {
	$failed = get_option( 'exports_reports_charset_errors' );

	if ( ! is_array( $failed ) || array() === $failed || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>' . wp_kses( __( '<strong>Exports and Reports:</strong> a table could not be converted to the site\'s character set. Convert it by hand or ask your host, then deactivate and reactivate the plugin to retry.', 'exports-and-reports' ), array( 'strong' => array() ) ) . '</p><ul>';

	foreach ( $failed as $table => $error ) {
		echo '<li><code>' . esc_html( $table ) . '</code>: ' . esc_html( $error ) . '</li>';
	}

	echo '</ul></div>';
}

/**
 * On activation: installs each site the activation covers (every site of the network when it is
 * activated for the network) when the plugin has never run there, upgrades it otherwise.
 *
 * @since 1.0.0 A network activation reaches every site.
 *
 * @param bool $network_wide Whether the plugin is being activated for the whole network.
 */
function exports_reports_activate( $network_wide = false ) {
	if ( ! $network_wide || ! is_multisite() ) {
		exports_reports_install_upgrade();

		return;
	}

	$site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		exports_reports_install_upgrade();
		restore_current_blog();
	}
}

/**
 * Installs the tables when the plugin has never run on this site, upgrades them otherwise; a fresh
 * install gives the administrator role the plugin's capabilities.
 */
function exports_reports_install_upgrade() {
	if ( '' === exports_reports_installed_version() ) {
		exports_reports_sql_install();
		exports_reports_add_role_capabilities();

		return;
	}

	exports_reports_upgrade();
}

/**
 * The version of the plugin this site last ran, from the exports_reports_version option. Since 1.0.0
 * the option holds the version itself; the versions before stored digits only (095 for 0.9.5, 60 for
 * 0.6), which are read as the version they stood for.
 *
 * @since 1.0.0
 *
 * @return string The version, or '' when the plugin has not run on this site.
 */
function exports_reports_installed_version() {
	$stored = trim( (string) get_option( 'exports_reports_version', '' ) );

	if ( '' === $stored || '0' === $stored ) {
		return '';
	}

	if ( false === strpos( $stored, '.' ) ) {
		$digits = (int) $stored;
		$stored = sprintf( '%d.%d.%d', (int) floor( $digits / 100 ), (int) floor( $digits / 10 ) % 10, $digits % 10 );
	}

	return $stored;
}

/**
 * Brings the tables and the stored version up to date when the plugin's version has changed. An
 * install from before 0.6 loses its API token (that version's had another shape); one from before 1.0
 * gets the capabilities on the administrator role, which the user_has_cap filter of the earlier
 * versions granted on each request; one from before 1.2.0 has its API token replaced by its hash.
 */
function exports_reports_upgrade() {
	$installed = exports_reports_installed_version();

	if ( '' === $installed || 0 === version_compare( $installed, EXPORTS_REPORTS_VERSION ) ) {
		return;
	}

	if ( version_compare( $installed, '0.6.0', '<' ) ) {
		// The token of those versions had another shape; a new one is generated on the Settings page.
		delete_option( 'exports_reports_token' );
	}

	exports_reports_sql_install( true );

	if ( version_compare( $installed, '1.0.0', '<' ) ) {
		exports_reports_add_role_capabilities();
	}

	if ( version_compare( $installed, '1.1.1', '<' ) ) {
		exports_reports_convert_export_formats_setting();
	}

	if ( version_compare( $installed, '1.2.0', '<' ) ) {
		// The API token stored in the clear becomes its hash; the token itself goes on working.
		exports_reports_token_hash();
	}
}

/**
 * On admin_init: installs the tables on a site the activation did not reach (a site added to a
 * network, say) and upgrades when the plugin's version has changed. The Members plugin lists the
 * plugin's capabilities through members_get_capabilities. The copy of exports_reports_full_access
 * that the versions before 0.9.5 wrote into an administrator's own meta, where it would outlive the
 * plugin, is removed; the role carries the capability now.
 */
function exports_reports_init() {
	exports_reports_install_upgrade();

	add_filter( 'members_get_capabilities', 'exports_reports_get_capabilities' );

	if ( ! is_user_logged_in() ) {
		return;
	}

	$user = wp_get_current_user();

	if ( isset( $user->caps['exports_reports_full_access'] ) && in_array( 'administrator', (array) $user->roles, true ) ) {
		$user->remove_cap( 'exports_reports_full_access' );
	}
}

/**
 * Moves this site's export files from the directory the plugin used before 0.9.5, wp-content/exports, into
 * the current one, when the two differ and the old one is still there: the files the site's export log
 * names, so on a network each site takes its own. The log keeps file names only, so downloads follow the
 * move. The protection files the plugin wrote there, and the directory, go once nothing else is left.
 * Runs on upgrade.
 *
 * @since 0.9.5
 */
function exports_reports_move_legacy_export_dir() {
	global $wpdb;

	$legacy = WP_CONTENT_DIR . '/exports';

	if ( EXPORTS_REPORTS_EXPORT_DIR === $legacy || ! is_dir( $legacy ) ) {
		return;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! WP_Filesystem() || ! wp_mkdir_p( EXPORTS_REPORTS_EXPORT_DIR ) ) {
		return;
	}

	/** @var WP_Filesystem_Base|null $wp_filesystem */ global $wp_filesystem;

	if ( ! $wp_filesystem ) {
		return;
	}

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';
	Exports_Reports_Admin_UI::protect_export_dir();

	$logged     = array_map( 'strval', (array) $wpdb->get_col( "SELECT `filename` FROM {$wpdb->exports_reports_log}" ) );
	$protection = array();
	$left       = array();

	foreach ( (array) $wp_filesystem->dirlist( $legacy, true ) as $file ) {
		$path     = $legacy . '/' . $file['name'];
		$contents = 'f' === $file['type'] && in_array( $file['name'], array( '.htaccess', 'index.php' ), true ) ? (string) $wp_filesystem->get_contents( $path ) : '';

		if ( 'f' === $file['type'] && in_array( $file['name'], $logged, true ) && exports_reports_is_export_file( $file['name'] ) ) {
			$wp_filesystem->move( $path, EXPORTS_REPORTS_EXPORT_DIR . '/' . $file['name'], true );
		} elseif ( false !== strpos( $contents, 'Exports and Reports' ) || false !== strpos( $contents, 'Silence is golden' ) ) {
			$protection[] = $path;
		} else {
			$left[] = $file['name'];
		}
	}

	if ( array() === $left ) {
		foreach ( $protection as $path ) {
			$wp_filesystem->delete( $path );
		}

		$wp_filesystem->rmdir( $legacy );
	}
}

/**
 * Clears the daily cleanup event on deactivation; it would otherwise fire for a plugin that is not loaded.
 *
 * @since 0.9.5
 */
function exports_reports_deactivate() {
	wp_clear_scheduled_hook( 'exports_reports_cleanup' );
}
