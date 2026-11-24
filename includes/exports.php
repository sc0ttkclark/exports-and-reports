<?php
/**
 * Export files: the download, the log, the daily cleanup and the robots.txt rule.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

/**
 * The admin-ajax action EXPORTS_REPORTS_EXPORT_ACTION: includes/download.php sends the file and exits, so a
 * request that names no export ends here.
 */
function exports_reports_export_download() {
	require_once EXPORTS_REPORTS_DIR . 'includes/download.php';

	wp_die( esc_html__( 'Invalid request.', 'exports-and-reports' ), '', array( 'response' => 400 ) );
}

/**
 * Sends a file from the export directory to the browser as an attachment.
 *
 * The directory refuses direct requests (Exports_Reports_Admin_UI::protect_export_dir() writes the
 * rules), so every download comes through here after the caller's nonce or token check. The file
 * is read in chunks; an export can be larger than the memory limit.
 *
 * @since 0.9.5
 *
 * @param string $filename The file's name inside the export directory, without a path.
 * @return bool False when no such export exists; true once the file has been sent.
 */
function exports_reports_send_export_file( $filename ) {
	$types     = exports_reports_export_mime_types();
	$filename  = basename( (string) $filename );
	$extension = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );
	$directory = realpath( EXPORTS_REPORTS_EXPORT_DIR );
	$path      = realpath( EXPORTS_REPORTS_EXPORT_DIR . '/' . $filename );

	if ( ! exports_reports_is_export_file( $filename ) || false === $directory || false === $path || dirname( $path ) !== $directory || ! is_file( $path ) ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- read in chunks; WP_Filesystem would load the whole export into memory.
	$handle = fopen( $path, 'rb' );

	if ( false === $handle ) {
		return false;
	}

	nocache_headers();
	header( 'Content-Type: ' . $types[ $extension ] );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . (string) filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );

	fpassthru( $handle );

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- paired with the fopen above.
	fclose( $handle );

	return true;
}

/**
 * The daily cleanup event: removes the exports older than a day and their log rows.
 */
function exports_reports_cleanup_event() {
	exports_reports_cleanup();
}

/**
 * The content types of the export files, by extension (pipe and custom delimited exports are .txt).
 *
 * @return array<string, string>
 */
function exports_reports_export_mime_types() {
	return array(
		'csv'  => 'text/csv; charset=utf-8',
		'tsv'  => 'text/tab-separated-values; charset=utf-8',
		'txt'  => 'text/plain; charset=utf-8',
		'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
		'xml'  => 'application/xml; charset=utf-8',
		'json' => 'application/json; charset=utf-8',
		'pdf'  => 'application/pdf',
	);
}

/**
 * Whether a file name is one an export writes: an export extension, and not one of the files that keep
 * the directory from being served. The functions that remove files from the export directory remove
 * only these, in case the directory is shared.
 *
 * @param mixed $filename The file name.
 *
 * @return bool
 */
function exports_reports_is_export_file( $filename ) {
	$filename = (string) $filename;

	if ( '' === $filename || in_array( $filename, array( 'index.php', '.htaccess' ), true ) ) {
		return false;
	}

	return isset( exports_reports_export_mime_types()[ strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ] );
}

/**
 * Whether the current user may download an export file: the file is in the export log, and the user
 * may view the report it belongs to.
 *
 * @param string $filename The file name.
 *
 * @return bool
 */
function exports_reports_user_can_download_export( $filename ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$filename = basename( (string) $filename );

	if ( ! exports_reports_is_export_file( $filename ) ) {
		return false;
	}

	$report_id = $wpdb->get_var( $wpdb->prepare( "SELECT `report_id` FROM {$wpdb->exports_reports_log} WHERE `filename` = %s ORDER BY `id` DESC LIMIT 1", $filename ) );

	if ( null === $report_id ) {
		return false;
	}

	$report = exports_reports_get_report( (int) $report_id );
	$group  = $report ? exports_reports_get_group( (int) $report->group ) : null;

	return $report && $group && exports_reports_user_can_view_report( $report, $group );
}

/**
 * Logs a written export file against its report (the exports_reports_post_export action).
 *
 * @since 1.0.0 Takes the file name and the screen, as the action passes them.
 *
 * @param string                  $filename The file name.
 * @param Exports_Reports_Admin_UI $admin    The report screen that wrote it.
 */
function exports_reports_log( $filename, $admin ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$wpdb->insert(
		$wpdb->exports_reports_log,
		array(
			'report_id' => $admin->report_id,
			'filename'  => $filename,
			'created'   => date_i18n( 'Y-m-d H:i:s' ),
		),
		array(
			'%d',
			'%s',
			'%s',
		)
	);
}

/**
 * Removes the log row of a deleted export file (the exports_reports_post_remove_export action).
 *
 * @since 1.0.0 Takes the file name, whether it is gone, its path and the screen, as the action passes them.
 *
 * @param string                  $filename The file name.
 * @param bool                    $removed  Whether the file is gone.
 * @param string                  $path     Its path.
 * @param Exports_Reports_Admin_UI $admin    The report screen.
 */
function exports_reports_delete_log( $filename, $removed, $path, $admin ) {
	/** @var wpdb $wpdb */ global $wpdb;

	if ( $removed ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->exports_reports_log} WHERE `report_id` = %d AND `filename` = %s", $admin->report_id, $filename ) );
	}
}

/**
 * Schedules the daily cleanup event, replacing the one already there.
 *
 * @return bool What wp_schedule_event() returns.
 */
function exports_reports_schedule_cleanup() {
	wp_clear_scheduled_hook( 'exports_reports_cleanup' );

	return wp_schedule_event( time(), 'daily', 'exports_reports_cleanup' );
}

/**
 * Puts the daily cleanup event back when it is missing, as after a site has cleared its cron table.
 *
 * @since 0.9.5
 */
function exports_reports_ensure_cleanup_scheduled() {
	if ( false === wp_next_scheduled( 'exports_reports_cleanup' ) ) {
		exports_reports_schedule_cleanup();
	}
}

/**
 * Delete all files in a directory
 *
 * @param string|null $directory
 */
function exports_reports_delete_dir_files( $directory ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';

	/** @var WP_Filesystem_Base|null $wp_filesystem */ global $wp_filesystem;

	WP_Filesystem();

	if ( ! $wp_filesystem ) {
		return;
	}

	if ( ! $wp_filesystem->is_dir( $directory ) ) {
		return;
	}

	$file_list = $wp_filesystem->dirlist( $directory, false );

	// Export files only: the directory may be shared, and the files that keep it from being served stay.
	foreach ( (array) $file_list as $file ) {
		if ( 'f' === $file['type'] && exports_reports_is_export_file( $file['name'] ) ) {
			$wp_filesystem->delete( $directory . DIRECTORY_SEPARATOR . $file['name'] );
		}
	}
}

/**
 * The daily cleanup: removes the export files older than a day and their log rows. With $full every
 * export file and the whole log go (the Clear Now button).
 *
 * @param bool $full Whether to remove everything.
 *
 * @return bool Whether anything was removed; always false for a full clear.
 */
function exports_reports_cleanup( $full = false ) {
	/** @var wpdb $wpdb */ global $wpdb;

	if ( $full ) {
		$wpdb->query( "TRUNCATE {$wpdb->exports_reports_log}" );

		exports_reports_delete_dir_files( EXPORTS_REPORTS_EXPORT_DIR );
	} else {
		$purge_age = 1; // day(s) in age to purge

		$cleanup = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->exports_reports_log} WHERE `created` < DATE_ADD( NOW(), INTERVAL -%d DAY )", $purge_age ) );

		if ( ! empty( $cleanup ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';

			/** @var WP_Filesystem_Base|null $wp_filesystem */ global $wp_filesystem;

			WP_Filesystem();

			foreach ( $cleanup as $export ) {
				if ( $wp_filesystem ) {
					$file = EXPORTS_REPORTS_EXPORT_DIR . '/' . str_replace( array( '/', '..' ), '', $export->filename );

					$wp_filesystem->delete( $file );
				}

				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->exports_reports_log} WHERE `id` = %d", $export->id ) );
			}

			return true;
		}
	}

	return false;
}

/**
 * Filter the robots.txt contents and add the export directory to the list of disallowed folders.
 *
 * @since 0.9.0
 *
 * @param string $robots_txt The robots.txt contents.
 *
 * @return string The robots.txt contents.
 */
function exports_reports_robots_txt( $robots_txt ) {
	$exclude_path = trim( (string) wp_parse_url( EXPORTS_REPORTS_EXPORT_URL, PHP_URL_PATH ), '/' );

	if ( '' === $exclude_path ) {
		return $robots_txt;
	}

	return $robots_txt . "\nDisallow: /" . $exclude_path . '/';
}

/**
 * The export formats the plugin offers: the label of each button by the type behind it.
 *
 * @since 1.1.0
 *
 * @return array<string, string>
 */
function exports_reports_export_formats() {
	return array(
		'csv'  => 'CSV',
		'tsv'  => 'TSV',
		'pipe' => 'TXT',
		'xlsx' => 'XLSX',
		'xml'  => 'XML',
		'json' => 'JSON',
		'pdf'  => 'PDF',
	);
}

/**
 * The formats the site offers: every format but the ones turned off on the Settings page. The setting
 * holds what is off, so a format a later version adds is on until someone turns it off.
 *
 * @since 1.1.0
 * @since 1.1.1 The setting names the formats turned off (exports_reports_export_formats_off).
 *
 * @return string[] The types, in the order of exports_reports_export_formats().
 */
function exports_reports_site_export_formats() {
	$all = array_keys( exports_reports_export_formats() );
	$off = get_option( 'exports_reports_export_formats_off', array() );

	if ( ! is_array( $off ) || empty( $off ) ) {
		return $all;
	}

	return array_values( array_diff( $all, array_map( 'strval', $off ) ) );
}

/**
 * Turns the setting 1.1.0 kept, the formats that were on, into the one kept since 1.1.1, the formats that
 * are off, and removes the old one. Runs on the upgrade to 1.1.1.
 *
 * @since 1.1.1
 */
function exports_reports_convert_export_formats_setting() {
	$on = get_option( 'exports_reports_export_formats', null );

	if ( is_array( $on ) ) {
		update_option( 'exports_reports_export_formats_off', array_values( array_diff( array_keys( exports_reports_export_formats() ), array_map( 'strval', $on ) ) ) );
	}

	delete_option( 'exports_reports_export_formats' );
}

/**
 * The formats a report offers: the site's, narrowed to the report's own list when its definition has one.
 *
 * @since 1.1.0
 *
 * @param object $report A row of the reports table.
 *
 * @return string[] The types.
 */
function exports_reports_report_export_formats( $report ) {
	$formats = exports_reports_site_export_formats();
	$own     = isset( $report->export_formats ) ? array_filter( array_map( 'trim', explode( ',', (string) $report->export_formats ) ) ) : array();

	if ( ! empty( $own ) ) {
		$formats = array_values( array_intersect( $formats, $own ) );
	}

	/**
	 * Filters the export formats a report offers.
	 *
	 * @since 1.1.0
	 *
	 * @param string[] $formats The types (csv, tsv, pipe, xlsx, xml, json, pdf).
	 * @param object   $report  The row of the reports table.
	 */
	return apply_filters( 'exports_reports_report_export_formats', $formats, $report );
}

/**
 * Writes an export for the report screen's buttons over admin-ajax: the screen's own export, with the
 * page's search, sort and filters from the query string and the format from the posted form. The answer
 * carries the message the screen would show and the address of the download.
 *
 * @since 1.1.0
 */
function exports_reports_ajax_export() {
	check_ajax_referer( 'exports-reports-export-request' );

	// phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended -- checked above.
	$report_id = isset( $_POST['report'] ) ? absint( wp_unslash( $_POST['report'] ) ) : 0;

	if ( 0 === $report_id && isset( $_GET['report'] ) ) {
		$report_id = absint( wp_unslash( $_GET['report'] ) );
	}
	// phpcs:enable

	$report = exports_reports_get_report( $report_id );
	$group  = $report ? exports_reports_get_group( (int) $report->group ) : null;

	if ( ! $report || ! $group || ! exports_reports_user_can_view_report( $report, $group ) ) {
		wp_send_json_error( array( 'html' => '<div id="message" class="error fade"><p>' . esc_html__( 'You are not allowed to export this report.', 'exports-and-reports' ) . '</p></div>' ), 403 );
	}

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

	// A large export outlives the request's defaults.
	// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- a large export may need longer than max_execution_time.
	set_time_limit( 6000 );
	ignore_user_abort( true );

	$admin    = new Exports_Reports_Admin_UI( exports_reports_report_options( $report ) );
	$removing = '' !== $admin->removing_export();

	if ( 'export' !== $admin->action || ( ! $removing && false === $admin->export_type ) ) {
		wp_send_json_error( array( 'html' => '<div id="message" class="error fade"><p>' . esc_html__( 'That export format is not offered for this report.', 'exports-and-reports' ) . '</p></div>' ), 403 );
	}

	ob_start();
	$admin->export();
	// The screen's messages, without the script that opens the download: the page's script does that itself.
	$html = (string) preg_replace( '#<script\b[^>]*>.*?</script>#is', '', (string) ob_get_clean() );

	if ( $removing ) {
		// The message says whether the file is gone.
		wp_send_json_success( array( 'html' => $html ) );
	}

	if ( empty( $admin->exported_file ) ) {
		wp_send_json_error( array( 'html' => $html ) );
	}

	wp_send_json_success(
		array(
			'html'     => $html,
			'file'     => (string) $admin->exported_file,
			'download' => $admin->export_url . rawurlencode( (string) $admin->exported_file ),
		)
	);
}
