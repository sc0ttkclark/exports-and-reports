<?php
/**
 * PHPUnit bootstrap: the WordPress test library with the plugin loaded as WordPress loads a plugin,
 * and the plugin's tables installed.
 *
 * Environment:
 *   WP_PHPUNIT__DIR           The wp-phpunit package; default vendor/wp-phpunit/wp-phpunit of this repository.
 *   WP_PHPUNIT__TESTS_CONFIG  The wp-tests-config.php to load; default tests/phpunit/wp-tests-config.php,
 *                             which reads its own settings from the environment.
 *
 * @package Exports_And_Reports
 */

$exports_reports_tests_root = dirname( __DIR__, 2 );

$exports_reports_tests_library = getenv( 'WP_PHPUNIT__DIR' );

if ( false === $exports_reports_tests_library || '' === $exports_reports_tests_library ) {
	$exports_reports_tests_library = $exports_reports_tests_root . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! file_exists( $exports_reports_tests_library . '/includes/functions.php' ) ) {
	fwrite( STDERR, 'The WordPress test library was not found at ' . $exports_reports_tests_library . '. Run composer install, or set WP_PHPUNIT__DIR to the wp-phpunit package.' . PHP_EOL );
	exit( 1 );
}

$exports_reports_tests_config = getenv( 'WP_PHPUNIT__TESTS_CONFIG' );

if ( false === $exports_reports_tests_config || '' === $exports_reports_tests_config ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );
}

// The PHPUnit Polyfills the library requires, when the PHPUnit that runs was not installed beside them.
if ( ! class_exists( 'Yoast\PHPUnitPolyfills\Autoload' ) && file_exists( $exports_reports_tests_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php' ) ) {
	require $exports_reports_tests_root . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
}

require $exports_reports_tests_library . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	function () use ( $exports_reports_tests_root ) {
		require $exports_reports_tests_root . '/exports-and-reports.php';
	}
);

require $exports_reports_tests_library . '/includes/bootstrap.php';

/*
 * The activation hook does not run here. The library drops and reinstalls WordPress's own tables for
 * each run; the plugin's tables are dropped the same way and installed from the schema, so a table left
 * by an earlier run does not refuse the schema's rows.
 */
global $wpdb;

foreach ( array( $wpdb->exports_reports_groups, $wpdb->exports_reports_reports, $wpdb->exports_reports_log ) as $exports_reports_tests_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$exports_reports_tests_table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

exports_reports_install_upgrade();

require __DIR__ . '/includes/trait-exports-reports-test-helpers.php';
require __DIR__ . '/includes/class-exports-reports-test-case.php';
