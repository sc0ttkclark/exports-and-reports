<?php
/**
 * The add and edit form of a screen: one row per form column, the submit button and the nonce.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this   The screen.
 * @var string                   $submit The submit button's text.
 * @var array                    $vars   The query variables of the form's action.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a method of Exports_Reports_Admin_UI: the variables are local to that call, not globals.
?>
<form method="post" action="<?php echo esc_url( $this->var_update( $vars ) ); ?>" class="wp_admin_ui">
	<table class="form-table">
		<?php
		foreach ( $this->form_columns as $column => $attributes ) {
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
			if ( ! empty( $attributes['comments'] ) && ! empty( $attributes['comments_top'] ) ) {
				?>
				<span class="description"><?php echo wp_kses_post( $attributes['comments'] ); ?></span>
				<?php
				if ( ! in_array( $attributes['type'], array( 'desc', 'code' ), true ) ) {
					echo '<br />';
				}
			}
			if ( false !== $attributes['custom_input'] && function_exists( "{$attributes['custom_input']}" ) ) {
				$attributes['custom_input']( $column, $attributes, $this );
				?>
				</td>
				</tr>
				<?php
				continue;
			}
			if ( false !== $attributes['custom_form_display'] && function_exists( "{$attributes['custom_form_display']}" ) ) {
				$this->row[ $column ] = $attributes['custom_form_display']( $column, $attributes, $this );
			}
			if ( false !== $attributes['readonly'] ) {
				?>
				<div id="admin_ui_<?php echo esc_attr( $column ); ?>"><?php echo esc_html( $this->row[ $column ] ); ?></div>
				<?php
			} elseif ( 'bool' === $attributes['type'] ) {
				?>
				<input type="checkbox" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="1"<?php checked( 1, (int) $this->row[ $column ] ); ?> />
				<?php
			} elseif ( 'password' === $attributes['type'] ) {
				?>
				<input type="password" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="<?php echo esc_attr( $this->row[ $column ] ); ?>" class="regular-text" />
				<?php
			} elseif ( 'desc' === $attributes['type'] || 'code' === $attributes['type'] ) {
				?>
				<textarea name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" rows="10" cols="50"><?php echo esc_textarea( $this->row[ $column ] ); ?></textarea>
				<?php
			} elseif ( in_array( $attributes['type'], array( 'date', 'datetime', 'time' ), true ) ) {
				$html_input_type = $attributes['type'];

				if ( 'datetime' === $html_input_type ) {
					$html_input_type = 'datetime-local';
				}
				?>
				<input type="<?php echo esc_attr( $html_input_type ); ?>" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="<?php echo esc_attr( str_replace( ' ', 'T', $this->row[ $column ] ) ); ?>" />
				<?php
			} elseif ( 'related' === $attributes['type'] && false !== $attributes['related'] ) {
				if ( ! is_array( $attributes['related'] ) ) {
					$related = $this->rows( 'SELECT id,`' . $this->sanitize( (string) $attributes['related_field'] ) . '` FROM ' . (string) $attributes['related'] . ( ! empty( $attributes['related_sql'] ) ? ' ' . (string) $attributes['related_sql'] : '' ) );
					$choices = array();
					foreach ( $related as $option ) {
						$choices[ (string) $option->id ] = (string) $option->{$attributes['related_field']};
					}
				} else {
					$choices = array();
					foreach ( (array) $attributes['related'] as $option_id => $option ) {
						$choices[ (string) $option_id ] = (string) $option;
					}
				}
				$selected_options = array_map( 'strval', explode( ',', (string) $this->row[ $column ] ) );
				if ( false !== $attributes['related_multiple'] ) {
					?>
					<fieldset class="er-choices" id="admin_ui_<?php echo esc_attr( $column ); ?>">
						<?php foreach ( $choices as $option_id => $option ) { ?>
							<label><input type="checkbox" name="<?php echo esc_attr( $column ); ?>[]" value="<?php echo esc_attr( $option_id ); ?>"<?php checked( in_array( $option_id, $selected_options, true ) ); ?> /> <?php echo esc_html( $option ); ?></label>
						<?php } ?>
					</fieldset>
					<?php
				} else {
					?>
					<select name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>">
						<?php foreach ( $choices as $option_id => $option ) { ?>
							<option value="<?php echo esc_attr( $option_id ); ?>"<?php selected( in_array( $option_id, $selected_options, true ) ); ?>><?php echo esc_html( $option ); ?></option>
						<?php } ?>
					</select>
					<?php
				}
			} else {
				?>
				<input type="text" name="<?php echo esc_attr( $column ); ?>" id="admin_ui_<?php echo esc_attr( $column ); ?>" value="<?php echo esc_attr( $this->row[ $column ] ); ?>" class="regular-text" />
				<?php
			}
			if ( ! empty( $attributes['comments'] ) && false === $attributes['comments_top'] ) {
				if ( ! in_array( $attributes['type'], array( 'desc', 'code' ), true ) ) {
					echo '<br />';
				}
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
	<p class="submit">
		<input type="submit" name="Submit" class="button-primary" value="<?php echo esc_attr( $submit ); ?>" />
		<?php wp_nonce_field( 'exports-reports-form-' . $vars['do'] ); ?>
	</p>
</form>
