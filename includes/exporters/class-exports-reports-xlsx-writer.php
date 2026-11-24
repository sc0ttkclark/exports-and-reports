<?php
/**
 * The spreadsheet writer of the XLSX export.
 *
 * @package Exports_And_Reports
 * @since   1.2.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * XLSXWriter with text kept as text. The library writes any string that starts with "=" as a formula
 * cell, before it looks at the column's type, so a value a report merely stores (a title, a name a
 * visitor typed) would run in the spreadsheet of whoever opens the export. Here such a string is an
 * inline string like every other text cell: the cell shows the value as stored, nothing is added to it
 * and nothing is evaluated. The other leading characters spreadsheets act on in a CSV file (+, -, @)
 * need nothing: an inline string is never evaluated.
 *
 * @since 1.2.9
 */
class Exports_Reports_XLSX_Writer extends ExportsReports__Prefixed__XLSXWriter {

	/**
	 * Whether a string that starts with "=" is written as a formula, as the library does on its own:
	 * the exports_reports_export_escape_formulas filter returning false sets it, for a site that writes
	 * formulas into its reports on purpose.
	 *
	 * @var bool
	 */
	public $formulas = false;

	/**
	 * Writes one cell.
	 *
	 * @param ExportsReports__Prefixed__XLSXWriter_BuffererWriter $file            The worksheet's file.
	 * @param int                                                 $row_number      The row, from 0.
	 * @param int                                                 $column_number   The column, from 0.
	 * @param mixed                                               $value           The value.
	 * @param string                                              $num_format_type The column's type, as the library names it.
	 * @param int                                                 $cell_style_idx  The cell's style.
	 */
	protected function writeCell( ExportsReports__Prefixed__XLSXWriter_BuffererWriter &$file, $row_number, $column_number, $value, $num_format_type, $cell_style_idx ) {
		if ( ! $this->formulas && is_string( $value ) && '' !== $value && '=' === $value[0] ) {
			$file->write( '<c r="' . self::xlsCell( $row_number, $column_number ) . '" s="' . $cell_style_idx . '" t="inlineStr"><is><t>' . self::xmlspecialchars( $value ) . '</t></is></c>' );

			return;
		}

		parent::writeCell( $file, $row_number, $column_number, $value, $num_format_type, $cell_style_idx );
	}
}
