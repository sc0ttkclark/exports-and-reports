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

		$_GET['limit'] = '2';

		$report = $this->view(
			'SELECT id, revenue FROM ' . self::videos() . ' ORDER BY id',
			'Paged totals',
			array( 'sql_count' => 'SELECT SUM(revenue) AS revenue FROM ' . self::videos() )
		);

		$rows_in_table = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::videos() ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertGreaterThan( 2, $rows_in_table );
		$this->assertSame( $rows_in_table, (int) $report->total );
		$this->assertCount( 2, $report->data );
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
}
