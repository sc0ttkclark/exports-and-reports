<?php
/**
 * The delimited exports: CSV, TSV and the pipe-separated TXT.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes a delimited export, from an unbuffered result when the columns allow it so that one row is in
 * memory at a time, every value quoted, a UTF-8 byte order mark first, formula-like values neutralised.
 *
 * @since 1.0.0 Out of Exports_Reports_Admin_UI::export_sv().
 */
class Exports_Reports_Exporter_Delimited extends Exports_Reports_Exporter {

	/**
	 * The export types this class writes, with their file extensions.
	 *
	 * @var array<string, string>
	 */
	const EXTENSIONS = array(
		'csv'  => 'csv',
		'tsv'  => 'tsv',
		'pipe' => 'txt',
	);

	/**
	 * The delimiter of each type.
	 *
	 * @var array<string, string>
	 */
	const DELIMITERS = array(
		'csv'  => ',',
		'tsv'  => "\t",
		'pipe' => '|',
	);

	/**
	 * The file extension: csv, tsv or txt.
	 *
	 * @return string
	 */
	public function extension() {
		$type = $this->admin->export_type;

		return isset( self::EXTENSIONS[ $type ] ) ? self::EXTENSIONS[ $type ] : 'csv';
	}

	/**
	 * The delimiter between values, by the export type. A CSV export formatted for a language that writes
	 * its decimals with a comma separates its values with semicolons, as the spreadsheets of that
	 * language read a CSV file; the tab and the pipe stay.
	 *
	 * @since 1.3.0 The $format argument and the semicolon.
	 *
	 * @param Exports_Reports_Value_Format|null $format How the export writes its numbers; null for the plugin's own format.
	 *
	 * @return string
	 */
	public function delimiter( $format = null ) {
		$type = $this->admin->export_type;

		if ( isset( self::DELIMITERS[ $type ] ) && 'csv' !== $type ) {
			return self::DELIMITERS[ $type ];
		}

		$comma_decimals = $format instanceof Exports_Reports_Value_Format && ',' === $format->decimal_point();

		/**
		 * Filters the delimiter between the values of a CSV export.
		 *
		 * @since 1.3.0
		 *
		 * @param string                   $delimiter A comma, or a semicolon when the export is formatted for a
		 *                                            language that writes its decimals with a comma.
		 * @param string                   $locale    The WordPress locale the export is formatted for, '' for none.
		 * @param Exports_Reports_Admin_UI $admin     The screen.
		 */
		$delimiter = (string) apply_filters( 'exports_reports_export_csv_delimiter', $comma_decimals ? ';' : ',', $format instanceof Exports_Reports_Value_Format ? $format->language() : '', $this->admin );

		return '' !== $delimiter ? $delimiter : ',';
	}

	/**
	 * Whether the rows can be read from an unbuffered result: wpdb must be on mysqli, and no column may run
	 * a query while the result is open, as a "related" lookup or a custom display callback might.
	 *
	 * @return bool
	 */
	public function can_stream() {
		global $wpdb;

		if ( ! isset( $wpdb->dbh ) || ! $wpdb->dbh instanceof mysqli ) {
			return false;
		}

		foreach ( $this->admin->exported_columns() as $attributes ) {
			if ( ! empty( $attributes['custom_display'] ) || ! empty( $attributes['custom_relate'] ) || ( isset( $attributes['type'], $attributes['related'] ) && 'related' === $attributes['type'] && false !== $attributes['related'] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Writes the file: the rows from the unbuffered result the screen's get_data() opened for it when
	 * stream_sql is set, from full_data otherwise.
	 *
	 * @return string|false The file name, or false when the statement failed (the screen shows the error).
	 */
	public function write() {
		global $wpdb;

		// Before the unbuffered result is open: a callback on the format's filter may query the database.
		$format           = $this->admin->export_format();
		$export_delimiter = $this->delimiter( $format );

		$export_file          = $this->file();
		$export_file_location = $this->path( $export_file );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$fp     = fopen( $export_file_location, 'a+' );
		$head   = array();
		$stream = null;

		$escape_formulas = $this->escapes_formulas(); // Before the unbuffered result is open: a callback may query the database.

		if ( '' !== $this->admin->stream_sql ) {
			// Unbuffered, on wpdb's connection: the statement may read temporary tables and session variables
			// that a filter callback created there, or that the statements before it in the definition set
			// up, which run first. One row is in memory at a time.
			$stream_sql = $this->admin->query->run_leading_statements( $this->admin->stream_sql );
			$stream     = false;

			if ( false !== $stream_sql ) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_query -- an unbuffered result on wpdb's connection; the statement is the report's own query.
				$stream = mysqli_query( $wpdb->dbh, $stream_sql, MYSQLI_USE_RESULT );
			}

			if ( ! $stream instanceof mysqli_result ) {
				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_error
				$wpdb->last_error        = mysqli_error( $wpdb->dbh );
				$this->admin->stream_sql = '';
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $fp );
				wp_delete_file( $export_file_location );
				$this->admin->error( '<strong>' . esc_html__( 'Error:', 'exports-and-reports' ) . '</strong> ' . esc_html( $wpdb->last_error ) );

				return false;
			}

			if ( empty( $this->admin->columns ) ) {
				$names = array();

				// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_fields
				foreach ( mysqli_fetch_fields( $stream ) as $field ) {
					$name = trim( str_replace( '`', '', $field->name ) );

					if ( 0 < strlen( $name ) ) {
						$names[] = $name;
					}
				}

				$this->admin->columns = $this->admin->setup_columns( $names, 'columns', true );
			}
		}

		$columns = $this->admin->exported_columns();
		$numbers = array();

		foreach ( $columns as $key => $attributes ) {
			$head[] = $attributes['label'];

			// The columns whose values the plugin itself writes as numbers.
			if ( in_array( $attributes['type'], array( 'number', 'decimal' ), true ) && empty( $attributes['custom_display'] ) && empty( $attributes['custom_relate'] ) ) {
				$numbers[ $key ] = true;
			}
		}

		// A UTF-8 byte order mark so spreadsheet applications read the encoding right. With the first label
		// quoted it also keeps Excel from taking a file that starts with "ID" for SYLK.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $fp, "\xEF\xBB\xBF" );
		$this->write_line( $fp, $head, $export_delimiter );

		$rows = $stream instanceof mysqli_result ? $this->stream_rows( $stream ) : $this->admin->full_data;

		foreach ( $rows as $item ) {
			$column_values = array();

			foreach ( $this->admin->export_row( $item, $columns, $format ) as $key => $value ) {
				$column_value = trim( str_replace( array( "\r", "\n" ), ' ', (string) $value ) );

				// A value a spreadsheet would evaluate as a formula is wrapped in a string formula, which shows the
				// text and stays inert. The leading characters are the ones spreadsheets act on; the tab and carriage
				// return of the usual list cannot reach this point, the trim and the line-break replacement above take
				// them. Numbers are left alone: a negative amount starts with "-", and one the plugin wrote from a
				// number of the row is a number however its language separates the thousands and the decimals.
				$number = is_numeric( $column_value ) || ( isset( $numbers[ $key ] ) && ( ! isset( $item[ $key ] ) || is_numeric( $item[ $key ] ) ) );

				if ( $escape_formulas && '' !== $column_value && ! $number && false !== strpos( '=+-@', $column_value[0] ) ) {
					$column_value = '="" & "' . str_replace( '"', '""', $column_value ) . '"';
				}

				$column_values[] = $column_value;
			}

			$this->write_line( $fp, $column_values, $export_delimiter );
		}

		if ( $stream instanceof mysqli_result ) {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_error
			$stream_error = mysqli_error( $wpdb->dbh );
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_free_result
			mysqli_free_result( $stream );
			$this->admin->stream_sql = '';

			if ( '' !== $stream_error ) {
				$wpdb->last_error = $stream_error;
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
				fclose( $fp );
				wp_delete_file( $export_file_location );
				$this->admin->error( '<strong>' . esc_html__( 'Error:', 'exports-and-reports' ) . '</strong> ' . esc_html( $stream_error ) );

				return false;
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $fp );

		return $export_file;
	}

	/**
	 * Yields the rows of an unbuffered result one at a time.
	 *
	 * @param mysqli_result $stream The result.
	 *
	 * @return Generator<int, array<string, string|null>>
	 */
	protected function stream_rows( $stream ) {
		// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_assoc
		$row = mysqli_fetch_assoc( $stream );

		while ( is_array( $row ) ) {
			yield $row;

			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_assoc
			$row = mysqli_fetch_assoc( $stream );
		}
	}

	/**
	 * Writes one line with every value quoted.
	 *
	 * @param resource $fp        The open export file.
	 * @param array    $values    The values of the line.
	 * @param string   $delimiter The delimiter between values.
	 */
	protected function write_line( $fp, $values, $delimiter ) {
		$quoted = array();

		foreach ( $values as $value ) {
			$quoted[] = '"' . str_replace( '"', '""', (string) $value ) . '"';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $fp, implode( $delimiter, $quoted ) . "\n" );
	}
}
