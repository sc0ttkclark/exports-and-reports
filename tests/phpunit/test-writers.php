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

	public function test_the_default_pdf_engine_follows_the_php_version() {
		$this->assertSame( PHP_VERSION_ID >= 80200 ? 'tc-lib-pdf' : 'tcpdf', Exports_Reports_Admin_UI::pdf_engine() );
	}
}
