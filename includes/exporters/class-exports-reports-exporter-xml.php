<?php
/**
 * The XML export.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes an XML document: one item element per row, each value in a CDATA section under its column name.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export().
 */
class Exports_Reports_Exporter_XML extends Exports_Reports_Exporter {

	/**
	 * Writes the file from the rows the screen loaded.
	 *
	 * @since 1.2.8 A CDATA terminator in a value is split across two sections, a column name an XML
	 *              name cannot carry is rewritten, and a write that fails says so.
	 *
	 * @return string|false The file name, or false when the file could not be written (the screen
	 *                      shows the error).
	 */
	public function write() {
		/** @var WP_Filesystem_Base $wp_filesystem */ global $wp_filesystem;

		$export_file          = $this->file();
		$export_file_location = $this->path( $export_file );
		$columns              = $this->admin->exported_columns();
		$file_contents        = '<?xml version="1.0" encoding="' . get_bloginfo( 'charset' ) . '" ?>' . "\r\n<items count=\"" . count( $this->admin->full_data ) . "\">\r\n";

		foreach ( $this->admin->full_data as $item ) {
			$file_contents .= "\t<item>\r\n";

			foreach ( $this->admin->export_row( $item, $columns ) as $key => $value ) {
				$element        = $this->element_name( $key );
				$value          = str_replace( ']]>', ']]]]><![CDATA[>', (string) $value );
				$file_contents .= "\t\t<{$element}><![CDATA[" . $value . "]]></{$element}>\r\n";
			}

			$file_contents .= "\t</item>\r\n";
		}

		$file_contents .= '</items>';

		if ( ! $wp_filesystem->put_contents( $export_file_location, $file_contents ) ) {
			$this->admin->error( __( '<strong>Error:</strong> The export file could not be written.', 'exports-and-reports' ) );

			return false;
		}

		return $export_file;
	}

	/**
	 * A column key as a valid XML element name: every character an XML name cannot carry becomes an
	 * underscore, and a name that cannot open one is opened by an underscore.
	 *
	 * @since 1.2.8
	 *
	 * @param string $key The column key.
	 *
	 * @return string
	 */
	protected function element_name( $key ) {
		$element = (string) preg_replace( '/[^A-Za-z0-9_.-]/', '_', (string) $key );

		if ( '' === $element || 1 !== preg_match( '/^[A-Za-z_]/', $element ) ) {
			$element = '_' . $element;
		}

		return $element;
	}
}
