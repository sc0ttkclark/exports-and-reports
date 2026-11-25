<?php
/**
 * How numbers and dates are written: the file exports as they have always been, the screens and the PDF
 * by the site's language, and an export formatted for a language from the Format menu or a REST
 * request's locale (issue #9).
 *
 * @package Exports_And_Reports
 */

class Tests_Value_Formats extends Exports_Reports_Test_Case {

	/**
	 * The locale switcher the suite runs with, while a test has put one of its own in its place.
	 *
	 * @var WP_Locale_Switcher|null
	 */
	protected $switcher = null;

	/**
	 * The number separators the suite runs with, while a test has put others in their place.
	 *
	 * @var array|null
	 */
	protected $separators = null;

	/**
	 * The language pack a test makes up, by msgid.
	 *
	 * @var array<string, string>
	 */
	protected $pack = array();

	/**
	 * Puts back what a test replaced.
	 */
	protected function clean() {
		global $wp_locale, $wp_locale_switcher;

		if ( null !== $this->switcher ) {
			$wp_locale_switcher = $this->switcher; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- the suite's own switcher, put back.
			$this->switcher     = null;
		}

		if ( null !== $this->separators ) {
			$wp_locale->number_format = $this->separators;
			$this->separators         = null;
		}

		remove_filter( 'get_available_languages', array( $this, 'install_italian' ) );
		remove_filter( 'gettext', array( $this, 'translate_from_the_pack' ), 10 );
		remove_filter( 'exports_reports_use_intl', '__return_false' );

		delete_option( 'exports_reports_site_date_formats' );

		parent::clean();
	}

	/**
	 * Two rows with a large number, a decimal with thousands (one of them negative), a date and a date
	 * and time: the 7th of March, so the day and the month show their order.
	 *
	 * @return string The report SQL.
	 */
	protected static function figures() {
		return "SELECT id, id * 1234567 AS views, revenue * 1000.5 AS amount, '2026-03-07' AS day, '2026-03-07 14:05:09' AS seen FROM " . self::videos() . ' WHERE id IN (1, 9) ORDER BY id';
	}

	/**
	 * The fields of figures().
	 *
	 * @return array
	 */
	protected static function figure_columns() {
		return array(
			'id',
			'views'  => array( 'type' => 'number' ),
			'amount' => array( 'type' => 'decimal' ),
			'day'    => array( 'type' => 'date' ),
			'seen'   => array( 'type' => 'datetime' ),
		);
	}

	/**
	 * The CSV export of figures(), as rows of values.
	 *
	 * @param string $locale  The locale the export is formatted for, '' for none.
	 * @param array  $options Options added to the report's.
	 * @return array{0: string, 1: array<int, string[]>} The file's contents, and its rows split at the delimiter found.
	 */
	protected function figures_csv( $locale = '', $options = array() ) {
		list( $admin ) = $this->run_report( self::figures(), 'Figures', false, array_merge( array( 'columns' => self::figure_columns() ), $options ) );

		$admin->export_locale = $locale;

		$csv       = $this->export_contents( $admin );
		$lines     = explode( "\n", trim( substr( $csv, 3 ) ) );
		$delimiter = false !== strpos( $lines[0], '";"' ) ? ';' : ',';
		$rows      = array();

		foreach ( $lines as $line ) {
			// Every value is quoted, and none of these values holds a quote.
			$rows[] = explode( '"' . $delimiter . '"', substr( $line, 1, -1 ) );
		}

		return array( $csv, $rows );
	}

	/**
	 * Skips a test that reads what PHP's intl extension writes when the extension is not loaded.
	 */
	protected function need_intl() {
		if ( ! Exports_Reports_Value_Format::has_intl() ) {
			$this->markTestSkipped( 'PHP runs without the intl extension.' );
		}
	}

	/**
	 * Lets WordPress switch to Italian as if the language pack were installed: the strings come from
	 * translate_from_the_pack().
	 */
	protected function install_italian_pack() {
		global $wp_locale_switcher;

		$this->pack = array(
			'number_format_thousands_sep' => '.',
			'number_format_decimal_point' => ',',
			'Y/m/d'                       => 'd/m/Y',
			'g:i a'                       => 'H:i',
		);

		add_filter( 'get_available_languages', array( $this, 'install_italian' ) );
		add_filter( 'gettext', array( $this, 'translate_from_the_pack' ), 10, 3 );

		// The switcher lists the installed languages when it is made.
		$this->switcher     = $wp_locale_switcher;
		$wp_locale_switcher = new WP_Locale_Switcher(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a switcher that knows the test's language, for the test.
		$wp_locale_switcher->init();
	}

	/**
	 * Adds Italian to the installed languages.
	 *
	 * @param string[] $languages The installed languages.
	 * @return string[]
	 */
	public function install_italian( $languages ) {
		return array_merge( (array) $languages, array( 'it_IT' ) );
	}

	/**
	 * Core's strings in Italian, while WordPress runs in Italian.
	 *
	 * @param string $translation The translation so far.
	 * @param string $text        The string.
	 * @param string $domain      The text domain.
	 * @return string
	 */
	public function translate_from_the_pack( $translation, $text, $domain ) {
		return 'default' === $domain && isset( $this->pack[ $text ] ) && 'it_IT' === determine_locale() ? $this->pack[ $text ] : $translation;
	}

	/**
	 * The PDF engines this PHP can load: the tc-lib-pdf engine is written for PHP 8.2 and later and does
	 * not parse below 7.4.
	 *
	 * @return string[] Class names.
	 */
	protected function pdf_engines() {
		require_once EXPORTS_REPORTS_DIR . 'includes/exporters/class-exports-reports-admin-ui-export-pdf-legacy.php';

		$engines = array( 'Exports_Reports_Admin_UI_Export_PDF_Legacy' );

		if ( PHP_VERSION_ID >= 80200 ) {
			require_once EXPORTS_REPORTS_DIR . 'includes/exporters/class-exports-reports-admin-ui-export-pdf.php';

			$engines[] = 'Exports_Reports_Admin_UI_Export_PDF';
		}

		return $engines;
	}

	/**
	 * A file export is what it has always been: the same bytes whatever the Settings page says about the
	 * site's date formats and whatever separators the site's language has. A negative number with a
	 * thousands separator is a number, not a formula to wrap.
	 */
	public function test_the_default_export_is_unchanged() {
		global $wp_locale;

		$expected = "\xEF\xBB\xBF" . '"Id","Views","Amount","Day","Seen"' . "\n"
			. '"1","1,234,567","1,500.75","2026/03/07","2026/03/07 2:05:09 PM"' . "\n"
			. '"9","11,111,103","-13,506.75","2026/03/07","2026/03/07 2:05:09 PM"' . "\n";

		list( $csv ) = $this->figures_csv();

		$this->assertSame( $expected, $csv );

		// A site in a language of comma decimals, with the site's own date formats on the screens.
		$this->separators         = $wp_locale->number_format;
		$wp_locale->number_format = array(
			'thousands_sep' => '.',
			'decimal_point' => ',',
		);

		update_option( 'exports_reports_site_date_formats', 1 );
		update_option( 'date_format', 'd.m.Y' );

		list( $csv ) = $this->figures_csv();

		$this->assertSame( $expected, $csv, 'the file does not follow the screen' );
	}

	/**
	 * Issue #9: an export for Italian has comma decimals, the day before the month, and, as a CSV file,
	 * semicolons between its values.
	 */
	public function test_an_export_formatted_for_italian() {
		$this->need_intl();

		list( $csv, $rows ) = $this->figures_csv( 'it_IT' );

		$this->assertStringStartsWith( "\xEF\xBB\xBF" . '"Id";"Views";"Amount";"Day";"Seen"' . "\n", $csv );
		$this->assertSame( array( '1', '1.234.567', '1.500,75', '07/03/2026' ), array_slice( $rows[1], 0, 4 ) );
		$this->assertSame( array( '9', '11.111.103', '-13.506,75', '07/03/2026' ), array_slice( $rows[2], 0, 4 ), 'a negative number is written as one, not wrapped as a formula' );
		$this->assertSame( 1, preg_match( '#^07/03/2026,? 14:05:09$#', $rows[1][4] ) );

		// The tab and the pipe stay.
		list( $admin ) = $this->run_report( self::figures(), 'Figures', false, array( 'columns' => self::figure_columns() ), 'tsv' );

		$admin->export_locale = 'it_IT';

		$this->assertStringContainsString( "\"1.234.567\"\t\"1.500,75\"\t\"07/03/2026\"", $this->export_contents( $admin ) );
	}

	/**
	 * A language whose thousands separator is neither a comma nor a period keeps it, and a language that
	 * groups its digits by two keeps that too.
	 */
	public function test_languages_group_their_digits_their_own_way() {
		$this->need_intl();

		list( , $french ) = $this->figures_csv( 'fr_FR' );

		$this->assertSame( 1, preg_match( '/^1[^0-9.,]234[^0-9.,]567$/u', $french[1][1] ), 'a space of some kind between the groups' );
		$this->assertSame( 1, preg_match( '/^-13[^0-9.,]506,75$/u', $french[2][2] ) );
		$this->assertSame( '07/03/2026', $french[1][3] );

		list( $csv, $indian ) = $this->figures_csv( 'en_IN' );

		$this->assertSame( '12,34,567', $indian[1][1] );
		$this->assertSame( '1,11,11,103', $indian[2][1] );
		$this->assertSame( '1,500.75', $indian[1][2] );
		$this->assertStringContainsString( '"Id","Views"', $csv, 'a decimal point keeps the comma between the values' );

		list( , $american ) = $this->figures_csv( 'en_US' );

		$this->assertSame( array( '1,234,567', '1,500.75', '3/7/2026' ), array_slice( $american[1], 1, 3 ) );
		$this->assertSame( 1, preg_match( '#^3/7/2026,? 2:05:09 PM$#', $american[1][4] ), 'a plain space before PM, which a spreadsheet reads' );
	}

	/**
	 * A language with digits of its own is written with the digits 0 to 9 unless the filter says otherwise.
	 */
	public function test_an_export_keeps_western_digits_unless_the_filter_says_otherwise() {
		$this->need_intl();

		$persian = Exports_Reports_Value_Format::locale( 'fa_IR' );

		$this->assertSame( 1, preg_match( '/^[0-9,.]+$/', $persian->number( 1234567, 0 ) ) );
		$this->assertSame( 1, preg_match( '#^[0-9/.\-]+$#', $persian->date( '2026-03-07', 'date' ) ), 'the Gregorian calendar, in Western digits' );
		$this->assertStringContainsString( '2026', $persian->date( '2026-03-07', 'date' ) );

		add_filter( 'exports_reports_export_western_digits', '__return_false' );

		$own = Exports_Reports_Value_Format::locale( 'fa_IR' )->number( 1234567, 0 );

		remove_filter( 'exports_reports_export_western_digits', '__return_false' );

		$this->assertSame( 0, preg_match( '/[0-9]/', $own ) );
	}

	/**
	 * Without intl, the language pack WordPress has installed says how the language writes numbers and
	 * dates; a language with no pack is written as the plugin's own format.
	 */
	public function test_without_intl_the_language_pack_formats_the_export() {
		add_filter( 'exports_reports_use_intl', '__return_false' );

		$this->install_italian_pack();

		list( $csv, $rows ) = $this->figures_csv( 'it_IT' );

		$this->assertStringStartsWith( "\xEF\xBB\xBF" . '"Id";"Views";"Amount";"Day";"Seen"' . "\n", $csv );
		$this->assertSame( array( '1', '1.234.567', '1.500,75', '07/03/2026', '07/03/2026 14:05:09' ), $rows[1] );
		$this->assertSame( '-13.506,75', $rows[2][2] );
		$this->assertSame( 'en_US', determine_locale(), 'the site is back in its own language' );

		list( $csv ) = $this->figures_csv( 'sv_SE' );

		$this->assertStringContainsString( '"1","1,234,567","1,500.75","2026/03/07","2026/03/07 2:05:09 PM"', $csv, 'no pack, no intl: the plugin\'s own format' );
	}

	/**
	 * The screens and the PDF write numbers with the separators of the language WordPress runs in, and
	 * dates in the site's formats once the Settings page says so.
	 */
	public function test_the_screens_and_the_pdf_follow_the_site() {
		global $wp_locale;

		$report = $this->report( self::figures(), 'Figures', array( 'columns' => self::figure_columns() ) );

		$this->assertSame( '1,234,567', $report->field_value( '1234567', 'views', $report->columns['views'] ), 'an English site sees what it saw' );
		$this->assertSame( '2026/03/07 2:05:09 PM', $report->field_value( '2026-03-07 14:05:09', 'seen', $report->columns['seen'] ) );

		$this->separators         = $wp_locale->number_format;
		$wp_locale->number_format = array(
			'thousands_sep' => '.',
			'decimal_point' => ',',
		);

		update_option( 'exports_reports_site_date_formats', 1 );
		update_option( 'date_format', 'd.m.Y' );
		update_option( 'time_format', 'H:i' );

		$report = $this->report( self::figures(), 'Figures', array( 'columns' => self::figure_columns() ) );

		$this->assertSame( '1.234.567', $report->field_value( '1234567', 'views', $report->columns['views'] ) );
		$this->assertSame( '-13.506,75', $report->field_value( '-13506.75', 'amount', $report->columns['amount'] ) );
		$this->assertSame( '07.03.2026', $report->field_value( '2026-03-07', 'day', $report->columns['day'] ) );
		$this->assertSame( '07.03.2026 14:05', $report->field_value( '2026-03-07 14:05:09', 'seen', $report->columns['seen'] ) );
		$this->assertSame( 'text', $report->field_value( 'text', 'views', $report->columns['views'] ), 'a value that is no number is shown as it is' );

		foreach ( $this->pdf_engines() as $engine ) {
			$format = $report->export_format( true );

			$this->assertSame( '1234567', (string) $engine::format_field( 'number', '1234567', $format ), $engine );
			$this->assertSame( '-13.506,75', (string) $engine::format_field( 'decimal', '-13506.75', $format ), $engine );
			$this->assertSame( '07.03.2026', (string) $engine::format_field( 'date', '2026-03-07', $format ), $engine );
			$this->assertSame( '1,500.75', (string) $engine::format_field( 'decimal', '1500.75', Exports_Reports_Value_Format::raw() ), $engine );
		}

		$this->assertStringContainsString( '07.03.' . gmdate( 'Y' ) . ' ', Exports_Reports_Value_Format::screen()->date( gmdate( 'Y' ) . '-03-07 10:00:00', 'datetime' ) );
		$this->assertSame( 1, preg_match( '/^\d\d\.\d\d\.\d{4} \d\d:\d\d$/', Exports_Reports_Value_Format::screen()->stamp() ), 'the PDF\'s date stamp follows the setting' );
		$this->assertSame( 1, preg_match( '#^\d\d/\d\d/\d{4} \d\d:\d\d:\d\d$#', Exports_Reports_Value_Format::raw()->stamp() ) );
	}

	/**
	 * A PDF formatted for a language carries that language's numbers and dates, in both engines.
	 */
	public function test_a_pdf_formatted_for_a_language() {
		$this->need_intl();

		$report                = $this->report( self::figures(), 'Figures', array( 'columns' => self::figure_columns() ) );
		$report->export_locale = 'it_IT';

		$format = $report->export_format( true );

		$this->assertSame( 'it_IT', $format->language() );

		foreach ( $this->pdf_engines() as $engine ) {
			$this->assertSame( '-13.506,75', (string) $engine::format_field( 'decimal', '-13506.75', $format ), $engine );
			$this->assertSame( '07/03/2026', (string) $engine::format_field( 'date', '2026-03-07', $format ), $engine );
		}

		$pdf = $this->pdf_export( $report );

		$this->assertStringStartsWith( '%PDF-', $pdf['contents'] );
	}

	/**
	 * The worksheet and the styles of the XLSX export of figures().
	 *
	 * @param string $locale The locale the export is formatted for, '' for none.
	 * @return array{0: string, 1: string}
	 */
	protected function figures_xlsx( $locale ) {
		list( $admin ) = $this->run_report( self::figures(), 'Figures', false, array( 'columns' => self::figure_columns() ) );

		$admin->action        = 'export';
		$admin->export_type   = 'xlsx';
		$admin->export_locale = $locale;

		$this->export_from_link( $admin );

		$path   = EXPORTS_REPORTS_EXPORT_DIR . '/' . (string) $admin->exported_file;
		$zip    = new ZipArchive();
		$sheet  = '';
		$styles = '';

		if ( true === $zip->open( $path ) ) {
			$sheet  = (string) $zip->getFromName( 'xl/worksheets/sheet1.xml' );
			$styles = (string) $zip->getFromName( 'xl/styles.xml' );
			$zip->close();
		}

		wp_delete_file( $path );

		return array( $sheet, $styles );
	}

	/**
	 * A cell of a worksheet: its type, its value and the number format of its style.
	 *
	 * @param string $sheet  The worksheet's XML.
	 * @param string $styles The styles' XML.
	 * @param string $cell   The cell (B2).
	 * @return array{type: string, value: string, format: int}
	 */
	protected function xlsx_cell( $sheet, $styles, $cell ) {
		$this->assertSame( 1, preg_match( '#<c r="' . $cell . '" s="(\d+)" t="(\w+)">(?:<v>([^<]*)</v>|<is><t>([^<]*)</t></is>)</c>#', $sheet, $found ), $cell . ' in ' . $sheet );

		preg_match( '#<cellXfs[^>]*>(.*)</cellXfs>#s', $styles, $xfs );
		preg_match_all( '#<xf [^>]*numFmtId="(\d+)"[^>]*>#', $xfs[1], $formats );

		return array(
			'type'   => $found[2],
			'value'  => '' !== $found[3] ? $found[3] : ( isset( $found[4] ) ? $found[4] : '' ),
			'format' => (int) $formats[1][ (int) $found[1] ],
		);
	}

	/**
	 * In an XLSX export formatted for a language, a number is a number, and a date is a date the
	 * spreadsheet shows in the regional format of whoever opens the file.
	 */
	public function test_an_xlsx_export_formatted_for_a_language_holds_numbers_and_dates() {
		list( $sheet, $styles ) = $this->figures_xlsx( 'it_IT' );

		$views  = $this->xlsx_cell( $sheet, $styles, 'B3' );
		$amount = $this->xlsx_cell( $sheet, $styles, 'C3' );
		$day    = $this->xlsx_cell( $sheet, $styles, 'D2' );
		$seen   = $this->xlsx_cell( $sheet, $styles, 'E2' );

		$this->assertSame( array( 'n', '11111103' ), array( $views['type'], $views['value'] ) );
		$this->assertSame( 'n', $amount['type'] );
		$this->assertSame( -13506.75, (float) $amount['value'] );
		$this->assertStringContainsString( 'formatCode="#,##0.00"', $styles );

		// The serial number counts days from the end of 1899; 25569 is 1970-01-01.
		$this->assertSame( 'n', $day['type'] );
		$this->assertSame( 14, $day['format'], 'the built-in short date' );
		$this->assertSame( '2026-03-07', gmdate( 'Y-m-d', ( (int) $day['value'] - 25569 ) * DAY_IN_SECONDS ) );
		$this->assertSame( 'n', $seen['type'] );
		$this->assertSame( 22, $seen['format'], 'the built-in date and time' );
		$this->assertSame( '2026-03-07 14:05:09', gmdate( 'Y-m-d H:i:s', (int) round( ( (float) $seen['value'] - 25569 ) * DAY_IN_SECONDS ) ) );

		// Without a language the file is what 1.2.9 wrote: a number column as a number, the rest as text.
		list( $sheet, $styles ) = $this->figures_xlsx( '' );

		$this->assertSame( array( 'n', '11111103' ), array_slice( array_values( $this->xlsx_cell( $sheet, $styles, 'B3' ) ), 0, 2 ) );
		$this->assertSame( array( 'inlineStr', '-13,506.75' ), array_slice( array_values( $this->xlsx_cell( $sheet, $styles, 'C3' ) ), 0, 2 ) );
		$this->assertSame( array( 'inlineStr', '2026/03/07' ), array_slice( array_values( $this->xlsx_cell( $sheet, $styles, 'D2' ) ), 0, 2 ) );
	}

	/**
	 * A typed column keeps a value its type cannot read as text, where a numeric cell with text in it
	 * would leave a file the spreadsheet has to repair.
	 */
	public function test_an_xlsx_export_keeps_text_in_a_typed_column_as_text() {
		list( $admin ) = $this->run_report(
			"SELECT id, 'n/a' AS views, 'pending' AS amount, '0000-00-00' AS day, '1850-01-01' AS seen FROM " . self::videos() . ' WHERE id = 1',
			'Figures',
			false,
			array( 'columns' => self::figure_columns() )
		);

		$admin->action        = 'export';
		$admin->export_type   = 'xlsx';
		$admin->export_locale = 'en_US';

		$this->export_from_link( $admin );

		$path  = EXPORTS_REPORTS_EXPORT_DIR . '/' . (string) $admin->exported_file;
		$zip   = new ZipArchive();
		$sheet = '';

		if ( true === $zip->open( $path ) ) {
			$sheet = (string) $zip->getFromName( 'xl/worksheets/sheet1.xml' );
			$zip->close();
		}

		wp_delete_file( $path );

		$this->assertStringContainsString( 't="inlineStr"><is><t>n/a</t></is>', $sheet );
		$this->assertStringContainsString( 't="inlineStr"><is><t>pending</t></is>', $sheet );
		$this->assertStringContainsString( 't="inlineStr"><is><t>N/A</t></is>', $sheet, 'an empty date, as the screen shows it' );
		$this->assertSame( 1, preg_match( '#<c r="E2" s="\d+" t="inlineStr"><is><t>[^<]*1850[^<]*</t></is></c>#', $sheet ), 'a date before 1900 has no serial number: ' . $sheet );
	}

	/**
	 * A group and a report on figures(), with the token, for the REST routes.
	 *
	 * @param array $values Columns of the report row.
	 * @return int The report's id.
	 */
	protected function figures_report( $values = array() ) {
		$fields = array();

		foreach ( self::figure_columns() as $name => $attributes ) {
			$fields[] = array(
				'name' => is_array( $attributes ) ? $name : $attributes,
				'type' => is_array( $attributes ) ? $attributes['type'] : 'text',
			);
		}

		exports_reports_set_token( 'er-test-token-of-24-chars' );

		return $this->insert_report(
			$this->insert_group(),
			array_merge(
				array(
					'sql_query'  => self::figures(),
					'field_data' => (string) wp_json_encode( $fields ),
				),
				$values
			)
		);
	}

	/**
	 * The contents of the CSV export a REST request writes; the file is removed.
	 *
	 * @param int   $report_id The report.
	 * @param array $params    Parameters added to the request's.
	 * @return array{0: int, 1: string, 2: array} The status, the file's contents and the response's body.
	 */
	protected function rest_csv( $report_id, $params = array() ) {
		$response = $this->rest_request( 'POST', sprintf( '/exports-reports/v1/reports/%d/export', $report_id ), array_merge( array( 'type' => 'csv' ), $params ), array( 'Authorization' => 'Bearer er-test-token-of-24-chars' ) );
		$body     = (array) $response->get_data();
		$path     = EXPORTS_REPORTS_EXPORT_DIR . '/' . ( isset( $body['file'] ) ? (string) $body['file'] : 'none' );
		$contents = is_readable( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		if ( '' !== $contents ) {
			wp_delete_file( $path );
		}

		return array( $response->get_status(), $contents, $body );
	}

	/**
	 * A script gets the plugin's own format unless its request names a locale, whatever the report's
	 * default: nothing changes for a cronjob that asked for nothing.
	 */
	public function test_a_rest_export_is_raw_unless_the_request_names_a_locale() {
		$this->need_intl();

		$report_id = $this->figures_report( array( 'export_locale' => 'de_DE' ) );

		list( $status, $csv, $body ) = $this->rest_csv( $report_id );

		$this->assertSame( 200, $status, wp_json_encode( $body ) );
		$this->assertStringContainsString( '"1","1,234,567","1,500.75","2026/03/07","2026/03/07 2:05:09 PM"', $csv );
		$this->assertSame( '', $body['locale'] );

		list( $status, $csv, $body ) = $this->rest_csv( $report_id, array( 'locale' => 'it_IT' ) );

		$this->assertSame( 200, $status, wp_json_encode( $body ) );
		$this->assertStringContainsString( '"1";"1.234.567";"1.500,75";"07/03/2026";', $csv );
		$this->assertSame( 'it_IT', $body['locale'] );

		list( $status ) = $this->rest_csv( $report_id, array( 'locale' => '../../etc' ) );

		$this->assertSame( 400, $status, 'a locale parameter that names no locale is refused' );

		// A format that holds raw values says so.
		$response = $this->rest_request( 'POST', sprintf( '/exports-reports/v1/reports/%d/export', $report_id ), array( 'type' => 'json', 'locale' => 'it_IT' ), array( 'Authorization' => 'Bearer er-test-token-of-24-chars' ) );
		$body     = (array) $response->get_data();
		$json     = (string) file_get_contents( EXPORTS_REPORTS_EXPORT_DIR . '/' . $body['file'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . $body['file'] );

		$this->assertSame( '', $body['locale'] );
		$this->assertStringContainsString( '"amount": "1,500.75"', $json );
	}

	/**
	 * The Format menu sits with the export buttons, belongs to their form, shows the report's default and
	 * then what the user last chose for the report; the choice is posted with the export.
	 */
	public function test_the_format_menu_remembers_the_choice_per_user_and_report() {
		$this->need_intl();

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$report_id = $this->figures_report( array( 'export_locale' => 'en_US' ) );
		$other_id  = $this->figures_report();
		$screen    = function ( $id, $post = array() ) {
			$_POST = $post;

			$admin = new Exports_Reports_Admin_UI( exports_reports_report_options( exports_reports_get_report( $id ) ) );

			$_POST = array();

			return $admin;
		};

		$admin = $screen( $report_id );

		ob_start();
		$admin->get_data();
		$admin->manage();
		$html = (string) ob_get_clean();

		$this->assertSame( 1, preg_match( '#<select id="exports-reports-export-locale"[^>]* name="export_locale" form="exports-reports-export"[^>]*>(.*?)</select>#s', $html, $menu ), 'one menu, in the export form' );
		$this->assertStringContainsString( '<option value="">Raw values</option>', $menu[1] );
		$this->assertStringContainsString( '<option value="en_US" selected=\'selected\'>', $menu[1], 'the report\'s default' );
		$this->assertLessThan( strpos( $html, 'name="export_type"' ), strpos( $html, 'name="export_locale"' ), 'the menu comes before the buttons it applies to' );

		// An export with a choice the menu offers.
		add_filter( 'exports_reports_export_locales', array( $this, 'offer_italian' ) );

		$post = array(
			'exports_reports_export' => '1',
			'export_type'            => 'csv',
			'export_locale'          => 'it_IT',
			'_wpnonce'               => wp_create_nonce( 'exports-reports-export-request' ),
		);

		$admin = $screen( $report_id, $post );

		$this->assertSame( 'it_IT', $admin->export_locale );

		$_POST = $post;
		ob_start();
		$admin->export();
		ob_end_clean();
		$_POST = array();

		$file = EXPORTS_REPORTS_EXPORT_DIR . '/' . (string) $admin->exported_file;
		$csv  = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		wp_delete_file( $file );

		$this->assertStringContainsString( '"1";"1.234.567";"1.500,75";"07/03/2026";', $csv );
		$this->assertSame( 'it_IT', $screen( $report_id )->export_locale_shown(), 'remembered for this report' );
		$this->assertSame( '', $screen( $other_id )->export_locale_shown(), 'and for no other' );

		// Raw is a choice like the others, over the report's default.
		$post['export_locale'] = '';
		$_POST                 = $post;
		$admin                 = $screen( $report_id, $post );
		$_POST                 = $post;
		ob_start();
		$admin->export();
		ob_end_clean();
		$_POST = array();

		wp_delete_file( EXPORTS_REPORTS_EXPORT_DIR . '/' . (string) $admin->exported_file );

		$this->assertSame( '', $admin->export_locale );
		$this->assertSame( '', $screen( $report_id )->export_locale_shown() );

		// A value the menu does not offer is not taken.
		$post['export_locale'] = 'xx_XX';

		$this->assertSame( '', $screen( $report_id, $post )->export_locale );

		remove_filter( 'exports_reports_export_locales', array( $this, 'offer_italian' ) );

		wp_set_current_user( 0 );
	}

	/**
	 * Adds Italian to the Format menu, as a site with intl may add a language that is not installed.
	 *
	 * @param array<string, string> $locales The formats offered.
	 * @return array<string, string>
	 */
	public function offer_italian( $locales ) {
		$locales['it_IT'] = 'Italiano';

		return $locales;
	}

	/**
	 * The filters have the last word: one names the language of an export, the other the delimiter of a
	 * CSV file.
	 */
	public function test_the_filters_decide_the_language_and_the_delimiter() {
		$this->need_intl();

		$seen   = array();
		$german = static function ( $locale, $admin ) use ( &$seen ) {
			$seen[] = array( $locale, $admin instanceof Exports_Reports_Admin_UI );

			return 'de_DE';
		};
		$tab    = static function ( $delimiter, $locale ) {
			return 'de_DE' === $locale && ';' === $delimiter ? '|' : $delimiter;
		};

		add_filter( 'exports_reports_export_locale', $german, 10, 2 );
		add_filter( 'exports_reports_export_csv_delimiter', $tab, 10, 2 );

		list( $csv ) = $this->figures_csv();

		remove_filter( 'exports_reports_export_locale', $german, 10 );
		remove_filter( 'exports_reports_export_csv_delimiter', $tab, 10 );

		$this->assertSame( array( array( false, true ) ), $seen, 'asked once for the export, with false for the raw values' );
		$this->assertStringContainsString( '"1"|"1.234.567"|"1.500,75"|"07.03.2026"|', $csv );
	}

	/**
	 * The Settings page keeps the box for the site's date formats, and the reset unticks it.
	 */
	public function test_the_settings_page_saves_the_date_formats_box() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$save = function ( $post ) {
			$_POST    = array_merge( $post, array( '_wpnonce' => wp_create_nonce( 'exports-reports-settings' ) ) );
			$_REQUEST = $_POST;

			ob_start();
			exports_reports_settings();
			$html = (string) ob_get_clean();

			$_POST    = array();
			$_REQUEST = array();

			return $html;
		};

		$html = $save(
			array(
				'date_formats_set'  => '1',
				'site_date_formats' => '1',
			)
		);

		$this->assertTrue( Exports_Reports_Value_Format::uses_site_date_formats() );
		$this->assertSame( 1, preg_match( '/name="site_date_formats" value="1"\s+checked/', $html ) );

		$save( array( 'date_formats_set' => '1' ) );

		$this->assertFalse( Exports_Reports_Value_Format::uses_site_date_formats() );

		update_option( 'exports_reports_site_date_formats', 1 );

		$save( array( 'reset' => '1' ) );

		$this->assertFalse( Exports_Reports_Value_Format::uses_site_date_formats(), 'the reset puts the plugin\'s own format back' );

		wp_set_current_user( 0 );
	}

	/**
	 * An install from before 1.3.0 gets the column of a report's default format on the upgrade, empty:
	 * its reports export what they exported.
	 */
	public function test_the_upgrade_adds_the_default_format_column() {
		global $wpdb;

		$table = $wpdb->exports_reports_reports;

		$wpdb->query( "ALTER TABLE `{$table}` DROP COLUMN `export_locale`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->assertNotContains( 'export_locale', $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		update_option( 'exports_reports_version', '1.2.8' );
		exports_reports_install_upgrade();

		$this->assertContains( 'export_locale', $wpdb->get_col( "SHOW COLUMNS FROM `{$table}`" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$this->assertSame( '', (string) $wpdb->get_var( "SELECT `export_locale` FROM `{$table}` ORDER BY `id` LIMIT 1" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * What may be a locale: a language, a region, the variants WordPress adds; nothing with a path or a
	 * keyword in it.
	 */
	public function test_what_counts_as_a_locale() {
		foreach ( array( 'it_IT', 'de_DE_formal', 'ckb', 'pt_PT_ao90', 'en' ) as $locale ) {
			$this->assertSame( $locale, Exports_Reports_Value_Format::sanitize_locale( $locale ) );
		}

		foreach ( array( '', 'IT', 'it-IT', 'it_IT@numbers=arab', '../it_IT', 'it IT', array( 'it_IT' ), false, 'it_' . str_repeat( 'x', 40 ) ) as $locale ) {
			$this->assertSame( '', Exports_Reports_Value_Format::sanitize_locale( $locale ) );
		}

		$this->assertSame( '', Exports_Reports_Value_Format::locale( 'nonsense!' )->language() );
		$this->assertArrayHasKey( 'en_US', Exports_Reports_Value_Format::locales(), 'the language WordPress ships in is always there' );
	}
}
