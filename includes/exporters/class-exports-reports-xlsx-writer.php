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
	 * The date formats of the export formatted for a language, as the library keeps them (it escapes the
	 * space), each with the number of the spreadsheet's built-in format that shows in the regional
	 * format of whoever opens the file.
	 *
	 * @since 1.3.0
	 *
	 * @var array<string, int>
	 */
	const BUILT_IN_FORMATS = array(
		'm/d/yyyy'        => 14,
		'm/d/yyyy\\ h:mm' => 22,
		'h:mm:ss'         => 21,
	);

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

		// A column with a number or a date format: a number is a numeric cell, and text (N/A, a value the
		// column's type cannot read) is a text cell, where the library would write it into a numeric one and
		// leave a file the spreadsheet repairs. A date arrives as its serial number (see the XLSX exporter).
		if ( in_array( $num_format_type, array( 'n_numeric', 'n_date', 'n_datetime' ), true ) && ( is_int( $value ) || is_float( $value ) || ( is_string( $value ) && '' !== $value ) ) ) {
			$cell = self::xlsCell( $row_number, $column_number );

			if ( is_float( $value ) ) {
				// Digits and a point, whatever the locale PHP runs in and however small the number.
				$value = rtrim( rtrim( number_format( $value, 10, '.', '' ), '0' ), '.' );
			} elseif ( is_string( $value ) && ( 'n_numeric' !== $num_format_type || 1 !== preg_match( '/^-?(0|[1-9][0-9]*)(\.[0-9]+)?$/', $value ) ) ) {
				$file->write( '<c r="' . $cell . '" s="' . $cell_style_idx . '" t="inlineStr"><is><t>' . self::xmlspecialchars( $value ) . '</t></is></c>' );

				return;
			}

			$file->write( '<c r="' . $cell . '" s="' . $cell_style_idx . '" t="n"><v>' . self::xmlspecialchars( (string) $value ) . '</v></c>' );

			return;
		}

		parent::writeCell( $file, $row_number, $column_number, $value, $num_format_type, $cell_style_idx );
	}

	/**
	 * The styles of the workbook, with the date formats pointed at the spreadsheet's built-in ones. The
	 * library writes every number format as a custom one, which shows the same in every country; the
	 * built-in short date (14), date and time (22) and time (21) are the ones Excel, LibreOffice and
	 * Google Sheets show in the regional format of whoever opens the file.
	 *
	 * @since 1.3.0
	 *
	 * @return string The name of the temporary file that holds the styles.
	 */
	protected function writeStylesXML() {
		$file = parent::writeStylesXML();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- the library's own temporary file, which it writes and reads back with fopen() itself.
		$styles = (string) file_get_contents( $file );

		if ( preg_match_all( '/<numFmt numFmtId="(\d+)" formatCode="([^"]*)" \/>/', $styles, $formats, PREG_SET_ORDER ) ) {
			foreach ( $formats as $format ) {
				if ( isset( self::BUILT_IN_FORMATS[ $format[2] ] ) ) {
					$styles = str_replace( 'numFmtId="' . $format[1] . '" xfId="0"', 'numFmtId="' . self::BUILT_IN_FORMATS[ $format[2] ] . '" xfId="0"', $styles );
				}
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- the same temporary file.
			file_put_contents( $file, $styles );
		}

		return $file;
	}
}
