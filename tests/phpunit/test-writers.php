<?php
/**
 * The export writers: XLSX, XML, JSON and PDF, with the labels and rows the report shows.
 *
 * @package Exports_And_Reports
 */

class Tests_Writers extends Exports_Reports_Test_Case {

	/**
	 * Writes an export of the first two rows and returns [ path, contents ]; the caller removes the file.
	 *
	 * @param string $type The export type.
	 * @return array{0: string, 1: string}
	 */
	protected function write_export( $type ) {
		list( $writer ) = $this->run_report( 'SELECT id, channel, title FROM ' . self::videos() . ' WHERE id <= 2 ORDER BY id', 'Writers' );

		$writer->action      = 'export';
		$writer->export_type = $type;

		$this->export_from_link( $writer );

		$file = (string) $writer->exported_file;

		$this->assertNotSame( '', $file, 'a file was written' );
		$this->assertStringEndsWith( '.' . $type, $file );

		$path     = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
		$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		$this->assertNotSame( '', $contents );

		return array( $path, $contents );
	}

	/**
	 * A zip with one worksheet; XLSXWriter writes the labels and the values as inline strings.
	 */
	public function test_xlsx_export_writes_the_labels_and_the_rows() {
		list( $path, $contents ) = $this->write_export( 'xlsx' );

		$zip   = new ZipArchive();
		$sheet = '';

		if ( true === $zip->open( $path ) ) {
			$sheet = (string) $zip->getFromName( 'xl/worksheets/sheet1.xml' );
			$zip->close();
		}

		wp_delete_file( $path );

		$this->assertStringStartsWith( "PK\x03\x04", $contents );
		$this->assertSame( 3, substr_count( $sheet, '<row ' ), 'the labels and two rows' );
		$this->assertStringContainsString( '<t>Id</t>', $sheet );
		$this->assertStringContainsString( '<t>Channel</t>', $sheet );
		$this->assertStringContainsString( '<t>Video 1</t>', $sheet );
		$this->assertStringContainsString( '<t>gamma</t>', $sheet );
	}

	/**
	 * Writes the XLSX export of a report definition and returns the XML of its worksheet; the file is removed.
	 *
	 * @param string $sql     The report SQL.
	 * @param array  $options Options added to the defaults (columns).
	 * @return string
	 */
	protected function xlsx_sheet( $sql, $options = array() ) {
		list( $writer ) = $this->run_report( $sql, 'Writers XLSX', false, $options );

		$writer->action      = 'export';
		$writer->export_type = 'xlsx';

		$this->export_from_link( $writer );

		$file = (string) $writer->exported_file;

		$this->assertNotSame( '', $file, 'a file was written' );

		$path  = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
		$zip   = new ZipArchive();
		$sheet = '';

		if ( true === $zip->open( $path ) ) {
			$sheet = (string) $zip->getFromName( 'xl/worksheets/sheet1.xml' );
			$zip->close();
		}

		wp_delete_file( $path );

		$this->assertNotSame( '', $sheet, 'the workbook has its worksheet' );

		return $sheet;
	}

	/**
	 * The seeded formula-like title (row 7) is a text cell: the worksheet holds no formula, and the text is
	 * the title as stored.
	 */
	public function test_xlsx_export_writes_a_formula_like_value_as_text() {
		$sheet = $this->xlsx_sheet( 'SELECT id, channel, title FROM ' . self::videos() . ' WHERE id = 7' );

		$this->assertStringNotContainsString( '<f>', $sheet, 'no cell is a formula' );
		$this->assertStringContainsString( 't="inlineStr"><is><t>=1+1</t></is>', $sheet, 'the title is text, as stored' );
	}

	/**
	 * Every leading character a spreadsheet acts on stays text, in a value and in a label; a number column
	 * keeps its value.
	 */
	public function test_xlsx_export_keeps_every_formula_trigger_as_text_and_numbers_as_numbers() {
		$sheet = $this->xlsx_sheet(
			"SELECT id, '=HYPERLINK(\"https://example.invalid\",\"x\")' AS equals, '+1+1' AS plus, '-1+1' AS minus, '@SUM(1,1)' AS at_sign FROM " . self::videos() . ' WHERE id = 7',
			array(
				'columns' => array(
					'id'      => array(
						'label' => 'Id',
						'type'  => 'number',
					),
					'equals'  => array( 'label' => '=1+1' ),
					'plus'    => array( 'label' => 'Plus' ),
					'minus'   => array( 'label' => 'Minus' ),
					'at_sign' => array( 'label' => 'At' ),
				),
			)
		);

		$this->assertStringNotContainsString( '<f>', $sheet, 'no cell is a formula' );
		$this->assertStringContainsString( '<t>=1+1</t>', $sheet, 'a label that starts with = is text' );
		$this->assertStringContainsString( '<t>=HYPERLINK(&quot;https://example.invalid&quot;,&quot;x&quot;)</t>', $sheet );
		$this->assertStringContainsString( '<t>+1+1</t>', $sheet );
		$this->assertStringContainsString( '<t>-1+1</t>', $sheet );
		$this->assertStringContainsString( '<t>@SUM(1,1)</t>', $sheet );
		$this->assertSame( 1, preg_match( '#<c r="A2"[^>]*>(?:<is><t>|<v>)7(?:</t></is>|</v>)</c>#', $sheet ), 'the number column keeps its value' );
	}

	/**
	 * The filter that turns the formula handling of the delimited exports off turns it off here as well:
	 * a site that writes formulas into its reports on purpose gets them.
	 */
	public function test_xlsx_export_writes_formulas_when_the_filter_turns_the_escaping_off() {
		add_filter( 'exports_reports_export_escape_formulas', '__return_false' );

		$sheet = $this->xlsx_sheet( 'SELECT id, channel, title FROM ' . self::videos() . ' WHERE id = 7' );

		remove_filter( 'exports_reports_export_escape_formulas', '__return_false' );

		$this->assertStringContainsString( '<f>1+1</f>', $sheet );
	}

	/**
	 * A number column is a numeric cell holding the whole value: the thousands separator the screen shows
	 * is not part of it, a fraction is rounded as on screen, and a value that is no number stays text
	 * (formatting one as a number stops PHP 8).
	 */
	public function test_xlsx_export_writes_a_number_column_as_the_whole_number() {
		$sheet = $this->xlsx_sheet(
			'SELECT id, id * 200 AS thousands, id * 200000 AS millions, 0 - id * 1000 AS minus, revenue AS rounded, title AS words, channel AS grade FROM ' . self::videos() . ' WHERE id = 7',
			array(
				'columns' => array(
					'id'        => array( 'label' => 'Id' ),
					'thousands' => array(
						'label' => 'Thousands',
						'type'  => 'number',
					),
					'millions'  => array(
						'label' => 'Millions',
						'type'  => 'number',
					),
					'minus'     => array(
						'label' => 'Minus',
						'type'  => 'number',
					),
					'rounded'   => array(
						'label' => 'Rounded',
						'type'  => 'number',
					),
					'words'     => array(
						'label' => 'Words',
						'type'  => 'number',
					),
					'grade'     => array(
						'label' => 'Grade',
						'type'  => 'decimal',
					),
				),
			)
		);

		$this->assertStringContainsString( '<c r="A2" s="1" t="inlineStr"><is><t>7</t></is></c>', $sheet, 'a text column is text' );
		$this->assertSame( 1, preg_match( '#<c r="B2" s="\d+" t="n"><v>1400</v></c>#', $sheet ), 'one thousand four hundred, not 1' );
		$this->assertSame( 1, preg_match( '#<c r="C2" s="\d+" t="n"><v>1400000</v></c>#', $sheet ) );
		$this->assertSame( 1, preg_match( '#<c r="D2" s="\d+" t="n"><v>-7000</v></c>#', $sheet ) );
		$this->assertSame( 1, preg_match( '#<c r="E2" s="\d+" t="n"><v>11</v></c>#', $sheet ), '10.50 rounds as the screen rounds it' );
		$this->assertSame( 1, preg_match( '#<c r="F2" s="\d+" t="inlineStr"><is><t>=1\+1</t></is></c>#', $sheet ), 'text in a number column stays text, and no formula' );
		$this->assertStringContainsString( '<c r="G2" s="1" t="inlineStr"><is><t>beta</t></is></c>', $sheet, 'text in a decimal column is shown as it is' );
	}

	/**
	 * One function decides what a number is, and every place that formats a number or decimal column
	 * asks it: the list and the exports through field_value(), and both PDF engines. A value that is no
	 * number reads as itself in each of them; NULL counts as 0, as it always has.
	 */
	public function test_a_number_reads_the_same_wherever_it_is_formatted() {
		$this->assertSame( '1,401', Exports_Reports_Value_Format::raw()->number( '1400.6' ) );
		$this->assertSame( '1401', Exports_Reports_Value_Format::raw()->number( 1400.6, 0, false ) );
		$this->assertSame( '1,234.50', Exports_Reports_Value_Format::raw()->number( '1234.5', 2 ) );
		$this->assertSame( '0', Exports_Reports_Value_Format::raw()->number( null ) );
		$this->assertSame( '', Exports_Reports_Value_Format::raw()->number( '' ) );
		$this->assertSame( 'n/a', Exports_Reports_Value_Format::raw()->number( 'n/a', 2 ) );

		$report  = $this->report( '', 'Numbers' );
		$engines = array( 'Exports_Reports_Admin_UI_Export_PDF_Legacy' );

		require_once EXPORTS_REPORTS_DIR . 'includes/exporters/class-exports-reports-admin-ui-export-pdf-legacy.php';

		// The tc-lib-pdf engine loads on PHP 8.2 and later only.
		if ( PHP_VERSION_ID >= 80200 ) {
			require_once EXPORTS_REPORTS_DIR . 'includes/exporters/class-exports-reports-admin-ui-export-pdf.php';

			$engines[] = 'Exports_Reports_Admin_UI_Export_PDF';
		}

		$values = array(
			'1400.6' => array( '1,401', '1,400.60', '1401' ),
			'n/a'    => array( 'n/a', 'n/a', 'n/a' ),
			''       => array( '', '', '' ),
		);

		foreach ( $values as $value => $expect ) {
			$value = (string) $value;

			$this->assertSame( $expect[0], $report->field_value( $value, 'amount', $report->setup_column( 'amount', array( 'type' => 'number' ) ) ), 'the list, a number: ' . $value );
			$this->assertSame( $expect[1], $report->field_value( $value, 'amount', $report->setup_column( 'amount', array( 'type' => 'decimal' ) ) ), 'the list, a decimal: ' . $value );

			foreach ( $engines as $engine ) {
				$this->assertSame( $expect[2], call_user_func( array( $engine, 'format_field' ), 'number', $value ), $engine . ', a number: ' . $value );
				$this->assertSame( $expect[1], call_user_func( array( $engine, 'format_field' ), 'decimal', $value ), $engine . ', a decimal: ' . $value );
			}
		}
	}

	public function test_xml_export_writes_the_labels_and_the_rows() {
		list( $path, $contents ) = $this->write_export( 'xml' );

		wp_delete_file( $path );

		$xml = simplexml_load_string( $contents );

		$this->assertInstanceOf( 'SimpleXMLElement', $xml );
		$this->assertSame( 2, (int) $xml['count'] );
		$this->assertCount( 2, $xml->item );
		$this->assertSame( 'beta', (string) $xml->item[0]->channel );
		$this->assertSame( 'Video 2', (string) $xml->item[1]->title );
	}

	public function test_json_export_writes_the_labels_and_the_rows() {
		list( $path, $contents ) = $this->write_export( 'json' );

		wp_delete_file( $path );

		$json = json_decode( $contents, true );

		$this->assertIsArray( $json );
		$this->assertSame( 2, $json['items']['count'] );
		$this->assertCount( 2, $json['items']['item'] );
		$this->assertSame( 'Video 1', $json['items']['item'][0]['title'] );
		$this->assertSame( 'gamma', $json['items']['item'][1]['channel'] );
	}

	/**
	 * A value holding a CDATA terminator and a column whose alias no XML name allows still parse: the
	 * terminator is split across two sections and the element opens with an underscore.
	 */
	public function test_xml_export_survives_cdata_terminators_and_odd_column_names() {
		list( $writer ) = $this->run_report( "SELECT id, CONCAT( title, ']]>', channel ) AS `2nd title` FROM " . self::videos() . ' WHERE id = 1', 'Writers CDATA' );

		$writer->action      = 'export';
		$writer->export_type = 'xml';

		$this->export_from_link( $writer );

		$file = (string) $writer->exported_file;

		$this->assertNotSame( '', $file, 'a file was written' );

		$path     = EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
		$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file the test wrote

		wp_delete_file( $path );

		$xml = simplexml_load_string( $contents );

		$this->assertInstanceOf( 'SimpleXMLElement', $xml );
		$this->assertSame( 'Video 1]]>beta', (string) $xml->item[0]->{'_2nd_title'} );
	}

	/**
	 * A file the viewer can open, with the pages the rows need: tc-lib-pdf on PHP 8.2 and later, TCPDF below.
	 */
	public function test_pdf_export_with_the_default_engine() {
		$report = $this->report( 'SELECT id, channel, title, revenue FROM ' . self::videos() . ' ORDER BY id', 'PDF export' );

		$this->assert_pdf_export( $this->pdf_export( $report ), PHP_VERSION_ID >= 80200 ? 8 : 6 );
	}

	/**
	 * The HTML the TCPDF engine hands to TCPDF's writeHTML() for a report: the engine's own table, as
	 * its content() builds it.
	 *
	 * @param Exports_Reports_Admin_UI $report The report, get_data( true ) already run.
	 * @return string
	 */
	protected function tcpdf_html( $report ) {
		require_once EXPORTS_REPORTS_DIR . 'includes/exporters/class-exports-reports-admin-ui-export-pdf-legacy.php';

		$content = new ReflectionMethod( 'Exports_Reports_Admin_UI_Export_PDF_Legacy', 'content' );

		if ( PHP_VERSION_ID < 80100 ) {
			$content->setAccessible( true );
		}

		return (string) $content->invoke( null, $report );
	}

	/**
	 * Markup a table stores is text to the TCPDF engine, as it is to the tc-lib-pdf engine: no tag of it
	 * reaches writeHTML(), which would fetch the address of an image (or read a local path) on the server.
	 * The file is still written.
	 */
	public function test_the_tcpdf_engine_prints_stored_markup_as_text() {
		global $wpdb;

		$stored = 'Seen <img src="http://example.invalid/x.png"> at <a href="https://example.invalid/">AT&T</a> when 1 < 2';
		$sql    = (string) $wpdb->prepare( 'SELECT id, %s AS title, channel FROM ' . self::videos() . ' WHERE id <= 2 ORDER BY id', $stored ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- the test's own table.

		list( $report ) = $this->run_report( $sql, 'TCPDF markup' );

		$html = $this->tcpdf_html( $report );

		$this->assertStringNotContainsString( '<img', $html, 'no image reaches TCPDF' );
		$this->assertStringNotContainsString( '<a ', $html, 'no link reaches TCPDF' );
		$this->assertStringNotContainsString( 'example.invalid', $html, 'the addresses went with their tags' );
		$this->assertStringContainsString( 'Seen  at AT&amp;T when 1 &lt; 2', $html, 'the text is kept, escaped' );
		$this->assertStringContainsString( '>beta<', $html, 'plain values are unchanged' );

		add_filter( 'exports_reports_export_pdf_engine', array( $this, 'tcpdf_engine' ) );

		$pdf = $this->pdf_export( $this->report( $sql, 'TCPDF markup' ) );

		remove_filter( 'exports_reports_export_pdf_engine', array( $this, 'tcpdf_engine' ) );

		$this->assertStringStartsWith( '%PDF-', $pdf['contents'], 'the export is written' );
		$this->assertSame( 6, $pdf['producer'], 'by TCPDF' );
	}

	/**
	 * What a display function returns is the administrator's own markup: the TCPDF engine keeps it.
	 */
	public function test_the_tcpdf_engine_keeps_the_markup_of_a_display_function() {
		list( $report ) = $this->run_report(
			'SELECT id, channel FROM ' . self::videos() . ' WHERE id = 1',
			'TCPDF callback',
			false,
			array(
				'columns' => array(
					'id'      => array( 'label' => 'Id' ),
					'channel' => array(
						'label'          => 'Channel',
						'custom_display' => 'exports_reports_tests_emphasis',
					),
				),
			)
		);

		$this->assertStringContainsString( '<em>beta</em>', $this->tcpdf_html( $report ) );
	}

	/**
	 * The exports_reports_export_pdf_engine callback that selects TCPDF.
	 *
	 * @return string
	 */
	public function tcpdf_engine() {
		return 'tcpdf';
	}

	public function test_the_default_pdf_engine_follows_the_php_version() {
		$this->assertSame( PHP_VERSION_ID >= 80200 ? 'tc-lib-pdf' : 'tcpdf', Exports_Reports_Admin_UI::pdf_engine() );
	}
}
