<?php
/**
 * The list of a report screen, on WP_List_Table.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! function_exists( 'convert_to_screen' ) ) {
	require_once ABSPATH . 'wp-admin/includes/template.php';
}

if ( ! function_exists( 'get_current_screen' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
}

if ( ! class_exists( 'WP_Screen' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
}

/**
 * Renders the rows of an Exports_Reports_Admin_UI screen the way core renders its lists: the report
 * page's rows, the groups, the reports, and the same rows with a drag handle on the reorder screen.
 * The screen keeps the rows and the request; the table draws them, with the screen's own links (order,
 * order_dir, limit and the page number) in the column headers and the paging.
 *
 * @since 1.0.0 Replaces the table Exports_Reports_Admin_UI::table() wrote itself.
 */
class Exports_Reports_List_Table extends WP_List_Table {

	/**
	 * The screen.
	 *
	 * @var Exports_Reports_Admin_UI
	 */
	protected $admin;

	/**
	 * Whether the rows are shown for reordering: a drag handle on the title, no links.
	 *
	 * @var bool
	 */
	protected $reorder = false;

	/**
	 * The columns shown, by name: each column's attributes plus its 'kind' (title, date or '') and 'label'.
	 *
	 * @var array<string, array>
	 */
	protected $shown = array();

	/**
	 * @param Exports_Reports_Admin_UI $admin   The screen.
	 * @param int                      $reorder 1 on the reorder screen.
	 */
	public function __construct( Exports_Reports_Admin_UI $admin, $reorder = 0 ) {
		$this->admin   = $admin;
		$this->reorder = 1 === (int) $reorder && false !== $admin->reorder;

		$args = array(
			'singular' => sanitize_key( (string) $admin->item ),
			'plural'   => sanitize_key( (string) $admin->items ),
			'ajax'     => false,
		);

		// The current admin screen, by id. Outside an admin request (a test, WP-CLI) a name of the plugin's own
		// stands in: WordPress 5.7 reads the hook suffix global for a missing one, which is not set there.
		$screen = get_current_screen();

		$args['screen'] = $screen instanceof WP_Screen ? $screen->id : 'exports-reports';

		parent::__construct( $args );
	}

	/**
	 * Reads the columns and the rows from the screen.
	 */
	public function prepare_items() {
		$this->shown = array();
		$untitled    = true;

		foreach ( (array) $this->admin->columns as $column => $attributes ) {
			if ( ! is_array( $attributes ) ) {
				$column     = $attributes;
				$attributes = $this->admin->setup_column( $column );
			}

			if ( false === $attributes['display'] ) {
				continue;
			}

			// The first column is the title unless its type says otherwise; a date column is a date.
			$kind = $untitled ? 'title' : '';

			if ( ! empty( $attributes['type'] ) ) {
				if ( 'other' === $attributes['type'] ) {
					$kind = '';
				}

				if ( in_array( $attributes['type'], array( 'date', 'datetime', 'time' ), true ) ) {
					$kind = 'date';
				}
			}

			if ( 'title' === $kind ) {
				$untitled = false;
			}

			if ( empty( $attributes['label'] ) ) {
				$attributes['label'] = ucwords( str_replace( '_', ' ', $column ) );
			}

			$attributes['kind']     = $kind;
			$this->shown[ $column ] = $attributes;
		}

		$this->items           = is_array( $this->admin->data ) ? $this->admin->data : array();
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), $this->get_primary_column_name() );

		$this->set_pagination_args(
			array(
				'total_items' => (int) $this->admin->total,
				'per_page'    => max( 1, (int) $this->admin->limit ),
			)
		);
	}

	/**
	 * The column labels, by column.
	 *
	 * @return array<string, string>
	 */
	public function get_columns() {
		$columns = array();

		foreach ( $this->shown as $column => $attributes ) {
			$columns[ $column ] = $attributes['label'];
		}

		return $columns;
	}

	/**
	 * Every column sorts.
	 *
	 * @return array<string, array>
	 */
	protected function get_sortable_columns() {
		$sortable = array();

		foreach ( array_keys( $this->shown ) as $column ) {
			$sortable[ $column ] = array( $column, false );
		}

		return $sortable;
	}

	/**
	 * The title column carries the row links and the actions.
	 *
	 * @return string
	 */
	protected function get_primary_column_name() {
		foreach ( $this->shown as $column => $attributes ) {
			if ( 'title' === $attributes['kind'] ) {
				return $column;
			}
		}

		return (string) key( $this->shown );
	}

	/**
	 * The table's classes, as the screens had them.
	 *
	 * @return string[]
	 */
	protected function get_table_classes() {
		$classes = array( 'widefat', 'striped', 'page', 'fixed', 'admin_ui_table', $this->_args['plural'] );

		// A table of many columns gives each the width its content needs and scrolls sideways (see admin.css).
		if ( 10 < count( $this->shown ) ) {
			$classes[] = 'exports-reports-wide';
		}

		return $classes;
	}

	/**
	 * The column headers, each a link that sorts by the column through the screen's order and order_dir
	 * variables; the sorted column shows its direction.
	 *
	 * @param bool $with_id Whether the headers carry ids (the head does, the foot does not).
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, , $sortable, $primary ) = $this->get_column_info();

		// Core shows both directions since 6.3 and one indicator before.
		$both = version_compare( get_bloginfo( 'version' ), '6.3', '>=' );

		foreach ( $columns as $column => $label ) {
			$attributes = isset( $this->shown[ $column ] ) ? $this->shown[ $column ] : array();
			$kind       = isset( $attributes['kind'] ) ? $attributes['kind'] : '';
			$classes    = array( 'manage-column', 'column-' . $column );
			$aria_sort  = '';
			$content    = '<span>' . wp_kses_post( $label ) . '</span>';

			if ( '' !== $kind ) {
				$classes[] = 'column-' . $kind;
			}

			if ( $column === $primary ) {
				$classes[] = 'column-primary';
			}

			if ( isset( $sortable[ $column ] ) ) {
				$sorted = $this->admin->order === $column;
				$dir    = $sorted && 'ASC' === $this->admin->order_dir ? 'DESC' : 'ASC';

				if ( $sorted ) {
					$classes[] = 'sorted';
					$classes[] = strtolower( (string) $this->admin->order_dir );
					$aria_sort = ' aria-sort="' . ( 'ASC' === $this->admin->order_dir ? 'ascending' : 'descending' ) . '"';
				} else {
					$classes[] = 'sortable';
					$classes[] = 'ASC' === $dir ? 'desc' : 'asc';
				}

				$link = $this->admin->var_update(
					array(
						'order'     => $column,
						'order_dir' => $dir,
					),
					array( 'limit', 'search_query' )
				);

				$indicator = $both ? '<span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span>' : '<span class="sorting-indicator" aria-hidden="true"></span>';
				$content   = '<a href="' . esc_url( $link ) . '">' . $content . $indicator . '</a>';
			}

			$width = ! empty( $attributes['width'] ) ? ' width="' . esc_attr( $attributes['width'] ) . '"' : '';
			$id    = $with_id ? ' id="' . esc_attr( $column ) . '"' : '';

			echo wp_kses_post( '<th scope="col"' . $id . ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $aria_sort . $width . '>' . $content . '</th>' );
		}
	}

	/**
	 * The bars above and below the table: the page sizes and the page links, and above the table the
	 * buttons (Add New, Reorder, the exports, or Update Order and Cancel while reordering). No bulk
	 * actions, so no nonce in the search form.
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ) {
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php
			$this->pagination( $which );
			$this->extra_tablenav( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	/**
	 * The page sizes and the page links, above and below the table, through the screen's limit() and pagination().
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	protected function pagination( $which ) {
		if ( ! $this->has_items() || false === $this->admin->pagination ) {
			return;
		}
		?>
		<div class="tablenav-pages">
			<?php
			esc_html_e( 'Show per page:', 'exports-and-reports' );
			$this->admin->limit();

			// The separator only when the count or the page links follow: the bar under a one-row report has neither.
			if ( 'top' === $which || 1 !== (int) $this->admin->total ) {
				echo ' &nbsp;|&nbsp; ';
			}

			$this->admin->pagination( 'top' === $which );
			?>
		</div>
		<?php
	}

	/**
	 * The buttons above the table.
	 *
	 * @param string $which 'top' or 'bottom'.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		if ( $this->reorder ) {
			?>
			<div class="alignleft actions">
				<button type="submit" form="exports-reports-reorder" class="button"><?php esc_html_e( 'Update Order', 'exports-and-reports' ); ?></button>
				<a class="button" href="<?php echo esc_url( $this->admin->var_update( array( 'action' => 'manage' ) ) ); ?>"><?php esc_html_e( 'Cancel', 'exports-and-reports' ); ?></a>
			</div>
			<?php
			return;
		}

		if ( ! $this->admin->add && ! $this->admin->export ) {
			return;
		}
		?>
		<div class="alignleft actions">
			<?php if ( $this->admin->add ) { ?>
				<a class="button" href="<?php echo esc_url( $this->admin->var_update( array( 'action' => 'add' ) ) ); ?>">
					<?php
					/* translators: %s: the item's name, as the screen calls it (Report, Report Group). */
					echo esc_html( sprintf( __( 'Add New %s', 'exports-and-reports' ), $this->admin->item ) );
					?>
				</a>
			<?php } ?>

			<?php if ( $this->admin->reorder ) { ?>
				<a class="button" href="<?php echo esc_url( $this->admin->var_update( array( 'action' => 'reorder' ) ) ); ?>"><?php esc_html_e( 'Reorder', 'exports-and-reports' ); ?></a>
			<?php } ?>

			<?php
			$offered = $this->admin->export && $this->has_items() ? $this->admin->export_formats_offered() : array();

			// The buttons submit the export form of the list screen (manage.php), which their form attribute names.
			if ( ! empty( $offered ) ) {
				?>
				<strong><?php esc_html_e( 'Export:', 'exports-and-reports' ); ?></strong>
				<?php foreach ( $offered as $type => $text ) { ?>
					<button type="submit" class="button" form="exports-reports-export" name="export_type" value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $text ); ?></button>
				<?php } ?>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Every export format, the label of each and the export type behind it. The buttons a screen shows are
	 * Exports_Reports_Admin_UI::export_formats_offered(): the formats the site and the report allow.
	 *
	 * @since 1.1.0 The buttons come from the screen; this is the full list.
	 *
	 * @return array<string, string>
	 */
	public static function export_formats() {
		return array_flip( exports_reports_export_formats() );
	}

	/**
	 * The text of the empty table.
	 */
	public function no_items() {
		esc_html_e( 'No items found', 'exports-and-reports' );
	}

	/**
	 * The rows, then the sum row of the count query on the last page.
	 */
	public function display_rows() {
		parent::display_rows();

		$sums = $this->admin->sum_data;

		if ( empty( $sums ) || ! is_array( $sums ) || (int) ceil( $this->admin->total / max( 1, (int) $this->admin->limit ) ) !== (int) $this->admin->page ) {
			return;
		}
		?>
		<tr id="exports-and-reports-sums" class="iedit">
			<?php foreach ( array_keys( $this->shown ) as $column ) { ?>
				<td class="exports-and-reports-sum-<?php echo esc_attr( $column ); ?>">
					<strong><?php echo esc_html( isset( $sums[0][ $column ] ) ? $sums[0][ $column ] : '' ); ?></strong>
				</td>
			<?php } ?>
		</tr>
		<?php
	}

	/**
	 * One row, with the row's identifier in its id.
	 *
	 * @param array $item The row.
	 */
	public function single_row( $item ) {
		$id = ! empty( $item[ $this->admin->identifier ] ) ? $item[ $this->admin->identifier ] : 0;
		?>
		<tr id="item-<?php echo esc_attr( $id ); ?>" class="iedit">
			<?php $this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}

	/**
	 * The cells of a row: the title links to the view or edit screen and carries the row actions (or the
	 * drag handle and the order field while reordering), a date is abbreviated, the others show their
	 * value, each through field_value() and the column's display callback.
	 *
	 * @param array $item The row.
	 */
	protected function single_row_columns( $item ) {
		list( , , , $primary ) = $this->get_column_info();

		$admin = $this->admin;
		$row   = (array) $item;
		$id    = ! empty( $row[ $admin->identifier ] ) ? $row[ $admin->identifier ] : 0;

		foreach ( $this->shown as $column => $attributes ) {
			$value = isset( $row[ $column ] ) ? $row[ $column ] : '';
			$value = $admin->field_value( $value, $column, $attributes );

			if ( false !== $attributes['custom_display'] && function_exists( "{$attributes['custom_display']}" ) ) {
				$value = $attributes['custom_display']( $value, $row, $column, $attributes, $admin );
			}

			$row[ $column ] = $value;
			$value          = (string) $value;
			$kind           = $attributes['kind'];
			$classes        = array( $column, 'column-' . $column );

			if ( 'title' === $kind ) {
				array_push( $classes, 'post-title', 'page-title', 'column-title' );
			} elseif ( 'date' === $kind ) {
				array_push( $classes, 'date', 'column-date' );
			} else {
				array_push( $classes, 'author', 'column-author' );
			}

			if ( $column === $primary ) {
				$classes[] = 'has-row-actions';
				$classes[] = 'column-primary';
			}

			if ( $this->reorder && 'title' === $kind ) {
				$classes[] = 'dragme';
			}
			?>
			<td class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-colname="<?php echo esc_attr( wp_strip_all_tags( (string) $attributes['label'] ) ); ?>">
				<?php
				if ( 'title' === $kind ) {
					$this->title_cell( $row, $column, $id, $value, $column === $primary );
				} elseif ( 'date' === $kind ) {
					?>
					<abbr title="<?php echo esc_attr( $value ); ?>"><?php echo wp_kses( $value, $admin->allowed_html() ); ?></abbr>
					<?php
				} else {
					echo wp_kses( $value, $admin->allowed_html() );
				}
				?>
			</td>
			<?php
		}
	}

	/**
	 * The content of the title cell.
	 *
	 * @param array  $row     The row, its earlier columns formatted.
	 * @param string $column  The column.
	 * @param mixed  $id      The row's identifier.
	 * @param string $value   The formatted value.
	 * @param bool   $primary Whether the column is the primary one, which carries the row actions.
	 */
	protected function title_cell( $row, $column, $id, $value, $primary ) {
		$admin = $this->admin;
		$text  = wp_kses( $value, $admin->allowed_html() );
		$plain = wp_strip_all_tags( $value );

		if ( ! $this->reorder && $admin->view ) {
			$link = $admin->var_update(
				array(
					'action' => 'view',
					'id'     => $id,
				)
			);
			/* translators: %s: the row's title. */
			$title = sprintf( __( 'View &#8220;%s&#8221;', 'exports-and-reports' ), $plain );
		} elseif ( ! $this->reorder && $admin->edit ) {
			$link = $admin->var_update(
				array(
					'action' => 'edit',
					'id'     => $id,
				)
			);
			/* translators: %s: the row's title. */
			$title = sprintf( __( 'Edit &#8220;%s&#8221;', 'exports-and-reports' ), $plain );
		} else {
			$link = '';
		}

		if ( '' !== $link ) {
			?>
			<strong><a class="row-title" href="<?php echo esc_url( $link ); ?>" title="<?php echo esc_attr( $title ); ?>"><?php echo wp_kses_post( $text ); ?></a></strong>
			<?php
		} else {
			?>
			<strong><?php echo wp_kses_post( $text ); ?></strong>
			<?php
		}

		if ( $this->reorder ) {
			?>
			<input type="hidden" name="order[]" value="<?php echo esc_attr( $id ); ?>" />
			<?php
			return;
		}

		if ( isset( $admin->custom['actions_start'] ) && function_exists( "{$admin->custom['actions_start']}" ) ) {
			call_user_func( $admin->custom['actions_start'], $admin, $row );
		}

		if ( $primary ) {
			echo wp_kses_post( $this->handle_row_actions( $row, $column, $column ) );
		}

		if ( isset( $admin->custom['actions_end'] ) && function_exists( "{$admin->custom['actions_end']}" ) ) {
			call_user_func( $admin->custom['actions_end'], $admin, $row );
		}
	}

	/**
	 * The links under the title: view, edit, duplicate, delete (with its nonce and confirmation) and the
	 * custom actions of the screen, through the exports_reports_row_actions filter.
	 *
	 * @param array  $item        The row.
	 * @param string $column_name The column.
	 * @param string $primary     The primary column.
	 *
	 * @return string The row actions, with core's toggle of the row's details on a narrow screen.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $column_name !== $primary ) {
			return '';
		}

		$admin   = $this->admin;
		$id      = ! empty( $item[ $admin->identifier ] ) ? $item[ $admin->identifier ] : 0;
		$plain   = wp_strip_all_tags( (string) $item[ $column_name ] );
		$actions = array();

		if ( $admin->view ) {
			$actions['view'] = '<a href="' . esc_url(
				$admin->var_update(
					array(
						'action' => 'view',
						'id'     => $id,
					)
				)
			) . '" title="' . esc_attr__( 'View this item', 'exports-and-reports' ) . '">' . esc_html__( 'View', 'exports-and-reports' ) . '</a>';
		}

		if ( $admin->edit ) {
			$actions['edit'] = '<a href="' . esc_url(
				$admin->var_update(
					array(
						'action' => 'edit',
						'id'     => $id,
					)
				)
			) . '" title="' . esc_attr__( 'Edit this item', 'exports-and-reports' ) . '">' . esc_html__( 'Edit', 'exports-and-reports' ) . '</a>';
		}

		if ( $admin->duplicate ) {
			$actions['duplicate'] = '<a href="' . esc_url(
				$admin->var_update(
					array(
						'action' => 'duplicate',
						'id'     => $id,
					)
				)
			) . '" title="' . esc_attr__( 'Duplicate this item', 'exports-and-reports' ) . '">' . esc_html__( 'Duplicate', 'exports-and-reports' ) . '</a>';
		}

		if ( $admin->delete ) {
			/* translators: %s: the item's title, as the row shows it. */
			$confirm = sprintf( __( 'You are about to delete "%s". Cancel to stop, OK to delete.', 'exports-and-reports' ), $plain );
			$link    = $admin->var_update(
				array(
					'action'   => 'delete',
					'id'       => $id,
					'_wpnonce' => wp_create_nonce( 'exports-reports-delete' ),
				)
			);

			$actions['delete'] = '<a class="submitdelete" title="' . esc_attr__( 'Delete this item', 'exports-and-reports' ) . '" href="' . esc_url( $link ) . '" data-exports-reports-confirm="' . esc_attr( $confirm ) . '">' . esc_html__( 'Delete', 'exports-and-reports' ) . '</a>';
		}

		if ( is_array( $admin->custom ) ) {
			$reserved = array( 'add', 'view', 'edit', 'duplicate', 'delete', 'save', 'readonly', 'export', 'reorder' );

			foreach ( $admin->custom as $custom_action => $custom_data ) {
				if ( ! is_array( $custom_data ) || ! isset( $custom_data['link'] ) || in_array( $custom_action, $reserved, true ) ) {
					continue;
				}

				if ( ! isset( $custom_data['label'] ) ) {
					$custom_data['label'] = ucwords( str_replace( '_', ' ', $custom_action ) );
				}

				/* translators: %s: the action's label (Download, Run, ...). */
				$title = sprintf( __( '%s this item', 'exports-and-reports' ), wp_strip_all_tags( (string) $custom_data['label'] ) );

				$actions[ $custom_action ] = '<a href="' . esc_url( $admin->parse_template_string( $custom_data['link'], $item ) ) . '" title="' . esc_attr( $title ) . '">' . wp_kses_post( $custom_data['label'] ) . '</a>';
			}
		}

		/**
		 * Filters the links under a row's title.
		 *
		 * @since 1.0.0 The links alone, by action, without the span around each; the row is passed.
		 *
		 * @param string[]                $actions The links, HTML, by action.
		 * @param array                   $row     The row.
		 * @param Exports_Reports_Admin_UI $admin   The screen.
		 */
		$actions = apply_filters( 'exports_reports_row_actions', $actions, $item, $admin );
		$actions = $admin->legacy_hook( 'row_actions', array( $actions, $item ) );

		return $this->row_actions( (array) $actions ); // With the toggle of the row's details on a narrow screen.
	}
}
