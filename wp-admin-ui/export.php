<?php

if ( ! defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ) {
	define( 'WP_ADMIN_UI_EXPORT_DIR', WP_CONTENT_DIR . '/exports' );
}

/** @var wpdb $wpdb */
global $wpdb;

if ( ! is_object( $wpdb ) ) {
	wp_die( 'Access denied' );
}
// FOR EXPORTS ONLY
if ( isset( $_GET['exports_and_reports_download'] ) && isset( $_GET['_wpnonce'] ) && false !== wp_verify_nonce( $_GET['_wpnonce'], 'wp-admin-ui-export' ) ) {
	do_action( 'wp_admin_ui_export_download' );

	$file = WP_ADMIN_UI_EXPORT_DIR . '/' . str_replace( [ '/', '..' ], '', $_GET['exports_and_reports_export'] );
	$file = realpath( $file );

	$file_url = WP_ADMIN_UI_EXPORT_URL . '/' . str_replace( [ '/', '..' ], '', $_GET['exports_and_reports_export'] );

	if ( ! isset( $_GET['exports_and_reports_export'] ) || empty( $_GET['exports_and_reports_export'] ) || ! file_exists( $file ) ) {
		wp_die( 'File not found.' );
	}

	wp_redirect( $file_url );
	die();

	// required for IE, otherwise Content-disposition is ignored
	if ( ini_get( 'zlib.output_compression' ) ) {
		ini_set( 'zlib.output_compression', 'Off' );
	}

	header( 'Pragma: public' ); // required
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Cache-Control: private', false ); // required for certain browsers
	header( 'Content-Type: application/force-download' );
	header( 'Content-Disposition: attachment; filename="' . basename( $file ) . '";' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Content-Length: ' . filesize( $file ) );
	flush();
	readfile( "$file" );
	exit();
}
