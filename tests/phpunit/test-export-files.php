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
}
