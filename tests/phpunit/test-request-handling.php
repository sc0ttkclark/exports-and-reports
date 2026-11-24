<?php
/**
 * Request handling: the report form's field editor, the nonces of save(), reorder() and the settings
 * form, and the request values on their way in.
 *
 * @package Exports_And_Reports
 */

class Tests_Request_Handling extends Exports_Reports_Test_Case {

	/**
	 * The report form's row as save() hands it to the field_data callbacks, with two saved fields.
	 *
	 * @return stdClass
	 */
	protected function form_row() {
		$row      = new stdClass();
		$row->do  = 'save';
		$row->row = array(
			'field_data' => wp_json_encode(
				array(
					array(
						'name'        => 'revenue',
						'type'        => 'decimal',
						'label'       => 'Revenue',
						'hide_report' => '1',
						'search'      => '1',
						'filter'      => '1',
						'width'       => '20',
					),
					array(
						'name'    => 'channel',
						'type'    => 'related',
						'related' => 'wp_users',
					),
				)
			),
		);

		return $row;
	}

	/**
	 * Two groups to reorder, and the Manage Groups screen on the first.
	 *
	 * @return array{0: int[], 1: Exports_Reports_Admin_UI} The group ids and the screen.
	 */
	protected function groups_screen() {
		global $wpdb;

		$now = current_time( 'mysql' );
		$ids = array();

		foreach ( array( 'Order A', 'Order B' ) as $weight => $name ) {
			$wpdb->insert(
				$wpdb->exports_reports_groups,
				array(
					'name'        => $name,
					'disabled'    => 0,
					'role_access' => '',
					'weight'      => $weight,
					'created'     => $now,
					'updated'     => $now,
				)
			);

			$ids[] = (int) $wpdb->insert_id;
		}

		$screen = $this->report(
			'',
			'Groups',
			array(
				'readonly'   => false,
				'table'      => $wpdb->exports_reports_groups,
				'identifier' => 'id',
				'reorder'    => 'weight',
				'id'         => $ids[0],
				'do'         => 'save',
			)
		);

		return array( $ids, $screen );
	}

	/**
	 * A column of a group row.
	 *
	 * @param string $column name or weight.
	 * @param int    $id     The group.
	 * @return string
	 */
	protected function group_value( $column, $id ) {
		global $wpdb;

		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT `{$column}` FROM {$wpdb->exports_reports_groups} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function test_the_report_field_editor_renders_the_saved_fields_and_a_template() {
		ob_start();
		exports_reports_report_field( 'field_data', array(), $this->form_row() );
		$html = (string) ob_get_clean();

		$this->assertSame( 3, substr_count( $html, 'class="er-field"' ), 'two fields and the template' );
		$this->assertSame( 1, preg_match( '#name="field_type\[0\]".*?<option value="decimal" selected#s', $html ) );
		$this->assertSame( 1, preg_match( '#name="field_type\[1\]".*?<option value="related" selected#s', $html ) );
		$this->assertSame( 1, preg_match( '#type="checkbox" name="field_hide_report\[0\]" value="1" checked#', $html ) );
		$this->assertSame( 1, preg_match( '#type="checkbox" name="field_search\[0\]" value="0" />#', $html ), 'the searchable flag is off for the first field' );
		$this->assertSame( 1, preg_match( '#type="checkbox" name="field_search\[1\]" value="0" checked#', $html ), 'the searchable flag is on for the second field' );
		$this->assertSame( 1, preg_match( '#name="width\[0\]" value="20"#', $html ) );
		$this->assertStringContainsString( 'name="field_name[__i__]"', $html );
	}

	public function test_the_report_field_editor_saves_the_posted_rows_in_order_unslashed_and_sanitised() {
		$_POST = array(
			'_wpnonce'          => wp_create_nonce( 'exports-reports-form-save' ),
			'field_name'        => array(
				3 => 'revenue',
				7 => 'channel',
				9 => '',
			),
			'field_type'        => array(
				3 => 'decimal',
				7 => 'text',
			),
			'field_hide_report' => array(
				3 => '1',
				7 => '0',
			),
			'field_search'      => array(
				3 => '1',
				7 => '0',
			),
			'width'             => array( 3 => '20' ),
			'field_label'       => array( 3 => "O\\'Brien <b>revenue</b>" ),
			'field_related_sql' => array( 7 => "WHERE name <> 'O\\'Brien'" ),
		);

		$saved = json_decode( exports_reports_report_field_save( '', 'field_data', array(), $this->form_row() ), true );

		$_POST = array();

		$this->assertIsArray( $saved );
		$this->assertCount( 2, $saved );
		$this->assertSame( 'revenue', $saved[0]['name'] );
		$this->assertSame( 'decimal', $saved[0]['type'] );
		$this->assertSame( 1, $saved[0]['hide_report'] );
		$this->assertSame( 1, $saved[0]['search'] );
		$this->assertSame( '20', $saved[0]['width'] );
		$this->assertSame( "O'Brien revenue", $saved[0]['label'] );
		$this->assertSame( 'channel', $saved[1]['name'] );
		$this->assertSame( 0, $saved[1]['search'] );
		$this->assertSame( "WHERE name <> 'O'Brien'", $saved[1]['related_sql'] );
	}

	public function test_save_refuses_a_request_without_the_form_nonce() {
		list( $ids, $screen ) = $this->groups_screen();

		$_POST = array( 'name' => 'Renamed' );

		ob_start();
		$refused = $screen->save();
		$output  = (string) ob_get_clean();

		$_POST = array();

		$this->assertFalse( $refused );
		$this->assertStringContainsString( 'expired', $output );
		$this->assertSame( 'Order A', $this->group_value( 'name', $ids[0] ) );
	}

	public function test_reorder_needs_the_reorder_nonce_then_writes_the_posted_order() {
		list( $ids, $screen ) = $this->groups_screen();

		$_POST = array( 'order' => array( $ids[1], $ids[0] ) );

		ob_start();
		$refused        = $screen->reorder();
		$refused_output = (string) ob_get_clean();

		$weight_refused = (int) $this->group_value( 'weight', $ids[0] );

		$_POST['_wpnonce'] = wp_create_nonce( 'exports-reports-reorder' );

		ob_start();
		$screen->reorder();
		$output = (string) ob_get_clean();

		$weight_a = (int) $this->group_value( 'weight', $ids[0] );
		$weight_b = (int) $this->group_value( 'weight', $ids[1] );

		$_POST = array();

		$this->assertFalse( $refused );
		$this->assertStringContainsString( 'expired', $refused_output );
		$this->assertSame( 0, $weight_refused, 'nothing written without the nonce' );
		$this->assertSame( 1, $weight_a );
		$this->assertSame( 0, $weight_b );
		$this->assertStringContainsString( 'Order updated', $output );
	}

	/**
	 * The plugin's stylesheet is enqueued on its own pages from admin_enqueue_scripts, so it is in the head.
	 */
	public function test_the_stylesheet_is_enqueued_in_the_head_on_the_plugin_pages() {
		global $plugin_page;

		$plugin_page = 'exports-reports-group-3';
		exports_reports_assets( 'reports_page_exports-reports-group-3' );

		$this->assertTrue( wp_style_is( 'exports-reports-admin', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'dashicons', 'enqueued' ) );

		wp_dequeue_style( 'exports-reports-admin' );
		wp_dequeue_style( 'dashicons' );
		$plugin_page = 'edit';
		exports_reports_assets( 'edit.php' );

		$this->assertFalse( wp_style_is( 'exports-reports-admin', 'enqueued' ), 'not on other pages' );

		$plugin_page = null;
	}

	public function test_the_settings_form_carries_its_nonce() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		ob_start();
		exports_reports_settings();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="_wpnonce"', $html );
		$this->assertStringContainsString( 'name="cronjob_token"', $html );
	}

	/**
	 * The management screens (groups, reports) count their own table on every view; only a report view keeps its count.
	 */
	/**
	 * The Manage Groups list sorts as the Reports menu does: by weight, ties by name. Equal weights fell in
	 * insertion order on the list and alphabetical in the menu, so the two showed different orders.
	 */
	public function test_the_management_lists_sort_like_the_menu() {
		global $wpdb;

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		foreach ( array( array( 'Order A', 0 ), array( 'Order B', 1 ), array( 'Order 0', 0 ) ) as $group ) {
			$wpdb->insert(
				$wpdb->exports_reports_groups,
				array(
					'name'        => $group[0],
					'disabled'    => 0,
					'role_access' => '',
					'weight'      => $group[1],
					'created'     => current_time( 'mysql' ),
					'updated'     => current_time( 'mysql' ),
				)
			);
		}

		$_GET = array( 'page' => 'exports-reports-admin' );

		ob_start();
		exports_reports_groups();
		$html = (string) ob_get_clean();

		$_GET = array();

		preg_match_all( '/Order [0AB]/', $html, $seen );

		$menu = $wpdb->get_col( "SELECT `name` FROM {$wpdb->exports_reports_groups} WHERE `name` LIKE 'Order %' ORDER BY `weight`, `name`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertSame( array( 'Order 0', 'Order A', 'Order B' ), $menu );
		$this->assertSame( $menu, array_values( array_unique( $seen[0] ) ), 'the list in the menu\'s order: the later "Order 0" among its equals by name' );
	}

	public function test_the_management_screens_keep_no_count() {
		update_option( 'exports_reports_count_cache', 5 );

		list( , $groups ) = $this->groups_screen();
		$report           = $this->report( 'SELECT id FROM ' . self::videos(), 'Kept' );

		$this->assertSame( '', $groups->count_cache_key( 'SELECT 1' ), 'the groups screen keeps none' );
		$this->assertStringStartsWith( 'exports_reports_count_', $report->count_cache_key( 'SELECT 1' ), 'a report view does' );

		delete_option( 'exports_reports_count_cache' );
	}

	public function test_the_settings_page_saves_the_row_count_cache_and_forgets_the_counts() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$generation = exports_reports_count_cache_generation();

		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'exports-reports-settings' ) );
		$_POST    = array( 'count_cache' => '15' );

		ob_start();
		exports_reports_settings();
		$html = (string) ob_get_clean();

		$_POST = array( 'count_cache_forget' => '1' );

		ob_start();
		exports_reports_settings();
		$forgotten = (string) ob_get_clean();

		$_POST    = array();
		$_REQUEST = array();

		$this->assertSame( 15 * MINUTE_IN_SECONDS, exports_reports_count_cache_ttl() );
		$this->assertStringContainsString( '<option value="15" selected=\'selected\'>', $html );
		$this->assertSame( $generation + 1, exports_reports_count_cache_generation() );
		$this->assertStringContainsString( 'counts are forgotten', $forgotten );

		delete_option( 'exports_reports_count_cache' );
	}

	/**
	 * Request values arrive slashed (wp_magic_quotes) and are read unslashed and sanitized; a value outside
	 * the allowed list (order_dir=asc) leaves the default; hidden fields carry them unslashed.
	 */
	public function test_request_values_are_unslashed_and_sanitised_on_the_way_in() {
		$_GET = array(
			'search_query' => "O\\'Brien <b>x</b>",
			'pg'           => '3',
			'order_dir'    => 'asc',
			'action'       => 'view',
			'filter_name'  => array( "a\\'b" ),
		);

		$report = $this->report( '', 'Request', array( 'search' => true ) );

		ob_start();
		$report->hidden_vars();
		$hidden = (string) ob_get_clean();

		$filter = $report->get_var( 'filter_name' );

		$_GET = array();

		$this->assertSame( "O'Brien x", $report->search_query );
		$this->assertSame( 3, $report->page );
		$this->assertSame( 'DESC', $report->order_dir );
		$this->assertSame( 'view', $report->action );
		$this->assertSame( array( "a'b" ), $filter );
		$this->assertStringContainsString( 'value="O&#039;Brien x"', $hidden );
		$this->assertStringNotContainsString( '\\', $hidden );
	}

	/**
	 * The page of a report for a request, and what its table prints.
	 *
	 * @param array  $request The query string's variables.
	 * @param string $sql     The report SQL.
	 * @param array  $options Options added to the defaults.
	 * @return array{0: Exports_Reports_Admin_UI, 1: string, 2: string} The screen, the statement it ran and the table.
	 */
	protected function page_for( array $request, $sql, $options = array() ) {
		$_GET = $request;

		$report    = $this->report( $sql, 'Paged', $options );
		$statement = $report->query->statement();

		ob_start();
		$report->get_data();
		$report->table();
		$table = (string) ob_get_clean();

		$_GET = array();

		return array( $report, $statement['sql'], $table );
	}

	/**
	 * A page size the screen does not offer never reaches the query: the screen's own size serves, for the
	 * LIMIT and for the page links alike. It was checked when the sizes were printed, after the query had
	 * run with whatever the address asked for.
	 */
	public function test_a_page_size_that_is_not_offered_falls_back_before_the_query_runs() {
		$sql = 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id';

		foreach ( array( '0', '7', '100000000', 'abc' ) as $asked ) {
			list( $report, $ran, $table ) = $this->page_for( array( 'limit' => $asked ), $sql );

			$this->assertSame( 25, $report->limit, 'limit=' . $asked );
			$this->assertStringContainsString( 'LIMIT 0,25', $ran, 'limit=' . $asked );
			$this->assertCount( 25, $report->data, 'limit=' . $asked );
			$this->assertStringContainsString( '<span class="total-pages">3</span>', $table, 'sixty rows at 25 a page, limit=' . $asked );
		}

		list( $report, $ran ) = $this->page_for( array( 'limit' => '50' ), $sql );

		$this->assertSame( 50, $report->limit, 'a size the screen offers is taken' );
		$this->assertStringContainsString( 'LIMIT 0,50', $ran );

		list( $unset ) = $this->page_for( array( 'limit' => '7' ), $sql, array( 'limit' => 0 ) );

		$this->assertSame( Exports_Reports_Admin_UI::DEFAULT_LIMIT, $unset->limit, 'a screen set up with no size takes the default' );
		$this->assertSame( 25, Exports_Reports_Admin_UI::DEFAULT_LIMIT );
	}

	/**
	 * The sizes a site offers through exports_reports_limit are the ones taken, and a screen set up with
	 * a size of its own offers that size too.
	 */
	public function test_the_page_sizes_offered_follow_the_filter_and_the_screens_own_size() {
		$sql   = 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id';
		$sizes = static function () {
			return array( 5, 15 );
		};

		add_filter( 'exports_reports_limit', $sizes );

		list( $fifteen )           = $this->page_for( array( 'limit' => '15' ), $sql, array( 'limit' => 5 ) );
		list( $ten )               = $this->page_for( array( 'limit' => '10' ), $sql, array( 'limit' => 5 ) );
		list( $own, $ran, $table ) = $this->page_for( array(), $sql, array( 'limit' => 12 ) );

		remove_filter( 'exports_reports_limit', $sizes );

		$this->assertSame( 15, $fifteen->limit );
		$this->assertSame( 5, $ten->limit, 'ten is not offered here' );
		$this->assertSame( 12, $own->limit, 'the size the screen was set up with' );
		$this->assertStringContainsString( 'LIMIT 0,12', $ran );
		$this->assertStringContainsString( '<span class="page-numbers current">12</span>', $table, 'and it is among the sizes shown' );
		$this->assertStringContainsString( '<span class="total-pages">5</span>', $table, 'sixty rows at 12 a page' );
	}

	/**
	 * Issue #32: 85 rows at 10 a page are 9 pages, with the links to reach them.
	 */
	public function test_eighty_five_rows_at_ten_a_page_are_nine_pages() {
		global $wpdb;

		for ( $i = 61; $i <= 85; $i++ ) {
			$wpdb->insert(
				self::$videos_table,
				array(
					'channel'   => 'delta',
					'title'     => 'Video ' . $i,
					'revenue'   => 1,
					'published' => '2021-06-01',
				)
			);
		}

		list( $report, $ran, $table ) = $this->page_for(
			array(
				'page'  => 'exports-reports-group-1',
				'limit' => '10',
				'pg'    => '3',
			),
			'SELECT id, title FROM ' . self::videos() . ' ORDER BY id'
		);

		$wpdb->query( 'DELETE FROM ' . self::videos() . ' WHERE id > 60' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the test's own table.

		$this->assertSame( 85, (int) $report->total );
		$this->assertStringContainsString( 'LIMIT 20,10', $ran );
		$this->assertCount( 10, $report->data );
		$this->assertSame( 2, substr_count( $table, '<span class="total-pages">9</span>' ), 'above and below the table' );
		$this->assertStringContainsString( 'pg=9', $table, 'the last page is linked' );

		// The links of the paging: each address escaped as an address, each title closed where it ends.
		$this->assertSame( 2, substr_count( $table, 'title="Go to the previous page" href="?page=exports-reports-group-1&#038;limit=10&#038;pg=2"' ) );
		$this->assertSame( 2, substr_count( $table, 'title="Go to the last page" href="?page=exports-reports-group-1&#038;limit=10&#038;pg=9"' ) );
	}

	/**
	 * debug=1 shows the statement and the database's errors to a user who manages reports, and to nobody
	 * else; wpdb shows errors afterwards as it did before.
	 */
	public function test_debug_shows_the_query_to_a_user_who_manages_reports_and_restores_the_error_display() {
		global $wpdb;

		$sql     = 'SELECT id, title FROM ' . self::videos() . ' WHERE id = 1';
		$manager = self::factory()->user->create( array( 'role' => 'editor' ) );

		get_userdata( $manager )->add_cap( 'exports_reports_settings' );

		$outputs = array();
		$states  = array();

		foreach ( array( 'manager' => $manager, 'subscriber' => self::factory()->user->create( array( 'role' => 'subscriber' ) ) ) as $who => $user ) {
			wp_set_current_user( $user );

			$_GET   = array( 'debug' => '1' );
			$report = $this->report( $sql, 'Debug' );
			$before = $wpdb->hide_errors();

			ob_start();
			$report->get_data();
			$outputs[ $who ] = (string) ob_get_clean();
			$states[ $who ]  = $wpdb->show_errors;

			$wpdb->show_errors( $before );

			$_GET = array();
		}

		wp_set_current_user( 0 );

		$this->assertStringContainsString( '<textarea', $outputs['manager'] );
		$this->assertStringContainsString( 'SQL_CALC_FOUND_ROWS id, title FROM', $outputs['manager'] );
		$this->assertSame( '', $outputs['subscriber'], 'nothing for a user who only views reports' );
		$this->assertFalse( $states['manager'], 'the error display is as it was before the query' );
		$this->assertFalse( $states['subscriber'] );
	}
}
