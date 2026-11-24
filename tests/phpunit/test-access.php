<?php
/**
 * Access: who reaches the report page, the REST routes, the exports, the downloads, the Manage screens,
 * Detect Fields and the Settings page; the capabilities on the administrator role; the display functions
 * a report definition may name.
 *
 * @package Exports_And_Reports
 */

class Tests_Access extends Exports_Reports_Test_Case {

	/**
	 * What a user may reach, in the order can() answers.
	 *
	 * @var string[]
	 */
	const DOORS = array( 'report page', 'REST data', 'REST export', 'ajax export', 'download', 'remove', 'Manage screens', 'Detect Fields', 'Settings' );

	/**
	 * A group that names the author role, with one report.
	 *
	 * @var int
	 */
	protected $group_id = 0;

	/**
	 * The report.
	 *
	 * @var int
	 */
	protected $report_id = 0;

	/**
	 * A group opened to authors and its report, for each test.
	 */
	protected function prepare() {
		parent::prepare();

		$this->group_id  = $this->insert_group(
			array(
				'name'        => 'Access',
				'role_access' => 'author',
			)
		);
		$this->report_id = $this->insert_report( $this->group_id, array( 'name' => 'Access report' ) );
	}

	/**
	 * Nobody stays logged in, the role has its capabilities and the export directory is empty again.
	 */
	protected function clean() {
		wp_set_current_user( 0 );
		exports_reports_add_role_capabilities();

		foreach ( $this->export_files() as $file ) {
			if ( exports_reports_is_export_file( basename( $file ) ) ) {
				wp_delete_file( $file );
			}
		}

		unset( $GLOBALS['plugin_page'] );

		parent::clean();
	}

	/**
	 * A user of one of the kinds the matrix names.
	 *
	 * @param string $kind The kind.
	 * @return int The user's id.
	 */
	protected function user_of_kind( $kind ) {
		$roles = array(
			'subscriber'          => 'subscriber',
			'role viewer'         => 'author',
			'view capability'     => 'subscriber',
			'settings capability' => 'subscriber',
			'administrator'       => 'administrator',
			'super admin'         => 'subscriber',
		);

		$user_id = self::factory()->user->create( array( 'role' => $roles[ $kind ] ) );

		if ( 'view capability' === $kind ) {
			get_userdata( $user_id )->add_cap( 'exports_reports_view' );
		} elseif ( 'settings capability' === $kind ) {
			get_userdata( $user_id )->add_cap( 'exports_reports_settings' );
		} elseif ( 'super admin' === $kind ) {
			grant_super_admin( $user_id );
		}

		return $user_id;
	}

	/**
	 * An export file of the report with its log row, as an export leaves them.
	 *
	 * @return string The file name.
	 */
	protected function logged_export() {
		global $wpdb;

		$file = 'access_report_' . wp_generate_password( 12, false ) . '.csv';

		file_put_contents( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file, "\"Id\"\n\"1\"\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- a local file of the test.

		$wpdb->insert(
			$wpdb->exports_reports_log,
			array(
				'report_id' => $this->report_id,
				'filename'  => $file,
				'created'   => gmdate( 'Y-m-d H:i:s' ),
			)
		);

		return $file;
	}

	/**
	 * Whether a screen function prints its screen for the current user, rather than refusing.
	 *
	 * @param callable $screen The screen function.
	 * @return array{0: bool, 1: string} Whether it was shown, and what it printed.
	 */
	protected function screen( $screen ) {
		$printed = '';

		// The report page flushes its buffer while the query runs; the callback keeps what it flushes.
		ob_start(
			static function ( $buffer ) use ( &$printed ) {
				$printed .= $buffer;

				return '';
			}
		);

		try {
			$shown = false !== call_user_func( $screen );
		} catch ( WPDieException $refused ) {
			$shown = false;
		}

		ob_end_clean();

		return array( $shown, $printed );
	}

	/**
	 * What the current user may reach, door by door (see DOORS).
	 *
	 * @return bool[]
	 */
	protected function can() {
		$route  = sprintf( '/exports-reports/v1/reports/%d', $this->report_id );
		$export = array(
			'exports_reports_export' => '1',
			'report'                 => (string) $this->report_id,
			'_wpnonce'               => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$GLOBALS['plugin_page'] = 'exports-reports-group-' . $this->group_id;

		list( $page ) = $this->screen( 'exports_reports_view' );

		unset( $GLOBALS['plugin_page'] );

		$data        = 200 === $this->rest_request( 'GET', $route . '/data' )->get_status();
		$rest_export = 200 === $this->rest_request( 'POST', $route . '/export', array( 'type' => 'csv' ) )->get_status();
		$ajax_export = $this->ajax( 'exports_reports_ajax_export', array_merge( $export, array( 'export_type' => 'csv' ) ) );
		$file        = $this->logged_export();
		$download    = exports_reports_user_can_download_export( $file );

		$this->ajax( 'exports_reports_ajax_export', array_merge( $export, array( 'remove_export' => $file ) ) );

		list( $groups )   = $this->screen( 'exports_reports_groups' );
		list( $reports )  = $this->screen( 'exports_reports_reports' );
		list( $settings ) = $this->screen( 'exports_reports_settings' );

		$detect = $this->ajax(
			'exports_reports_ajax_detect_columns',
			array(
				'sql'      => 'SELECT id FROM ' . self::videos(),
				'_wpnonce' => wp_create_nonce( 'exports-reports-detect-columns' ),
			)
		);

		return array(
			$page,
			$data,
			$rest_export,
			is_array( $ajax_export ) && true === $ajax_export['success'],
			$download,
			! file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file ),
			$groups && $reports,
			is_array( $detect ) && ( true === $detect['success'] || 'You are not allowed to edit reports.' !== $detect['data']['message'] ),
			$settings,
		);
	}

	/**
	 * Asserts what the current user may reach.
	 *
	 * @param string $kind   The kind of user, for the message.
	 * @param bool[] $expect What is expected, door by door.
	 */
	protected function assert_reach( $kind, array $expect ) {
		foreach ( $this->can() as $door => $reached ) {
			$this->assertSame( $expect[ $door ], $reached, $kind . ': ' . self::DOORS[ $door ] );
		}
	}

	/**
	 * The matrix: viewing through a role the group names or the view capability, managing through the
	 * settings capability, both through the administrator role's capabilities. Detect Fields runs SQL, so
	 * on a network it is for the super admins (the network case below).
	 */
	public function test_who_reaches_what() {
		$writes  = ! is_multisite();
		$viewer  = array( true, true, true, true, true, true, false, false, false );
		$nothing = array( false, false, false, false, false, false, false, false, false );
		$matrix  = array(
			'subscriber'          => $nothing,
			'role viewer'         => $viewer,
			'view capability'     => $viewer,
			'settings capability' => array( false, false, false, false, false, false, true, $writes, true ),
			'administrator'       => array( true, true, true, true, true, true, true, $writes, true ),
		);

		foreach ( $matrix as $kind => $expect ) {
			wp_set_current_user( $this->user_of_kind( $kind ) );

			$this->assert_reach( $kind, $expect );
		}

		wp_set_current_user( 0 );

		$this->assert_reach( 'nobody', $nothing );
	}

	/**
	 * On a single site the plugin's capabilities decide: an administrator whose role has lost them is
	 * refused like anyone else, though WordPress calls every administrator of a single site a super admin.
	 */
	public function test_an_administrator_without_the_capabilities_is_refused_on_a_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'A network has super admins of its own; see the network cases.' );
		}

		$administrator = $this->user_of_kind( 'administrator' );

		foreach ( exports_reports_capabilities() as $cap ) {
			get_role( 'administrator' )->remove_cap( $cap );
		}

		wp_set_current_user( $administrator );

		$this->assertTrue( is_super_admin(), 'WordPress calls the administrator of a single site a super admin' );
		$this->assertFalse( exports_reports_current_user_has_full_access() );
		$this->assertFalse( exports_reports_current_user_can_manage() );
		$this->assert_reach( 'administrator without the capabilities', array( false, false, false, false, false, false, false, false, false ) );

		// Activating the plugin again, or installing it afresh, puts them back.
		wp_set_current_user( 0 );
		delete_option( 'exports_reports_version' );
		exports_reports_install_upgrade();
		wp_set_current_user( $administrator );

		$this->assertTrue( current_user_can( 'exports_reports_full_access' ), 'a fresh install gives administrators access' );
		$this->assertTrue( exports_reports_current_user_can_manage() );
	}

	/**
	 * The Manage Reports screen as the current user gets it, for a request.
	 *
	 * @param array $get  The query string's variables.
	 * @param array $post The posted variables.
	 * @return string What the screen prints.
	 */
	protected function manage_reports( $get = array(), $post = array() ) {
		$_GET  = $get;
		$_POST = $post;

		list( , $printed ) = $this->screen( 'exports_reports_reports' );

		$_GET  = array();
		$_POST = array();

		return $printed;
	}

	/**
	 * The report's name, as stored.
	 *
	 * @return string
	 */
	protected function report_name() {
		global $wpdb;

		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT `name` FROM {$wpdb->exports_reports_reports} WHERE `id` = %d", $this->report_id ) );
	}

	/**
	 * On a network a report's SQL reaches every site's tables, so its super admins write the groups and
	 * the reports: a site's administrator views them, keeps the Settings page and gets the Manage screens
	 * as lists, without the forms, the saves or Detect Fields.
	 */
	public function test_on_a_network_super_admins_edit_groups_and_reports() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'Needs a network: run the suite with WP_MULTISITE=1.' );
		}

		$everything = array( true, true, true, true, true, true, true, true, true );
		$save       = array(
			'action' => 'edit',
			'do'     => 'save',
			'id'     => (string) $this->report_id,
		);

		wp_set_current_user( $this->user_of_kind( 'super admin' ) );

		$this->assertTrue( exports_reports_current_user_has_full_access() );
		$this->assertTrue( exports_reports_current_user_can_edit_reports() );
		$this->assert_reach( 'super admin', $everything );
		$this->assertStringContainsString( 'action=add', $this->manage_reports(), 'a super admin gets the Add New button' );

		wp_set_current_user( $this->user_of_kind( 'administrator' ) );

		$this->assertTrue( exports_reports_current_user_can_manage() );
		$this->assertFalse( exports_reports_current_user_can_edit_reports() );
		$this->assert_reach( 'site administrator', array( true, true, true, true, true, true, true, false, true ) );

		$list = $this->manage_reports();

		$this->assertStringContainsString( 'Access report', $list, 'the list is shown' );
		$this->assertStringContainsString( 'edited by the administrators of the network', $list );
		$this->assertStringNotContainsString( 'action=add', $list );
		$this->assertStringNotContainsString( 'action=edit', $list );
		$this->assertStringNotContainsString( 'action=duplicate', $list );
		$this->assertStringNotContainsString( 'action=delete', $list );

		$form = $this->manage_reports(
			$save,
			array(
				'_wpnonce'  => wp_create_nonce( 'exports-reports-form-save' ),
				'name'      => 'Renamed by a site administrator',
				'sql_query' => 'SELECT user_login, user_pass FROM wp_users',
			)
		);

		$this->assertSame( 'Access report', $this->report_name(), 'a posted form saves nothing' );
		$this->assertStringNotContainsString( 'name="sql_query"', $form, 'and the edit form is not shown' );

		// A network that leaves the definitions to its sites says so.
		add_filter( 'exports_reports_site_admins_edit_reports', '__return_true' );

		$this->assertTrue( exports_reports_current_user_can_edit_reports() );
		$this->assert_reach( 'site administrator of a network that leaves the reports to its sites', $everything );
		$this->assertStringContainsString( 'action=add', $this->manage_reports() );

		remove_filter( 'exports_reports_site_admins_edit_reports', '__return_true' );
	}

	public function test_administrators_hold_full_access_through_their_role_not_user_meta() {
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$subscriber    = self::factory()->user->create( array( 'role' => 'subscriber' ) );

		$this->assertTrue( get_role( 'administrator' )->has_cap( 'exports_reports_full_access' ), 'the role carries the capability' );
		$this->assertTrue( user_can( $administrator, 'exports_reports_full_access' ) );
		$this->assertFalse( user_can( $subscriber, 'exports_reports_full_access' ) );
		$this->assertFalse( isset( get_userdata( $administrator )->caps['exports_reports_full_access'] ), 'the capability is not in the user meta' );
	}

	/**
	 * A function a plugin defines is called; a function PHP itself defines is not, and the column drops it.
	 */
	public function test_display_functions_are_limited_to_plugin_functions() {
		$report = $this->report(
			'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1',
			'Internal callback',
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

		$this->assertTrue( $report->is_allowed_callback( 'exports_reports_tests_shout' ) );
		$this->assertFalse( $report->is_allowed_callback( 'strtoupper' ) );
		$this->assertFalse( $report->is_allowed_callback( 'no_such_function_er' ) );
		$this->assertFalse( $report->columns['channel']['custom_display'] );
	}
}
