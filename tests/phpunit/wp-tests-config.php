<?php
/**
 * The configuration of the WordPress test library for this plugin's suite.
 *
 * Every setting that differs between machines is read from the environment, with the defaults of a
 * local run: the WordPress copy at /tmp/wp/latest/, the wordpress_test database as wp/wp on
 * localhost. CI sets the variables for each job of its matrix and uses this same file (the
 * WP_PHPUNIT__TESTS_CONFIG variable names it). The library DROPS every table carrying the prefix
 * below in the database named here: give the tests a database of their own.
 *
 * @package Exports_And_Reports
 */

if ( ! function_exists( 'exports_reports_tests_env' ) ) {
	/**
	 * An environment variable, or the default when it is not set.
	 *
	 * @param string $name     The variable.
	 * @param string $fallback The default.
	 * @return string
	 */
	function exports_reports_tests_env( $name, $fallback ) {
		$value = getenv( $name );

		return false === $value ? $fallback : $value;
	}
}

// The WordPress copy to test against, with a trailing slash. Its own wp-config.php is not read.
define( 'ABSPATH', rtrim( exports_reports_tests_env( 'WP_TESTS_ABSPATH', '/tmp/wp/latest/' ), '/\\' ) . '/' );

// The library's empty theme (data/themedir1/default), so the active theme adds nothing.
define( 'WP_DEFAULT_THEME', 'default' );

// Test with WordPress debug mode (default).
define( 'WP_DEBUG', true );

// ** Database settings ** //

define( 'DB_NAME', exports_reports_tests_env( 'WP_TESTS_DB_NAME', 'wordpress_test' ) );
define( 'DB_USER', exports_reports_tests_env( 'WP_TESTS_DB_USER', 'wp' ) );
define( 'DB_PASSWORD', exports_reports_tests_env( 'WP_TESTS_DB_PASSWORD', 'wp' ) );
define( 'DB_HOST', exports_reports_tests_env( 'WP_TESTS_DB_HOST', 'localhost' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Authentication keys and salts: fixed values serve a test database.
define( 'AUTH_KEY', 'exports-reports-tests-auth-key' );
define( 'SECURE_AUTH_KEY', 'exports-reports-tests-secure-auth-key' );
define( 'LOGGED_IN_KEY', 'exports-reports-tests-logged-in-key' );
define( 'NONCE_KEY', 'exports-reports-tests-nonce-key' );
define( 'AUTH_SALT', 'exports-reports-tests-auth-salt' );
define( 'SECURE_AUTH_SALT', 'exports-reports-tests-secure-auth-salt' );
define( 'LOGGED_IN_SALT', 'exports-reports-tests-logged-in-salt' );
define( 'NONCE_SALT', 'exports-reports-tests-nonce-salt' );

$table_prefix = 'wptests_'; // Only numbers, letters, and underscores please!

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Test Blog' );

// The PHP the library installs WordPress with, in a separate process: the one running the tests.
define( 'WP_PHP_BINARY', exports_reports_tests_env( 'WP_PHP_BINARY', '' !== PHP_BINARY ? PHP_BINARY : 'php' ) );

define( 'WPLANG', '' );

// The suite's uploads directory, and with it the plugin's export directory (a folder of the uploads),
// apart from the uploads of whatever site runs on the same WordPress copy.
define( 'UPLOADS', 'wp-content/uploads-tests' );

// The exports write through WP_Filesystem: the direct method, without the ownership probe that writes a
// temporary file into wp-content on every call.
define( 'FS_METHOD', 'direct' );
