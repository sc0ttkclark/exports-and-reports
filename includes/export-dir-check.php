<?php
/**
 * The check that the web server does not serve the export directory: the request for a file written into
 * it, the finding the Settings page shows, and the Site Health test.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether a directory is inside the part of the file system the web server serves: under the WordPress
 * directory, the content directory or the server's document root. A directory elsewhere has no address,
 * and nothing to probe.
 *
 * @since 1.3.0
 *
 * @param string $directory The directory.
 *
 * @return bool
 */
function exports_reports_dir_in_web_root( $directory ) {
	$real      = realpath( $directory );
	$directory = trailingslashit( wp_normalize_path( false !== $real ? $real : $directory ) );
	$roots     = array( ABSPATH, WP_CONTENT_DIR );

	if ( ! empty( $_SERVER['DOCUMENT_ROOT'] ) ) {
		$roots[] = sanitize_text_field( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) );
	}

	foreach ( $roots as $root ) {
		$real = realpath( $root );
		$root = trailingslashit( wp_normalize_path( false !== $real ? $real : $root ) );

		if ( '/' !== $root && 0 === strpos( $directory, $root ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Finds out whether the web server serves the export directory: writes a file into it, asks for the file
 * by its address the way a visitor would, and removes it. The plugin's .htaccess keeps Apache from
 * serving the directory; nginx and other servers ignore that file, and there only a rule in the server's
 * configuration, or a directory outside the web root, keeps an export from whoever has its address.
 *
 * @since 1.3.0
 *
 * @param string|null $directory The directory; the export directory when null.
 * @param string|null $url       Its address; the export directory's when null.
 *
 * @return string 'outside' when the directory is outside the web root, 'missing' when it does not exist yet
 *                (the first export creates it), 'served' when the file came back, 'protected' when the
 *                server refused it, 'unknown' when the file could not be written or the site could not
 *                reach itself.
 */
function exports_reports_probe_export_dir( $directory = null, $url = null ) {
	$directory = null === $directory ? EXPORTS_REPORTS_EXPORT_DIR : $directory;
	$url       = null === $url ? EXPORTS_REPORTS_EXPORT_URL : $url;

	if ( ! exports_reports_dir_in_web_root( $directory ) ) {
		return 'outside';
	}

	if ( ! is_dir( $directory ) ) {
		return 'missing';
	}

	$token = wp_generate_password( 32, false );
	$file  = 'probe_' . wp_generate_password( 24, false ) . '.txt';
	$path  = trailingslashit( $directory ) . $file;

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a file of a few bytes in the plugin's own directory, removed below.
	if ( false === file_put_contents( $path, $token ) ) {
		return 'unknown';
	}

	$response = wp_remote_get(
		trailingslashit( $url ) . $file,
		array(
			'timeout'     => 5,
			'redirection' => 0,
			/** This filter is documented in wp-includes/class-wp-http-streams.php */
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core's own filter for a request a site sends itself, applied as core applies it.
			'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
		)
	);

	wp_delete_file( $path );

	if ( is_wp_error( $response ) ) {
		return 'unknown';
	}

	return 200 === (int) wp_remote_retrieve_response_code( $response ) && false !== strpos( (string) wp_remote_retrieve_body( $response ), $token ) ? 'served' : 'protected';
}

/**
 * Whether the web server serves the export directory, as exports_reports_probe_export_dir() found it
 * within the last hour, or finds it now.
 *
 * @since 1.3.0
 *
 * @param bool $fresh Whether to probe now whatever was found before.
 *
 * @return string One of the answers of exports_reports_probe_export_dir().
 */
function exports_reports_export_dir_status( $fresh = false ) {
	$status = $fresh ? false : get_transient( 'exports_reports_export_dir_status' );

	if ( ! is_string( $status ) || '' === $status ) {
		$status = exports_reports_probe_export_dir();

		set_transient( 'exports_reports_export_dir_status', $status, HOUR_IN_SECONDS );
	}

	return $status;
}

/**
 * What to do about an export directory the web server serves, as the Settings page and Site Health say it.
 *
 * @since 1.3.0
 *
 * @return string HTML.
 */
function exports_reports_export_dir_advice() {
	return wp_kses(
		sprintf(
			/* translators: 1: the nginx location rule, 2: the EXPORTS_REPORTS_EXPORT_DIR constant. */
			__( 'Add %1$s to the server block (nginx ignores the plugin\'s .htaccess), or define %2$s in wp-config.php with a writable directory outside the web root, which works on any server.', 'exports-and-reports' ),
			'<code>location ~* ' . esc_html( trailingslashit( (string) wp_parse_url( EXPORTS_REPORTS_EXPORT_URL, PHP_URL_PATH ) ) ) . ' { deny all; }</code>',
			'<code>EXPORTS_REPORTS_EXPORT_DIR</code>'
		),
		array( 'code' => array() )
	);
}

/**
 * Adds the plugin's test to Site Health (the site_status_tests filter).
 *
 * @since 1.3.0
 *
 * @param array $tests The tests.
 *
 * @return array
 */
function exports_reports_site_status_tests( $tests ) {
	$tests['direct']['exports_reports_export_dir'] = array(
		'label' => __( 'Exports and Reports: the export directory', 'exports-and-reports' ),
		'test'  => 'exports_reports_site_status_export_dir',
	);

	return $tests;
}

/**
 * The Site Health test: whether an export can be downloaded by its address, without the plugin's access check.
 *
 * @since 1.3.0
 *
 * @return array The result, as Site Health takes it.
 */
function exports_reports_site_status_export_dir() {
	$status = exports_reports_export_dir_status( true );
	$result = array(
		'label'       => __( 'The export files of Exports and Reports are not served by the web server', 'exports-and-reports' ),
		'status'      => 'good',
		'badge'       => array(
			'label' => __( 'Security', 'exports-and-reports' ),
			'color' => 'blue',
		),
		'description' => '<p>' . esc_html__( 'Exports and Reports writes its export files into a directory and serves each download itself, after checking that the user may view the report. A request for a file by its address was refused, as it should be.', 'exports-and-reports' ) . '</p>',
		'actions'     => '',
		'test'        => 'exports_reports_export_dir',
	);

	if ( 'outside' === $status ) {
		$result['description'] = '<p>' . esc_html__( 'Exports and Reports writes its export files into a directory outside the web root, which the web server cannot serve; the plugin serves each download itself, after checking that the user may view the report.', 'exports-and-reports' ) . '</p>';
	} elseif ( 'missing' === $status ) {
		$result['description'] = '<p>' . esc_html__( 'Exports and Reports has not written an export yet; its export directory is created, and checked here, with the first one.', 'exports-and-reports' ) . '</p>';
	} elseif ( 'served' === $status ) {
		$result['label']       = __( 'The export files of Exports and Reports can be downloaded by their address', 'exports-and-reports' );
		$result['status']      = 'critical';
		$result['description'] = '<p>' . esc_html__( 'A file in the export directory of Exports and Reports was served to a request for its address. An export stays there for up to a day, and until it is removed anyone who has or guesses its address can download it without logging in.', 'exports-and-reports' ) . '</p><p>' . exports_reports_export_dir_advice() . '</p>';
	} elseif ( 'unknown' === $status ) {
		$result['label']       = __( 'Exports and Reports could not check whether its export files are served by the web server', 'exports-and-reports' );
		$result['status']      = 'recommended';
		$result['description'] = '<p>' . esc_html__( 'The check writes a file into the export directory and asks the site for it by its address; the file could not be written, or the site could not reach itself. Ask for a file in the directory from a browser: the server should refuse it.', 'exports-and-reports' ) . '</p><p>' . exports_reports_export_dir_advice() . '</p>';
	}

	return $result;
}
