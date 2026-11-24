<?php
/**
 * Installation and upgrade: the tables and their character set, the move of the export directory, the uninstall.
 *
 * @package Exports_And_Reports
 */

class Tests_Install extends Exports_Reports_Test_Case {

	/**
	 * The collation of a table of the test database.
	 *
	 * @param string $table The table.
	 * @return string
	 */
	protected function table_collation( $table ) {
		global $wpdb;

		return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', $table ) );
	}

	public function test_the_plugin_tables_are_created_with_the_site_collation() {
		global $wpdb;

		$this->assertNotEmpty( $wpdb->collate, 'the site has a collation' );

		foreach ( array( 'groups', 'reports', 'log' ) as $suffix ) {
			$this->assertSame( $wpdb->collate, $this->table_collation( $wpdb->prefix . EXPORTS_REPORTS_TBL . $suffix ), "the {$suffix} table carries the site collation" );
		}
	}

	/**
	 * The example group and reports are written on a first install into empty tables only: a site whose version
	 * option is missing while it has groups or reports of its own gets none, and an upgrade never writes them.
	 */
	public function test_the_examples_are_written_into_empty_tables_only() {
		global $wpdb;

		$count = function () use ( $wpdb ) {
			return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->exports_reports_reports} WHERE `name` LIKE 'Example %'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		};

		$wpdb->query( "DELETE FROM {$wpdb->exports_reports_reports}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "DELETE FROM {$wpdb->exports_reports_groups}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->insert(
			$wpdb->exports_reports_groups,
			array(
				'name'        => 'Mine',
				'disabled'    => 0,
				'role_access' => '',
				'weight'      => 0,
				'created'     => current_time( 'mysql' ),
				'updated'     => current_time( 'mysql' ),
			)
		);

		delete_option( 'exports_reports_version' );
		exports_reports_install_upgrade();
		$with_a_group = $count();

		update_option( 'exports_reports_version', '1.1.4' );
		exports_reports_install_upgrade();
		$on_upgrade = $count();

		$wpdb->query( "DELETE FROM {$wpdb->exports_reports_groups}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		delete_option( 'exports_reports_version' );
		exports_reports_install_upgrade();
		$into_empty_tables = $count();

		$this->assertSame( 0, $with_a_group, 'a site with a group of its own gets no examples' );
		$this->assertSame( 0, $on_upgrade, 'an upgrade writes none' );
		$this->assertSame( 2, $into_empty_tables, 'a first install into empty tables gets the two examples' );
		$this->assertSame( EXPORTS_REPORTS_VERSION, get_option( 'exports_reports_version' ) );
	}

	/**
	 * Installs before 0.9.5 created the tables in three-byte utf8 whatever the site used; the upgrade converts them.
	 */
	public function test_a_three_byte_utf8_table_is_converted_on_upgrade() {
		global $wpdb;

		if ( 'utf8mb4' !== $wpdb->charset ) {
			$this->markTestSkipped( 'The conversion runs towards utf8mb4 only; the site uses ' . $wpdb->charset . '.' );
		}

		$log_table = $wpdb->exports_reports_log;

		$wpdb->query( "ALTER TABLE `{$log_table}` CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$converted = exports_reports_convert_tables_charset();

		$this->assertSame( array( $log_table ), $converted, 'the converted table is reported' );
		$this->assertSame( $wpdb->collate, $this->table_collation( $log_table ) );
	}

	public function test_the_export_files_this_site_logged_move_from_the_old_directory_and_other_files_stay() {
		global $wpdb;

		$legacy = $this->require_free_legacy_export_dir();

		wp_mkdir_p( $legacy );
		file_put_contents( $legacy . '/legacy_move_test.csv', 'a,b' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $legacy . '/another_site.csv', 'c,d' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$wpdb->insert(
			$wpdb->exports_reports_log,
			array(
				'report_id' => 0,
				'filename'  => 'legacy_move_test.csv',
				'created'   => date_i18n( 'Y-m-d H:i:s' ),
			)
		);

		exports_reports_move_legacy_export_dir();

		$moved = file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/legacy_move_test.csv' );
		$gone  = ! file_exists( $legacy . '/legacy_move_test.csv' );
		$kept  = file_exists( $legacy . '/another_site.csv' );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/legacy_move_test.csv' );
		wp_delete_file( $legacy . '/legacy_move_test.csv' );
		wp_delete_file( $legacy . '/another_site.csv' );
		rmdir( $legacy ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- the directory the test made.

		$this->assertTrue( $moved, 'the logged file is in the export directory' );
		$this->assertTrue( $gone, 'the logged file is gone from the old directory' );
		$this->assertTrue( $kept, 'the other file stays where it was' );
	}

	/**
	 * The protection files the plugin wrote there go with the directory.
	 */
	public function test_the_old_export_directory_goes_once_nothing_is_left_in_it() {
		$legacy = $this->require_free_legacy_export_dir();

		wp_mkdir_p( $legacy );
		file_put_contents( $legacy . '/.htaccess', "# Exports and Reports: export files are served by the plugin after an access check.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		file_put_contents( $legacy . '/index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		exports_reports_move_legacy_export_dir();

		$this->assertDirectoryDoesNotExist( $legacy );
	}

	/**
	 * Runs uninstall.php against the test database, then installs the plugin again for the tests that follow.
	 *
	 * The test library turns CREATE TABLE and DROP TABLE into their TEMPORARY forms inside a test; both
	 * rewrites are lifted here, so the uninstall drops the real tables and the fresh install that follows
	 * creates real ones. Their DDL commits the test transaction, so the re-install is committed as a whole
	 * and the run goes on from a fresh install.
	 */
	public function test_uninstall_removes_tables_options_the_cleanup_event_and_export_files() {
		global $wpdb;

		$this->require_free_legacy_export_dir();

		$leftover = EXPORTS_REPORTS_EXPORT_DIR . '/leftover.csv';

		wp_mkdir_p( EXPORTS_REPORTS_EXPORT_DIR );
		file_put_contents( $leftover, 'x' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		exports_reports_set_token( 'uninstall-token' );
		set_transient( 'exports_reports_count_abc', array( 'total' => 5, 'at' => time() ), HOUR_IN_SECONDS );

		require EXPORTS_REPORTS_DIR . 'uninstall.php';

		$kept_count = $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name = '_transient_exports_reports_count_abc'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$table_left    = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->exports_reports_reports ) );
		$version       = get_option( 'exports_reports_version' );
		$token         = get_option( 'exports_reports_token_hash' );
		$event         = wp_next_scheduled( 'exports_reports_cleanup' );
		$leftover_left = file_exists( $leftover );

		exports_reports_install_upgrade();
		self::commit_transaction();

		$this->assertNull( $table_left, 'the reports table is gone' );
		$this->assertFalse( $version, 'the version option is gone' );
		$this->assertFalse( $token, 'the token hash option is gone' );
		$this->assertNull( $kept_count, 'the kept row counts are gone' );
		$this->assertFalse( $event, 'the cleanup event is gone' );
		$this->assertFalse( $leftover_left, 'the export file is gone' );
	}
}
