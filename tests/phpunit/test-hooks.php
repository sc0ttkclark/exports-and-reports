<?php
/**
 * The plugin's filters as site code uses them (their arguments one by one, the screen last), and the
 * wp_admin_ui_* hook names of the versions before 0.9.5, which keep the array form.
 *
 * @package Exports_And_Reports
 */

class Tests_Hooks extends Exports_Reports_Test_Case {

	/**
	 * The plugin runs one statement; a site that needs variables or temporary tables splits the report
	 * itself and runs the preparation on wpdb's connection, which this callback does for the test.
	 *
	 * @param string|false $sql The statement, as exports_reports_get_data_sql passes it.
	 * @return string|false
	 */
	public function run_preparation_statements( $sql ) {
		global $wpdb;

		$sql = (string) $sql;

		// Split on ";" followed by whitespace, outside single-quoted strings.
		$statements = preg_split( "/;\\s+(?=(?:[^']*'[^']*')*[^']*$)/", $sql, -1, PREG_SPLIT_NO_EMPTY );

		if ( count( $statements ) < 2 ) {
			return $sql;
		}

		$last = trim( array_pop( $statements ) );

		// phpcs:disable WordPress.DB.RestrictedFunctions -- the preparation statements on wpdb's connection.
		if ( mysqli_multi_query( $wpdb->dbh, implode( '; ', $statements ) ) ) {
			do {
				$result = mysqli_store_result( $wpdb->dbh );

				if ( $result instanceof mysqli_result ) {
					mysqli_free_result( $result );
				}

				if ( ! mysqli_more_results( $wpdb->dbh ) ) {
					break;
				}
			} while ( mysqli_next_result( $wpdb->dbh ) );
		}

		if ( '' !== mysqli_error( $wpdb->dbh ) ) {
			$wpdb->last_error = 'Preparation statement failed: ' . mysqli_error( $wpdb->dbh );

			return false;
		}
		// phpcs:enable

		return $last;
	}

	/**
	 * Several statements, run through the exports_reports_get_data_sql filter by site code.
	 */
	public function test_variables_and_a_temporary_table_through_the_sql_filter() {
		global $wpdb;

		add_filter( 'exports_reports_get_data_sql', array( $this, 'run_preparation_statements' ) );

		list( , $rows, $error ) = $this->run_report( "SET @cutoff = 50;\nCREATE TEMPORARY TABLE er_test_top AS SELECT * FROM " . self::videos() . " WHERE revenue > @cutoff;\nSELECT id, title, revenue FROM er_test_top ORDER BY revenue DESC", 'Prepared' );

		$wpdb->query( 'DROP TEMPORARY TABLE IF EXISTS er_test_top' );

		$this->assertSame( '', $error );
		$this->assertCount( self::$videos_over_fifty, $rows );
	}

	/**
	 * The view path runs the count query for the totals row.
	 */
	public function test_the_count_query_runs_through_its_filter() {
		$seen = '';

		add_filter(
			'exports_reports_get_data_sql_count',
			static function ( $sql_count ) use ( &$seen ) {
				$seen = (string) $sql_count;

				return 'SELECT 42 AS revenue';
			}
		);

		$report = $this->report(
			'SELECT id, revenue FROM ' . self::videos() . ' ORDER BY id',
			'Count',
			array( 'sql_count' => 'SELECT SUM(revenue) AS revenue FROM ' . self::videos() )
		);

		ob_start();
		$report->get_data( false );
		ob_end_clean();

		$this->assertStringContainsString( 'SUM(revenue)', $seen, 'the filter saw the count query' );
		$this->assertTrue( isset( $report->sum_data[0]['revenue'] ) );
		$this->assertSame( 42, (int) $report->sum_data[0]['revenue'] );
	}

	/**
	 * The callback returns false once it has reported why: nothing runs, no export is written.
	 */
	public function test_a_declined_query_runs_nothing_and_writes_no_export() {
		add_filter(
			'exports_reports_get_data_sql',
			static function () {
				global $wpdb;

				$wpdb->last_error = 'declined by the test';

				return false;
			}
		);

		$report = $this->report( 'SELECT id FROM ' . self::videos(), 'Declined' );

		$report->export_type = 'csv';

		$before = $this->export_files();

		$_GET = array( '_wpnonce' => wp_create_nonce( 'exports-reports-export-request' ) );

		ob_start();
		$data   = $report->get_data( true );
		$export = $report->export();
		ob_end_clean();

		$_GET = array();

		$this->assertFalse( $data );
		$this->assertFalse( $export );
		$this->assertSame( array(), array_values( array_diff( $this->export_files(), $before ) ), 'no new file' );
		$this->assertEmpty( $report->full_data );
	}

	/**
	 * A function PHP itself defines is not called back unless the filter allows it; the filter has the last word.
	 */
	public function test_the_allowed_callback_filter_admits_a_php_function() {
		add_filter(
			'exports_reports_allowed_callback',
			static function ( $allowed, $callback ) {
				return 'strtoupper' === $callback ? true : $allowed;
			},
			10,
			2
		);

		$report = $this->report(
			'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1',
			'Internal callback allowed',
			array(
				'columns' => array(
					'id'      => array( 'label' => 'Id' ),
					'channel' => array(
						'label'          => 'Channel',
						'custom_display' => 'strtoupper',
					),
				),
			)
		);

		$this->assertSame( 'strtoupper', $report->columns['channel']['custom_display'] );
	}

	/**
	 * The old names still receive the arguments as one array indexed from 1, and return it.
	 */
	public function test_the_wp_admin_ui_hook_names_reach_their_callbacks_as_deprecated_aliases() {
		$this->setExpectedDeprecated( 'wp_admin_ui_get_data_sql' );

		$runs     = 0;
		$replaced = '';

		add_action(
			'deprecated_hook_run',
			static function ( $hook, $replacement ) use ( &$replaced ) {
				if ( 'wp_admin_ui_get_data_sql' === $hook ) {
					$replaced = (string) $replacement;
				}
			},
			10,
			2
		);
		add_filter(
			'wp_admin_ui_get_data_sql',
			static function ( $args ) use ( &$runs, &$shape ) {
				++$runs;
				$shape = is_array( $args ) && isset( $args[1] ) && is_string( $args[1] ) && isset( $args[2] ) && is_bool( $args[2] );

				return $args;
			}
		);

		$shape = false;

		list( , $rows, $error ) = $this->run_report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Legacy hook' );

		$this->assertSame( 1, $runs, 'the legacy callback ran once' );
		$this->assertTrue( $shape, 'the legacy callback received the statement and the full flag as an array indexed from 1' );
		$this->assertSame( 'exports_reports_get_data_sql', $replaced, 'the deprecation names the replacement' );
		$this->assertSame( '', $error );
		$this->assertCount( self::$video_rows, $rows );
	}

	/**
	 * prepare() keeps a literal % behind its placeholder escape until the query runs.
	 */
	public function test_the_data_filter_sql_filter_sees_the_condition_of_an_array_filter_value() {
		global $wpdb;

		$seen = array();

		add_filter(
			'exports_reports_data_filter_sql',
			static function ( $filter_sql ) use ( &$seen, $wpdb ) {
				$seen[] = $wpdb->remove_placeholder_escape( $filter_sql );

				return $filter_sql;
			}
		);

		$_GET = array( 'filter_channel' => array( 'alpha', 'beta', '' ) );

		list( , $rows, $error ) = $this->run_report( 'SELECT id, channel FROM ' . self::videos(), 'Array filter', false, $this->channel_filter_options() );

		$_GET = array();

		$want = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() . " WHERE channel IN ('alpha', 'beta')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( '', $error );
		$this->assertSame( array( "(`channel` LIKE '%alpha%' OR `channel` LIKE '%beta%')" ), $seen );
		$this->assertGreaterThan( 0, $want );
		$this->assertCount( $want, $rows );
	}

	public function test_formula_wrapping_can_be_turned_off_through_the_filter() {
		add_filter( 'exports_reports_export_escape_formulas', '__return_false' );

		list( $admin, , $error ) = $this->run_report( 'SELECT id, title FROM ' . self::videos() . ' WHERE id = 7', 'Formulas kept', true );

		$this->assertSame( '', $error );
		$this->assertSame( "\xEF\xBB\xBF\"Id\",\"Title\"\n\"7\",\"=1+1\"\n", $this->export_contents( $admin ) );
	}

	/**
	 * TCPDF on request, whatever the PHP version; the producer string tells the engines apart.
	 */
	public function test_the_pdf_engine_filter_selects_tcpdf() {
		add_filter(
			'exports_reports_export_pdf_engine',
			static function () {
				return 'tcpdf';
			}
		);

		$report = $this->report( 'SELECT id, channel, title, revenue FROM ' . self::videos() . ' ORDER BY id', 'PDF export' );

		$this->assert_pdf_export( $this->pdf_export( $report ), 6 );
	}
}
