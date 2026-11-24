<?php
/**
 * The REST API: report data and exports for a report, with the token or as a user who may view it.
 *
 * @package Exports_And_Reports
 */

class Tests_REST_API extends Exports_Reports_Test_Case {

	/**
	 * The report of the test, in a group of its own.
	 *
	 * @var int
	 */
	protected $report_id = 0;

	/**
	 * The report's route base.
	 *
	 * @var string
	 */
	protected $route = '';

	/**
	 * A user the report is not shown to.
	 *
	 * @var int
	 */
	protected $subscriber = 0;

	/**
	 * A user who may view every report.
	 *
	 * @var int
	 */
	protected $administrator = 0;

	/**
	 * A group, a report on the seeded table, the token and two users, for each test.
	 */
	protected function prepare() {
		parent::prepare();

		global $wpdb;

		$now = current_time( 'mysql' );

		$wpdb->insert(
			$wpdb->exports_reports_groups,
			array(
				'name'        => 'API test',
				'disabled'    => 0,
				'role_access' => '',
				'weight'      => 0,
				'created'     => $now,
				'updated'     => $now,
			)
		);

		$group_id = (int) $wpdb->insert_id;

		$wpdb->insert(
			$wpdb->exports_reports_reports,
			array(
				'name'             => 'API report',
				'group'            => $group_id,
				'disabled'         => 0,
				'disable_export'   => 0,
				'default_none'     => 0,
				'role_access'      => '',
				'sql_query'        => 'SELECT id, channel, title, revenue FROM ' . self::videos() . ' ORDER BY id LIMIT %%LIMIT%%',
				'sql_query_count'  => '',
				'page_orientation' => 'P',
				'field_data'       => '',
				'weight'           => 0,
				'created'          => $now,
				'updated'          => $now,
			)
		);

		$this->report_id = (int) $wpdb->insert_id;
		$this->route     = sprintf( '/exports-reports/v1/reports/%d', $this->report_id );

		exports_reports_set_token( 'er-test-token' );

		$this->subscriber    = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Writes a CSV export through the route, with the bearer token, and returns [ response, body, file ].
	 *
	 * @return array{0: WP_REST_Response, 1: array, 2: string}
	 */
	protected function rest_export_csv() {
		$response = $this->rest_request( 'POST', $this->route . '/export', array( 'type' => 'csv' ), array( 'Authorization' => 'Bearer er-test-token' ) );
		$body     = $response->get_data();
		$file     = is_array( $body ) && isset( $body['file'] ) ? (string) $body['file'] : '';

		return array( $response, $body, $file );
	}

	public function test_rest_refuses_the_report_without_the_token_or_a_user() {
		$anonymous     = $this->rest_request( 'GET', $this->route . '/data' );
		$wrong_token   = $this->rest_request( 'GET', $this->route . '/data', array( 'token' => 'nope' ) );
		$unknown       = $this->rest_request( 'GET', '/exports-reports/v1/reports/999999/data' );
		$unknown_token = $this->rest_request( 'GET', '/exports-reports/v1/reports/999999/data', array( 'token' => 'er-test-token' ) );

		$this->assertSame( 401, $anonymous->get_status() );
		$this->assertSame( 401, $wrong_token->get_status() );
		$this->assertSame( 401, $unknown->get_status(), 'a caller without a credential learns nothing about which ids exist' );
		$this->assertSame( 404, $unknown_token->get_status() );
	}

	public function test_rest_full_data_with_the_token_parameter() {
		$response = $this->rest_request(
			'GET',
			$this->route . '/data',
			array(
				'token' => 'er-test-token',
				'full'  => 1,
			)
		);
		$body     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertArrayHasKey( 'rows', $body );
		$this->assertCount( self::$video_rows, $body['rows'] );
		$this->assertSame( self::$video_rows, $body['total'] );
	}

	public function test_rest_paged_data_with_the_bearer_token() {
		$response = $this->rest_request(
			'GET',
			$this->route . '/data',
			array(
				'pg'    => 2,
				'limit' => 25,
			),
			array( 'Authorization' => 'Bearer er-test-token' )
		);
		$body     = $response->get_data();

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( isset( $body['rows'][0]['id'] ), 'rows came back' );
		$this->assertCount( 25, $body['rows'] );
		$this->assertSame( 26, (int) $body['rows'][0]['id'] );
		$this->assertSame( self::$video_rows, $body['total'] );
		$this->assertSame( 2, $body['page'] );
	}

	public function test_rest_follows_the_report_access_rules_for_users() {
		wp_set_current_user( $this->subscriber );
		$as_subscriber = $this->rest_request( 'GET', $this->route . '/data' );

		wp_set_current_user( $this->administrator );
		$as_administrator = $this->rest_request( 'GET', $this->route . '/data', array( 'full' => 1 ) );

		wp_set_current_user( 0 );

		$this->assertSame( 403, $as_subscriber->get_status() );
		$this->assertSame( 200, $as_administrator->get_status() );
	}

	public function test_rest_export_writes_the_file_logs_it_and_names_its_download_url() {
		global $wpdb;

		list( $response, $body, $file ) = $this->rest_export_csv();

		$exists = '' !== $file && file_exists( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file );
		$logged = '' === $file ? 0 : (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->exports_reports_log} WHERE report_id = %d AND filename = %s", $this->report_id, $file ) );

		if ( '' !== $file ) {
			wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file );
		}

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $body ) );
		$this->assertNotSame( '', $file );
		$this->assertStringEndsWith( '.csv', $file );
		$this->assertTrue( $exists, 'the file was written' );
		$this->assertSame( 1, $logged, 'the export is logged against its report' );
		$this->assertArrayHasKey( 'url', $body );
		$this->assertStringContainsString( $this->route . '/exports/', $body['url'] );
	}

	/**
	 * The serving filter streams the file itself after closing every output buffer, so the test checks its
	 * decision on the two kinds of response and reads the file through the function it calls.
	 */
	public function test_rest_download_names_the_logged_export_for_serving_and_refuses_other_names() {
		list( , , $file ) = $this->rest_export_csv();

		$this->assertNotSame( '', $file, 'the export was written' );

		$download = $this->rest_request( 'GET', $this->route . '/exports/' . $file, array( 'token' => 'er-test-token' ) );
		$body     = $download->get_data();
		$data     = $this->rest_request(
			'GET',
			$this->route . '/data',
			array(
				'token' => 'er-test-token',
				'full'  => 1,
			)
		);
		$passed   = exports_reports_rest_serve_file( false, $data, new WP_REST_Request( 'GET', $this->route . '/data' ) );
		$other    = $this->rest_request( 'GET', $this->route . '/exports/other_report.csv', array( 'token' => 'er-test-token' ) );

		list( $sent_ok, $sent ) = $this->send_export_file( $file );

		$on_disk = (string) file_get_contents( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file );

		$this->assertSame( 200, $download->get_status() );
		$this->assertTrue( isset( $body['download'], $body['file'] ) );
		$this->assertTrue( $body['download'] );
		$this->assertSame( $file, $body['file'] );
		$this->assertFalse( $passed, 'a data response is left alone' );
		$this->assertTrue( $sent_ok );
		$this->assertNotSame( '', $sent );
		$this->assertSame( $on_disk, $sent );
		$this->assertSame( 404, $other->get_status() );
	}

	/**
	 * The admin-ajax download serves a logged file to a user who may view its report.
	 */
	public function test_download_authorisation_follows_the_log_row_and_the_report_access_rules() {
		list( , , $file ) = $this->rest_export_csv();

		$this->assertNotSame( '', $file, 'the export was written' );

		wp_set_current_user( $this->subscriber );
		$as_subscriber = exports_reports_user_can_download_export( $file );

		wp_set_current_user( $this->administrator );
		$as_administrator = exports_reports_user_can_download_export( $file );
		$unknown          = exports_reports_user_can_download_export( 'other_report.csv' );
		$foreign          = exports_reports_user_can_download_export( 'index.php' );

		wp_set_current_user( 0 );
		$anonymous = exports_reports_user_can_download_export( $file );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $file );

		$this->assertFalse( $as_subscriber );
		$this->assertTrue( $as_administrator );
		$this->assertFalse( $unknown );
		$this->assertFalse( $foreign );
		$this->assertFalse( $anonymous );
	}

	public function test_rest_hides_a_disabled_report() {
		global $wpdb;

		$wpdb->update( $wpdb->exports_reports_reports, array( 'disabled' => 1 ), array( 'id' => $this->report_id ) );

		$response = $this->rest_request( 'GET', $this->route . '/data', array( 'token' => 'er-test-token' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * The Settings page's buttons: Turn Token Access Off removes the hash and closes the routes to the old
	 * token; Generate a Token stores a hash and prints the token once, and that token opens the routes.
	 */
	public function test_the_settings_page_turns_token_access_off_and_generates_a_token_it_shows_once() {
		wp_set_current_user( $this->administrator );

		$_POST    = array( 'disable_token' => '1' );
		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'exports-reports-settings' ) );

		ob_start();
		exports_reports_settings();
		$off_html = (string) ob_get_clean();

		$hash_after_off = get_option( 'exports_reports_token_hash' );

		$_POST = array( 'generate_token' => '1' );

		ob_start();
		exports_reports_settings();
		$on_html = (string) ob_get_clean();

		$_POST    = array();
		$_REQUEST = array();

		$this->assertTrue( preg_match( '#<code>([A-Za-z0-9]{32})</code>#', $on_html, $shown ) > 0, 'the new token is printed once' );
		$this->assertStringNotContainsString( $shown[1], get_option( 'exports_reports_token_hash' ), 'the option holds a hash, not the token' );
		$this->assertStringContainsString( 'Token access is off', $off_html );
		$this->assertStringContainsString( 'Token access is on', $on_html );
		$this->assertStringContainsString( 'name="disable_token"', $on_html );
		$this->assertStringNotContainsString( 'name="disable_token"', $off_html );

		wp_set_current_user( 0 );

		$old_token = $this->rest_request( 'GET', $this->route . '/data', array( 'token' => 'er-test-token' ) );
		$new_token = $this->rest_request( 'GET', $this->route . '/data', array(), array( 'Authorization' => 'Bearer ' . $shown[1] ) );

		$this->assertFalse( $hash_after_off, 'turning token access off removes the hash' );
		$this->assertSame( 401, $old_token->get_status(), 'the old token no longer opens the report' );
		$this->assertSame( 200, $new_token->get_status(), 'the new one does' );
	}

	/**
	 * A token of the administrator's own, typed into the field: stored as a hash and shown once. The field
	 * saved empty, as it always is since the token cannot be shown in it, changes nothing.
	 */
	public function test_a_token_of_ones_own_is_hashed_and_an_empty_field_changes_nothing() {
		wp_set_current_user( $this->administrator );

		$_REQUEST = array( '_wpnonce' => wp_create_nonce( 'exports-reports-settings' ) );
		$_POST    = array( 'cronjob_token' => 'My Own Token' );

		ob_start();
		exports_reports_settings();
		$html = (string) ob_get_clean();

		$_POST = array(
			'cronjob_token'      => '',
			'export_formats_set' => '1',
			'export_formats'     => array( 'csv' ),
		);

		ob_start();
		exports_reports_settings();
		ob_end_clean();

		$_POST    = array();
		$_REQUEST = array();

		$this->assertStringContainsString( '<code>my-own-token</code>', $html, 'the token is shown once, as stored (a slug, as before 1.2.0)' );
		$this->assertTrue( exports_reports_token_matches( 'my-own-token' ) );
		$this->assertFalse( exports_reports_token_matches( 'er-test-token' ) );
		$this->assertTrue( exports_reports_token_matches( 'my-own-token' ), 'the empty field left the token alone' );
		$this->assertFalse( exports_reports_token_matches( '' ) );
	}

	/**
	 * The token a version before 1.2.0 stored in the clear opens the routes on the first request after the
	 * update, before any admin visit, and is replaced by its hash then.
	 */
	public function test_a_token_stored_in_the_clear_by_an_earlier_version_works_once_and_becomes_a_hash() {
		delete_option( 'exports_reports_token_hash' );
		update_option( 'exports_reports_token', 'legacy-token' );

		$response = $this->rest_request( 'GET', $this->route . '/data', array( 'token' => 'legacy-token' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertFalse( get_option( 'exports_reports_token' ), 'the token in the clear is gone' );
		$this->assertNotEmpty( get_option( 'exports_reports_token_hash' ) );
		$this->assertTrue( wp_check_password( 'legacy-token', get_option( 'exports_reports_token_hash' ) ) );

		$again = $this->rest_request( 'GET', $this->route . '/data', array( 'token' => 'legacy-token' ) );

		$this->assertSame( 200, $again->get_status(), 'and the token goes on working' );
	}
}
