<?php
/**
 * Report definitions of several shapes through get_data( true ), the path the report page and the
 * exports share, and the page view's search, filter, totals and sort handling.
 *
 * @package Exports_And_Reports
 */

class Tests_Report_Queries extends Exports_Reports_Test_Case {

	/**
	 * Runs the page view of a report definition and returns the instance.
	 *
	 * @param string $sql     The report SQL.
	 * @param string $name    The report name.
	 * @param array  $options Options added to the defaults.
	 * @return Exports_Reports_Admin_UI
	 */
	protected function view( $sql, $name, $options = array() ) {
		$report = $this->report( $sql, $name, $options );

		ob_start();
		$report->get_data( false );
		ob_end_clean();

		return $report;
	}

	public function test_a_plain_select() {
		list( , $rows, $error ) = $this->run_report( 'SELECT id, channel, title, revenue FROM ' . self::videos() . ' ORDER BY id', 'Plain' );

		$this->assertSame( '', $error );
		$this->assertCount( self::$video_rows, $rows );
		$this->assertSame( array( 'id', 'channel', 'title', 'revenue' ), array_keys( $rows[0] ) );
	}

	public function test_a_common_table_expression() {
		list( , $rows, $error ) = $this->run_report( 'WITH per_channel AS ( SELECT channel, SUM(revenue) AS total FROM ' . self::videos() . ' GROUP BY channel ) SELECT channel, total FROM per_channel ORDER BY channel', 'CTE' );

		$this->assertSame( '', $error );
		$this->assertCount( 3, $rows );
		$this->assertSame( 'alpha', $rows[0]['channel'] );
	}

	/**
	 * A dynamic statement with the LIMIT placeholder inside its string; MariaDB only.
	 */
	public function test_execute_immediate_with_the_limit_placeholder() {
		if ( ! $this->is_mariadb() ) {
			$this->markTestSkipped( 'EXECUTE IMMEDIATE is MariaDB only.' );
		}

		list( , $rows, $error ) = $this->run_report( "EXECUTE IMMEDIATE CONCAT('SELECT channel, COUNT(*) AS videos FROM " . self::videos() . " GROUP BY channel ORDER BY channel LIMIT %%LIMIT%%')", 'Dynamic' );

		$this->assertSame( '', $error );
		$this->assertCount( 3, $rows );
		$this->assertSame( 20, (int) $rows[0]['videos'] );
	}

	/**
	 * A definition of several statements: the ones before the query run first, on the same connection, so
	 * the query reads the session variables they set. Doubled and trailing semicolons, and a semicolon in a
	 * string, do not get in the way. (The variables compared are numbers: a text variable takes the
	 * connection's collation, which need not be the column's.)
	 */
	public function test_statements_before_the_query_run_first() {
		list( , $rows, $error ) = $this->run_report( "SET @remainder = 0;;\nSET @note = 'a; b';\nSELECT id, title, @note AS note FROM " . self::videos() . " WHERE id % 3 = @remainder ORDER BY id;", 'Session variables' );

		$this->assertSame( '', $error );
		$this->assertCount( 20, $rows );
		$this->assertSame( 3, (int) $rows[0]['id'] );
		$this->assertSame( 'a; b', $rows[0]['note'] );
	}

	/**
	 * The page view of such a definition: the count and the page's LIMIT go into the last statement; the ones
	 * before it stay as written (a SELECT in one of them does not take the count).
	 */
	public function test_the_page_of_several_statements_counts_the_last() {
		$report        = $this->report( "SET @total = ( SELECT COUNT(*) FROM " . self::videos() . " );\nSELECT id, title, @total AS total FROM " . self::videos() . " WHERE channel = 'beta' ORDER BY id", 'Session variables page' );
		$report->limit = 5;

		ob_start();
		$report->get_data( false );
		ob_end_clean();

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( 20, (int) $report->total );
		$this->assertCount( 5, $report->data );
		$this->assertSame( self::$video_rows, (int) $report->data[0]['total'] );

		$sql = $report->query->statement( false );
		$sql = $sql['sql'];

		$this->assertStringStartsWith( 'SET @total = ( SELECT COUNT(*)', $sql );
		$this->assertSame( 1, substr_count( $sql, 'SQL_CALC_FOUND_ROWS' ) );
		$this->assertGreaterThan( strpos( $sql, ";\n" ), strpos( $sql, 'SQL_CALC_FOUND_ROWS' ), 'the count goes into the last statement' );
		$this->assertGreaterThan( strpos( $sql, ";\n" ), strpos( $sql, ' LIMIT ' ), 'the page goes into the last statement' );
	}

	/**
	 * The shape dynamic reports take: session variables, then EXECUTE IMMEDIATE with the LIMIT placeholder
	 * inside its string and a line break before its SELECT, which takes the count; MariaDB only.
	 */
	public function test_session_variables_then_execute_immediate() {
		if ( ! $this->is_mariadb() ) {
			$this->markTestSkipped( 'EXECUTE IMMEDIATE is MariaDB only.' );
		}

		$report        = $this->report( "SET @least = 41;;\nEXECUTE IMMEDIATE CONCAT('\nSELECT id, title FROM " . self::videos() . " WHERE id >= ', @least, ' ORDER BY id LIMIT %%LIMIT%%');", 'Dynamic with variables' );
		$report->limit = 5;

		ob_start();
		$report->get_data( false );
		ob_end_clean();

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( 20, (int) $report->total );
		$this->assertCount( 5, $report->data );
		$this->assertSame( 41, (int) $report->data[0]['id'] );
		$this->assertSame( array( 'id', 'title' ), array_keys( $report->data[0] ), 'the columns come from the result' );
	}

	/**
	 * A statement before the query that fails stops the report with the database's error; nothing runs after it.
	 */
	public function test_a_failing_statement_before_the_query_stops_the_report() {
		global $wpdb;

		$shown = $wpdb->suppress_errors();

		list( , $rows, $error ) = $this->run_report( "SET @broken = (SELECT no_such_column FROM " . self::videos() . ");\nSELECT id FROM " . self::videos(), 'Broken variable' );

		$wpdb->suppress_errors( $shown );

		$this->assertSame( array(), $rows );
		$this->assertStringContainsString( 'no_such_column', $error );
	}

	/**
	 * The splitter: semicolons inside quotes and comments do not split, a comment stays with its statement,
	 * empty pieces and comment-only pieces are dropped.
	 */
	public function test_statements_are_split_outside_quotes_and_comments() {
		$sql = "SET @a = 'x;y';; -- a note; not a split\nSET @b = \"p;q\"; /* c; */ SELECT `we;ird`, 'it\\'s' AS apostrophe FROM t WHERE a LIKE '%;%';\n# tail; comment";

		$this->assertSame(
			array(
				"SET @a = 'x;y'",
				"-- a note; not a split\nSET @b = \"p;q\"",
				"/* c; */ SELECT `we;ird`, 'it\\'s' AS apostrophe FROM t WHERE a LIKE '%;%'",
			),
			Exports_Reports_Query::statements( $sql )
		);
		$this->assertSame( array( 'SELECT 1' ), Exports_Reports_Query::statements( ' SELECT 1 ; ; ' ) );
		$this->assertSame( array(), Exports_Reports_Query::statements( "-- nothing\n;" ) );
	}

	/**
	 * A full read after the page (the list screen writes an export after it has loaded its page) keeps the
	 * page's count; a streamed export counts nothing and must not zero it.
	 */
	public function test_a_full_read_after_the_page_keeps_the_page_count() {
		$report = $this->view( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Count kept' );

		$this->assertSame( self::$video_rows, (int) $report->total );

		$report->stream_export = true;

		ob_start();
		$report->get_data( true );
		ob_end_clean();

		$report->stream_export = false;

		$this->assertSame( self::$video_rows, (int) $report->total );
	}

	/**
	 * The paging prints the page count the way it prints the row count.
	 */
	public function test_the_page_count_is_formatted_like_the_row_count() {
		$report        = $this->report( 'SELECT id FROM ' . self::videos(), 'Pages' );
		$report->total = 100000;
		$report->limit = 1;

		ob_start();
		$report->pagination( true );
		$html = ob_get_clean();

		$this->assertStringContainsString( '100,000 pages', $html );
		$this->assertStringContainsString( '<span class="total-pages">100,000</span>', $html );
	}

	/**
	 * The row count goes into the main SELECT, not into the first SELECT in the text (the query of a WITH clause).
	 */
	public function test_the_row_count_goes_into_the_main_select_of_a_cte_query() {
		$report = $this->view( 'WITH per_channel AS ( SELECT channel, COUNT(*) AS videos FROM ' . self::videos() . ' GROUP BY channel ) SELECT channel, videos FROM per_channel ORDER BY channel', 'CTE page' );

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( 3, (int) $report->total );
		$this->assertCount( 3, $report->data );

		$sql = $report->query->statement( false );
		$sql = $sql['sql'];

		$this->assertStringContainsString( '( SELECT channel, COUNT(*)', $sql, 'the WITH clause is untouched' );
		$this->assertStringContainsString( ') SELECT SQL_CALC_FOUND_ROWS channel, videos FROM per_channel', $sql );
		$this->assertStringContainsString( 'ORDER BY channel LIMIT 0,', $sql, 'the page goes after the main ORDER BY' );
	}

	/**
	 * A sort from the request goes into the main ORDER BY, not into a window function's.
	 */
	public function test_a_sort_goes_into_the_main_order_by_not_a_window_function() {
		$_GET['order']     = 'id';
		$_GET['order_dir'] = 'DESC';

		$report = $this->view( 'SELECT id, title, ROW_NUMBER() OVER (PARTITION BY channel ORDER BY id DESC) AS rn FROM ' . self::videos() . ' ORDER BY title', 'Window sort' );

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( self::$video_rows, (int) $report->data[0]['id'] );

		$sql = $report->query->statement( false );
		$sql = $sql['sql'];

		$this->assertStringContainsString( 'OVER (PARTITION BY channel ORDER BY id DESC)', $sql, 'the window function is untouched' );
		$this->assertSame( 1, preg_match( '/ORDER BY `id` DESC,\\s+title LIMIT/', $sql ), $sql );
	}

	/**
	 * A filter goes into the main SELECT's WHERE (added before its ORDER BY), not into a WITH clause's WHERE.
	 */
	public function test_a_filter_goes_into_the_main_where_not_a_cte_where() {
		$_GET = array( 'filter_channel' => array( 'alpha' ) );

		$report = $this->view( 'WITH recent AS ( SELECT * FROM ' . self::videos() . ' WHERE id > 30 ) SELECT id, channel FROM recent ORDER BY id', 'CTE filter', $this->channel_filter_options() );

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( 10, (int) $report->total );
		$this->assertSame( 33, (int) $report->data[0]['id'] );

		$sql = $report->query->statement( false );
		$sql = $sql['sql'];

		$this->assertStringContainsString( 'WHERE id > 30 ) SELECT', $sql, 'the WITH clause is untouched' );
		$this->assertSame( 1, preg_match( '/FROM recent\\s+WHERE\\s*\\(`channel` LIKE/', $sql ), $sql );
	}

	/**
	 * A statement with a LIMIT of its own and no placeholder pages itself: one page of what it returns, no count.
	 */
	public function test_a_statement_with_its_own_limit_is_one_page() {
		$report = $this->view( 'SELECT id FROM ' . self::videos() . ' ORDER BY id LIMIT 7', 'Own limit' );

		$this->assertSame( '', $this->db_error() );
		$this->assertCount( 7, $report->data );
		$this->assertSame( 7, (int) $report->total );

		$sql = $report->query->statement( false );

		$this->assertStringNotContainsString( 'SQL_CALC_FOUND_ROWS', $sql['sql'] );
		$this->assertStringEndsWith( 'LIMIT 7 ', $sql['sql'] );
	}

	/**
	 * The dynamic shape: WITH clauses inside EXECUTE IMMEDIATE's string, the main SELECT after them with no
	 * space before it, the LIMIT placeholder inside the string; MariaDB only.
	 */
	public function test_the_row_count_goes_into_the_main_select_of_a_dynamic_cte_query() {
		if ( ! $this->is_mariadb() ) {
			$this->markTestSkipped( 'EXECUTE IMMEDIATE is MariaDB only.' );
		}

		$report        = $this->report( "SET @least = 41;\nEXECUTE IMMEDIATE CONCAT(\"WITH recent AS (SELECT id, title FROM " . self::videos() . " WHERE id >= \", @least, \"), other AS (SELECT 1 AS one)\nSELECT id, title FROM recent ORDER BY id LIMIT %%LIMIT%%\")", 'Dynamic CTE' );
		$report->limit = 5;

		ob_start();
		$report->get_data( false );
		ob_end_clean();

		$this->assertSame( '', $this->db_error() );
		$this->assertSame( 20, (int) $report->total );
		$this->assertCount( 5, $report->data );
		$this->assertSame( 41, (int) $report->data[0]['id'] );

		$sql = $report->query->statement( false );

		$this->assertStringContainsString( 'AS one) SELECT SQL_CALC_FOUND_ROWS id, title FROM recent', $sql['sql'] );
	}

	/**
	 * The scanner: the main SELECT and its clauses by depth, placeholders where they stand, the end of a dynamic statement's text.
	 */
	public function test_the_outline_finds_the_main_select_and_its_clauses() {
		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-query.php';

		$outline = Exports_Reports_Query::outline( " WITH a AS (SELECT x FROM t WHERE y = 1 ORDER BY z) SELECT a.x, (SELECT 1) AS one FROM a WHERE a.x > 0 GROUP BY a.x HAVING COUNT(*) > 1 ORDER BY a.x LIMIT 5 " );

		$plain = " WITH a AS (SELECT x FROM t WHERE y = 1 ORDER BY z) SELECT a.x, (SELECT 1) AS one FROM a WHERE a.x > 0 GROUP BY a.x HAVING COUNT(*) > 1 ORDER BY a.x LIMIT 5 ";

		$this->assertSame( 0, $outline['depth'] );
		$this->assertSame( 'SELECT a.x,', substr( $plain, $outline['select'], 11 ) );
		$this->assertSame( 'FROM a', substr( $plain, $outline['from'], 6 ) );
		$this->assertLessThan( $outline['where'], $outline['from'] );
		$this->assertLessThan( $outline['group'], $outline['where'] );
		$this->assertLessThan( $outline['having'], $outline['group'] );
		$this->assertLessThan( $outline['order'], $outline['having'] );
		$this->assertLessThan( $outline['limit'], $outline['order'] );

		$dynamic = 'EXECUTE IMMEDIATE CONCAT("SELECT a FROM t WHERE b = \'x)\' LIMIT %%LIMIT%%")';
		$outline = Exports_Reports_Query::outline( $dynamic );

		$this->assertSame( 1, $outline['depth'] );
		$this->assertSame( strrpos( $dynamic, '"' ), $outline['end'], 'a clause added at the end goes inside the string' );
		$this->assertNotFalse( $outline['where'] );
		$this->assertNotFalse( $outline['limit'] );

		$this->assertFalse( Exports_Reports_Query::outline( 'CALL some_procedure()' )['select'] );
	}

	/**
	 * A SELECT in a comment, in a string or in a quoted identifier is not the main one; a SELECT right after a
	 * parenthesis, with or without a space, is read the same; the words are matched whole and in any case.
	 */
	public function test_the_outline_ignores_comments_and_strings_and_reads_select_after_a_parenthesis() {
		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-query.php';

		$sql = "-- SELECT nothing FROM here\n# SELECT nothing either\n/* SELECT * FROM nowhere; */\nSELECT 'SELECT a FROM b' AS quoted, `select` AS backticked, (SELECT 1) AS tight, ( SELECT 2 ) AS spaced, selectors FROM t -- WHERE commented\nWHERE x = '(' ORDER BY y";

		$outline = Exports_Reports_Query::outline( $sql );

		$this->assertSame( "SELECT 'SELECT a FROM b'", substr( $sql, $outline['select'], 24 ) );
		$this->assertSame( 'FROM t', substr( $sql, $outline['from'], 6 ) );
		$this->assertSame( "WHERE x = '('", substr( $sql, $outline['where'], 13 ) );
		$this->assertSame( 'ORDER BY y', substr( $sql, $outline['order'], 10 ) );
		$this->assertFalse( $outline['limit'] );

		$tokens = array_column( Exports_Reports_Query::tokens( $sql )['tokens'], 'kind' );

		$this->assertSame( array( 'select', 'select', 'select', 'from', 'where', 'order' ), $tokens, 'the two subqueries, nothing from comments, strings or identifiers' );

		$lower = Exports_Reports_Query::outline( 'with c as (select 1) select a from c' );

		$this->assertSame( 'select a', substr( 'with c as (select 1) select a from c', $lower['select'], 8 ) );
	}

	/**
	 * A failing query names the database's message to a user who may edit reports and a plain note to others;
	 * a query that returns no rows on a report without field definitions says so.
	 */
	public function test_a_failing_query_is_reported_and_an_empty_result_is_not_a_configuration_error() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$subscriber    = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		global $wpdb;

		$shown  = $wpdb->suppress_errors();
		$report = $this->view( 'SELECT no_such_column FROM ' . self::videos(), 'Broken' );
		$wpdb->suppress_errors( $shown );

		$this->assertStringContainsString( 'no_such_column', $report->query_error );

		wp_set_current_user( $administrator );
		ob_start();
		$report->table();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'query failed', $html );
		$this->assertStringContainsString( 'no_such_column', $html );
		$this->assertStringNotContainsString( 'Missing &quot;columns&quot;', $html );
		$this->assertStringNotContainsString( 'Missing "columns"', $html );

		wp_set_current_user( $subscriber );
		ob_start();
		$report->table();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Ask an administrator', $html );
		$this->assertStringNotContainsString( 'no_such_column', $html );

		$report = $this->view( 'SELECT id FROM ' . self::videos() . ' WHERE id > 1000', 'Empty' );

		$this->assertSame( '', $report->query_error );

		ob_start();
		$report->table();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'returned no rows', $html );
		$this->assertStringNotContainsString( 'Missing', $html );
	}

	/**
	 * The placeholder removal must not depend on spaces.
	 */
	/**
	 * The row count of a page view is kept for a while (Settings, five minutes unless changed) and spares the
	 * query its count on the page views that follow; the Settings page's button and a cache turned off each
	 * count again. The key is the rows the count describes: another search gets its own count.
	 */
	public function test_the_row_count_is_kept_and_the_query_runs_without_it_meanwhile() {
		global $wpdb;

		$seen = array();
		$spy  = static function ( $sql ) use ( &$seen ) {
			$seen[] = $sql;

			return $sql;
		};

		add_filter( 'exports_reports_get_data_sql', $spy );
		update_option( 'exports_reports_count_cache', 5 );

		$sql   = 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id';
		$first = $this->view( $sql, 'Count cache' );

		$wpdb->insert(
			self::$videos_table,
			array(
				'channel'   => 'delta',
				'title'     => 'Video extra',
				'revenue'   => 1,
				'published' => '2026-01-01',
			)
		);
		$extra = (int) $wpdb->insert_id;

		$second = $this->view( $sql, 'Count cache' );

		ob_start();
		$first->pagination( true );
		$first_pagination = (string) ob_get_clean();

		ob_start();
		$second->pagination( true );
		$second_pagination = (string) ob_get_clean();

		$_GET['search_query'] = 'extra';
		$searched             = $this->view( $sql, 'Count cache', array( 'search' => true, 'columns' => array( 'id', 'title' => array( 'search' => true ) ) ) );
		$_GET                 = array();

		exports_reports_forget_counts();

		$third = $this->view( $sql, 'Count cache' );

		update_option( 'exports_reports_count_cache', 0 );
		$wpdb->delete( self::$videos_table, array( 'id' => $extra ) );

		$fourth = $this->view( $sql, 'Count cache' );

		remove_filter( 'exports_reports_get_data_sql', $spy );
		delete_option( 'exports_reports_count_cache' );

		$this->assertSame( self::$video_rows, (int) $first->total );
		$this->assertStringContainsString( 'SQL_CALC_FOUND_ROWS', $seen[0] );
		$this->assertSame( self::$video_rows, (int) $second->total, 'the kept count, a row more notwithstanding' );
		$this->assertStringNotContainsString( 'SQL_CALC_FOUND_ROWS', $seen[1], 'the query ran without the count' );
		$this->assertStringNotContainsString( 'exports-reports-counted', $first_pagination, 'the page that counted says nothing' );
		$this->assertSame( 1, preg_match( '/\(counted \d+ \w+ ago\)/', $second_pagination ), 'the page with the kept count says when it was counted: ' . $second_pagination );
		$this->assertSame( 1, (int) $searched->total, 'a search has a count of its own' );
		$this->assertSame( self::$video_rows + 1, (int) $third->total, 'forgotten: counted again' );
		$this->assertStringContainsString( 'SQL_CALC_FOUND_ROWS', $seen[3] );
		$this->assertSame( self::$video_rows, (int) $fourth->total, 'off: counted each time' );
		$this->assertStringContainsString( 'SQL_CALC_FOUND_ROWS', $seen[4] );
	}

	public function test_a_tab_indented_limit_placeholder() {
		list( , $rows, $error ) = $this->run_report( "SELECT id, title\n\tFROM " . self::videos() . "\n\tORDER BY id\n\tLIMIT %%LIMIT%%", 'Tabs' );

		$this->assertSame( '', $error );
		$this->assertCount( self::$video_rows, $rows );
	}

	/**
	 * A search for an underscore matches only titles that contain one.
	 */
	public function test_a_search_terms_like_wildcards_are_escaped() {
		global $wpdb;

		$_GET['search_query'] = '_';

		$report = $this->view(
			'SELECT id, title FROM ' . self::videos() . ' ORDER BY id',
			'Wildcard search',
			array(
				'search'  => true,
				'columns' => array( 'title' ),
			)
		);

		$expected = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::videos() . ' WHERE title LIKE %s', '%' . $wpdb->esc_like( '_' ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertLessThan( self::$video_rows, $expected, 'an unescaped underscore would match every title' );
		$this->assertSame( $expected, (int) $report->total );
	}

	public function test_a_paged_view_with_a_totals_query_counts_every_row() {
		global $wpdb;

		$_GET['limit'] = '10'; // A size the screen offers; any other falls back to the screen's own.

		$report = $this->view(
			'SELECT id, revenue FROM ' . self::videos() . ' ORDER BY id',
			'Paged totals',
			array( 'sql_count' => 'SELECT SUM(revenue) AS revenue FROM ' . self::videos() )
		);

		$rows_in_table = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertGreaterThan( 10, $rows_in_table );
		$this->assertSame( $rows_in_table, (int) $report->total );
		$this->assertCount( 10, $report->data );
		$this->assertTrue( isset( $report->sum_data[0]['revenue'] ), 'the totals row is there' );
	}

	/**
	 * The alias map must not carry the row-count modifier.
	 */
	public function test_a_search_on_an_aliased_column_of_a_report_with_a_totals_query() {
		global $wpdb;

		$_GET['search_query'] = 'alpha';

		$report = $this->view(
			'SELECT channel AS chan, revenue FROM ' . self::videos() . ' ORDER BY id',
			'Alias search',
			array(
				'search'    => true,
				'columns'   => array( 'chan' ),
				'sql_count' => 'SELECT SUM(revenue) AS revenue FROM ' . self::videos() . ' WHERE %%WHERE%% 1=1',
			)
		);

		$alpha_rows = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() . " WHERE channel = 'alpha'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( '', $this->db_error() );
		$this->assertGreaterThan( 0, $alpha_rows );
		$this->assertCount( $alpha_rows, $report->data );
		$this->assertSame( $alpha_rows, (int) $report->total );
	}

	/**
	 * A multiple-select filter sends an array: the SQL takes every value, and the page does not fail.
	 */
	public function test_an_array_filter_value_filters_on_each_value() {
		global $wpdb;

		$_GET = array( 'filter_channel' => array( 'alpha', 'beta', '' ) );

		list( , $rows, $error ) = $this->run_report( 'SELECT id, channel FROM ' . self::videos(), 'Array filter', false, $this->channel_filter_options() );

		$_GET = array();

		$want = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() . " WHERE channel IN ('alpha', 'beta')" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( '', $error );
		$this->assertGreaterThan( 0, $want );
		$this->assertCount( $want, $rows );
	}

	/**
	 * A sort column the request names must be one of the report's columns; anything else keeps the report's own order.
	 */
	public function test_the_sort_column_must_be_one_of_the_reports_columns() {
		$options = array(
			'order'     => '`id`',
			'order_dir' => 'DESC',
		);

		$_GET['order']     = 'no_such_column';
		$_GET['order_dir'] = 'ASC';

		$unknown = $this->view( 'SELECT id, revenue FROM ' . self::videos(), 'Sort check', $options );

		$_GET['order'] = 'revenue';

		$known = $this->view( 'SELECT id, revenue FROM ' . self::videos(), 'Sort check', $options );

		$this->assertTrue( isset( $unknown->data[0]['id'] ), 'the unknown column leaves rows' );
		$this->assertSame( self::$video_rows, (int) $unknown->data[0]['id'], 'the report order, id DESC, applies' );
		$this->assertTrue( isset( $known->data[0]['revenue'], $known->data[1]['revenue'] ) );
		$this->assertLessThanOrEqual( (float) $known->data[1]['revenue'], (float) $known->data[0]['revenue'], 'revenue ASC' );
	}

	/**
	 * The ORDER BY writes the column as the report does.
	 */
	public function test_a_sort_column_is_matched_whatever_its_case() {
		$_GET['order']     = 'Revenue';
		$_GET['order_dir'] = 'ASC';

		$report = $this->view( 'SELECT id, revenue AS Revenue FROM ' . self::videos(), 'Sort alias' );

		$this->assertTrue( isset( $report->data[0]['Revenue'], $report->data[1]['Revenue'] ) );
		$this->assertSame( -13.5, (float) $report->data[0]['Revenue'] );
		$this->assertLessThanOrEqual( (float) $report->data[1]['Revenue'], (float) $report->data[0]['Revenue'] );
	}

	/**
	 * The titles a search or a filter leaves on the page of a report, and the database's complaint.
	 *
	 * @param array  $request The query string's variables.
	 * @param string $sql     The report SQL.
	 * @param array  $columns The report's fields.
	 * @param string $column  The column to read from the rows.
	 * @param array  $options Options added to the defaults.
	 * @return array{0: string[], 1: string, 2: Exports_Reports_Admin_UI}
	 */
	protected function found( array $request, $sql, array $columns, $column, $options = array() ) {
		global $wpdb;

		$this->db_errors  = array();
		$wpdb->last_error = '';

		$_GET = $request;

		$report = $this->view(
			$sql,
			'Search',
			array_merge(
				array(
					'search'  => true,
					'columns' => $columns,
				),
				$options
			)
		);

		$_GET = array();

		return array( wp_list_pluck( $report->data, $column ), wp_strip_all_tags( $this->db_error() ), $report );
	}

	/**
	 * Issue #31, as reported: a row of the second page is found by a search from the first.
	 */
	public function test_a_search_finds_a_row_of_a_later_page() {
		list( $found, $error, $report ) = $this->found( array( 'search_query' => 'Video 42' ), 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', array( 'title' ), 'title' );

		$this->assertSame( '', $error );
		$this->assertSame( array( 'Video 42' ), $found );
		$this->assertSame( 1, (int) $report->total );
	}

	/**
	 * A searched column that is the alias of a column named with its table: each part of the name is
	 * quoted on its own, whether the definition quotes them or not.
	 */
	public function test_a_search_on_the_alias_of_a_table_qualified_column() {
		$queries = array(
			'SELECT v.id, v.title AS name FROM ' . self::videos() . ' v ORDER BY v.id',
			'SELECT `v`.`id`, `v`.`title` AS `name` FROM ' . self::videos() . ' AS `v` ORDER BY `v`.`id`',
		);

		foreach ( $queries as $sql ) {
			list( $found, $error, $report ) = $this->found( array( 'search_query' => 'Video 42' ), $sql, array( 'name' ), 'name' );

			$this->assertSame( '', $error, $sql );
			$this->assertSame( array( 'Video 42' ), $found, $sql );

			$statement = $report->query->statement();

			$this->assertStringContainsString( '(`v`.`title` LIKE ', $statement['sql'], 'each part quoted on its own' );
		}
	}

	/**
	 * A searched column that is the alias of an expression: the expression itself is searched, commas and
	 * all, in the report's query and in its totals query.
	 */
	public function test_a_search_on_the_alias_of_an_expression() {
		global $wpdb;

		$label = "CONCAT( channel, ': ', title )";
		$want  = $wpdb->get_col( 'SELECT ' . $label . ' FROM ' . self::videos() . " WHERE title LIKE 'Video 4%' AND channel = 'beta' ORDER BY id" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the test's own table.

		list( $found, $error, $report ) = $this->found(
			array( 'search_query' => 'beta: Video 4' ),
			'SELECT id, ' . $label . ' AS label, revenue FROM ' . self::videos() . ' ORDER BY id',
			array( 'label' ),
			'label',
			array( 'sql_count' => 'SELECT COUNT(*) AS id FROM ' . self::videos() . ' WHERE %%WHERE%% 1=1' )
		);

		$this->assertSame( '', $error );
		$this->assertCount( 5, $want, 'videos 4, 40, 43, 46 and 49 are on the beta channel' );
		$this->assertSame( $want, $found );
		$this->assertSame( 5, (int) $report->sum_data[0]['id'], 'the totals query takes the same condition' );
	}

	/**
	 * A searched column that is an aggregate cannot be named in WHERE: the search goes into HAVING, where
	 * every column of the result is named by its own name, and a term that matches any of them finds the row.
	 */
	public function test_a_search_on_an_aggregate_goes_into_having() {
		$sql     = 'SELECT channel, COUNT(*) AS videos, SUM(revenue) AS total FROM ' . self::videos() . ' GROUP BY channel ORDER BY channel';
		$columns = array( 'channel', 'videos' );

		list( $by_count, $error, $report ) = $this->found( array( 'search_query' => '20' ), $sql, $columns, 'channel' );

		$statement = $report->query->statement();

		$this->assertSame( '', $error );
		$this->assertSame( array( 'alpha', 'beta', 'gamma' ), $by_count, 'each channel has twenty videos' );
		$this->assertSame( '', $statement['where'] );
		$this->assertStringContainsString( '`channel` LIKE ', $statement['having'] );
		$this->assertStringContainsString( ' OR `videos` LIKE ', $statement['having'] );

		list( $by_name, $error ) = $this->found( array( 'search_query' => 'alp' ), $sql, $columns, 'channel' );

		$this->assertSame( '', $error );
		$this->assertSame( array( 'alpha' ), $by_name, 'the other column of the search still finds its rows' );
	}

	/**
	 * No condition can name the result of a window function, and a condition on an expression that sets a
	 * session variable would run it twice for each row: the search leaves such columns out and still
	 * searches the others. What a quoted string of an expression holds does not make it one of them.
	 */
	public function test_a_search_leaves_out_a_column_no_condition_can_name() {
		$columns = array( 'title', 'position' );

		foreach ( array( 'ROW_NUMBER() OVER ( ORDER BY id )', '( @exports_reports_tests_row := 1 )' ) as $unnamed ) {
			$_GET = array( 'search_query' => 'Video 42' );

			$statement = $this->report( 'SELECT id, title, ' . $unnamed . ' AS position FROM ' . self::videos() . ' ORDER BY id', 'Search', array( 'search' => true, 'columns' => $columns ) )->query->statement();

			$this->assertStringContainsString( '(`title` LIKE ', $statement['where'], $unnamed );
			$this->assertStringNotContainsString( 'position', $statement['where'], $unnamed );
			$this->assertSame( '', $statement['having'], $unnamed );
		}

		list( $found, $error, $report ) = $this->found( array( 'search_query' => 'over (the' ), "SELECT id, CONCAT( title, ' over (the top)' ) AS label FROM " . self::videos() . ' ORDER BY id', array( 'label' ), 'label', array( 'limit' => 100 ) );

		$this->assertSame( '', $error );
		$this->assertCount( self::$video_rows, $found, 'the words of a quoted string are not the words of the expression' );
		$this->assertStringContainsString( "((CONCAT( title, ' over (the top)' )) LIKE ", $report->query->statement()['where'] );
	}

	/**
	 * The filters share the search's naming: a filter on an alias of a qualified column or of an
	 * expression goes into WHERE, one on an aggregate into HAVING.
	 */
	public function test_filters_name_aliased_columns_like_the_search() {
		$filter = static function ( $name ) {
			return array(
				$name => array(
					'label'  => 'Filtered',
					'filter' => true,
				),
			);
		};

		list( $qualified, $error ) = $this->found( array( 'filter_name' => 'Video 42' ), 'SELECT v.id, v.title AS name FROM ' . self::videos() . ' v ORDER BY v.id', $filter( 'name' ), 'name' );

		$this->assertSame( '', $error );
		$this->assertSame( array( 'Video 42' ), $qualified );

		list( $expression, $error ) = $this->found( array( 'filter_label' => 'gamma: Video 5' ), "SELECT id, CONCAT( channel, ': ', title ) AS label FROM " . self::videos() . ' ORDER BY id', $filter( 'label' ), 'label' );

		$this->assertSame( '', $error );
		$this->assertSame( array( 'gamma: Video 5', 'gamma: Video 50', 'gamma: Video 53', 'gamma: Video 56', 'gamma: Video 59' ), $expression );

		list( $aggregate, $error, $report ) = $this->found( array( 'filter_total' => '915' ), 'SELECT channel, SUM(revenue) AS total FROM ' . self::videos() . ' GROUP BY channel ORDER BY channel', $filter( 'total' ), 'channel' );

		// A filter's value is read from the request when the statement is built.
		$_GET      = array( 'filter_total' => '915' );
		$statement = $report->query->statement();
		$_GET      = array();

		$this->assertSame( '', $error );
		$this->assertStringContainsString( '`total` LIKE ', $statement['having'] );
		$this->assertSame( '', $statement['where'] );
		$this->assertCount( 1, $aggregate, 'one channel earned a total that holds 915: ' . implode( ', ', $aggregate ) );
	}

	/**
	 * The select list is read item by item, at its top-level commas: a function call with commas is one
	 * item, so the list still tells which columns the result has, and a sort column is checked against them.
	 */
	public function test_the_select_list_is_split_at_its_top_level_commas() {
		$items = Exports_Reports_Query::select_items( " v.id, CONCAT( a, ', ', b ) AS `full name`, IF( x > 1, 'a,b', \"c,d\" ) AS pick, `t`.`plain`, COUNT(*) AS n, CAST( y AS CHAR ) " );

		$this->assertSame( array( '', 'full name', 'pick', '', 'n', '' ), wp_list_pluck( $items, 'alias' ) );
		$this->assertSame( array( 'id', '', '', 'plain', '', '' ), wp_list_pluck( $items, 'column' ) );
		$this->assertSame( "CONCAT( a, ', ', b )", $items[1]['source'] );
		$this->assertSame( 'CAST( y AS CHAR )', $items[5]['source'], 'the AS of a cast is not an alias' );

		$distinct = Exports_Reports_Query::select_items( ' DISTINCT v.title AS name, channel ' );

		$this->assertSame( array( 'v.title', 'channel' ), wp_list_pluck( $distinct, 'source' ), 'DISTINCT is not part of the first item' );
		$this->assertSame( array( '', 'channel' ), wp_list_pluck( $distinct, 'column' ) );
		$this->assertSame( '`v`.`title`', Exports_Reports_Query::qualified_name( 'v.title' ) );
		$this->assertSame( '`db`.`v`.`my title`', Exports_Reports_Query::qualified_name( 'db . `v`.`my title`' ) );
		$this->assertSame( '', Exports_Reports_Query::qualified_name( 'NULL' ) );
		$this->assertSame( '', Exports_Reports_Query::qualified_name( 'a + b' ) );

		$_GET = array( 'order' => 'no_such_column' );

		$report = $this->view( "SELECT id, CONCAT( channel, ', ', title ) AS label, revenue FROM " . self::videos() . ' ORDER BY id', 'Sort check' );

		$_GET = array();

		$this->assertSame( '', $this->db_error(), 'a sort column the list does not have is not sent to the database' );
		$this->assertFalse( $report->order_from_request );
	}
}
