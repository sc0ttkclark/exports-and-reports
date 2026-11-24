<?php
/**
 * The filters and the search box of the list screen, inside its form.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this The screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a method of Exports_Reports_Admin_UI: the variables are local to that call, not globals.

	$excluded_filters = array();
foreach ( $this->filters as $filter ) {
	$excluded_filters[] = 'filter_' . $filter . '_start';
	$excluded_filters[] = 'filter_' . $filter . '_end';
	$excluded_filters[] = 'filter_' . $filter;
}
	$excluded_filters = array_merge( $excluded_filters, array( 'search_query', 'pg' . $this->num ) );
	$this->hidden_vars( $excluded_filters );
foreach ( $this->filters as $filter ) {
	if ( ! isset( $this->search_columns[ $filter ] ) ) {
		continue;
	}

	$filter_column = $this->search_columns[ $filter ];
	$date_exists   = false;

	if ( in_array( $filter_column['type'], array( 'date', 'datetime', 'time' ), true ) ) {
		$html_input_type = $filter_column['type'];

		if ( 'datetime' === $html_input_type ) {
			$html_input_type = 'datetime-local';
		}

		$start = implode( ', ', $this->filter_values( 'filter_' . $filter . '_start', $filter_column['filter_default'] ) );
		$end   = implode( ', ', $this->filter_values( 'filter_' . $filter . '_end', $filter_column['filter_ongoing_default'] ) );
		?>
			&nbsp;&nbsp;
			<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>_start"><?php echo esc_html( $filter_column['filter_label'] ); ?>:</label>
			<input type="<?php echo esc_attr( $html_input_type ); ?>" name="filter_<?php echo esc_attr( $filter ); ?>_start" class="admin_ui_filter admin_ui_date" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>_start" value="<?php echo esc_attr( $start ); ?>" />
			<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>_end"><?php esc_html_e( 'to', 'exports-and-reports' ); ?></label>
			<input type="<?php echo esc_attr( $html_input_type ); ?>" name="filter_<?php echo esc_attr( $filter ); ?>_end" class="admin_ui_filter admin_ui_date" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>_end" value="<?php echo esc_attr( $end ); ?>" />
			<?php
	} elseif ( 'related' === $filter_column['type'] && false !== $filter_column['related'] ) {
		if ( ! is_array( $filter_column['related'] ) ) {
			$related  = $this->rows( 'SELECT ' . $this->backtick( $filter_column['related_id'] ) . ',' . $this->backtick( $filter_column['related_field'] ) . ' FROM ' . $filter_column['related'] . ( ! empty( $filter_column['related_sql'] ) ? ' ' . $filter_column['related_sql'] : '' ) );
			$selected = $this->filter_values( 'filter_' . $filter, $filter_column['filter_default'] );
			?>
				<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $filter_column['filter_label'] ); ?>:</label>
				<select name="filter_<?php echo esc_attr( $filter ); ?><?php echo( false !== $filter_column['related_multiple'] ? '[]' : '' ); ?>" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"<?php echo( false !== $filter_column['related_multiple'] ? ' size="10" class="admin_ui_filter_multiple" multiple' : '' ); ?>>
					<option value=""><?php esc_html_e( '-- Show All --', 'exports-and-reports' ); ?></option>
				<?php
				foreach ( $related as $option ) {
					?>
						<option value="<?php echo esc_attr( $option->{$filter_column['related_id']} ); ?>"<?php selected( in_array( (string) $option->{$filter_column['related_id']}, $selected, true ) ); ?>><?php echo esc_html( $option->{$filter_column['related_field']} ); ?></option>
						<?php
				}
				?>
				</select>
				<?php
		} else {
			$related  = $filter_column['related'];
			$selected = $this->filter_values( 'filter_' . $filter, $filter_column['filter_default'] );
			?>
				<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $filter_column['filter_label'] ); ?>:</label>
				<select name="filter_<?php echo esc_attr( $filter ); ?><?php echo( false !== $filter_column['related_multiple'] ? '[]' : '' ); ?>" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"<?php echo( false !== $filter_column['related_multiple'] ? ' size="10" class="admin_ui_filter_multiple" multiple' : '' ); ?>>
					<option value=""><?php esc_html_e( '-- Show All --', 'exports-and-reports' ); ?></option>
				<?php
				foreach ( $related as $option_id => $option ) {
					?>
						<option value="<?php echo esc_attr( $option_id ); ?>"<?php selected( in_array( (string) $option_id, $selected, true ) ); ?>><?php echo esc_html( $option ); ?></option>
						<?php
				}
				?>
				</select>
				<?php
		}
	} elseif ( 'bool' === $filter_column['type'] ) {
		$selected = $this->filter_values( 'filter_' . $filter, $filter_column['filter_default'] );
		$selected = array() === $selected ? '' : ( 1 === (int) $selected[0] ? '1' : '0' );
		?>
			<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $filter_column['filter_label'] ); ?>:</label>
			<select name="filter_<?php echo esc_attr( $filter ); ?>" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>">
				<option value=""><?php esc_html_e( '-- Show All --', 'exports-and-reports' ); ?></option>
				<option value="1"<?php selected( '1', $selected ); ?>><?php esc_html_e( 'Yes', 'exports-and-reports' ); ?></option>
				<option value="0"<?php selected( '0', $selected ); ?>><?php esc_html_e( 'No', 'exports-and-reports' ); ?></option>
			</select>
			<?php
	} else {
		?>
			<label for="admin_ui_filter_<?php echo esc_attr( $filter ); ?>"><?php echo esc_html( $filter_column['filter_label'] ); ?>:</label>
			<input type="text" name="filter_<?php echo esc_attr( $filter ); ?>" class="admin_ui_filter" id="admin_ui_filter_<?php echo esc_attr( $filter ); ?>" value="<?php echo esc_attr( implode( ', ', $this->filter_values( 'filter_' . $filter, $filter_column['filter_default'] ) ) ); ?>" />
			<?php
	}
}
?>
	<span class="exports-reports-search">
	<label<?php echo( empty( $this->filters ) ? ' class="screen-reader-text"' : '' ); ?> for="page-search-input"><?php esc_html_e( 'Search:', 'exports-and-reports' ); ?></label>
	<input type="text" name="search_query" id="page-search-input" value="<?php echo esc_attr( $this->search_query ); ?>" />
	<input type="submit" value="<?php esc_attr_e( 'Search', 'exports-and-reports' ); ?>" class="button" />
	</span>
	<?php
	if ( false !== $this->search_query ) {
		$clear_filters = array();
		foreach ( $this->filters as $filter ) {
			$clear_filters[ 'filter_' . $filter . '_start' ] = '';
			$clear_filters[ 'filter_' . $filter . '_end' ]   = '';
			$clear_filters[ 'filter_' . $filter ]            = '';
		}
		?>
		&nbsp;&nbsp;&nbsp;
		<small>[<a href="
		<?php
			echo esc_url(
				$this->var_update(
					$clear_filters,
					array(
						'order',
						'order_dir',
						'limit',
					)
				)
			);
		?>
							"><?php esc_html_e( 'Reset Filters', 'exports-and-reports' ); ?></a>] </small>
		<?php
	}
	?>
