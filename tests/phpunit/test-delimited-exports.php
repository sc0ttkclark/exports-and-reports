<?php
/**
 * The delimited exports: CSV and TSV, streamed from an unbuffered result or buffered, every value
 * quoted, the byte order mark first, formula-like text neutralised, the file names.
 *
 * @package Exports_And_Reports
 */

class Tests_Delimited_Exports extends Exports_Reports_Test_Case {

	/**
	 * The CSV export of the whole table.
	 *
	 * @param bool $stream Whether export_sv() reads the rows itself.
	 * @return string The file's contents.
	 */
	protected function csv_of_the_table( $stream ) {
		list( $admin, , $error ) = $this->run_report( 'SELECT id, channel, title, revenue FROM ' . self::videos() . ' ORDER BY id', 'Export ' . ( $stream ? 'streamed' : 'buffered' ), $stream );

		$this->assertSame( '', $error );

		$csv = $this->export_contents( $admin );

		$this->assertNotSame( '', $csv, 'a file was written' );

		return $csv;
	}

	/**
	 * Every value quoted, the labels ucwords() of the column names, the formula-like title wrapped in a
	 * string formula, the title with quotes, a comma and a semicolon intact, a negative amount left a
	 * number, a line break replaced by a space.
	 *
	 * @param string $csv The file's contents.
	 */
	protected function assert_csv_of_the_table( $csv ) {
		$lines = explode( "\n", rtrim( $csv, "\n" ) );

		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv, 'the byte order mark comes first' );
		$this->assertCount( self::$video_rows + 1, $lines );
		$this->assertSame( "\xEF\xBB\xBF" . '"Id","Channel","Title","Revenue"', $lines[0] );
		$this->assertSame( '"7","beta","="""" & ""=1+1""","10.50"', $lines[7] );
		$this->assertSame( '"8","gamma","Say ""hi"", pause; go","12.00"', $lines[8] );
		$this->assertSame( '"9","alpha","Video 9","-13.50"', $lines[9] );
		$this->assertSame( '"10","beta","Two lines","15.00"', $lines[10] );
	}

	public function test_csv_export_streamed_from_an_unbuffered_result() {
		$this->assert_csv_of_the_table( $this->csv_of_the_table( true ) );
	}

	public function test_csv_export_buffered() {
		$this->assert_csv_of_the_table( $this->csv_of_the_table( false ) );
	}

	public function test_streamed_and_buffered_csv_exports_are_identical() {
		$this->assertSame( $this->csv_of_the_table( false ), $this->csv_of_the_table( true ) );
	}

	/**
	 * A definition of several statements streams too: the ones before the query run first, buffered; the query
	 * is the unbuffered one.
	 */
	public function test_a_streamed_export_runs_the_statements_before_the_query_first() {
		list( $admin, , $error ) = $this->run_report( "SET @remainder = 2;\nSELECT id, title FROM " . self::videos() . " WHERE id % 3 = @remainder ORDER BY id", 'Streamed variables', true );

		$this->assertSame( '', $error );
		$this->assertStringContainsString( ";\n", $admin->stream_sql, 'the statements reach the exporter together' );

		$lines = explode( "\n", rtrim( $this->export_contents( $admin ), "\n" ) );

		$this->assertCount( 21, $lines );
		$this->assertStringStartsWith( '"2",', $lines[1] );
	}

	public function test_tsv_export_is_the_same_writer_with_a_tab() {
		list( $admin, , $error ) = $this->run_report( 'SELECT id, channel FROM ' . self::videos() . ' WHERE id <= 2 ORDER BY id', 'Export tsv', true, array(), 'tsv' );

		$this->assertSame( '', $error );
		$this->assertSame( "\xEF\xBB\xBF\"Id\"\t\"Channel\"\n\"1\"\t\"beta\"\n\"2\"\t\"gamma\"\n", $this->export_contents( $admin ) );
	}

	/**
	 * Streaming needs plain columns: a related column or a custom display callback keeps the buffered path.
	 */
	public function test_streaming_only_with_plain_columns() {
		$sql     = 'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1';
		$columns = array(
			'id'      => array( 'label' => 'Id' ),
			'channel' => array( 'label' => 'Channel' ),
		);

		$plain = $this->report( $sql, 'Plain columns', array( 'columns' => $columns ) );

		$columns['channel']['custom_display'] = 'exports_reports_tests_shout';

		$custom = $this->report( $sql, 'Custom display', array( 'columns' => $columns ) );

		$columns['channel'] = array(
			'label'         => 'Channel',
			'type'          => 'related',
			'related'       => self::$videos_table,
			'related_id'    => 'channel',
			'related_field' => 'channel',
		);

		$related = $this->report( $sql, 'Related column', array( 'columns' => $columns ) );

		$this->assertTrue( $plain->can_stream_export() );
		$this->assertFalse( $custom->can_stream_export() );
		$this->assertFalse( $related->can_stream_export() );
	}

	public function test_a_custom_display_column_exports_through_the_buffered_path() {
		$custom = $this->report(
			'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1',
			'Custom display',
			array(
				'columns' => array(
					'id'      => array( 'label' => 'Id' ),
					'channel' => array(
						'label'          => 'Channel',
						'custom_display' => 'exports_reports_tests_shout',
					),
				),
			)
		);

		$custom->export_type = 'csv';

		$before = $this->export_files();

		$this->export_from_link( $custom );

		$new      = array_values( array_diff( $this->export_files(), $before ) );
		$contents = 1 === count( $new ) ? (string) file_get_contents( $new[0] ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		foreach ( $new as $path ) {
			wp_delete_file( $path );
		}

		$this->assertCount( 1, $new, 'one file was written' );
		$this->assertSame( "\xEF\xBB\xBF\"Id\",\"Channel\"\n\"1\",\"BETA\"\n", $contents );
	}

	/**
	 * A report name outside ASCII still gives a file name the download link carries.
	 */
	public function test_export_file_names_stay_ascii_for_a_report_named_outside_ascii() {
		list( $admin ) = $this->run_report( 'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1', '报告 Ünïcode / Sales' );

		ob_start();
		$file = $admin->export_sv();
		ob_end_clean();

		$path   = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
		$exists = is_string( $file ) && file_exists( $path );

		if ( is_string( $file ) ) {
			wp_delete_file( $path );
		}

		$this->assertIsString( $file );
		$this->assertSame( 1, preg_match( '/^unicode_sales_\d{2}-\d{2}-\d{4}_\d{2}-\d{2}-\d{2}_[A-Za-z0-9]{8}\.csv$/', $file ), $file );
		$this->assertSame( $file, sanitize_file_name( $file ), 'the download link reads the name back unchanged' );
		$this->assertTrue( $exists, 'the file was written' );

		$blank = $this->report( 'SELECT id FROM ' . self::videos() . ' WHERE id = 1', '报告' );

		$this->assertStringStartsWith( 'export_', $blank->export_file_name( 'csv' ), 'nothing of the name survives: export_' );
	}

	/**
	 * A column expression may contain %d.
	 */
	public function test_a_streamed_export_with_a_search_term_matches_the_buffered_one() {
		global $wpdb;

		$options = array(
			'search'  => true,
			'columns' => array(
				'id'    => array( 'label' => 'Id' ),
				'title' => array(
					'label'     => 'Title',
					'real_name' => "CONCAT(title, '%d')",
					'search'    => true,
				),
			),
		);
		$want    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() . " WHERE title LIKE '%Video 1%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$csv     = array();
		$modes   = array(
			'streamed' => true,
			'buffered' => false,
		);

		foreach ( $modes as $mode => $stream ) {
			$_GET = array( 'search_query' => 'Video 1' );

			list( $admin, , $error ) = $this->run_report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Search export', $stream, $options );

			$_GET = array();

			$this->assertSame( '', $error, $mode );

			$csv[ $mode ] = $this->export_contents( $admin );
		}

		$this->assertGreaterThan( 0, $want );
		$this->assertCount( $want + 1, explode( "\n", rtrim( $csv['buffered'], "\n" ) ) );
		$this->assertSame( $csv['buffered'], $csv['streamed'] );
	}
}
