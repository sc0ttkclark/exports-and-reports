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
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
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

		foreach ( $columns as $attributes ) {
			$header[ $attributes['label'] ] = 'string';
		}

		$writer = new ExportsReports__Prefixed__XLSXWriter();
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
				$value = wp_strip_all_tags( (string) $value );

				// The cell is text unless the column is a number.
				$row[] = ! empty( $columns[ $key ]['type'] ) && 'number' === $columns[ $key ]['type'] ? (int) $value : $value;
			}

			$writer->writeSheetRow( 'Sheet1', $row );
		}

		$wp_filesystem->put_contents( $export_file_location, (string) $writer->writeToString() );

		return $export_file;
	}
}
