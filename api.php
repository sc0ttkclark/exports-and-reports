<?php
// @todo Replace with REST API endpoint

global $wpdb;

//define('WP_DEBUG',true);

if ( ! is_object( $wpdb ) ) {
	ob_start();
	require_once( '../../../wp-load.php' );
	ob_end_clean();
}

if ( ! defined( 'WP_ADMIN_UI_EXPORT_URL' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_URL', WP_CONTENT_URL . '/exports' );
}

if ( ! defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_DIR', WP_CONTENT_DIR . '/exports' );
}

if ( ! function_exists( 'is_plugin_active' ) || ! is_plugin_active( basename( dirname( __FILE__ ) ) . '/exports-and-reports.php' ) ) {
	wp_die( 'Exports and Reports plugin not activated' );
}

set_time_limit( 6000 );
@ini_set( 'zlib.output_compression', 0 );
@ini_set( 'output_buffering', 'off' );
@ini_set( 'memory_limit', '64M' );
ignore_user_abort( true );

if ( ! headers_sent() ) {
	status_header( 200 );
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
}

$wpdb->query( 'SET session wait_timeout = 800' );

$check = get_option( 'exports_reports_token' );

if ( ! function_exists( 'wp_send_json_success' ) ) {
	wp_die( 'WordPress 3.5+ is required to use this feature' );
} elseif ( ! isset( $_GET['token'] ) || $_GET['token'] != $check ) {
	wp_send_json_error( 'Invalid Token' );
} elseif ( empty( $_GET['report'] ) ) {
	wp_send_json_error( 'Invalid Report' );
} elseif ( empty( $_GET['export_type'] ) ) {
	wp_send_json_error( 'Invalid Export Type' );
}

$_GET['export_type'] = strtolower( $_GET['export_type'] );

// Run export
$report = absint( $_GET['report'] );
$report = $wpdb->get_row( 'SELECT * FROM `' . EXPORTS_REPORTS_TBL . 'reports` WHERE `id`=' . $report . ' LIMIT 1' );

if ( empty( $report ) ) {
	wp_send_json_error( 'Report not found' );
} else {
	$report_data                    = array();
	$report_data['name']            = $report->name;
	$report_data['sql_query']       = $report->sql_query;
	$report_data['sql_query_count'] = $report->sql_query_count;
	$report_data['default_none']    = $report->default_none;
	$report_data['export']          = ( 0 === (int) $report->disable_export ? true : false );
	$report_data['field_data']      = $report->field_data;

	if ( ! $report_data['export'] ) {
		wp_send_json_error( 'Exports disabled for this report.' );
	}

	require_once EXPORTS_REPORTS_DIR . 'wp-admin-ui/Admin.class.php';
	$options                 = array();
	$options['css']          = EXPORTS_REPORTS_URL . 'assets/admin.css';
	$options['readonly']     = true;
	$options['identifier']   = true;
	$options['export']       = $report_data['export'];
	$options['search']       = ( strlen( $report_data['field_data'] ) > 0 ? true : false );
	$options['default_none'] = ( 1 === (int) $report_data['default_none'] ? true : false );
	$options['sql']          = trim( $report_data['sql_query'] );
	$options['sql_count']    = trim( $report_data['sql_query_count'] );
	if ( empty( $options['sql_count'] ) ) {
		unset( $options['sql_count'] );
	}
	$options['item']    = $options['items'] = $report_data['name'];
	$options['icon']    = EXPORTS_REPORTS_URL . 'assets/icons/32.png';
	$options['heading'] = array( 'manage' => 'View Report:' );
	$field_data         = @json_decode( $report_data['field_data'], true );
	if ( is_array( $field_data ) && ! empty( $field_data ) ) {
		$options['columns'] = array();
		foreach ( $field_data as $field ) {
			$field                                = exports_reports_field_defaults( $field );
			$options['columns'][ $field['name'] ] = array();
			if ( 0 < strlen( $field['real_name'] ) ) {
				$options['columns'][ $field['name'] ]['real_name'] = $field['real_name'];
			}
			if ( 0 < strlen( $field['label'] ) ) {
				$options['columns'][ $field['name'] ]['label'] = $field['label'];
			}
			if ( 0 < strlen( $field['filter_label'] ) ) {
				$options['columns'][ $field['name'] ]['filter_label'] = $field['filter_label'];
			}
			if ( 0 < strlen( $field['custom_display'] ) ) {
				$options['columns'][ $field['name'] ]['custom_display'] = $field['custom_display'];
			}
			if ( 0 < strlen( $field['type'] ) ) {
				$options['columns'][ $field['name'] ]['type'] = $field['type'];
			}
			if ( 1 === (int) $field['hide_report'] ) {
				$options['columns'][ $field['name'] ]['display'] = false;
			}
			if ( 1 === (int) $field['hide_export'] ) {
				$options['columns'][ $field['name'] ]['export'] = false;
			}
			if ( 1 === (int) $field['search'] ) {
				$options['columns'][ $field['name'] ]['search'] = false;
			} else {
				$options['columns'][ $field['name'] ]['search'] = true;
			}
			if ( 1 === (int) $field['filter'] ) {
				$options['columns'][ $field['name'] ]['filter'] = true;
			}
			if ( 1 === (int) $field['filter'] ) {
				if ( 0 < strlen( $field['filter_default'] ) ) {
					$options['columns'][ $field['name'] ]['filter_default'] = $field['filter_default'];
				}
				if ( 0 < strlen( $field['filter_ongoing'] ) ) {
					$options['columns'][ $field['name'] ]['date_ongoing'] = $field['filter_ongoing'];
					if ( 0 < strlen( $field['filter_ongoing_default'] ) ) {
						$options['columns'][ $field['name'] ]['filter_ongoing_default'] = $field['filter_ongoing_default'];
					}
				}
			}
			if ( 1 === (int) $field['total_field'] ) {
				$options['columns'][ $field['name'] ]['total_field'] = true;
			}
			if ( 1 === (int) $field['group_related'] ) {
				$options['columns'][ $field['name'] ]['group_related'] = true;
			}
			if ( 'related' === $field['type'] ) {
				if ( 0 < strlen( $field['related'] ) ) {
					$options['columns'][ $field['name'] ]['related'] = $field['related'];
				}
				if ( 0 < strlen( $field['related_field'] ) ) {
					$options['columns'][ $field['name'] ]['related_field'] = $field['related_field'];
				}
				if ( 0 < strlen( $field['related_sql'] ) ) {
					$options['columns'][ $field['name'] ]['related_sql'] = $field['related_sql'];
				}
			}
		}
	}
	$options['report_id'] = $report->id;

	if ( ! isset( $_GET['action'] ) ) {
		$_GET['action'] = 'export';
	} // Force manage action (mainly for action=json)
	else {
		$_GET['action'] = 'manage';
	}

	$data = false;

	$download = false;

	ob_start();
	$admin         = new WP_Admin_UI( $options );
	$admin->action = 'export';

	if ( 'manage' === $_GET['action'] ) {
		if ( isset( $_GET['full'] ) && 1 === (int) $_GET['full'] ) {
			$data = $admin->get_data( true );
		} else {
			$data = $admin->get_data();
		}
	} else {
		$admin->go();

		if ( $admin->exported_file ) {
			$data = array(
				'export_file' => WP_ADMIN_UI_EXPORT_URL . '/' . $admin->exported_file,
				'message'     => 'Report exported'
			);

			if ( ! empty( $_GET['exports_and_reports_download'] ) ) {
				$download = true;
			}
		} else {
			$data = new WP_Error( 'exports-reports-failed-export', 'Report failed to export' );
		}
	}

	$ui = ob_get_clean();

	preg_match( '/<div id="message" class="error fade"><p><strong>Error:<\/strong> (.*)<\/p><\/div>/i', $ui, $error );

	if ( ! empty( $error ) ) {
		$error = $error[1];

		wp_send_json_error( $error );
	} elseif ( is_wp_error( $data ) ) {
		/**
		 * @var $data WP_Error
		 */
		wp_send_json_error( $data->get_error_message() );
	} elseif ( ! empty( $data ) ) {
		if ( $download && ! empty( $data['export_file'] ) ) {
			do_action( 'wp_admin_ui_export_download' );
			$file = $data['export_file'];
			$file = realpath( $file );
			if ( ! file_exists( $file ) ) {
				wp_send_json_error( 'Report failed to export' );
			}
			// required for IE, otherwise Content-disposition is ignored
			if ( ini_get( 'zlib.output_compression' ) ) {
				ini_set( 'zlib.output_compression', 'Off' );
			}
			header( "Pragma: public" ); // required
			header( "Expires: 0" );
			header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
			header( "Cache-Control: private", false ); // required for certain browsers
			header( "Content-Type: application/force-download" );
			header( "Content-Disposition: attachment; filename=\"" . basename( $file ) . "\";" );
			header( "Content-Transfer-Encoding: binary" );
			header( "Content-Length: " . filesize( $file ) );
			flush();
			readfile( "$file" );
			exit();
		}

		wp_send_json_success( $data );
	} else {
		wp_send_json_success( 'Report exported' );
	}
}
