<?php
/**
 * Exports and Reports
 *
 * @package   ExportsReports
 * @author    Scott Kingsley Clark, Daniel Caspi
 * @copyright 2024-2026 Scott Kingsley Clark, Daniel Caspi
 * @license   GPL v2 or later
 *
 * Plugin Name:       Exports and Reports
 * Plugin URI:        https://github.com/sc0ttkclark/exports-and-reports
 * Description:       Turn any MySQL SELECT query into a report your users can search, filter and export to CSV, XLSX, PDF, JSON or XML.
 * Version:           1.2.9
 * Author:            Scott Kingsley Clark, Daniel Caspi
 * Author URI:        https://www.element26.net/
 * Text Domain:       exports-and-reports
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.7
 * Requires PHP:      7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EXPORTS_REPORTS_TBL', 'exportsreports_' );
define( 'EXPORTS_REPORTS_VERSION', '1.2.9' );
define( 'EXPORTS_REPORTS_URL', plugin_dir_url( __FILE__ ) );
define( 'EXPORTS_REPORTS_DIR', plugin_dir_path( __FILE__ ) );

// The export directory and its URL: a folder of the uploads directory, unless wp-config.php defines the
// constants. The names before 0.9.5, WP_ADMIN_UI_EXPORT_DIR and WP_ADMIN_UI_EXPORT_URL, are still read.
if ( ! defined( 'EXPORTS_REPORTS_EXPORT_DIR' ) ) {
	define( 'EXPORTS_REPORTS_EXPORT_DIR', defined( 'WP_ADMIN_UI_EXPORT_DIR' ) ? WP_ADMIN_UI_EXPORT_DIR : wp_get_upload_dir()['basedir'] . '/exports-and-reports' );
}

if ( ! defined( 'EXPORTS_REPORTS_EXPORT_URL' ) ) {
	define( 'EXPORTS_REPORTS_EXPORT_URL', defined( 'WP_ADMIN_UI_EXPORT_URL' ) ? WP_ADMIN_UI_EXPORT_URL : wp_get_upload_dir()['baseurl'] . '/exports-and-reports' );
}

// The admin-ajax action that serves an export file, and the nonce action of its link.
define( 'EXPORTS_REPORTS_EXPORT_ACTION', 'exports_reports_export' );
define( 'EXPORTS_REPORTS_EXPORT_NONCE', 'exports-reports-export' );

// The fewest characters a token typed into the Settings page may have; a generated one has 32.
define( 'EXPORTS_REPORTS_TOKEN_MIN_LENGTH', 24 );

/**
 * The plugin's tables as properties of $wpdb ($wpdb->exports_reports_groups, $wpdb->exports_reports_reports and
 * $wpdb->exports_reports_log), named the way core names its own, so a statement writes them in place. Set again
 * when the site switches, as the prefix does.
 *
 * @since 0.9.5
 */
function exports_reports_tables() {
	global $wpdb;

	foreach ( array( 'groups', 'reports', 'log' ) as $table ) {
		$wpdb->{'exports_reports_' . $table} = $wpdb->prefix . EXPORTS_REPORTS_TBL . $table;
	}
}
exports_reports_tables();
add_action( 'switch_blog', 'exports_reports_tables' );

register_activation_hook( __FILE__, 'exports_reports_activate' );
register_deactivation_hook( __FILE__, 'exports_reports_deactivate' );

add_action( 'admin_init', 'exports_reports_init' );
add_action( 'admin_init', 'exports_reports_ensure_cleanup_scheduled' );
add_action( 'admin_menu', 'exports_reports_menu' );
add_action( 'admin_menu', 'exports_reports_admin_menu' );

add_action( 'wp_ajax_' . EXPORTS_REPORTS_EXPORT_ACTION, 'exports_reports_export_download' );
add_action( 'wp_ajax_exports_reports_export_report', 'exports_reports_ajax_export' );
add_action( 'wp_ajax_exports_reports_detect_columns', 'exports_reports_ajax_detect_columns' );
add_action( 'exports_reports_cleanup', 'exports_reports_cleanup_event' );
add_action( 'admin_enqueue_scripts', 'exports_reports_assets' );
add_action( 'in_admin_header', 'exports_reports_flush_admin_header' );
add_action( 'admin_notices', 'exports_reports_charset_errors_notice' );
add_action( 'admin_notices', 'exports_reports_display_callbacks_notice' );
add_action( 'admin_init', 'exports_reports_dismiss_notice' );
add_action( 'exports_reports_post_export', 'exports_reports_log', 10, 2 );
add_action( 'exports_reports_post_remove_export', 'exports_reports_delete_log', 10, 4 );
add_filter( 'site_status_tests', 'exports_reports_site_status_tests' );

require_once EXPORTS_REPORTS_DIR . 'vendor/autoload.php';

require_once EXPORTS_REPORTS_DIR . 'vendor/vendor-prefixed/autoload.php';
require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-value-format.php';
require_once EXPORTS_REPORTS_DIR . 'includes/capabilities.php';
require_once EXPORTS_REPORTS_DIR . 'includes/install.php';
require_once EXPORTS_REPORTS_DIR . 'includes/admin-menus.php';
require_once EXPORTS_REPORTS_DIR . 'includes/admin-settings.php';
require_once EXPORTS_REPORTS_DIR . 'includes/admin-groups.php';
require_once EXPORTS_REPORTS_DIR . 'includes/admin-reports.php';
require_once EXPORTS_REPORTS_DIR . 'includes/reports.php';
require_once EXPORTS_REPORTS_DIR . 'includes/display-functions.php';
require_once EXPORTS_REPORTS_DIR . 'includes/exports.php';
require_once EXPORTS_REPORTS_DIR . 'includes/export-dir-check.php';
require_once EXPORTS_REPORTS_DIR . 'includes/rest-api.php';
