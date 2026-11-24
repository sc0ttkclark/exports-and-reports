<?php
/**
 * What the test classes share: the seeded table, a report definition set up the way the report page
 * and the exports set it up, the export files and the REST requests.
 *
 * @package Exports_And_Reports
 */

/**
 * A display function a plugin defines: the custom_display cases call it back.
 *
 * @param mixed $value The value.
 * @return string
 */
function exports_reports_tests_shout( $value ) {
	return strtoupper( (string) $value );
}

trait Exports_Reports_Test_Helpers {

	/**
	 * The seeded table, with the test prefix.
	 *
	 * @var string
	 */
	protected static $videos_table = '';

	/**
	 * The rows of the seeded table.
	 *
	 * @var int
	 */
	protected static $video_rows = 60;

	/**
	 * The rows of the seeded table whose revenue is above 50.
	 *
	 * @var int
	 */
	protected static $videos_over_fifty = 0;

	/**
	 * Database errors recorded while a case ran, from every statement.
	 *
	 * @var string[]
	 */
	protected $db_errors = array();

	/**
	 * Creates and seeds the table before the class's tests: a real table, outside the test transactions,
	 * dropped after them. Sixty rows in three channels, revenue = id * 1.5, and four special rows: a
	 * formula-like title (7), a title with quotes, a comma and a semicolon (8), a negative revenue (9)
	 * and a title with a line break (10).
	 *
	 * @param WP_UnitTest_Factory $factory The factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		self::create_videos_table();
	}

	/**
	 * Drops the seeded table after the class's tests.
	 */
	public static function wpTearDownAfterClass() {
		self::drop_videos_table();
	}

	/**
	 * Before each test; the test classes extend it.
	 */
	protected function prepare() {
		global $wpdb;

		$_REQUEST         = array();
		$this->db_errors  = array();
		$wpdb->last_error = '';

		add_filter( 'query', array( $this, 'record_db_error' ) );

		wp_mkdir_p( EXPORTS_REPORTS_EXPORT_DIR );
	}

	/**
	 * After each test; the test classes extend it.
	 */
	protected function clean() {
		remove_filter( 'query', array( $this, 'record_db_error' ) );

		$_REQUEST = array();
	}

	/**
	 * Records the error of the statement before this one: the query filter runs before wpdb clears it,
	 * so the errors of every statement a case runs are kept, not only the last one's.
	 *
	 * @param string $query The statement about to run.
	 * @return string
	 */
	public function record_db_error( $query ) {
		global $wpdb;

		if ( '' !== (string) $wpdb->last_error ) {
			$this->db_errors[] = $wpdb->last_error;
		}

		return $query;
	}

	/**
	 * The database errors recorded since the case began, on one line.
	 *
	 * @return string '' when there was none.
	 */
	protected function db_error() {
		global $wpdb;

		$errors = $this->db_errors;

		if ( '' !== (string) $wpdb->last_error ) {
			$errors[] = $wpdb->last_error;
		}

		return implode( ' | ', array_unique( $errors ) );
	}

	/**
	 * Creates and seeds the table.
	 *
	 * @throws RuntimeException When the rows could not be written.
	 */
	public static function create_videos_table() {
		global $wpdb;

		$table    = $wpdb->prefix . 'er_test_videos';
		$channels = array( 'alpha', 'beta', 'gamma' );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- the test's own table.
		$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
		$wpdb->query( "CREATE TABLE `{$table}` ( `id` INT NOT NULL AUTO_INCREMENT, `channel` VARCHAR(32) NOT NULL, `title` VARCHAR(191) NOT NULL, `revenue` DECIMAL(10,2) NOT NULL, `published` DATE NOT NULL, PRIMARY KEY (`id`) )" );
		// phpcs:enable

		$over_fifty = 0;

		for ( $i = 1; $i <= self::$video_rows; $i++ ) {
			$title   = 'Video ' . $i;
			$revenue = round( $i * 1.5, 2 );

			if ( 7 === $i ) {
				$title = '=1+1';
			} elseif ( 8 === $i ) {
				$title = 'Say "hi", pause; go';
			} elseif ( 9 === $i ) {
				$revenue = -13.5;
			} elseif ( 10 === $i ) {
				$title = "Two\nlines";
			}

			if ( $revenue > 50 ) {
				++$over_fifty;
			}

			$wpdb->insert(
				$table,
				array(
					'channel'   => $channels[ $i % 3 ],
					'title'     => $title,
					'revenue'   => $revenue,
					'published' => gmdate( 'Y-m-d', strtotime( '-' . $i . ' days' ) ),
				)
			);
		}

		if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}`" ) !== self::$video_rows ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			throw new RuntimeException( 'could not seed ' . $table . ': ' . $wpdb->last_error );
		}

		self::$videos_table      = $table;
		self::$videos_over_fifty = $over_fifty;
	}

	/**
	 * Drops the seeded table.
	 */
	public static function drop_videos_table() {
		global $wpdb;

		if ( '' !== self::$videos_table ) {
			$wpdb->query( 'DROP TABLE IF EXISTS `' . self::$videos_table . '`' ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * The seeded table as a quoted identifier, for a statement.
	 *
	 * @return string
	 */
	protected static function videos() {
		return '`' . self::$videos_table . '`';
	}

	/**
	 * Whether the database is MariaDB.
	 *
	 * @return bool
	 */
	protected function is_mariadb() {
		global $wpdb;

		return false !== stripos( (string) $wpdb->db_server_info(), 'mariadb' );
	}

	/**
	 * An admin UI instance for a report definition, with the options exports_reports_view() sets.
	 *
	 * @param string $sql     The report SQL.
	 * @param string $name    The report name.
	 * @param array  $options Options added to the defaults (columns, sql_count).
	 * @return Exports_Reports_Admin_UI
	 */
	protected function report( $sql, $name, $options = array() ) {
		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

		return new Exports_Reports_Admin_UI(
			array_merge(
				array(
					'readonly'     => true,
					'export'       => true,
					'search'       => false,
					'default_none' => false,
					'sql'          => $sql,
					'item'         => 'item',
					'items'        => $name,
					'heading'      => array( 'manage' => 'View Report:' ),
					'report_id'    => 0,
				),
				$options
			)
		);
	}

	/**
	 * Runs a report definition the way an export does and returns [ instance, rows, error ].
	 *
	 * @param string $sql     The report SQL.
	 * @param string $name    The report name.
	 * @param bool   $stream  Whether export_sv() should read the rows itself.
	 * @param array  $options Options added to the defaults.
	 * @param string $type    The export type.
	 * @return array{0: Exports_Reports_Admin_UI, 1: array, 2: string}
	 */
	protected function run_report( $sql, $name, $stream = false, $options = array(), $type = 'csv' ) {
		global $wpdb;

		$this->db_errors  = array();
		$wpdb->last_error = '';

		$admin                = $this->report( $sql, $name, $options );
		$admin->export_type   = $type;
		$admin->stream_export = $stream;

		ob_start();
		$admin->get_data( true );
		ob_end_clean();

		$rows = is_array( $admin->full_data ) ? $admin->full_data : array();

		return array( $admin, $rows, $this->db_error() );
	}

	/**
	 * Writes the delimited export of a prepared instance and returns the file's contents; the file is removed.
	 *
	 * @param Exports_Reports_Admin_UI $admin The instance, get_data( true ) already run.
	 * @return string The contents, '' when no file was written.
	 */
	protected function export_contents( $admin ) {
		ob_start();
		$file = $admin->export_sv();
		ob_end_clean();

		if ( ! is_string( $file ) ) {
			return '';
		}

		$path = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;

		if ( ! is_readable( $path ) ) {
			return '';
		}

		$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote
		wp_delete_file( $path );

		return $contents;
	}

	/**
	 * Runs export() as the export links do, with their nonce in the request.
	 *
	 * @param Exports_Reports_Admin_UI $admin The instance.
	 * @param array                    $vars  Other request variables (remove_export).
	 * @return array{0: mixed, 1: string} What export() returned, and what it printed.
	 */
	protected function export_from_link( $admin, $vars = array() ) {
		$_GET = array_merge( $vars, array( '_wpnonce' => wp_create_nonce( 'exports-reports-export-request' ) ) );

		ob_start();
		$result = $admin->export();
		$output = (string) ob_get_clean();

		$_GET = array();

		return array( $result, $output );
	}

	/**
	 * Runs export() as the screen's forms do, with their nonce and the variables posted.
	 *
	 * @param Exports_Reports_Admin_UI $admin The instance.
	 * @param array                    $vars  Other posted variables (export_type, remove_export).
	 * @return array{0: mixed, 1: string} What export() returned, and what it printed.
	 */
	protected function export_from_post( $admin, $vars = array() ) {
		$_POST = array_merge(
			$vars,
			array(
				'exports_reports_export' => '1',
				'_wpnonce'               => wp_create_nonce( 'exports-reports-export-request' ),
			)
		);

		ob_start();
		$result = $admin->export();
		$output = (string) ob_get_clean();

		$_POST = array();

		return array( $result, $output );
	}

	/**
	 * Writes the PDF export of a report definition and reads what the viewer would: the file name, the
	 * contents, the page count and the major version in the producer string (TCPDF 6 is the TCPDF
	 * engine, "TCPDF 8" is tc-lib-pdf). The file is removed.
	 *
	 * @param Exports_Reports_Admin_UI $admin The instance.
	 * @return array{file: string, contents: string, pages: int, producer: int}
	 */
	protected function pdf_export( $admin ) {
		$admin->action      = 'export';
		$admin->export_type = 'pdf';

		$this->export_from_link( $admin );

		$file     = (string) $admin->exported_file;
		$path     = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
		$contents = '' !== $file && is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		if ( '' !== $file ) {
			wp_delete_file( $path );
		}

		// The producer string is UTF-16BE. PCRE escapes: a NUL byte in the pattern itself is refused below PHP 8.2.
		preg_match( '/T\x00C\x00P\x00D\x00F\x00 \x00(\d)/', $contents, $producer );

		return array(
			'file'     => $file,
			'contents' => $contents,
			'pages'    => preg_match_all( '#/Type\s*/Page[^s]#', $contents ),
			'producer' => isset( $producer[1] ) ? (int) $producer[1] : 0,
		);
	}

	/**
	 * Asserts a PDF export the viewer can open, with the pages the sixty rows need, by the engine expected.
	 *
	 * @param array $pdf            What pdf_export() returned.
	 * @param int   $producer_major 6 for TCPDF, 8 for tc-lib-pdf.
	 */
	protected function assert_pdf_export( array $pdf, $producer_major ) {
		$this->assertNotSame( '', $pdf['file'], 'a file was written' );
		$this->assertStringEndsWith( '.pdf', $pdf['file'] );
		$this->assertStringStartsWith( '%PDF-', $pdf['contents'] );
		$this->assertStringContainsString( '%%EOF', $pdf['contents'] );
		$this->assertGreaterThanOrEqual( 2, $pdf['pages'], 'the rows need two pages' );
		$this->assertSame( $producer_major, $pdf['producer'], 'the producer string names the engine' );
	}

	/**
	 * Sends a file from the export directory as a download and returns [ sent, body ].
	 *
	 * PHPUnit has written to stdout by the time a test runs, so PHP refuses the headers the function sets,
	 * with a warning; that one warning is let through, and the function's decision and the file it streams
	 * are what the tests read.
	 *
	 * @param string $filename The file name.
	 * @return array{0: bool, 1: string}
	 */
	protected function send_export_file( $filename ) {
		$previous = null;
		$previous = set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			static function ( $errno, $errstr, $errfile, $errline ) use ( &$previous ) {
				if ( E_WARNING === $errno && 0 === strpos( $errstr, 'Cannot modify header information' ) ) {
					return true;
				}

				return is_callable( $previous ) ? (bool) call_user_func( $previous, $errno, $errstr, $errfile, $errline ) : false;
			}
		);

		ob_start();
		$sent = exports_reports_send_export_file( $filename );
		$body = (string) ob_get_clean();

		restore_error_handler();

		return array( $sent, $body );
	}

	/**
	 * Dispatches a REST request.
	 *
	 * @param string $method  GET or POST.
	 * @param string $route   The route.
	 * @param array  $params  Request parameters.
	 * @param array  $headers Request headers.
	 * @return WP_REST_Response
	 */
	protected function rest_request( $method, $route, $params = array(), $headers = array() ) {
		$request = new WP_REST_Request( $method, $route );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		foreach ( $headers as $key => $value ) {
			$request->set_header( $key, $value );
		}

		ob_start();
		$response = rest_do_request( $request );
		ob_end_clean();

		return $response;
	}

	/**
	 * Writes the files that keep the export directory from being served, as an export does.
	 */
	protected function protect_export_dir() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

		wp_mkdir_p( EXPORTS_REPORTS_EXPORT_DIR );
		WP_Filesystem();
		Exports_Reports_Admin_UI::protect_export_dir();
	}

	/**
	 * The files in the export directory, without index.php (the first export writes it).
	 *
	 * @return string[] Paths.
	 */
	protected function export_files() {
		return array_values( array_diff( (array) glob( EXPORTS_REPORTS_EXPORT_DIR . '/*' ), array( EXPORTS_REPORTS_EXPORT_DIR . '/index.php' ) ) );
	}

	/**
	 * The options of a report with a multiple-choice filter on the channel.
	 *
	 * @return array
	 */
	protected function channel_filter_options() {
		return array(
			'search'  => true,
			'columns' => array(
				'id'      => array( 'label' => 'Id' ),
				'channel' => array(
					'label'   => 'Channel',
					'type'    => 'related',
					'related' => array(
						'alpha' => 'Alpha',
						'beta'  => 'Beta',
					),
					'filter'  => true,
				),
			),
		);
	}

	/**
	 * The directory the plugin used before 0.9.5, wp-content/exports of the WordPress copy under test;
	 * the test is skipped when another install's files are in it, which the test would otherwise take
	 * for its own.
	 *
	 * @return string The directory.
	 */
	protected function require_free_legacy_export_dir() {
		$legacy = WP_CONTENT_DIR . '/exports';

		if ( is_dir( $legacy ) && array() !== array_diff( (array) scandir( $legacy ), array( '.', '..' ) ) ) {
			$this->markTestSkipped( $legacy . ' holds files of another install on this WordPress copy.' );
		}

		return $legacy;
	}
}
