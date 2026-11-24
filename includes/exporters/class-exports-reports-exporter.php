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
	 * Whether a value a spreadsheet would evaluate as a formula is written as text: true unless the
	 * exports_reports_export_escape_formulas filter says otherwise. The delimited exports and the XLSX
	 * export ask here, so one filter decides for every spreadsheet format. Ask before an unbuffered
	 * result is open: a callback may query the database.
	 *
	 * @since 1.2.9 Out of Exports_Reports_Exporter_Delimited::write(), for the XLSX export as well.
	 *
	 * @return bool
	 */
	protected function escapes_formulas() {
		/**
		 * Filters whether values a spreadsheet would evaluate as formulas are written as text, in the CSV,
		 * TSV, TXT and XLSX exports.
		 *
		 * @since 1.0.0 The arguments one by one; the array form stays with wp_admin_ui_export_escape_formulas.
		 * @since 1.2.9 The XLSX export follows it too.
		 *
		 * @param bool                    $escape True by default.
		 * @param Exports_Reports_Admin_UI $admin  The screen.
		 */
		$escape = apply_filters( 'exports_reports_export_escape_formulas', true, $this->admin );

		return false !== $this->admin->legacy_hook( 'export_escape_formulas', array( $escape ) );
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
require_once __DIR__ . '/class-exports-reports-xlsx-writer.php';
require_once __DIR__ . '/class-exports-reports-exporter-xlsx.php';
require_once __DIR__ . '/class-exports-reports-exporter-json.php';
require_once __DIR__ . '/class-exports-reports-exporter-pdf.php';
