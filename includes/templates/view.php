<?php
/**
 * The view screen of a row: the heading and one row per view column.
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
?>
<div class="wrap wp-admin-ui-wrap">
	<?php
	$screen_title = $this->heading['view'] . ' ' . $this->item;
	$back         = true;
	require __DIR__ . '/heading.php';
	?>
	<table class="form-table">
		<?php
		foreach ( $this->view_columns as $column => $attributes ) {
			if ( ! is_array( $attributes ) ) {
				$column     = $attributes;
				$attributes = $this->setup_column( $column );
			}
			if ( ! isset( $this->row[ $column ] ) ) {
				$this->row[ $column ] = '';
			}
			if ( false === $attributes['display'] ) {
				continue;
			}
			?>
			<tr valign="top">
			<th scope="row">
				<label for="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $attributes['label'] ); ?></label>
			</th>
			<td>
			<?php
			if ( false !== $attributes['custom_view'] && is_callable( $attributes['custom_view'] ) ) {
				echo wp_kses( (string) call_user_func( $attributes['custom_view'], $this->row[ $column ], $this->row, $column, $attributes, $this ), $this->allowed_html() );
				?>
				</td>
				</tr>
				<?php
				continue;
			}
			if ( 'date' === $attributes['type'] ) {
				$this->row[ $column ] = date_i18n( 'Y/m/d', strtotime( $this->row[ $column ] ) );
			} elseif ( 'time' === $attributes['type'] ) {
				$this->row[ $column ] = date_i18n( 'g:i:s A', strtotime( $this->row[ $column ] ) );
			} elseif ( 'datetime' === $attributes['type'] ) {
				$this->row[ $column ] = date_i18n( 'Y/m/d g:i:s A', strtotime( $this->row[ $column ] ) );
			} elseif ( 'bool' === $attributes['type'] ) {
				$this->row[ $column ] = ( 1 === (int) $this->row[ $column ] ? __( 'Yes', 'exports-and-reports' ) : __( 'No', 'exports-and-reports' ) );
			} elseif ( 'number' === $attributes['type'] ) {
				$this->row[ $column ] = (int) ( $this->row[ $column ] );
			} elseif ( 'decimal' === $attributes['type'] ) {
				$this->row[ $column ] = number_format( $this->row[ $column ], 2 );
			} elseif ( 'related' === $attributes['type'] && false !== $attributes['related'] ) {
				$old_value            = $this->row[ $column ];
				$this->row[ $column ] = '';
				if ( ! empty( $old_value ) ) {
					$this->row[ $column ] = array();
					if ( ! is_array( $attributes['related'] ) ) {
						$related = $this->rows( 'SELECT `id`,`' . $this->sanitize( (string) $attributes['related_field'] ) . '` FROM ' . (string) $attributes['related'] . ' WHERE `id` IN (' . $this->sanitize( (string) $old_value ) . ')' . ( ! empty( $attributes['related_sql'] ) ? ' ' . (string) $attributes['related_sql'] : '' ) );

						foreach ( $related as $option ) {
							$this->row[ $column ][] = esc_html( $option->{$attributes['related_field']} );
						}
					} else {
						$related          = $attributes['related'];
						$selected_options = explode( ',', $old_value );
						foreach ( $related as $option_id => $option ) {
							if ( in_array( (string) $option_id, $selected_options, true ) ) {
								$this->row[ $column ][] = esc_html( $option );
							}
						}
					}
					$this->row[ $column ] = '<ul><li>' . implode( '</li><li>', $this->row[ $column ] ) . '</li></ul>';
				} else {
					$this->row[ $column ] = __( 'N/A', 'exports-and-reports' );
				}
			}
			?>
			<div id="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo wp_kses_post( $this->row[ $column ] ); ?></div>
			<?php
			if ( ! empty( $attributes['comments'] ) && false === $attributes['comments_top'] ) {
				?>
				<span class="description"><?php echo wp_kses_post( $attributes['comments'] ); ?></span>
				<?php
			}
			?>
			</td>
			</tr>
			<?php
		}
		?>
	</table>
</div>
