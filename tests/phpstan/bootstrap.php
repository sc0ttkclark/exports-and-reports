<?php
/**
 * Two constants the main plugin file defines from its own location, for phpstan: it does not learn them
 * from exports-and-reports.php because it cannot evaluate plugin_dir_url() and plugin_dir_path(). The
 * directory is the real one, so the paths the plugin requires resolve.
 *
 * @package Exports_And_Reports
 */

define( 'EXPORTS_REPORTS_URL', 'https://example.com/wp-content/plugins/exports-and-reports/' );
define( 'EXPORTS_REPORTS_DIR', dirname( __DIR__, 2 ) . '/' );
