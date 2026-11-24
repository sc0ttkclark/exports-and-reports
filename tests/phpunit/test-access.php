<?php
/**
 * Access: the capabilities on the administrator role, and the display functions a report definition may name.
 *
 * @package Exports_And_Reports
 */

class Tests_Access extends Exports_Reports_Test_Case {

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
