<?php
/**
 * The PDF export.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the report as a PDF with tc-lib-pdf (PHP 8.2 and later) or TCPDF, unless a callback on the
 * exports_reports_export_pdf_report_file filter writes the file itself.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
 */
class Exports_Reports_Exporter_PDF extends Exports_Reports_Exporter {

	/**
	 * The PDF engine: tc-lib-pdf on PHP 8.2 and later, TCPDF below, the exports_reports_export_pdf_engine
	 * filter deciding where both run.
	 *
	 * @return string 'tc-lib-pdf' or 'tcpdf'.
	 */
	public static function engine() {
		/**
		 * Filters the PDF engine.
		 *
		 * @param string $engine 'tc-lib-pdf' on PHP 8.2 and later, 'tcpdf' below.
		 */
		$engine = apply_filters( 'exports_reports_export_pdf_engine', PHP_VERSION_ID >= 80200 ? 'tc-lib-pdf' : 'tcpdf' );
		$engine = apply_filters_deprecated( 'wp_admin_ui_export_pdf_engine', array( $engine ), '0.9.5', 'exports_reports_export_pdf_engine' );

		return ( 'tcpdf' === $engine || PHP_VERSION_ID < 80200 ) ? 'tcpdf' : 'tc-lib-pdf';
	}

	/**
	 * Writes the file.
	 *
	 * @return string The file name.
	 */
	public function write() {
		if ( 'tcpdf' === self::engine() ) {
			require_once __DIR__ . '/class-exports-reports-admin-ui-export-pdf-legacy.php';
			$pdf_class = 'Exports_Reports_Admin_UI_Export_PDF_Legacy';
		} else {
			require_once __DIR__ . '/class-exports-reports-admin-ui-export-pdf.php';
			$pdf_class = 'Exports_Reports_Admin_UI_Export_PDF';
		}

		/**
		 * Filters the PDF export before the plugin renders it: a callback that writes the file into the
		 * export directory itself returns its name.
		 *
		 * @param string|null              $export_file Null: the plugin renders the report.
		 * @param Exports_Reports_Admin_UI $admin       The report.
		 */
		$export_file = apply_filters( 'exports_reports_export_pdf_report_file', null, $this->admin );
		$export_file = apply_filters_deprecated( 'wp_admin_ui_export_pdf_report_file', array( $export_file, $this->admin ), '0.9.5', 'exports_reports_export_pdf_report_file' );

		if ( ! $export_file ) {
			$export_file = $pdf_class::CreateReport( $this->admin );
		}

		return $export_file;
	}
}
