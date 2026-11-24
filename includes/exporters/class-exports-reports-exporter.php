<?php
/**
 * The export formats: one class per format behind a shared base.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes an export of a report screen into the export directory. Exports_Reports_Admin_UI::export()
 * loads the rows, checks the directory and the request, then hands the screen to the exporter of the
 * requested type and shows the download once the file is written.
 *
 * @since 1.0.0
 */
abstract class Exports_Reports_Exporter {

	/**
	 * The screen whose rows are exported.
	 *
	 * @var Exports_Reports_Admin_UI
	 */
	protected $admin;

	/**
	 * @param Exports_Reports_Admin_UI $admin The screen.
	 */
	public function __construct( Exports_Reports_Admin_UI $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Writes the file.
	 *
	 * @return string|false The file name in the export directory, or false when nothing was written (the
	 *                      exporter has shown the error through the screen).
	 */
	abstract public function write();

	/**
	 * The file extension, which is also the format named in the download message.
	 *
	 * @return string
	 */
	public function extension() {
		return $this->admin->export_type;
	}

	/**
	 * The exporter of an export type.
	 *
	 * @param string                   $type  csv, tsv, pipe, xml, xlsx, json or pdf.
	 * @param Exports_Reports_Admin_UI $admin The screen.
	 *
	 * @return Exports_Reports_Exporter|null Null for a type the plugin does not write.
	 */
	public static function create( $type, Exports_Reports_Admin_UI $admin ) {
		if ( self::streams( $type ) ) {
			return new Exports_Reports_Exporter_Delimited( $admin );
		}

		switch ( $type ) {
			case 'xml':
				return new Exports_Reports_Exporter_XML( $admin );
			case 'xlsx':
				return new Exports_Reports_Exporter_XLSX( $admin );
			case 'json':
				return new Exports_Reports_Exporter_JSON( $admin );
			case 'pdf':
				return new Exports_Reports_Exporter_PDF( $admin );
		}

		return null;
	}

	/**
	 * Whether an export type is a delimited one, which can read its rows from an unbuffered result.
	 *
	 * @param string $type The export type.
	 *
	 * @return bool
	 */
	public static function streams( $type ) {
		return isset( Exports_Reports_Exporter_Delimited::EXTENSIONS[ $type ] );
	}

	/**
	 * The name of the file to write, through the screen's exports_reports_export_file filter.
	 *
	 * @return string
	 */
	protected function file() {
		return $this->admin->export_file( $this->extension() );
	}

	/**
	 * The path of a file in the export directory.
	 *
	 * @param string $file The file name.
	 *
	 * @return string
	 */
	protected function path( $file ) {
		return EXPORTS_REPORTS_EXPORT_DIR . '/' . $file;
	}
}

require_once __DIR__ . '/class-exports-reports-exporter-delimited.php';
require_once __DIR__ . '/class-exports-reports-exporter-xml.php';
require_once __DIR__ . '/class-exports-reports-exporter-xlsx.php';
require_once __DIR__ . '/class-exports-reports-exporter-json.php';
require_once __DIR__ . '/class-exports-reports-exporter-pdf.php';
