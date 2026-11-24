<?php
/**
 * The JSON export.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes a JSON document: the rows under items.item with their count.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
 */
class Exports_Reports_Exporter_JSON extends Exports_Reports_Exporter {

	/**
	 * Writes the file from the rows the screen loaded.
	 *
	 * @since 1.2.8 A write that fails, or rows JSON cannot encode, say so instead of reporting success.
	 *
	 * @return string|false The file name, or false when the file could not be written (the screen
	 *                      shows the error).
	 */
	public function write() {
		/** @var WP_Filesystem_Base $wp_filesystem */ global $wp_filesystem;

		$export_file          = $this->file();
		$export_file_location = $this->path( $export_file );
		$columns              = $this->admin->exported_columns();
		$rows                 = array();

		foreach ( $this->admin->full_data as $item ) {
			$rows[] = $this->admin->export_row( $item, $columns );
		}

		$data = array(
			'items' => array(
				'count' => count( $rows ),
				'item'  => $rows,
			),
		);

		$json = wp_json_encode( $data, JSON_PRETTY_PRINT );

		if ( false === $json || ! $wp_filesystem->put_contents( $export_file_location, $json ) ) {
			$this->admin->error( __( '<strong>Error:</strong> The export file could not be written.', 'exports-and-reports' ) );

			return false;
		}

		return $export_file;
	}
}
