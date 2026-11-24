<?php
/**
 * The list screen: the heading, the header callback, the export message, the filters and the search, the
 * table (the reorder form on the reorder screen) and the totals.
 *
 * @package Exports_And_Reports
 * @since   1.0.0
 *
 * @var Exports_Reports_Admin_UI $this    The screen.
 * @var int                      $reorder 1 on the reorder screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included from a method of Exports_Reports_Admin_UI: the variables are local to that call, not globals.

$reordering   = 1 === $reorder && false !== $this->reorder;
$searchable   = ( ! empty( $this->data ) || false !== $this->search_query || false !== $this->default_none ) && $this->has_search_fields();
$screen_title = ( $reordering ? $this->heading['reorder'] : $this->heading['manage'] ) . ' ' . $this->items;
$back         = $reordering;

?>
<div class="wrap wp-admin-ui-wrap">
	<?php
	require __DIR__ . '/heading.php';

	if ( isset( $this->custom['header'] ) && function_exists( "{$this->custom['header']}" ) ) {
		echo wp_kses( (string) call_user_func( $this->custom['header'], $this ), $this->allowed_html() );
	}

	if ( false !== $this->export ) {
		// The export buttons of the table submit the first form, which their form attribute names, and the button
		// under an export's message submits the second, which removes the file: POSTs, which no browser keeps
		// as an address. The screen's script sends them in the background and shows the answer.
		?>
		<form id="exports-reports-export" class="exports-reports-export" method="post" action="<?php echo esc_url( $this->export_form_action() ); ?>" data-ajax="<?php echo esc_url( $this->export_ajax_url() ); ?>" data-preparing="<?php esc_attr_e( 'Preparing your export…', 'exports-and-reports' ); ?>">
			<input type="hidden" name="exports_reports_export" value="1" />
			<?php if ( 0 < (int) $this->report_id ) : ?>
				<input type="hidden" name="report" value="<?php echo esc_attr( (string) (int) $this->report_id ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( 'exports-reports-export-request', '_wpnonce', false ); ?>
		</form>
		<form id="exports-reports-remove-export" class="exports-reports-export" method="post" action="<?php echo esc_url( $this->export_form_action() ); ?>" data-ajax="<?php echo esc_url( $this->export_ajax_url() ); ?>" data-preparing="<?php esc_attr_e( 'Removing the export…', 'exports-and-reports' ); ?>">
			<input type="hidden" name="exports_reports_export" value="1" />
			<?php if ( 0 < (int) $this->report_id ) : ?>
				<input type="hidden" name="report" value="<?php echo esc_attr( (string) (int) $this->report_id ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( 'exports-reports-export-request', '_wpnonce', false ); ?>
		</form>
		<?php
	}

	if ( false !== $this->export && 'export' === $this->action && $this->export_from_address ) {
		// A link from before 1.1.0 (or a removal link from before 1.2.0), opened again from the history or a
		// bookmark: the export it named was written then; another one starts from the buttons.
		$this->message( __( 'This address pointed at an export that was written when its link was made. Exports start from the Export buttons, which leave the address as it is.', 'exports-and-reports' ) );
	} elseif ( false !== $this->export && 'export' === $this->action && '' !== $this->removing_export() ) {
		// The button under an export's message, when the screen's script did not send it in the background.
		$this->export();
	} elseif ( false !== $this->export && 'export' === $this->action ) {
		// The file is written during this request. A note for the wait, sent ahead when the server lets it
		// through; the screen's script removes it once the page, and with it the export, is done.
		?>
		<div class="notice notice-info exports-reports-progress"><p><span class="spinner is-active"></span><?php esc_html_e( 'Preparing your export…', 'exports-and-reports' ); ?></p></div>
		<?php
		if ( 0 < ob_get_level() ) {
			ob_flush();
		}
		flush();

		$this->export();
	}

	if ( $reordering ) {
		if ( $searchable ) {
			?>
			<form id="posts-filter" action="" method="get">
				<p class="search-box exports-reports-filters">
					<?php require __DIR__ . '/list-filters.php'; ?>
				</p>
			</form>
			<?php
		} else {
			?>
			<br class="clear" />
			<?php
		}
		require __DIR__ . '/list-body.php'; // The reorder form is a form of its own, after the search form.
	} else {
		// The table's form: the search box and the filters when there is something to search, and in any case
		// the page field of the pagination, with the request's other values carried along.
		?>
		<form id="posts-filter" action="" method="get">
			<?php if ( $searchable ) { ?>
				<p class="search-box exports-reports-filters">
					<?php require __DIR__ . '/list-filters.php'; ?>
				</p>
			<?php } else { ?>
				<?php $this->hidden_vars( array( 'pg' . $this->num ) ); ?>
			<?php } ?>
			<?php require __DIR__ . '/list-body.php'; ?>
		</form>
		<?php
	}

	if ( ! empty( $this->data ) && 1 !== $reorder && false !== $this->totals ) {
		?>
		<h3 class="admin_ui_totals_heading"><?php esc_html_e( 'Totals', 'exports-and-reports' ); ?></h3>
		<table class="widefat fixed admin_ui_table admin_ui_totals" cellspacing="0">
			<thead>
			<tr>
				<?php
				$columns = array();
				foreach ( $this->columns as $column => $attributes ) {
					if ( ! isset( $this->totals[ $column ] ) ) {
						continue;
					}
					$label = ucwords( str_replace( '_', ' ', $column ) );
					if ( false !== $this->get_var( 'label', false, false, $attributes ) ) {
						$label = $attributes['label'];
					}
					?>
					<th scope="col" class="manage-column"><?php echo wp_kses_post( $label ); ?></th>
					<?php
					if ( 'number' === $attributes['type'] ) {
						$total = (int) $this->totals[ $column ];
					} elseif ( 'decimal' === $attributes['type'] ) {
						$total = number_format( $this->totals[ $column ], 2 );
					} else {
						$total = $this->totals[ $column ];
					}
					$columns[ $column ] = $total;
				}
				?>
			</tr>
			</thead>
			<tbody>
			<tr>
				<?php
				foreach ( $columns as $column => $total ) {
					?>
					<td><?php echo esc_html( $total ); ?></td>
					<?php
				}
				?>
			</tr>
			</tbody>
		</table>
		<?php
	}

	// The note printed while the report loaded (reports.php) goes as soon as the report is in the page, whatever
	// else the page is still loading; the screen's script removes it as well, once the page has loaded.
	wp_print_inline_script_tag( 'document.querySelectorAll( ".exports-reports-loading, .exports-reports-progress" ).forEach( function ( note ) { note.parentNode.removeChild( note ); } );' );
	?>
</div>
