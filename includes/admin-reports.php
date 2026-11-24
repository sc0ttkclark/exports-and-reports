<?php
/**
 * The Manage Reports screen and the report form's field editor.
 *
 * @package Exports_And_Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- the plugin runs the administrator's report queries and keeps its own tables; there is nothing to cache.

/**
 * The Manage Reports page: an Exports_Reports_Admin_UI on the reports table, with the field editor on
 * the field_data column.
 */
function exports_reports_reports() {
	global $wpdb;

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

	$columns = array(
		'name',
		'group'    => array(
			'label'   => __( 'Group', 'exports-and-reports' ),
			'type'    => 'related',
			'related' => $wpdb->exports_reports_groups,
		),
		'disabled' => array(
			'label' => __( 'Disabled', 'exports-and-reports' ),
			'type'  => 'bool',
		),
		'created'  => array(
			'label' => __( 'Date Created', 'exports-and-reports' ),
			'type'  => 'datetime',
		),
		'updated'  => array(
			'label' => __( 'Last Modified', 'exports-and-reports' ),
			'type'  => 'datetime',
		),
		'id'       => array(
			'label' => __( 'Report ID', 'exports-and-reports' ),
			'type'  => 'number',
		),
	);

	$columns['created']['filter']       = true;
	$columns['created']['filter_label'] = 'Lifespan (created / modified)';
	$columns['created']['date_ongoing'] = 'updated';

	$form_columns = $columns;

	unset( $form_columns['id'] );

	$form_columns['disabled']['label'] = __( 'Disabled?', 'exports-and-reports' );

	$form_columns['disable_export'] = array(
		'label' => __( 'Disable Export?', 'exports-and-reports' ),
		'type'  => 'bool',
	);

	$form_columns['export_formats'] = array(
		'label'            => __( 'Export Formats', 'exports-and-reports' ),
		'type'             => 'related',
		'related'          => exports_reports_export_formats(),
		'related_multiple' => true,
		'comments'         => __( 'Leave every box empty to offer the formats enabled in Settings. A format turned off in Settings is not offered even when checked here. CSV, TSV and TXT are written row by row and suit very large reports; the other formats hold the whole report in memory while they are written.', 'exports-and-reports' ),
	);

	$form_columns['default_none'] = array(
		'label'    => __( 'Default to No Results?', 'exports-and-reports' ),
		'type'     => 'bool',
		'comments' => __( 'On = Show no results and require search; Off (default) = Show all results', 'exports-and-reports' ),
	);

	$form_columns['page_orientation'] = array(
		'label'   => __( 'PDF Report Page Orientation', 'exports-and-reports' ),
		'type'    => 'related',
		'related' => array(
			'L' => __( 'Landscape', 'exports-and-reports' ),
			'P' => __( 'Portrait', 'exports-and-reports' ),
		),
	);

	$form_columns['created']['date_touch_on_create'] = true;
	$form_columns['created']['display']              = false;
	$form_columns['updated']['date_touch']           = true;
	$form_columns['updated']['display']              = false;

	$roles = exports_reports_get_roles();

	$form_columns['role_access'] = array(
		'label'            => __( 'WP Roles with Access', 'exports-and-reports' ),
		'type'             => 'related',
		'related'          => $roles,
		'related_multiple' => true,
	);

	$form_columns['sql_query'] = array(
		'label'    => __( 'SQL Query', 'exports-and-reports' ),
		'type'     => 'desc',
		'comments' => __( 'Available Variables: %%WHERE%% %%HAVING%% %%ORDERBY%% %%LIMIT%%<br />(example: WHERE %%WHERE%% my_field=1)', 'exports-and-reports' ),
	);

	$form_columns['sql_query_count'] = array(
		'label'    => __( 'SQL Query for Totals (advanced, optional)', 'exports-and-reports' ),
		'type'     => 'desc',
		'comments' => __( 'Runs after the report query with the same search and filters and returns one row, whose values show in the Totals row under the last page of the report and as the totals of the REST data (for example SELECT SUM(amount) AS amount, COUNT(*) AS id FROM ...). It does not count the rows: the report query does.<br />Available Variables: %%WHERE%% %%HAVING%%<br />(example: WHERE %%WHERE%% my_field=1)', 'exports-and-reports' ),
	);

	$form_columns['field_data'] = array(
		'label'        => __( 'Fields (optional)', 'exports-and-reports' ),
		'custom_input' => 'exports_reports_report_field',
		'custom_save'  => 'exports_reports_report_field_save',
	);

	$admin_ui = array(
		'reorder'       => 'weight',
		'reorder_order' => '`group` ASC, `weight`, `name`',
		'order'         => '`group` ASC, `weight`, `name`', // The Reports menu's order within a group: by weight, ties by name.
		'order_dir'     => 'ASC',
		'css'           => EXPORTS_REPORTS_URL . 'assets/css/admin.css',
		'item'          => __( 'Report', 'exports-and-reports' ),
		'items'         => __( 'Reports', 'exports-and-reports' ),
		'table'         => $wpdb->exports_reports_reports,
		'columns'       => $columns,
		'form_columns'  => $form_columns,
		'duplicate'     => true,
	);

	$admin = new Exports_Reports_Admin_UI( $admin_ui );

	$admin->go();
}

/**
 * The field editor of the report form: one card per field, the advanced options folded away, new
 * rows from a template or detected from the query, the order by drag and drop (assets/js/report-fields.js).
 *
 * @param string                   $column     The column name (field_data).
 * @param array                    $attributes The column attributes.
 * @param Exports_Reports_Admin_UI $obj        The admin UI.
 */
function exports_reports_report_field( $column, $attributes, $obj ) {
	$field_data = json_decode( isset( $obj->row[ $column ] ) ? (string) $obj->row[ $column ] : '', true );
	$fields     = is_array( $field_data ) ? array_values( $field_data ) : array();
	$types      = exports_reports_field_types();
	?>
	<div
		class="er-fields"
		data-next-index="<?php echo esc_attr( (string) count( $fields ) ); ?>"
		data-detect-nonce="<?php echo esc_attr( wp_create_nonce( 'exports-reports-detect-columns' ) ); ?>"
		data-confirm-remove="<?php esc_attr_e( 'Remove this field from the report?', 'exports-and-reports' ); ?>"
		data-detecting="<?php esc_attr_e( 'Running the query…', 'exports-and-reports' ); ?>"
		<?php /* translators: 1: how many fields were added, 2: how many columns were listed already. */ ?>
		data-detected="<?php esc_attr_e( 'Fields added: %1$s. Already listed: %2$s.', 'exports-and-reports' ); ?>"
		data-detect-empty="<?php esc_attr_e( 'Type the query first.', 'exports-and-reports' ); ?>"
		data-detect-failed="<?php esc_attr_e( 'The request did not get through. Save the report and try again.', 'exports-and-reports' ); ?>"
	>
		<p class="description"><?php esc_html_e( 'Leave this empty to show every column the query returns. Drag a field by its handle to change the order. Detect Fields runs the query above for one row and adds a field for each column of the result that is not listed yet, with the data type that fits the column.', 'exports-and-reports' ); ?></p>
		<div class="er-fields__list">
			<?php
			foreach ( $fields as $index => $field ) {
				exports_reports_report_field_row( (string) $index, exports_reports_field_defaults( $field ), $types );
			}
			?>
		</div>
		<p class="er-fields__actions">
			<button type="button" class="button er-fields__add"><?php esc_html_e( 'Add Field', 'exports-and-reports' ); ?></button>
			<button type="button" class="button er-fields__detect"><?php esc_html_e( 'Detect Fields from the Query', 'exports-and-reports' ); ?></button>
			<span class="er-fields__note" role="status"></span>
		</p>
		<template class="er-fields__template"><?php exports_reports_report_field_row( '__i__', exports_reports_field_defaults(), $types ); ?></template>
	</div>
	<input type="hidden" name="<?php echo esc_attr( $column ); ?>" value="" />
	<?php
}

/**
 * The admin-ajax action exports_reports_detect_columns, for the field editor's Detect Fields button: runs
 * the query posted from the report form, one row of it, and answers with the columns of its result and the
 * data type that fits each (exports_reports_detect_columns()).
 *
 * @since 1.2.0
 */
function exports_reports_ajax_detect_columns() {
	check_ajax_referer( 'exports-reports-detect-columns' );

	if ( ! exports_reports_current_user_can_manage() ) {
		wp_send_json_error( array( 'message' => __( 'You are not allowed to edit reports.', 'exports-and-reports' ) ), 403 );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the query the administrator typed into the form, run as the report would run it; the report form stores it the same way.
	$sql = isset( $_POST['sql'] ) ? trim( (string) wp_unslash( $_POST['sql'] ) ) : '';

	if ( '' === $sql ) {
		wp_send_json_error( array( 'message' => __( 'Type the query first.', 'exports-and-reports' ) ) );
	}

	$detected = exports_reports_detect_columns( $sql );

	if ( '' !== $detected['error'] ) {
		wp_send_json_error( array( 'message' => $detected['error'] ) );
	}

	wp_send_json_success( array( 'columns' => $detected['columns'] ) );
}

/**
 * The columns a report query returns, each with the data type that fits its column type, read from one row
 * of its result.
 *
 * The query runs as the report runs it (the statements before the last first, the placeholders substituted,
 * nothing of the plugin's added), capped at one row by the session's sql_select_limit, which also caps a
 * dynamic statement, where the plugin can place no LIMIT; a LIMIT of the query's own counts instead.
 *
 * @since 1.2.0
 *
 * @param string $sql The query, as typed into the report form.
 *
 * @return array{columns: array<int, array{name: string, type: string}>, rows: int, error: string} The columns and the
 *               rows read, or the database's message in error when the query failed or its last statement returns no rows.
 */
function exports_reports_detect_columns( $sql ) {
	/** @var wpdb $wpdb */ global $wpdb;

	require_once EXPORTS_REPORTS_DIR . 'includes/class-exports-reports-admin-ui.php';

	$report = (object) array(
		'id'               => 0,
		'name'             => '',
		'sql_query'        => $sql,
		'sql_query_count'  => '',
		'field_data'       => '',
		'disable_export'   => 1,
		'export_formats'   => '',
		'default_none'     => 0,
		'page_orientation' => 'P',
	);

	$admin     = new Exports_Reports_Admin_UI( exports_reports_report_options( $report ) );
	$statement = $admin->query->statement( true );
	$columns   = array();
	$rows      = 0;
	$error     = '';

	// The database's complaints go into the answer, not the page.
	$suppressed = $wpdb->suppress_errors();

	$wpdb->query( 'SET SESSION sql_select_limit = 1' );

	$last = $admin->query->run_leading_statements( $statement['sql'] );

	if ( false === $last ) {
		$error = '' !== (string) $wpdb->last_error ? (string) $wpdb->last_error : __( 'The query is empty.', 'exports-and-reports' );
	} else {
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- the administrator's own query; see Exports_Reports_Query::rows().
		$wpdb->query( $last );

		$result = $wpdb->__get( 'result' ); // What the last statement returned: a result set, or true for a statement without one.
		$rows   = (int) $wpdb->num_rows;

		if ( '' !== (string) $wpdb->last_error ) {
			$error = (string) $wpdb->last_error;
		} elseif ( ! $result instanceof mysqli_result ) {
			$error = __( 'The last statement of the query returns no rows.', 'exports-and-reports' );
		} else {
			// phpcs:ignore WordPress.DB.RestrictedFunctions.mysql_mysqli_fetch_fields -- the columns of the result wpdb holds.
			foreach ( mysqli_fetch_fields( $result ) as $field ) {
				$columns[] = array(
					'name' => (string) $field->name,
					'type' => exports_reports_column_data_type( (int) $field->type, (int) $field->length ),
				);
			}
		}
	}

	$wpdb->query( 'SET SESSION sql_select_limit = DEFAULT' );
	$wpdb->suppress_errors( $suppressed );

	return array(
		'columns' => $columns,
		'rows'    => $rows,
		'error'   => $error,
	);
}

/**
 * The data type of the field editor that fits a column of a result: dates, times and date-times, integers
 * (a one-digit TINYINT and a BIT as a flag), decimals, and text for the rest.
 *
 * @since 1.2.0
 *
 * @param int $type   The column type, a MYSQLI_TYPE_* constant.
 * @param int $length The column's display width.
 *
 * @return string A key of exports_reports_field_types().
 */
function exports_reports_column_data_type( $type, $length ) {
	switch ( $type ) {
		case MYSQLI_TYPE_DATE:
		case MYSQLI_TYPE_NEWDATE:
			return 'date';
		case MYSQLI_TYPE_TIME:
			return 'time';
		case MYSQLI_TYPE_DATETIME:
		case MYSQLI_TYPE_TIMESTAMP:
			return 'datetime';
		case MYSQLI_TYPE_BIT:
			return 'bool';
		case MYSQLI_TYPE_TINY:
			return $length <= 1 ? 'bool' : 'number';
		case MYSQLI_TYPE_SHORT:
		case MYSQLI_TYPE_INT24:
		case MYSQLI_TYPE_LONG:
		case MYSQLI_TYPE_LONGLONG:
		case MYSQLI_TYPE_YEAR:
			return 'number';
		case MYSQLI_TYPE_DECIMAL:
		case MYSQLI_TYPE_NEWDECIMAL:
		case MYSQLI_TYPE_FLOAT:
		case MYSQLI_TYPE_DOUBLE:
			return 'decimal';
	}

	return 'text';
}

/**
 * The data types a field can have.
 *
 * @return array<string, string>
 */
function exports_reports_field_types() {
	return array(
		'text'     => __( 'Text', 'exports-and-reports' ),
		'bool'     => __( 'Boolean (Checkbox)', 'exports-and-reports' ),
		'date'     => __( 'Date', 'exports-and-reports' ),
		'time'     => __( 'Time', 'exports-and-reports' ),
		'datetime' => __( 'Date + Time', 'exports-and-reports' ),
		'number'   => __( 'Number (no decimal)', 'exports-and-reports' ),
		'decimal'  => __( 'Decimal (two places)', 'exports-and-reports' ),
		'related'  => __( 'Related', 'exports-and-reports' ),
	);
}

/**
 * One field of the editor. The input names are the ones exports_reports_report_field_save() reads;
 * a flag posts its "off" value from a hidden input and its "on" value from the checkbox after it.
 *
 * @param string $index The row index, or the placeholder the template carries.
 * @param array  $field The field with every key (exports_reports_field_defaults()).
 * @param array  $types The data types.
 */
function exports_reports_report_field_row( $index, $field, $types ) {
	$text = static function ( $key, $label, $hint = '' ) use ( $index, $field ) {
		?>
		<label>
			<span><?php echo esc_html( $label ); ?></span>
			<input type="text" name="<?php echo esc_attr( $key . '[' . $index . ']' ); ?>" value="<?php echo esc_attr( (string) $field[ str_replace( array( 'field_', 'width' ), array( '', 'width' ), $key ) ] ); ?>" class="regular-text"<?php echo '' !== $hint ? ' placeholder="' . esc_attr( $hint ) . '"' : ''; ?> />
		</label>
		<?php
	};
	$flag = static function ( $key, $label, $checked, $on = '1', $off = '0' ) use ( $index ) {
		$name = $key . '[' . $index . ']';
		?>
		<label class="er-field__flag">
			<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $off ); ?>" />
			<input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $on ); ?>"<?php checked( $checked ); ?> />
			<?php echo esc_html( $label ); ?>
		</label>
		<?php
	};
	?>
	<div class="er-field">
		<div class="er-field__bar">
			<span class="er-field__handle dashicons dashicons-move" title="<?php esc_attr_e( 'Drag to reorder', 'exports-and-reports' ); ?>"></span>
			<?php $text( 'field_name', __( 'Field name (column or alias)', 'exports-and-reports' ) ); ?>
			<label>
				<span><?php esc_html_e( 'Data type', 'exports-and-reports' ); ?></span>
				<select name="<?php echo esc_attr( 'field_type[' . $index . ']' ); ?>">
					<?php foreach ( $types as $type => $type_label ) { ?>
						<option value="<?php echo esc_attr( $type ); ?>"<?php selected( $field['type'], $type ); ?>><?php echo esc_html( $type_label ); ?></option>
					<?php } ?>
				</select>
			</label>
			<?php $text( 'field_label', __( 'Label', 'exports-and-reports' ), __( 'defaults to the field name', 'exports-and-reports' ) ); ?>
			<button type="button" class="button-link button-link-delete er-field__remove"><?php esc_html_e( 'Remove', 'exports-and-reports' ); ?></button>
		</div>
		<div class="er-field__flags">
			<?php
			$flag( 'field_hide_report', __( 'Hide from report', 'exports-and-reports' ), 1 === (int) $field['hide_report'] );
			$flag( 'field_hide_export', __( 'Hide from export', 'exports-and-reports' ), 1 === (int) $field['hide_export'] );
			$flag( 'field_search', __( 'Searchable', 'exports-and-reports' ), 1 !== (int) $field['search'], '0', '1' );
			$flag( 'field_filter', __( 'Filterable', 'exports-and-reports' ), 1 === (int) $field['filter'] );
			$flag( 'field_group_related', __( 'Filter with HAVING', 'exports-and-reports' ), 1 === (int) $field['group_related'] );
			?>
		</div>
		<details class="er-field__advanced">
			<summary><?php esc_html_e( 'Advanced options', 'exports-and-reports' ); ?></summary>
			<div class="er-field__grid">
				<?php
				$text( 'field_real_name', __( 'Real field', 'exports-and-reports' ), __( 'when the name is an alias', 'exports-and-reports' ) );
				$text( 'field_custom_display', __( 'Display function', 'exports-and-reports' ), __( 'a function a plugin or theme defines', 'exports-and-reports' ) );
				$text( 'width', __( 'Width', 'exports-and-reports' ), __( 'relative, for the PDF export', 'exports-and-reports' ) );
				$text( 'field_filter_label', __( 'Filter label', 'exports-and-reports' ) );
				$text( 'field_filter_default', __( 'Default filter value', 'exports-and-reports' ) );
				$text( 'field_filter_ongoing', __( 'Ongoing date field', 'exports-and-reports' ), __( 'for a date range filter', 'exports-and-reports' ) );
				$text( 'field_filter_ongoing_default', __( 'Ongoing default filter value', 'exports-and-reports' ) );
				$text( 'field_related', __( 'Related table', 'exports-and-reports' ), __( 'related type only', 'exports-and-reports' ) );
				$text( 'field_related_field', __( 'Related field', 'exports-and-reports' ) );
				$text( 'field_related_id', __( 'Related ID field', 'exports-and-reports' ) );
				$text( 'field_related_sql', __( 'Related WHERE / ORDER BY', 'exports-and-reports' ) );
				?>
			</div>
		</details>
	</div>
	<?php
}

/**
 * The custom_save callback of the field_data column: reads the rows the field editor posted and
 * returns them as JSON, in order, each with the keys exports_reports_field_defaults() names.
 *
 * @param mixed                    $value      The column's value in the request; the rows are read from the field inputs instead.
 * @param string                   $column     The column name (field_data).
 * @param array                    $attributes The column attributes.
 * @param Exports_Reports_Admin_UI $obj        The admin UI.
 *
 * @return string JSON: the list of fields, empty when none was posted.
 */
function exports_reports_report_field_save( $value, $column, $attributes, $obj ) {
	// Exports_Reports_Admin_UI::save() verified the form nonce before calling this custom_save callback; checked again so the function stands on its own.
	$form = isset( $obj->do ) && is_string( $obj->do ) ? $obj->do : '';

	if ( '' === $form || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'exports-reports-form-' . $form ) ) {
		return $value;
	}

	$value = array();

	if ( empty( $_POST['field_name'] ) || ! is_array( $_POST['field_name'] ) ) {
		return wp_json_encode( $value );
	}

	$names = array_map( 'sanitize_text_field', wp_unslash( $_POST['field_name'] ) );

	$types = array_keys( exports_reports_field_types() );
	$texts = array(
		'real_name'              => 'field_real_name',
		'label'                  => 'field_label',
		'filter_label'           => 'field_filter_label',
		'group_related'          => 'field_group_related',
		'custom_display'         => 'field_custom_display',
		'width'                  => 'width',
		'filter_default'         => 'field_filter_default',
		'filter_ongoing'         => 'field_filter_ongoing',
		'filter_ongoing_default' => 'field_filter_ongoing_default',
		'related'                => 'field_related',
		'related_field'          => 'field_related_field',
		'related_id'             => 'field_related_id',
	);
	$flags = array(
		'hide_report' => 'field_hide_report',
		'hide_export' => 'field_hide_export',
		'search'      => 'field_search',
		'filter'      => 'field_filter',
	);

	foreach ( $names as $key => $name ) {
		if ( '' === $name ) {
			continue;
		}

		$field = array( 'name' => $name );

		foreach ( $texts as $option => $input ) {
			$field[ $option ] = isset( $_POST[ $input ][ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $input ][ $key ] ) ) : '';
		}

		foreach ( $flags as $option => $input ) {
			$field[ $option ] = isset( $_POST[ $input ][ $key ] ) ? absint( wp_unslash( $_POST[ $input ][ $key ] ) ) : 0;
		}

		$type = isset( $_POST['field_type'][ $key ] ) ? sanitize_key( wp_unslash( $_POST['field_type'][ $key ] ) ) : 'text';

		$field['type']        = in_array( $type, $types, true ) ? $type : 'text';
		$field['total_field'] = '';
		// A query fragment written by the administrator; stored as typed, like the report's own queries.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a SQL fragment the administrator wrote for the lookup, kept as written; the report's own query is stored the same way.
		$field['related_sql'] = isset( $_POST['field_related_sql'][ $key ] ) ? trim( (string) wp_unslash( $_POST['field_related_sql'][ $key ] ) ) : '';

		$value[] = $field;
	}

	return wp_json_encode( $value );
}

/**
 * A field definition with every key set, empty strings for the ones not given.
 *
 * @param array|null $field A field definition, possibly partial.
 *
 * @return array
 */
function exports_reports_field_defaults( $field = null ) {
	if ( ! is_array( $field ) ) {
		$field = array();
	}

	$defaults = array(
		'name'                   => '',
		'real_name'              => '',
		'label'                  => '',
		'filter_label'           => '',
		'hide_report'            => '',
		'hide_export'            => '',
		'group_related'          => '',
		'custom_display'         => '',
		'type'                   => '',
		'width'                  => '',
		'search'                 => '',
		'filter'                 => '',
		'filter_default'         => '',
		'filter_ongoing'         => '',
		'filter_ongoing_default' => '',
		'total_field'            => '',
		'related'                => '',
		'related_field'          => '',
		'related_sql'            => '',
		'related_id'             => '',
	);

	$field = array_merge( $defaults, $field );

	return $field;
}
