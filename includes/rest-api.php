<?php
/**
 * REST API: report data and exports for scripts and cronjobs.
 *
 * Routes under exports-reports/v1, answered either for a logged-in user who may view the report
 * (cookie and nonce, or an application password) or for the API token from the Settings page, sent
 * as "Authorization: Bearer <token>" or as the token query parameter. The token is stored as a hash,
 * like a password (exports_reports_token_hash()).
 *
 *   GET  /reports/<id>/data?full=1|pg=N&limit=N   the rows (paged, or all of them)
 *   POST /reports/<id>/export?type=csv[&download=1]   write an export; with download=1 the file is the response
 *   GET  /reports/<id>/exports/<file>               download an export of that report
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

add_action( 'rest_api_init', 'exports_reports_rest_routes' );
add_filter( 'rest_pre_serve_request', 'exports_reports_rest_serve_file', 10, 3 );

/**
 * Registers the routes.
 *
 * @since 0.9.5
 */
function exports_reports_rest_routes() {
	$id = array(
		'type'              => 'integer',
		'required'          => true,
		'sanitize_callback' => 'absint',
	);

	register_rest_route(
		'exports-reports/v1',
		'/reports/(?P<id>\d+)/data',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'exports_reports_rest_data',
			'permission_callback' => 'exports_reports_rest_permission',
			'args'                => array(
				'id'    => $id,
				'full'  => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'pg'    => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'limit' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		)
	);

	register_rest_route(
		'exports-reports/v1',
		'/reports/(?P<id>\d+)/export',
		array(
			'methods'             => array( WP_REST_Server::READABLE, WP_REST_Server::CREATABLE ),
			'callback'            => 'exports_reports_rest_export',
			'permission_callback' => 'exports_reports_rest_permission',
			'args'                => array(
				'id'       => $id,
				'type'     => array(
					'type'    => 'string',
					'enum'    => array( 'csv', 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' ),
					'default' => 'csv',
				),
				'download' => array(
					'type'    => 'boolean',
					'default' => false,
				),
			),
		)
	);

	register_rest_route(
		'exports-reports/v1',
		'/reports/(?P<id>\d+)/exports/(?P<file>[A-Za-z0-9._-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'exports_reports_rest_download',
			'permission_callback' => 'exports_reports_rest_permission',
			'args'                => array(
				'id'   => $id,
				'file' => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_file_name',
				),
			),
		)
	);
}

/**
 * Whether the request may read the report: the API token, or a logged-in user the Reports menu
 * would show the report to.
 *
 * @since 0.9.5
 *
 * @param WP_REST_Request $request The request.
 * @return true|WP_Error
 */
function exports_reports_rest_permission( $request ) {
	$token_matches = exports_reports_rest_token_matches( $request );

	// A caller without a credential learns nothing about which ids exist.
	if ( ! $token_matches && ! is_user_logged_in() ) {
		return new WP_Error( 'exports_reports_unauthorized', __( 'Send the token or log in to read reports.', 'exports-and-reports' ), array( 'status' => 401 ) );
	}

	$report = exports_reports_get_report( (int) $request['id'] );
	$group  = $report ? exports_reports_get_group( (int) $report->group ) : null;

	if ( ! $report || ! $group ) {
		return new WP_Error( 'exports_reports_not_found', __( 'No report with that id.', 'exports-and-reports' ), array( 'status' => 404 ) );
	}

	if ( $token_matches || exports_reports_user_can_view_report( $report, $group ) ) {
		return true;
	}

	return new WP_Error( 'exports_reports_forbidden', __( 'You are not allowed to read this report.', 'exports-and-reports' ), array( 'status' => rest_authorization_required_code() ) );
}

/**
 * Whether the request carries the API token, in the Authorization header or the token parameter.
 *
 * @since 0.9.5
 * @since 1.2.0 Compared against the token's hash.
 *
 * @param WP_REST_Request $request The request.
 * @return bool
 */
function exports_reports_rest_token_matches( $request ) {
	$given = (string) $request->get_param( 'token' );
	$auth  = (string) $request->get_header( 'authorization' );

	if ( 0 === stripos( $auth, 'Bearer ' ) ) {
		$given = trim( substr( $auth, 7 ) );
	}

	return exports_reports_token_matches( $given );
}

/**
 * Whether a token is the site's API token: checked against the stored hash, the way core checks a
 * password.
 *
 * @since 1.2.0
 *
 * @param string $given The token a request carries.
 * @return bool False when token access is off.
 */
function exports_reports_token_matches( $given ) {
	$hash = exports_reports_token_hash();

	if ( '' === $hash || '' === $given ) {
		return false;
	}

	return wp_check_password( $given, $hash );
}

/**
 * The hash of the API token, or '' when token access is off.
 *
 * Versions before 1.2.0 stored the token itself; one still stored that way is hashed here on first use
 * and the stored token removed, so a cronjob keeps working before an administrator has visited the
 * admin (where the upgrade routine runs) and the token is never kept in the clear once this version
 * has run.
 *
 * @since 1.2.0
 *
 * @return string
 */
function exports_reports_token_hash() {
	$hash = (string) get_option( 'exports_reports_token_hash', '' );

	if ( '' !== $hash ) {
		return $hash;
	}

	$legacy = (string) get_option( 'exports_reports_token', '' );

	if ( '' === $legacy ) {
		return '';
	}

	exports_reports_set_token( $legacy );

	return (string) get_option( 'exports_reports_token_hash', '' );
}

/**
 * Stores the hash of an API token, or turns token access off. The token itself is not kept: the
 * Settings page shows it once, when it is set.
 *
 * @since 1.2.0
 *
 * @param string $token The token; '' turns token access off.
 */
function exports_reports_set_token( $token ) {
	delete_option( 'exports_reports_token' );

	if ( '' === $token ) {
		delete_option( 'exports_reports_token_hash' );

		return;
	}

	update_option( 'exports_reports_token_hash', wp_hash_password( $token ), false );
}

/**
 * Generates an API token, stores its hash and returns the token, for the Settings page to show once.
 *
 * @since 1.2.0
 *
 * @return string The token.
 */
function exports_reports_generate_token() {
	$token = wp_generate_password( 32, false );

	exports_reports_set_token( $token );

	return $token;
}

/**
 * The rows of a report, paged or all of them.
 *
 * @since 0.9.5
 *
 * @param WP_REST_Request $request The request.
 * @return WP_REST_Response|WP_Error
 */
function exports_reports_rest_data( $request ) {
	$report = exports_reports_get_report( (int) $request['id'] );

	if ( ! $report ) {
		return new WP_Error( 'exports_reports_not_found', __( 'No report with that id.', 'exports-and-reports' ), array( 'status' => 404 ) );
	}

	require_once __DIR__ . '/class-exports-reports-admin-ui.php';

	$admin      = new Exports_Reports_Admin_UI( exports_reports_report_options( $report ) );
	$admin->api = true;
	$full       = (bool) $request['full'];

	if ( ! $full ) {
		$admin->page = max( 1, (int) $request['pg'] );

		if ( null !== $request['limit'] ) {
			$admin->limit = max( 1, (int) $request['limit'] );
		}
	}

	ob_start();
	$rows  = $admin->get_data( $full );
	$error = exports_reports_rest_ui_error( (string) ob_get_clean() );

	if ( '' !== $admin->query_error ) {
		// The database's message to the token and to a user who may edit reports.
		$error = wp_strip_all_tags( $admin->query_error_message( exports_reports_rest_token_matches( $request ) ? true : null ) );
	}

	if ( '' !== $error || ! is_array( $rows ) ) {
		return new WP_Error( 'exports_reports_query_failed', '' !== $error ? $error : __( 'The report query failed.', 'exports-and-reports' ), array( 'status' => 500 ) );
	}

	$data = array(
		'report' => (int) $report->id,
		'name'   => $report->name,
		'rows'   => array_values( $rows ),
		'total'  => $full ? count( $rows ) : (int) $admin->total,
	);

	if ( ! $full ) {
		$data['page']  = (int) $admin->page;
		$data['limit'] = (int) $admin->limit;
	}

	if ( ! empty( $admin->sum_data ) ) {
		$data['totals'] = $admin->sum_data;
	}

	return rest_ensure_response( $data );
}

/**
 * Writes an export of the report and names the file; with download=1 the file is the response.
 *
 * @since 0.9.5
 *
 * @param WP_REST_Request $request The request.
 * @return WP_REST_Response|WP_Error
 */
function exports_reports_rest_export( $request ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$report = exports_reports_get_report( (int) $request['id'] );

	if ( ! $report ) {
		return new WP_Error( 'exports_reports_not_found', __( 'No report with that id.', 'exports-and-reports' ), array( 'status' => 404 ) );
	}

	if ( 0 !== (int) $report->disable_export ) {
		return new WP_Error( 'exports_reports_export_disabled', __( 'Exports are disabled for this report.', 'exports-and-reports' ), array( 'status' => 403 ) );
	}

	if ( ! in_array( (string) $request['type'], exports_reports_report_export_formats( $report ), true ) ) {
		return new WP_Error( 'exports_reports_format_not_offered', __( 'That export format is not offered for this report.', 'exports-and-reports' ), array( 'status' => 403 ) );
	}

	require_once __DIR__ . '/class-exports-reports-admin-ui.php';

	// A large export outlives the request's defaults, and a cronjob that disconnects still gets its file.
	// phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- a large export may need longer than max_execution_time.
	set_time_limit( 6000 );
	ignore_user_abort( true );
	$wpdb->query( 'SET SESSION wait_timeout = 800' );

	$admin              = new Exports_Reports_Admin_UI( exports_reports_report_options( $report ) );
	$admin->api         = true;
	$admin->action      = 'export';
	$admin->export_type = (string) $request['type'];

	ob_start();
	$admin->export();
	$error = exports_reports_rest_ui_error( (string) ob_get_clean() );

	if ( '' !== $error || empty( $admin->exported_file ) ) {
		return new WP_Error( 'exports_reports_export_failed', '' !== $error ? $error : __( 'The export failed.', 'exports-and-reports' ), array( 'status' => 500 ) );
	}

	$file = (string) $admin->exported_file;

	return rest_ensure_response(
		array(
			'report'   => (int) $report->id,
			'name'     => $report->name,
			'type'     => (string) $request['type'],
			'file'     => $file,
			'url'      => rest_url( sprintf( 'exports-reports/v1/reports/%d/exports/%s', (int) $report->id, rawurlencode( $file ) ) ),
			'download' => (bool) $request['download'],
		)
	);
}

/**
 * Names an export of the report for download; the file itself is sent by exports_reports_rest_serve_file().
 *
 * @since 0.9.5
 *
 * @param WP_REST_Request $request The request.
 * @return WP_REST_Response|WP_Error
 */
function exports_reports_rest_download( $request ) {
	/** @var wpdb $wpdb */ global $wpdb;

	$file = basename( (string) $request['file'] );

	$logged = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->exports_reports_log} WHERE `report_id` = %d AND `filename` = %s", (int) $request['id'], $file ) );

	if ( 0 === $logged || ! is_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file ) ) {
		return new WP_Error( 'exports_reports_file_not_found', __( 'No such export of this report.', 'exports-and-reports' ), array( 'status' => 404 ) );
	}

	return rest_ensure_response(
		array(
			'report'   => (int) $request['id'],
			'file'     => $file,
			'download' => true,
		)
	);
}

/**
 * Sends the export file instead of the JSON when a response asks for it.
 *
 * @since 0.9.5
 *
 * @param bool             $served  Whether the request was served already.
 * @param WP_HTTP_Response $result  The response.
 * @param WP_REST_Request  $request The request.
 * @return bool
 */
function exports_reports_rest_serve_file( $served, $result, $request ) {
	if ( $served || 0 !== strpos( $request->get_route(), '/exports-reports/v1/reports/' ) || 200 !== $result->get_status() ) {
		return $served;
	}

	$data = $result->get_data();

	if ( ! is_array( $data ) || empty( $data['download'] ) || empty( $data['file'] ) ) {
		return $served;
	}

	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	if ( ! exports_reports_send_export_file( (string) $data['file'] ) ) {
		status_header( 404 );
		echo wp_json_encode(
			array(
				'code'    => 'exports_reports_file_not_found',
				'message' => __( 'No such export of this report.', 'exports-and-reports' ),
			)
		);
	}

	return true;
}

/**
 * The message of an error the admin UI printed, if it printed one.
 *
 * @since 0.9.5
 *
 * @param string $output What the admin UI echoed.
 * @return string The message, or an empty string.
 */
function exports_reports_rest_ui_error( $output ) {
	if ( preg_match( '/<div id="message" class="error fade"><p><strong>Error:<\/strong> (.*?)<\/p><\/div>/is', $output, $matches ) ) {
		return trim( wp_strip_all_tags( $matches[1] ) );
	}

	return '';
}
