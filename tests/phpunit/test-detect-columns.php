<?php
/**
 * The field editor's Detect Fields button: the columns one row of the report query returns, with the data
 * type that fits each, over admin-ajax.
 *
 * @package Exports_And_Reports
 */

class Tests_Detect_Columns extends Exports_Reports_Test_Case {

	/**
	 * A plain query: the columns in order with the types of their columns, one row read, and the session's row
	 * cap put back afterwards.
	 */
	public function test_the_columns_of_a_query_with_their_data_types() {
		global $wpdb;

		$detected = exports_reports_detect_columns( 'SELECT id, channel, title, revenue, published, (revenue > 50) AS big, DATE_FORMAT(published, "%H:%i") AS at_time, NOW() AS seen FROM ' . self::videos() . ' ORDER BY id' );

		$this->assertSame( '', $detected['error'] );
		$this->assertSame(
			array(
				array( 'name' => 'id', 'type' => 'number' ),
				array( 'name' => 'channel', 'type' => 'text' ),
				array( 'name' => 'title', 'type' => 'text' ),
				array( 'name' => 'revenue', 'type' => 'decimal' ),
				array( 'name' => 'published', 'type' => 'date' ),
				array( 'name' => 'big', 'type' => 'number' ),
				array( 'name' => 'at_time', 'type' => 'text' ),
				array( 'name' => 'seen', 'type' => 'datetime' ),
			),
			$detected['columns']
		);
		$this->assertSame( '18446744073709551615', (string) $wpdb->get_var( 'SELECT @@SESSION.sql_select_limit' ), 'the row cap is back to its default' );
	}

	/**
	 * The query runs as the report runs it: the statements before the last first, so the last reads what they set,
	 * and the placeholders substituted; one row is read, the cap notwithstanding the query's size.
	 */
	public function test_the_statements_before_the_query_run_and_one_row_is_read() {
		$read = array();
		$spy  = static function ( $query ) use ( &$read ) {
			$read[] = $query;

			return $query;
		};

		add_filter( 'query', $spy );

		$detected = exports_reports_detect_columns( "SET @note = 'x';\nSELECT id, @note AS note FROM " . self::videos() . ' WHERE %%WHERE%% 1 = 1 ORDER BY id LIMIT %%LIMIT%%' );

		remove_filter( 'query', $spy );

		$this->assertSame( '', $detected['error'] );
		$this->assertSame( array( 'id', 'note' ), array_column( $detected['columns'], 'name' ) );
		$this->assertSame( 1, $detected['rows'], 'one row read of ' . self::$video_rows );
		$this->assertContains( "SET @note = 'x'", $read );
		$this->assertSame( 1, count( preg_grep( '/^SELECT id, @note AS note FROM .* WHERE\s+1 = 1 ORDER BY id\s*$/', $read ) ), 'the placeholders are gone and no LIMIT of the plugin\'s is in' );
	}

	/**
	 * A dynamic statement, MariaDB only: the cap reaches it too.
	 */
	public function test_the_columns_of_a_dynamic_statement() {
		if ( ! $this->is_mariadb() ) {
			$this->markTestSkipped( 'EXECUTE IMMEDIATE is MariaDB only.' );
		}

		$detected = exports_reports_detect_columns( "EXECUTE IMMEDIATE CONCAT('SELECT channel, COUNT(*) AS videos FROM " . self::videos() . " GROUP BY channel')" );

		$this->assertSame( '', $detected['error'] );
		$this->assertSame(
			array(
				array( 'name' => 'channel', 'type' => 'text' ),
				array( 'name' => 'videos', 'type' => 'number' ),
			),
			$detected['columns']
		);
		$this->assertSame( 1, $detected['rows'] );
	}

	/**
	 * A failing query answers with the database's message; a last statement that returns no rows with a note;
	 * neither leaves the row cap behind.
	 */
	public function test_a_failing_query_and_a_statement_without_rows_are_reported() {
		global $wpdb;

		$failing = exports_reports_detect_columns( 'SELECT nothing FROM ' . self::videos() . '_missing' );
		$no_rows = exports_reports_detect_columns( 'SET @x = 1' );

		$this->assertSame( array(), $failing['columns'] );
		$this->assertSame( 1, preg_match( "/Unknown column|doesn't exist/", $failing['error'] ), 'the database\'s message (MariaDB names the column, MySQL the table): ' . $failing['error'] );
		$this->assertSame( array(), $no_rows['columns'] );
		$this->assertSame( 'The last statement of the query returns no rows.', $no_rows['error'] );
		$this->assertSame( '18446744073709551615', (string) $wpdb->get_var( 'SELECT @@SESSION.sql_select_limit' ) );
	}

	/**
	 * The field editor carries the button, the nonce and the texts its script shows.
	 */
	public function test_the_field_editor_offers_the_detection() {
		$row      = new stdClass();
		$row->row = array( 'field_data' => '' );

		ob_start();
		exports_reports_report_field( 'field_data', array(), $row );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'class="button er-fields__detect"', $html );
		$this->assertSame( 1, preg_match( '/data-detect-nonce="([a-f0-9]+)"/', $html, $nonce ) );
		$this->assertNotFalse( wp_verify_nonce( $nonce[1], 'exports-reports-detect-columns' ) );
		$this->assertStringContainsString( 'data-confirm-remove="Remove this field from the report?"', $html );
		$this->assertStringContainsString( 'class="er-fields__note"', $html );
	}

	/**
	 * The capability the action asks for is the Reports Admin menu's.
	 */
	public function test_only_a_user_who_manages_reports_may_detect() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( exports_reports_current_user_can_manage() );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( exports_reports_current_user_can_manage() );

		wp_set_current_user( 0 );
	}
}
