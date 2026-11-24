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
 * An export formatted for a language (the Format menu, a REST request's locale) holds typed cells: a
 * number or a decimal as a number, a date, a time or a date and time as the spreadsheet's own date
 * value. The file is the same whatever the language: the spreadsheet of whoever opens it shows the
 * separators and the date order of their own region.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
 * @since 1.2.9 A value that starts with "=" is a text cell, not a formula; a number column is a numeric cell
 *              holding the whole value.
 * @since 1.3.0 Typed cells in an export formatted for a language.
 */
class Exports_Reports_Exporter_XLSX extends Exports_Reports_Exporter {

	/**
	 * The cell format of each column type in an export formatted for a language. The three date formats
	 * become the spreadsheet's built-in ones (Exports_Reports_XLSX_Writer::BUILT_IN_FORMATS).
	 *
	 * @since 1.3.0
	 *
	 * @var array<string, string>
	 */
	const TYPED_FORMATS = array(
		'number'   => '#,##0',
		'decimal'  => '#,##0.00',
		'date'     => 'm/d/yyyy',
		'datetime' => 'm/d/yyyy h:mm',
		'time'     => 'h:mm:ss',
	);

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
		$format               = $this->admin->export_format();
		$typed                = '' !== $format->language();
		$header               = array();
		$numbers              = array();
		$types                = array();

		foreach ( $columns as $key => $attributes ) {
			// A column the plugin formats itself can be a typed cell; one a display function writes is text.
			$type  = empty( $attributes['type'] ) ? '' : (string) $attributes['type'];
			$plain = false === $attributes['custom_display'] && empty( $attributes['custom_relate'] );
			$cell  = 'string';

			if ( $typed && $plain && isset( self::TYPED_FORMATS[ $type ] ) ) {
				$cell          = self::TYPED_FORMATS[ $type ];
				$types[ $key ] = $type;
			} elseif ( $plain && 'number' === $type ) {
				// The general format types a cell by its value.
				$cell            = 'GENERAL';
				$numbers[ $key ] = true;
			}

			$header[ $attributes['label'] ] = $cell;
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

			foreach ( $this->admin->export_row( $item, $columns, $format ) as $key => $value ) {
				$stored = isset( $item[ $key ] ) ? $item[ $key ] : null;

				if ( isset( $numbers[ $key ] ) ) {
					$number = self::whole_number( $stored );

					if ( null !== $number ) {
						$row[] = $number;
						continue;
					}
				}

				if ( isset( $types[ $key ] ) ) {
					$cell = self::typed_value( $stored, $types[ $key ], $value );

					if ( null !== $cell ) {
						$row[] = $cell;
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
	 * The value of a typed cell: the number of a number or decimal column as the row holds it, to its last
	 * digit (the cell's format rounds what is shown, not what is kept), and the date of a date, time or
	 * datetime column as the serial number spreadsheets count dates in: days since the end of 1899, the
	 * time of day as the fraction. The date is taken as it is stored; no time zone is applied.
	 *
	 * @since 1.3.0
	 *
	 * @param mixed  $stored    The value from the row.
	 * @param string $type      number, decimal, date, time or datetime.
	 * @param mixed  $formatted The value as the export's format writes it.
	 *
	 * @return string|int|float|null Null when the cell is text: a value that is no number or no date the
	 *                               spreadsheet can count (N/A, a date before March 1900).
	 */
	protected static function typed_value( $stored, $type, $formatted ) {
		if ( 'number' === $type || 'decimal' === $type ) {
			if ( null === $stored ) {
				return 0;
			}

			if ( is_int( $stored ) || ( is_string( $stored ) && 1 === preg_match( '/^-?(0|[1-9][0-9]*)(\.[0-9]+)?$/', $stored ) ) ) {
				return $stored;
			}

			return is_numeric( $stored ) ? (float) $stored : null;
		}

		// What the screen shows as N/A (an empty date, a time of midnight) is text here as well.
		if ( ! is_string( $stored ) || '' === trim( $stored ) || 'N/A' === $formatted ) {
			return null;
		}

		try {
			$seconds = ( new DateTimeImmutable( $stored, new DateTimeZone( 'UTC' ) ) )->getTimestamp();
		} catch ( Exception $error ) {
			return null;
		}

		$days = (int) floor( $seconds / DAY_IN_SECONDS );
		$time = ( $seconds - $days * DAY_IN_SECONDS ) / DAY_IN_SECONDS;

		if ( 'time' === $type ) {
			return (float) $time;
		}

		// 25569 is 1970-01-01. Before 1900-03-01 the spreadsheets' count is off by the leap day 1900 never had.
		$serial = $days + 25569;

		if ( $serial < 61 ) {
			return null;
		}

		return 'date' === $type ? $serial : (float) ( $serial + $time );
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
