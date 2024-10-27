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
if ( isset( $_GET['exports_and_reports_download'] ) && isset( $_GET['_wpnonce'] ) && false !== wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'wp-admin-ui-export' ) ) {
	do_action( 'wp_admin_ui_export_download' );

	$file = WP_ADMIN_UI_EXPORT_DIR . '/' . str_replace( [ '/', '..' ], '', sanitize_text_field( $_GET['exports_and_reports_export'] ) );
	$file = realpath( $file );

	$file_url = WP_ADMIN_UI_EXPORT_URL . '/' . str_replace( [ '/', '..' ], '', sanitize_text_field( $_GET['exports_and_reports_export'] ) );

	if ( ! $file || ! isset( $_GET['exports_and_reports_export'] ) || empty( $_GET['exports_and_reports_export'] ) || ! file_exists( $file ) ) {
		wp_die( 'File not found.' );
	}

	wp_redirect( $file_url );
	die();
}
