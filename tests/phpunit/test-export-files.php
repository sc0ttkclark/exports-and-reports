<?php
/**
 * The export files in the uploads directory: the link nonce, the protection files, the download
 * function, the cleanup event and the Clear Now button.
 *
 * @package Exports_And_Reports
 */

class Tests_Export_Files extends Exports_Reports_Test_Case {

	/**
	 * An export needs its nonce; remove_export takes export files only.
	 */
	public function test_export_needs_the_link_nonce_and_remove_export_leaves_the_protection_files_alone() {
		$report = $this->report( 'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1', 'Export nonce' );

		$report->export_type = 'csv';

		$_GET = array();

		ob_start();
		$without_nonce  = $report->export();
		$without_output = (string) ob_get_clean();

		$this->assertFalse( $without_nonce );
		$this->assertStringContainsString( 'expired', $without_output );

		$this->export_from_link( $report );

		$file = (string) $report->exported_file;

		$this->assertNotSame( '', $file, 'with the nonce the export is written' );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file );

		$link = $report->export_link( array( 'export_type' => 'csv' ) );

		$this->assertStringContainsString( 'action=export', $link );
		$this->assertStringContainsString( '_wpnonce=', $link );

		list( $removed, $removed_output ) = $this->export_from_post( $report, array( 'remove_export' => '.htaccess' ) );

		$this->assertFalse( $removed );
		$this->assertStringContainsString( 'not an export file', $removed_output );
		$this->assertFileExists( EXPORTS_REPORTS_EXPORT_DIR . '/.htaccess' );
	}

	/**
	 * An export is removed by the button under its message, a POST; the address of a removal link from before
	 * 1.2.0 is noted and removes nothing, like the export links before 1.1.0.
	 */
	public function test_an_export_is_removed_by_the_posted_button_and_not_by_an_address() {
		$report = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', 'Removal' );

		$report->export_type = 'csv';

		list( , $output ) = $this->export_from_post( $report, array( 'export_type' => 'csv' ) );

		$file = (string) $report->exported_file;
		$path = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;

		$this->assertNotSame( '', $file );
		$this->assertStringContainsString( '<button type="submit" class="button-link exports-reports-remove-export" form="exports-reports-remove-export" name="remove_export" value="' . $file . '">', $output, 'the message carries the removal button, kept by the message filter' );
		$this->assertStringNotContainsString( 'remove_export=', $output, 'and no removal address' );

		$_GET = array(
			'action'        => 'export',
			'remove_export' => $file,
			'_wpnonce'      => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$from_address = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', 'Removal' );

		ob_start();
		$from_address->manage();
		$address_output = (string) ob_get_clean();

		$_GET = array();

		$this->assertTrue( $from_address->export_from_address );
		$this->assertStringContainsString( 'This address pointed at an export', $address_output );
		$this->assertStringContainsString( 'id="exports-reports-remove-export"', $address_output, 'the removal form is on the screen' );
		$this->assertFileExists( $path, 'the address removed nothing' );

		list( , $removed_output ) = $this->export_from_post( $report, array( 'remove_export' => $file ) );

		$this->assertStringContainsString( 'removed successfully', $removed_output );
		$this->assertFileDoesNotExist( $path );
	}

	public function test_the_export_directory_carries_htaccess_and_index_php_after_an_export() {
		$report = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', 'Guards' );

		$report->export_type = 'csv';

		$this->export_from_link( $report );

		if ( '' !== (string) $report->exported_file ) {
			wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $report->exported_file );
		}

		$this->assertFileExists( EXPORTS_REPORTS_EXPORT_DIR . '/.htaccess' );
		$this->assertFileExists( EXPORTS_REPORTS_EXPORT_DIR . '/index.php' );
	}

	public function test_the_download_function_streams_an_export_and_refuses_anything_else() {
		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/download_test.csv', "a,b\n1,2\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		list( $sent, $body ) = $this->send_export_file( 'download_test.csv' );

		$refused = array();

		foreach ( array( '../index.php', 'index.php', '.htaccess', 'missing.csv' ) as $name ) {
			list( $refused[ $name ] ) = $this->send_export_file( $name );
		}

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/download_test.csv' );

		$this->assertTrue( $sent );
		$this->assertSame( "a,b\n1,2\n", $body );
		$this->assertSame(
			array(
				'../index.php' => false,
				'index.php'    => false,
				'.htaccess'    => false,
				'missing.csv'  => false,
			),
			$refused
		);
	}

	/**
	 * The pipe and custom exports are .txt files.
	 */
	public function test_txt_exports_are_served_and_the_daily_cleanup_has_a_handler() {
		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/served.txt', "a|b\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		list( $sent, $body ) = $this->send_export_file( 'served.txt' );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/served.txt' );

		$this->assertTrue( $sent );
		$this->assertSame( "a|b\n", $body );
		$this->assertTrue( exports_reports_is_export_file( 'x.txt' ) );
		$this->assertFalse( exports_reports_is_export_file( 'index.php' ) );
		$this->assertFalse( exports_reports_is_export_file( 'notes.md' ) );
		$this->assertSame( 10, has_action( 'exports_reports_cleanup', 'exports_reports_cleanup_event' ) );
	}

	public function test_deactivation_clears_the_cleanup_event_and_the_next_admin_request_reschedules_it() {
		exports_reports_deactivate();

		$cleared = wp_next_scheduled( 'exports_reports_cleanup' );

		exports_reports_ensure_cleanup_scheduled();

		$back = wp_next_scheduled( 'exports_reports_cleanup' );

		$this->assertFalse( $cleared );
		$this->assertNotFalse( $back );
	}

	/**
	 * Clear Now removes export files only.
	 */
	public function test_clearing_the_export_directory_removes_export_files_only() {
		$this->protect_export_dir();

		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/old.csv', 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/notes.md', 'keep' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		exports_reports_cleanup( true );

		$old_gone   = ! file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/old.csv' );
		$notes_kept = file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/notes.md' );
		$index_kept = file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/index.php' );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/notes.md' );

		$this->assertTrue( $old_gone, 'old.csv is gone' );
		$this->assertTrue( $notes_kept, 'notes.md is kept' );
		$this->assertTrue( $index_kept, 'index.php is kept' );
	}

	/**
	 * The removal asks the export log, as the download does: an export goes from the screen of its own
	 * report only, and a file the log does not know stays.
	 */
	public function test_an_export_is_removed_only_from_the_screen_of_its_own_report() {
		global $wpdb;

		$sql   = 'SELECT id FROM ' . self::videos() . ' WHERE id = 1';
		$own   = $this->report( $sql, 'Own', array( 'report_id' => 9101 ) );
		$other = $this->report( $sql, 'Other', array( 'report_id' => 9102 ) );

		$own->export_type = 'csv';

		$this->export_from_post( $own, array( 'export_type' => 'csv' ) );

		$file = (string) $own->exported_file;
		$path = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;

		$this->assertFileExists( $path );
		$this->assertSame( '9101', (string) $wpdb->get_var( $wpdb->prepare( "SELECT `report_id` FROM {$wpdb->exports_reports_log} WHERE `filename` = %s", $file ) ) );

		list( $refused, $refused_output ) = $this->export_from_post( $other, array( 'remove_export' => $file ) );

		$this->assertFalse( $refused );
		$this->assertStringContainsString( 'not an export of this report', $refused_output );
		$this->assertFileExists( $path, 'the screen of another report removes nothing' );

		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/stray.csv', 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		list( , $stray_output ) = $this->export_from_post( $own, array( 'remove_export' => 'stray.csv' ) );

		$stray_kept = file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/stray.csv' );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/stray.csv' );

		$this->assertStringContainsString( 'not an export of this report', $stray_output );
		$this->assertTrue( $stray_kept, 'a file the log does not know stays' );

		list( , $removed_output ) = $this->export_from_post( $own, array( 'remove_export' => $file ) );

		$this->assertStringContainsString( 'removed successfully', $removed_output );
		$this->assertFileDoesNotExist( $path );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( "SELECT `id` FROM {$wpdb->exports_reports_log} WHERE `filename` = %s", $file ) ), 'and its log row goes with it' );
	}

	/**
	 * The log keeps UTC and the cleanup counts the day in UTC, whatever the site's time zone: an export
	 * goes when it is a day old, not earlier or later by the site's distance from UTC. The rows an
	 * earlier version wrote in local time are moved on the upgrade.
	 */
	public function test_the_export_log_keeps_utc_and_the_cleanup_counts_the_day_in_utc() {
		global $wpdb;

		update_option( 'timezone_string', 'Pacific/Honolulu' ); // Ten hours behind UTC, all year.

		$report = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', 'Zone', array( 'report_id' => 9103 ) );

		$report->export_type = 'csv';

		$this->export_from_post( $report, array( 'export_type' => 'csv' ) );

		$file   = (string) $report->exported_file;
		$logged = (string) $wpdb->get_var( $wpdb->prepare( "SELECT `created` FROM {$wpdb->exports_reports_log} WHERE `filename` = %s", $file ) );

		$this->assertLessThan( 60, abs( strtotime( $logged . ' UTC' ) - time() ), 'the export is logged at the time in UTC: ' . $logged );

		$ages = array(
			'day_old.csv'  => 25 * HOUR_IN_SECONDS,
			'same_day.csv' => 23 * HOUR_IN_SECONDS,
		);

		foreach ( $ages as $name => $age ) {
			file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/' . $name, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

			$wpdb->insert(
				$wpdb->exports_reports_log,
				array(
					'report_id' => 9103,
					'filename'  => $name,
					'created'   => gmdate( 'Y-m-d H:i:s', time() - $age ),
				)
			);
		}

		$this->assertTrue( exports_reports_cleanup() );

		$kept = array(
			'day_old.csv'  => file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/day_old.csv' ),
			'same_day.csv' => file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/same_day.csv' ),
			$file          => file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file ),
		);

		foreach ( array_keys( $kept ) as $name ) {
			wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $name );
		}

		$this->assertFalse( $kept['day_old.csv'], 'an export of 25 hours ago is removed' );
		$this->assertTrue( $kept['same_day.csv'], 'an export of 23 hours ago stays' );
		$this->assertTrue( $kept[ $file ], 'and so does the one just written' );
		$this->assertSame( array( 'same_day.csv', $file ), $wpdb->get_col( "SELECT `filename` FROM {$wpdb->exports_reports_log} WHERE `report_id` = 9103 ORDER BY `filename`" ) );

		// A row of an earlier version, written at the site's local time, on the upgrade.
		$wpdb->insert(
			$wpdb->exports_reports_log,
			array(
				'report_id' => 9104,
				'filename'  => 'earlier.csv',
				'created'   => '2026-03-01 08:00:00',
			)
		);

		exports_reports_log_to_utc();

		$this->assertSame( '2026-03-01 18:00:00', $wpdb->get_var( "SELECT `created` FROM {$wpdb->exports_reports_log} WHERE `filename` = 'earlier.csv'" ), 'ten hours behind UTC' );

		update_option( 'timezone_string', 'Asia/Kolkata' ); // Five and a half hours ahead of UTC.

		exports_reports_log_to_utc();

		$this->assertSame( '2026-03-01 12:30:00', $wpdb->get_var( "SELECT `created` FROM {$wpdb->exports_reports_log} WHERE `filename` = 'earlier.csv'" ), 'five and a half hours ahead' );
	}

	/**
	 * An error about the export directory names its path on the server to a user who manages reports, and
	 * to nobody else.
	 */
	public function test_the_export_directory_is_named_to_a_user_who_manages_reports_only() {
		$report  = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', 'Path' );
		$message = '<strong>Error:</strong> Your export directory (<strong>' . EXPORTS_REPORTS_EXPORT_DIR . '</strong>) needs to be writable.';
		$shown   = array();

		foreach ( array( 'administrator', 'subscriber' ) as $role ) {
			wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );

			ob_start();
			$report->export_dir_error( $message );
			$shown[ $role ] = (string) ob_get_clean();
		}

		wp_set_current_user( 0 );

		$this->assertStringContainsString( EXPORTS_REPORTS_EXPORT_DIR, $shown['administrator'] );
		$this->assertStringNotContainsString( EXPORTS_REPORTS_EXPORT_DIR, $shown['subscriber'] );
		$this->assertStringNotContainsString( ABSPATH, $shown['subscriber'] );
		$this->assertStringContainsString( 'Ask an administrator', $shown['subscriber'] );
	}

	/**
	 * robots.txt no longer names the export directory: the line told every crawler where the exports are
	 * and kept nobody out.
	 */
	public function test_robots_txt_does_not_name_the_export_directory() {
		$this->assertFalse( has_filter( 'robots_txt', 'exports_reports_robots_txt' ) );
		$this->assertStringNotContainsString( 'exports-and-reports', (string) apply_filters( 'robots_txt', "User-agent: *\nDisallow: /wp-admin/\n", true ) );
	}

	/**
	 * Answers the request of the directory's check as a test wants the server to answer.
	 *
	 * @param string $how 'served' (the file itself), 'page' (a 200 that is not the file), 'refused' or 'unreachable'.
	 * @return callable The pre_http_request callback; it counts its requests in $this->probes.
	 */
	protected function server_that( $how ) {
		$this->probes = 0;

		return function ( $response, $args, $url ) use ( $how ) {
			if ( 0 !== strpos( (string) $url, EXPORTS_REPORTS_EXPORT_URL ) ) {
				return $response;
			}

			++$this->probes;

			if ( 'unreachable' === $how ) {
				return new WP_Error( 'http_request_failed', 'cURL error 7' );
			}

			$body = 'served' === $how ? (string) file_get_contents( EXPORTS_REPORTS_EXPORT_DIR . '/' . basename( $url ) ) : 'A page of the site'; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			return array(
				'headers'  => array(),
				'body'     => $body,
				'response' => array(
					'code'    => 'refused' === $how ? 403 : 200,
					'message' => '',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};
	}

	/**
	 * The requests the directory's check sent to the server of the test.
	 *
	 * @var int
	 */
	protected $probes = 0;

	/**
	 * The check writes a file into the export directory and asks for it by its address: served when the
	 * file comes back, protected when the server refuses it or answers with something else, unknown when
	 * the site cannot reach itself; a directory outside the web root is not asked for. The file is gone
	 * afterwards.
	 */
	public function test_the_check_tells_a_served_export_directory_from_a_protected_one() {
		$this->protect_export_dir();

		$before  = $this->export_files();
		$answers = array();

		foreach ( array( 'served', 'page', 'refused', 'unreachable' ) as $how ) {
			$server = $this->server_that( $how );

			add_filter( 'pre_http_request', $server, 1, 3 );

			$answers[ $how ] = exports_reports_probe_export_dir();

			remove_filter( 'pre_http_request', $server, 1 );

			$this->assertSame( 1, $this->probes, $how . ': one request' );
		}

		$this->assertSame(
			array(
				'served'      => 'served',
				'page'        => 'protected',
				'refused'     => 'protected',
				'unreachable' => 'unknown',
			),
			$answers
		);
		$this->assertSame( $before, $this->export_files(), 'the file of the check is removed' );

		$server = $this->server_that( 'served' );

		add_filter( 'pre_http_request', $server, 1, 3 );

		$outside = exports_reports_probe_export_dir( sys_get_temp_dir(), 'http://example.org/tmp' );
		$missing = exports_reports_probe_export_dir( EXPORTS_REPORTS_EXPORT_DIR . '/not-there', EXPORTS_REPORTS_EXPORT_URL . '/not-there' );

		remove_filter( 'pre_http_request', $server, 1 );

		$this->assertSame( 'outside', $outside );
		$this->assertSame( 'missing', $missing );
		$this->assertSame( 0, $this->probes, 'neither is asked for' );
	}

	/**
	 * Site Health reports a served directory as critical and says what to do; the Settings page shows the
	 * same finding, from the last hour's check unless the form was just saved.
	 */
	public function test_site_health_and_the_settings_page_report_a_served_export_directory() {
		$this->protect_export_dir();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$tests = apply_filters( 'site_status_tests', array( 'direct' => array(), 'async' => array() ) );

		$this->assertSame( 'exports_reports_site_status_export_dir', $tests['direct']['exports_reports_export_dir']['test'] );

		$settings = static function () {
			ob_start();
			exports_reports_settings();

			return (string) ob_get_clean();
		};

		$served = $this->server_that( 'served' );

		add_filter( 'pre_http_request', $served, 1, 3 );

		$critical = exports_reports_site_status_export_dir();
		$warned   = $settings();
		$asked    = $this->probes;

		remove_filter( 'pre_http_request', $served, 1 );

		$this->assertSame( 'critical', $critical['status'] );
		$this->assertStringContainsString( 'location ~* /wp-content/uploads-tests/exports-and-reports/ { deny all; }', $critical['description'] );
		$this->assertStringContainsString( 'EXPORTS_REPORTS_EXPORT_DIR', $critical['description'] );
		$this->assertStringContainsString( 'exports-reports-dir-served', $warned, 'the Settings page warns' );
		$this->assertSame( 1, $asked, 'the Settings page shows what Site Health just found, without asking again' );

		$refused = $this->server_that( 'refused' );

		add_filter( 'pre_http_request', $refused, 1, 3 );

		$good  = exports_reports_site_status_export_dir();
		$quiet = $settings();

		remove_filter( 'pre_http_request', $refused, 1 );

		$this->assertSame( 'good', $good['status'] );
		$this->assertStringNotContainsString( 'exports-reports-dir-served', $quiet );
		$this->assertStringContainsString( 'a request for a file in the directory by its address was refused', $quiet );

		$unreachable = $this->server_that( 'unreachable' );

		add_filter( 'pre_http_request', $unreachable, 1, 3 );

		$recommended = exports_reports_site_status_export_dir();

		remove_filter( 'pre_http_request', $unreachable, 1 );

		wp_set_current_user( 0 );

		$this->assertSame( 'recommended', $recommended['status'] );
	}
}
