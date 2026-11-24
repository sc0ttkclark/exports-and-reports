<?php
/**
 * What the screens print of the values a report reads: text, unless a display function wrote the markup or
 * the exports_reports_html_values filter opts a column back in.
 *
 * @package Exports_And_Reports
 */

class Tests_Screen_Output extends Exports_Reports_Test_Case {

	/**
	 * Markup as a visitor might store it in a table a report reads.
	 *
	 * @var string
	 */
	const STORED = '<a href="https://example.invalid/reset">Reset your password</a><img src="https://example.invalid/pixel.png"><script>alert(1)</script>';

	/**
	 * The table of a report whose first (title) column and second column both hold the stored markup.
	 *
	 * @param array $options Options added to the defaults.
	 * @return string What the screen prints.
	 */
	protected function report_table( $options = array() ) {
		global $wpdb;

		$sql    = (string) $wpdb->prepare( 'SELECT %s AS title, %s AS note, channel, id FROM ' . self::videos() . ' WHERE id = 1', self::STORED, self::STORED ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the test's own table.
		$report = $this->report( $sql, 'Stored markup', $options );

		ob_start();
		$report->get_data();
		$report->table();

		return (string) ob_get_clean();
	}

	/**
	 * A link, an image and a script a table stores are shown as the text they are, in the title column and in the others.
	 */
	public function test_stored_markup_is_text_on_the_report_page() {
		$html = $this->report_table();

		$this->assertStringNotContainsString( '<a href="https://example.invalid/reset"', $html, 'no stored link is a link' );
		$this->assertStringNotContainsString( '<img', $html, 'no stored image is loaded' );
		$this->assertStringNotContainsString( '<script>alert', $html );
		$this->assertSame( 2, substr_count( $html, '&lt;a href=&quot;https://example.invalid/reset&quot;&gt;Reset your password&lt;/a&gt;&lt;img' ), 'both cells show the markup as text' );
		$this->assertStringContainsString( '>beta<', preg_replace( '/\s+/', '', $html ), 'a plain value is unchanged' );
	}

	/**
	 * What a display function returns is the administrator's own markup, kept within the allowed tags.
	 */
	public function test_a_display_function_keeps_its_markup() {
		$html = $this->report_table(
			array(
				'columns' => array(
					'title'   => array( 'label' => 'Title' ),
					'note'    => array( 'label' => 'Note' ),
					'channel' => array(
						'label'          => 'Channel',
						'custom_display' => 'exports_reports_tests_emphasis',
					),
				),
			)
		);

		$this->assertStringContainsString( '<em>beta</em>', $html );
		$this->assertStringNotContainsString( '<a href="https://example.invalid/reset"', $html, 'the columns without a display function are text' );
	}

	/**
	 * The filter opts a column of a report back in: its values render with the tags post content may
	 * have (the link and the image, not the script), and the other columns stay text.
	 */
	public function test_the_html_values_filter_opts_a_column_back_in() {
		$seen = array();
		$opt  = static function ( $html, $column, $attributes, $admin ) use ( &$seen ) {
			$seen[ $column ] = array( $html, isset( $attributes['label'] ), $admin instanceof Exports_Reports_Admin_UI );

			return 'note' === $column ? true : $html;
		};

		add_filter( 'exports_reports_html_values', $opt, 10, 4 );

		$html = $this->report_table();

		remove_filter( 'exports_reports_html_values', $opt, 10 );

		$this->assertSame( array( false, true, true ), $seen['note'], 'off by default; the column, its attributes and the screen are passed' );
		$this->assertSame( 1, substr_count( $html, '<a href="https://example.invalid/reset">Reset your password</a><img' ), 'the note column renders its markup' );
		$this->assertSame( 1, substr_count( $html, '&lt;a href=&quot;https://example.invalid/reset&quot;' ), 'the title column stays text' );
		$this->assertStringNotContainsString( '<script>', $html, 'a script is never among the allowed tags' );
	}

	/**
	 * The view screen of a row escapes the row's values the same way, and still lists a related column's labels.
	 */
	public function test_the_view_screen_shows_stored_markup_as_text() {
		global $wpdb;

		require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

		$original = $wpdb->get_var( 'SELECT title FROM ' . self::videos() . ' WHERE id = 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the test's own table.

		$wpdb->update( self::$videos_table, array( 'title' => self::STORED ), array( 'id' => 1 ) );

		$admin = new Exports_Reports_Admin_UI(
			array(
				'table'   => self::$videos_table,
				'view'    => true,
				'id'      => 1,
				'item'    => 'Video',
				'items'   => 'Videos',
				'columns' => array(
					'title',
					'channel' => array(
						'label'   => 'Channel',
						'type'    => 'related',
						'related' => array(
							'alpha' => 'Alpha',
							'beta'  => '<b>Beta</b>',
						),
					),
				),
			)
		);

		ob_start();
		$admin->view();
		$html = (string) ob_get_clean();

		$wpdb->update( self::$videos_table, array( 'title' => $original ), array( 'id' => 1 ) );

		$this->assertStringNotContainsString( '<a href="https://example.invalid/reset"', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( '&lt;a href=&quot;https://example.invalid/reset&quot;&gt;Reset your password', $html );
		$this->assertStringContainsString( '<ul><li>&lt;b&gt;Beta&lt;/b&gt;</li></ul>', $html, 'the list of a related column is the screen\'s own markup, its labels escaped' );
	}
}
