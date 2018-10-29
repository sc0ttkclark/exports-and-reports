<?php

/*
Plugin Name: Exports and Reports
Plugin URI: http://scottkclark.com/wordpress/exports-and-reports/
Description: Define custom exports / reports for users by creating each export / report and defining the fields as well as custom MySQL queries to run.
Version: 0.7.4
Author: Scott Kingsley Clark
Author URI: http://scottkclark.com/
*/

use ExportAndReports\ExportReports;

require 'includes\ExportReports.php';
require 'includes\ExportReportsAdmin.php';

define( 'EXPORTS_REPORTS_URL', plugin_dir_url( __FILE__ ) );
define( 'EXPORTS_REPORTS_DIR', plugin_dir_path( __FILE__ ) );

//
//install
//
register_activation_hook( __FILE__, array( 'ExportAndReports\ExportReports', 'install' ) );
//
//Main program
//
add_action('admin_init',['ExportAndReports\ExportReports','run']);
add_action('admin_init',['ExportAndReports\ExportReportsAdmin','run']);

//todo better handling
add_action('exports_reports_cleanup',['ExportAndReports\ExportReports','cleanup']);
add_action( 'wp_ajax_wp_admin_ui_export', ['ExportAndReports\ExportReports','wp_ui_export'] );
add_action( 'wp_admin_ui_post_export', ['ExportAndReports\ExportReports','log'], 10, 2 );
add_action( 'wp_admin_ui_post_remove_export', ['ExportAndReports\ExportReports','delete_log'], 10, 2 );










