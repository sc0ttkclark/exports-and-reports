<?php
/**
 * The export formats a site and a report offer, how the screen, the REST route and the export form follow
 * them, the Settings page that saves them, the column the upgrade adds, and the refresh of the export
 * directory's protection files.
 *
 * @package Exports_And_Reports
 */

class Tests_Export_Formats extends Exports_Reports_Test_Case {

	protected $group_id = 0;

	protected $report_id = 0;

	protected $administrator = 0;

	protected function prepare() {
		parent::prepare();

		global $wpdb;

		$now = current_time( 'mysql' );

		$wpdb->insert(
			$wpdb->exports_reports_groups,
			array(
				'name'        => 'Formats test',
				'disabled'    => 0,
				'role_access' => '',
				'weight'      => 0,
				'created'     => $now,
				'updated'     => $now,
			)
		);

		$this->group_id = (int) $wpdb->insert_id;

		$wpdb->insert(
			$wpdb->exports_reports_reports,
			array(
				'name'             => 'Formats report',
				'group'            => $this->group_id,
				'disabled'         => 0,
				'disable_export'   => 0,
				'default_none'     => 0,
				'role_access'      => '',
				'sql_query'        => 'SELECT id, channel, title FROM ' . self::videos() . ' ORDER BY id',
				'sql_query_count'  => '',
				'page_orientation' => 'L',
				'field_data'       => '[]',
				'weight'           => 0,
				'created'          => $now,
				'updated'          => $now,
			)
		);

		$this->report_id     = (int) $wpdb->insert_id;
		$this->administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );

		delete_option( 'exports_reports_export_formats' );
		delete_option( 'exports_reports_export_formats_off' );
	}

	protected function clean() {
		global $wpdb;

		$wpdb->delete( $wpdb->exports_reports_reports, array( 'id' => $this->report_id ) );
		$wpdb->delete( $wpdb->exports_reports_groups, array( 'id' => $this->group_id ) );

		delete_option( 'exports_reports_export_formats' );
		delete_option( 'exports_reports_export_formats_off' );

		$_POST = array();
		$_GET  = array();

		parent::clean();
	}

	/**
	 * The report row, fresh from the table.
	 *
	 * @return object
	 */
	protected function report_row() {
		return exports_reports_get_report( $this->report_id );
	}

	public function test_the_site_offers_every_format_but_the_ones_turned_off() {
		$this->assertSame( array( 'csv', 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' ), exports_reports_site_export_formats() );

		update_option( 'exports_reports_export_formats_off', array( 'pdf', 'xlsx', 'bogus' ) );

		$this->assertSame( array( 'csv', 'tsv', 'pipe', 'xml', 'json' ), exports_reports_site_export_formats(), 'in the plugin\'s order; a type the plugin no longer has is ignored' );

		update_option( 'exports_reports_export_formats_off', array( 'csv', 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' ) );

		$this->assertSame( array(), exports_reports_site_export_formats(), 'every format off turns the buttons off' );
	}

	/**
	 * The setting 1.1.0 kept named the formats that were on; the upgrade turns it into the formats that are
	 * off and removes it, so a format added later is on.
	 */
	public function test_the_upgrade_converts_the_formats_setting_of_1_1_0() {
		update_option( 'exports_reports_export_formats', array( 'csv', 'xlsx' ) );
		update_option( 'exports_reports_version', '1.1.0' );

		exports_reports_install_upgrade();

		$this->assertSame( array( 'tsv', 'pipe', 'xml', 'json', 'pdf' ), get_option( 'exports_reports_export_formats_off' ) );
		$this->assertFalse( get_option( 'exports_reports_export_formats' ) );
		$this->assertSame( array( 'csv', 'xlsx' ), exports_reports_site_export_formats() );
		$this->assertSame( EXPORTS_REPORTS_VERSION, get_option( 'exports_reports_version' ) );
	}

	public function test_a_report_narrows_the_site_formats_and_cannot_widen_them() {
		global $wpdb;

		$this->assertSame( array( 'csv', 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' ), exports_reports_report_export_formats( $this->report_row() ) );

		$wpdb->update( $wpdb->exports_reports_reports, array( 'export_formats' => 'pdf,csv' ), array( 'id' => $this->report_id ) );

		$this->assertSame( array( 'csv', 'pdf' ), exports_reports_report_export_formats( $this->report_row() ) );

		update_option( 'exports_reports_export_formats_off', array( 'pdf' ) );

		$this->assertSame( array( 'csv' ), exports_reports_report_export_formats( $this->report_row() ), 'the report cannot offer what the site turned off' );

		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

		$this->assertSame( array( 'csv' ), ( new Exports_Reports_Admin_UI( exports_reports_report_options( $this->report_row() ) ) )->export_formats );
	}

	public function test_the_screen_refuses_a_format_it_does_not_offer_and_writes_one_it_does() {
		$admin = $this->report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Narrowed', array( 'export_formats' => array( 'csv', 'tsv' ) ) );

		$this->assertSame( array( 'csv', 'tsv' ), $admin->allowed_export_types() );
		$this->assertSame( array( 'csv' => 'CSV', 'tsv' => 'TSV' ), $admin->export_formats_offered() );

		$admin->action      = 'export';
		$admin->export_type = 'pdf';

		list( , $output ) = $this->export_from_link( $admin );

		$this->assertStringContainsString( 'not offered for this report', $output );
		$this->assertFalse( $admin->exported_file );

		$admin->export_type = 'csv';

		list( , $output ) = $this->export_from_link( $admin );

		$this->assertStringContainsString( 'Your export is ready', $output );
		$this->assertStringEndsWith( '.csv', (string) $admin->exported_file );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $admin->exported_file );
	}

	public function test_the_rest_export_refuses_a_format_the_report_does_not_offer() {
		wp_set_current_user( $this->administrator );
		update_option( 'exports_reports_export_formats_off', array( 'tsv', 'pipe', 'xlsx', 'xml', 'json', 'pdf' ) );

		$response = $this->rest_request( 'GET', '/exports-reports/v1/reports/' . $this->report_id . '/export', array( 'type' => 'pdf' ) );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'exports_reports_format_not_offered', $response->get_data()['code'] );

		$response = $this->rest_request( 'GET', '/exports-reports/v1/reports/' . $this->report_id . '/export', array( 'type' => 'csv' ) );

		$this->assertSame( 200, $response->get_status() );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $response->get_data()['file'] );
	}

	/**
	 * The list screen: the export form names the report and keeps the search and the sort, the buttons
	 * follow the offered formats and submit that form.
	 */
	public function test_the_export_buttons_follow_the_offered_formats_and_submit_the_export_form() {
		$_GET['search_query'] = 'Video';
		$_GET['order']        = 'id';
		$_GET['order_dir']    = 'DESC';
		$_GET['pg']           = '2';

		$admin = $this->report(
			'SELECT id, title FROM ' . self::videos() . ' ORDER BY id',
			'Buttons',
			array(
				'export_formats' => array( 'csv', 'xlsx' ),
				'report_id'      => 42,
			)
		);

		ob_start();
		$admin->manage();
		$html = (string) ob_get_clean();

		$this->assertSame( 1, preg_match( '/<form id="exports-reports-export"[^>]*action="([^"]*)"/', $html, $form ) );
		$this->assertStringContainsString( 'report=42', $form[1] );
		$this->assertStringContainsString( 'search_query=Video', $form[1] );
		$this->assertStringContainsString( 'order_dir=DESC', $form[1] );
		$this->assertStringNotContainsString( 'pg=', $form[1] );
		$this->assertStringContainsString( 'data-ajax="', $html );
		$this->assertStringContainsString( 'action=exports_reports_export_report', $html );
		$this->assertStringContainsString( 'name="exports_reports_export" value="1"', $html );
		$this->assertStringContainsString( '<form id="exports-reports-remove-export"', $html, 'the form the removal button under an export\'s message submits' );

		preg_match_all( '/<button type="submit" class="button" form="exports-reports-export" name="export_type" value="([a-z]+)">/', $html, $buttons );

		$this->assertSame( array( 'csv', 'xlsx' ), $buttons[1] );
		$this->assertStringNotContainsString( 'action=export&amp;export_type', $html, 'no export link in an address' );
	}

	/**
	 * The search box shows when something can be searched: a column the search reads, or a filter. A report
	 * whose fields are dates or marked unsearchable, with no filter, gets the table's form without it (the page
	 * field still needs the form, with the request's values as hidden fields). The note printed while the
	 * report loaded is removed by a line of script right after the report.
	 */
	public function test_the_search_box_shows_only_when_something_can_be_searched() {
		$sql = 'SELECT id, title, published FROM ' . self::videos() . ' ORDER BY id';

		$_GET = array(
			'page'   => 'exports-reports-group-1',
			'report' => '7',
		);

		$render = function ( $columns ) use ( $sql ) {
			$admin = $this->report( $sql, 'Search box', array( 'columns' => $columns, 'search' => true ) );

			ob_start();
			$admin->manage();

			return (string) ob_get_clean();
		};

		$nothing  = $render(
			array(
				'id'        => array( 'search' => false, 'type' => 'number' ),
				'published' => array( 'type' => 'date' ),
			)
		);
		$a_text   = $render( array( 'id' => array( 'search' => false ), 'title' => array( 'type' => 'text' ) ) );
		$a_filter = $render( array( 'id' => array( 'search' => false ), 'published' => array( 'type' => 'date', 'filter' => true ) ) );
		$all      = $render( array() );

		$_GET = array();

		$this->assertStringNotContainsString( 'class="search-box exports-reports-filters"', $nothing, 'dates and an unsearchable column: no search box' );
		$this->assertStringContainsString( '<form id="posts-filter"', $nothing, 'the table keeps its form' );
		$this->assertStringContainsString( 'name="report" value="7"', $nothing, 'with the request carried as hidden fields' );
		$this->assertStringContainsString( 'class="search-box exports-reports-filters"', $a_text, 'a text column: the search box' );
		$this->assertStringContainsString( 'class="search-box exports-reports-filters"', $a_filter, 'a filter: the search box, with the filter' );
		$this->assertStringContainsString( 'admin_ui_filter_published_start', $a_filter );
		$this->assertStringContainsString( 'class="search-box exports-reports-filters"', $all, 'no field definitions: every column is searched' );
		$this->assertSame( 1, preg_match( '#<script[^>]*>.*?querySelectorAll\( "\.exports-reports-loading.*?</script>#s', $nothing ), 'the loading note is removed by the script after the report' );
	}

	public function test_a_posted_export_form_runs_the_export_with_its_nonce() {
		$_POST = array(
			'exports_reports_export' => '1',
			'export_type'            => 'tsv',
			'_wpnonce'               => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$admin = $this->report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Posted' );

		$this->assertSame( 'export', $admin->action );
		$this->assertSame( 'tsv', $admin->export_type );

		ob_start();
		$admin->export();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Your export is ready', $output );
		$this->assertStringEndsWith( '.tsv', (string) $admin->exported_file );

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $admin->exported_file );

		$_POST['_wpnonce'] = 'stale';

		$admin = $this->report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Posted stale' );

		ob_start();
		$admin->export();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'The export link has expired', $output );
		$this->assertFalse( $admin->exported_file );
	}

	/**
	 * A link from before 1.1.0 (the action and the type in the address) opened again writes nothing: the
	 * screen shows the report with a note. The removal link, without a type, still works through the address.
	 */
	public function test_an_export_asked_for_through_the_address_is_not_written_again() {
		$_GET = array(
			'action'      => 'export',
			'export_type' => 'csv',
			'_wpnonce'    => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$before = count( $this->export_files() );
		$admin  = $this->report( 'SELECT id, title FROM ' . self::videos() . ' ORDER BY id', 'Old link' );

		$this->assertSame( 'export', $admin->action );
		$this->assertFalse( $admin->export_type );
		$this->assertTrue( $admin->export_from_address );

		ob_start();
		$admin->manage();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Exports start from the Export buttons', $html );
		$this->assertStringNotContainsString( 'Your export is ready', $html );
		$this->assertStringContainsString( 'form="exports-reports-export"', $html, 'the report and its buttons are shown' );
		$this->assertCount( $before, $this->export_files(), 'no file was written' );
		$this->assertFalse( $admin->exported_file );
	}

	public function test_a_posted_format_the_screen_does_not_offer_is_not_taken() {
		$_POST = array(
			'exports_reports_export' => '1',
			'export_type'            => 'pdf',
			'_wpnonce'               => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$admin = $this->report( 'SELECT id FROM ' . self::videos(), 'Posted narrowed', array( 'export_formats' => array( 'csv' ) ) );

		$this->assertSame( 'export', $admin->action );
		$this->assertFalse( $admin->export_type );
	}

	public function test_the_settings_page_saves_the_formats() {
		wp_set_current_user( $this->administrator );

		$_POST = array(
			'_wpnonce'           => wp_create_nonce( 'exports-reports-settings' ),
			'export_formats_set' => '1',
			'export_formats'     => array( 'tsv', 'csv', 'nope' ),
		);
		$_REQUEST = $_POST;

		ob_start();
		exports_reports_settings();
		$html = (string) ob_get_clean();

		$this->assertSame( array( 'pipe', 'xlsx', 'xml', 'json', 'pdf' ), get_option( 'exports_reports_export_formats_off' ), 'what is off is stored, so a format added later is on' );
		$this->assertSame( array( 'csv', 'tsv' ), exports_reports_site_export_formats() );
		$this->assertSame( 1, preg_match( '/name="export_formats\[\]" value="csv" checked/', $html ) );
		$this->assertSame( 1, preg_match( '/name="export_formats\[\]" value="pdf" \/>/', $html ) );

		$_POST = array(
			'_wpnonce'           => wp_create_nonce( 'exports-reports-settings' ),
			'export_formats_set' => '1',
		);
		$_REQUEST = $_POST;

		ob_start();
		exports_reports_settings();
		ob_end_clean();

		$this->assertSame( array(), exports_reports_site_export_formats(), 'none ticked turns every button off' );

		$_POST = array(
			'_wpnonce' => wp_create_nonce( 'exports-reports-settings' ),
			'reset'    => 'Reset Now',
		);
		$_REQUEST = $_POST;

		ob_start();
		exports_reports_settings();
		ob_end_clean();

		$this->assertFalse( get_option( 'exports_reports_export_formats_off' ), 'the reset puts every format back on' );
	}

	public function test_the_upgrade_adds_the_export_formats_column() {
		global $wpdb;

		$table = $wpdb->exports_reports_reports;

		$wpdb->query( "ALTER TABLE `{$table}` DROP COLUMN `export_formats`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertNotContains( 'export_formats', $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		update_option( 'exports_reports_version', '1.0.2' );
		exports_reports_install_upgrade();

		$columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$found   = array_filter(
			$columns,
			static function ( $column ) {
				return 'export_formats' === $column['Field'];
			}
		);

		$this->assertCount( 1, $found );
		$this->assertSame( '', current( $found )['Default'] );
		$this->assertSame( EXPORTS_REPORTS_VERSION, get_option( 'exports_reports_version' ) );
		$this->assertSame( '', (string) $this->report_row()->export_formats, 'the rows that were there offer the site\'s formats' );
	}

	public function test_protect_export_dir_rewrites_its_own_outdated_rules_and_leaves_a_custom_file() {
		$this->protect_export_dir();

		$htaccess = EXPORTS_REPORTS_EXPORT_DIR . '/.htaccess';
		$rules    = (string) file_get_contents( $htaccess ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$this->assertStringContainsString( 'Require all denied', $rules );

		file_put_contents( $htaccess, "# Exports and Reports: an older version's rules\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->protect_export_dir();

		$this->assertSame( $rules, (string) file_get_contents( $htaccess ), 'the plugin\'s own file is brought up to date' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		file_put_contents( $htaccess, "Options -Indexes\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$this->protect_export_dir();

		$this->assertSame( "Options -Indexes\n", (string) file_get_contents( $htaccess ), 'a file the site wrote stays' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		wp_delete_file( $htaccess );
		$this->protect_export_dir();

		$this->assertSame( $rules, (string) file_get_contents( $htaccess ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}
}
