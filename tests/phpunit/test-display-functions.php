<?php
/**
 * The display functions a report definition may name: the ones the site lists, and the ones its
 * definitions named before the list existed.
 *
 * @package Exports_And_Reports
 */

class Tests_Display_Functions extends Exports_Reports_Test_Case {

	/**
	 * Nobody stays logged in.
	 */
	protected function clean() {
		wp_set_current_user( 0 );

		parent::clean();
	}

	/**
	 * A report whose channel column names a display function, and the channel its first row shows.
	 *
	 * @param string $callback The function the field names.
	 * @return array{0: Exports_Reports_Admin_UI, 1: string} The report and the exported value.
	 */
	protected function channel_through( $callback ) {
		$report = $this->report(
			'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1',
			'Display function',
			array(
				'columns' => array(
					'id'      => array( 'label' => 'Id' ),
					'channel' => array(
						'label'          => 'Channel',
						'custom_display' => $callback,
					),
				),
			)
		);

		ob_start();
		$rows = $report->get_data( true );
		ob_end_clean();

		$row = $report->export_row( $rows[0], $report->exported_columns() );

		return array( $report, (string) $row['channel'] );
	}

	/**
	 * A function is called when the site lists it. A function nothing lists is not, whoever defines it:
	 * one of another plugin, one of WordPress that takes a file's path, one of PHP. The column drops it
	 * and shows the value.
	 */
	public function test_a_display_function_is_called_only_when_the_site_lists_it() {
		list( $listed, $shouted ) = $this->channel_through( 'exports_reports_tests_shout' );

		$this->assertTrue( $listed->is_allowed_callback( 'exports_reports_tests_shout' ) );
		$this->assertTrue( $listed->is_allowed_callback( 'Exports_Reports_Tests_Shout' ), 'function names are not case sensitive' );
		$this->assertSame( 'BETA', $shouted );

		foreach ( array( 'exports_reports_tests_unlisted', 'wp_delete_file', 'strtoupper', 'no_such_function_er' ) as $callback ) {
			list( $report, $value ) = $this->channel_through( $callback );

			$this->assertFalse( $report->is_allowed_callback( $callback ), $callback );
			$this->assertFalse( $report->columns['channel']['custom_display'], $callback . ': the column drops it' );
			$this->assertSame( 'beta', $value, $callback . ': the value is shown as it is' );
		}
	}

	/**
	 * Listing a function of PHP's own does not make it one the plugin calls; the filter that decides for
	 * one name at a time still can (Tests_Hooks covers it).
	 */
	public function test_listing_a_php_function_does_not_admit_it() {
		$list = static function ( $callbacks ) {
			$callbacks[] = 'strtoupper';

			return $callbacks;
		};

		add_filter( 'exports_reports_display_callbacks', $list );

		$listed = exports_reports_display_callbacks();

		list( $report, $value ) = $this->channel_through( 'strtoupper' );

		remove_filter( 'exports_reports_display_callbacks', $list );

		$this->assertContains( 'strtoupper', $listed );
		$this->assertFalse( $report->is_allowed_callback( 'strtoupper' ) );
		$this->assertSame( 'beta', $value );
	}

	/**
	 * The helper of a {@field,helper} tag follows the same list.
	 */
	public function test_a_template_helper_is_called_only_when_the_site_lists_it() {
		$report = $this->report( '', 'Template' );
		$row    = array( 'title' => 'quiet' );

		$this->assertSame( 'a QUIET b', $report->parse_template_string( 'a {@title,exports_reports_tests_shout} b', $row ) );
		$this->assertSame( 'a quiet b', $report->parse_template_string( 'a {@title,exports_reports_tests_unlisted} b', $row ) );
		$this->assertSame( 'a quiet b', $report->parse_template_string( 'a {@title,wp_delete_file} b', $row ) );
	}

	/**
	 * The functions the stored definitions name when the list is first asked for go on it, once, so the
	 * reports of a site that upgrades keep working; a name typed into a field afterwards does not.
	 */
	public function test_the_functions_stored_definitions_name_are_recorded_once() {
		remove_filter( 'exports_reports_display_callbacks', array( $this, 'list_display_functions' ) );

		$fields = static function ( $callback ) {
			return wp_json_encode(
				array(
					array(
						'name'           => 'channel',
						'custom_display' => $callback,
					),
					array(
						'name'           => 'title',
						'custom_display' => '',
					),
				)
			);
		};

		$group = $this->insert_group();

		$this->insert_report( $group, array( 'field_data' => $fields( 'exports_reports_tests_shout' ) ) );
		$this->insert_report( $group, array( 'field_data' => $fields( 'a_function_of_a_plugin_that_is_off' ) ) );
		$this->insert_report( $group, array( 'field_data' => $fields( 'exports_reports_tests_shout' ) ) );

		delete_option( 'exports_reports_display_callbacks' );
		delete_option( 'exports_reports_display_callbacks_notice' );

		$recorded = array( 'a_function_of_a_plugin_that_is_off', 'exports_reports_tests_shout' );

		$this->assertSame( $recorded, exports_reports_display_callbacks() );
		$this->assertSame( $recorded, get_option( 'exports_reports_display_callbacks' ) );
		$this->assertSame( $recorded, get_option( 'exports_reports_display_callbacks_notice' ), 'the notice has the names to show' );

		list( , $shouted ) = $this->channel_through( 'exports_reports_tests_shout' );

		$this->assertSame( 'BETA', $shouted, 'a recorded function is called as before' );

		$this->insert_report( $group, array( 'field_data' => $fields( 'exports_reports_tests_unlisted' ) ) );

		$this->assertSame( $recorded, exports_reports_display_callbacks(), 'a name a field takes afterwards is not recorded' );

		list( , $unlisted ) = $this->channel_through( 'exports_reports_tests_unlisted' );

		$this->assertSame( 'beta', $unlisted );
	}

	/**
	 * A site whose definitions name no function records an empty list and has nothing to announce.
	 */
	public function test_nothing_is_announced_when_no_definition_names_a_function() {
		delete_option( 'exports_reports_display_callbacks' );
		delete_option( 'exports_reports_display_callbacks_notice' );

		remove_filter( 'exports_reports_display_callbacks', array( $this, 'list_display_functions' ) );

		$this->assertSame( array(), exports_reports_display_callbacks() );
		$this->assertSame( array(), get_option( 'exports_reports_display_callbacks' ) );
		$this->assertFalse( get_option( 'exports_reports_display_callbacks_notice' ) );
	}

	/**
	 * The notice names the recorded functions to a user who manages reports, on every admin page until
	 * one of them dismisses it; nobody else sees it.
	 */
	public function test_the_notice_names_the_recorded_functions_until_a_manager_dismisses_it() {
		update_option( 'exports_reports_display_callbacks_notice', array( 'exports_reports_tests_shout', '<b>odd</b>' ) );

		$notice = static function () {
			ob_start();
			exports_reports_display_callbacks_notice();

			return (string) ob_get_clean();
		};

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( '', $notice() );

		$_GET = array(
			'exports_reports_dismiss' => 'display-functions',
			'_wpnonce'                => wp_create_nonce( 'exports-reports-dismiss' ),
		);

		exports_reports_dismiss_notice();

		$this->assertNotFalse( get_option( 'exports_reports_display_callbacks_notice' ), 'a user who does not manage reports dismisses nothing' );

		$_GET = array();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$shown = $notice();

		$this->assertStringContainsString( '<code>exports_reports_tests_shout</code>', $shown );
		$this->assertStringContainsString( '<code>&lt;b&gt;odd&lt;/b&gt;</code>', $shown, 'the names are escaped' );
		$this->assertStringContainsString( 'exports_reports_display_callbacks', $shown, 'the filter that lists a function is named' );
		$this->assertSame( 1, preg_match( '/href="([^"]*exports_reports_dismiss=display-functions[^"]*)"/', $shown, $link ) );

		parse_str( (string) wp_parse_url( html_entity_decode( $link[1] ), PHP_URL_QUERY ), $_GET );

		$_GET['_wpnonce'] = 'not-the-nonce';

		exports_reports_dismiss_notice();

		$this->assertNotFalse( get_option( 'exports_reports_display_callbacks_notice' ), 'the link needs its nonce' );

		parse_str( (string) wp_parse_url( html_entity_decode( $link[1] ), PHP_URL_QUERY ), $_GET );

		exports_reports_dismiss_notice();

		$_GET = array();

		$this->assertFalse( get_option( 'exports_reports_display_callbacks_notice' ) );
		$this->assertSame( '', $notice() );
	}

	/**
	 * The field editor says so under a field whose display function is not on the list.
	 */
	public function test_the_field_editor_flags_a_display_function_that_is_not_listed() {
		$editor = function ( $callback ) {
			$screen      = $this->report( '', 'Editor' );
			$screen->row = array(
				'field_data' => wp_json_encode(
					array(
						array(
							'name'           => 'channel',
							'custom_display' => $callback,
						),
					)
				),
			);

			ob_start();
			exports_reports_report_field( 'field_data', array(), $screen );

			return (string) ob_get_clean();
		};

		$flagged = $editor( 'exports_reports_tests_unlisted' );

		$this->assertStringContainsString( 'er-fields__unlisted', $flagged );
		$this->assertStringContainsString( '<code>exports_reports_tests_unlisted</code>', $flagged );
		$this->assertStringNotContainsString( 'er-fields__unlisted', $editor( 'exports_reports_tests_shout' ) );
		$this->assertStringNotContainsString( 'er-fields__unlisted', $editor( '' ) );
	}
}
