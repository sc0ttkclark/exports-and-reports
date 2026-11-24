<?php
/**
 * The XLSX export.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes a spreadsheet with the column labels as a header row; cells are text unless the column is a number.
 * Text stays text whatever it starts with (Exports_Reports_XLSX_Writer), unless the
 * exports_reports_export_escape_formulas filter turns that off.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
 * @since 1.2.9 A value that starts with "=" is a text cell, not a formula; a number column is a numeric cell
 *              holding the whole value.
 */
class Exports_Reports_Exporter_XLSX extends Exports_Reports_Exporter {

	/**
	 * Writes the file from the rows the screen loaded.
	 *
	 * @return string The file name.
	 */
	public function write() {
		/** @var WP_Filesystem_Base $wp_filesystem */ global $wp_filesystem;

		$export_file          = $this->file();
		$export_file_location = $this->path( $export_file );
		$columns              = $this->admin->exported_columns();
		$header               = array();
		$numbers              = array();

		foreach ( $columns as $key => $attributes ) {
			// A number column the plugin formats itself is a numeric cell (the general format types a cell by its
			// value); the others, and a number column a display function writes, are text.
			$is_number = ! empty( $attributes['type'] ) && 'number' === $attributes['type'] && false === $attributes['custom_display'];

			$header[ $attributes['label'] ] = $is_number ? 'GENERAL' : 'string';

			if ( $is_number ) {
				$numbers[ $key ] = true;
			}
		}

		$writer           = new Exports_Reports_XLSX_Writer();
		$writer->formulas = ! $this->escapes_formulas();

		$writer->writeSheetHeader(
			'Sheet1',
			$header,
			array(
				'fill'   => '#eee',
				'halign' => 'center',
				'border' => 'left,right,top,bottom',
			)
		);

		foreach ( $this->admin->full_data as $item ) {
			$row = array();

			foreach ( $this->admin->export_row( $item, $columns ) as $key => $value ) {
				if ( isset( $numbers[ $key ] ) ) {
					$number = self::whole_number( isset( $item[ $key ] ) ? $item[ $key ] : null );

					if ( null !== $number ) {
						$row[] = $number;
						continue;
					}
				}

				$row[] = wp_strip_all_tags( (string) $value );
			}

			$writer->writeSheetRow( 'Sheet1', $row );
		}

		$wp_filesystem->put_contents( $export_file_location, (string) $writer->writeToString() );

		return $export_file;
	}

	/**
	 * The value of a number column as the digits a numeric cell holds, read from the row as the query
	 * returned it: the screen's formatted value carries thousands separators, which are no part of the
	 * number (casting "1,400" gave 1). An integer is kept digit for digit, whatever its size; a fraction
	 * is rounded as the screen rounds it.
	 *
	 * @since 1.2.9
	 *
	 * @param mixed $value The value from the row.
	 *
	 * @return string|null The digits, or null when the value is no number (the cell is text then).
	 */
	protected static function whole_number( $value ) {
		if ( is_int( $value ) || ( is_string( $value ) && 1 === preg_match( '/^-?(0|[1-9][0-9]*)$/', $value ) ) ) {
			return (string) $value;
		}

		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$digits = number_format( (float) $value, 0, '', '' );

		return '-0' === $digits ? '0' : $digits;
	}
}
